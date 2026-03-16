
<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use Throwable;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Laravel\Socialite\Facades\Socialite;
use App\Actions\User\ActiveOauthProviderAction;
use App\Actions\User\HandleOauthCallbackAction;
use App\Exceptions\OAuthAccountLinkingException;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

final class OauthController extends Controller
{
    public function __construct(
        private readonly HandleOauthCallbackAction $handleOauthCallbackAction,
    ) {}

    public function redirect(string $provider): SymfonyRedirectResponse
    {
        abort_unless($this->isValidProvider($provider), 404);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        abort_unless($this->isValidProvider($provider), 404);

        try {
            /** @var SocialiteUser $socialiteUser */
            $socialiteUser = Socialite::driver($provider)->user();
            $authenticatedUser = Auth::user();
            $user = $this->handleOauthCallbackAction->handle($provider, $socialiteUser, $authenticatedUser);
        } catch (InvalidStateException) {
            return Redirect::intended(Auth::check() ? route('profile.show') : route('login'))->with('error', __('The request timed out. Please try again.'));
        } catch (OAuthAccountLinkingException $oauthAccountLinkingException) {
            return Redirect::intended(Auth::check() ? route('profile.show') : route('login'))->with('error', $oauthAccountLinkingException->getMessage());
        } catch (Throwable $throwable) {
            report($throwable);

            return Redirect::intended(Auth::check() ? route('profile.show') : route('login'))->with('error', __('An error occurred during authentication. Please try again.'));
        }

        if (Auth::guest()) {
            Auth::login($user, true);

            $workspace = $user->ownedWorkspaces()->first() ?? $user->workspaces()->first();

            if ($workspace) {
                return Redirect::to($workspace->slug . '/dashboard');
            }

            return Redirect::intended(config('fortify.home'));
        }

        return Redirect::intended(route('profile.show'))->with('success', "Your {$provider} account has been linked.");
    }

    public function destroy(string $provider): RedirectResponse
    {
        abort_unless($this->isValidProvider($provider), 404);

        $user = Auth::user();

        $user?->oauthConnections()->where('provider', $provider)->delete();
        session()->flash('success', "Your {$provider} account has been unlinked.");

        return Redirect::route('profile.show');
    }

    private function isValidProvider(string $provider): bool
    {
        $activeProviders = (new ActiveOauthProviderAction)->handle();

        return collect($activeProviders)->contains('slug', $provider);
    }
}
