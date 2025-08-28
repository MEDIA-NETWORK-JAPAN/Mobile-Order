# 設計書連携型 ToDo リスト

## 🚨 実装前必須確認事項
**いかなる実装も、該当する設計書セクションの確認なしに開始してはならない**

---

## Phase 2: 管理機能実装（現在）

### ✅ 完了済み
- [x] データベース基盤構築
  - 設計書: `02_database_design.md`
  - 実装内容: マイグレーション、モデル、シーダー

### 🔴 要修正：設計書違反の是正

#### 商品管理機能のLivewire化
- [ ] **ProductControllerの削除** 
  - 設計書: `03_api_design.md L210-211, L3210`
  - 違反内容: Controllerで実装されているが、設計書はLivewire指定
  - 修正方法: ProductController.phpを削除
  
- [ ] **ProductCreate Livewireコンポーネントの有効化**
  - 設計書: `03_api_design.md L3229-3269`
  - 実装方法: `Route::get('/products/create', ProductCreate::class)`
  - 禁止事項: Controllerを使用しない
  
- [ ] **ProductEdit Livewireコンポーネントの作成**
  - 設計書: `03_api_design.md L3229-3269`
  - 実装方法: `app/Livewire/Admin/Products/ProductEdit.php`
  - 禁止事項: Controllerを使用しない

### 📝 実装予定

#### カテゴリ管理機能
- [ ] **CategoryIndex（一覧）**
  - 設計書: `03_api_design.md L215-216`
  - 実装方法: Livewireコンポーネント
  - 実装済み: ✅（確認済み）

- [ ] **CategoryCreate（作成）**
  - 設計書: `03_api_design.md L215-216`
  - 実装方法: Livewireコンポーネント
  - 実装済み: ✅（確認済み）

- [ ] **CategoryEdit（編集）**
  - 設計書: `03_api_design.md L215-216`
  - 実装方法: Livewireコンポーネント
  - 未実装

#### オプション管理機能
- [ ] **OptionIndex（一覧）**
  - 設計書: `03_api_design.md L217-218`
  - 実装方法: Livewireコンポーネント
  - 実装済み: ✅（確認済み）

- [ ] **OptionCreate（作成）**
  - 設計書: `03_api_design.md L217-218`
  - 実装方法: Livewireコンポーネント
  - 実装済み: ✅（確認済み）

- [ ] **OptionEdit（編集）**
  - 設計書: `03_api_design.md L217-218`
  - 実装方法: Livewireコンポーネント
  - 未実装

#### ユーザー管理機能
- [ ] **UserIndex（一覧）**
  - 設計書: `03_api_design.md L225`
  - 実装方法: Livewireコンポーネント
  - 権限: 管理者が編集可能

- [ ] **UserCreate（作成）**
  - 設計書: `03_api_design.md L226`
  - 実装方法: Livewireコンポーネント
  
- [ ] **UserEdit（編集）**
  - 設計書: `03_api_design.md L227`
  - 実装方法: Livewireコンポーネント

#### システム設定管理
- [ ] **SystemSettings（設定管理）**
  - 設計書: `03_api_design.md L221-222, L3318-3354`
  - 実装方法: Livewireコンポーネント
  - 権限: Web固有設定のみ変更可能（POS設定は変更不可）

---

## Phase 3: お客様向け機能

### QRコード認証
- [ ] **QRコードセッション開始**
  - 設計書: `03_api_design.md L246-273`
  - 実装方法: LaravelコントローラーSessionController::start()
  - エンドポイント: `GET /order?session=xxx`
  
- [ ] **ゲスト登録**
  - 設計書: `03_api_design.md L277-299`
  - 実装方法: Livewireアクション registerGuest()
  - エンドポイント: `POST /guest/register`

### メニュー表示（Livewire実装）
- [ ] **メニュー画面**
  - 設計書: `03_api_design.md L147`
  - 実装方法: Livewireコンポーネント
  - エンドポイント: `GET /menu`
  - 禁止事項: APIではなくWebルート

