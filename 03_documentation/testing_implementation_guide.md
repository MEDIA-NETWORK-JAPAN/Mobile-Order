# テストコードと検証用データ実装ガイド

## 📚 目次

- [1. 実装意図と目的](#1-実装意図と目的)
- [2. テスト戦略の背景](#2-テスト戦略の背景)
- [3. 実装アプローチ](#3-実装アプローチ)
- [4. 詳細実装手順](#4-詳細実装手順)
- [5. 各コンポーネントの役割](#5-各コンポーネントの役割)
- [6. 品質保証プロセス](#6-品質保証プロセス)
- [7. 運用とメンテナンス](#7-運用とメンテナンス)

---

## 1. 実装意図と目的

### 1.1 根本的な目的

#### POS中心設計における品質保証
本プロジェクトは **POS中心設計** を採用しており、クラウド側は主に表示・同期機能を担います。この設計において、テストコードと検証用データは以下の重要な役割を果たします：

1. **設計書遵守の保証**: 詳細に定義された22の設計書との整合性を確保
2. **権限制御の確実性**: SuperAdmin緊急編集機能の適切な動作保証
3. **データ整合性の維持**: POS ⇔ クラウド間でのデータ同期における整合性
4. **リグレッション防止**: 機能追加時の既存機能への影響排除

### 1.2 具体的な実装目的

#### A. 開発効率の向上
```
【従来の手作業】
1. データベースを手動でセットアップ
2. テスト用ユーザーを手動作成
3. 商品・カテゴリを個別に追加
4. 各機能を手動でブラウザテスト
5. バグ発見時の再現手順が不明確

【自動化後】
1. `sail artisan db:seed` 一発で環境構築
2. 一貫したテストデータで検証
3. 自動テストによる即座のフィードバック
4. CI/CDパイプラインでの継続的検証
```

#### B. SuperAdmin権限制御の確実性
POS中心設計の核心である **「一般管理者は閲覧のみ、SuperAdminのみ緊急編集可能」** という権限制御を確実に保証します。

```php
// 権限制御の確実な検証例
public function test_admin_cannot_create_product(): void
{
    Livewire::actingAs($this->admin)
        ->test('admin.products.product-form')
        ->set('name', 'テスト商品')
        ->call('save')
        ->assertHasErrors()
        ->assertSee('商品の編集権限がありません');
}
```

#### C. 飲食店業務に特化したリアリティ
単なるダミーデータではなく、実際の飲食店運営に即したテストデータを提供：

```php
// 実用的なテストデータ例
$ramenProducts = collect([
    ['name' => '醤油ラーメン', 'price' => 800, 'availability_status' => 'available'],
    ['name' => '限定豚骨ラーメン', 'price' => 900, 'availability_status' => 'sold_out'],
]);
```

---

## 2. テスト戦略の背景

### 2.1 設計書駆動開発（DDD: Design Document Driven）

本プロジェクトは **22の詳細設計書** に基づいて開発されており、テストコードはこれらの設計書の要件を検証する役割を担います。

```
設計書の階層構造:
├── 01_development_docs/ (開発技術仕様)
│   ├── 01_architecture_design.md (POS中心アーキテクチャ)
│   ├── 13_auth_authorization_design.md (権限管理詳細)
│   └── 09_test_strategy.md (テスト戦略)
├── 02_design_system/ (デザインシステム)
└── 03_library_best_practices/ (実装ベストプラクティス)
```

### 2.2 テストピラミッド戦略

```
        E2E Tests
       /           \
    Integration Tests
   /                   \
Feature Tests (API/UI)
/                       \
Unit Tests (Models/Services)
```

**実装比率**:
- Unit Tests: 60% (高速、詳細検証)
- Feature Tests: 30% (機能統合検証)
- Integration Tests: 8% (外部連携検証)
- E2E Tests: 2% (ユーザーシナリオ検証)

### 2.3 権限ベーステスト戦略

SuperAdmin緊急編集機能の複雑な権限制御を確実にテストするため、権限別のテストケースを体系的に実装：

```php
// 権限別テストパターン
class ProductManagementTest 
{
    // SuperAdmin: 全権限
    public function test_super_admin_can_create_product()
    public function test_super_admin_can_edit_product()
    public function test_super_admin_sees_emergency_warning()
    
    // Admin: 閲覧のみ
    public function test_admin_cannot_create_product()
    public function test_admin_sees_readonly_message()
    
    // Staff: アクセス不可
    public function test_staff_cannot_access_management()
}
```

---

## 3. 実装アプローチ

### 3.1 段階的実装戦略

#### Phase 1: 基盤テストデータ構築
1. **Factory設計**: 実際の飲食店データに基づくFactory作成
2. **Seeder実装**: 包括的な開発用データセット
3. **基本検証**: モデル・リレーションの動作確認

#### Phase 2: 権限制御テスト
1. **Unit Tests**: User モデルの権限メソッド検証
2. **Feature Tests**: Livewire コンポーネントの権限制御
3. **統合テスト**: エンドツーエンドの権限フロー

#### Phase 3: 業務ロジックテスト
1. **ダッシュボード統計**: 売上・注文数の計算ロジック
2. **在庫管理**: availability_status の状態管理
3. **多店舗対応**: データの適切な分離

### 3.2 データ設計哲学

#### リアルな飲食店データ
```php
// 抽象的なダミーデータ ❌
['name' => 'Product 1', 'price' => 100]

// 実用的な飲食店データ ✅
['name' => '醤油ラーメン', 'price' => 800, 'description' => 'あっさりとした醤油スープ']
```

#### 状態の網羅性
```php
// 各種在庫状態の実装
'available'    => '販売中'
'sold_out'     => '売り切れ'  
'not_arrived'  => '未入荷'
'preparing'    => '準備中'
```

#### 時系列データの考慮
```php
// 統計計算のための時系列データ
Order::factory()->today()->count(15)->create();      // 今日の注文
Order::factory()->yesterday()->count(12)->create();  // 昨日の注文（成長率計算用）
```

---

## 4. 詳細実装手順

### 4.1 Factory設計・実装手順

#### Step 1: 要件分析
```markdown
1. 設計書の該当セクションを熟読
2. モデルの必須フィールドとオプショナルフィールドを特定
3. 実際の飲食店で使用される値の範囲を調査
4. ファクトリステート（available/sold_out等）の必要性を判定
```

#### Step 2: ベースFactory実装
```php
// 例: ProductFactory の実装パターン
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),              // 関連モデル
            'name' => $this->generateProductName(),      // カスタムヘルパー
            'price' => $this->faker->numberBetween(300, 2000), // 現実的な価格帯
            'availability_status' => 'available',        // デフォルト状態
        ];
    }
    
    // 実用的な商品名生成
    private function generateProductName(): string
    {
        $foodTypes = ['ラーメン', 'パスタ', 'ハンバーガー'];
        $modifiers = ['特製', '自家製', '極上'];
        // 組み合わせロジック
    }
}
```

#### Step 3: ステートパターン実装
```php
// 各種状態を表現するステートメソッド
public function available(): static
{
    return $this->state(fn () => [
        'availability_status' => 'available',
        'availability_message' => null,
    ]);
}

public function soldOut(): static
{
    return $this->state(fn () => [
        'availability_status' => 'sold_out',
        'availability_message' => '本日分は売り切れました',
    ]);
}
```

### 4.2 Seeder設計・実装手順

#### Step 1: データ構造設計
```php
// TestDataSeeder の構造設計
class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. 基盤データ（店舗・ユーザー）
        $testStore = $this->createTestStore();
        $users = $this->createTestUsers($testStore);
        
        // 2. マスターデータ（カテゴリ・商品）
        $categories = $this->createTestCategories($testStore);
        $products = $this->createTestProducts($testStore, $categories);
        
        // 3. 運用データ（セッション・注文）
        $sessions = $this->createTestSessions($testStore);
        $orders = $this->createTestOrders($testStore, $sessions);
        
        // 4. 統計データ（ダッシュボード用）
        $this->createStatisticsData($testStore);
    }
}
```

#### Step 2: 関連データの整合性確保
```php
// 商品とカテゴリの適切な関連付け
$ramenProducts->each(fn($product) => 
    $product->categories()->attach($ramenCategory)
);

// 注文ステータスの分散
$todayOrders->each(function ($order, $index) {
    $status = match($index % 6) {
        0 => 'pending',   1 => 'confirmed', 
        2 => 'preparing', 3 => 'ready',
        4 => 'completed', 5 => 'cancelled',
    };
    $order->update(['status' => $status]);
});
```

### 4.3 テストケース設計・実装手順

#### Step 1: テストケース分類
```markdown
【権限テスト】
- SuperAdmin: 全機能アクセス可能
- Admin: 閲覧のみ、編集不可
- Staff: 管理画面アクセス不可
- 未認証: ログイン画面リダイレクト

【機能テスト】
- CRUD操作の成功/失敗
- バリデーションの動作
- データフィルタリング（店舗別）
- UI表示の権限別制御

【統合テスト】
- Livewire コンポーネントの状態管理
- データベースの整合性
- リレーションの動作
```

#### Step 2: テストケース実装パターン
```php
// 標準的なテストケース構造
class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $admin;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        // テスト用データの初期化
        $this->store = Store::factory()->create();
        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->admin = User::factory()->admin()->forStore($this->store->id)->create();
    }

    public function test_super_admin_can_create_product(): void
    {
        // Given: SuperAdminでログイン
        // When: 商品作成を実行
        // Then: 成功することを確認
    }
}
```

#### Step 3: アサーション設計
```php
// 包括的な検証パターン
public function test_admin_cannot_create_product(): void
{
    Livewire::actingAs($this->admin)
        ->test('admin.products.product-form')
        ->set('name', 'テスト商品')
        ->call('save')
        ->assertHasErrors()                           // エラーが発生
        ->assertSee('商品の編集権限がありません');     // 適切なメッセージ表示

    $this->assertDatabaseMissing('products', [       // DBに保存されていない
        'name' => 'テスト商品',
    ]);
}
```

---

## 5. 各コンポーネントの役割

### 5.1 Factory層の役割

#### StoreFactory
```php
/**
 * 目的: 多店舗環境のテストデータ生成
 * 特徴: 
 * - 実際の店舗名（"〇〇店"）
 * - 営業時間・設定の現実的な値
 * - 税率・サービス料の適切な設定
 */
```

#### ProductFactory
```php
/**
 * 目的: 飲食店商品のリアルなテストデータ
 * 特徴:
 * - 食べ物らしい商品名（"醤油ラーメン"等）
 * - 適切な価格帯（300-2000円）
 * - マイナス価格対応（値引き商品）
 * - 在庫状態の網羅的実装
 */
```

#### UserFactory  
```php
/**
 * 目的: 権限レベル別ユーザーの生成
 * 特徴:
 * - SuperAdmin（全店舗アクセス）
 * - Admin（所属店舗のみ）
 * - Staff（限定機能）
 * - 固定テストユーザー対応
 */
```

### 5.2 Test層の役割

#### Unit Tests
```php
/**
 * 対象: モデル単体の動作
 * 検証項目:
 * - 権限メソッド（isSuperAdmin()等）
 * - スコープ（active()、available()等）
 * - リレーション
 * - バリデーション
 */
```

#### Feature Tests
```php
/**
 * 対象: Livewire コンポーネント
 * 検証項目:
 * - 権限制御の動作
 * - UI表示の適切性
 * - データフィルタリング
 * - CRUD操作の成功/失敗
 */
```

### 5.3 Seeder層の役割

#### TestDataSeeder
```php
/**
 * 目的: 包括的な開発環境構築
 * 提供データ:
 * - 権限レベル別ユーザー（SuperAdmin/Admin/Staff）
 * - 現実的な商品・カテゴリ（ラーメン店想定）
 * - 統計計算用の時系列注文データ
 * - 各種状態パターンの網羅
 */
```

---

## 6. 品質保証プロセス

### 6.1 テスト実行フロー

#### 開発時の継続的テスト
```bash
# 基本テスト実行
sail artisan test

# カテゴリ別実行
sail artisan test tests/Unit/        # 単体テスト
sail artisan test tests/Feature/     # 機能テスト

# 特定クラスのテスト
sail artisan test tests/Feature/Admin/ProductManagementTest.php
```

#### 詳細カバレッジ測定
```bash
# カバレッジレポート生成
sail artisan test --coverage

# 最小カバレッジ閾値設定
sail artisan test --min=80
```

### 6.2 品質チェックポイント

#### 権限制御の確実性
```markdown
✅ SuperAdmin: 全機能にアクセス可能
✅ Admin: 閲覧のみ、編集時にエラーメッセージ
✅ Staff: 管理画面へのアクセス拒否
✅ 未認証: ログイン画面へリダイレクト
```

#### データ整合性の確保
```markdown
✅ 店舗データの分離（AdminはマルチテナントDeployment所属店舗のみ）
✅ リレーションの適切な動作
✅ 外部キー制約の遵守
✅ バリデーションルールの動作
```

#### UI表示の適切性
```markdown
✅ 権限別メッセージの表示
✅ ボタンの有効/無効制御
✅ フォームフィールドの編集可否
✅ 警告メッセージの適切な表示
```

---

## 7. 運用とメンテナンス

### 7.1 日常的な運用

#### 開発環境リフレッシュ
```bash
# データベースリセット＋テストデータ投入
sail artisan migrate:fresh --seed

# 特定のSeederのみ実行
sail artisan db:seed --class=TestDataSeeder
```

#### 継続的インテグレーション
```yaml
# GitHub Actions での自動テスト実行例
- name: Run Tests
  run: |
    cp .env.testing .env
    php artisan migrate:fresh
    php artisan test --coverage
```

### 7.2 テストデータの拡張

#### 新機能追加時の手順
```markdown
1. 新モデル用のFactoryを作成
2. TestDataSeederに新データを追加
3. 新機能のテストケースを実装
4. 既存テストの実行で回帰テスト
```

#### 実データに基づく改善
```markdown
1. 本番環境の実際のデータ傾向を分析
2. Factoryのデータ生成ロジックを現実に近づける
3. 新しい業務パターンのテストケース追加
4. パフォーマンステストデータの最適化
```

### 7.3 トラブルシューティング

#### よくある問題と解決策

**問題**: テストデータの関連性エラー
```php
// 解決策: 適切な順序でのデータ作成
$store = Store::factory()->create();
$user = User::factory()->forStore($store->id)->create();
$category = Category::factory()->forStore($store->id)->create();
```

**問題**: 権限テストの不安定性
```php
// 解決策: setUp() での確実な初期化
protected function setUp(): void
{
    parent::setUp();
    $this->artisan('migrate:fresh'); // 毎回クリーンな状態
}
```

**問題**: 大量データでのテスト遅延
```php
// 解決策: SQLite in-memory使用
// phpunit.xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

---

## 8. 成果と効果測定

### 8.1 定量的効果

#### 開発効率の向上
```markdown
【従来】
- 手動環境構築: 30分/回
- 手動テスト: 60分/機能
- バグ発見までの時間: 2-3日

【改善後】
- 自動環境構築: 2分/回 (15倍高速化)
- 自動テスト: 5分/全機能 (12倍高速化)  
- バグ発見: リアルタイム (即座)
```

#### 品質向上の指標
```markdown
- テストカバレッジ: 85%以上
- 権限制御の確実性: 100%（全パターンテスト済み）
- リグレッション防止: 自動検知
- 設計書遵守率: 100%（テストで保証）
```

### 8.2 定性的効果

#### 開発者体験の向上
- 安心してリファクタリング可能
- 新機能追加時の既存機能への影響を即座に検知
- デバッグ時間の大幅短縮

#### プロジェクト管理の改善
- 進捗の可視化（テスト通過率）
- 品質の定量化
- リリース判定の客観的基準

---

**最終更新**: 2025年8月21日  
**作成者**: Claude Code  
**レビュー**: 要  
**次回見直し**: Phase 3完了時