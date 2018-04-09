(function ( $ ) {
    $( document ).ready( function () {
        if ( transcoding_status.load_flag ) {
            $( '[name="check_status_btn"]' ).live( 'click', function ( e ) {

                var post_id = $( this ).data( "value" );
                var btn_text = $( '#btn_check_status' + post_id ).text();

                $( '#span_status' + post_id ).html( '' );
                $( '#btn_check_status' + post_id ).html( 'Checking...' );
                $( '#span_status' + post_id ).hide();
                $( '#btn_check_status' + post_id ).prop( 'disabled', true );

                var data = {
                    action : 'checkstatus',
                    postid : post_id,
                    security : transcoding_status.security_nonce
                };

                jQuery.post( ajaxurl, data, function ( response ) {

                    var obj = jQuery.parseJSON( response.replace( /&quot;/g, '"' ) );

                    if ( obj["status"] === 'Success' )
                    {
                        $( '#btn_check_status' + post_id ).hide();
                    }

                    $( '#span_status' + post_id ).html( obj["message"] );
                    $( '#span_status' + post_id ).show();
                    $( '#btn_check_status' + post_id ).html( btn_text );
                    $( '#btn_check_status' + post_id ).prop( 'disabled', false );
                } );
            } );
        }
    } );
})( jQuery );
