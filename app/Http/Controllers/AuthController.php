<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    // Inscription d'un nouvel utilisateur
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json(compact('user', 'token'), 201);
    }

    // Connexion d'un utilisateur
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if ($token = JWTAuth::attempt($credentials)) {
            return response()->json(compact('token'));
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // Récupérer les informations de l'utilisateur connecté
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    // Demande de réinitialisation de mot de passe
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        Password::sendResetLink($request->only('email'));

        return response()->json(['message' => 'Password reset link sent!']);
    }

    // Réinitialisation du mot de passe
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|confirmed',
            'token' => 'required',
        ]);

        $response = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        if ($response == Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password has been reset successfully.'], 200);
        }

        return response()->json(['error' => 'This password reset token is invalid.'], 400);
    }

    // Déconnexion de l'utilisateur
    public function logout(Request $request)
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['message' => 'Logged out successfully']);
    }

    // Mettre à jour le profil de l'utilisateur
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $user->update($request->only(['name', 'email']));

        return response()->json($user);
    }

    // Supprimer le compte de l'utilisateur
    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        $user->delete();

        return response()->json(['message' => 'Account deleted successfully']);
    }

    // Vérifier la validité du token JWT
    public function verifyToken(Request $request)
    {
        if (auth()->check()) {
            return response()->json(['message' => 'Token valide'], 200);
        }

        return response()->json(['message' => 'Token invalide ou expiré'], 401);
    }

    // Mettre à jour l'image de profil de l'utilisateur
    public function updateProfileImg(Request $request)
    {
        $user = $request->user();

        // Mettre à jour les informations de base
        $user->update($request->only(['name', 'email']));

        // Mettre à jour l'image de profil si elle est fournie
        if ($request->hasFile('profile_image')) {
            $request->validate([
                'profile_image' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            ], [
                'profile_image.image' => 'Le fichier doit être une image.',
                'profile_image.mimes' => 'Seuls les formats jpeg, png, jpg et gif sont autorisés.',
                'profile_image.max' => 'La taille de l\'image ne doit pas dépasser 2 Mo.',
            ]);

            // Supprimer l'ancienne image si elle existe
            if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
            }

            // Stocker la nouvelle image dans le disque public
            $path = $request->file('profile_image')->store('profile_images', 'public');
            $user->profile_image = $path;
            $user->save();
        }

        // Renvoyer l'URL complète de l'image
        return response()->json([
            'message' => 'Profile updated successfully',
            'profile_image' => $user->profile_image ? asset("storage/$user->profile_image") : null,
        ]);
    }

    // Récupérer l'image de profil de l'utilisateur
    public function getProfileImage(Request $request)
    {
        $user = $request->user();

        if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
            return response()->json([
                'profile_image' => asset("storage/$user->profile_image"),
            ]);
        }

        return response()->json(['message' => 'No profile image found'], 404);
    }
    // Supprimer l'image de profil de l'utilisateur
    public function deleteProfileImage(Request $request)
    {
        $user = $request->user();

        if ($user->profile_image && Storage::exists($user->profile_image)) {
            Storage::delete($user->profile_image);
            $user->profile_image = null;
            $user->save();

            return response()->json(['message' => 'Profile image deleted successfully']);
        }

        return response()->json(['message' => 'No profile image found'], 404);
    }
}
