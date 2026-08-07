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

namespace App\Modules\Product\Models;

use Papastro\ORM\Model;

class Product extends Model
{
    protected string $table    = 'product';
    protected array  $fillable = [];
    protected bool   $timestamps = true;
}