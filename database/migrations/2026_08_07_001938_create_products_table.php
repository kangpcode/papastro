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
        $this->create('products', function ($table) {
            $table->id();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->dropIfExists('products');
    }
};