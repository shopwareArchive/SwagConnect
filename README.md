# General behaviour

## Uninstall

 - when the plugin is uninstalled, all attribute fields will be preserved
 - all products imported from bepado will be de-activated
 - currently there is no way to force the plugin to uninstall all attribute fields



# FromShop
## The backend module
### Price configuration

Configuration->Prices allows to, which price field from which customer group should be exported as "price" to bepado and which price field from which customer group should be exported as "purchase price" to bepado.
This way the price management can be adjusted to the way, prices are managed within the shop.
Tiny shops might want to simply export the default customer group's prices to bepado, larger shop could create a own 'bepado' price group, for example.


# ToShop
## Dispatch
In order to exclude a given dispatch from bepado, configure a "bepado attribute" in the plugin configuration. By default this will be "attr19".

In Settings->Configuration->Storefront->Dispatch module set add the following string to "extended sql query":

, MAX(at.attr19) as bepado

Know edit the dispatches you want to exclude from bepado and set navigate to "Additional settings".

In the "own conditions" you can add

!bepado

## The backend module
### Changed products
The "changed products" view shows products, which have been changed by the supplier of the product but where the changes where not applied to the local products, as the toShop's owner configured the corresponding fields (e.g. price, name) to not be updated automatically.
The "changed products" view shows number, name and supplier of the products. Furthermore the "affected fields" are shown - these are the fields which have changed and where not updated automatically.
By selecting a row in this view, the toShop's owner can have a look at the actual changes and apply them to the affected product.

## Updating
 In the bepado plugin configuration, for prices, names, longDescription, shortDescription and images the update behaviour can be configured:

 - Always overwrite with the data from fromShop
 - Always manage by hand

This is the global configuration you can overwrite or inherit this configuration on per-product base

### Prices

- If the fromShop configured fixedPrices, prices will always be imported and cannot be modified in the local shop
- If the fromShop configured default prices, toShop can configure if prices should be overwritten or managed by hand

### Images 

- Manually added images will never be removed
- In "overwrite" mode, updates will always recreate all the bepado images which have changed since the last update
- In "non-overwrite" mode, images will imported only once at initial import

