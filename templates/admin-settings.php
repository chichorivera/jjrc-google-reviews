<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap jjrc-gr-admin">
    <h1>
        <span class="dashicons dashicons-admin-settings"></span>
        JJRC Google Reviews — Configuración
    </h1>

    <form method="post" action="options.php">
        <?php settings_fields( 'jjrc_gr_settings' ); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="jjrc_gr_serpapi_key">API Key de SerpApi</label>
                </th>
                <td>
                    <input
                        type="text"
                        id="jjrc_gr_serpapi_key"
                        name="jjrc_gr_serpapi_key"
                        value="<?php echo esc_attr( get_option( 'jjrc_gr_serpapi_key', '' ) ); ?>"
                        class="regular-text"
                        placeholder="Tu Private API Key de SerpApi"
                    />
                    <p class="description">
                        Obtén tu API Key en
                        <a href="https://serpapi.com/manage-api-key" target="_blank">serpapi.com</a>.
                        El plugin usa los motores <code>google_maps</code> y <code>google_maps_reviews</code>.
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button( 'Guardar configuración' ); ?>
    </form>

    <hr>

    <h2>¿Cómo usar el plugin?</h2>
    <ol>
        <li>Ingresa tu API Key de SerpApi arriba y guarda.</li>
        <li>Ve a <strong>Comercios</strong> y agrega un nuevo comercio buscando por nombre.</li>
        <li>Selecciona el comercio correcto del autocompletado para obtener su Place ID.</li>
        <li>Elige el tipo de visualización y los colores.</li>
        <li>Copia el shortcode generado y pégalo en cualquier página o entrada.</li>
    </ol>

    <h2>Shortcode disponible</h2>
    <code>[jjrc_reviews key="nombre_del_comercio"]</code>
</div>
