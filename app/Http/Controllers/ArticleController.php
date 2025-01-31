<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ArticleController extends Controller
{
    public function allArticles()
    {
        $articles = Article::with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $articles->transform(function ($article) {
            $article->image = $article->image ? url('storage/' . $article->image) : null;
            return $article;
        });

        return response()->json($articles);
    }


    public function getUserArticles()
    {
        // Récupère l'utilisateur connecté
        $user = auth()->user();

        // Récupère les articles de cet utilisateur
        $articles = Article::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $articles->transform(function ($article) {
            $article->image = $article->image ? url('storage/' . $article->image) : null;
            return $article;
        });

        return response()->json($articles, 200);
    }

    public function getArticle($id)
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json(['message' => 'Article non trouvé'], 404);
        }

        return response()->json($article); // Retourne l'article sous forme de JSON
    }

    // Méthode pour créer un nouvel article (associé à l'utilisateur connecté)
    public function createArticle(Request $request)
    {
        // Validation des données reçues
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'image.image' => 'Le fichier doit être une image.',
            'image.mimes' => 'Seuls les formats jpeg, png, jpg et gif sont autorisés.',
            'image.max' => 'La taille de l\'image ne doit pas dépasser 2 Mo.',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400); // Retourne les erreurs de validation
        }

        // Sauvegarder l'image si elle existe
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('articles', 'public');
        }

        // Créer l'article avec l'ID de l'utilisateur connecté
        $article = Article::create([
            'title' => $request->title,
            'content' => $request->content,
            'image' => $imagePath, // Sauvegarde le chemin de l'image
            'user_id' => Auth::id(), // Associe l'ID de l'utilisateur connecté
        ]);

        return response()->json($article, 201); // Retourne l'article créé avec un code HTTP 201
    }

    // Méthode pour mettre à jour un article existant
    public function updateArticle(Request $request, $id)
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json(['message' => 'Article non trouvé'], 404);
        }

        if ($article->user_id !== Auth::id()) {
            return response()->json(['message' => 'Vous ne pouvez pas modifier cet article'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Mise à jour de l'image si nécessaire
        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image si elle existe
            if ($article->image) {
                Storage::disk('public')->delete($article->image);
            }

            // Sauvegarder la nouvelle image
            $article->image = $request->file('image')->store('articles', 'public');
        }

        // Mise à jour des autres champs
        $article->update([
            'title' => $request->title,
            'content' => $request->content,
        ]);

        return response()->json($article);
    }


    // Méthode pour supprimer un article
    public function deleteArticle($id)
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json(['message' => 'Article non trouvé'], 404);
        }

        if ($article->user_id !== Auth::id()) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer cet article'], 403);
        }

        // Supprimer l'image si elle existe
        if ($article->image) {
            Storage::disk('public')->delete($article->image);
        }

        $article->delete();

        return response()->json(['message' => 'Article supprimé avec succès']);
    }
}
