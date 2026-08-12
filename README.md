# JJRC Google Reviews

Plugin de WordPress para mostrar reseñas de Google Maps mediante shortcodes configurables, con vista de carousel (Owl Carousel) o grid con paginación.

## Requisitos

- WordPress 5.3+
- PHP 7.4+
- API Key de [SerpApi](https://serpapi.com/) (plan con crédito suficiente según volumen de comercios/tráfico)

## Instalación

1. Clona o descarga el repositorio en `wp-content/plugins/jjrc-google-reviews/`
2. Activa el plugin desde **Plugins → Plugins instalados**
3. Ve a **Google Reviews → Configuración** e ingresa tu API Key

## Configuración

### Obtener una API Key

1. Crea una cuenta en [serpapi.com](https://serpapi.com/)
2. Copia tu **Private API Key** desde [serpapi.com/manage-api-key](https://serpapi.com/manage-api-key)
3. Pégala en **Google Reviews → Configuración**

El plugin consume dos motores de SerpApi:

- `google_maps` (`type=search`) — para el buscador de comercios por nombre
- `google_maps_reviews` (`sort_by=ratingHigh`) — para traer las reseñas, ordenadas de mayor a menor nota

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
| Cache | Frecuencia de actualización desde la API (6h / 12h / 24h / 48h / 1 semana / 1 mes) | 12 horas |
| Color navegación | Color de dots y flechas del carousel | igual que Primario |
| Nota mínima | Ocultar reseñas por debajo de este puntaje | 4 estrellas |
| Mostrar dots | Indicadores de posición del carousel | Sí |
| Mostrar flechas | Flechas de navegación del carousel | Sí |
| Posición flechas | A los costados (overlay) o debajo del carousel | Costados |
| Truncar texto a | Líneas de texto visibles antes de truncar la reseña con botón "Leer más" | 4 líneas |

## Caché

Las reseñas se almacenan en base de datos para minimizar llamadas a la API de Google. Puedes refrescar la caché manualmente con el botón **🔄 Cache** en la tabla de comercios. La caché también se limpia automáticamente al editar un comercio.

## Límite de reseñas

Desde v2.0.0 el plugin usa **SerpApi** (`google_maps_reviews`) en lugar de la Places API oficial de Google, que solo entregaba un resumen de 5 reseñas "más relevantes" sin poder ordenarlas por nota. SerpApi permite:

- Traer varias páginas de reseñas por comercio (hasta 3 páginas, ~30 reseñas)
- Ordenarlas por **nota más alta primero** (`sort_by=ratingHigh`), antes de aplicar el filtro de nota mínima

Si el filtro de nota mínima es muy restrictivo, puede que las reseñas devueltas no cumplan el umbral — en ese caso, baja el filtro o refresca la caché.

## Estructura del plugin

```
jjrc-google-reviews/
├── jjrc-google-reviews.php     # Entry point, constantes, hooks
├── includes/
│   ├── class-database.php      # CRUD tablas gr_comercios y gr_reviews_cache
│   ├── class-api.php           # SerpApi (Google Maps Reviews) + lógica de caché
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

### 2.2.1
- **Fix:** El botón "Leer más" no aparecía en la vista grid — la detección de texto truncado corría antes de que la paginación mostrara las cards (arrancan en `display:none`), por lo que siempre medía altura 0. Ahora se re-evalúa cada vez que una card se hace visible (carga inicial, cambio de página, o tras inicializar el carousel)

### 2.2.0
- **Nuevo:** Truncado de reseñas largas — configurable por comercio (2 a 8 líneas, default 4), con botón "Leer más" / "Leer menos" que solo aparece si el texto realmente se corta. En el carousel, expandir una reseña recalcula el `autoHeight` del slide activo

### 2.1.0
- **Nuevo:** Opciones "1 semana" (168h) y "1 mes" (720h) en la frecuencia de cache de cada comercio — pensado para planes con cuota limitada de peticiones (ej. SerpApi free), donde conviene sincronizar manualmente vía el botón 🔄 Cache en vez de dejar que la caché expire varias veces por semana
- **Fix:** Columna `cache_horas` ampliada de `TINYINT` (máx. 255h) a `SMALLINT` (máx. 65535h) para soportar la opción de 1 mes

### 2.0.0
- **Breaking:** Migración de la Places API oficial de Google a **[SerpApi](https://serpapi.com/)** (`google_maps` + `google_maps_reviews`) — la API oficial solo entregaba un resumen de 5 reseñas sin control de orden
- **Nuevo:** Reseñas ordenadas por nota más alta (`sort_by=ratingHigh`) y paginadas (hasta 3 páginas por comercio), en vez del resumen fijo de 5 reseñas
- **Breaking:** La opción de API Key cambió de `jjrc_gr_api_key` a `jjrc_gr_serpapi_key` — **debes reingresar tu API Key** (ahora de SerpApi, no de Google Cloud) en **Google Reviews → Configuración**
- **Eliminado:** Carga del script `maps.googleapis.com` (autocompletado JS legacy) — ya no se usa

### 1.4.8
- **Fix:** `autoHeight` cortaba las cards — agregado `padding-bottom: 30px` en `.owl-stage-outer` para que el espacio se sume a la altura calculada

### 1.4.7
- **Mejora:** Carousel con `autoHeight: true` — la altura se ajusta automáticamente al slide activo

### 1.4.6
- **Fix:** `maybe_upgrade()` ahora detecta si las tablas no existen y las crea automáticamente — cubre instalaciones por FTP/git sin pasar por el botón "Activar" de WordPress (plug and play)

### 1.4.5
- **Fix:** `install()` no incluía las columnas `show_dots`, `show_nav`, `nav_position` y `color_nav` en el `CREATE TABLE`, causando "Error al guardar en la base de datos" en instalaciones nuevas

### 1.4.4
- **Mejora:** Mensaje de error más informativo cuando el filtro de nota mínima elimina todas las reseñas — muestra cuántas reseñas devolvió la API y cuál fue la nota más alta

### 1.4.3
- **Nuevo:** Color de navegación independiente (`color_nav`) para dots y flechas del carousel, separado del color primario (estrellas). Comercios existentes heredan el color primario como valor por defecto

### 1.4.0
- **Nuevo:** Checkbox para mostrar/ocultar indicadores (dots) del carousel
- **Nuevo:** Checkbox para mostrar/ocultar flechas de navegación
- **Nuevo:** Selector de posición de flechas: a los costados (overlay centrado) o debajo del carousel
- **Mejora:** Opciones de carousel se ocultan automáticamente al elegir vista Grid; selector de posición se oculta al desmarcar flechas

### 1.3.2
- **Mejora:** CSS del frontend reforzado para evitar conflictos con Elementor y otros constructores visuales — reset de aislamiento, todos los selectores bajo `.jjrc-gr-wrap`, `!important` estratégico en propiedades que Elementor sobreescribe (márgenes de `p`, dimensiones de `img`, links)

### 1.3.1
- **Fix:** Idioma de reseñas en inglés — `X-Goog-LanguageCode` no es un header válido en Places API (New); corregido usando `languageCode=es` como query parameter en la URL

### 1.3.0
- **Migración:** Plugin completo migrado a **Places API (New)** — hasta 53 reseñas por lugar (antes 5)
- **Mejora:** Buscador también migrado al nuevo endpoint `places:searchText` (POST)
- **Mejora:** Autenticación vía header `X-Goog-Api-Key` en lugar de query param — más compatible con restricciones de API key
- **Breaking:** Requiere que **Places API (New)** esté habilitada en Google Cloud Console (además de Places API)

### 1.2.2
- **Fix:** La migración de `min_rating` no se ejecutaba porque la versión DB ya estaba marcada como actualizada — corregido incrementando `DB_VERSION` a `1.2`

### 1.2.1
- **Fix:** La columna `min_rating` no se creaba en tablas existentes — reemplazada migración con `dbDelta` por `ALTER TABLE` explícito con verificación previa

### 1.2.0
- **Nuevo:** Filtro de nota mínima por comercio — elige mostrar reseñas desde 1 a 5 estrellas (default: 4★). El filtro se aplica en tiempo de render, sin afectar la caché almacenada
- **Mejora:** Migración automática de base de datos al cargar el plugin (`maybe_upgrade`), sin necesidad de reactivar

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
