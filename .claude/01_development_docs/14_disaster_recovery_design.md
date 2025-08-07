# 障害復旧設計書

## 1. 概要

### 1.1 設計目的
クラウドサーバー障害時にオンプレシステム単独で営業継続し、復旧後にデータ同期を行う機能の設計。

### 1.2 対象障害
- **クラウドサーバー完全ダウン**
- **ネットワーク障害**（オンプレ→クラウド間通信不通）

### 1.3 システム構成（正しい理解）
```
【店舗内システム】
ハンディ端末(Android) ←→ POS端末(Windows+Delphi+FireBird) ←→ クラウド(Laravel+MySQL)
     ↑                            ↑                                    ↑
  注文入力のみ                データ管理中枢                    Web注文受付
 クラウド通信なし              唯一のクラウド通信                 POS連携のみ
```

### 1.4 基本方針
```
平常時: POS端末 ⟷ クラウド（ポーリング同期）
障害時: POS端末単独運用（ハンディ注文継続）
復旧時: POS端末 → クラウド（未同期データ送信）
```

## 2. 障害時の動作パターン

### 2.1 正常時の動作

#### スマホ注文フロー
```
1. お客様スマホ → クラウド → ordersテーブル
2. POS端末 → change_logsポーリング → 新規注文検知
3. POS端末 → FireBird order_managementテーブルに書き込み
4. 厨房印字・調理・提供・会計
```

#### ハンディ注文フロー
```
1. ハンディ端末 → POS端末 → FireBird order_managementテーブルに書き込み
2. 厨房印字・調理・提供・会計
```

### 2.2 障害時の動作

#### 影響を受ける処理
```
❌ スマホ注文: クラウドが落ちているため不可
❌ QRコード生成: クラウド連携が必要なため不可
```

#### 影響を受けない処理
```
✅ ハンディ注文: POS端末直結のため正常動作
✅ 厨房処理: FireBirdベースなので正常動作  
✅ 会計処理: ローカル完結なので正常動作
```

#### 代替運用
```
お客様: 「スマホで注文したい」
スタッフ: 「システム都合でお伺いします」
        ↓
    ハンディで代行注文
        ↓
   通常の厨房・会計フロー
```

## 3. 実装設計（最小変更）

### 3.1 FireBirdテーブル変更

#### 既存テーブルへの最小変更
```sql
-- 注文管理テーブルに1つのフラグ追加のみ
ALTER TABLE order_management ADD cloud_synced CHAR(1) DEFAULT 'Y';
-- Y = 同期済み（スマホ注文 or 正常時ハンディ注文）
-- N = 未同期（障害時ハンディ注文）

CREATE INDEX idx_order_management_cloud_synced ON order_management (cloud_synced);
```

### 3.2 Delphiでの実装

#### 障害検知
```pascal
// POS端末でのヘルスチェック（1分間隔）
procedure TMainForm.TimerHealthCheckTimer(Sender: TObject);
begin
  if TestCloudConnection then
  begin
    if not FCloudOnline then
    begin
      FCloudOnline := True;
      StatusPanel.Text := '正常稼働';
      TimerSync.Enabled := True; // 同期開始
    end;
  end else
  begin
    if FCloudOnline then
    begin
      FCloudOnline := False;
      StatusPanel.Text := 'オフライン';
      WriteLog('クラウド接続断検知');
    end;
  end;
end;

function TestCloudConnection: Boolean;
var
  HTTP: THTTPClient;
begin
  try
    HTTP := THTTPClient.Create;
    try
      Result := HTTP.Get('https://cloud-api.mobile-order.com/health').StatusCode = 200;
    finally
      HTTP.Free;
    end;
  except
    Result := False;
  end;
end;
```

#### ハンディ注文処理
```pascal
// ハンディからの注文受信（既存処理にフラグ追加のみ）
procedure ReceiveHandyOrder(TableNo, ItemName: string; Amount: Currency);
var
  Query: TFDQuery;
begin
  Query := TFDQuery.Create(nil);
  try
    Query.Connection := FDConnection;
    Query.SQL.Text := 
      'INSERT INTO order_management (table_number, item_name, total_amount, cloud_synced) ' +
      'VALUES (:table, :item, :amount, :synced)';
      
    Query.ParamByName('table').AsString := TableNo;
    Query.ParamByName('item').AsString := ItemName;
    Query.ParamByName('amount').AsCurrency := Amount;
    
    // 障害時は未同期マーク
    if FCloudOnline then
      Query.ParamByName('synced').AsString := 'Y'
    else
      Query.ParamByName('synced').AsString := 'N';
      
    Query.ExecSQL;
    
    // 既存の厨房印字処理
    PrintToKitchen(Query.Connection.GetLastAutoGenValue);
    
  finally
    Query.Free;
  end;
end;
```

