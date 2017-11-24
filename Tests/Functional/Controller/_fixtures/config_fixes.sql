REPLACE INTO `sw_connect_shop_config` VALUES ('_price_type', 2, '2017-10-11 12:12:12');
UPDATE s_plugin_connect_config SET value = 'price' WHERE name = 'priceFieldForPriceExport';
UPDATE s_plugin_connect_config SET value = 'EK' WHERE name = 'priceGroupForPriceExport';
UPDATE s_filter SET sortmode = 0;