<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components\Utils;

use Shopware\Components\Model\ModelManager;
use Shopware\Models\Country\Repository as CountryRepository;
use Shopware\Models\Customer\Customer;

/**
 * The CountryCodeResolver class helps to determine the country code for the current user
 *
 * Class CountryCode
 * @package ShopwarePlugins\Connect\Components
 */
class CountryCodeResolver
{
    /**
     * @var ModelManager
     */
    protected $modelManager;

    /**
     * @var CountryRepository
     */
    protected $countryRepository;

    /**
     * @var string
     */
    protected $default = 'DEU';

    /**
     * @var Customer
     */
    protected $customer;

    /**
     * @var int
     */
    protected $countryId;

    /**
     * @param ModelManager $modelManager
     * @param Customer $customer            Customer object, if available
     * @param null $countryId               CountryID - e.g. from session
     * @param string $default               Fallback
     */
    public function __construct(ModelManager $modelManager, Customer $customer = null, $countryId = null, $default = 'DEU')
    {
        $this->modelManager = $modelManager;
        $this->default = $default;
        $this->customer = $customer;
        $this->countryId = $countryId;
        $this->countryRepository = $this->modelManager->getRepository(\Shopware\Models\Country\Country::class);
    }

    /**
     * @return \Shopware\Models\Country\Country
     */
    public function getShippingCountry()
    {
        if ($this->customer && $this->customer->getShipping()) {
            $countryId = $this->customer->getShipping()->getCountryId();
        } elseif ($this->countryId) {
            $countryId = $this->countryId;
        } else {
            return $this->countryRepository->findOneBy(['iso3' => $this->default]);
        }

        return $this->countryRepository->find($countryId);
    }

    /**
     * @return string
     */
    public function getIso3CountryCode()
    {
        return $this->getShippingCountry()->getIso3();
    }
}
