<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Subscribers;

use Enlight\Event\SubscriberInterface;
use ShopwarePlugins\Connect\Components\BasketHelper;
use ShopwarePlugins\Connect\Components\Helper;

/**
 * Handle vouchers, remove discounts and don't allow percentaged vouchers for connect baskets
 */
class Voucher implements SubscriberInterface
{
    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var BasketHelper
     */
    private $basketHelper;

    /**
     * @var \Shopware_Components_Snippet_Manager
     */
    private $snippetManager;

    /**
     * @param Helper $helper
     * @param BasketHelper $basketHelper
     * @param \Shopware_Components_Snippet_Manager $snippetManager
     */
    public function __construct(Helper $helper, BasketHelper $basketHelper, \Shopware_Components_Snippet_Manager $snippetManager)
    {
        $this->helper = $helper;
        $this->basketHelper = $basketHelper;
        $this->snippetManager = $snippetManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Basket_AddVoucher_Start' => 'preventPercentagedVoucher',
            'Shopware_Modules_Basket_GetBasket_FilterSQL' => 'removeDiscount'
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
        $message = $this->snippetManager->getNamespace('frontend/connect/checkout')->get(
            'noPercentagedDiscountsAllowed',
            'In Kombination mit connect-Produkten sind keine prozentualen Rabatte möglich.',
            true
        );

        if ($this->helper->hasBasketConnectProducts(Shopware()->SessionID())) {
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
     * @return bool|null
     */
    public function preventPercentagedVoucher(\Enlight_Event_EventArgs $args)
    {
        $code = $args->getCode();

        if (!$this->helper->hasBasketConnectProducts(Shopware()->SessionID())) {
            return null;
        }

        $message = $this->snippetManager->getNamespace('frontend/connect/checkout')->get(
            'noPercentagedVoucherAllowed',
            'In Kombination mit connect-Produkten sind keine prozentualen Gutscheine möglich.',
            true
        );

        // Exclude general percentaged vouchers
        $result = $this->basketHelper->findPercentagedVouchers($code);
        if (!empty($result)) {
            Shopware()->Template()->assign('sVoucherError', $message);

            return true;
        }

        // Exclude $this->basketHelper percentaged vouchers
        $result = $this->basketHelper->findPercentagedIndividualVouchers($code);
        if (!empty($result)) {
            Shopware()->Template()->assign('sVoucherError', $message);

            return true;
        }
    }
}
