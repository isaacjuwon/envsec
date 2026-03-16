<?php

namespace App\Support;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

class Impersonator
{
    /**
     * Impersonate the given user
     */
    public function take(Authenticatable $user): void
    {
        if ($this->impersonating()) {
            throw new AuthenticationException('Cannot impersonate while already impersonating.');
        }

        if (! Auth::check()) {
            throw new AuthenticationException('Cannot impersonate without a currently authenticated user.');
        }

        // Store the original user ID in session
        session()->put($this->sessionName(), Auth::id());
        session()->regenerate();

        // Log in as the target user
        Auth::login($user);
    }

    /**
     * Stop impersonating and resume as original user
     */
    public function stop(): void
    {
        if ($id = session()->get($this->sessionName())) {
            Auth::loginUsingId($id);
            session()->forget($this->sessionName());
            session()->regenerate();
        }
    }

    /**
     * Check if currently impersonating
     */
    public function impersonating(): bool
    {
        return session()->has($this->sessionName());
    }

    /**
     * Get unique session key for impersonation
     */
    public function sessionName(): string
    {
        return 'impersonator.web._'.sha1(static::class);
    }
}
