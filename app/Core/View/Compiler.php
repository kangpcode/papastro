<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v3.0.0
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
 * Frontmatter (server-side PHP, supports leading HTML comments):
 * ---
 * use App\Modules\Blog\Models\Post;
 * $posts = Post::latest()->get();
 * ---
 *
 * HTML body with directives:
 * - {!! $raw !!}                         → raw HTML output
 * - {{ $var }}                           → escaped HTML output
 * - @foreach / @forelse / @for / @while  → loops
 * - @if / @elseif / @else / @unless      → conditionals
 * - @isset / @empty / @auth / @guest     → state checks
 * - @include('template')                 → partial inclusion
 * - @layout('layouts.base')              → master layout wrapping
 * - @section('name') ... @endsection     → buffered section capture
 * - @yield('name', 'default')            → section output
 * - <island:load component="Blog/Comments" :postId="$post->id" />
 * - <Counter client:visible initialCount="0" />
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
        return $this->compileString($content);
    }

    /**
     * Compile a raw .pstro template string into executable PHP.
     */
    public function compileString(string $content): string
    {
        // 1. Extract frontmatter and @layout directive first
        [$frontmatterPhp, $content] = $this->extractFrontmatter($content);
        [$layout, $content] = $this->extractLayout($content);

        // 2. Compile islands, echos, and directives
        $content = $this->compileIslands($content);
        $content = $this->compilePascon($content);
        $content = $this->compileEchos($content);
        $content = $this->compileDirectives($content);

        // 3. Assemble with frontmatter and optional layout wrapper
        if ($layout !== null) {
            return $this->wrapWithLayout($frontmatterPhp, $content, $layout);
        }

        return ($frontmatterPhp ? $frontmatterPhp . "\n" : '') . $content;
    }

    /**
     * Extract PHP frontmatter (---...---) even when preceded by HTML comments or whitespace.
     *
     * @return array{0: string, 1: string} [frontmatterPhp, remainingContent]
     */
    protected function extractFrontmatter(string $content): array
    {
        if (preg_match('/\A((?:\s*<!--.*?-->\s*)*)---\s*\r?\n(.*?)\r?\n---\s*\r?\n?/s', $content, $matches)) {
            $leadingComments = trim($matches[1]);
            $phpCode = trim($matches[2]);
            $frontmatterPhp = "<?php\n" . $phpCode . "\n?>";
            $remaining = ($leadingComments !== '' ? $leadingComments . "\n" : '') . substr($content, strlen($matches[0]));
            return [$frontmatterPhp, $remaining];
        }

        return ['', $content];
    }

    /**
     * Extract @layout('...') directive if present.
     *
     * @return array{0: ?string, 1: string} [layoutName, remainingContent]
     */
    protected function extractLayout(string $content): array
    {
        if (preg_match('/@layout\([\'"]([^\'"]+)[\'"]\)/', $content, $matches)) {
            $layout = $matches[1];
            $content = str_replace($matches[0], '', $content);
            return [$layout, $content];
        }

        return [null, $content];
    }

    /**
     * Wrap compiled template with layout rendering logic.
     * Supports both explicit @section('content') and implicit body content.
     */
    protected function wrapWithLayout(string $frontmatterPhp, string $bodyContent, string $layout): string
    {
        $escapedLayout = addslashes($layout);

        return <<<PHP
{$frontmatterPhp}
<?php
ob_start();
?>
{$bodyContent}
<?php
\$__implicitBody = trim(ob_get_clean());
if (!isset(\$__sections['content']) && \$__implicitBody !== '') {
    \$__sections['content'] = \$__implicitBody;
}
\$__layoutData = array_merge(\$__data ?? [], get_defined_vars(), ['__sections' => \$__sections ?? []]);
unset(\$__layoutData['__data'], \$__layoutData['__implicitBody'], \$__layoutData['path']);
echo app(\\Papastro\\View\\ViewEngine::class)->render('{$escapedLayout}', \$__layoutData);
?>
PHP;
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
                $strategy = $m[1];
                $component = $m[2];
                $attrs = $this->parseIslandAttributes($m[3]);
                return $this->buildIslandPhp($component, $strategy, $attrs);
            },
            $content
        );

        // <ComponentName client:STRATEGY ... />
        $content = preg_replace_callback(
            '/<([A-Z][a-zA-Z0-9_\/]*)\s+client:(load|idle|visible|only)([^>]*?)\/>/s',
            function ($m) {
                $component = $m[1];
                $strategy = $m[2];
                $attrs = $this->parseIslandAttributes($m[3]);
                return $this->buildIslandPhp($component, $strategy, $attrs);
            },
            $content
        );

        return $content;
    }

    /**
     * Parse attributes string into key-value array for island directives.
     * Returns array of ['key' => ['value' => ..., 'isExpr' => bool]]
     */
    protected function parseIslandAttributes(string $attrsString): array
    {
        $attrs = [];
        preg_match_all('/(:?)([a-zA-Z][a-zA-Z0-9_-]*)="([^"]*)"/', $attrsString, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $isDynamic = $match[1] === ':';
            $key = $match[2];
            $val = $match[3];

            // Also check if literal contains {{ $expr }}
            if (!$isDynamic && preg_match('/^\{\{\s*(.*?)\s*\}\}$/', $val, $echoMatch)) {
                $isDynamic = true;
                $val = $echoMatch[1];
            }

            $attrs[$key] = [
                'value' => $val,
                'dynamic' => $isDynamic,
            ];
        }
        return $attrs;
    }

    /**
     * Generate PHP code for an island component.
     */
    protected function buildIslandPhp(string $component, string $strategy, array $attrs): string
    {
        $id = 'island-' . strtolower(str_replace(['/', '_'], '-', $component)) . '-' . substr(md5($component . $strategy . serialize($attrs)), 0, 6);

        $propLines = [];
        foreach ($attrs as $key => $info) {
            $escapedKey = addslashes($key);
            if ($info['dynamic']) {
                $propLines[] = "\$__islandProps['{$escapedKey}'] = {$info['value']};";
            } else {
                $val = $info['value'];
                if (is_numeric($val)) {
                    $propLines[] = "\$__islandProps['{$escapedKey}'] = " . ($val + 0) . ";";
                } elseif ($val === 'true' || $val === 'false') {
                    $propLines[] = "\$__islandProps['{$escapedKey}'] = {$val};";
                } else {
                    $escapedVal = addslashes($val);
                    $propLines[] = "\$__islandProps['{$escapedKey}'] = '{$escapedVal}';";
                }
            }
        }

        $propsSetup = implode("\n", $propLines);
        $escapedComponent = addslashes($component);
        $escapedStrategy = addslashes($strategy);

        return <<<PHP
<?php
\$__islandProps = [];
{$propsSetup}
\\Papastro\\Island\\IslandRenderer::renderPlaceholder('{$id}', '{$escapedComponent}', '{$escapedStrategy}', \$__islandProps);
?>
PHP;
    }

    /**
     * Compile Pascon native SVG icon tags: <pascon:name attr="val" />
     */
    protected function compilePascon(string $content): string
    {
        return preg_replace_callback(
            '/<pascon:([\w\-]+)([^>]*)\/?>/i',
            function ($matches) {
                $name = addslashes($matches[1]);
                $attrs = addslashes($matches[2]);
                return "<?php echo \\Papastro\\Pascon\\Pascon::renderTag('{$name}', '{$attrs}'); ?>";
            },
            $content
        );
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
     * Balanced-parenthesis regex fragment (named group "p") so nested calls like
     * @if(!empty($x) && count($y) > 1) compile correctly.
     */
    protected const BAL = '(?<p>\((?:[^()]++|(?&p))*\))';

    /**
     * Compile template directives.
     */
    protected function compileDirectives(string $content): string
    {
        $b = self::BAL;
        $pre = '(?<![\w@])';

        // @section('a', expr) inline  |  @section('a') ... @endsection block  (sequential, stack based)
        $stack = [];
        $content = preg_replace_callback(
            "/{$pre}@section\\s*{$b}|{$pre}@endsection\\b/s",
            function ($m) use (&$stack) {
                if (!isset($m['p'])) {
                    $name = array_pop($stack);
                    return $name === null ? $m[0] : "<?php \$__sections['{$name}'] = ob_get_clean(); ?>";
                }
                $inner = substr($m['p'], 1, -1);
                if (preg_match('/^\s*[\'"]([^\'"]+)[\'"]\s*,\s*(.+)$/s', $inner, $x)) {
                    return "<?php \$__sections['{$x[1]}'] = htmlspecialchars((string)({$x[2]}), ENT_QUOTES, 'UTF-8'); ?>";
                }
                if (preg_match('/^\s*[\'"]([^\'"]+)[\'"]\s*$/', $inner, $x)) {
                    $stack[] = $x[1];
                    return '<?php ob_start(); ?>';
                }
                return $m[0];
            },
            $content
        );

        // @yield('name'[, default])
        $content = preg_replace_callback("/{$pre}@yield\\s*{$b}/s", function ($m) {
            $inner = substr($m['p'], 1, -1);
            if (!preg_match('/^\s*[\'"]([^\'"]+)[\'"]\s*(?:,\s*(.+))?$/s', $inner, $x)) {
                return $m[0];
            }
            $default = isset($x[2]) && trim($x[2]) !== '' ? $x[2] : "''";
            return "<?php echo \$__sections['{$x[1]}'] ?? {$default}; ?>";
        }, $content);

        // @include('template'[, [data]])
        $content = preg_replace_callback("/{$pre}@include\\s*{$b}/s", function ($m) {
            $inner = substr($m['p'], 1, -1);
            if (!preg_match('/^\s*[\'"]([^\'"]+)[\'"]\s*(?:,\s*(.+))?$/s', $inner, $x)) {
                return $m[0];
            }
            $data = isset($x[2]) && trim($x[2]) !== '' ? "array_merge(\$__data ?? [], {$x[2]})" : '($__data ?? [])';
            return "<?php echo app(\\Papastro\\View\\ViewEngine::class)->render('{$x[1]}', {$data}); ?>";
        }, $content);

        // Directives that take a balanced expression
        $withExpr = [
            'foreach' => '<?php foreach$1: ?>',
            'forelse' => '<?php if(!empty(__FORELSE_PLACEHOLDER__)): foreach$1: ?>',
            'for'     => '<?php for$1: ?>',
            'while'   => '<?php while$1: ?>',
            'elseif'  => '<?php elseif$1: ?>',
            'if'      => '<?php if$1: ?>',
            'unless'  => '<?php if(!$1): ?>',
            'isset'   => '<?php if(isset$1): ?>',
            'empty'   => '<?php if(empty$1): ?>',
            'json'    => '<?php echo json_encode$1, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>',
            'dd'      => '<?php var_dump$1; die(); ?>',
            'dump'    => '<?php var_dump$1; ?>',
            'method'  => '<?php echo method_field$1; ?>',
            'pascon'  => '<?php echo pascon$1; ?>',
        ];

        // @forelse is rewritten manually (needs the iterable expression twice)
        $content = preg_replace_callback("/{$pre}@forelse\\s*{$b}/s", function ($m) {
            $inner = substr($m['p'], 1, -1);
            if (!preg_match('/^\s*(.+?)\s+as\s+(.+)$/s', $inner, $x)) {
                return $m[0];
            }
            return "<?php if(!empty({$x[1]})): foreach({$inner}): ?>";
        }, $content);
        unset($withExpr['forelse']);

        foreach ($withExpr as $name => $replacement) {
            $content = preg_replace_callback("/{$pre}@{$name}\\s*{$b}/s", function ($m) use ($name, $replacement) {
                $expr = $m['p'];
                return match ($name) {
                    'unless' => '<?php if(!' . $expr . '): ?>',
                    'json'   => '<?php echo json_encode(' . substr($expr, 1, -1) . ', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>',
                    'dd'     => '<?php var_dump(' . substr($expr, 1, -1) . '); die(); ?>',
                    'dump'   => '<?php var_dump(' . substr($expr, 1, -1) . '); ?>',
                    'method' => '<?php echo method_field(' . substr($expr, 1, -1) . '); ?>',
                    default  => str_replace('$1', $expr, $replacement),
                };
            }, $content);
        }

        $simple = [
            '/(?<![\w@])@empty\b(?!\s*\()/'      => '<?php else: ?>',          // @forelse ... @empty ... @endforelse
            '/(?<![\w@])@endforelse\b/'          => '<?php endif; ?>',
            '/(?<![\w@])@endforeach\b/'          => '<?php endforeach; ?>',
            '/(?<![\w@])@endfor\b/'              => '<?php endfor; ?>',
            '/(?<![\w@])@endwhile\b/'            => '<?php endwhile; ?>',
            '/(?<![\w@])@else\b/'                => '<?php else: ?>',
            '/(?<![\w@])@endif\b/'               => '<?php endif; ?>',
            '/(?<![\w@])@endunless\b/'           => '<?php endif; ?>',
            '/(?<![\w@])@endisset\b/'            => '<?php endif; ?>',
            '/(?<![\w@])@endempty\b/'            => '<?php endif; ?>',
            '/(?<![\w@])@auth\b/'                => '<?php if(!empty(session(\'user_id\'))): ?>',
            '/(?<![\w@])@endauth\b/'             => '<?php endif; ?>',
            '/(?<![\w@])@guest\b/'               => '<?php if(empty(session(\'user_id\'))): ?>',
            '/(?<![\w@])@endguest\b/'            => '<?php endif; ?>',
            '/(?<![\w@])@production\b/'          => '<?php if(app()->isProduction()): ?>',
            '/(?<![\w@])@endproduction\b/'       => '<?php endif; ?>',
            '/(?<![\w@])@php\b/'                 => '<?php',
            '/(?<![\w@])@endphp\b/'              => '?>',
            '/(?<![\w@])@csrf\b/'                => '<?php echo csrf_field(); ?>',
        ];

        foreach ($simple as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }
}
