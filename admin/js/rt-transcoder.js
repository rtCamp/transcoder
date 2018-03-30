(function( $ ) {
    $( document ).ready( function() {
        if(object_status.load_flag){
            $('[name="check_status_btn"]').live('click', function (e) {                    
                var post_id =  $(this).data("value");
                var btn_text = document.getElementById('btn_check_status'+post_id).innerHTML;
                $('#span_status'+post_id).html('');
                $('#btn_check_status'+post_id).html('Checking...');
                $('#span_status'+post_id).hide();
                $('#btn_check_status'+post_id).prop('disabled', true);
                var data = {
                        'action': 'checkstatus',
                        'postid': post_id,
                        security: object_status.security_nonce
                };		
                jQuery.post(ajaxurl, data, function(response) {
                    var obj = jQuery.parseJSON(response.replace(/&quot;/g,'"'));
                        if( obj["status"] == 'Your file is transcoded successfully. Please refresh the page.' )
                        {
                                $('#btn_check_status'+post_id).hide();
                        }
                        $('#span_status'+post_id).html(obj["status"]);
                        $('#span_status'+post_id).show();
                        $('#btn_check_status'+post_id).html(btn_text);
                        $('#btn_check_status'+post_id).prop('disabled', false);
                });
            });   
        }
    } );
})( jQuery );
