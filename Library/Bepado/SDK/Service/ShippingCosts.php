<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.141
 */

namespace Bepado\SDK\Service;

use Bepado\SDK\Gateway;
use Bepado\SDK\ShippingCostCalculator;

/**
 * Service to maintain transactions
 *
 * @version 1.1.141
 */
class ShippingCosts
{
    /**
     * Shipping costs gateway
     *
     * @var Gateway\ShippingCosts
     */
    protected $shippingCosts;

    /**
     * Shipping cost calculator
     *
     * @var ShippingCostCalculator
     */
    protected $calculator;

    /**
     * COnstruct from gateway
     *
     * @param Gateway\ShippingCosts $shippingCosts
     * @param ShippingCostCalculator $calculator
     * @return void
     */
    public function __construct(
        Gateway\ShippingCosts $shippingCosts,
        ShippingCostCalculator $calculator
    ) {
        $this->shippingCosts = $shippingCosts;
        $this->calculator = $calculator;
    }

    /**
     * Get last revision
     *
     * @return string
     */
    public function lastRevision()
    {
        return $this->shippingCosts->getLastShippingCostsRevision();
    }

    /**
     * Replicate changes
     *
     * @param array $changes
     * @return void
     */
    public function replicate(array $changes)
    {
        foreach ($changes as $change) {
            $this->shippingCosts->storeShippingCosts(
                $change['from_shop_id'],
                $change['to_shop_id'],
                $change['revision'],
                $change['shippingCosts']
            );
        }
    }
}
