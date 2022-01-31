"use strict";

/**
 * Lazyframe.js (https://github.com/viktorbergehall/lazyframe)
 */
!function ($) {
    // Exit if not needed
    var $meerkatVideowall = $('.meerkat-videowall');
    if (!$meerkatVideowall.find('.content').length) return;

    function doLazyLoad() {
        if (!document.getElementsByClassName) {
            var videos;
            // IE8 support
            var getElementsByClassName = function (node, classname) {
                var a   = [];
                var re  = new RegExp('(^| )' + classname + '( |$)');
                var els = node.getElementsByTagName("*");
                for (var i = 0, j = els.length; i < j; i++)
                    if (re.test(els[i].className))a.push(els[i]);
                return a;
            };
            videos = getElementsByClassName(document.body, "lazy-yt");
        } else {
            videos = document.getElementsByClassName("lazy-yt");
        }

        var nb_videos = videos.length;
        for (var i = 0; i < nb_videos; i++) {
            // Based on the YouTube ID, we can easily find the thumbnail image
            //videos[i].style.backgroundImage = 'url(http://i.ytimg.com/vi/' + videos[i].id + '/sddefault.jpg)';

            // Overlay the Play icon to make it look like a video player
            var play = document.createElement("span");
            play.setAttribute("class", "youtube-play fab fab-youtube");
            videos[i].appendChild(play);
        }

        document.querySelector('body').addEventListener('click', function(e){
            if(e.target.className.indexOf('youtube-play') > -1){
                // _this = figure.youtube
                var _this = e.target.parentNode;
                // Create an iFrame with autoplay set to true
                var iframe     = document.createElement("iframe");
                var iframe_url = "https://www.youtube.com/embed/" + _this.id + "?autoplay=1&modestbranding=1&rel=0";
                if (_this.getAttribute("data-params")) {
                    iframe_url += '&' + _this.getAttribute("data-params");
                }
                iframe.setAttribute("src", iframe_url);
                iframe.setAttribute("frameborder", '0');

                // The height and width of the iFrame should be the same as parent
                //iframe.style.width  = _this.style.width;
                //iframe.style.height = _this.style.height;

                // Replace the YouTube thumbnail with YouTube Player
                _this.parentNode.replaceChild(iframe, _this);
            }
        });
    }

    // Custom scrollbar
    $(window).load(function () {
        $('.meerkat-videowall').find('.content').mCustomScrollbar();
    });

    // cache container
    var $container = $meerkatVideowall.find('.items');

    // initialize isotope
    /*$container.imagesLoaded(function () {
        $container.isotope({
            itemSelector  : '.meerkat-videowall .item',
            layoutMode    : 'perfectMasonry',
            perfectMasonry: {
                layout : "vertical",      // Set layout as vertical/horizontal (default: vertical)
                //columnWidth: 375,        // Set/prefer specific column width (liquid layout tries to prefer said width)
                //rowHeight: 200,          // Set/prefer specific row height (liquid layout tries to prefer said height)
                liquid : true,            // Set layout as liquid (default: false)
                //cols: 3,                 // Force to have x columns (default: null)
                //rows: 3,                 // Force to have y rows (default: null)
                //minCols: 1,              // Set min col count (default: 1)
                //minRows: 3,              // Set min row count (default: 1)
                maxCols: 3              // Set max col count (default: 9999)
                //maxRows: 4               // Set max row count (default: 9999)
            }
        });
    });*/

    // filter items when filter link is clicked
    $('.filter a').click(function () {
        $('.current').removeClass('current');
        $(this).addClass('current');
        var selector = $(this).attr('data-filter');
        selector     = selector == '*' ? selector : '.' + selector;
        //$container.isotope({filter: selector + ', .intro'});
        return false;
    });

    doLazyLoad();
}(jQuery);
