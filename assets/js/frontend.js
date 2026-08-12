/* global jQuery */
(function ($) {
    'use strict';

    $(document).ready(function () {

        // ---- TRUNCADO DE RESEÑAS LARGAS ----
        // Se corre antes de inicializar Owl Carousel para que autoHeight
        // mida el alto ya truncado de cada slide.
        $('.jjrc-review-text').each(function () {
            var $text = $(this);

            // Si el contenido real es más alto que la caja truncada (line-clamp),
            // hay texto oculto y corresponde mostrar el botón "Leer más".
            if ( this.scrollHeight <= this.clientHeight + 1 ) return;

            var $btn = $('<button type="button" class="jjrc-read-more">Leer más</button>');

            $btn.on('click', function () {
                var expanded = $text.toggleClass('jjrc-expanded').hasClass('jjrc-expanded');
                $btn.text(expanded ? 'Leer menos' : 'Leer más');

                // Si la reseña está dentro de un carousel con autoHeight, hay
                // que forzar el recálculo del alto del slide activo.
                var $owl = $text.closest('.jjrc-owl-carousel');
                if ($owl.length) {
                    $owl.trigger('refresh.owl.carousel');
                }
            });

            $text.after($btn);
        });

        // ---- OWL CAROUSEL ----
        $('.jjrc-gr-carousel .jjrc-owl-carousel').each(function () {
            var $el      = $(this);
            var showDots = parseInt( $el.data('dots') ) !== 0;
            var showNav  = parseInt( $el.data('nav') )  !== 0;

            $el.owlCarousel({
                loop:       false,
                margin:     16,
                nav:        showNav,
                dots:       showDots,
                autoplay:   false,
                autoHeight: true,
                navText:    ['&#8249;', '&#8250;'],
                responsive: {
                    0:    { items: 1 },
                    600:  { items: 2 },
                    1024: { items: 3 }
                }
            });
        });

        // ---- GRID CON PAGINACIÓN ----
        $('.jjrc-gr-grid').each(function () {
            var $wrap       = $(this);
            var $cards      = $wrap.find('.jjrc-review-card');
            var $pagination = $wrap.find('.jjrc-pagination');
            var perPage     = parseInt($wrap.find('.jjrc-grid-container').data('per-page')) || 3;
            var totalPages  = Math.ceil($cards.length / perPage);

            if (totalPages <= 1) {
                // Mostrar todas sin paginación
                $cards.addClass('active');
                return;
            }

            function goToPage(page) {
                $cards.removeClass('active').each(function (i) {
                    if (Math.floor(i / perPage) + 1 === page) {
                        $(this).addClass('active');
                    }
                });
                $pagination.find('button').removeClass('active');
                $pagination.find('[data-page="' + page + '"]').addClass('active');
            }

            // Crear botones de paginación
            for (var i = 1; i <= totalPages; i++) {
                $('<button></button>')
                    .text(i)
                    .attr('data-page', i)
                    .on('click', (function (p) {
                        return function () { goToPage(p); };
                    })(i))
                    .appendTo($pagination);
            }

            goToPage(1);
        });

    });

})(jQuery);