#### 復旧時の同期処理
```pascal
// 復旧時の自動同期（10分間隔）
procedure TMainForm.TimerSyncTimer(Sender: TObject);
var
  UnsyncedCount: Integer;
begin
  if FCloudOnline then
  begin
    UnsyncedCount := GetUnsyncedOrderCount;
    if UnsyncedCount > 0 then
    begin
      StatusPanel.Text := Format('同期中...(%d件)', [UnsyncedCount]);
      if SyncUnsyncedOrders then
        StatusPanel.Text := '同期完了'
      else
        StatusPanel.Text := '同期エラー';
    end else
    begin
      StatusPanel.Text := '正常稼働';
      TimerSync.Enabled := False; // 同期完了
    end;
  end;
end;

function GetUnsyncedOrderCount: Integer;
var
  Query: TFDQuery;
begin
  Query := TFDQuery.Create(nil);
  try
    Query.Connection := FDConnection;
    Query.SQL.Text := 'SELECT COUNT(*) as cnt FROM order_management WHERE cloud_synced = ''N''';
    Query.Open;
    Result := Query.FieldByName('cnt').AsInteger;
  finally
    Query.Free;
  end;
end;

function SyncUnsyncedOrders: Boolean;
var
  Query: TFDQuery;
  OrdersArray: TJSONArray;
  SyncData: TJSONObject;
  HTTP: THTTPClient;
  Response: IHTTPResponse;
begin
  Result := False;
  
  // 未同期注文を取得
  Query := TFDQuery.Create(nil);
  try
    Query.Connection := FDConnection;
    Query.SQL.Text := 
      'SELECT order_id, table_number, item_name, total_amount, created_at ' +
      'FROM order_management WHERE cloud_synced = ''N''';
    Query.Open;
    
    if Query.RecordCount = 0 then
    begin
      Result := True;
      Exit;
    end;
    
    // JSON配列作成
    OrdersArray := TJSONArray.Create;
    try
      while not Query.Eof do
      begin
        with TJSONObject.Create do
        begin
          AddPair('order_id', Query.FieldByName('order_id').AsString);
          AddPair('table_number', Query.FieldByName('table_number').AsString);
          AddPair('item_name', Query.FieldByName('item_name').AsString);
          AddPair('total_amount', TJSONNumber.Create(Query.FieldByName('total_amount').AsCurrency));
          AddPair('ordered_at', DateTimeToISO8601(Query.FieldByName('created_at').AsDateTime));
          AddPair('status', 'completed');
          
          OrdersArray.AddElement(Self);
        end;
        Query.Next;
      end;
      
      // API送信
      SyncData := TJSONObject.Create;
      try
        SyncData.AddPair('sync_batch_id', FormatDateTime('yyyymmdd_hhnnss', Now));
        SyncData.AddPair('orders', OrdersArray);
        
        HTTP := THTTPClient.Create;
        try
          Response := HTTP.Post('https://cloud-api.mobile-order.com/api/v1/pos/sync-handy-orders',
                               TStringStream.Create(SyncData.ToString),
                               nil, TNetHeaders.Create(TNetHeader.Create('Content-Type', 'application/json')));
          
          if Response.StatusCode = 200 then
          begin
            MarkOrdersAsSynced;
            Result := True;
          end;
          
        finally
          HTTP.Free;
        end;
        
      finally
        SyncData.Free;
      end;
      
    finally
      OrdersArray.Free;
    end;
    
  finally
    Query.Free;
  end;
end;

procedure MarkOrdersAsSynced;
var
  Query: TFDQuery;
begin
  Query := TFDQuery.Create(nil);
  try
    Query.Connection := FDConnection;
    Query.SQL.Text := 'UPDATE order_management SET cloud_synced = ''Y'' WHERE cloud_synced = ''N''';
    Query.ExecSQL;
  finally
    Query.Free;
  end;
end;
```

## 4. Laravel側の実装

