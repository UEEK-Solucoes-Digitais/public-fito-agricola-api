<?php

namespace App\Providers;

use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Maatwebsite\Excel\Facades\Excel;

class HelperComponents extends ServiceProvider
{

    public function register()
    {
        foreach (glob(app_path('Helpers') . '/*.php') as $file) {
            require_once $file;
        }
    }

    public function boot()
    {
        view()->composer('*', function ($view) {
        });
    }
}
