[![Build Status](https://travis-ci.org/shopware/SwagConnect.svg?branch=master)](https://travis-ci.org/shopware/SwagConnect)
[![Coverage Status](https://coveralls.io/repos/github/shopware/SwagConnect/badge.svg?branch=master)](https://coveralls.io/github/shopware/SwagConnect?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/shopware/SwagConnect/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/shopware/SwagConnect/?branch=master)

# Development

## Installation

1. Clone this repository
2. Run `$ composer install`
3. Move to `vendor/shopware/plugin-dev-tools` and execute `$ ./install.sh`

## Running tests

The test suite relies on the [plugin-dev-tools](https://github.com/shopwareLabs/plugin-dev-tools).

1. Run `$ ./psh local:init` to initialize a testing environment on your local system.
2. Run `$ ./psh local:unit` to execute all tests or `$ ./psh local:unit-coverage` to generate the code-coverage

### Run tests without psh

`$ export SHOPWARE_ENV=swagconnecttest`
`$ ../../../../../../vendor/bin/phpunit`

# General behaviour

## Uninstall

 - when the plugin is uninstalled, all attribute fields will be preserved
 - all products imported from shopware Connect will be de-activated
 - currently there is no way to force the plugin to uninstall all attribute fields

# The backend module

## Configuration
### General
* Api Key: Your shopware Connect API key. Without this key, no imports and exports are possible
* Enable Cloud Search: If a search in your local shop does not return any results, also search the shopware Connect cloud. Needs to be enabled for your account
* Show shopware Connect hint on detail page: Show a hint that this is a shopware Connect product.
* Shop shopware Connect hint during checkout: Show a hint that the current product is a shopware Connect product
* Set "noindex" meta tag: Set "noindex" tag for shopware Connect products so that search engines don't find duplicate content with on other shops with the same product
* shopware Connect attribute: shopware Connect will store the product's remote ID in this attribute, if it is a shopware Connect product. Useful for risk managment and other modules only supporting the default attributes
* Alternate shopware Connect host: Developer only: Use another host than "bepado.de"
* Enable logging: Write logs for all operations
### Import
* Overwrite fields during product update: Configure, which fields are automatically update and which you want to update manually
* Import images during product's first import: Always import images, even if it might slow down the importing
### Export
* Product description field: Field to export the long description from. Useful if you don't want to expose your SEO optimized texts to shopware Connect.
* Automatically sync changes to shopware Connect: Whenever a local shopware Connect product is changed, it is automatically synced to shopware Connect.
* User price: Where should shopware Connect read the end user's price from
* Merchang price: Where should shopware Connect read the merchant's price from


## Category mapping
### Import
Map remote shopware Connect categories to your local shopware categories. When a new product is being imported from shopware Connect, it will get the corresponding shopware category assigned
### Export
Map local shopware categories to shopware Connect categories. When being exported the product will get the configured shopware Connect category assigned
## Products
### Import
Overview of products imported from shopware Connect. Here you can
 * open product for edit
 * enable product for local shop
 * disable product for local sho
 * assign product to local sw category
 * enable/disable grouping products by shopware Connect category
### Export
Overview of products exported to shopware Connect. Here you can
 * export products to shopware Connect
 * update already exported products
 * remove products from export
 * open local product for editing
### Recent changes
Usefull for imported products. Will show remote changes, which have not been applied, yet. Here you can manually apply changed prices / images / descriptions.
## Log
A technical overview of shopware Connect requests. Usefull for debugging if you are not sure, if a product was actually exported to shopware Connect or not. Will automatically be cleared every few days.



# FromShop
## The backend module

...

# ToShop
## Dispatch
By default any dispatch can be used with shopware Connect. You might want to disable some dispatches, however. In order to do so, just open Settings->Shipping and edit the shipping type you want to disable.
In the "advanced configuration" you can now uncheck "Allow with shopware Connect".

## Updating
In the shopware Connect plugin configuration, for prices, names, longDescription, shortDescription and images the update behaviour can be configured:

 - Always overwrite with the data from fromShop
 - Always manage by hand

This is the global configuration you can overwrite or inherit this configuration on per-product base

### Importing images
Images can be directly imported whenever a new product is transfered to the shop. This will, however, massively slow down the actual import.
shopware Connect will automatically register a CronJob so images can automatically imported via the default shopware cronjob. In order to test this, you can just call http://www.your-shop.com/backend/cron.

### Prices

- If the fromShop configured fixedPrices, prices will always be imported and cannot be modified in the local shop
- If the fromShop configured default prices, toShop can configure if prices should be overwritten or managed by hand

### Images 

- Manually added images will never be removed
- In "overwrite" mode, updates will always recreate all the shopware Connect images which have changed since the last update
- In "non-overwrite" mode, images will imported only once at initial import

# Manipulating and extending the plugin

Overwriting parts of the plugin will make the plugin unable to be updated, so it is best practice to use a seperate custom plugin, that makes use of events and hooks, to extend the Shopware Connect Plugin. We are using the same kind of events as Shopware, so you can define listeners in the exact same way.

## Events

The SDK is accessed by RPC calls over a single route, this is handled by the Connect Gateway. We will throw events before the request is handled, and after it has been handled. These are the two event names:

- 'Shopware_Connect_SDK_Handle_Before'
- 'Shopware_Connect_SDK_Handle_After'

Both events will receive the request as their first parameter. The request contains the XML document that is used by the RPC call. It contains the service and the command that will be executed. You can find the available services and commands in the [DependencyResolver in the Plugin Library](https://github.com/shopware/SwagConnect/blob/master/Library/Shopware/Connect/DependencyResolver.php#L267).

The After Event will also contain the response that will be sent back to Connect. The Events will be fired on any time Connect calls the shop for any function.