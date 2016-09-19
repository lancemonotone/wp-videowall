jQuery(document).ready(function(){
    jQuery('.videowall-playlist-id').change(function(){
        var form = jQuery(this).parents('form');
        var videowall_data = {
            action        : 'get_playlist_tags',
            playlist_id   : jQuery(this).val(),
            username      : jQuery('input[id$="username"]',form).val(),
            password      : jQuery('input[id$="password"]',form).val()
        };
        jQuery.post(videowallAjax.ajaxurl, videowall_data, function(response) {
            alert('Got this from the server: ' + response);
        });
    });
    jQuery('.videowall-hide-intro:checked').parent().next('fieldset').hide(); 
    jQuery('.videowall-hide-intro').change(function(){
        jQuery(this).parent().next('fieldset').toggle(!this.checked);
    })
});