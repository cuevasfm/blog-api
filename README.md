# Blog API

Una API RESTful para un blog construida con Laravel 11 y Sanctum para autenticación. Soporta contenido estructurado usando Editor.js, gestión de imágenes, categorías y etiquetas.

## Características

- 🔐 Autenticación con Laravel Sanctum
- 📝 Contenido enriquecido con Editor.js
- 🖼️ Subida y gestión de imágenes
- 🏷️ Sistema de categorías y etiquetas
- 📄 Paginación y filtrado de posts
- 🔍 Búsqueda de contenido
- 🌐 URLs amigables (slugs)
- 🔒 Autorización basada en políticas

## Requisitos

- PHP 8.2+
- MySQL/PostgreSQL
- Composer

## Instalación

1. Clonar el repositorio
2. Instalar dependencias:
   ```bash
   composer install
   ```

3. Configurar el archivo `.env`:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Configurar la base de datos en `.env`:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=blog_api
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. Ejecutar migraciones:
   ```bash
   php artisan migrate
   ```

6. Crear enlace simbólico para storage:
   ```bash
   php artisan storage:link
   ```

## Endpoints de la API

### Autenticación

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/register` | Registrar nuevo usuario |
| POST | `/api/login` | Iniciar sesión |
| POST | `/api/logout` | Cerrar sesión (requiere auth) |
| GET | `/api/user` | Obtener usuario actual (requiere auth) |

### Posts del Blog

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/posts` | Listar posts publicados |
| GET | `/api/posts/{slug}` | Obtener post por slug |
| POST | `/api/posts` | Crear nuevo post (requiere auth) |
| PUT | `/api/posts/{id}` | Actualizar post (requiere auth) |
| DELETE | `/api/posts/{id}` | Eliminar post (requiere auth) |

### Categorías

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/categories` | Listar categorías |
| GET | `/api/categories/{slug}` | Obtener categoría por slug |
| POST | `/api/categories` | Crear categoría (requiere auth) |
| PUT | `/api/categories/{id}` | Actualizar categoría (requiere auth) |
| DELETE | `/api/categories/{id}` | Eliminar categoría (requiere auth) |

### Etiquetas

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/tags` | Listar etiquetas |
| GET | `/api/tags/{slug}` | Obtener etiqueta por slug |
| POST | `/api/tags` | Crear etiqueta (requiere auth) |
| PUT | `/api/tags/{id}` | Actualizar etiqueta (requiere auth) |
| DELETE | `/api/tags/{id}` | Eliminar etiqueta (requiere auth) |

### Imágenes

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/images` | Subir imagen (requiere auth) |
| POST | `/api/images/editor-upload` | Subir imagen para Editor.js (requiere auth) |
| DELETE | `/api/images/{id}` | Eliminar imagen (requiere auth) |

## Parámetros de Consulta

### Listar Posts
- `?status=draft|published|archived` - Filtrar por estado (solo para usuarios autenticados)
- `?category=category-slug` - Filtrar por categoría
- `?tag=tag-slug` - Filtrar por etiqueta
- `?search=término` - Buscar en título y extracto
- `?per_page=15` - Número de posts por página

## Estructura del Contenido (Editor.js)

El campo `content` de los posts acepta la estructura JSON de Editor.js:

```json
{
  "time": 1672531200000,
  "blocks": [
    {
      "id": "abc123",
      "type": "paragraph",
      "data": {
        "text": "Texto del párrafo"
      }
    },
    {
      "id": "def456",
      "type": "header",
      "data": {
        "text": "Título",
        "level": 2
      }
    },
    {
      "id": "ghi789",
      "type": "image",
      "data": {
        "file": {
          "url": "https://example.com/storage/images/image.jpg"
        },
        "caption": "Descripción de la imagen",
        "withBorder": false,
        "stretched": false,
        "withBackground": false
      }
    }
  ],
  "version": "2.28.2"
}
```

## Ejemplos de Uso

### Registrar Usuario
```javascript
const response = await fetch('/api/register', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    name: 'Juan Pérez',
    email: 'juan@example.com',
    password: 'password123',
    password_confirmation: 'password123'
  })
});
```

### Crear Post
```javascript
const response = await fetch('/api/posts', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    title: 'Mi Primer Post',
    excerpt: 'Un breve resumen del post',
    content: {
      time: Date.now(),
      blocks: [
        {
          type: 'paragraph',
          data: {
            text: 'Contenido del post...'
          }
        }
      ]
    },
    category_id: 1,
    status: 'published',
    published_at: '2024-01-01 12:00:00',
    tags: [1, 2, 3]
  })
});
```

### Subir Imagen
```javascript
const formData = new FormData();
formData.append('image', file);
formData.append('alt_text', 'Descripción de la imagen');

const response = await fetch('/api/images', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`
  },
  body: formData
});
```

## Desarrollo

Para ejecutar en modo desarrollo:

```bash
php artisan serve
```

La API estará disponible en `http://localhost:8000/api`

## Testing

Para ejecutar las pruebas:

```bash
php artisan test
```

## Licencia

Este proyecto está bajo la licencia MIT.
