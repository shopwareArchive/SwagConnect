<?php

namespace Shopware\Bepado\Components\ShippingCosts;

use Bepado\SDK\ShippingCosts\Rule;
use Bepado\SDK\ShippingCosts\Rules;
use Bepado\SDK\ShippingCosts\RulesVisitor;
use Shopware\Bepado\Components\Translations\TranslationServiceInterface;

class ShippingCostRuleVisitor extends RulesVisitor
{

    public $rules;
    public $vatMode;

    protected $currentRule;

    /** @var \Shopware\Bepado\Components\Translations\TranslationServiceInterface  */
    protected $translationService;

    public function __construct(TranslationServiceInterface $translationService)
    {
        $this->translationService = $translationService;
    }

    public function startVisitRules(Rules $rules)
    {
        $this->rules = array();
        $this->vatMode = null;
        $this->currentRule = null;
    }

    public function stopVisitRules(Rules $rules)
    {
        $this->vatMode = $rules->vatMode;
    }

    public function startVisitRule(Rule $rule)
    {
        switch (get_class($rule)) {
            case 'Bepado\SDK\ShippingCosts\Rule\CountryDecorator':
                $type = 'country';
                break;
            default:
                $type = 'unknown';
        }

        $this->currentRule = array('type' => $type);

    }

    public function stopVisitRule(Rule $rule)
    {
        $this->rules[] = $this->currentRule;
    }

    public function visitFixedPrice(Rule\FixedPrice $rule)
    {
        // TODO: Implement visitFixedPrice() method.
    }

    public function visitUnitPrice(Rule\UnitPrice $rule)
    {
        // TODO: Implement visitUnitPrice() method.
    }

    public function visitDownstreamCharges(Rule\DownstreamCharges $rule)
    {
        // TODO: Implement visitDownstreamCharges() method.
    }

    public function visitCountryDecorator(Rule\CountryDecorator $rule)
    {
        $this->currentRule['values'] = $this->translationService->get('countries', $rule->countries);
        $this->currentRule['price'] = $rule->delegatee->price;
        $this->currentRule['name'] = $rule->delegatee->label;
    }

    public function visitFreeCarriageLimit(Rule\FreeCarriageLimit $rule)
    {
        // TODO: Implement visitFreeCarriageLimit() method.
    }

    public function visitWeightDecorator(Rule\WeightDecorator $rule)
    {
        // TODO: Implement visitWeightDecorator() method.
    }
}