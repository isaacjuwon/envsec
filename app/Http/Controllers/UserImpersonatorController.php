<?php

namespace App\Http\Controllers;

use App\Models\User;
use Facades\App\Support\Impersonator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class UserImpersonationController
{
    /**
     * Start impersonating a user
     */
    public function store(User $user): RedirectResponse
    {
        Gate::authorize('impersonate', $user);

        Impersonator::take($user);

        return to_route('dashboard');
    }

    /**
     * Stop impersonating and return to original user
     */
    public function destroy(): RedirectResponse
    {
        Impersonator::stop();

        return back();
    }
}