### 4.1 ハンディ注文同期API

#### ルート定義
```php
// routes/api.php
Route::post('/pos/sync-handy-orders', [PosController::class, 'syncHandyOrders'])
    ->middleware(['auth:sanctum', 'throttle:pos-api']);
```

#### コントローラー実装
```php
// app/Http/Controllers/Api/PosController.php
public function syncHandyOrders(Request $request)
{
    $validated = $request->validate([
        'sync_batch_id' => 'required|string',
        'orders' => 'required|array|min:1',
        'orders.*.order_id' => 'required|string',
        'orders.*.table_number' => 'required|string',
        'orders.*.item_name' => 'required|string',
        'orders.*.total_amount' => 'required|numeric|min:0',
        'orders.*.ordered_at' => 'required|date',
        'orders.*.status' => 'required|in:completed'
    ]);
    
    $syncedCount = 0;
    $errors = [];
    
    DB::beginTransaction();
    try {
        foreach ($validated['orders'] as $orderData) {
            try {
                $this->createHandyProxyOrder($orderData);
                $syncedCount++;
            } catch (Exception $e) {
                $errors[] = [
                    'order_id' => $orderData['order_id'],
                    'error' => $e->getMessage()
                ];
            }
        }
        
        if (empty($errors)) {
            DB::commit();
            Log::info('Handy orders synced successfully', [
                'batch_id' => $validated['sync_batch_id'],
                'count' => $syncedCount
            ]);
            
            return response()->json([
                'success' => true,
                'synced_count' => $syncedCount,
                'batch_id' => $validated['sync_batch_id']
            ]);
        } else {
            DB::rollback();
            return response()->json([
                'success' => false,
                'synced_count' => $syncedCount,
                'errors' => $errors
            ], 422);
        }
        
    } catch (Exception $e) {
        DB::rollback();
        Log::error('Handy orders sync failed', [
            'batch_id' => $validated['sync_batch_id'],
            'error' => $e->getMessage()
        ]);
        
        return response()->json([
            'success' => false,
            'error' => 'データベースエラーが発生しました'
        ], 500);
    }
}

private function createHandyProxyOrder(array $orderData): Order
{
    // 1. 疑似セッション作成（存在しない場合）
    $session = Session::firstOrCreate([
        'qr_code' => 'HANDY_TABLE_' . $orderData['table_number'],
        'store_id' => auth()->user()->store_id
    ], [
        'table_number' => $orderData['table_number'],
        'customer_count' => 1,
        'status' => 'completed',
        'expires_at' => now()->addDay(),
        'started_at' => Carbon::parse($orderData['ordered_at']),
        'completed_at' => now()
    ]);
    
    // 2. 疑似ゲスト情報生成
    $guestToken = 'handy_' . $orderData['order_id'];
    $deviceFingerprint = 'pos_handy_table_' . $orderData['table_number'];
    
    // 3. 重複チェック
    $existingOrder = Order::where('order_number', 'HANDY_' . $orderData['order_id'])->first();
    if ($existingOrder) {
        throw new Exception('Order already exists: ' . $orderData['order_id']);
    }
    
    // 4. 注文作成
    $order = Order::create([
        'store_id' => auth()->user()->store_id,
        'session_id' => $session->id,
        'guest_token' => $guestToken,
        'device_fingerprint' => $deviceFingerprint,
        'order_number' => 'HANDY_' . $orderData['order_id'],
        'status' => $orderData['status'],
        'total_amount' => $orderData['total_amount'],
        'ordered_at' => Carbon::parse($orderData['ordered_at']),
        'completed_at' => now(),
        'notes' => 'ハンディ代行注文（オフライン同期）'
    ]);
    
    // 5. 簡単な注文明細
    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $this->findOrCreateSimpleProduct($orderData['item_name']),
        'quantity' => 1,
        'unit_price' => $orderData['total_amount'],
        'total_price' => $orderData['total_amount'],
        'notes' => 'ハンディ注文'
    ]);
    
    // 6. 変更ログ記録
    ChangeLog::create([
        'entity_type' => 'orders',
        'entity_id' => $order->id,
        'action' => 'created',
        'sync_source' => 'onpremise',
        'user_type' => 'handy_system',
        'user_id' => auth()->id()
    ]);
    
    return $order;
}

private function findOrCreateSimpleProduct(string $itemName): int
{
    // 商品名で検索、なければ「その他」商品として登録
    $product = Product::where('name', $itemName)
        ->where('store_id', auth()->user()->store_id)
        ->first();
        
    if (!$product) {
        $product = Product::create([
            'store_id' => auth()->user()->store_id,
            'code' => 'HANDY_' . Str::slug($itemName),
            'name' => $itemName,
            'description' => 'ハンディ注文商品',
            'price' => 0, // 価格は注文時に決定
            'tax_in_price' => 0,
            'tax_type' => 'standard',
            'availability_status' => 'available',
            'is_active' => true
        ]);
    }
    
    return $product->id;
}
```

