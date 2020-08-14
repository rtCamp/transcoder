/*! 
 * Transcoder Library 
 * @package Transcoder 
 */

!function(a){a(document).ready(function(){transcoding_status.load_flag&&a('[name="check_status_btn"]').click(function(b){var c=a(this).data("value"),d=a("#btn_check_status"+c).text(),e=a("#span_status"+c),f=a("#btn_check_status"+c);e.text(""),f.text("Checking..."),e.hide(),f.prop("disabled",!0);var g={action:"checkstatus",postid:c,security:transcoding_status.security_nonce};jQuery.post(ajaxurl,g,function(a){var b=jQuery.parseJSON(a.replace(/&quot;/g,'"'));"Success"===b.status&&f.hide(),e.text(b.message),e.show(),f.text(d),f.prop("disabled",!1)})})})}(jQuery);