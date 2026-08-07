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
use App\Modules\Product\Http\Controllers\ProductController;

Route::get('/product', [ProductController::class, 'index'])->name('api.product.index');