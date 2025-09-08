<?php
/**
 * Mobile Order System - ECS Health Check Endpoint
 * ALB Target Group Health Check用
 */

// ヘルスチェック専用エンドポイント
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    // 基本システムチェック
    $checks = [
        'timestamp' => date('c'),
        'status' => 'ok',
        'service' => 'mobile-order-web',
        'checks' => []
    ];
    
    // PHP バージョンチェック
    $checks['checks']['php'] = [
        'status' => 'ok',
        'version' => PHP_VERSION
    ];
    
    // Laravel フレームワークチェック
    if (file_exists('/var/www/html/bootstrap/app.php')) {
        $checks['checks']['laravel'] = [
            'status' => 'ok',
            'message' => 'Framework loaded'
        ];
    } else {
        $checks['checks']['laravel'] = [
            'status' => 'error',
            'message' => 'Framework not found'
        ];
        $checks['status'] = 'error';
    }
    
    // ストレージ書き込みチェック
    $storageWritable = is_writable('/var/www/html/storage');
    $checks['checks']['storage'] = [
        'status' => $storageWritable ? 'ok' : 'error',
        'writable' => $storageWritable
    ];
    
    if (!$storageWritable) {
        $checks['status'] = 'error';
    }
    
    // レスポンス送信
    http_response_code($checks['status'] === 'ok' ? 200 : 503);
    echo json_encode($checks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Throwable $e) {
    // エラー時のレスポンス
    http_response_code(503);
    echo json_encode([
        'timestamp' => date('c'),
        'status' => 'error',
        'service' => 'mobile-order-web',
        'error' => [
            'message' => $e->getMessage(),
            'code' => $e->getCode()
        ]
    ], JSON_PRETTY_PRINT);
}

exit;