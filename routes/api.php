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
| API Routes
|--------------------------------------------------------------------------
| These routes are prefixed with /api automatically by RouteServiceProvider.
*/

Route::get('/health', function () {
    return response()->json([
        'status'  => 'ok',
        'version' => PAPASTRO_VERSION,
        'time'    => date('c'),
    ]);
})->name('api.health');
