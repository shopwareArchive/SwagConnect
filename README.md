# General behaviour

## Uninstall

 - when the plugin is uninstalled, all attribute fields will be preserved
 - all products imported from bepado will be de-activated
 - currently there is no way to force the plugin to uninstall all attribute fields



# FromShop


# ToShop
## The backend module
### Changed products
The "changed products" view shows products, which have been changed by the supplier of the product but where the changes where not applied to the local products, as the toShop's owner configured the corresponding fields (e.g. price, name) to not be updated automatically.
The "changed products" view shows number, name and supplier of the products. Furthermore the "affected fields" are shown - these are the fields which have changed and where not updated automatically.
By selecting a row in this view, the toShop's owner can have a look at the actual changes.

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


