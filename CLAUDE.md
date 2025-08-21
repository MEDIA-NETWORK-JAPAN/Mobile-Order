# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## プロジェクト概要

**Mobile Order System** - 飲食店向けモバイル注文システム
- QRコード読み取りによる注文開始
- 商品のオプション選択（麺の硬さ、トッピング等）
- リアルタイム在庫管理（売り切れ/未入荷/準備中）
- 多言語対応（日本語、英語、中国語繁体/簡体、韓国語）
- POS連携（Laravel Sanctum認証）

## 開発コマンド

### Docker環境（Laravel Sail）
```bash
sail up -d                      # コンテナ起動
sail down                       # コンテナ停止
sail ps                         # 状態確認
sail exec laravel.test bash     # コンテナ内でbash実行
```

### データベース操作
```bash
sail artisan migrate            # マイグレーション実行
sail artisan migrate:fresh      # 全テーブル削除して再作成
sail artisan migrate:rollback   # 直前のマイグレーションをロールバック
sail artisan db:seed            # シーダー実行
sail artisan tinker             # 対話シェル起動
```

### 開発サーバー
```bash
sail npm run dev                # Vite開発サーバー起動（ホットリロード）
sail npm run build              # 本番用ビルド
```

### テスト実行
```bash
sail artisan test               # 全テスト実行
sail artisan test --filter=TestName  # 特定のテスト実行
sail artisan test tests/Feature/     # Feature テストのみ実行
```

### コード品質
```bash
sail exec laravel.test vendor/bin/pint        # Laravel Pint実行
sail exec laravel.test vendor/bin/pint --test # ドライラン（変更なし）
```

### モデル・コントローラー作成
```bash
sail artisan make:model Product -mfc   # モデル、マイグレーション、ファクトリー、コントローラー作成
sail artisan make:livewire ProductCard # Livewireコンポーネント作成
sail artisan make:migration create_cart_logs_table  # マイグレーション作成
```

## 🚨 最重要：設計書絶対遵守ルール

### 設計書の位置づけ
**このプロジェクトは詳細な設計書（22文書）に基づいて開発されています。**
**実装は必ず設計書に完全準拠してください。**

### 厳守事項
1. **設計書が唯一の真実**: 実装前に必ず該当する設計書を熟読する
2. **勝手な判断禁止**: 「一般的にはこうする」という推測での実装は絶対禁止
3. **設計書にない機能は実装しない**: 明記されていない機能の追加は禁止
4. **不明点は実装前に確認**: 設計書の解釈に迷ったら必ず確認を取る

### POS中心設計の理解
- **マスターデータ管理主体**: POS端末（Delphi + FireBird）
- **クラウド側の役割**: POSからのデータ受信・表示（CUD操作は行わない）
- **管理画面**: 商品・カテゴリ・オプションの**閲覧のみ**（編集機能なし）
- **データ更新フロー**: POS側で更新 → API経由でクラウドに同期

### 実装前チェックリスト
- [ ] 該当する設計書を特定し、熟読したか
- [ ] 設計書の仕様と実装内容が完全に一致しているか
- [ ] POS中心設計の原則に反していないか
- [ ] 勝手な機能追加をしていないか

### 緊急時のSuperAdmin権限運用
- **原則**: マスターデータ（商品・カテゴリ・オプション）はPOS側で管理
- **緊急時対応**: SuperAdminのみクラウド側で直接編集可能（POS障害時等）
- **同期方法**: 手動（POS側でデータを手動更新）
- **通常管理者**: 閲覧のみ（編集・削除不可）
- **警告表示**: SuperAdmin編集時は「緊急編集モード」警告を表示
- **注意事項**: クラウド側での編集後は必ずPOS側のデータを手動で同期すること

## アーキテクチャ概要

### 統一商品マスター設計
```
products（商品マスター）
├── メイン商品（ラーメン、寿司など）
├── オプション商品（チャーシュー、ネギなど）
└── サイズ商品（大盛り、特盛りなど）

※すべてproductsテーブルで管理し、関連はcategory_product、option_detailで構築
```

### 認証システム（2層認証 + 管理・POS）
1. **2層認証システム（お客様用）**:
   - **第1層**: 席セッション（QRコード→同席者間での注文履歴共有）
   - **第2層**: ゲストセッション（個人識別・不正防止・端末特定）
   - **同意画面**: ハンドルキーパー・セキュリティポリシー同意
