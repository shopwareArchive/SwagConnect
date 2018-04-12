
INSERT IGNORE INTO `s_core_snippets` (`namespace`, `shopID`, `localeID`, `name`, `value`, `created`, `updated`) VALUES
('frontend/checkout/connect', 1, 1, 'frontend_checkout_cart_connect_phone', 'Um diese Produkte zu bestellen, müssen Sie ihre Telefonnummer hinterlegen. Klicken Sie hier, um diese Änderung jetzt durchzuführen.', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),
('frontend/checkout/connect', 1, 1, 'frontend_checkout_cart_connect_not_shippable', 'Ihre Bestellung kann nicht geliefert werden. Der Händler %supplierName liefert nicht in Dein Land.', '2014-04-22 17:29:48', '2014-04-22 17:29:48'),
('frontend/checkout/connect', 1, 1, 'frontend_checkout_cart_connect_payment_not_allowed', 'Die ausgewählte Zahlungsmethode steht für Marktplatzprodukte nicht zur Verfügung. Bitte ändern Sie diese.', '2014-04-22 17:29:48', '2014-04-22 17:29:48'),
('frontend/search/connect', 1, 1, 'SearchHeading', 'Zu "{$searchQuery|escape}" wurden in diesem Shop keine Produkte gefunden,<br> eventuell sind die Produkte unserer Partnershops für Sie interessant.', '2014-04-25 11:00:00', '2014-04-25 11:00:00'),
('frontend/search/connect', 1, 1, 'SearchHeadingEmpty', 'Leider wurden zu "{$searchQuery|escape}" keine Artikel gefunden', '2014-04-25 11:00:00', '2014-04-25 11:00:00'),
('frontend/checkout/connect', 1, 2, 'price_of_product__product_changed_to__price_', 'Price of product %product changed to %price.', '2014-01-11 18:30:18', '2014-01-11 18:30:18'),
('frontend/checkout/connect', 1, 2, 'frontend_checkout_cart_connect_phone', 'You need to leave your phone number in order to purchase these products. Click here in order to change your phone number now.', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),
('frontend/checkout/connect', 1, 2, 'frontend_checkout_cart_connect_not_shippable', 'Your order can not be delivered. %supplierName does not deliver into your country.', '2014-04-22 17:29:48', '2014-04-22 17:29:48'),
('frontend/checkout/connect', 1, 2, 'frontend_checkout_cart_connect_payment_not_allowed', 'Selected payment method is not allowed with connect products. Please change it.', '2014-04-22 17:29:48', '2014-04-22 17:29:48'),
('frontend/search/connect', 1, 2, 'SearchHeading', 'No results have been found for "{$searchQuery|escape}",<br> but maybe these products in our partner shops are interesting for you.', '2014-04-25 11:00:00', '2014-04-25 11:00:00'),
('frontend/search/connect', 1, 2, 'SearchHeadingEmpty', 'No results have been found for "{$searchQuery|escape}"', '2014-04-25 11:00:00', '2014-04-25 11:00:00'),

('frontend/checkout/connect', 1, 1, 'frontend_checkout_cart_connect_phone', 'Um diese Produkte zu bestellen, müssen Sie ihre Telefonnummer hinterlegen. Klicken Sie hier, um diese Änderung jetzt durchzuführen.', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),
('frontend/checkout/connect', 1, 2, 'frontend_checkout_cart_connect_phone', 'You need to leave your phone number in order to purchase these products. Click here in order to change your phone number now.', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),

('frontend/detail/connect', 1, 2, 'connect_detail_marketplace_article', 'Article from marketplace {$connectShop->name}', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),
('frontend/detail/connect', 1, 2, 'connect_detail_marketplace_article_implicit', 'Article from storage {$connectShop->id}', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),
('frontend/detail/connect', 1, 1, 'connect_detail_marketplace_article', 'Marktplatz-Artikel von {$connectShop->name}', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),
('frontend/detail/connect', 1, 1, 'connect_detail_marketplace_article_implicit', 'Artikel aus Lager {$connectShop->id}', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),

('frontend/connect/shipping_costs', 1, 1, 'connect_storage_dispatch', 'Lagerversand', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),
('frontend/connect/shipping_costs', 1, 2, 'connect_storage_dispatch', 'Shipping from storage', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),

('frontend/connect/shipping_costs', 1, 1, 'connect_dispatch_shop_name', 'Versand von »{$item.shopInfo.name}«', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),
('frontend/connect/shipping_costs', 1, 2, 'connect_dispatch_shop_name', 'Shipping from »{$item.shopInfo.name}«', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),

