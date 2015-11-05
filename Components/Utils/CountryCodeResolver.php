<?php


namespace Shopware\Connect\Components\Utils;

use \Shopware\Components\Model\ModelManager;
use \Shopware\Models\Customer\Customer;

/**
 * The CountryCodeResolver class helps to determine the country code for the current user
 *
 * Class CountryCode
 * @package Shopware\Connect\Components
 */
class CountryCodeResolver
{

    /** @var \Shopware\Components\Model\ModelManager  */
    protected $modelManager;
    /** @var  \Shopware\Models\Country\Repository */
    protected $countryRepository;
    /** @var string  */
    protected $default = 'DEU';
    /** @var  \Shopware\Models\Customer\Customer */
    protected $customer;
    /** @var  int */
    protected $countryId;

    /**
     * @param ModelManager $modelManager
     * @param Customer $customer            Customer object, if available
     * @param null $countryId               CountryID - e.g. from session
     * @param string $default               Fallback
     */
    public function __construct(ModelManager $modelManager, Customer $customer=null, $countryId=null,  $default = 'DEU')
    {
        $this->modelManager = $modelManager;
        $this->default = 'DEU';
        $this->customer = $customer;
        $this->countryId = $countryId;
    }

    /**
     * @return \Shopware\Components\Model\ModelRepository|\Shopware\Models\Country\Repository
     */
    private function getCountryRepository()
    {
        if (!$this->countryRepository) {
            $this->countryRepository = $this->modelManager->getRepository('Shopware\Models\Country\Country');
        }
        return $this->countryRepository;
    }

    /**
     * @return \Shopware\Models\Country\Country
     */
    public function getShippingCountry()
    {
        if ($this->customer && $this->customer->getShipping()) {
            $countryId = $this->customer->getShipping()->getCountryId();
        } else if ($this->countryId) {
            $countryId = $this->countryId;
        } else {
            return $this->getCountryRepository()->findOneBy(array('iso3' => $this->default));
        }

        return $this->getCountryRepository()->find($countryId);
    }

    /**
     * @return string
     */
    public function getIso3CountryCode()
    {
        return $this->getShippingCountry()->getIso3();
    }
}