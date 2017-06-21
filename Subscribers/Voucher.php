<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Subscribers;

/**
 * Handle vouchers, remove discounts and don't allow percentaged vouchers for connect baskets
 *
 * Class Voucher
 */
class Voucher extends BaseSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Basket_AddVoucher_Start' => 'preventPercentagedVoucher',
            'Shopware_Modules_Basket_GetBasket_FilterSQL' => 'removeDiscount',
        ];
    }

    /**
     * Helper method to remove percentaged discounts from the basket if connect products are available
     *
     * Alternative:
     *  * Calculate discount only for the default products
     *  * Removed percentaged discounts only if connect product has a fixedPrice
     *
     * @event Shopware_Modules_Basket_GetBasket_FilterSQL
     */
    public function removeDiscount(\Enlight_Event_EventArgs $args)
    {
        $message = Shopware()->Snippets()->getNamespace('frontend/connect/checkout')->get(
            'noPercentagedDiscountsAllowed',
            'In Kombination mit connect-Produkten sind keine prozentualen Rabatte möglich.',
            true
        );

        if ($this->getHelper()->hasBasketConnectProducts(Shopware()->SessionID())) {
            $stmt = Shopware()->Db()->query(
                'DELETE FROM s_order_basket WHERE sessionID=? AND modus=3',
                [Shopware()->SessionID()]
            );

            // If rows where actually affected, show the corresponding message
            if ($stmt->rowCount()) {
                Shopware()->Template()->assign('sVoucherError', $message);
            }
        }
    }

    /**
     * Will not allow percentaged vouchers if connect products are in the basket
     *
     * @event Shopware_Modules_Basket_AddVoucher_Start
     *
     * @param \Enlight_Event_EventArgs $args
     *
     * @return bool|null
     */
    public function preventPercentagedVoucher(\Enlight_Event_EventArgs $args)
    {
        $code = $args->getCode();

        if (!$this->getHelper()->hasBasketConnectProducts(Shopware()->SessionID())) {
            return null;
        }

        $basketHelper = $this->getBasketHelper();

        $message = Shopware()->Snippets()->getNamespace('frontend/connect/checkout')->get(
            'noPercentagedVoucherAllowed',
            'In Kombination mit connect-Produkten sind keine prozentualen Gutscheine möglich.',
            true
        );

        // Exclude general percentaged vouchers
        $result = $basketHelper->findPercentagedVouchers($code);
        if (!empty($result)) {
            Shopware()->Template()->assign('sVoucherError', $message);

            return true;
        }

        // Exclude individual percentaged vouchers
        $result = $basketHelper->findPercentagedIndividualVouchers($code);
        if (!empty($result)) {
            Shopware()->Template()->assign('sVoucherError', $message);

            return true;
        }
    }
}
