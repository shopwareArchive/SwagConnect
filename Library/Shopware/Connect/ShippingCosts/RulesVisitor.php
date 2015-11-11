<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\ShippingCosts;

/**
 * Visit shipping cost rules data structure.
 */
abstract class RulesVisitor
{
    public function visit($struct)
    {
        if ($struct instanceof Rules) {
            $this->startVisitRules($struct);

            foreach ($struct as $rule) {
                $this->startVisitRule($rule);
                $this->visit($rule);
                $this->stopVisitRule($rule);
            }

            $this->stopVisitRules($struct);
        } else if ($struct instanceof Rule\FixedPrice) {
            $this->visitFixedPrice($struct);
        } else if ($struct instanceof Rule\UnitPrice) {
            $this->visitUnitPrice($struct);
        } else if ($struct instanceof Rule\DownstreamCharges) {
            $this->visitDownstreamCharges($struct);
        } else if ($struct instanceof Rule\CountryDecorator) {
            $this->visitCountryDecorator($struct);

            $this->visit($struct->delegatee);
        } else if ($struct instanceof Rule\MinimumBasketValue) {
            $this->visitMinimumBasketValue($struct);

            $this->visit($struct->delegatee);
        } else if ($struct instanceof Rule\WeightDecorator) {
            $this->visitWeightDecorator($struct);

            $this->visit($struct->delegatee);
        }
    }

    abstract public function startVisitRules(Rules $rules);

    abstract public function stopVisitRules(Rules $rules);

    abstract public function startVisitRule(Rule $rule);

    abstract public function stopVisitRule(Rule $rule);

    abstract public function visitFixedPrice(Rule\FixedPrice $rule);

    abstract public function visitUnitPrice(Rule\UnitPrice $rule);

    abstract public function visitDownstreamCharges(Rule\DownstreamCharges $rule);

    abstract public function visitCountryDecorator(Rule\CountryDecorator $rule);

    abstract public function visitMinimumBasketValue(Rule\MinimumBasketValue $rule);

    abstract public function visitWeightDecorator(Rule\WeightDecorator $rule);
}
