# FromShop


# ToShop
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

