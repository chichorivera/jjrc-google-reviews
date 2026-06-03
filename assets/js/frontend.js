/* global jQuery */
(function ($) {
    'use strict';

    $(document).ready(function () {

        // ---- OWL CAROUSEL ----
        $('.jjrc-gr-carousel .jjrc-owl-carousel').each(function () {
            $(this).owlCarousel({
                loop:       false,
                margin:     16,
                nav:        true,
                dots:       true,
                autoplay:   false,
                navText:    ['&#8249;', '&#8250;'],
                responsive: {
                    0:   { items: 1 },
                    600: { items: 2 },
                    1024:{ items: 3 }
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
