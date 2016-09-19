jQuery(document).ready(function($){
    // Exit if not needed
    if(!$('.videowall-content').length) return;

    // Custom scrollbar
    $(window).load(function(){
        $('.videowall-content').mCustomScrollbar();
    });

    // Filters

    // cache container
    var $container = $('.videowall-items');
    // initialize isotope
    $container.imagesLoaded(function(){
        $container.isotope({
            itemSelector: '.videowall-item',
            layoutMode: 'perfectMasonry',
            perfectMasonry: {
                layout: "vertical",      // Set layout as vertical/horizontal (default: vertical)
                //columnWidth: 375,        // Set/prefer specific column width (liquid layout tries to prefer said width)
                //rowHeight: 200,          // Set/prefer specific row height (liquid layout tries to prefer said height)
                liquid: true,            // Set layout as liquid (default: false)
                //cols: 3,                 // Force to have x columns (default: null)
                //rows: 3,                 // Force to have y rows (default: null)
                //minCols: 1,              // Set min col count (default: 1)
                //minRows: 3,              // Set min row count (default: 1)
                maxCols: 3              // Set max col count (default: 9999)
                //maxRows: 4               // Set max row count (default: 9999)
            }
        });
    });

    // filter items when filter link is clicked
    $('.filter a').click(function(){
        $('.current').removeClass('current');
        $(this).addClass('current');
        var selector = $(this).attr('data-filter');
        selector = selector == '*' ? selector : '.' + selector;
        $container.isotope({ filter: selector + ', .videowall-intro' });
        return false;
    });
    
    // Video pops up in fancybox or featherlight
    var is_fancybox = typeof $.fancybox == 'function';
    var $videowallFancybox = $('.videowall-fancybox')
    if($videowallFancybox.length){
        if(is_fancybox) {
            $videowallFancybox.fancybox({
                type: 'iframe',
                beforeLoad: function () {
                    this.autoSize = Boolean(this.element.data('fancybox-autosize'));
                    if (!this.autoSize) {
                        this.width = parseInt(this.element.data('fancybox-width'));
                        this.height = parseInt(this.element.data('fancybox-height'));
                    }
                },
                padding: 2,
                openEffect: 'fade',
                closeEffect: 'fade',
                prevEffect: 'fade',
                nextEffect: 'fade',
                loop: false,
                arrows: true,
                helpers: {
                    media: {
                        youtube: {
                            params: {
                                wmode: 'opaque',
                                autoplay: 0, // 1 = will enable autoplay
                            }
                        }
                    },
                    title: {type: 'inside'}
                }
            });
        }else{
            $videowallFancybox.featherlight();
        }
    }
});
