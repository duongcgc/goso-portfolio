(function ($) {
    "use strict";
    var GOSO = GOSO || {};

    GOSO.portfolio_extra = function () {
        if ($().theiaStickySidebar) {
            var top_margin = 90,
                pbody = $('body');
            if (pbody.hasClass('admin-bar') && pbody.hasClass('goso-vernav-enable')) {
                top_margin = 62;
            } else if (!$('body').hasClass('admin-bar') && $('body').hasClass('goso-vernav-enable')) {
                top_margin = 30;
            } else if ($('body').hasClass('admin-bar') && !$('body').hasClass('goso-vernav-enable')) {
                top_margin = 122;
            }

            if ($('body.single-portfolio').find('.portfolio-page-content.portfolio-sticky-content').length > 0) {

                $('.post-entry.portfolio-style-3 .goso-portfolio-meta-wrapper,.post-entry.portfolio-style-3 .portfolio-sticky-content').theiaStickySidebar({
                    additionalMarginTop: top_margin,
                });
            }

        } // if sticky
    }

    /* Init functions
	 ---------------------------------------------------------------*/
    $(document).ready(function () {
        GOSO.portfolio_extra();
    });
})(jQuery);	// EOF
