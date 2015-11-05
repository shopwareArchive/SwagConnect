jQuery(document).bind('DOMNodeInserted', function(){
    // this file is included only when connect product is in basket
    // if class .modal_paypal_button or .basket_bottom_paypal exists
    // hide the button
    if(jQuery('.modal_paypal_button').length > 0)
    {
        jQuery('.modal_paypal_button').hide();
    }

    if(jQuery('.basket_bottom_paypal').length > 0)
    {
        jQuery('.basket_bottom_paypal').hide();
    }
});