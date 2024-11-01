jQuery(document).ready(function($){
	$(document).on('click', '#viator-product-id', function(e){
		let productId = $.trim($(this).attr('data-product-id'));
		productIdLength = productId.length;
		if(productIdLength > 0){
			$.ajax({
				type: 'POST',
				url: viator_localize_admin_data.ajax_url,
				data: {action:"viatorwp_update_product_details_from_api_ajax", product_id:productId,vas_admin_security_nonce:viator_localize_admin_data.vas_admin_security_nonce},
				success: function(response){
					alert("Data updated successfully!!");
				}
			});
		}
	});


	//query form send starts here

    $(".viatorwp-send-query").on("click", function(e){
        e.preventDefault();   
        var message     = $("#viatorwp_query_message").val();  
        var email       = $("#viatorwp_query_email").val();  
        if($.trim(message) !='' && $.trim(email) !='' && viatorwpIsEmail(email) == true){
         	$.ajax({
                type: "POST",    
                url:ajaxurl,                    
                dataType: "json",
                data:{action:"viatorwp_send_query_message", message:message,email:email, viatorwp_setting_security_nonce:viator_localize_admin_data.vas_admin_security_nonce},
                success:function(response){                       
                  if(response['status'] =='t'){
                    $(".viatorwp-query-success").show();
                    $(".viatorwp-query-error").hide();
                  }else{                                  
                    $(".viatorwp-query-success").hide();  
                    $(".viatorwp-query-error").show();
                  }
                },
                error: function(response){                    
                	console.log(response);
                }
            });   
        }else{
            
            if($.trim(message) =='' && $.trim(email) ==''){
                alert('Please enter the message, email');
            }else{
	            if($.trim(message) == ''){
	                alert('Please enter the message');
	            }
	            if($.trim(email) == ''){
	                alert('Please enter the email');
	            }
	            if(viatorwpIsEmail(email) == false){
	                alert('Please enter a valid email');
	            }
            }
        }                        
    });

    /* Newletters js starts here */      
            
        if(viator_localize_admin_data.do_tour){
            flag = 0;
            let  content = '<h3>Thanks for using Viator Integration For WP!</h3>';
                 content += '<p>Do you want the latest updates on <b>Viator Integration For WP update</b> before others and some best resources on monetization in a single email? - Free just for users of Viator Integration For WP!</p>';
                 content += '<style type="text/css">';
                 content += '.wp-pointer-buttons{ padding:0; overflow: hidden; }';
                 content += '.wp-pointer-content .button-secondary{  left: -25px;background: transparent;top: 5px; border: 0;position: relative; padding: 0; box-shadow: none;margin: 0;color: #0085ba;} .wp-pointer-content .button-primary{ display:none}  #viatorwp_mc_embed_signup{background:#fff; clear:left; font:14px Helvetica,Arial,sans-serif; }';
                 content += '</style>';                        
                 content += '<div id="viatorwp_mc_embed_signup">';
                 content += '<form method="POST" accept-charset="utf-8" id="viatorwp-news-letter-form">';
                 content += '<div id="viatorwp_mc_embed_signup_scroll">';
                 content += '<div class="viatorwp-mc-field-group" style="    margin-left: 15px;    width: 195px;    float: left;">';
                 content += '<input type="text" name="viatorwp_subscriber_name" class="form-control" placeholder="Name" hidden value="'+viator_localize_admin_data.current_user_name+'" style="display:none">';
                 content += '<input type="text" value="'+viator_localize_admin_data.current_user_email+'" id="viatorwp_subscriber_email" name="viatorwp_subscriber_email" class="form-control" placeholder="Email*"  style="      width: 180px;    padding: 6px 5px;">';                        
                 content += '<input type="text" name="viatorwp_subscriber_website" class="form-control" placeholder="Website" hidden style=" display:none; width: 168px; padding: 6px 5px;" value="'+viator_localize_admin_data.get_home_url+'">';
                 content += '<input type="hidden" name="ml-submit" value="1" />';
                 content += '</div>';
                 content += '<div id="mce-responses">';                                                
                 content += '</div>';
                 content += '<div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="b_a631df13442f19caede5a5baf_c9a71edce6" tabindex="-1" value=""></div>';
                 content += '<input type="submit" value="Subscribe" name="subscribe" id="pointer-close" class="button mc-newsletter-sent" style=" background: #0085ba; border-color: #006799; padding: 0px 16px; text-shadow: 0 -1px 1px #006799,1px 0 1px #006799,0 1px 1px #006799,-1px 0 1px #006799; height: 30px; margin-top: 1px; color: #fff; box-shadow: 0 1px 0 #006799;">';
                 content += '<p id="viatorwp-news-letter-status"></p>';
                 content += '</div>';
                 content += '</form>';
                 content += '</div>';
                 
                 $(document).on("submit", "#viatorwp-news-letter-form", function(e){
                   e.preventDefault(); 
                   
                   var $form = $(this),
                   name = $form.find('input[name="viatorwp_subscriber_name"]').val(),
                   email = $form.find('input[name="viatorwp_subscriber_email"]').val();
                   website = $form.find('input[name="viatorwp_subscriber_website"]').val();

                    if($.trim(email) !='' && viatorwpIsEmail(email) == true){    
                        flag = 1;                    
                       $.post(viator_localize_admin_data.ajax_url,
                                  {action:'viatorwp_subscribe_to_news_letter',
                                  viatorwp_newsletter_security_nonce:viator_localize_admin_data.vas_admin_security_nonce,
                                  name:name, email:email, website:website },
                         function(data) {
                           
                             if(data)
                             {
                               if(data=="Some fields are missing.")
                               {
                                 $("#viatorwp-news-letter-status").text("");
                                 $("#viatorwp-news-letter-status").css("color", "red");
                               }
                               else if(data=="Invalid email address.")
                               {
                                 $("#viatorwp-news-letter-status").text("");
                                 $("#viatorwp-news-letter-status").css("color", "red");
                               }
                               else if(data=="Invalid list ID.")
                               {
                                 $("#viatorwp-news-letter-status").text("");
                                 $("#viatorwp-news-letter-status").css("color", "red");
                               }
                               else if(data=="Already subscribed.")
                               {
                                 $("#viatorwp-news-letter-status").text("You are already Subscribed");
                                 $("#viatorwp-news-letter-status").css("color", "red");
                               }
                               else
                               {
                                 $("#viatorwp-news-letter-status").text("You're subscribed!");
                                 $("#viatorwp-news-letter-status").css("color", "green");
                               }
                             }
                             else
                             {
                               alert("Sorry, unable to subscribe. Please try again later!");
                             }
                         }
                       );
                    }else{
                        $('#viatorwp_subscriber_email').focus();
                        $('#viatorwp-news-letter-status').html('<strong style="color: #FF0000">Please enter valid email id</strong>');
                        return false;
                    }
                 });

         
         var setup;                
         var wp_pointers_tour_opts = {
             content:content,
             position:{
                 edge:"left",
                 align:"left"
             }
         };
                         
         wp_pointers_tour_opts = $.extend (wp_pointers_tour_opts, {
                 buttons: function (event, t) {
                         button= jQuery ('<a id="pointer-close" class="button-secondary">' + viator_localize_admin_data.button1 + '</a>');
                         button_2= jQuery ('#pointer-close.button');
                         button.bind ('click.pointer', function () {
                            // if(flag == 1){
                                 t.element.pointer ('close');
                            // }
                         });
                         button_2.on('click', function() {
                           setTimeout(function(){ 
                               // if(flag == 1){
                                t.element.pointer ('close');
                               // }
                          }, 3000);
                               
                         } );
                         return button;
                 },
                 close: function () {
                         $.post (viator_localize_admin_data.ajax_url, {
                                 pointer: 'viatorwp_subscribe_pointer',
                                 action: 'dismiss-wp-pointer'
                         });
                 },
                 show: function(event, t){
                  t.pointer.css({'left':'170px', 'top':'160px'});
               }                                               
         });
         setup = function () {
                 $('#toplevel_page_viator-integration-for-wp').pointer(wp_pointers_tour_opts).pointer('open');
                  if (viator_localize_admin_data.button2) {
                         jQuery ('#pointer-close').after ('<a id="pointer-primary" class="button-primary">' + viator_localize_admin_data.button2+ '</a>');
                         jQuery ('#pointer-primary').click (function () {
                                 viator_localize_admin_data.function_name;
                         });
                         jQuery ('#pointer-close').click (function () {
                                 $.post (viator_localize_admin_data.ajax_url, {
                                         pointer: 'viatorwp_subscribe_pointer',
                                         action: 'dismiss-wp-pointer'
                                 });
                         });
                  }
         };
         if (wp_pointers_tour_opts.position && wp_pointers_tour_opts.position.defer_loading) {
                 $(window).bind('load.wp-pointers', setup);
         }
         else {
                 setup ();
         }
         
     }
    /* Newletters js ends here */ 

});


function viatorwpIsEmail(email) {
    var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    return regex.test(email);
}