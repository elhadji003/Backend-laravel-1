<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    public function resetPassword(Request $request)
    {
        // Valider les données reçues
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|confirmed',
        ]);

        // Réinitialiser le mot de passe
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                // Mettre à jour le mot de passe
                $user->forceFill([
                    'password' => bcrypt($password),
                ])->save();

                // Supprimer tous les tokens existants (sécurité)
                $user->tokens()->delete();
            }
        );

        // Retourner la réponse appropriée
        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => __($status)])
            : response()->json(['error' => __($status)], 400);
    }
}
