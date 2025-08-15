# ドキュメントガイド まとめ

## アーキテクチャ・基盤設計

### `01_architecture_design.md`
- **採用設計**：DDD + Clean Architecture
- **目的**：「各レイヤーの責任」を明確化
- **効果**：AIが迷わずロジックの配置判断が可能に

### `02_database_design.md`
- **内容**：ER図、テーブル定義、命名規則、インデックス、リレーション制約
- **目的**：設計ミスやマイグレーションの手戻り防止

### `03_api_design.md`
- **対象**：RESTful API
- **定義項目**：
  - エンドポイント命名規則
  - リクエスト/レスポンス形式
  - エラー時のフォーマット統一
- **効果**：フロントエンドの実装効率向上

## 画面設計・SEO

### `04_screen_transition_design.md`
- **内容**：
  - 画面遷移図
  - 権限要件の整理
- **目的**：ユーザー動線の明確化

### `05_seo_requirements.md`
- **SEO施策**：
  - メタタグ設定
  - 構造化データ
  - サイトマップ生成

## エラーハンドリング & 型定義

### `06_error_handling_design.md`
- **分類**：バリデーション / 認証 / システムエラー
- **内容**：
  - 表示メッセージ
  - ログの出力方針
  - ログレベルのルール化

### `07_type_definitions.md`
- **型定義の集中管理**：
  - ドメインモデル
  - APIレスポンス
  - フォーム入力

## 開発効率向上のための設計

### `08_development_setup.md`
- **セットアップ手順**：
  - ツールインストール
  - 環境変数
  - 開発DB準備

### `09_test_strategy.md`
- **戦略整理**：
  - 単体 / 統合 / E2Eテストの役割と範囲
  - TDDを前提に設計

### `10_frontend_design.md`
- **コンポーネント分類**：
  - `ui` / `features` / `layouts`
- **設計方針**：props・状態管理ルール

## 運用・テスト戦略

### `11_cicd_design.md`
- **CI/CDパイプライン**：
  - GitHub Actionsのチェック
  - 環境別設定
  - デプロイ手順

### `12_e2e_test_design.md`
- **E2Eテスト設計**：
  - クリティカルパスの洗い出し
  - テストの具体的方針

## セキュリティ・パフォーマンス

### `13_security_design.md`
- **対象**：
  - 認証/認可
  - 入力値検証
  - ファイルアップロード制限

### `14_performance_optimization.md` + `15_performance_monitoring.md`
- **最適化**：
  - 画像・キャッシュ戦略
  - Core Web Vitals目標値
- **監視**：
  - パフォーマンス監視の設計

## デザインシステム（5つ）

### `00_basic_design.md`
- **概要**：デザインシステムの全体像とスタートガイド

### `01_design_principles.md`
- **基本原則**：
  - カラー、タイポグラフィ、スペーシング
  - 一貫性ある数値定義

### `02_component_design.md`
- **UI構成**：
  - ボタン、カード、フォーム等のバリエーションと使い分け

### `03_animation_system.md`
- **動きの統一**：
  - イージング関数
  - アニメーション時間の基準

### `04_layout_system.md`
- **レイアウト設計**：
  - グリッド、ブレークポイント
  - 最大幅設定等

## ライブラリ対策ドキュメント（4つ）

### `01_shadcn_doc.md`
- **Shadcn UI**のパターンと注意点を集約

### `02_supabase_auth_vitest.md` & `03_supabase_storage_vitest.md`
- **Supabaseのテスト**：
  - 認証・ストレージ周りの環境・モック設定手順

### `04_nextjs_app_router_patterns.md`
- **Next.js App Router**：
  - Server/Client Componentsの使い分け
  - フェッチ/エラーハンドリングのベストプラクティス
