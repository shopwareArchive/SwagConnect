# General behaviour

## Uninstall

 - when the plugin is uninstalled, all attribute fields will be preserved
 - all products imported from bepado will be de-activated
 - currently there is no way to force the plugin to uninstall all attribute fields

# The backend module

## Configuration
### General
* Api Key: Your bepado API key. Without this key, no imports and exports are possible
* Enable Cloud Search: If a search in your local shop does not return any results, also search the bepado cloud. Needs to be enabled for your account
* Show bepado hint on detail page: Show a hint that this is a bepado product.
* Shop bepado hint during checkout: Show a hint that the current product is a bepado product
* Set "noindex" meta tag: Set "noindex" tag for bepado products so that search engines don't find duplicate content with on other shops with the same product
* bepado attribute: bepado will store the product's remote ID in this attribute, if it is a bepado product. Useful for riskmanagment and other modules only supporting the default attributes
* Alternate bepado host: Developer only: Use another host than "bepado.de"
* Enable logging: Write logs for all operations
### Import
* Overwrite fields during product update: Configure, which fields are automatically update and which you want to update manually
* Import images during product's first import: Always import images, even if it might slow down the importing
### Export
* Product description field: Field to export the long description from. Useful if you don't want to expose your SEO optimized texts to bepado.
* Automatically sync changes to bepado: Whenever a local bepado product is changed, it is automatically synced to bepado.
* User price: Where should bepado read the end user's price from
* Merchang price: Where should bepado read the merchant's price from


## Category mapping
### Import
Map remote bepado categories to your local shopware categories. When a new product is being imported from bepado, it will get the corresponding shopware category assigned
### Export
Map local shopware categories to bepado categories. When being exported the product will get the configured bepado category assigned
## Products
### Import
Overview of products imported from bepado. Here you can
 * open product for edit
 * enable product for local shop
 * disable product for local sho
 * assign product to local sw category
 * enable/disable grouping products by bepado category
### Export
Overview of products exported to bepado. Here you can
 * export products to bepado
 * update already exported products
 * remove products from export
 * open local product for editing
### Recent changes
Usefull for imported products. Will show remote changes, which have not been applied, yet. Here you can manually apply changed prices / images / descriptions.
## Log
A technical overview of bepado requests. Usefull for debugging if you are not sure, if a product was actually exported to bepado or not. Will automatically be cleared every few days.



# FromShop
## The backend module

...

# ToShop
## Dispatch
By default any dispatch can be used with bepado. You might want to disable some dispatches, however. In order to do so, just open Settings->Shipping and edit the shipping type you want to disable.
In the "advanced configuration" you can now uncheck "Allow with bepado".

## Updating
In the bepado plugin configuration, for prices, names, longDescription, shortDescription and images the update behaviour can be configured:

 - Always overwrite with the data from fromShop
 - Always manage by hand

This is the global configuration you can overwrite or inherit this configuration on per-product base

### Importing images
Images can be directly imported whenever a new product is transfered to the shop. This will, however, massively slow down the actual import.
Bepado will automatically register a CronJob so images can automatically imported via the default shopware cronjob. In order to test this, you can just call http://www.your-shop.com/backend/cron.

### Prices

- If the fromShop configured fixedPrices, prices will always be imported and cannot be modified in the local shop
- If the fromShop configured default prices, toShop can configure if prices should be overwritten or managed by hand

### Images 

- Manually added images will never be removed
- In "overwrite" mode, updates will always recreate all the bepado images which have changed since the last update
- In "non-overwrite" mode, images will imported only once at initial import

