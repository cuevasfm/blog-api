<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageController extends Controller
{
    public function index(Request $request)
    {
        $query = Image::query();

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('filename', 'like', '%' . $request->search . '%')
                  ->orWhere('original_name', 'like', '%' . $request->search . '%')
                  ->orWhere('alt_text', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('type')) {
            $query->where('mime_type', 'like', 'image/' . $request->type . '%');
        }

        $images = $query->orderBy('created_at', 'desc')
                       ->paginate($request->get('per_page', 20));

        // Agregar información de uso a cada imagen
        $images->getCollection()->transform(function ($image) {
            $image->usage_count = $image->usage_count;
            $image->is_used = $image->isUsed();
            return $image;
        });

        return response()->json($images);
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'alt_text' => 'nullable|string|max:255',
        ]);

        $file = $request->file('image');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('images', $filename, 'public');

        $image = Image::create([
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'alt_text' => $request->alt_text,
        ]);

        return response()->json([
            'id' => $image->id,
            'url' => $image->url,
            'filename' => $image->filename,
            'original_name' => $image->original_name,
            'alt_text' => $image->alt_text,
            'size' => $image->size,
        ], 201);
    }

    public function destroy(Image $image)
    {
        Storage::disk('public')->delete($image->path);
        $image->delete();

        return response()->json(['message' => 'Image deleted successfully']);
    }

    public function uploadForEditor(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB
        ]);

        $file = $request->file('image');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('images/editor', $filename, 'public');

        $image = Image::create([
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'alt_text' => null,
        ]);

        return response()->json([
            'success' => 1,
            'file' => [
                'url' => $image->url,
                'name' => $image->original_name,
            ],
        ]);
    }

    public function cleanupUnused(Request $request)
    {
        try {
            $deletedCount = 0;
            $deletedSize = 0;
            $errors = [];

            // Obtener todas las imágenes no utilizadas
            $unusedImages = Image::all()->filter(function ($image) {
                return !$image->isUsed();
            });

            foreach ($unusedImages as $image) {
                try {
                    $deletedSize += $image->size;
                    
                    // Eliminar archivo físico
                    if (Storage::disk('public')->exists($image->path)) {
                        Storage::disk('public')->delete($image->path);
                    }
                    
                    // Eliminar registro de la base de datos
                    $image->delete();
                    $deletedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Error eliminando {$image->filename}: " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Limpieza completada: {$deletedCount} imágenes eliminadas",
                'deleted_count' => $deletedCount,
                'deleted_size' => $deletedSize,
                'deleted_size_formatted' => $this->formatBytes($deletedSize),
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error durante la limpieza: ' . $e->getMessage()
            ], 500);
        }
    }


    private function formatBytes($bytes)
    {
        if ($bytes === 0) return '0 Bytes';
        
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
}
