<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

use Papastro\Router\RouteFacade as Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| These routes are loaded by the RouteServiceProvider.
| Modules register their own routes in app/Modules/[Module]/Routes/web.php
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/offline', function () {
    return view('offline');
})->name('offline');

/*
|--------------------------------------------------------------------------
| Pasway Realtime Routes
|--------------------------------------------------------------------------
*/
Route::post('/pasway/auth', [\Papastro\Pasway\AuthController::class, 'authenticate'])->name('pasway.auth');
Route::get('/pasway/sse', [\Papastro\Pasway\AuthController::class, 'sse'])->name('pasway.sse');
