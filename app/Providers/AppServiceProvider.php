<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. MySQL 5.7 / MariaDB utf8mb4 index length fix for Hostinger
        Schema::defaultStringLength(191);

        // 2. Hostinger Self-Healing Directory Auto-Creation
        // Hostinger shared hosting and Git deployments frequently lose empty storage subfolders.
        // We guarantee their existence and permissions on runtime to prevent 500 fatal errors.
        $this->ensureStorageDirectoriesExist();
    }

    /**
     * Ensure all required storage and cache directories exist with proper permissions.
     */
    protected function ensureStorageDirectoriesExist(): void
    {
        $directories = [
            storage_path('framework/views'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/testing'),
            storage_path('app/public/listings'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                @mkdir($directory, 0775, true);
            }
        }
    }
}
