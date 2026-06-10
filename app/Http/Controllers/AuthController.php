<?php

namespace App\Http\Controllers;

use App\Models\User;
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
     * Callback Microsoft — création ou mise à jour de l'utilisateur.
     */
    public function handleMicrosoftCallback()
    {
        try {
            $microsoftUser = Socialite::driver('microsoft')->user();
        } catch (\Exception $e) {
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

        Auth::login($user, remember: true);

        return redirect()->intended(route('home'));
    }

    /**
     * Déconnexion.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('login');
    }
}
