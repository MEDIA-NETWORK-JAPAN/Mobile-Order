# オンプレシステム構成設計書

## 📚 目次

- [1. システム構成概要](#1-システム構成概要)
  - [1.1 物理構成](#11-物理構成)
  - [1.2 主従関係の明確化](#12-主従関係の明確化)
- [2. データフローパターン](#2-データフローパターン)
  - [2.1 パターン1: オンプレ単独運用（スタッフ注文）](#21-パターン1-オンプレ単独運用スタッフ注文)
  - [2.2 パターン2: クラウド連携運用（お客様注文）](#22-パターン2-クラウド連携運用お客様注文)
- [3. 注文管理テーブルの役割](#3-注文管理テーブルの役割)
  - [3.1 中核的な位置づけ](#31-中核的な位置づけ)
  - [3.2 テーブル構造（FireBird DB）](#32-テーブル構造firebird-db)
  - [3.3 ライフサイクル](#33-ライフサイクル)
- [4. FireBird DBでのデータ操作](#4-firebird-dbでのデータ操作)
  - [4.1 注文データの挿入・検索](#41-注文データの挿入検索)
  - [4.2 ポーリング間隔・エラーハンドリング](#42-ポーリング間隔エラーハンドリング)
- [5. ポーリング機能の詳細](#5-ポーリング機能の詳細)
  - [5.1 change_logs監視処理](#51-change_logs監視処理)
- [5. 会計処理の詳細](#5-会計処理の詳細)
  - [5.1 会計フロー](#51-会計フロー)
  - [5.2 データクリアの意味](#52-データクリアの意味)
- [6. 障害時の動作](#6-障害時の動作)
  - [6.1 クラウド障害時の影響](#61-クラウド障害時の影響)
  - [6.2 代替運用フロー](#62-代替運用フロー)
- [7. システム設計上の重要なポイント](#7-システム設計上の重要なポイント)
  - [7.1 POS端末の自律性](#71-pos端末の自律性)
  - [7.2 クラウドとの関係](#72-クラウドとの関係)
  - [7.3 データの一貫性](#73-データの一貫性)
- [8. 将来拡張の考慮点](#8-将来拡張の考慮点)
  - [8.1 ハイブリッド注文の可能性](#81-ハイブリッド注文の可能性)
  - [8.2 オフライン対応の強化](#82-オフライン対応の強化)

---

## 1. システム構成概要

### 1.1 物理構成
```
店舗内システム構成:
┌─────────────────┐    ┌─────────────────┐
│   POS端末       │    │  ハンディ端末    │
│  (Windows)      │◄──►│  (Android)      │
│                 │    │                 │
│ ・ローカルDB    │    │ ・注文入力UI    │
│ ・注文管理      │    │ ・商品選択      │
│ ・厨房連携      │    │ ・席選択        │
│ ・会計処理      │    │                 │
└─────────────────┘    └─────────────────┘
         │
         │ 【クラウド連携】
         ▼
┌─────────────────┐
│  クラウドサーバー │
│  (Laravel App)   │
│                  │
│ ・Web注文受付    │
│ ・QRコード生成   │
│ ・注文データ管理 │
└─────────────────┘
```

### 1.2 主従関係の明確化
- **主役: POS端末** - 店舗運営の中核システム
- **補助: ハンディ端末** - POS端末の入力インターフェース
- **補助: クラウドシステム** - お客様向けWeb注文の受け皿

## 2. データフローパターン

### 2.1 パターン1: オンプレ単独運用（スタッフ注文）

```
【ハンディ端末での注文受付フロー】

1. ハンディ端末起動
   │
   ▼
2. POS端末のローカルDBに接続
   │
   ▼
3. 商品マスター・席情報を取得
   │
   ▼
4. スタッフ操作
   ├─ 席選択（テーブル番号指定）
   ├─ 商品選択（メニューから選択）
   └─ 注文確定
   │
   ▼
5. POS端末内「注文管理テーブル」に直接書き込み
   │
   ▼
6. POS端末が注文管理テーブルの追加を検知
   │
   ▼
7. 厨房処理開始
   ├─ 厨房プリンターに印字
   ├─ 厨房モニターに表示
   └─ 調理指示
   │
   ▼
8. 会計処理
   ├─ POS端末でテーブル番号入力
   ├─ 席の注文内容が表示
   └─ 会計完了
   │
   ▼
9. 注文管理テーブルから該当席データをクリア
```

### 2.2 パターン2: クラウド連携運用（お客様注文）

```
【スマートフォンでの注文フロー】

1. POS端末でテーブル番号を入力
   │
   ▼
2. クラウドのWebアプリに要求送信
   │
   ▼
3. Webアプリ側で注文URL生成・返却
   │
   ▼
4. POS端末でQRコード生成
   │
   ▼
5. レシートプリンターからQRコード印刷
   │
   ▼
6. お客様がQRコードを読み込み
   │
   ▼
7. スマートフォンで注文操作
   │
   ▼
8. 注文データをクラウドサーバーに送信
   │
   ▼
9. クラウドDB書き込み
   ├─ ordersテーブル
   └─ change_logsテーブル
   │
   ▼
10. POS端末がchange_logsをポーリング監視
    │
    ▼
11. 新しい注文を検出・取得
    │
    ▼
12. POS端末内「注文管理テーブル」に書き込み
    │
    ▼
13. ★ ここで「パターン1の手順6」と合流 ★
    │
    ▼
14. 厨房処理開始（パターン1と同じフロー）
```

## 3. 注文管理テーブルの役割

### 3.1 中核的な位置づけ
```
注文管理テーブル = 店舗内注文処理の「心臓部」

【入力元】
- ハンディ端末 → 直接書き込み
- クラウド経由  → ポーリング取得後に書き込み

【出力先】  
- 厨房プリンター
- 厨房モニター
- POS画面（会計時）
- 売上管理システム
```

### 3.2 テーブル構造（FireBird DB）
```sql
-- POS端末内ローカルDB（FireBird）
CREATE TABLE order_management (
    id INTEGER NOT NULL PRIMARY KEY,
    table_number VARCHAR(10) NOT NULL,                     -- テーブル番号
    menu_items BLOB SUB_TYPE TEXT NOT NULL,                -- 注文商品（JSON文字列）
    total_amount DECIMAL(10,2) NOT NULL,                   -- 合計金額
    order_status VARCHAR(20) DEFAULT 'pending'             -- 注文状態
        CHECK (order_status IN ('pending', 'printing', 'cooking', 'ready', 'served')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,        -- 注文受付時刻
    printed_at TIMESTAMP,                                  -- 厨房印字時刻
    served_at TIMESTAMP                                    -- 提供完了時刻
);

-- FireBird用インデックス
CREATE INDEX idx_order_management_table ON order_management (table_number);
CREATE INDEX idx_order_management_status ON order_management (order_status);
CREATE INDEX idx_order_management_created ON order_management (created_at);
```

### 3.3 ライフサイクル
```
注文受付 → order_management INSERT
    ↓
厨房処理 → printed_at 更新
    ↓  
提供完了 → served_at 更新
    ↓
会計完了 → order_management DELETE（データクリア）
```

## 4. FireBird DBでのデータ操作

### 4.1 注文データの挿入・検索

#### JSON文字列での注文データ管理
```sql
-- 注文データ挿入（FireBird）
INSERT INTO order_management (
    table_number,
    menu_items,
    total_amount,
    order_status
) VALUES (
    '4',
    '{"items":[{"name":"醤油ラーメン","price":800,"options":["大盛り"]}]}',
    950,
    'pending'
);

-- テーブル別注文検索
SELECT id, table_number, menu_items, total_amount, order_status
FROM order_management 
WHERE table_number = '4' AND order_status <> 'served';

-- 商品名での部分検索
SELECT * FROM order_management 
WHERE menu_items CONTAINING '"name":"醤油ラーメン"';
```

#### ステータス管理
```pascal
// POS端末内でのステータス定数（Delphi実装）
type
  TOrderStatus = class
  public
    const PENDING = 'pending';   // 注文受付
    const PRINTING = 'printing'; // 印字中
    const COOKING = 'cooking';   // 調理中  
    const READY = 'ready';       // 調理完了
    const SERVED = 'served';     // 提供完了
  end;
```

## 5. ポーリング機能の詳細

### 5.1 change_logs監視処理
```pascal
// POS端末内での定期実行処理（Delphi + FireBird実装）
procedure TMainForm.PollCloudChanges;
var
  HTTP: THTTPClient;
  Response: IHTTPResponse;
  JSONResponse, ChangeArray, ChangeItem: TJSONValue;
  Query: TFDQuery;
  LastSyncTime, StoreId: string;
  I: Integer;
begin
  try
    // 最後の同期時刻とストアIDを取得
    LastSyncTime := GetLastSyncTime;
    StoreId := GetStoreId;
    
    HTTP := THTTPClient.Create;
    try
      // クラウドAPIから変更データを取得
      Response := HTTP.Get(Format('https://cloud-api.mobile-order.com/api/v1/pos/changes?since=%s&store_id=%s', 
                                   [LastSyncTime, StoreId]));
      
      if Response.StatusCode = 200 then
      begin
        JSONResponse := TJSONObject.ParseJSONValue(Response.ContentAsString);
        try
          if (JSONResponse is TJSONObject) and TJSONObject(JSONResponse).GetValue('success').GetValue<Boolean> then
          begin
            ChangeArray := TJSONObject(JSONResponse).GetValue('data');
            if ChangeArray is TJSONArray then
            begin
              for I := 0 to TJSONArray(ChangeArray).Count - 1 do
              begin
                ChangeItem := TJSONArray(ChangeArray).Items[I];
                if (ChangeItem.GetValue<string>('entity_type') = 'orders') and
                   (ChangeItem.GetValue<string>('action') = 'created') then
                begin
                  // 新規注文をローカルに同期
                  SyncOrderToLocal(ChangeItem.GetValue<string>('entity_id'));
                end;
              end;
            end;
            
            UpdateLastSyncTime(Now);
          end;
        finally
          JSONResponse.Free;
        end;
      end;
    finally
      HTTP.Free;
    end;
    
  except
    on E: Exception do
    begin
      WriteLog('Polling failed: ' + E.Message);
      // 障害時は次回のポーリングで再試行
    end;
  end;
end;

procedure TMainForm.SyncOrderToLocal(const OrderId: string);
var
  HTTP: THTTPClient;
  Response: IHTTPResponse;
  OrderDetail: TJSONObject;
  Query: TFDQuery;
  TableNumber, MenuItemsJSON: string;
  TotalAmount: Currency;
begin
  HTTP := THTTPClient.Create;
  try
    // クラウドから注文詳細を取得
    Response := HTTP.Get(Format('https://cloud-api.mobile-order.com/api/v1/orders/%s', [OrderId]));
    
    if Response.StatusCode = 200 then
    begin
      OrderDetail := TJSONObject.ParseJSONValue(Response.ContentAsString) as TJSONObject;
      try
        // 注文データを解析
        TableNumber := OrderDetail.GetValue('session').GetValue<string>('table_number');
        MenuItemsJSON := OrderDetail.GetValue('items').ToString;
        TotalAmount := OrderDetail.GetValue<Currency>('total_amount');
        
        // ローカルの注文管理テーブルに挿入
        Query := TFDQuery.Create(nil);
        try
          Query.Connection := FDConnection;
          Query.SQL.Text := 
            'INSERT INTO order_management (table_number, menu_items, total_amount, order_status) ' +
            'VALUES (:table, :items, :amount, :status)';
          
          Query.ParamByName('table').AsString := TableNumber;
          Query.ParamByName('items').AsString := MenuItemsJSON;
          Query.ParamByName('amount').AsCurrency := TotalAmount;
          Query.ParamByName('status').AsString := 'pending';
          
          Query.ExecSQL;
        finally
          Query.Free;
        end;
        
      finally
        OrderDetail.Free;
      end;
    end;
  finally
    HTTP.Free;
  end;
end;
```

### 4.2 ポーリング間隔・エラーハンドリング
- **通常間隔**: 30秒〜1分
- **エラー時**: 指数バックオフで再試行間隔を延長
- **障害判定**: 3回連続失敗でオフラインモード判定

## 5. 会計処理の詳細

### 5.1 会計フロー
```
1. POS端末でテーブル番号入力
   │
   ▼
2. order_management テーブルから該当席の注文を検索
   SELECT * FROM order_management WHERE table_number = ?
   │
   ▼
3. 注文内容をPOS画面に表示
   ├─ 商品名・数量・単価
   ├─ オプション・特記事項
   └─ 合計金額
   │
   ▼
4. 会計処理実行
   ├─ 現金・クレジットカード・電子マネー等
   ├─ レシート印刷
   └─ 売上データ記録
   │
   ▼
5. 会計完了時のデータクリア
   DELETE FROM order_management WHERE table_number = ?
```

### 5.2 データクリアの意味
- **席の解放**: そのテーブルが新しいお客様を受け入れ可能な状態に
- **注文サイクル完了**: 「注文→調理→提供→会計→完了」の一連の流れが終了
- **システムリセット**: 該当席に関する一時的なデータを全てクリア

## 6. 障害時の動作

### 6.1 クラウド障害時の影響

#### ✅ 影響を受けない処理
- **ハンディ端末での注文受付**: POS端末と直接通信のため正常動作
- **厨房での調理指示**: 注文管理テーブル経由で正常動作
- **会計処理**: ローカルDBベースのため正常動作

#### ❌ 影響を受ける処理
- **QRコード生成**: クラウドAPIが必要
- **スマホでの注文**: クラウドサーバーが必要
- **注文データの同期**: ポーリング処理が停止

### 6.2 代替運用フロー
```
【障害時の代替フロー】

1. お客様「スマホで注文したい」
   │
   ▼
2. スタッフ「申し訳ございません、システムの都合上、お伺いします」
   │
   ▼
3. スタッフがハンディ端末で代行注文
   │
   ▼
4. 以降は通常のハンディ注文フローと同じ
   │
   ▼
5. 厨房での調理・提供・会計も通常通り
```

## 7. システム設計上の重要なポイント

### 7.1 POS端末の自律性
- **スタンドアローン動作**: クラウドが停止してもコア機能は継続
- **ローカルDB完結**: 注文管理・会計・売上記録が独立して動作
- **冗長性確保**: 単一障害点にならない設計

### 7.2 クラウドとの関係
- **補助的役割**: POS端末の機能を拡張する位置づけ
- **非同期連携**: リアルタイム性よりも確実性を重視
- **障害影響限定**: クラウド障害が店舗運営を止めない

### 7.3 データの一貫性
- **マスターデータ**: 商品・価格情報はPOS端末が正
- **取引データ**: 注文・売上データもPOS端末が正
- **同期データ**: クラウドはレポート・分析用の副次的データ

## 8. 将来拡張の考慮点

### 8.1 ハイブリッド注文の可能性
```
将来的な機能拡張案:
- ハンディ端末でQRコード生成
- スマホ注文をハンディで代行受付
- 注文方法の切り替え（スマホ⇔ハンディ）
```

### 8.2 オフライン対応の強化
```
さらなる自律性向上:
- 商品マスターのローカルキャッシュ
- 価格変更のオフライン対応
- 売上レポートのローカル生成
```

---

## まとめ

このオンプレシステム構成の理解により、以下が明確になりました：

1. **POS端末が中核** - クラウドは補助的な役割
2. **注文管理テーブルが心臓部** - 全ての注文がここに集約
3. **自律性の高い設計** - 障害時も基本機能は継続
4. **会計完了でデータクリア** - 注文ライフサイクルの完結

この理解をベースに、現実的で実装可能な障害復旧機能を設計することができます。