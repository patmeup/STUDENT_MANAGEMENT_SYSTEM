<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Date; 
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        
    }

    public function boot(): void
    {
        date_default_timezone_set('Asia/Manila');
        
        config(['app.timezone' => 'Asia/Manila']);
    }
}