2. **管理者認証**: Laravel Breeze（セッション/Cookie）
3. **POS認証**: Laravel Sanctum（Bearer Token + IP制限）

### セッション管理（2層ハイブリッド方式）
- **第1層 - 席セッション**: QRコード読み取り後の席管理（sessionsテーブル）
  - 同席者間での注文履歴共有
  - デフォルト無期限（固定モード）または3時間TTL（都度発行モード）
- **第2層 - ゲストセッション**: 個人端末の識別管理（データベース: guest_sessionsテーブル）
  - 個人識別・不正防止・端末特定
  - 30分TTL（アクティビティで自動延長）
- **カートログ**: 全操作履歴をDBに記録（cart_logsテーブル）

### カート管理（ハイブリッド方式）
- **Redisキャッシュ**: 高速アクセス用（30分TTL、自動延長）
  - `guest_cart:{token}` - カートアイテムのみ
- **DBログ**: 全操作履歴を永続化（cart_logsテーブル）
  - 成功/失敗フラグ（is_success）
  - エラー情報（error_code, error_message）
  - 操作時の価格とカート合計
  - デバイス情報（fingerprint, IP, UA）

### 重要な設計判断
- **マイナス価格対応**: 値引き商品のため価格フィールドはマイナス値を許可
- **ブラウザバック無効化**: 注文フロー中の誤操作防止のため実装
- **availability_status**: ENUM型で4状態管理（available/sold_out/not_arrived/preparing）

## ディレクトリ構造

```
app/
├── Http/
│   ├── Controllers/      # API・Webコントローラー
│   ├── Middleware/       # 認証・権限チェック
│   └── Livewire/         # Livewireコンポーネント
├── Models/               # Eloquentモデル
├── Services/             # ビジネスロジック
└── Policies/             # 権限管理

resources/
├── views/
│   ├── livewire/         # Livewireビュー
│   └── components/       # Bladeコンポーネント
├── css/                  # TailwindCSS
└── js/                   # Alpine.js

database/
├── migrations/           # スキーマ定義
├── factories/            # テストデータ生成
└── seeders/              # 初期データ投入
```

## 作業実施ルール

### 🚨 必須：作業前の許可確認
**いかなる修正・変更作業も、事前許可なしでは絶対に実行しない**

#### 作業開始前の必須報告事項
以下の内容を必ず報告し、明確な許可を得てから作業開始：

```
■ 修正対象ファイル: [ファイルパスを明記]
■ 発見した問題: [具体的な不整合・エラー内容]
■ 修正予定内容: [変更前 → 変更後の具体的内容]
■ 影響範囲: [他ファイルへの影響、関連機能への影響]
■ 修正方針: [なぜこの修正方法を選択するか]
■ 確認方法: [修正後どのように検証するか]
```

#### 許可確認の流れ
1. **問題発見時**: 即座に作業を停止
2. **報告**: 上記事項を詳細に報告
3. **許可待ち**: 明確な「開始してください」の指示を待つ
4. **作業実行**: 許可後のみ作業開始
5. **完了報告**: 結果と検証内容を詳細報告

#### 絶対禁止事項
- ❌ 「ついでに修正」「明らかな問題だから」等の理由での無許可作業
- ❌ 許可前の推測による作業開始
- ❌ 部分的な作業開始後の事後報告

### 🔒 厳格な作業品質基準

**🚨 重要：推測作業・雑な検証・虚偽報告は絶対禁止**

#### 必須検証プロセス（作業前）
```
□ 元文書の該当箇所を特定・熟読した
□ 修正対象の現在の記載内容を確認した
□ 両者の差異を具体的にリストアップした
□ 修正方針を明確に決定した
```

#### 必須検証プロセス（作業後）
```
□ 修正後の内容を元文書と逐一比較した
□ すべてのフィールド名・型・必須フラグが一致することを確認した
□ JSONサンプルが完全に一致することを確認した
□ 他への影響がないことを確認した
```

#### 必須の比較検証手順
1. **作業時は必ず実行**:
   - 元文書の該当部分をコピー&ペーストで引用
   - 現在の記載内容をコピー&ペーストで引用  
   - 差異を明示的にリストアップ
   - 修正後は再度両方を引用して一致確認

