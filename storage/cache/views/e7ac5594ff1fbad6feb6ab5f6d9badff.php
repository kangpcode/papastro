<?php
use App\Modules\Blog\Models\Post;
$posts = Post::published()->latest()->paginate(10, (int)request('page', 1));
$pageTitle = 'Blog';
?>
<?php
ob_start();
?>
<!--
  Papastro Framework — created by kangpcode (Dhafa Nazula Permadi)
  Free to use & extend. This watermark must remain in source code.
-->


<?php ob_start(); ?>
<div class="blog-header">
    <div class="container">
        <h1>Blog</h1>
        <p style="color: var(--color-muted); margin-top: 8px;"><?php echo htmlspecialchars((string)($posts['total']), ENT_QUOTES, 'UTF-8'); ?> articles published</p>
    </div>
</div>

<div class="container">
    <?php if(empty($posts['data'])): ?>
        <div style="text-align: center; padding: 80px 0; color: var(--color-muted);">
            <p style="font-size: 48px">📝</p>
            <p style="margin-top: 16px; font-size: 18px;">No posts yet. <a href="/blog/create" style="color: var(--color-primary);">Write the first one →</a></p>
        </div>
    <?php else: ?>
        <div class="posts-grid">
            <?php foreach($posts['data'] as $post): ?>
                <a href="/blog/<?php echo htmlspecialchars((string)($post->slug), ENT_QUOTES, 'UTF-8'); ?>" class="post-card">
                    <div style="font-size: 12px; color: var(--color-muted); margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.05em;">
                        <?php echo htmlspecialchars((string)($post->getReadingTime()), ENT_QUOTES, 'UTF-8'); ?> min read &middot; <?php echo htmlspecialchars((string)(date('M j, Y', strtotime($post->created_at))), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <h2><?php echo htmlspecialchars((string)($post->title), ENT_QUOTES, 'UTF-8'); ?></h2>
                    <?php if($post->excerpt): ?>
                    <p style="margin-top: 10px;"><?php echo htmlspecialchars((string)($post->excerpt), ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                    <div style="margin-top: 16px; color: var(--color-primary); font-size: 14px; font-weight: 600;">
                        Read more →
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if($posts['last_page'] > 1): ?>
        <div style="display: flex; justify-content: center; gap: 8px; margin: 48px 0;">
            <?php if($posts['current_page'] > 1): ?>
            <a href="?page=<?php echo htmlspecialchars((string)($posts['current_page'] - 1), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline">← Previous</a>
            <?php endif; ?>
            <span style="padding: 10px 20px; color: var(--color-muted); font-size: 14px;">
                Page <?php echo htmlspecialchars((string)($posts['current_page']), ENT_QUOTES, 'UTF-8'); ?> of <?php echo htmlspecialchars((string)($posts['last_page']), ENT_QUOTES, 'UTF-8'); ?>
            </span>
            <?php if($posts['current_page'] < $posts['last_page']): ?>
            <a href="?page=<?php echo htmlspecialchars((string)($posts['current_page'] + 1), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline">Next →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php $__sections['content'] = ob_get_clean(); ?>

<?php
$__implicitBody = trim(ob_get_clean());
if (!isset($__sections['content']) && $__implicitBody !== '') {
    $__sections['content'] = $__implicitBody;
}
$__layoutData = array_merge($__data ?? [], get_defined_vars(), ['__sections' => $__sections ?? []]);
unset($__layoutData['__data'], $__layoutData['__implicitBody'], $__layoutData['path']);
echo app(\Papastro\View\ViewEngine::class)->render('layouts.base', $__layoutData);
?>