<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class JJRC_GR_Api {

    const ENDPOINT  = 'https://serpapi.com/search';
    const MAX_PAGES = 3; // Páginas de reseñas a traer por comercio (~10 reseñas por página)

    private static function api_key() {
        return get_option( 'jjrc_gr_serpapi_key', '' );
    }

    /**
     * Búsqueda de lugares — SerpApi engine=google_maps (type=search)
     * Devuelve múltiples candidatos con place_id y dirección.
     */
    public static function autocomplete( $input ) {
        $key = self::api_key();
        if ( empty( $key ) ) return [ 'error' => 'API Key no configurada.' ];

        $url = add_query_arg( [
            'engine'  => 'google_maps',
            'type'    => 'search',
            'q'       => $input,
            'hl'      => 'es',
            'api_key' => $key,
        ], self::ENDPOINT );

        $response = wp_remote_get( $url, [ 'timeout' => 20 ] );

        if ( is_wp_error( $response ) ) {
            return [ 'error' => $response->get_error_message() ];
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['error'] ) ) {
            return [ 'error' => $body['error'] ];
        }

        $results = [];
        foreach ( $body['local_results'] ?? [] as $p ) {
            $place_id = $p['place_id'] ?? $p['data_id'] ?? '';
            if ( empty( $place_id ) ) continue;

            $results[] = [
                'place_id'    => $place_id,
                'description' => ( $p['title'] ?? '' ) . ( ! empty( $p['address'] ) ? ' — ' . $p['address'] : '' ),
            ];
        }

        return [ 'predictions' => $results ];
    }

    /**
     * Obtener reviews — SerpApi engine=google_maps_reviews, ordenadas por nota más alta primero.
     * Pagina hasta MAX_PAGES (via next_page_token) para traer más reseñas que el resumen
     * de 5 que entregaba la Places API oficial.
     */
    public static function get_reviews( $place_id ) {
        $key = self::api_key();
        if ( empty( $key ) ) return [ 'error' => 'API Key no configurada.' ];

        $reviews    = [];
        $place_info = [];
        $next_token = null;

        for ( $page = 1; $page <= self::MAX_PAGES; $page++ ) {
            $params = [
                'engine'  => 'google_maps_reviews',
                'sort_by' => 'ratingHigh',
                'hl'      => 'es',
                'api_key' => $key,
            ];

            if ( $next_token ) {
                $params['next_page_token'] = $next_token;
            } else {
                $params['place_id'] = $place_id;
            }

            $response = wp_remote_get( add_query_arg( $params, self::ENDPOINT ), [ 'timeout' => 20 ] );

            if ( is_wp_error( $response ) ) {
                if ( empty( $reviews ) ) return [ 'error' => $response->get_error_message() ];
                break;
            }

            $body = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( isset( $body['error'] ) ) {
                if ( empty( $reviews ) ) return [ 'error' => $body['error'] ];
                break;
            }

            if ( $page === 1 ) {
                $place_info = $body['place_info'] ?? [];
            }

            foreach ( $body['reviews'] ?? [] as $r ) {
                $reviews[] = [
                    'author'       => $r['user']['name']      ?? '',
                    'author_photo' => $r['user']['thumbnail'] ?? '',
                    'rating'       => $r['rating']             ?? 0,
                    'text'         => $r['snippet']            ?? '',
                    'time'         => $r['date']               ?? '',
                    'timestamp'    => isset( $r['iso_date'] ) ? strtotime( $r['iso_date'] ) : 0,
                ];
            }

            $next_token = $body['serpapi_pagination']['next_page_token'] ?? null;
            if ( ! $next_token ) break;
        }

        return [
            'name'          => $place_info['title']   ?? '',
            'rating'        => $place_info['rating']  ?? 0,
            'total_ratings' => $place_info['reviews'] ?? 0,
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
            $updated = strtotime( $cache->updated_at );
            $max_age = absint( $comercio->cache_horas ) * HOUR_IN_SECONDS;

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
