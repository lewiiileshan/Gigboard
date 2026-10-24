<?php
namespace App\Controllers;

use App\Core\{Auth, Config, Session, Url, View};
use App\Models\User;

/** Register, log in, log out (use cases UC01 and UC02; FR01 to FR03). */
class AuthController
{
    public function showRegister(): void
    {
        if (Auth::check()) Url::redirect('/dashboard');
        View::render('auth/register', ['title' => 'Create account'], 'layouts/auth');
    }

    public function register(): void
    {
        Session::checkCsrf();

        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm'] ?? '';
        $role     = $_POST['role'] ?? '';
        $terms    = !empty($_POST['terms']);
        $min      = Config::get('password_min_len');

        // Validation happens on the server, always (NFR04). Browser checks can be bypassed.
        $error = null;
        if (!in_array($role, ['client', 'freelancer'], true))           $error = 'Choose whether you want to find work or hire talent.';
        elseif (mb_strlen($name) < 2 || mb_strlen($name) > 100)         $error = 'Enter your full name.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))             $error = 'Enter a valid email address.';
        elseif ($phone !== '' && !preg_match('/^\+?[0-9 ]{9,15}$/', $phone)) $error = 'Enter a valid phone number or leave it blank.';
        elseif (strlen($password) < $min)                               $error = "Password must be at least $min characters.";
        elseif ($password !== $confirm)                                 $error = 'The two passwords do not match.';
        elseif (!$terms)                                                $error = 'You must accept the terms of service.';   // FR02
        elseif (User::findByEmail($email))                              $error = 'An account with that email already exists.';

        if ($error) {
            Session::keepOld(['name' => $name, 'email' => $email, 'phone' => $phone, 'role' => $role]);
            Session::flash('error', $error);
            Url::redirect('/register');
        }

        $id = User::create($name, $email, $phone, $password, $role);
        Session::clearOld();
        Auth::login(['id' => $id]);
        Session::flash('success', 'Welcome to GigBoard KE, ' . $name . '!');
        Url::redirect('/dashboard');
    }

    public function showLogin(): void
    {
        if (Auth::check()) Url::redirect('/dashboard');
        View::render('auth/login', ['title' => 'Log in'], 'layouts/auth');
    }

    public function login(): void
    {
        Session::checkCsrf();

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $user     = $email !== '' ? User::findByEmail($email) : null;

        // Same message for "no such email" and "wrong password" so attackers cannot probe which emails exist.
        if (!$user || !password_verify($password, $user['password_hash'])) {
            Session::keepOld(['email' => $email]);
            Session::flash('error', 'Incorrect email or password.');
            Url::redirect('/login');
        }

        Session::clearOld();
        Auth::login($user);
        Url::redirect('/dashboard');
    }

    public function logout(): void
    {
        Session::checkCsrf();
        Auth::logout();
        Session::flash('success', 'You have been logged out.');
        Url::redirect('/login');
    }
}
