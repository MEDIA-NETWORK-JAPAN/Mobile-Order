# Mobile Order System - タスク完了時チェックリスト

## 必須実行項目（タスク完了時）

### 1. コード整形
```bash
# Laravel Pintでコード整形（必須）
sail exec laravel.test vendor/bin/pint

# ドライラン（変更内容確認）
sail exec laravel.test vendor/bin/pint --test
```

### 2. テスト実行（テストがある場合）
```bash
# 全テスト実行
sail artisan test

# 関連するFeatureテストのみ実行
sail artisan test tests/Feature/

# 特定のテスト実行
sail artisan test --filter=TestName
```

### 3. マイグレーション確認（DBスキーマ変更時）
```bash
# マイグレーションステータス確認
sail artisan migrate:status

# マイグレーション実行
sail artisan migrate

# ロールバック動作確認
sail artisan migrate:rollback
sail artisan migrate
```

### 4. 動作確認
- ブラウザで実際の動作を確認
- エラーログの確認: `tail -f storage/logs/laravel.log`
- 開発サーバーのコンソールエラー確認

### 5. Git操作前の確認
```bash
# 変更内容確認
git status
git diff

# 不要なファイルが含まれていないか確認
# 特に.env、vendor/、node_modules/など
```

## 重要な確認事項

### 設計書との整合性
- [ ] 実装内容が設計書と完全に一致しているか
- [ ] 設計書にない機能を追加していないか
- [ ] POS中心設計の原則に従っているか

### コード品質
- [ ] Laravel Pintを実行したか
- [ ] 不要なコメントを削除したか
- [ ] エラーハンドリングは適切か
- [ ] 型ヒントを使用しているか

### セキュリティ
- [ ] SQLインジェクション対策（Eloquent使用）
- [ ] XSS対策（Blade自動エスケープ）
- [ ] 権限チェック（canEditプロパティ）
- [ ] セッション管理は適切か

### パフォーマンス
- [ ] N+1問題の回避（eager loading使用）
- [ ] 不要なクエリの削減
- [ ] キャッシュの適切な使用

## 報告時の必須記載事項
1. 実施した作業内容
2. 変更したファイル一覧
3. テスト実行結果
4. 動作確認結果
5. 特記事項（問題点、改善提案など）

## 注意事項
- **推測での実装禁止**: 不明点は必ず確認
- **部分的な作業禁止**: タスクは完全に完了させる
- **虚偽報告禁止**: 実行していない項目を実行済みと報告しない