<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK;

abstract class ShippingRuleParser
{
    /**
     * Parse shipping rules out of string
     *
     * @param string $string
     * @return Struct\ShippingRules
     */
    abstract public function parseString($string);
}
