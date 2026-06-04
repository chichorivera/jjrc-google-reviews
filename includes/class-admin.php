<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class JJRC_GR_Admin {

    public static function init() {
        add_action( 'admin_menu',             [ __CLASS__, 'register_menu' ] );
        add_action( 'admin_init',             [ __CLASS__, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts',  [ __CLASS__, 'enqueue_assets' ] );
        add_action( 'wp_ajax_jjrc_gr_autocomplete',   [ __CLASS__, 'ajax_autocomplete' ] );
        add_action( 'wp_ajax_jjrc_gr_save_comercio',  [ __CLASS__, 'ajax_save_comercio' ] );
        add_action( 'wp_ajax_jjrc_gr_delete_comercio',[ __CLASS__, 'ajax_delete_comercio' ] );
        add_action( 'wp_ajax_jjrc_gr_refresh_cache',  [ __CLASS__, 'ajax_refresh_cache' ] );
    }

    public static function register_menu() {
        add_menu_page(
            'JJRC Google Reviews',
            'Google Reviews',
            'manage_options',
            'jjrc-google-reviews',
            [ __CLASS__, 'page_comercios' ],
            'dashicons-star-filled',
            80
        );
        add_submenu_page(
            'jjrc-google-reviews',
            'Comercios',
            'Comercios',
            'manage_options',
            'jjrc-google-reviews',
            [ __CLASS__, 'page_comercios' ]
        );
        add_submenu_page(
            'jjrc-google-reviews',
            'Configuración',
            'Configuración',
            'manage_options',
            'jjrc-gr-settings',
            [ __CLASS__, 'page_settings' ]
        );
    }

    public static function register_settings() {
        register_setting( 'jjrc_gr_settings', 'jjrc_gr_api_key', [
            'sanitize_callback' => 'sanitize_text_field',
        ] );
    }

    public static function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'jjrc-google-reviews' ) === false && strpos( $hook, 'jjrc-gr-settings' ) === false ) return;

        wp_enqueue_style(
            'jjrc-gr-admin',
            JJRC_GR_URL . 'assets/css/admin.css',
            [],
            JJRC_GR_VERSION
        );

        // Google Places Autocomplete (legacy JS API)
        $api_key = get_option( 'jjrc_gr_api_key', '' );
        if ( $api_key ) {
            wp_enqueue_script(
                'google-maps-places',
                "https://maps.googleapis.com/maps/api/js?key={$api_key}&libraries=places",
                [],
                null,
                true
            );
        }

        wp_enqueue_script(
            'jjrc-gr-admin',
            JJRC_GR_URL . 'assets/js/admin.js',
            [ 'jquery' ],
            JJRC_GR_VERSION,
            true
        );

        wp_localize_script( 'jjrc-gr-admin', 'jjrcGR', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'jjrc_gr_nonce' ),
        ] );
    }

    // ---------- PÁGINAS ----------

    public static function page_comercios() {
        $comercios = JJRC_GR_Database::get_comercios();
        $api_key   = get_option( 'jjrc_gr_api_key', '' );
        include JJRC_GR_PATH . 'templates/admin-comercios.php';
    }

    public static function page_settings() {
        include JJRC_GR_PATH . 'templates/admin-settings.php';
    }

    // ---------- AJAX ----------

    private static function verify_nonce() {
        if ( ! check_ajax_referer( 'jjrc_gr_nonce', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => 'Nonce inválido.' ], 403 );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Sin permisos.' ], 403 );
        }
    }

    public static function ajax_autocomplete() {
        self::verify_nonce();
        $input = sanitize_text_field( $_POST['input'] ?? '' );
        if ( empty( $input ) ) wp_send_json_error( [ 'message' => 'Input vacío.' ] );
        $result = JJRC_GR_Api::autocomplete( $input );
        isset( $result['error'] ) ? wp_send_json_error( $result ) : wp_send_json_success( $result );
    }

    public static function ajax_save_comercio() {
        self::verify_nonce();

        $data = [
            'id'             => absint( $_POST['id'] ?? 0 ),
            'nombre'         => sanitize_text_field( $_POST['nombre']        ?? '' ),
            'place_id'       => sanitize_text_field( $_POST['place_id']      ?? '' ),
            'shortcode_key'  => sanitize_key( $_POST['shortcode_key']        ?? '' ),
            'tipo_vista'     => sanitize_text_field( $_POST['tipo_vista']    ?? 'carousel' ),
            'color_primario' => sanitize_hex_color( $_POST['color_primario'] ?? '#f5a623' ),
            'color_fondo'    => sanitize_hex_color( $_POST['color_fondo']    ?? '#ffffff' ),
            'color_texto'    => sanitize_hex_color( $_POST['color_texto']    ?? '#333333' ),
            'cache_horas'    => absint( $_POST['cache_horas'] ?? 12 ),
            'min_rating'     => absint( $_POST['min_rating']   ?? 1 ),
            'show_dots'      => ! empty( $_POST['show_dots'] ) ? 1 : 0,
            'show_nav'       => ! empty( $_POST['show_nav'] )  ? 1 : 0,
            'nav_position'   => sanitize_text_field( $_POST['nav_position']  ?? 'sides' ),
            'color_nav'      => sanitize_hex_color( $_POST['color_nav']     ?? '#f5a623' ),
        ];

        if ( empty( $data['nombre'] ) || empty( $data['place_id'] ) || empty( $data['shortcode_key'] ) ) {
            wp_send_json_error( [ 'message' => 'Faltan campos obligatorios.' ] );
        }

        $id = JJRC_GR_Database::save_comercio( $data );

        if ( ! $id ) wp_send_json_error( [ 'message' => 'Error al guardar en la base de datos.' ] );

        // Limpiar cache si se editó
        if ( $data['id'] ) JJRC_GR_Database::delete_cache( $data['id'] );

        wp_send_json_success( [
            'id'        => $id,
            'shortcode' => '[jjrc_reviews key="' . $data['shortcode_key'] . '"]',
            'message'   => 'Comercio guardado correctamente.',
        ] );
    }

    public static function ajax_delete_comercio() {
        self::verify_nonce();
        $id = absint( $_POST['id'] ?? 0 );
        if ( ! $id ) wp_send_json_error( [ 'message' => 'ID inválido.' ] );
        JJRC_GR_Database::delete_comercio( $id );
        wp_send_json_success( [ 'message' => 'Comercio eliminado.' ] );
    }

    public static function ajax_refresh_cache() {
        self::verify_nonce();
        $id       = absint( $_POST['id'] ?? 0 );
        $comercio = JJRC_GR_Database::get_comercio( $id );
        if ( ! $comercio ) wp_send_json_error( [ 'message' => 'Comercio no encontrado.' ] );

        JJRC_GR_Database::delete_cache( $id );
        $data = JJRC_GR_Api::get_reviews_cached( $comercio );

        isset( $data['error'] )
            ? wp_send_json_error( $data )
            : wp_send_json_success( [ 'message' => 'Cache actualizada.', 'rating' => $data['rating'], 'total' => $data['total_ratings'] ] );
    }
}
