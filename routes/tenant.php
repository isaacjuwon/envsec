<?php

use Illuminate\Support\Facades\Route;

\App\Support\Tenant::routes(function () {
    Route::redirect('tenant', 'tenant/platform');
    Route::redirect('tenant', 'tenant/platform')->name('tenant.settings');

    Route::livewire('tenant/billing', 'pages::tenant.settings.billing')->name('tenant.billing');
    Route::livewire('tenant/platform', 'pages::tenant.settings.platform')->name('tenant.platform');
});
