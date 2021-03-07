/* ==========================================================================
 Scripts voor de frontend
 ========================================================================== */
require(['jquery'], function ($) {
    $(function () {
        $('.sidebar').on('click', '.o-list li', function () {
            var element = $(this);

            if (element.hasClass('active')) {
                element.find('ul').slideUp();

                element.removeClass('active');
                element.find('li').removeClass('active');

                element.find('i').removeClass('fa-angle-down').addClass('fa-angle-up');
            } else {
                element.children('ul').slideDown();
                element.siblings('li').children('ul').slideUp();
                element.parent('ul').find('i').removeClass('fa-angle-down').addClass('fa-angle-up');
                element.find('> span i').removeClass('fa-angle-up').addClass('fa-angle-down');

                element.addClass('active');
                element.siblings('li').removeClass('active');
                element.siblings('li').find('li').removeClass('active');
                element.siblings('li').find('ul').slideUp();
            }
        });
    });
});