## 5. 具体的な障害復旧シナリオ

### 5.1 障害発生から復旧までの流れ

#### Phase 1: 正常運用中（12:00）
```
- テーブル1: 田中さんがスマホで醤油ラーメン注文 → クラウド経由で処理
- テーブル2: 佐藤さんがスマホで味噌ラーメン注文 → クラウド経由で処理
```

#### Phase 2: 障害発生（12:15）
```
- POS端末: ヘルスチェックでクラウド接続断を検知
- ステータス: 「オフライン」表示
- 影響: スマホ注文不可、QRコード生成不可
```

#### Phase 3: 代替運用（12:20〜14:30）
```
12:20 - テーブル3: 山田さん「スマホで注文したい」
        → スタッフがハンディで代行注文「塩ラーメン ¥900」
        → FireBird: cloud_synced='N' でマーク
        → 厨房印字・調理・提供・会計（通常通り）

12:35 - テーブル4: 鈴木さんもハンディで代行注文「つけ麺 ¥1,200」
        → 同様にcloud_synced='N'

... 計8件の代行注文が蓄積
```

#### Phase 4: 復旧・同期（14:30）
```
14:30 - POS端末: ヘルスチェックでクラウド復旧検知
        → ステータス: 「同期中...(8件)」
        → 未同期注文をJSON形式でクラウドに送信

14:32 - Laravel: 疑似セッション・トークン生成
        → ordersテーブルに8件挿入
        → レスポンス: 同期完了

14:33 - POS端末: cloud_synced='Y'に更新
        → ステータス: 「正常稼働」
```

### 5.2 実装後の効果

#### 障害時の営業継続性
- **売上機会損失**: ほぼゼロ（スタッフ代行で対応）
- **顧客満足度**: 軽微な影響（注文方法変更のみ）
- **運営負荷**: 低い（慣れたハンディ操作）

#### データ整合性
- **データロス**: なし（全て同期される）
- **重複注文**: なし（重複チェック実装）
- **売上データ**: 正確（全注文がクラウドに記録）

## 6. 実装規模・工数

### 6.1 変更規模

#### FireBird側
- **テーブル変更**: 1カラム追加 + インデックス1つ
- **Delphiコード**: 約150行追加（タイマー処理・同期処理）
- **既存への影響**: 最小限（注文処理に1行追加のみ）

#### Laravel側
- **新規API**: 1エンドポイント
- **新規コード**: 約200行（バリデーション・疑似データ生成・DB操作）
- **既存への影響**: なし（完全に独立した機能）

#### 総工数見積もり
- **設計・実装**: 3-5日
- **テスト**: 2-3日
- **運用準備**: 1日
- **合計**: 6-9日（1-2週間）

### 6.2 テスト項目
1. **障害検知テスト**: クラウドサーバー停止時の自動検知
2. **代行注文テスト**: ハンディ注文のcloud_syncedフラグ設定
3. **復旧検知テスト**: クラウド復旧時の自動同期開始
4. **同期処理テスト**: 未同期データの正確な送信・受信
5. **疑似データ生成テスト**: セッション・トークン・フィンガープリント生成
6. **重複防止テスト**: 同じ注文の重複同期防止
7. **エラーハンドリング**: 同期失敗時の適切な処理

## 7. 運用手順

### 7.1 障害発生時
```
1. POS端末画面で「オフライン」表示を確認
2. スタッフに「スマホ注文不可、ハンディで代行」を周知
3. 通常通りハンディ注文で営業継続
```

### 7.2 復旧時
```
1. POS端末画面で「同期中...(X件)」表示を確認
2. 同期完了まで待機（通常2-3分）
3. 「正常稼働」表示でスマホ注文再開を周知
```

