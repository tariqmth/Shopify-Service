// Removes shipping rates from DOM based on fulfilment method selected on cart page
function ClickAndCollectCheckout(fulfilmentMethod,storeName) {
  
  // Name of Store Pickup/Click and Collect shipping rate configured in store settings
  var cncRate = 'Picking up in store';

  // Only execute on shipping method page
  if ( Shopify.Checkout.step == 'shipping_method' ) {
    
    // Check shipping rates have loaded
    if ($('[name="checkout[shipping_rate][id]"]').length == 0) {
	    setTimeout(function () { ClickAndCollectCheckout(fulfilmentMethod,storeName); }, 500);
    }
    
    // If Store Pickup/Click and Collect chosen, remove all other rates and append store name to label
    // Else remove the Click and Collect method and set new default rate
    if ( fulfilmentMethod == 'store' ) {

      $('.radio-wrapper:not([data-shipping-method*="'+encodeURIComponent(cncRate)+'"])').parent().remove();
      $('[data-shipping-method-label-title="'+cncRate+'"]').html($('[data-shipping-method-label-title="'+cncRate+'"]').html().concat(' (',storeName,')'));

    } else {

      $('[data-shipping-method*="'+encodeURIComponent(cncRate)+'"]').parent().remove();      
      $('[id*="checkout_shipping_rate"]:first').prop('checked', true);

    }

  }

}