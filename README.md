# JJRC Google Reviews

Plugin de WordPress para mostrar reseñas de Google Maps mediante shortcodes configurables, con vista de carousel (Owl Carousel) o grid con paginación.

## Requisitos

- WordPress 5.3+
- PHP 7.4+
- API Key de Google Maps con la **Places API** habilitada

## Instalación

1. Clona o descarga el repositorio en `wp-content/plugins/jjrc-google-reviews/`
2. Activa el plugin desde **Plugins → Plugins instalados**
3. Ve a **Google Reviews → Configuración** e ingresa tu API Key

## Configuración

### Obtener una API Key

1. Accede a [Google Cloud Console](https://console.cloud.google.com/apis/credentials)
2. Crea un proyecto (o usa uno existente)
3. Habilita la **Places API**
4. Crea una credencial de tipo **API Key**
5. En **Restricciones de aplicación** deja la key sin restricción, o usa restricción por **IP** (no por HTTP referrer, ya que las llamadas se hacen desde el servidor PHP)

### Agregar un comercio

1. Ve a **Google Reviews → Comercios**
2. Haz clic en **+ Agregar Comercio**
3. Ingresa el **Place ID** directamente (si ya lo conoces) o usa el ícono 🔍 para buscarlo por nombre
4. Completa el nombre, shortcode key, tipo de vista y colores
5. Guarda — el shortcode se genera automáticamente

## Uso

Pega el shortcode en cualquier página, entrada o widget:

```
[jjrc_reviews key="nombre_del_comercio"]
```

Puedes tener múltiples shortcodes en la misma página, cada uno con sus propios colores y tipo de vista.

## Tipos de vista

| Vista | Descripción |
|-------|-------------|
| **Carousel** | Slider horizontal con Owl Carousel, responsive (1/2/3 columnas) |
| **Grid** | Cuadrícula con paginación JS, 3 reseñas por página |

## Opciones por comercio

| Campo | Descripción | Default |
|-------|-------------|---------|
| Place ID | Identificador único de Google Maps | — |
| Nombre | Nombre mostrado en el widget | — |
| Shortcode key | Identificador para el shortcode | — |
| Tipo de vista | `carousel` o `grid` | carousel |
| Color primario | Color de estrellas y controles | `#f5a623` |
| Color de fondo | Fondo de las tarjetas | `#ffffff` |
| Color de texto | Texto de las tarjetas | `#333333` |
| Cache | Frecuencia de actualización desde Google | 12 horas |

## Caché

Las reseñas se almacenan en base de datos para minimizar llamadas a la API de Google. Puedes refrescar la caché manualmente con el botón **🔄 Cache** en la tabla de comercios. La caché también se limpia automáticamente al editar un comercio.

## Limitación de Google Places API

La API de Google Places devuelve un máximo de **5 reseñas** por lugar. Esto es una restricción de Google y no puede superarse con la Places API estándar.

## Estructura del plugin

```
jjrc-google-reviews/
├── jjrc-google-reviews.php     # Entry point, constantes, hooks
├── includes/
│   ├── class-database.php      # CRUD tablas gr_comercios y gr_reviews_cache
│   ├── class-api.php           # Google Places API + lógica de caché
│   ├── class-admin.php         # Panel admin + handlers AJAX
│   └── class-shortcode.php     # Shortcode + enqueue de assets
├── templates/
│   ├── admin-comercios.php     # Vista admin — listado y formulario
│   ├── admin-settings.php      # Vista admin — configuración API Key
│   ├── carousel.php            # Frontend — vista carousel
│   └── grid.php                # Frontend — vista grid
└── assets/
    ├── css/admin.css
    ├── css/frontend.css
    ├── js/admin.js
    └── js/frontend.js
```

## Changelog

### 1.1.4
- Bump de versión

### 1.1.3
- **Fix:** Agregado header `Referer` en todas las peticiones a la API de Google para compatibilidad con keys que requieren identificación del origen del servidor

### 1.1.2
- **Fix:** Buscador sin resultados — reemplazado endpoint `autocomplete` por `textsearch`, mismo que usa la API de Google en el script de referencia (mayor compatibilidad con configuraciones de API key)

### 1.1.1
- docs: README inicial

### 1.1.0
- **Fix:** El autocompletado devolvía cero resultados por doble encoding en la URL de la API
- **Nuevo:** Campo Place ID directo en el formulario + modal de búsqueda con lupa
- **Nuevo:** Nombre del comercio como campo visible y editable
- **Fix:** `esc_html()` en el avatar inicial de reseñas (XSS menor)
- **Fix:** `current_time('timestamp')` reemplazado por `time()` (deprecado en WP 5.3)
- **Mejora:** Paginación del grid desacoplada de PHP/JS via `data-per-page`
- **Mejora:** Botón de copiar shortcode muestra confirmación inline en vez de `alert()`

### 1.0.0
- Versión inicial

## Licencia

GPL-2.0+
