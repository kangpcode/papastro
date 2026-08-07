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

namespace App\Modules\User\Models;

use Papastro\ORM\Model;

class User extends Model
{
    protected string $table    = 'users';
    protected array  $fillable = ['name', 'email', 'password'];
    protected array  $hidden   = ['password', 'remember_token'];
    protected bool   $timestamps = true;

    /**
     * Hash the password before saving.
     */
    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = password_hash($value, PASSWORD_ARGON2ID);
    }

    /**
     * Verify a plain password against the stored hash.
     */
    public function verifyPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->attributes['password'] ?? '');
    }

    /**
     * Find a user by email.
     */
    public static function findByEmail(string $email): ?static
    {
        $row = static::query()->where('email', $email)->first();
        return $row ? static::newFromBuilder($row) : null;
    }
}