('frontend/connect/shipping_costs', 1, 1, 'connect_dispatch_shop_id', 'Versand für Lager {$item.shopInfo.id}', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),
('frontend/connect/shipping_costs', 1, 2, 'connect_dispatch_shop_id', 'Shipping from storage {$item.shopInfo.id}', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),

('frontend/checkout/connect', 1, 1, 'due_to_technical_reasons__product__product_is_not_available_', 'Bestellung von Artikel %product kann aus technischen Gründen nicht abgeschlossen werden', '2016-10-07 18:32:48', '2016-10-07 18:32:48'),
('frontend/checkout/connect', 1, 2, 'due_to_technical_reasons__product__product_is_not_available_', 'Due to technical reasons, product %product is not available.', '2016-10-07 18:32:48', '2016-10-07 18:32:48'),

('frontend/connect/shipping_costs', 1, 2, 'connect_dispatch_tax_info', 'Tax rate for gross prices might be smaller', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),
('frontend/connect/shipping_costs', 1, 2, 'connect_dispatch_country_label', 'Dispatch by country', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),
('frontend/connect/shipping_costs', 1, 2, 'connect_dispatch_country_column_header', 'Country', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),
('frontend/connect/shipping_costs', 1, 2, 'connect_dispatch_weight_label', 'Dispatch by weight', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),
('frontend/connect/shipping_costs', 1, 2, 'connect_dispatch_country_column_header', 'max weight', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),
('frontend/connect/shipping_costs', 1, 2, 'connect_dispatch_net_price', 'net price', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),
('frontend/connect/shipping_costs', 1, 2, 'connect_dispatch_gross_price', 'gross price', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),

('frontend/checkout/error_messages', 1, 1, 'connect_product_out_of_stock_message', 'Das Product "%ptitle" in Ihrer Bestellung ist aktuell nicht lieferbar, bitte entfernen Sie das Produkt um fortzufahren.', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),
('frontend/checkout/error_messages', 1, 2, 'connect_product_out_of_stock_message', 'Product "%ptitle" in your order is currently out of stock. delete the product in your order to continue', '2017-04-11 15:36:48', '2017-04-11 15:36:48'),

('frontend/checkout/error_messages', 1, 1, 'connect_product_lower_stock_message', 'Der Lagerbestand von Produkt "%ptitle" hat sich auf %availability geändert', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),
('frontend/checkout/error_messages', 1, 2, 'connect_product_lower_stock_message', 'Availability of product "%ptitle" changed to %availability', '2017-04-11 15:36:48', '2017-04-11 15:36:48'),


('frontend/checkout/connect', 1, 1, 'delivery_address_empty', 'Die Lieferadresse kann nicht leer sein', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),
('frontend/checkout/connect', 1, 2, 'delivery_address_empty', 'Delivery address could not be empty', '2017-04-11 15:36:48', '2017-04-11 15:36:48'),

('frontend/checkout/connect', 1, 1, 'order_not_shippable_to_country', 'Die Bestellung kann nicht nach %country geliefert werden', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),
('frontend/checkout/connect', 1, 2, 'order_not_shippable_to_country', 'Order could not be shipped to %country', '2017-04-11 15:36:48', '2017-04-11 15:36:48'),

('frontend/checkout/connect', 1, 1, 'default_shop_not_found', 'Standardshop konnte nicht gefunden werden', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),
('frontend/checkout/connect', 1, 2, 'default_shop_not_found', 'Default shop could not be found', '2017-04-11 15:36:48', '2017-04-11 15:36:48'),

('frontend/checkout/connect', 1, 1, 'default_shipping_not_found', 'Standardversand konnte nicht gefunden werden', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),
('frontend/checkout/connect', 1, 2, 'default_shipping_not_found', 'Default shipping could not be found', '2017-04-11 15:36:48', '2017-04-11 15:36:48'),

('frontend/checkout/connect', 1, 1, 'checkout_not_possible', 'Der Checkout ist zur Zeit nicht möglich, mögliche Gründe sind: Produkt ist inaktiv, entfernt oder ähnliches', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),
('frontend/checkout/connect', 1, 2, 'checkout_not_possible', 'Checkout is not possible at the moment possible reasons are: inactive product, removed product, etc.', '2017-04-11 15:36:48', '2017-04-11 15:36:48')

ON DUPLICATE KEY UPDATE
  `namespace` = VALUES(`namespace`),
  `shopID` = VALUES(`shopID`),
  `name` = VALUES(`name`),
  `localeID` = VALUES(`localeID`),
  `value` = VALUES(`value`)
;