### 7.3 トラブル時
```
同期エラーが発生した場合:
1. POS端末再起動
2. クラウド接続確認
3. 手動同期実行（管理画面）
```

---

## まとめ

この障害復旧設計により、**最小限の変更で最大限の効果**を実現できます：

- **シンプル**: FireBirdにフラグ1つ追加のみ
- **確実**: 全ての注文が確実に同期される  
- **低コスト**: 短期間・少工数で実装可能
- **運用容易**: 自動化により運用負荷最小限
- **拡張可能**: 将来的な機能追加にも対応

実際のPOS環境（Delphi + FireBird）の制約を考慮し、現実的で実装しやすい設計となっています。

### 3.3 同期API拡張

#### 差分同期エンドポイント
```php
// オンプレ → クラウド
POST /api/v1/pos/sync/orders-batch
{
  "sync_batch_id": "SYNC_20240101_001",
  "offline_period": {
    "start": "2024-01-01T10:00:00+09:00",
    "end": "2024-01-01T14:00:00+09:00"
  },
  "orders": [
    {
      "order_id": "P2024010112345001",
      "order_data": {...},
      "order_items": [...],
      "session_data": {...},
      "created_at": "2024-01-01T12:00:00+09:00"
    }
  ]
}
```

## 4. 復旧時の差分同期手順

### 4.1 同期フェーズ

#### Phase 1: 接続復旧確認
```php
class RecoveryProcess
{
    public function startRecovery(): bool
    {
        // 1. クラウド接続確認
        if (!$this->healthChecker->checkConnection()) {
            return false;
        }
        
        // 2. システムモード切り替え
        SystemStatus::updateMode(SystemMode::SYNCING);
        
        // 3. 未同期データ数確認
        $pendingCount = OfflineOrder::where('sync_status', 'pending')->count();
        Log::info("Starting recovery process", ['pending_orders' => $pendingCount]);
        
        return true;
    }
}
```

#### Phase 2: 差分データ送信
```php
public function syncOfflineOrders(): array
{
    $syncBatchId = 'SYNC_' . date('Ymd_His');
    $offlineOrders = OfflineOrder::where('sync_status', 'pending')
        ->orderBy('created_at')
        ->get();
    
    $results = [];
    foreach ($offlineOrders->chunk(10) as $chunk) {
        $response = $this->sendOrdersBatch($syncBatchId, $chunk);
        $results[] = $this->processResponse($response, $chunk);
    }
    
    return $results;
}
```

#### Phase 3: 整合性確認
```php
public function verifyConsistency(): bool
{
    // 1. 席セッション状態の突合
    $sessionMismatches = $this->compareSessionStates();
    
    // 2. 注文データの整合性チェック  
    $orderMismatches = $this->compareOrderData();
    
    // 3. 不整合がある場合は手動解決待ち
    if (!empty($sessionMismatches) || !empty($orderMismatches)) {
        $this->flagForManualResolution($sessionMismatches, $orderMismatches);
        return false;
    }
    
    return true;
}
```

### 4.2 競合解決ルール

#### 自動解決ルール
```php
class ConflictResolver
{
    public function resolveOrderConflict($cloudOrder, $onpremiseOrder): array
    {
        // ルール1: より新しいタイムスタンプを優先
        if ($onpremiseOrder['updated_at'] > $cloudOrder['updated_at']) {
            return ['resolution' => 'onpremise_wins', 'data' => $onpremiseOrder];
        }
        
        // ルール2: ステータスが進んでいる方を優先
        $statusPriority = ['pending' => 1, 'confirmed' => 2, 'preparing' => 3, 'ready' => 4, 'completed' => 5];
        if ($statusPriority[$onpremiseOrder['status']] > $statusPriority[$cloudOrder['status']]) {
            return ['resolution' => 'onpremise_wins', 'data' => $onpremiseOrder];
        }
        
        // デフォルト: クラウド優先
        return ['resolution' => 'cloud_wins', 'data' => $cloudOrder];
    }
}
```

### 4.3 同期完了処理
```php
public function completeRecovery(): void
{
    // 1. 全データ同期完了確認
    $pendingCount = OfflineOrder::where('sync_status', 'pending')->count();
    if ($pendingCount > 0) {
        throw new RecoveryException("Still has pending orders: {$pendingCount}");
    }
    
    // 2. システムモードを正常に戻す
    SystemStatus::updateMode(SystemMode::ONLINE);
    
    // 3. オフライン期間のログ記録
    $this->logRecoveryCompletion();
    
    Log::info('Recovery process completed successfully');
}
```

