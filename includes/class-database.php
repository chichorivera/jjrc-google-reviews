<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class JJRC_GR_Database {

    const DB_VERSION = '1.1';

    public static function install() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $sql_comercios = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}gr_comercios (
            id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre          VARCHAR(255) NOT NULL,
            place_id        VARCHAR(255) NOT NULL,
            shortcode_key   VARCHAR(100) NOT NULL UNIQUE,
            tipo_vista      ENUM('carousel','grid') NOT NULL DEFAULT 'carousel',
            color_primario  VARCHAR(7)  NOT NULL DEFAULT '#f5a623',
            color_fondo     VARCHAR(7)  NOT NULL DEFAULT '#ffffff',
            color_texto     VARCHAR(7)  NOT NULL DEFAULT '#333333',
            cache_horas     TINYINT UNSIGNED NOT NULL DEFAULT 12,
            min_rating      TINYINT UNSIGNED NOT NULL DEFAULT 1,
            created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";

        $sql_cache = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}gr_reviews_cache (
            id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
            comercio_id  INT UNSIGNED NOT NULL,
            reviews_json LONGTEXT NOT NULL,
            rating       DECIMAL(2,1) NOT NULL DEFAULT 0,
            total_ratings INT UNSIGNED NOT NULL DEFAULT 0,
            updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY comercio_id (comercio_id)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_comercios );
        dbDelta( $sql_cache );

        update_option( 'jjrc_gr_db_version', self::DB_VERSION );
    }

    public static function maybe_upgrade() {
        if ( get_option( 'jjrc_gr_db_version' ) === self::DB_VERSION ) return;

        global $wpdb;

        $columns = $wpdb->get_col( "DESCRIBE {$wpdb->prefix}gr_comercios", 0 );
        if ( ! in_array( 'min_rating', $columns, true ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}gr_comercios ADD COLUMN min_rating TINYINT UNSIGNED NOT NULL DEFAULT 1" );
        }

        update_option( 'jjrc_gr_db_version', self::DB_VERSION );
    }

    public static function uninstall() {
        global $wpdb;
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}gr_reviews_cache" );
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}gr_comercios" );
        delete_option( 'jjrc_gr_db_version' );
        delete_option( 'jjrc_gr_api_key' );
    }

    // ---------- COMERCIOS ----------

    public static function get_comercios() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}gr_comercios ORDER BY id DESC" );
    }

    public static function get_comercio( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gr_comercios WHERE id = %d", $id
        ) );
    }

    public static function get_comercio_by_key( $key ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gr_comercios WHERE shortcode_key = %s", $key
        ) );
    }

    public static function save_comercio( $data ) {
        global $wpdb;
        $fields = [
            'nombre'         => sanitize_text_field( $data['nombre'] ),
            'place_id'       => sanitize_text_field( $data['place_id'] ),
            'shortcode_key'  => sanitize_key( $data['shortcode_key'] ),
            'tipo_vista'     => in_array( $data['tipo_vista'], ['carousel','grid'] ) ? $data['tipo_vista'] : 'carousel',
            'color_primario' => sanitize_hex_color( $data['color_primario'] ) ?: '#f5a623',
            'color_fondo'    => sanitize_hex_color( $data['color_fondo'] )    ?: '#ffffff',
            'color_texto'    => sanitize_hex_color( $data['color_texto'] )    ?: '#333333',
            'cache_horas'    => absint( $data['cache_horas'] ) ?: 12,
            'min_rating'     => max( 1, min( 5, absint( $data['min_rating'] ?? 1 ) ) ),
        ];

        if ( ! empty( $data['id'] ) ) {
            $wpdb->update( "{$wpdb->prefix}gr_comercios", $fields, [ 'id' => absint( $data['id'] ) ] );
            return absint( $data['id'] );
        } else {
            $wpdb->insert( "{$wpdb->prefix}gr_comercios", $fields );
            return $wpdb->insert_id;
        }
    }

    public static function delete_comercio( $id ) {
        global $wpdb;
        $wpdb->delete( "{$wpdb->prefix}gr_reviews_cache", [ 'comercio_id' => $id ] );
        $wpdb->delete( "{$wpdb->prefix}gr_comercios",     [ 'id'          => $id ] );
    }

    // ---------- CACHE ----------

    public static function get_cache( $comercio_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gr_reviews_cache WHERE comercio_id = %d", $comercio_id
        ) );
    }

    public static function save_cache( $comercio_id, $reviews, $rating, $total_ratings ) {
        global $wpdb;
        $table = "{$wpdb->prefix}gr_reviews_cache";
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $table WHERE comercio_id = %d", $comercio_id
        ) );

        $data = [
            'reviews_json' => wp_json_encode( $reviews ),
            'rating'       => $rating,
            'total_ratings'=> $total_ratings,
            'updated_at'   => current_time( 'mysql' ),
        ];

        if ( $exists ) {
            $wpdb->update( $table, $data, [ 'comercio_id' => $comercio_id ] );
        } else {
            $data['comercio_id'] = $comercio_id;
            $wpdb->insert( $table, $data );
        }
    }

    public static function delete_cache( $comercio_id ) {
        global $wpdb;
        $wpdb->delete( "{$wpdb->prefix}gr_reviews_cache", [ 'comercio_id' => $comercio_id ] );
    }
}
