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
use App\Modules\Blog\Http\Controllers\PostController;

Route::get('/blog',            [PostController::class, 'index'])->name('blog.index');
Route::get('/blog/create',     [PostController::class, 'create'])->name('blog.create');
Route::post('/blog',           [PostController::class, 'store'])->name('blog.store');
Route::get('/blog/{slug}',     [PostController::class, 'show'])->name('blog.show');
Route::get('/blog/{id}/edit',  [PostController::class, 'edit'])->name('blog.edit');
Route::put('/blog/{id}',       [PostController::class, 'update'])->name('blog.update');
Route::delete('/blog/{id}',    [PostController::class, 'destroy'])->name('blog.destroy');

// Island route for post detail with hydration + SEO
Route::island('/blog/{slug}/view', 'Blog/PostDetail')
    ->seo(fn ($post) => [
        'title'       => $post?->title ?? 'Blog Post',
        'description' => $post?->excerpt ?? '',
    ]);
