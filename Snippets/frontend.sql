
INSERT IGNORE INTO `s_core_snippets` (`namespace`, `shopID`, `localeID`, `name`, `value`, `created`, `updated`) VALUES
('frontend/checkout/bepado', 1, 1, 'price_of_product__product_changed_to__price_', 'Der Preis von  %product hat sich auf %price geändert.', '2014-01-11 18:30:18', '2014-01-11 18:30:18'),
('frontend/checkout/bepado', 1, 1, 'availability_of_product__product_changed_to__availability_', 'Die Verfügbarkeit von %product hat sich auf %availability geändert.', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),
('frontend/checkout/bepado', 1, 1, 'frontend_checkout_cart_bepado_phone', 'Um diese Produkte zu bestellen, müssen Sie ihre Telefonnummer hinterlegen. Klicken Sie hier, um diese Änderung jetzt durchzuführen.', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),
('frontend/detail/bepado', 1, 1, 'detail_date_price_info', 'Preise {if $sOutputNet}zzgl.{else}inkl.{/if} gesetzlicher MwSt. <a title="Versandkosten" href="{url controller=custom sCustom=$shippingCostsPage}" style="text-decoration:underline">zzgl. Versandkosten</a>', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),
('frontend/checkout/bepado', 1, 2, 'price_of_product__product_changed_to__price_', 'Price of product %product changed to %price.', '2014-01-11 18:30:18', '2014-01-11 18:30:18'),
('frontend/checkout/bepado', 1, 2, 'availability_of_product__product_changed_to__availability_', 'Availability of product %product changed to %availability.', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),
('frontend/checkout/bepado', 1, 2, 'frontend_checkout_cart_bepado_phone', 'You need to leave your phone number in order to purchase these products. Click here in order to change your phone number now.', '2014-01-11 18:32:48', '2014-01-11 18:32:48'),
('frontend/detail/bepado', 1, 2, 'detail_date_price_info', 'Prices {if $sOutputNet} plus {else}incl.{/if} VAT <a title="shipping costs" href="{url controller=custom sCustom=$shippingCostsPage}" style="text-decoration:underline">plus shipping costs</a>', '2014-01-11 18:32:48', '2014-01-11 18:32:48');

