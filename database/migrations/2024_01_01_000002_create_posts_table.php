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

use Papastro\ORM\Migration;

return new class extends Migration {
    public function up(): void
    {
        $this->create('posts', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->string('slug', 191)->unique('slug');
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->boolean('published', false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index('user_id');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        $this->dropIfExists('posts');
    }
};