## 5. 技術実装詳細

### 5.1 FireBird対応のデータ操作

#### JSON文字列の処理（Delphi + FireBird実装）
```pascal
// データ挿入時（FireBird）
procedure InsertOfflineOrder(const OrderId: string; const OrderData: TJSONObject);
var
  Query: TFDQuery;
  SyncData: TJSONObject;
begin
  Query := TFDQuery.Create(nil);
  try
    Query.Connection := FDConnection;
    
    // JSON形式の同期データ作成
    SyncData := TJSONObject.Create;
    try
      SyncData.AddPair('guest_info', '代行注文');
      SyncData.AddPair('staff_id', TJSONNumber.Create(123));
      SyncData.AddPair('reason', 'cloud_outage');
      
      Query.SQL.Text := 
        'INSERT INTO order_management (order_id, menu_items, sync_data, cloud_synced) ' +
        'VALUES (:order_id, :order_data, :sync_data, :synced)';
        
      Query.ParamByName('order_id').AsString := OrderId;
      Query.ParamByName('order_data').AsString := OrderData.ToString;
      Query.ParamByName('sync_data').AsString := SyncData.ToString;
      Query.ParamByName('synced').AsString := 'N'; // 未同期
      
      Query.ExecSQL;
      
    finally
      SyncData.Free;
    end;
  finally
    Query.Free;
  end;
end;

// データ検索時（部分文字列検索）
function GetOfflineOrdersByReason(const Reason: string): TFDQuery;
var
  Query: TFDQuery;
begin
  Query := TFDQuery.Create(nil);
  Query.Connection := FDConnection;
  Query.SQL.Text := 
    'SELECT * FROM order_management ' +
    'WHERE sync_data CONTAINING :reason_pattern';
  Query.ParamByName('reason_pattern').AsString := Format('"reason":"%s"', [Reason]);
  Query.Open;
  Result := Query;
end;

// JSON解析
function ParseSyncData(const SyncDataStr: string): Integer;
var
  SyncData: TJSONObject;
  StaffIdValue: TJSONValue;
begin
  Result := 0;
  SyncData := TJSONObject.ParseJSONValue(SyncDataStr) as TJSONObject;
  try
    if Assigned(SyncData) then
    begin
      StaffIdValue := SyncData.GetValue('staff_id');
      if Assigned(StaffIdValue) then
        Result := StaffIdValue.GetValue<Integer>;
    end;
  finally
    if Assigned(SyncData) then
      SyncData.Free;
  end;
end;
```

#### ENUM代替のステータス管理（Delphi実装）
```pascal
// ステータス定数定義
type
  TSyncStatus = class
  public
    const PENDING = 'pending';
    const SYNCING = 'syncing';
    const COMPLETED = 'completed';
    const FAILED = 'failed';
    
    class function GetValidStatuses: TArray<string>; static;
  end;

class function TSyncStatus.GetValidStatuses: TArray<string>;
begin
  SetLength(Result, 4);
  Result[0] := PENDING;
  Result[1] := SYNCING;
  Result[2] := COMPLETED;
  Result[3] := FAILED;
end;

// ステータス更新
procedure UpdateSyncStatus(const OrderId: string; const NewStatus: string);
var
  Query: TFDQuery;
begin
  Query := TFDQuery.Create(nil);
  try
    Query.Connection := FDConnection;
    Query.SQL.Text := 
      'UPDATE order_management SET cloud_synced = :status WHERE order_id = :order_id';
    Query.ParamByName('status').AsString := NewStatus;
    Query.ParamByName('order_id').AsString := OrderId;
    Query.ExecSQL;
  finally
    Query.Free;
  end;
end;
```

### 5.2 データベーストランザクション管理

#### 原子性保証
```php
public function syncOrderWithConsistency($orderData): bool
{
    DB::beginTransaction();
    try {
        // 1. 注文作成
        $order = Order::create($orderData['order']);
        
        // 2. 注文明細作成
        foreach ($orderData['items'] as $itemData) {
            OrderItem::create(array_merge($itemData, ['order_id' => $order->id]));
        }
        
        // 3. change_log記録
        ChangeLog::create([
            'entity_type' => 'orders',
            'entity_id' => $order->id,
            'action' => 'created',
            'sync_source' => 'onpremise',
            'sync_batch_id' => $orderData['sync_batch_id']
        ]);
        
        DB::commit();
        return true;
        
    } catch (Exception $e) {
        DB::rollBack();
        Log::error('Order sync failed', ['error' => $e->getMessage(), 'order_data' => $orderData]);
        return false;
    }
}
```

