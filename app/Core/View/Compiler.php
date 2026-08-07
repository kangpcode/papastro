<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v1.1.0
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Papastro\View;

use Papastro\Foundation\Application;

/**
 * Compiler — converts .pstro templates to executable PHP.
 *
 * .pstro template syntax:
 *
 * Frontmatter (server-side PHP):
 * ---
 * use App\Modules\Blog\Models\Post;
 * $posts = Post::latest()->get();
 * ---
 *
 * HTML body with directives:
 * - {!! $raw !!}                         → raw HTML output
 * - {{ $var }}                           → escaped HTML output
 * - @foreach ($items as $item) ... @endforeach
 * - @if ($cond) ... @elseif ... @else ... @endif
 * - @include('template')
 * - @layout('layouts.base')
 * - @section('name') ... @endsection
 * - @yield('name')
 * - <island:load component="Blog/Comments" :postId="$post->id" />
 */
class Compiler
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Compile a .pstro file into executable PHP.
     */
    public function compile(string $path): string
    {
        $content = file_get_contents($path);

        // Extract and handle frontmatter
        $content = $this->compileFrontmatter($content);

        // Compile directives in order
        $content = $this->compileIslands($content);
        $content = $this->compileEchos($content);
        $content = $this->compileDirectives($content);
        $content = $this->compileLayout($content);

        return $content;
    }

    /**
     * Extract PHP frontmatter (---...---) and inject as PHP.
     */
    protected function compileFrontmatter(string $content): string
    {
        if (preg_match('/^---\s*\n(.*?)\n---\s*\n/s', $content, $matches)) {
            $php  = "<?php\n" . $matches[1] . "\n?>";
            return $php . "\n" . substr($content, strlen($matches[0]));
        }
        return $content;
    }

    /**
     * Compile island directives to server-side placeholder + hydration scripts.
     *
     * Supported forms:
     *   <island:load component="Blog/Comments" :postId="$id" />
     *   <ComponentName client:load :prop="$val" />
     */
    protected function compileIslands(string $content): string
    {
        // <island:STRATEGY component="..." :prop="$val" />
        $content = preg_replace_callback(
            '/<island:(load|idle|visible|only)\s+component="([^"]+)"([^>]*?)\/>/s',
            function ($m) {
                $strategy  = $m[1];
                $component = $m[2];
                $attrs     = $this->parseIslandAttributes($m[3]);
                return $this->buildIslandPhp($component, $strategy, $attrs);
            },
            $content
        );

        // <ComponentName client:STRATEGY ... />
        $content = preg_replace_callback(
            '/<([A-Z][a-zA-Z]+)\s+client:(load|idle|visible|only)([^>]*?)\/>/s',
            function ($m) {
                $component = $m[1];
                $strategy  = $m[2];
                $attrs     = $this->parseIslandAttributes($m[3]);
                return $this->buildIslandPhp($component, $strategy, $attrs);
            },
            $content
        );

        return $content;
    }

    /**
     * Parse attributes string into key-value array for island directives.
     */
    protected function parseIslandAttributes(string $attrsString): array
    {
        $attrs = [];
        // Match :prop="$phpExpr" and prop="literal"
        preg_match_all('/:?([a-zA-Z][a-zA-Z0-9_-]*)="([^"]*)"/', $attrsString, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $attrs[$match[1]] = $match[2];
        }
        return $attrs;
    }

    /**
     * Generate PHP code for an island component.
     */
    protected function buildIslandPhp(string $component, string $strategy, array $attrs): string
    {
        $attrsJson = [];
        foreach ($attrs as $key => $val) {
            $attrsJson[] = '"' . $key . '": <?php echo json_encode(' . $val . '); ?>';
        }
        $propsOutput = implode(', ', $attrsJson);

        $id = 'island-' . strtolower(str_replace('/', '-', $component)) . '-' . substr(md5($component . $strategy), 0, 6);

        return <<<PHP
<?php
\$__islandProps = [];
PHP
        . "\n" . implode("\n", array_map(function ($k, $v) {
            return "<?php \$__islandProps['{$k}'] = {$v}; ?>";
        }, array_keys($attrs), array_values($attrs)))
        . <<<PHP

<?php \Papastro\Island\IslandRenderer::renderPlaceholder('{$id}', '{$component}', '{$strategy}', \$__islandProps); ?>
PHP;
    }

    /**
     * Compile echo statements: {{ $var }} and {!! $raw !!}
     */
    protected function compileEchos(string $content): string
    {
        // Raw unescaped: {!! $expr !!}
        $content = preg_replace('/\{!!\s*(.*?)\s*!!\}/s', '<?php echo $1; ?>', $content);

        // Escaped: {{ $expr }}
        $content = preg_replace('/\{\{\s*(.*?)\s*\}\}/s', '<?php echo htmlspecialchars((string)($1), ENT_QUOTES, \'UTF-8\'); ?>', $content);

        return $content;
    }

    /**
     * Compile template directives.
     */
    protected function compileDirectives(string $content): string
    {
        $replacements = [
            // @foreach / @endforeach
            '/@foreach\s*\((.*?)\)/s'  => '<?php foreach($1): ?>',
            '/@endforeach/'             => '<?php endforeach; ?>',

            // @for / @endfor
            '/@for\s*\((.*?)\)/s'       => '<?php for($1): ?>',
            '/@endfor/'                  => '<?php endfor; ?>',

            // @while / @endwhile
            '/@while\s*\((.*?)\)/s'     => '<?php while($1): ?>',
            '/@endwhile/'               => '<?php endwhile; ?>',

            // @if / @elseif / @else / @endif
            '/@if\s*\((.*?)\)/s'        => '<?php if($1): ?>',
            '/@elseif\s*\((.*?)\)/s'    => '<?php elseif($1): ?>',
            '/@else/'                    => '<?php else: ?>',
            '/@endif/'                   => '<?php endif; ?>',

            // @php / @endphp
            '/@php/'                     => '<?php',
            '/@endphp/'                  => '?>',

            // @csrf
            '/@csrf/'                    => '<?php echo csrf_field(); ?>',

            // @method
            '/@method\([\'"]([^\'"]+)[\'"]\)/' => '<?php echo method_field(\'$1\'); ?>',

            // @include
            '/@include\([\'"]([^\'"]+)[\'"]\)/' => '<?php echo app(\Papastro\View\ViewEngine::class)->render(\'$1\'); ?>',

            // @json
            '/@json\((.*?)\)/s'          => '<?php echo json_encode($1, JSON_UNESCAPED_UNICODE); ?>',

            // @dd — dump and die
            '/@dd\((.*?)\)/s'            => '<?php var_dump($1); die(); ?>',

            // @dump
            '/@dump\((.*?)\)/s'          => '<?php var_dump($1); ?>',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        // @yield('name')
        $content = preg_replace_callback(
            '/@yield\([\'"]([^\'"]+)[\'"]\)/',
            fn ($m) => "<?php echo \$__sections['{$m[1]}'] ?? ''; ?>",
            $content
        );

        // @section('name') ... @endsection
        $content = preg_replace_callback(
            '/@section\([\'"]([^\'"]+)[\'"]\)(.*?)@endsection/s',
            fn ($m) => "<?php \$__sections['{$m[1]}'] = <<<'PSTRO_SECTION'\n{$m[2]}\nPSTRO_SECTION; ?>",
            $content
        );

        return $content;
    }

    /**
     * Compile @layout directive to wrap content in a layout.
     */
    protected function compileLayout(string $content): string
    {
        if (preg_match('/@layout\([\'"]([^\'"]+)[\'"]\)/', $content, $matches)) {
            $layout  = $matches[1];
            $content = str_replace($matches[0], '', $content);

            // Wrap: render the layout and inject this page as @yield('content')
            $wrapper = <<<PHP
<?php
\$__sections['content'] = (function() use (\$__data) {
    extract(\$__data, EXTR_SKIP);
    ob_start();
?>
PHP;
            $footer = <<<PHP
<?php
    return ob_get_clean();
})();
echo app(\Papastro\View\ViewEngine::class)->render('{$layout}', array_merge(\$__data, ['__sections' => \$__sections]));
?>
PHP;
            $content = $wrapper . "\n" . $content . "\n" . $footer;
        }

        return $content;
    }
}