- [ ] **商品詳細モーダル**
  - 設計書: `03_api_design.md L149`
  - 実装方法: Livewireコンポーネント
  - エンドポイント: `GET /menu/products/{id}`

### カート管理（Livewire実装）
- [ ] **カートアイテム追加**
  - 設計書: `03_api_design.md L152`
  - 実装方法: Livewireアクション
  - エンドポイント: `POST /cart/items`

- [ ] **カート表示**
  - 設計書: `03_api_design.md L153`
  - 実装方法: Livewireコンポーネント
  - エンドポイント: `GET /cart`

### 注文管理（Livewire実装）
- [ ] **注文作成**
  - 設計書: `03_api_design.md L158`
  - 実装方法: Livewireアクション
  - エンドポイント: `POST /orders`

- [ ] **注文履歴表示**
  - 設計書: `03_api_design.md L159`
  - 実装方法: Livewireコンポーネント
  - エンドポイント: `GET /order-history`

---

## Phase 4: POS連携

### POS認証（API実装）
- [ ] **POSログイン認証**
  - 設計書: `03_api_design.md L164`
  - 実装方法: APIコントローラー
  - エンドポイント: `POST /api/v1/pos/auth/login`
  - 認証: Laravel Sanctum Bearer Token

- [ ] **POSトークン更新**
  - 設計書: `03_api_design.md L165`
  - 実装方法: APIコントローラー
  - エンドポイント: `POST /api/v1/pos/auth/refresh`

### システム監視
- [ ] **双方向ヘルスチェック同期**
  - 設計書: `03_api_design.md L169`
  - 実装方法: APIコントローラー
  - エンドポイント: `POST /api/v1/pos/health/sync`
  - 重要: 30秒→90秒段階的タイムアウト

### セッション・注文管理
- [ ] **席セッション作成**
  - 設計書: `03_api_design.md L172`
  - 実装方法: APIコントローラー
  - エンドポイント: `POST /api/v1/pos/sessions`
  - 注意: POS側でセッションID生成

- [ ] **変更履歴取得（ポーリング）**
  - 設計書: `03_api_design.md L175`
  - 実装方法: APIコントローラー
  - エンドポイント: `GET /api/v1/pos/changes`
  - 重要: cloud_synced=FALSEのレコード取得

### 商品マスター管理（POS専用）
- [ ] **商品同期検証**
  - 設計書: `03_api_design.md L193`
  - 実装方法: APIコントローラー
  - エンドポイント: `POST /api/v1/pos/products/verify`

- [ ] **商品CRUD操作**
  - 設計書: `03_api_design.md L194-196`
  - 実装方法: APIコントローラー
  - 注意: POS端末からのみアクセス可能

---

## Phase 5: 最適化・本番準備

- [ ] **パフォーマンス最適化**
  - 設計書: `11_non_functional_requirements.md`
  - 内容: クエリ最適化、キャッシュ戦略

- [ ] **セキュリティ監査**
  - 設計書: `11_non_functional_requirements.md`
  - 内容: ペネトレーションテスト、脆弱性診断

- [ ] **負荷テスト**
  - 設計書: `09_test_strategy.md`
  - 内容: 同時接続数テスト、ピーク時シミュレーション

---

## 🚨 実装時の必須プロセス

### 各タスク実装前
1. **設計書の該当箇所を必ず引用**
2. **実装方法を設計書から確認**
3. **既存実装との整合性確認**
4. **禁止事項の確認**

### 実装後
1. **設計書との差異チェック**
2. **テストの実行**
3. **次のタスクへの影響確認**

---

## 重要な設計原則

### POS中心設計
- マスターデータ管理主体: POS端末
- クラウド側: データ受信・表示のみ
- SuperAdminのみ緊急編集可能

### 実装方式
- 管理画面: **Livewire実装（Controllerではない）**
- お客様画面: **Livewire実装（APIではない）**
- POS連携: **REST API実装**

### 認証方式
- 管理画面: Laravel Breezeセッション認証
- お客様: 2層認証（席セッション＋ゲストセッション）
- POS: Laravel Sanctum Bearer Token

---

最終更新: 2024年現在
次回レビュー: Phase 2完了時