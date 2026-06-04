<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap jjrc-gr-admin">
    <h1>
        <span class="dashicons dashicons-star-filled"></span>
        JJRC Google Reviews — Comercios
    </h1>

    <?php if ( empty( $api_key ) ) : ?>
        <div class="notice notice-warning">
            <p>
                <strong>Atención:</strong> No has configurado tu API Key de Google.
                <a href="<?php echo admin_url( 'admin.php?page=jjrc-gr-settings' ); ?>">Ir a Configuración →</a>
            </p>
        </div>
    <?php endif; ?>

    <!-- Botón nuevo -->
    <div class="jjrc-gr-toolbar">
        <button class="button button-primary" id="jjrc-btn-nuevo">
            + Agregar Comercio
        </button>
    </div>

    <!-- Modal formulario -->
    <div id="jjrc-modal-overlay" class="jjrc-modal-overlay" style="display:none;">
        <div class="jjrc-modal">
            <div class="jjrc-modal-header">
                <h2 id="jjrc-modal-title">Nuevo Comercio</h2>
                <button class="jjrc-modal-close" id="jjrc-modal-close">&times;</button>
            </div>
            <div class="jjrc-modal-body">
                <form id="jjrc-form-comercio">
                    <input type="hidden" id="jjrc-field-id" name="id" value="0">

                    <div class="jjrc-field-group">
                        <label>Place ID <span class="required">*</span></label>
                        <div class="jjrc-place-id-wrap">
                            <input type="text" id="jjrc-field-place-id" name="place_id" placeholder="ChIJ…" autocomplete="off">
                            <button type="button" id="jjrc-btn-search-place" class="button jjrc-btn-search" title="Buscar comercio en Google">
                                <span class="dashicons dashicons-search"></span>
                            </button>
                        </div>
                        <p class="jjrc-field-hint">Ingresa el Place ID directamente o haz clic en la lupa para buscarlo.</p>
                    </div>

                    <div class="jjrc-field-group">
                        <label>Nombre del comercio <span class="required">*</span></label>
                        <input type="text" id="jjrc-field-nombre" name="nombre" placeholder="Ej: Restaurante El Chipe">
                    </div>

                    <div class="jjrc-field-group">
                        <label>Shortcode key <span class="required">*</span></label>
                        <input type="text" id="jjrc-field-key" name="shortcode_key" placeholder="ej: restaurante_centro">
                        <p class="jjrc-field-hint">Solo letras, números y guiones bajos. Se usará como: <code>[jjrc_reviews key="<span id="jjrc-key-preview">…</span>"]</code></p>
                    </div>

                    <div class="jjrc-field-row">
                        <div class="jjrc-field-group">
                            <label>Tipo de vista</label>
                            <select name="tipo_vista" id="jjrc-field-vista">
                                <option value="carousel">Owl Carousel</option>
                                <option value="grid">Grid con paginación</option>
                            </select>
                        </div>
                        <div class="jjrc-field-group">
                            <label>Mostrar reseñas desde</label>
                            <select name="min_rating" id="jjrc-field-min-rating">
                                <option value="1">⭐ Todas (1 estrella o más)</option>
                                <option value="2">⭐⭐ 2 estrellas o más</option>
                                <option value="3">⭐⭐⭐ 3 estrellas o más</option>
                                <option value="4" selected>⭐⭐⭐⭐ 4 estrellas o más</option>
                                <option value="5">⭐⭐⭐⭐⭐ Solo 5 estrellas</option>
                            </select>
                        </div>
                        <div class="jjrc-field-group">
                            <label>Actualizar cache cada</label>
                            <select name="cache_horas" id="jjrc-field-cache">
                                <option value="6">6 horas</option>
                                <option value="12" selected>12 horas</option>
                                <option value="24">24 horas</option>
                                <option value="48">48 horas</option>
                            </select>
                        </div>
                    </div>

                    <!-- Opciones exclusivas del carousel -->
                    <div id="jjrc-carousel-options" class="jjrc-field-group">
                        <label>Opciones del carousel</label>
                        <div class="jjrc-checkboxes-row">
                            <label class="jjrc-check-label">
                                <input type="checkbox" name="show_dots" id="jjrc-field-show-dots" value="1" checked>
                                Mostrar indicadores (dots)
                            </label>
                            <label class="jjrc-check-label">
                                <input type="checkbox" name="show_nav" id="jjrc-field-show-nav" value="1" checked>
                                Mostrar flechas de navegación
                            </label>
                        </div>
                        <div id="jjrc-nav-position-wrap" class="jjrc-field-group" style="margin-top:10px; margin-bottom:0;">
                            <label>Posición de las flechas</label>
                            <select name="nav_position" id="jjrc-field-nav-position">
                                <option value="sides">A los costados</option>
                                <option value="bottom">Debajo del carousel</option>
                            </select>
                        </div>
                    </div>

                    <div class="jjrc-field-group">
                        <label>Colores</label>
                        <div class="jjrc-colors-row">
                            <div class="jjrc-color-field">
                                <label>Primario (estrellas)</label>
                                <input type="color" name="color_primario" id="jjrc-field-color-primario" value="#f5a623">
                            </div>
                            <div class="jjrc-color-field">
                                <label>Fondo tarjeta</label>
                                <input type="color" name="color_fondo" id="jjrc-field-color-fondo" value="#ffffff">
                            </div>
                            <div class="jjrc-color-field">
                                <label>Texto</label>
                                <input type="color" name="color_texto" id="jjrc-field-color-texto" value="#333333">
                            </div>
                        </div>
                    </div>

                    <div class="jjrc-modal-footer">
                        <span id="jjrc-form-message" class="jjrc-form-message"></span>
                        <button type="button" class="button" id="jjrc-btn-cancelar">Cancelar</button>
                        <button type="submit" class="button button-primary" id="jjrc-btn-guardar">
                            <span class="jjrc-btn-text">Guardar</span>
                            <span class="jjrc-spinner" style="display:none;">⏳</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de búsqueda de comercio -->
    <div id="jjrc-search-overlay" class="jjrc-modal-overlay jjrc-search-overlay" style="display:none;">
        <div class="jjrc-modal jjrc-search-modal">
            <div class="jjrc-modal-header">
                <h2>Buscar comercio en Google</h2>
                <button class="jjrc-modal-close" id="jjrc-search-close">&times;</button>
            </div>
            <div class="jjrc-modal-body">
                <div class="jjrc-field-group" style="margin-bottom:8px;">
                    <input type="text" id="jjrc-search-input" placeholder="Ej: Restaurante El Chipe, Santiago" autocomplete="off">
                </div>
                <p id="jjrc-search-hint" class="jjrc-field-hint">Escribe al menos 3 caracteres para buscar.</p>
                <ul id="jjrc-search-results" class="jjrc-search-results-list" style="display:none;"></ul>
            </div>
        </div>
    </div>

    <!-- Tabla de comercios -->
    <table class="wp-list-table widefat fixed striped jjrc-table">
        <thead>
            <tr>
                <th>Comercio</th>
                <th>Place ID</th>
                <th>Shortcode</th>
                <th>Vista</th>
                <th>Rating</th>
                <th>Reviews visibles</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="jjrc-comercios-tbody">
            <?php if ( empty( $comercios ) ) : ?>
                <tr id="jjrc-row-empty">
                    <td colspan="7" style="text-align:center; padding: 30px; color:#999;">
                        No hay comercios configurados. Haz clic en <strong>+ Agregar Comercio</strong> para comenzar.
                    </td>
                </tr>
            <?php else : ?>
                <?php foreach ( $comercios as $c ) :
                    $cache       = JJRC_GR_Database::get_cache( $c->id );
                    $min_rating  = absint( $c->min_rating ?? 1 );
                    $all_reviews = $cache ? ( json_decode( $cache->reviews_json, true ) ?? [] ) : [];
                    $total_cache = count( $all_reviews );
                    $visibles    = count( array_filter( $all_reviews, fn( $r ) => ( $r['rating'] ?? 0 ) >= $min_rating ) );
                ?>
                <tr id="jjrc-row-<?php echo absint( $c->id ); ?>">
                    <td><strong><?php echo esc_html( $c->nombre ); ?></strong></td>
                    <td><code><?php echo esc_html( $c->place_id ); ?></code></td>
                    <td>
                        <code class="jjrc-shortcode-copy" data-clipboard="[jjrc_reviews key=&quot;<?php echo esc_attr( $c->shortcode_key ); ?>&quot;]">
                            [jjrc_reviews key="<?php echo esc_html( $c->shortcode_key ); ?>"]
                        </code>
                        <button class="button button-small jjrc-btn-copy" data-text="[jjrc_reviews key=&quot;<?php echo esc_attr( $c->shortcode_key ); ?>&quot;]">Copiar</button>
                    </td>
                    <td><?php echo $c->tipo_vista === 'carousel' ? 'Carousel' : 'Grid'; ?></td>
                    <td>
                        <?php if ( $cache ) : ?>
                            <span class="jjrc-rating"><?php echo esc_html( $cache->rating ); ?> (<?php echo number_format( $cache->total_ratings ); ?>)</span>
                        <?php else : ?>
                            <span class="jjrc-no-cache">Sin cache</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ( $cache ) : ?>
                            <span class="jjrc-visible-count <?php echo $visibles === 0 ? 'jjrc-visible-zero' : ''; ?>">
                                <?php echo absint( $visibles ); ?> <span class="jjrc-visible-total">/ <?php echo absint( $total_cache ); ?></span>
                            </span>
                            <?php if ( $min_rating > 1 ) : ?>
                                <span class="jjrc-visible-filter">≥ <?php echo $min_rating; ?>★</span>
                            <?php endif; ?>
                        <?php else : ?>
                            <span class="jjrc-no-cache">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="jjrc-actions">
                        <button class="button button-small jjrc-btn-edit"
                            data-id="<?php echo $c->id; ?>"
                            data-nombre="<?php echo esc_attr( $c->nombre ); ?>"
                            data-place_id="<?php echo esc_attr( $c->place_id ); ?>"
                            data-key="<?php echo esc_attr( $c->shortcode_key ); ?>"
                            data-vista="<?php echo esc_attr( $c->tipo_vista ); ?>"
                            data-cache="<?php echo esc_attr( $c->cache_horas ); ?>"
                            data-color_primario="<?php echo esc_attr( $c->color_primario ); ?>"
                            data-color_fondo="<?php echo esc_attr( $c->color_fondo ); ?>"
                            data-color_texto="<?php echo esc_attr( $c->color_texto ); ?>"
                            data-min_rating="<?php echo absint( $c->min_rating ?? 1 ); ?>"
                            data-show_dots="<?php echo absint( $c->show_dots ?? 1 ); ?>"
                            data-show_nav="<?php echo absint( $c->show_nav ?? 1 ); ?>"
                            data-nav_position="<?php echo esc_attr( $c->nav_position ?? 'sides' ); ?>">
                            Editar
                        </button>
                        <button class="button button-small jjrc-btn-refresh" data-id="<?php echo $c->id; ?>">
                            Cache
                        </button>
                        <button class="button button-small jjrc-btn-delete" data-id="<?php echo $c->id; ?>">
                            Eliminar
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
