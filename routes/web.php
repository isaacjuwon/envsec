<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\OauthController;

Route::view('/', 'welcome')->name('home');


Route::prefix('auth')->group(
    function () {
        // OAuth
        Route::get('/redirect/{provider}', [OauthController::class , 'redirect'])->name('oauth.redirect');
        Route::get('/callback/{provider}', [OauthController::class , 'callback'])->name('oauth.callback');


    }
);
Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('onboarding', 'pages::onboarding.onboarding')->name('onboarding');
});


\App\Support\Tenant::routes(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('queue/failed', 'pages::queue.failed-jobs')->name('queue.failed');
    Route::livewire('queue/history', 'pages::queue.job-queue')->name('queue.history');

    // Projects
    Route::livewire('projects', 'pages::projects.index')->name('projects.index');
    Route::livewire('projects/create', 'pages::projects.create')->name('projects.create');
    Route::livewire('projects/{projectId}/edit', 'pages::projects.edit')->name('projects.edit');

    // Projects → Secrets
    Route::prefix('projects/{projectId}/secrets')->name('projects.secrets.')->group(function () {
            Route::livewire('/', 'pages::secrets.index')->name('index');
            Route::livewire('/{secretId}', 'pages::secrets.show')->name('show');
        }
        );
    });

require __DIR__ . '/settings.php';
