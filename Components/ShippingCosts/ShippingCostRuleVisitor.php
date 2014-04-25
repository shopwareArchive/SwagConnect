<?php

namespace Shopware\Bepado\Components\ShippingCosts;

use Bepado\SDK\ShippingCosts\Rule;
use Bepado\SDK\ShippingCosts\Rules;
use Bepado\SDK\ShippingCosts\RulesVisitor;
use Shopware\Bepado\Components\Translations\TranslationServiceInterface;

class ShippingCostRuleVisitor extends RulesVisitor
{

    public $rules = array();
    public $vatMode;
    public $vat;

    protected $currentRule = null;

    /** @var \Shopware\Bepado\Components\Translations\TranslationServiceInterface */
    protected $translationService;

    public function __construct(TranslationServiceInterface $translationService)
    {
        $this->translationService = $translationService;
    }

    public function startVisitRules(Rules $rules)
    {
        $this->vatMode = $rules->vatMode;
        $this->vat = $rules->vat;
    }

    public function stopVisitRules(Rules $rules)
    {

    }

    public function startVisitRule(Rule $rule)
    {
        switch (get_class($rule)) {
            case 'Bepado\SDK\ShippingCosts\Rule\CountryDecorator':
                $type = 'country';
                break;
            case 'Bepado\SDK\ShippingCosts\Rule\FreeCarriageLimit':
                $type = 'freeCarriage';
                break;
            case 'Bepado\SDK\ShippingCosts\Rule\WeightDecorator':
                $type = 'weight';
                break;
            default:
                $type = null;
        }

        if (null === $type) {
            return;
        }
        $this->currentRule = array('type' => $type);
    }

    private function mergeCurrentRule()
    {
        $type = $this->currentRule['type'];

        if (null === $type) {
            return;
        }

        if (!isset($this->rules[$type])) {
            $this->rules[$type] = $this->currentRule;
            return;
        }

        $this->rules[$type]['values'] = array_merge($this->rules[$type]['values'] , $this->currentRule['values']);
        
    }

    public function stopVisitRule(Rule $rule)
    {
        $this->mergeCurrentRule();
    }

    public function visitFixedPrice(Rule\FixedPrice $rule)
    {
        foreach ($this->currentRule['values'] as &$data) {
            $data['netPrice'] = $rule->price;
            $data['grossPrice'] = $this->calculateGrossPrice($rule->price);
        }
        $this->currentRule['name'] = $rule->label;
    }

    public function visitUnitPrice(Rule\UnitPrice $rule)
    {
        foreach ($this->currentRule['values'] as &$data) {
            $data['netPrice'] = $rule->price;
            $data['grossPrice'] = $this->calculateGrossPrice($rule->price);
        }
        $this->currentRule['name'] = $rule->label;
    }

    public function visitDownstreamCharges(Rule\DownstreamCharges $rule)
    {
        // TODO: Implement visitDownstreamCharges() method.
    }

    public function visitCountryDecorator(Rule\CountryDecorator $rule)
    {
        $this->currentRule['values'] = array_map(
            function ($value) {
                return array('value' => $value);
            },
            $this->translationService->get('countries', $rule->countries)
        );
    }

    public function visitFreeCarriageLimit(Rule\FreeCarriageLimit $rule)
    {
        $this->currentRule['values'] = array(array('value' => $rule->freeLimit));
    }

    public function visitWeightDecorator(Rule\WeightDecorator $rule)
    {
        $this->currentRule['values'] = array(array('value' => $rule->maxWeight));
    }

    private function calculateGrossPrice($netPrice)
    {
        switch ($this->vatMode) {
            case 'fix':
                $vat = $this->vat + 1;
                break;
            case 'max':
            case 'dominaiting':
            default:
                $vat = 1.19;
        }

        return round($netPrice * $vat, 2);
    }
}