#### 報告基準の厳格化
**❌ 絶対に使用禁止の表現**:
- 「完璧に一致」「完全に整合」
- 「✅」マークでの安易な完了報告
- 確認していない内容への断定的表現

**✅ 使用すべき表現**:
- 「以下の項目を確認し、一致を確認しました: [具体的項目リスト]」
- 「修正前後の比較結果: [具体的な比較内容]」
- 「確認できた範囲: [具体的範囲]、未確認: [未確認事項]」

#### 作業記録の必須化
**毎回の作業で必ず記録**:
```
1. 確認した元文書の箇所: [ファイル名:行番号・引用内容]
2. 発見した不整合: [具体的内容]
3. 実施した修正: [before/after の具体的内容]
4. 検証結果: [確認した項目と結果の詳細]
```

#### 禁止事項
- **推測による作業**: 「こうあるべき」という思い込みでの作業
- **手抜きの検証**: シンプル化を理由とした詳細確認の省略  
- **虚偽の品質報告**: 確認していない内容への完了報告
- **工程の飛ばし**: 確認→修正→再確認の基本工程の無視

## ドキュメント整合性チェック手法

### 🔄 ハイブリッド段階的整合性確認（推奨手法）

#### Phase 1: 自動化による基本整合性チェック
```bash
# 専用エージェントを使用した自動チェック
- 全22文書から重要キーワード（テーブル名、API名、技術用語）を抽出
- 定義の差異や記載漏れを自動検出
- 効率的に大量チェックを実行
```

#### Phase 2: ドメイン別クロスリファレンス
```bash
# 重要ドメインごとに横断確認
認証システム: 認証設計書 ↔ API設計書 ↔ 画面遷移
POS連携: 障害復旧設計書 ↔ API設計書 ↔ オンプレシステム設計書  
データ管理: データベース設計書 ↔ API設計書 ↔ UI設計書
```

#### Phase 3: 最新仕様変更の波及確認
```bash
# 最近の更新内容の全文書反映チェック
- QRコード運用モード（固定/都度発行）
- POSヘルスチェック（30秒/90秒段階的）
- 障害復旧プロセス（canceledステータス）
```

#### 整合性チェック実行コマンド
```bash
# .claudeフォルダ内全文書の整合性確認
claude code "ドキュメント整合性確認を実行してください"

# 特定ドメインの整合性確認
claude code "認証システムの整合性を確認してください"
claude code "POS連携の整合性を確認してください"
```

#### チェック項目チェックリスト
- [ ] テーブル名・フィールド名の統一
- [ ] API エンドポイント名の統一
- [ ] データ型・必須フラグの一致
- [ ] エラーコード・メッセージの統一
- [ ] 認証方式の一貫性
- [ ] セッション管理仕様の統一
- [ ] 最新仕様変更の全文書反映

## 開発フロー状況

- **Phase 0**: プロジェクト初期化とドキュメント整備 ✅ 完了
- **Phase 1**: 基盤構築（データベース、認証システム） ← 現在
- **Phase 2**: 管理機能（管理者ログイン、メニュー管理）
- **Phase 3**: お客様向け機能（QRコード、メニュー表示、注文）
- **Phase 4**: POS連携（変更記録、ポーリングAPI）
- **Phase 5**: 最適化・本番準備

## 設計ドキュメント参照

### 01_development_docs/ - 開発技術設計書
- **アーキテクチャ設計**: `.claude/01_development_docs/01_architecture_design.md`
  - POS中心設計、全体アーキテクチャ、技術スタック詳細
- **データベース設計**: `.claude/01_development_docs/02_database_design.md`
  - 統一商品マスター、2層セッション管理、カートログ設計
- **API設計**: `.claude/01_development_docs/03_api_design.md`
  - RESTful API仕様、認証方式別エンドポイント、POS連携API
- **UIコンポーネント設計**: `.claude/01_development_docs/04_ui_component_design.md`
  - Livewire+MaryUI実装、コンポーネント分類、多言語対応
- **エラーハンドリング設計**: `.claude/01_development_docs/05_error_handling_design.md`
  - 多言語エラーメッセージ、POS連携エラー、自動リトライ機構
