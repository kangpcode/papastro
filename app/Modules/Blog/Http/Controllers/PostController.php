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

declare(strict_types=1);

namespace App\Modules\Blog\Http\Controllers;

use Papastro\Http\Request;
use Papastro\Http\Response;
use App\Modules\Blog\Models\Post;

class PostController
{
    public function index(): Response
    {
        $posts = Post::published()->latest()->paginate(10);
        return view('Blog::index', compact('posts'));
    }

    public function show(string $slug): Response
    {
        $post = Post::findBySlug($slug);

        if (!$post) {
            abort(404, "Post [{$slug}] not found.");
        }

        return view('Blog::show', compact('post'));
    }

    public function create(): Response
    {
        return view('Blog::create');
    }

    public function store(Request $request): Response
    {
        $data = $request->validate([
            'title'   => 'required|min:3',
            'body'    => 'required|min:10',
            'excerpt' => 'nullable',
        ]);

        $data['user_id']   = session('user_id') ?? 1;
        $data['published'] = 0;

        $post = Post::create($data);

        return redirect("/blog/{$post->slug}");
    }

    public function edit(string $id): Response
    {
        $post = Post::findOrFail($id);
        return view('Blog::edit', compact('post'));
    }

    public function update(Request $request, string $id): Response
    {
        $post = Post::findOrFail($id);
        $data = $request->validate([
            'title' => 'required',
            'body'  => 'required',
        ]);
        $post->update($data);
        return redirect("/blog/{$post->slug}");
    }

    public function destroy(string $id): Response
    {
        Post::findOrFail($id)->delete();
        return redirect('/blog');
    }
}
