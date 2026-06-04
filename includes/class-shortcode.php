<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class JJRC_GR_Shortcode {

    public static function init() {
        add_shortcode( 'jjrc_reviews', [ __CLASS__, 'render' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
    }

    public static function enqueue_assets() {
        // Owl Carousel
        wp_register_style(
            'owl-carousel',
            'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css',
            [],
            '2.3.4'
        );
        wp_register_style(
            'owl-carousel-theme',
            'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css',
            [ 'owl-carousel' ],
            '2.3.4'
        );
        wp_register_script(
            'owl-carousel',
            'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js',
            [ 'jquery' ],
            '2.3.4',
            true
        );

        // Plugin frontend
        wp_register_style(
            'jjrc-gr-frontend',
            JJRC_GR_URL . 'assets/css/frontend.css',
            [],
            JJRC_GR_VERSION
        );
        wp_register_script(
            'jjrc-gr-frontend',
            JJRC_GR_URL . 'assets/js/frontend.js',
            [ 'jquery', 'owl-carousel' ],
            JJRC_GR_VERSION,
            true
        );
    }

    public static function render( $atts ) {
        $atts = shortcode_atts( [ 'key' => '' ], $atts, 'jjrc_reviews' );

        if ( empty( $atts['key'] ) ) {
            return '<p class="jjrc-gr-error">Shortcode sin key definida.</p>';
        }

        $comercio = JJRC_GR_Database::get_comercio_by_key( $atts['key'] );

        if ( ! $comercio ) {
            return '<p class="jjrc-gr-error">Comercio no encontrado: ' . esc_html( $atts['key'] ) . '</p>';
        }

        $data = JJRC_GR_Api::get_reviews_cached( $comercio );

        if ( isset( $data['error'] ) ) {
            return '<p class="jjrc-gr-error">Error al obtener reseñas: ' . esc_html( $data['error'] ) . '</p>';
        }

        // Filtrar por nota mínima (se aplica sobre la caché, no afecta lo almacenado)
        $min_rating = absint( $comercio->min_rating ?? 1 );
        if ( $min_rating > 1 ) {
            $data['reviews'] = array_values( array_filter( $data['reviews'], function ( $r ) use ( $min_rating ) {
                return $r['rating'] >= $min_rating;
            } ) );
        }

        if ( empty( $data['reviews'] ) ) {
            return '<p class="jjrc-gr-error">No hay reseñas con ' . esc_html( $min_rating ) . ' estrellas o más.</p>';
        }

        // Encolar assets
        wp_enqueue_style( 'owl-carousel' );
        wp_enqueue_style( 'owl-carousel-theme' );
        wp_enqueue_style( 'jjrc-gr-frontend' );
        wp_enqueue_script( 'owl-carousel' );
        wp_enqueue_script( 'jjrc-gr-frontend' );

        // CSS variables inline
        $uid = 'jjrc-gr-' . esc_attr( $comercio->shortcode_key );
        $color_nav  = ! empty( $comercio->color_nav ) ? $comercio->color_nav : $comercio->color_primario;
        $inline_css = "
            #{$uid} {
                --jjrc-color-primary: {$comercio->color_primario};
                --jjrc-color-bg:      {$comercio->color_fondo};
                --jjrc-color-text:    {$comercio->color_texto};
                --jjrc-color-nav:     {$color_nav};
            }
        ";
        wp_add_inline_style( 'jjrc-gr-frontend', $inline_css );

        ob_start();

        if ( $comercio->tipo_vista === 'carousel' ) {
            include JJRC_GR_PATH . 'templates/carousel.php';
        } else {
            include JJRC_GR_PATH . 'templates/grid.php';
        }

        return ob_get_clean();
    }

    public static function render_stars( $rating ) {
        $rating = floatval( $rating );
        $html   = '';
        for ( $i = 1; $i <= 5; $i++ ) {
            if ( $rating >= $i ) {
                $html .= '★';
            } elseif ( $rating >= $i - 0.5 ) {
                $html .= '½';
            } else {
                $html .= '☆';
            }
        }
        return $html;
    }
}
