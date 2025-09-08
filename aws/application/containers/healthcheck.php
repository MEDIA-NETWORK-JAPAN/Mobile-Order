<?php
/**
 * ================================================
 * Mobile Order System - Health Check Endpoint
 * ECS ALB Health Checkと詳細システム状態確認
 * ================================================
 */

header('Content-Type: application/json; charset=utf-8');

// Simple health check for ALB
if (isset($_GET['simple'])) {
    http_response_code(200);
    echo json_encode(['status' => 'OK', 'timestamp' => date('c')]);
    exit;
}

$health = [
    'status' => 'OK',
    'timestamp' => date('c'),
    'checks' => []
];

$allPassed = true;

// 1. PHP基本チェック
try {
    $health['checks']['php'] = [
        'version' => PHP_VERSION,
        'memory_limit' => ini_get('memory_limit'),
        'memory_usage' => memory_get_usage(true),
        'status' => 'OK'
    ];
} catch (Exception $e) {
    $health['checks']['php'] = ['status' => 'FAIL', 'error' => $e->getMessage()];
    $allPassed = false;
}

// 2. Laravel フレームワークチェック
try {
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
        
        // Laravel app instance check
        if (file_exists(__DIR__ . '/../bootstrap/app.php')) {
            $app = require_once __DIR__ . '/../bootstrap/app.php';
            $health['checks']['laravel'] = [
                'framework' => 'Laravel',
                'version' => app()->version(),
                'status' => 'OK'
            ];
        } else {
            $health['checks']['laravel'] = ['status' => 'FAIL', 'error' => 'Bootstrap file not found'];
            $allPassed = false;
        }
    } else {
        $health['checks']['laravel'] = ['status' => 'FAIL', 'error' => 'Composer autoload not found'];
        $allPassed = false;
    }
} catch (Exception $e) {
    $health['checks']['laravel'] = ['status' => 'FAIL', 'error' => $e->getMessage()];
    $allPassed = false;
}

// 3. ディスク容量チェック
try {
    $diskFree = disk_free_space(__DIR__);
    $diskTotal = disk_total_space(__DIR__);
    $diskUsagePercent = (($diskTotal - $diskFree) / $diskTotal) * 100;
    
    $health['checks']['disk'] = [
        'free_bytes' => $diskFree,
        'total_bytes' => $diskTotal,
        'usage_percent' => round($diskUsagePercent, 2),
        'status' => $diskUsagePercent > 90 ? 'WARN' : 'OK'
    ];
    
    if ($diskUsagePercent > 95) {
        $allPassed = false;
    }
} catch (Exception $e) {
    $health['checks']['disk'] = ['status' => 'FAIL', 'error' => $e->getMessage()];
    $allPassed = false;
}

// 4. 必要なディレクトリの書き込み権限チェック
$writableDirs = [
    __DIR__ . '/../storage/logs',
    __DIR__ . '/../storage/framework/cache',
    __DIR__ . '/../storage/framework/sessions',
    __DIR__ . '/../storage/framework/views',
    __DIR__ . '/../bootstrap/cache'
];

$health['checks']['permissions'] = [];
foreach ($writableDirs as $dir) {
    if (is_dir($dir) && is_writable($dir)) {
        $health['checks']['permissions'][$dir] = 'OK';
    } else {
        $health['checks']['permissions'][$dir] = 'FAIL';
        $allPassed = false;
    }
}

// 5. 環境変数チェック（セキュリティ考慮で値は出力しない）
$requiredEnvVars = ['APP_KEY', 'DB_HOST', 'DB_DATABASE'];
$health['checks']['environment'] = [];
foreach ($requiredEnvVars as $var) {
    $health['checks']['environment'][$var] = getenv($var) !== false ? 'SET' : 'MISSING';
    if (getenv($var) === false) {
        $allPassed = false;
    }
}

// 最終ステータス設定
if (!$allPassed) {
    $health['status'] = 'FAIL';
    http_response_code(503);
} else {
    http_response_code(200);
}

// Response output
echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);