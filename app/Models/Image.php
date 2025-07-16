<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    protected $fillable = [
        'filename',
        'original_name',
        'path',
        'mime_type',
        'size',
        'alt_text',
        'imageable_type',
        'imageable_id',
    ];

    protected $appends = [
        'url',
        'size_formatted'
    ];

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(): string
    {
        // Usar la URL configurada en APP_URL
        $baseUrl = config('app.url') . '/storage/' . $this->path;
        
        // En desarrollo, agregar timestamp para evitar cache
        if (config('app.env') === 'local') {
            $baseUrl .= '?t=' . filemtime(storage_path('app/public/' . $this->path));
        }
        
        return $baseUrl;
    }

    public function getSizeFormattedAttribute(): string
    {
        $bytes = $this->size;
        if ($bytes === 0) return '0 Bytes';
        
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }

    public function isUsed(): bool
    {
        // Verificar si la imagen está asociada directamente con un post
        if ($this->imageable_id !== null) {
            return true;
        }

        // Buscar en el contenido de los posts
        $usageCount = $this->findUsageInPosts();
        
        return $usageCount > 0;
    }

    public function getUsageCountAttribute(): int
    {
        $blogPostsCount = $this->findUsageInPosts();
        return $blogPostsCount + ($this->imageable_id ? 1 : 0);
    }

    private function findUsageInPosts(): int
    {
        $posts = \App\Models\BlogPost::all();
        $count = 0;
        
        // Posibles formas en que puede aparecer la URL
        $searchPatterns = [
            $this->url,                    // URL completa
            $this->path,                   // Path relativo
            $this->filename,               // Solo el nombre del archivo
            basename($this->path),         // Nombre del archivo desde el path
            storage_path('app/public/' . $this->path), // Path absoluto
        ];
        
        foreach ($posts as $post) {
            $contentString = '';
            
            // Si content es un array (Editor.js format)
            if (is_array($post->content)) {
                $contentString = json_encode($post->content);
            } else {
                // Si content es string (HTML tradicional)
                $contentString = $post->content;
            }
            
            // Buscar cualquier patrón
            foreach ($searchPatterns as $pattern) {
                if (stripos($contentString, $pattern) !== false) {
                    $count++;
                    break; // Solo contar una vez por post
                }
            }
        }
        
        return $count;
    }
}