### 5.2 べき等性保証

#### 重複処理防止
```php
public function createOrderIdempotent($orderData): ?Order
{
    // 既存チェック（order_numberで）
    $existing = Order::where('order_number', $orderData['order_number'])->first();
    if ($existing) {
        Log::info('Order already exists', ['order_number' => $orderData['order_number']]);
        return $existing;
    }
    
    // 新規作成
    return Order::create($orderData);
}
```

### 5.3 エラーハンドリング

#### 同期エラー対応
```php
public function handleSyncError($error, $orderData): void
{
    // 1. エラー記録
    OfflineOrder::where('order_id', $orderData['order_id'])
        ->update([
            'sync_status' => 'failed',
            'sync_attempts' => DB::raw('sync_attempts + 1'),
            'sync_error' => $error->getMessage()
        ]);
    
    // 2. 重試行判定
    $attempts = OfflineOrder::where('order_id', $orderData['order_id'])->value('sync_attempts');
    if ($attempts >= 3) {
        // 手動解決待ちにマーク
        $this->flagForManualIntervention($orderData['order_id'], $error);
    } else {
        // 再試行スケジュール
        $this->scheduleRetry($orderData['order_id'], $attempts);
    }
}
```

## 6. 運用手順

### 6.1 障害発生時対応

```bash
# 1. システム状態確認
php artisan system:status

# 2. オフラインモード切り替え（自動 or 手動）
php artisan system:switch-offline

# 3. 営業継続確認
php artisan system:health-check --offline
```

### 6.2 復旧時対応

```bash
# 1. 接続復旧確認
php artisan cloud:health-check

# 2. 復旧プロセス開始
php artisan recovery:start

# 3. 進捗監視
php artisan recovery:status

# 4. 手動解決（必要時）
php artisan recovery:resolve-conflicts

# 5. 復旧完了
php artisan recovery:complete
```

### 6.3 監視ポイント

#### ダッシュボード項目
- システム稼働モード（online/offline/syncing）
- 未同期データ数
- 最後の同期時刻
- エラー発生状況
- 手動解決待ちデータ数

## 7. テスト設計

### 7.1 障害シミュレーション

```php
class DisasterRecoveryTest extends TestCase
{
    /** @test */
    public function システムは_クラウド障害時に_オフラインモードに切り替わる()
    {
        // クラウド接続を模擬的に遮断
        Http::fake(['*' => Http::response(null, 500)]);
        
        // ヘルスチェック実行
        $checker = new CloudHealthChecker();
        $this->assertFalse($checker->checkConnection());
        
        // オフラインモード切り替え
        $checker->switchToOfflineMode();
        
        // システム状態確認
        $this->assertEquals(SystemMode::OFFLINE, SystemStatus::getCurrentMode());
    }
    
    /** @test */
    public function オフライン時の注文は_ローカルに保存される()
    {
        SystemStatus::updateMode(SystemMode::OFFLINE);
        
        $orderData = [
            'session_id' => 123,
            'items' => [['product_id' => 1, 'quantity' => 2]]
        ];
        
        // 注文処理
        $service = new OfflineOrderService();
        $order = $service->createOrder($orderData);
        
        // オフラインストレージに保存確認
        $this->assertDatabaseHas('offline_orders', [
            'order_id' => $order->order_number,
            'sync_status' => 'pending'
        ]);
    }
}
```

---

## 実装優先度

### Phase 1（必須機能）
- [x] 要件分析
- [ ] オフラインストレージ設計
- [ ] 接続監視機能
- [ ] 基本的な差分同期

### Phase 2（拡張機能）  
- [ ] 競合解決機能
- [ ] 運用コマンド
- [ ] 監視ダッシュボード

### Phase 3（運用改善）
- [ ] 自動復旧機能
- [ ] パフォーマンス最適化
- [ ] 詳細ログ・分析機能

この設計により、現在の2層認証・POS連携・change_logs設計を活用しながら、障害復旧機能を実現できます。