# Product ETA

The following files in Shopify need to be created or updated.

##layout/theme.liquid

Ensure the header contains jQuery. This may already be implemented.

```html
{{ '//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js' | script_tag }}
```

Add the following to the end of the header:

```html
{% include 'rex-product-eta' %}
```

##snippets/rex-product-eta.liquid

Copy rex-product-eta.liquid to the Snippets folder.

##assets/rex-product-eta.js

Copy rex-product-eta.js to the Assets folder.

##assets/click-and-collect.scss

Copy rex-product-eta.css to the Assets folder.

# Configure settings in rex-product-eta.js

Edit the rex-product-eta.js file and at the top of the file in the constructor update the following:

- this.clientName = the prefix of the Retail Express URL in camel case (minus the env for non-production) e.g. for uat-borexuat.rextest.net the client name is BORexUAT
- this.shopName = the prefix of the Shopify store e.g. for uat-borex-3.myshopify.com the shop name is uat-borex-3

*NOTE: these parameters will be removed prior to full release as we are still making changes to the code to not require them*