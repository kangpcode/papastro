<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v4.0.0
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace App\Modules\User\Http\Controllers;

use Papastro\Http\Request;
use Papastro\Http\Response;
use Papastro\Security\RateLimiter;
use App\Modules\User\Models\User;

class AuthController
{
    public function showLogin(): Response
    {
        return view('User::login', ['error' => flash('error')]);
    }

    public function login(Request $request, RateLimiter $limiter): Response
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // Brute-force protection: 5 attempts / minute per IP+email.
        $key = 'login:' . $request->ip() . '|' . strtolower($data['email']);
        if ($limiter->tooManyAttempts($key, 5)) {
            return view('User::login', ['error' => 'Too many attempts. Try again in ' . $limiter->availableIn($key) . 's.'])
                ->setStatusCode(429);
        }

        $user = User::findByEmail($data['email']);

        if (!$user || !$user->verifyPassword((string) $data['password'])) {
            $limiter->hit($key, 60);
            return view('User::login', ['error' => 'Invalid credentials'])->setStatusCode(422);
        }

        $limiter->clear($key);

        $session = session();
        $session->regenerate();               // prevent session fixation
        $session->put('user_id', $user->getKey());
        $session->put('user_name', $user->name);

        return redirect('/');
    }

    public function logout(): Response
    {
        session()->invalidate();
        return redirect('/');
    }

    public function showRegister(): Response
    {
        return view('User::register');
    }

    public function register(Request $request): Response
    {
        $data = $request->validate([
            'name'     => 'required|min:2|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:8',
        ]);

        $user = User::create($data);   // password hashed by User::setPasswordAttribute

        $session = session();
        $session->regenerate();
        $session->put('user_id', $user->getKey());
        $session->put('user_name', $user->name);

        return redirect('/');
    }
}
