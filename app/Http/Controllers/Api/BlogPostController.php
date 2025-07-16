<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlogPostController extends Controller
{
    public function index(Request $request)
    {
        $query = BlogPost::with(['user', 'category', 'tags', 'images']);

        // Determinar si el usuario está autenticado
        $isAuthenticated = Auth::check();

        if ($isAuthenticated) {
            // Usuario autenticado - puede ver todos los posts o filtrar por estado
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }
            // Si no especifica status o status=all, mostramos todos los posts
        } else {
            // Para usuarios no autenticados, solo posts publicados
            $query->published();
        }

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        if ($request->has('tag')) {
            $query->byTag($request->tag);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('excerpt', 'like', '%' . $request->search . '%');
            });
        }

        $posts = $query->orderBy('created_at', 'desc')
                      ->paginate($request->get('per_page', 15));

        return response()->json($posts);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'required|array',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'in:draft,published,archived',
            'published_at' => 'nullable|date',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        $post = BlogPost::create([
            'title' => $request->title,
            'excerpt' => $request->excerpt,
            'content' => $request->content,
            'category_id' => $request->category_id,
            'user_id' => Auth::id(),
            'status' => $request->status ?? 'draft',
            'published_at' => $request->published_at,
        ]);

        if ($request->has('tags')) {
            $post->tags()->sync($request->tags);
        }

        $post->load(['user', 'category', 'tags', 'images']);

        return response()->json($post, 201);
    }

    public function show(string $slug)
    {
        $post = BlogPost::with(['user', 'category', 'tags', 'images'])
                       ->where('slug', $slug)
                       ->firstOrFail();

        if ($post->status !== 'published' && !Auth::check()) {
            abort(404);
        }

        return response()->json($post);
    }

    public function showById(int $id)
    {
        // Los usuarios autenticados pueden ver posts en cualquier estado
        if (!Auth::check()) {
            abort(403, 'Unauthorized');
        }

        $post = BlogPost::with(['user', 'category', 'tags', 'images'])
                       ->where('id', $id)
                       ->firstOrFail();

        return response()->json($post);
    }

    public function update(Request $request, BlogPost $blogPost)
    {
        // Solo usuarios autenticados pueden actualizar posts
        if (!Auth::check()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'sometimes|required|array',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'in:draft,published,archived',
            'published_at' => 'nullable|date',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        $blogPost->update($request->only([
            'title', 'excerpt', 'content', 'category_id', 'status', 'published_at'
        ]));

        if ($request->has('tags')) {
            $blogPost->tags()->sync($request->tags);
        }

        $blogPost->load(['user', 'category', 'tags', 'images']);

        return response()->json($blogPost);
    }

    public function destroy(BlogPost $blogPost)
    {
        // Solo usuarios autenticados pueden eliminar posts
        if (!Auth::check()) {
            abort(403, 'Unauthorized');
        }

        $blogPost->delete();

        return response()->json(['message' => 'Blog post deleted successfully']);
    }
}
