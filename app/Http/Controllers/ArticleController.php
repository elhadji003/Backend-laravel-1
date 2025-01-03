<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ArticleController extends Controller
{
    public function allArticles()
    {
        $articles = Article::with('user:id,name') // Inclut uniquement les champs `id` et `name` de l'utilisateur
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($articles);
    }

    public function getUserArticles()
    {
        // Récupère l'utilisateur connecté
        $user = auth()->user();

        // Récupère les articles de cet utilisateur
        $articles = Article::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($articles, 200);
    }

    // Méthode pour récupérer un article par son ID
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
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400); // Retourne les erreurs de validation
        }

        // Créer l'article avec l'ID de l'utilisateur connecté
        $article = Article::create([
            'title' => $request->title,
            'content' => $request->content,
            'user_id' => Auth::id(), // Associe l'ID de l'utilisateur connecté
        ]);

        return response()->json($article, 201); // Retourne l'article créé avec un code HTTP 201
    }

    // Méthode pour mettre à jour un article existant
    public function updateArticle(Request $request, $id)
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json(['message' => 'Article non trouvé'], 404); // Si l'article n'existe pas
        }

        // Vérifie que l'article appartient à l'utilisateur connecté
        if ($article->user_id !== Auth::id()) {
            return response()->json(['message' => 'Vous ne pouvez pas modifier cet article'], 403); // Si l'utilisateur essaie de modifier un article qui ne lui appartient pas
        }

        // Validation des données reçues
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400); // Retourne les erreurs de validation
        }

        // Mise à jour de l'article
        $article->update([
            'title' => $request->title,
            'content' => $request->content,
        ]);

        return response()->json($article); // Retourne l'article mis à jour
    }

    // Méthode pour supprimer un article
    public function deleteArticle($id)
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json(['message' => 'Article non trouvé'], 404); // Si l'article n'existe pas
        }

        // Vérifie que l'article appartient à l'utilisateur connecté
        if ($article->user_id !== Auth::id()) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer cet article'], 403); // Si l'utilisateur essaie de supprimer un article qui ne lui appartient pas
        }

        $article->delete(); // Supprimer l'article

        return response()->json(['message' => 'Article supprimé avec succès']); // Confirme la suppression
    }
}
