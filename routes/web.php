<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth:sanctum', 'verified'])->get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

Route::middleware(config('fortify.middleware', ['web']))->prefix('metamask')->group(function () {
    $limiter = config('fortify.limiters.metamask');

    Route::get('/ethereum/signature', [\App\Http\Controllers\Web3AuthController::class, 'signature'])
        ->name('metamask.signature')
        ->middleware('guest:'.config('fortify.guard'));

    Route::post('/ethereum/authenticate', [\App\Http\Controllers\Web3AuthController::class, 'authenticate'])
        ->middleware(array_filter([
            'guest:'.config('fortify.guard'),
            $limiter ? 'throttle:'.$limiter : null,
        ]))->name('metamask.authenticate');
});