- **テスト戦略**: `.claude/01_development_docs/09_test_strategy.md`
  - Feature/Unit/Integration テスト方針、POS連携テスト
- **非機能要件**: `.claude/01_development_docs/11_non_functional_requirements.md`
  - パフォーマンス、セキュリティ、可用性、運用要件
- **インフラ・デプロイ設計**: `.claude/01_development_docs/12_infrastructure_deployment_design.md`
  - Docker環境、本番デプロイメント、監視・ログ設計
- **認証・認可設計**: `.claude/01_development_docs/13_auth_authorization_design.md`
  - 2層認証詳細、Laravel Breeze + Sanctum実装
- **障害復旧設計**: `.claude/01_development_docs/14_disaster_recovery_design.md`
  - オンプレ障害時の自動復旧、cloud_syncedフラグ管理
- **オンプレシステム設計**: `.claude/01_development_docs/15_onpremise_system_architecture.md`
  - Delphi+FireBird POS、ハンディ端末、障害時運用継続
- **画面遷移フロー**: `.claude/01_development_docs/16_screen_transition_flow.md`
  - QRコード読み取りから注文完了までのUX設計

### 02_design_system/ - デザインシステム
- **基本設計**: `.claude/02_design_system/00_basic_design.md`
  - デザインシステム全体方針、技術構成、モバイルファースト
- **デザイン原則**: `.claude/02_design_system/01_design_principles.md`
  - カラーシステム（アンバー系）、タイポグラフィ、アイコン指針
- **コンポーネント設計**: `.claude/02_design_system/02_component_design.md`
  - 再利用可能コンポーネント、状態管理、バリアント定義
- **アニメーションシステム**: `.claude/02_design_system/03_animation_system.md`
  - 控えめなトランジション、パフォーマンス重視設計
- **レイアウトシステム**: `.claude/02_design_system/04_layout_system.md`
  - レスポンシブグリッド、スペーシングシステム

### 03_library_best_practices/ - ライブラリベストプラクティス
- **Laravel Sail**: `.claude/03_library_best_practices/01_laravel_sail_best_practices.md`
  - Docker環境構築、開発効率化のコマンド集
- **Mary UI**: `.claude/03_library_best_practices/02_mary_ui_best_practices.md`
  - TailwindCSSベースUI実装、カスタマイゼーション方針
- **Livewireテスト**: `.claude/03_library_best_practices/03_livewire_test_strategy.md`
  - リアクティブコンポーネントのテスト手法
- **REST API統合**: `.claude/03_library_best_practices/04_rest_api_integration_patterns.md`
  - POS連携パターン、エラーハンドリング、認証統合

### 00_project/ - プロジェクト基礎資料
- **機能要件定義**: `.claude/00_project/01_mo_concept_requirements.md`
  - システム概要、ステークホルダー定義、機能要件詳細

## Docker環境URL

- **アプリケーション**: http://localhost:8080
- **Mailpit（メール確認）**: http://localhost:8025
- **phpMyAdmin（DB管理）**: http://localhost:8090
- **MySQL**: localhost:3306 (DB: mobile_order)
- **Redis**: localhost:6379

## オンプレミスシステム設計

### システム構成
- POS(windows)
- ハンディ端末(Android)

### オンプレ単独運用
1. ハンディ端末起動→POS端末のDBに接続→商品・席情報等取得
2. ハンディ端末操作：席選択→商品選択→注文送信(POS端末内の注文管理テーブルに書き込み)
3. 注文管理テーブルの追加を検知して、POS端末のプログラムが動作開始→例：厨房プリンターに印字
4. 会計処理：POS端末でテーブル番号を入力→席の注文内容が表示→会計完了
5. 会計完了時に注文管理テーブルから該当席に紐づくデータをクリア

### クラウドアプリとの連携
1. POS端末で席番号を入力→Webアプリ側で注文URLを生成・返却
2. 注文URLを元にQRコードを生成→レシートプリンターから印刷
3. 印刷されたQRコードをゲストが読み込み→注文開始
4. ゲストが注文をクラウドサーバーへ送信→orders, change_logsテーブルに書き込み
5. POSからのポーリングで、change_logsに追加されたレコードから最新の注文内容を取得
6. POS端末内の注文管理テーブルに書き込み→例：厨房プリンターに印字（オンプレ単独運用の処理とはここで合流する）