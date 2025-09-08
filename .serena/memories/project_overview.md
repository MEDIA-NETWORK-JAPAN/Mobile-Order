# Mobile Order System - プロジェクト概要

## プロジェクト概要
**Mobile Order System** - 飲食店向けモバイル注文システム
- QRコード読み取りによる注文開始
- 商品のオプション選択（麺の硬さ、トッピング等）
- リアルタイム在庫管理（売り切れ/未入荷/準備中）
- 多言語対応（日本語、英語、中国語繁体/簡体、韓国語）
- POS連携（Laravel Sanctum認証）

## 技術スタック
### バックエンド
- **PHP 8.1+**
- **Laravel 10.x** - PHPフレームワーク
- **Laravel Sail** - Docker開発環境
- **Laravel Sanctum** - API認証
- **Laravel Breeze** - 認証スターター

### フロントエンド
- **Livewire 3.x** - リアクティブコンポーネント
- **Mary UI 2.x** - UIコンポーネントライブラリ（TailwindCSSベース）
- **Alpine.js 3.x** - 軽量JSフレームワーク
- **TailwindCSS 3.x** - CSSフレームワーク
- **DaisyUI 5.x** - TailwindCSSコンポーネント
- **Vite 5.x** - ビルドツール
- **SortableJS** - ドラッグ&ドロップ

### データベース・キャッシュ
- **MySQL** - メインデータベース
- **Redis** - セッション・キャッシュストレージ

### その他
- **Simple QRCode 4.x** - QRコード生成
- **Guzzle 7.x** - HTTPクライアント

## 開発環境URL
- **アプリケーション**: http://localhost:8080
- **Mailpit（メール確認）**: http://localhost:8025
- **phpMyAdmin（DB管理）**: http://localhost:8090
- **MySQL**: localhost:3306 (DB: mobile_order)
- **Redis**: localhost:6379

## 開発フェーズ
- **Phase 0**: プロジェクト初期化とドキュメント整備 ✅ 完了
- **Phase 1**: 基盤構築（データベース、認証システム） ✅ 完了
- **Phase 2**: 管理機能（管理者ログイン、メニュー管理） ✅ 完了
- **Phase 3**: お客様向け機能（QRコード、メニュー表示、注文）
- **Phase 4**: POS連携（変更記録、ポーリングAPI）
- **Phase 5**: 最適化・本番準備

## 重要な設計原則
### POS中心設計
- マスターデータ管理はPOS端末（Delphi + FireBird）が主体
- クラウド側はPOSからのデータ受信・表示が主な役割
- 管理画面は基本的に閲覧のみ（SuperAdminのみ緊急時編集可能）
- データ更新フロー: POS側で更新 → API経由でクラウドに同期

### 設計書遵守
- 詳細な設計書（22文書）に基づいて開発
- 実装は必ず設計書に完全準拠
- 設計書にない機能は実装しない
- 勝手な判断での実装は禁止