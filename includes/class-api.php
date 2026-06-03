<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class JJRC_GR_Api {

    private static function api_key() {
        return get_option( 'jjrc_gr_api_key', '' );
    }

    /**
     * Búsqueda de lugares — Places API (New): POST places:searchText
     * Devuelve múltiples candidatos con place_id y dirección.
     */
    public static function autocomplete( $input ) {
        $key = self::api_key();
        if ( empty( $key ) ) return [ 'error' => 'API Key no configurada.' ];

        $response = wp_remote_post( 'https://places.googleapis.com/v1/places:searchText', [
            'timeout' => 10,
            'headers' => [
                'X-Goog-Api-Key'   => $key,
                'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress',
                'Content-Type'     => 'application/json',
            ],
            'body' => wp_json_encode( [
                'textQuery'    => $input,
                'languageCode' => 'es',
            ] ),
        ] );

        if ( is_wp_error( $response ) ) {
            return [ 'error' => $response->get_error_message() ];
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['error'] ) ) {
            return [ 'error' => $body['error']['message'] ?? 'Error desconocido.' ];
        }

        $results = [];
        foreach ( $body['places'] ?? [] as $p ) {
            $results[] = [
                'place_id'    => $p['id'],
                'description' => ( $p['displayName']['text'] ?? '' ) .
                                 ( ! empty( $p['formattedAddress'] ) ? ' — ' . $p['formattedAddress'] : '' ),
            ];
        }

        return [ 'predictions' => $results ];
    }

    /**
     * Obtener reviews — Places API (New): GET places/{place_id}
     * Devuelve hasta 53 reseñas (vs 5 de la API antigua).
     */
    public static function get_reviews( $place_id ) {
        $key = self::api_key();
        if ( empty( $key ) ) return [ 'error' => 'API Key no configurada.' ];

        $url = 'https://places.googleapis.com/v1/places/' . rawurlencode( $place_id );

        $response = wp_remote_get( $url, [
            'timeout' => 10,
            'headers' => [
                'X-Goog-Api-Key'      => $key,
                'X-Goog-FieldMask'    => 'displayName,rating,userRatingCount,reviews',
                'X-Goog-LanguageCode' => 'es',
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return [ 'error' => $response->get_error_message() ];
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['error'] ) ) {
            return [ 'error' => $body['error']['message'] ?? 'Error desconocido.' ];
        }

        $reviews = [];
        foreach ( $body['reviews'] ?? [] as $r ) {
            $reviews[] = [
                'author'       => $r['authorAttribution']['displayName'] ?? '',
                'author_photo' => $r['authorAttribution']['photoUri']    ?? '',
                'rating'       => $r['rating']                           ?? 0,
                'text'         => $r['text']['text']                     ?? '',
                'time'         => $r['relativePublishTimeDescription']   ?? '',
                'timestamp'    => isset( $r['publishTime'] ) ? strtotime( $r['publishTime'] ) : 0,
            ];
        }

        return [
            'name'          => $body['displayName']['text'] ?? '',
            'rating'        => $body['rating']              ?? 0,
            'total_ratings' => $body['userRatingCount']     ?? 0,
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
