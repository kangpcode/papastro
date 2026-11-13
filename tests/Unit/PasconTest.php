<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v5.0.0 — created by kangpcode (Dhafa Nazula Permadi)
 *  Free to use & extend. This watermark must remain in source code.
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use Papastro\Pascon\Pascon;
use Papastro\View\ViewEngine;
use App\Modules\Blog\Models\Post;

class PasconTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->bootApp();
    }

    public function testPasconSvgRendersCorrectly(): void
    {
        $svg = Pascon::svg('rocket', ['size' => 20, 'class' => 'test-icon']);

        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('width="20"', $svg);
        $this->assertStringContainsString('height="20"', $svg);
        $this->assertStringContainsString('class="test-icon"', $svg);
        $this->assertStringContainsString('viewBox="0 0 24 24"', $svg);
    }

    public function testPasconGlobalHelper(): void
    {
        $svg = pascon('pencil');
        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('</svg>', $svg);
    }

    public function testBlogCreateViewCanBeRendered(): void
    {
        $viewEngine = $this->app->make(ViewEngine::class);
        $html = $viewEngine->render('Blog::create');

        $this->assertStringContainsString('Write New Article', $html);
        $this->assertStringContainsString('<form action="/blog" method="POST">', $html);
        $this->assertStringContainsString('name="title"', $html);
        $this->assertStringContainsString('name="body"', $html);
    }

    public function testBlogEditViewCanBeRendered(): void
    {
        $viewEngine = $this->app->make(ViewEngine::class);
        $post = Post::newFromBuilder([
            'id' => 42,
            'title' => 'Test Post Title',
            'slug' => 'test-post-title',
            'excerpt' => 'Test excerpt',
            'body' => 'Test post body content',
            'published' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $html = $viewEngine->render('Blog::edit', ['post' => $post]);

        $this->assertStringContainsString('Edit Article', $html);
        $this->assertStringContainsString('value="Test Post Title"', $html);
        $this->assertStringContainsString('Test post body content', $html);
        $this->assertStringContainsString('<form action="/blog/42" method="POST">', $html);
    }
}
