<?php

namespace App\Helpers;

class AdminHelper
{
    /**
     * 管理者画面のURLプレフィックスを取得
     */
    public static function getAdminPrefix(): string
    {
        return config('app.admin_prefix', 'admin');
    }
    
    /**
     * 管理者画面のフルURLを生成
     */
    public static function adminUrl(string $path = ''): string
    {
        $prefix = self::getAdminPrefix();
        $path = ltrim($path, '/');
        
        if (empty($path)) {
            return url("/{$prefix}");
        }
        
        return url("/{$prefix}/{$path}");
    }
    
    /**
     * 管理者ルート名を生成
     */
    public static function adminRoute(string $routeName, array $parameters = []): string
    {
        return route("admin.{$routeName}", $parameters);
    }
    
    /**
     * 現在のリクエストが管理者エリアかチェック
     */
    public static function isAdminArea(): bool
    {
        $prefix = self::getAdminPrefix();
        return request()->is("{$prefix}") || request()->is("{$prefix}/*");
    }
}