# Click and Collect

Code will either be included in this document or in the other files in this directory.

The following files in Shopify need to be created or updated.

##layout/theme.liquid

Ensure the header contains jQuery. This may already be implemented.

```html
{{ '//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js' | script_tag }}
```

Add the following to the end of the header:

```html
{% include 'click-and-collect' %}
```

##snippets/click-and-collect.liquid

Copy click-and-collect.liquid to the Snippets folder.

If you are using a non-production environment, set apiPrefix to 'dev-shopify.rextest.net' or 'test-shopify.rextest.net'. Otherwise leave as 'shopify.retailexpress.com.au'.

Set showTables and showQuantity to change how much information is displayed in the cart screen.

If you have a Google Maps API key set up and wish to enable Maps on the cart page, set enableMap to true and replace YOUR_API_KEY in the link to the Google Maps script. You will also need to enter this information in the back office of Retail Express and ensure your outlets have been synced to the Shopify connector with accurate address information. Please make sure your API key supports the Maps Javascript API and Geocoding API.

If you are not using Google Maps, you should ensure the link to the Google Maps script is removed.

If you are using the ES5 version of the script, replace the link to 'click-and-collect-es6.js' with 'click-and-collect-es5-min.js' (see below). You also need to uncomment the polyfill script for backwards compatibility.

##assets/click-and-collect-es6.js

The Click and Collect script is written in Javascript ES6 (2015). It is not compatible with Internet Explorer 11 or earlier. If you would like IE compatibility, an ES5 version of the script has been created and minified for you. You can also do this yourself, using Babel (https://babeljs.io/).

Copy the appropriate script (click-and-collect-es6.js or click-and-collect-es5-min.js) to the Assets folder and make sure to check that the file name matches what is entered in "snippets/click-and-collect.liquid".

##assets/click-and-collect.scss

Copy click-and-collect.scss to the Assets folder.

If you would like to have the availability table and store information side by side for larger displays, set $multi-column to true.

##templates/cart-template.liquid and templates/product-template.liquid

Place the following code where you want the pickup locations to appear. This is usually following the shipping element (class "cart__shipping") on the cart page. On the product page, this is typically following the "add to cart" button, at the end of the "product-single__meta" div element.

```html
<div id="cnc-container"></div>
<div id="cnc-results-container"></div>
```

If you are using Google maps on the cart page, you should use the following code instead.

```html
<div id="cnc-container"></div>
<div id="cnc-map-container"></div>
<div id="cnc-results-container"></div>
```

##Customise checkout in Shopify Plus

For customers with Shopify Plus stores, minor changes can be made to the checkout theme files. The below will hide shipping rates based on the fulfilment method selected on the cart page i.e. when home delivery ise selected, hide the pickup method. When store pickup is selected, hide all delivery methods.

Copy click-and-collect-checkout.js to the assets folder

In this file there is a variable called cncRate that must be set to the name of the shipping rate that has been setup in the store settings e.g. "Picking up in store"

Place the following code inside the ```<head></head>``` tag of checkout.liquid (only available in Plus stores and must be requested through the customers Shopify Plus account manager):

```html
{{ 'click-and-collect-checkout.js' | asset_url | script_tag }}
<script type="text/javascript">
  document.addEventListener("DOMContentLoaded", function(event) {
    ClickAndCollectCheckout('{{ checkout.attributes.cnc-fulfilment-method }}','{{ checkout.attributes.cnc-store-name }}');
  });
</script>
```