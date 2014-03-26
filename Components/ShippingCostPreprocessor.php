<?php


namespace Shopware\Bepado\Components;

use Bepado\SDK\ShippingCosts\Rule\CountryDecorator;

/**
 * The ShippingCostPreprocessor will prepare a given shippingCost structure for the template
 *
 * Class ShippingCostPreprocessor
 * @package Shopware\Bepado\Components
 */
class ShippingCostPreprocessor
{
    protected $shippingCosts;

    /** @var  \PDO|\Enlight_Components_Db_Adapter_Pdo_Mysql */
    protected $db;
    protected $countryCode;

    public function __construct($shippingCosts, $db, $language)
    {
        $this->shippingCosts = $shippingCosts;
        $this->db = $db;
        $this->countryCode = $language;
    }

    public function prepare()
    {
        return $this->groupByType();
    }

    /**
     * Group the rules by type, so they can easily be used in the template
     *
     * @return array
     */
    protected function groupByType()
    {
        $output = array();
        foreach ($this->shippingCosts as $shopId => $rules) {
            $output[$shopId] = array('rules' => array(), 'shopInfo' => $this->getShopInfo($shopId));
            foreach ($rules->rules as $rule) {
                $name = $this->mapShippingCostRuleToName($rule);

                if (!isset($output[$shopId]['rules'][$name])) {
                    $output[$shopId]['rules'][$name] = array();
                }

                $output[$shopId]['rules'][$name] = $this->getNormalizesRule($rule);
            }
        }

        return $output;
    }

    /**
     * This will return the various rule types in a similar form
     *
     * @param $rule
     * @return array|bool
     */
    private function getNormalizesRule($rule)
    {
        switch (true) {
            case $rule instanceof CountryDecorator:
                return array(
                    'price' => $rule->delegatee->price,
                    'values' => $this->getTranslatedCountryNames($rule->countries)
                );
            default:
                return false;
        }
    }

    /**
     * Create simple names from the rule types (e.g. CountryDecorator => country)
     *
     * @param $ruleType
     * @return string
     */
    private function mapShippingCostRuleToName($ruleType)
    {
        switch (true) {
            case $ruleType instanceof CountryDecorator:
                return "country";
            default:
                return "unknown";
        }
    }

    /**
     * Get id/name of a given remote shop
     *
     * @param $shopId
     * @return array
     */
    private function getShopInfo($shopId)
    {
        $shop = Shopware()->BepadoSDK()->getShop($shopId);
        return array(
            'id' => $shop->id,
            'name' => $shop->name
        );
    }

    /**
     * Translate the iso3 country name to either an english or a german string
     *
     * @param $countries
     * @return mixed
     */
    private function getTranslatedCountryNames($countries)
    {
        if (in_array($this->countryCode, array('DEU', 'AUT'))) {
            $select = 'countryname';
        } else {
            $select = 'countryen';
        }

        $translatedCountries = $this->db->fetchAssoc(
            "SELECT iso3, {$select} as `name` FROM s_core_countries WHERE iso3 IN ({$this->db->quote($countries)})"
        );


        foreach ($countries as &$country) {
            $countries = $translatedCountries[$country]['name'];
        }

        return $countries;

    }
}