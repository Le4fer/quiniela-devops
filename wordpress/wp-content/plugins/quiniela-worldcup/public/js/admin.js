/**
 * QWC Admin JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        console.log('QWC Admin loaded');
        
        // Confirmación de eliminación
        $('.qwc-delete-btn').on('click', function(e) {
            if (!confirm('¿Estás seguro de eliminar este elemento?')) {
                e.preventDefault();
            }
        });
    });

})(jQuery);
