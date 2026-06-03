<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class JJRC_GR_Api {

    private static function api_key() {
        return get_option( 'jjrc_gr_api_key', '' );
    }

    /**
     * Búsqueda de lugares por texto (para el admin)
     * Usa textsearch en lugar de autocomplete para mayor compatibilidad de API key.
     */
    public static function autocomplete( $input ) {
        $key = self::api_key();
        if ( empty( $key ) ) return [ 'error' => 'API Key no configurada.' ];

        $url = add_query_arg( [
            'query'    => $input,
            'language' => 'es',
            'key'      => $key,
        ], 'https://maps.googleapis.com/maps/api/place/textsearch/json' );

        $response = wp_remote_get( $url, [ 'timeout' => 10 ] );

        if ( is_wp_error( $response ) ) {
            return [ 'error' => $response->get_error_message() ];
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $body['status'] !== 'OK' && $body['status'] !== 'ZERO_RESULTS' ) {
            return [ 'error' => $body['status'] . ': ' . ( $body['error_message'] ?? '' ) ];
        }

        $results = [];
        foreach ( $body['results'] ?? [] as $r ) {
            $results[] = [
                'place_id'    => $r['place_id'],
                'description' => $r['name'] . ( ! empty( $r['formatted_address'] ) ? ' — ' . $r['formatted_address'] : '' ),
            ];
        }

        return [ 'predictions' => $results ];
    }

    /**
     * Obtener reviews de un place_id
     */
    public static function get_reviews( $place_id ) {
        $key = self::api_key();
        if ( empty( $key ) ) return [ 'error' => 'API Key no configurada.' ];

        $url = add_query_arg( [
            'place_id' => $place_id,
            'fields'   => 'name,rating,user_ratings_total,reviews',
            'language' => 'es',
            'reviews_sort' => 'newest',
            'key'      => $key,
        ], 'https://maps.googleapis.com/maps/api/place/details/json' );

        $response = wp_remote_get( $url, [ 'timeout' => 10 ] );

        if ( is_wp_error( $response ) ) {
            return [ 'error' => $response->get_error_message() ];
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $body['status'] !== 'OK' ) {
            return [ 'error' => $body['status'] . ': ' . ( $body['error_message'] ?? '' ) ];
        }

        $result  = $body['result'];
        $reviews = [];

        foreach ( $result['reviews'] ?? [] as $r ) {
            $reviews[] = [
                'author'       => $r['author_name'],
                'author_photo' => $r['profile_photo_url'] ?? '',
                'rating'       => $r['rating'],
                'text'         => $r['text'],
                'time'         => $r['relative_time_description'],
                'timestamp'    => $r['time'],
            ];
        }

        return [
            'name'          => $result['name'],
            'rating'        => $result['rating']              ?? 0,
            'total_ratings' => $result['user_ratings_total']  ?? 0,
            'reviews'       => $reviews,
        ];
    }

    /**
     * Obtener reviews con cache
     */
    public static function get_reviews_cached( $comercio ) {
        $cache = JJRC_GR_Database::get_cache( $comercio->id );
        $now   = time();

        if ( $cache ) {
            $updated  = strtotime( $cache->updated_at );
            $max_age  = absint( $comercio->cache_horas ) * HOUR_IN_SECONDS;

            if ( ( $now - $updated ) < $max_age ) {
                return [
                    'name'          => $comercio->nombre,
                    'rating'        => $cache->rating,
                    'total_ratings' => $cache->total_ratings,
                    'reviews'       => json_decode( $cache->reviews_json, true ),
                    'cached'        => true,
                ];
            }
        }

        // Cache expirada o inexistente → llamar API
        $data = self::get_reviews( $comercio->place_id );

        if ( isset( $data['error'] ) ) return $data;

        JJRC_GR_Database::save_cache(
            $comercio->id,
            $data['reviews'],
            $data['rating'],
            $data['total_ratings']
        );

        $data['cached'] = false;
        return $data;
    }
}
