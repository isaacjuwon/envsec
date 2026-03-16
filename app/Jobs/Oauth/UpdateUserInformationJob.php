<?php

declare(strict_types=1);

namespace App\Jobs\User;

use App\Models\User;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Socialite\Two\User as SocialiteUser;

final class UpdateUserProfileInformationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private User $user,
        private readonly SocialiteUser $socialiteUser, private readonly string $provider)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = $this->user;
        $socialiteUser = $this->socialiteUser;
        $user->oauthConnections()->updateOrCreate([
            'provider' => $this->provider,
        ], [
            'provider_id' => $socialiteUser->getId(),
            'data' => $socialiteUser->getRaw(),
            'token' => $socialiteUser->token,
            'refresh_token' => $socialiteUser->refreshToken,
            'expires_at' => $socialiteUser->expiresIn ? now()->addSeconds($socialiteUser->expiresIn) : null,
        ]);

        $user->profile_photo_path = $socialiteUser->getAvatar();
        $user->email_verified_at ??= now();
        $user->save();
    }
}
