<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    /**
     * Page de connexion.
     */
    public function login()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('app');
    }

    /**
     * Redirection vers Microsoft pour authentification.
     */
    public function redirectToMicrosoft()
    {
        return Socialite::driver('microsoft')->redirect();
    }

    /**
     * Callback Microsoft - création ou mise à jour de l'utilisateur.
     */
    public function handleMicrosoftCallback()
    {
        try {
            $microsoftUser = Socialite::driver('microsoft')->user();
        } catch (\Exception $e) {
            ActivityLogger::loginFailed(['ip' => request()->ip(), 'error' => mb_substr($e->getMessage(), 0, 200)]);

            return redirect('/login?error=' . urlencode('Erreur lors de l\'authentification Microsoft : ' . $e->getMessage()));
        }

        // Récupérer ou créer l'utilisateur
        $user = User::updateOrCreate(
            ['microsoft_id' => $microsoftUser->getId()],
            [
                'name'       => $microsoftUser->getName(),
                'email'      => $microsoftUser->getEmail(),
                'firstname'  => $microsoftUser->user['givenName'] ?? $microsoftUser->getName(),
                'lastname'   => $microsoftUser->user['surname'] ?? '',
            ]
        );

        // Promotion automatique des adresses listées dans ADMIN_EMAILS
        if ($user->email && in_array(strtolower($user->email), config('app.admin_emails', []), true) && ! $user->isAdmin()) {
            $user->forceFill(['role' => User::ROLE_ADMIN])->save();
        }

        Auth::login($user, remember: true);
        ActivityLogger::userLogin($user->id, [
            'email'      => $user->email,
            'ip'         => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 160),
        ]);

        return redirect()->intended(route('home'));
    }

    /**
     * Déconnexion.
     */
    public function logout(Request $request)
    {
        if ($request->user()) {
            ActivityLogger::userLogout($request->user()->id, ['ip' => $request->ip()]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('login');
    }
}
