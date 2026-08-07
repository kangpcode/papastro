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

namespace App\Modules\User\Http\Controllers;

use Papastro\Http\Request;
use Papastro\Http\Response;
use App\Modules\User\Models\User;

class AuthController
{
    public function showLogin(): Response
    {
        return view('User::login');
    }

    public function login(Request $request): Response
    {
        $email    = $request->input('email');
        $password = $request->input('password');

        $user = User::findByEmail($email);

        if (!$user || !$user->verifyPassword($password)) {
            return view('User::login', ['error' => 'Invalid credentials']);
        }

        session_start();
        $_SESSION['user_id']   = $user->getKey();
        $_SESSION['user_name'] = $user->name;

        return redirect('/dashboard');
    }

    public function logout(): Response
    {
        session_start();
        session_destroy();
        return redirect('/');
    }

    public function showRegister(): Response
    {
        return view('User::register');
    }

    public function register(Request $request): Response
    {
        $data = $request->validate([
            'name'     => 'required|min:2',
            'email'    => 'required|email',
            'password' => 'required|min:8',
        ]);

        $user = User::create($data);

        session_start();
        $_SESSION['user_id'] = $user->getKey();

        return redirect('/dashboard');
    }
}
