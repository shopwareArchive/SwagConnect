<?php
/**
 * Shopware 4.0
 * Copyright © 2013 shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace ShopwarePlugins\Connect\Components;

use ShopwarePlugins\Connect\Components\Marketplace\MarketplaceSettings;
use Shopware\Components\Model\ModelManager;
use Shopware\CustomModels\Connect\Config as ConfigModel;

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagConnect
 */
class Config
{
    const MARKETPLACE_URL = 'shopware.connect.com';
    const SN_PREFIX = 'sn.';

    /**
     * @var ModelManager
     */
    private $manager;

    /**
     * @var \Shopware\CustomModels\Connect\ConfigRepository
     */
    private $repository;

    /** @var  \Shopware\Models\Shop\Shop */
    private $shopRepository;

    /** @var  \Shopware\Models\Site\Repository */
    private $staticPagesRepository;

    /**
     * @param ModelManager $manager
     */
    public function __construct(ModelManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param $name
     * @param null $default
     * @param null $shopId
     * @return null
     */
    public function getConfig($name, $default = null, $shopId = null)
    {
        $result = $this->getPluginConfig($name);
        if (!empty($result)) {
            return $result;
        }

        if (is_null($shopId)) {
            return $this->getMainConfig($name, $default);
        }
        $query = $this->getConfigRepository()->getConfigsQuery($name, $shopId);
        $query->setMaxResults(1);
        $result = $query->getResult();
        $model = $result[0];

        if ($model) {
            $decodedString = json_decode($model->getValue(), true);
            if ($decodedString !== null) {
                return $decodedString;
            }
            return $model->getValue();
        }

        $shop = $this->getShopRepository()->find($shopId);
        if (!$shop) {
            return $this->getMainConfig($name, $default);
        }

        $mainShop = $shop->getMain();
        if ($mainShop) {
            $mainShopId = $mainShop->getId();
            $query = $this->getConfigRepository()->getConfigsQuery($name, $mainShopId);
            $query->setMaxResults(1);
            $result = $query->getResult();
            $model = $result[0];

            if ($model) {
                $decodedString = json_decode($model->getValue(), true);
                if ($decodedString !== null) {
                    return $decodedString;
                }
                return $model->getValue();
            }
        }

        return $this->getMainConfig($name, $default);
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed
     */
    private function getPluginConfig($name, $default = null)
    {
        return Shopware()->Config()->getByNamespace('SwagConnect', $name, $default);
    }

    /**
     * @param $name
     * @param null $default
     * @return null
     */
    private function getMainConfig($name, $default = null)
    {
        $query = $this->getConfigRepository()->getConfigsQuery($name);
        $query->setMaxResults(1);
        $result = $query->getResult();
        if (count($result) === 0) {
            return $default;
        }

        $decodedString = json_decode($result[0]->getValue(), true);
        if ($decodedString !== null) {
            return $decodedString;
        }
        return $result[0]->getValue();
    }

    /**
     * @param null $name
     * @param null $shopId
     * @param null $groupName
     * @return array
     */
    public function getConfigs($name = null, $shopId = null, $groupName = null)
    {
        $query = $this->getConfigRepository()->getConfigsQuery($name, $shopId, $groupName);

        return $query->getResult();
    }

    /**
     * @param $name
     * @param $value
     * @param null $shopId
     * @param null $groupName
     */
    public function setConfig($name, $value, $shopId = null, $groupName = null)
    {
        $model = $this->getConfigRepository()->findOneBy(array('name' => $name));

        if (!$model) {
            $model = new ConfigModel();
            $this->manager->persist($model);
        }

        $model->setName($name);

        if (is_array($value)) {
            $model->setValue(json_encode($value));
        } else {
            $model->setValue($value);
        }

        $model->setShopId($shopId);
        $model->setGroupName($groupName);

        $this->manager->flush();
    }

    public function deleteConfig($name, $shopId = null)
    {
        $whereClause = array('name' => $name);
        if ($shopId > 0) {
            $whereClause['shopId'] = $shopId;
        }
        $model = $this->getConfigRepository()->findOneBy($whereClause);
        if (!$model) {
            throw new \Exception(sprintf(
                'Config entity %s not found!',
                $name
            ));
        }

        $this->getConfigRepository()->remove($model);
    }

    /**
     * Helper function which returns general configuration
     * for each shop.
     *
     * @return array
     */
    public function getGeneralConfig()
    {
        $configsArray = array();

        $query = "SELECT `name`, `value` FROM s_plugin_connect_config
        WHERE `shopId` IS NULL AND `groupName` = 'general'";

        $result = Shopware()->Db()->fetchPairs($query);
        $configsArray[] = $result;

        return $configsArray;
    }

    /**
     * Stores data general config
     * data into database.
     *
     * @param array $data
     */
    public function setGeneralConfigs($data)
    {
        foreach ($data as $key => $configItem) {

            /** @var \Shopware\CustomModels\Connect\Config $model */
            $model = $this->getConfigRepository()->findOneBy(array(
                'name' => $key,
                'groupName' => 'general'
            ));

            if (is_null($model)) {
                $model = new ConfigModel();
                $model->setName($key);
                $model->setGroupName('general');
            }

            if (is_array($configItem)) {
                $model->setValue(json_encode($configItem));
            } else {
                $model->setValue($configItem);
            }

            $this->manager->persist($model);
        }

        $this->manager->flush();
    }

    /**
     * Helper function which returns import configuration.
     *
     * @return array
     */
    public function getImportConfig()
    {
        $query = "SELECT `name`, `value` FROM s_plugin_connect_config
        WHERE `shopId` IS NULL AND `groupName` = 'import'";

        $result = Shopware()->Db()->fetchPairs($query);

        return $result;
    }

    /**
     * Stores data import config
     * data into database.
     *
     * @param array $data
     */
    public function setImportConfigs($data)
    {
        foreach ($data as $config) {
            unset($config['id']);
            foreach ($config as $key => $configValue) {
                /** @var \Shopware\CustomModels\Connect\Config $model */
                $model = $this->getConfigRepository()->findOneBy(array(
                    'name' => $key,
                    'shopId' => null,
                    'groupName' => 'import'
                ));
                if (is_null($model)) {
                    $model = new ConfigModel();
                    $model->setName($key);
                    $model->setGroupName('import');
                    $model->setShopId(null);
                }

                if (is_array($configValue)) {
                    $model->setValue(json_encode($configValue));
                } else {
                    $model->setValue($configValue);
                }
                $this->manager->persist($model);
            }
        }

        $this->manager->flush();
    }

    /**
     * Helper function which returns export configuration.
     *
     * @return array
     */
    public function getExportConfig()
    {
        $query = "SELECT `name`, `value` FROM s_plugin_connect_config
        WHERE `shopId` IS NULL AND `groupName` = 'export'";

        $result = Shopware()->Db()->fetchPairs($query);

        foreach ($result as $key => $value) {
            $decodedString = json_decode($value, true);
            if ($decodedString !== null) {
                $result[$key] = $decodedString;
            }
        }

        return $result;
    }

    public function setExportConfigs($data)
    {
        foreach ($data as $config) {
            unset($config['id']);
            foreach ($config as $key => $configValue) {
                /** @var \Shopware\CustomModels\Connect\Config $model */
                $model = $this->getConfigRepository()->findOneBy(array(
                    'name' => $key,
                    'shopId' => null,
                    'groupName' => 'export'
                ));
                if (is_null($model)) {
                    $model = new ConfigModel();
                    $model->setName($key);
                    $model->setGroupName('export');
                    $model->setShopId(null);
                }

                if (is_array($configValue)) {
                    $model->setValue(json_encode($configValue));
                } else {
                    $model->setValue($configValue);
                }

                $this->manager->persist($model);
            }
        }

        $this->manager->flush();
    }

    /**
     * Stores units mapping
     * data into database.
     * @param $units
     */
    public function setUnitsMapping($units)
    {
        foreach ($units as $unit) {
            /** @var \Shopware\CustomModels\Connect\Config $model */
            $model = $this->getConfigRepository()->findOneBy(array(
                'name' => $unit['connectUnit'],
                'shopId' => null,
                'groupName' => 'units'
            ));

            if (!$model) {
                continue;
            }

            $model->setValue($unit['shopwareUnitKey']);
            $this->manager->persist($model);
        }

        $this->manager->flush();
    }

    /**
     * Returns units mapping from Connect config table
     *
     * @return array
     */
    public function getUnitsMappings()
    {
        $query = "SELECT `name`, `value` FROM s_plugin_connect_config
        WHERE `shopId` IS NULL AND `groupName` = 'units'";

        return Shopware()->Db()->fetchPairs($query);
    }

    /**
     * Compare given export price configuration
     * and current export price configuration
     * @param array $config
     * @return bool
     */
    public function compareExportConfiguration($config)
    {
        $currentConfig = $this->getExportConfig();
        if ($currentConfig['priceGroupForPriceExport'] != $config['priceGroupForPriceExport'])
            return true;
        elseif ($currentConfig['priceFieldForPriceExport'] != $config['priceFieldForPriceExport'])
            return true;
        elseif ($currentConfig['priceGroupForPurchasePriceExport'] != $config['priceGroupForPurchasePriceExport'])
            return true;
        elseif ($currentConfig['priceFieldForPurchasePriceExport'] != $config['priceFieldForPurchasePriceExport'])
            return true;
        elseif ($currentConfig['exportPriceMode'] != $config['exportPriceMode'])
            return true;

        return false;
    }

    /**
     * @param $priceExportMode
     * @param $customerGroupKey
     * @return array
     */
    public function collectExportPrice($priceExportMode, $customerGroupKey)
    {
        $exportConfigArray = $this->getExportConfig();
        $postfix = 'ForPriceExport';

        if ($priceExportMode == 'purchasePrice') {
            $postfix = 'ForPurchasePriceExport';
        }

        $group = 'priceGroup' . $postfix;
        $price = 'priceField' . $postfix;

        $allowGroup = isset($exportConfigArray[$group]) && $exportConfigArray[$group] == $customerGroupKey;

        $customerGroup = $this->getCustomerGroupRepository()->findOneBy(array('key' => $customerGroupKey));

        $productCount = $this->getPriceGateway()->countProducts($customerGroup);
        $priceConfiguredProducts = $this->getPriceGateway()->countProductsWithConfiguredPrice($customerGroup, 'price');
        $basePriceConfiguredProducts = $this->getPriceGateway()->countProductsWithConfiguredPrice($customerGroup, 'baseprice');
        $pseudoPriceConfiguredProducts = $this->getPriceGateway()->countProductsWithConfiguredPrice($customerGroup, 'pseudoprice');

        return array(
            'price' => $allowGroup && $exportConfigArray[$price] == 'price' ? true : false,
            'priceAvailable' => false,
            'priceConfiguredProducts' => $priceConfiguredProducts,
            'basePrice' =>$allowGroup && $exportConfigArray[$price] == 'basePrice' ? true : false,
            'basePriceAvailable' => false,
            'basePriceConfiguredProducts' => $basePriceConfiguredProducts,
            'pseudoPrice' =>$allowGroup && $exportConfigArray[$price] == 'pseudoPrice' ? true : false,
            'pseudoPriceAvailable' => false,
            'pseudoPriceConfiguredProducts' => $pseudoPriceConfiguredProducts,
            'productCount' => $productCount
        );
    }


    /**
     * Returns config entity by value
     * @param $value
     * @return \Shopware\CustomModels\Connect\Config
     */
    public function getConfigByValue($value)
    {
        $model = $this->getConfigRepository()->findOneBy(array('value' => $value, 'groupName' => 'units'));

        return $model;
    }

    /**
     * Saves array with marketplace settings to config table
     *
     * @param MarketplaceSettings $settings
     */
    public function setMarketplaceSettings(MarketplaceSettings $settings)
    {
        $settings = (array)$settings;
        foreach ($settings as $settingName => $settingValue) {
            $this->setConfig($settingName, $settingValue, null, 'marketplace');
        }
    }

    public function getMarketplaceUrl()
    {
        if ($this->getConfig('marketplaceNetworkUrl')) {
            return $this->getConfig('marketplaceNetworkUrl');
        }

        return self::MARKETPLACE_URL;
    }

    public function getSocialNetworkPrefix()
    {
        return self::SN_PREFIX;
    }

    /**
     * @return \Shopware\Components\Model\ModelRepository|\Shopware\CustomModels\Connect\ConfigRepository
     */
    private function getConfigRepository()
    {
        if (!$this->repository) {
            $this->repository = $this->manager->getRepository('Shopware\CustomModels\Connect\Config');
        }

        return $this->repository;
    }

    /**
     * @return \Shopware\Components\Model\ModelRepository|\Shopware\Models\Shop\Shop
     */
    private function getShopRepository()
    {
        if (!$this->shopRepository) {
            $this->shopRepository = $this->manager->getRepository('Shopware\Models\Shop\Shop');
        }

        return $this->shopRepository;
    }

    private function getPriceGateway()
    {
        if (!$this->priceGateway) {
            $this->priceGateway = new \ShopwarePlugins\Connect\Components\PriceGateway(
                Shopware()->Db()
            );
        }

        return $this->priceGateway;
    }

    /**
     * @return \Shopware\Components\Model\ModelRepository
     */
    private function getCustomerGroupRepository()
    {
        if (!$this->customerGroupRepository) {
            $this->customerGroupRepository = Shopware()->Models()->getRepository('Shopware\Models\Customer\Group');
        }

        return $this->customerGroupRepository;
    }

    /**
     * @return \Shopware\Models\Site\Repository
     */
    private function getStaticPagesRepository()
    {
        if (!$this->staticPagesRepository) {
            $this->staticPagesRepository = $this->manager->getRepository('Shopware\Models\Site\Site');
        }

        return $this->staticPagesRepository;
    }
} 