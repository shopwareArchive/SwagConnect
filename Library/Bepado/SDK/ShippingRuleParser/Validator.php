<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\ShippingRuleParser;

use Bepado\SDK\ShippingRuleParser;
use Bepado\SDK\Struct\ShippingRules;
use Bepado\SDK\ShippingCosts\Rule;
use Bepado\SDK\Struct\VerificatorDispatcher;

class Validator extends ShippingRuleParser
{
    /**
     * Aggregate
     *
     * @var ShippingRuleParser
     */
    private $aggregate;

    /**
     * Verificator
     *
     * @var VerificatorDispatcher
     */
    private $verificator;

    /**
     * __construct
     *
     * @param ShippingRuleParser $aggregate
     * @param VerificatorDispatcher $verificator
     * @return void
     */
    public function __construct(ShippingRuleParser $aggregate, VerificatorDispatcher $verificator)
    {
        $this->aggregate = $aggregate;
        $this->verificator = $verificator;
    }

    /**
     * Parse shipping rules out of string
     *
     * @param string $string
     * @return Struct\ShippingRules
     */
    public function parseString($string)
    {
        $rules = $this->aggregate->parseString($string);

        $this->verificator->verify($rules);
        return $rules;
    }
}
