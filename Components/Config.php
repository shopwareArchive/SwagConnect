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

namespace Shopware\Bepado\Components;

use Shopware\Components\Model\ModelManager;
use Shopware\CustomModels\Bepado\Config as ConfigModel;

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagBepado
 */
class Config
{
    /**
     * @var ModelManager
     */
    private $manager;

    /**
     * @var \Shopware\CustomModels\Bepado\ConfigRepository
     */
    private $repository;

    /** @var  \Shopware\Models\Shop\Shop */
    private $shopRepository;

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
    public function getConfig($name, $default=null, $shopId=null)
    {
        if (is_null($shopId)) {
            return $this->getMainConfig($name, $default);
        }
        $query = $this->getConfigRepository()->getConfigsQuery($name, $shopId);
        $query->setMaxResults(1);
        $result = $query->getResult();
        $model = $result[0];

        if ($model) {
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
                return $model->getValue();
            }
        }

        return $this->getMainConfig($name, $default);
    }

    /**
     * @param $name
     * @param null $default
     * @return null
     */
    private function getMainConfig($name, $default=null)
    {
        $query = $this->getConfigRepository()->getConfigsQuery($name);
        $query->setMaxResults(1);
        $result = $query->getResult();
        $model = $result[0];

        if ($model) {
            return $model->getValue();
        }

        return $default;
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
        $model->setValue($value);
        $model->setShopId($shopId);
        $model->setGroupName($groupName);

        $this->manager->flush();
    }

    /**
     * Helper function which returns general configuration
     * for each shop.
     *
     * @return array
     */
    public function getGeneralConfigArrays()
    {
        $configsArray = array();

        $shops = $this->getShopRepository()->findAll();
        /** @var \Shopware\Models\Shop\Shop $shop */

        foreach ($shops as $shop) {
            $shopId = $shop->getId();
            $shopConfig = array();

            if ($shop->getDefault() === false) {
                $query = "SELECT `name`, `value` FROM s_plugin_bepado_config
                          WHERE `shopId` = $shopId AND `groupName` = 'general'";
                $shopConfig['shopId'] = $shopId;
                $shopConfig['isDefaultShop'] = $shop->getDefault();
            } else {
                $query = "SELECT `name`, `value` FROM s_plugin_bepado_config
                WHERE `shopId` IS NULL AND `groupName` = 'general'";
                $shopConfig['shopId'] = $shopId;
                $shopConfig['isDefaultShop'] = $shop->getDefault();
            }

            $result = Shopware()->Db()->fetchPairs($query);
            $configsArray[] = array_merge($shopConfig, $result);
        }

        return $configsArray;
    }

    /**
     * Stores data general config
     * data into database.
     *
     * @param array $data
     */
    public function setGeneralConfigsArrays($data)
    {
        foreach ($data as $shopConfig) {
            $shopId = null;
            if ($shopConfig['isDefaultShop'] === false) {
                $shopId = $shopConfig['shopId'];
                // store only config options
                // which are different for each shop
                $shopConfig = array(
                    'cloudSearch' =>$shopConfig['cloudSearch'],
                    'detailShopInfo' =>$shopConfig['detailShopInfo'],
                    'detailProductNoIndex' =>$shopConfig['detailProductNoIndex'],
                    'checkoutShopInfo' =>$shopConfig['checkoutShopInfo'],
                );
            } else {
                unset($shopConfig['shopId']);
            }

            unset($shopConfig['isDefaultShop']);

            foreach ($shopConfig as $key => $configItem) {

            /** @var \Shopware\CustomModels\Bepado\Config $model */
            $model = $this->getConfigRepository()->findOneBy(array(
                    'name' => $key,
                    'shopId' => $shopId,
                    'groupName' => 'general'
                ));

                if (is_null($model)) {
                    $model = new ConfigModel();
                    $model->setName($key);
                    $model->setGroupName('general');
                    $model->setShopId($shopId);
                }

                $model->setValue($configItem);
                $this->manager->persist($model);
            }
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
        $query = "SELECT `name`, `value` FROM s_plugin_bepado_config
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
                /** @var \Shopware\CustomModels\Bepado\Config $model */
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

                $model->setValue($configValue);
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
        $query = "SELECT `name`, `value` FROM s_plugin_bepado_config
        WHERE `shopId` IS NULL AND `groupName` = 'export'";

        $result = Shopware()->Db()->fetchPairs($query);

        return $result;
    }

    public function setExportConfigs($data)
    {
        foreach ($data as $config) {
            unset($config['id']);
            foreach ($config as $key => $configValue) {
                /** @var \Shopware\CustomModels\Bepado\Config $model */
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

                $model->setValue($configValue);
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
            /** @var \Shopware\CustomModels\Bepado\Config $model */
            $model = $this->getConfigRepository()->findOneBy(array(
                    'name' => $unit['shopwareUnitKey'],
                    'shopId' => null,
                    'groupName' => 'units'
                ));
            if (is_null($model)) {
                $model = new ConfigModel();
                $model->setName($unit['shopwareUnitKey']);
                $model->setGroupName('units');
                $model->setShopId(null);
            }

            $model->setValue($unit['bepadoUnit']);
            $this->manager->persist($model);
        }

        $this->manager->flush();
    }

    /**
     * @return \Shopware\Components\Model\ModelRepository|\Shopware\CustomModels\Bepado\ConfigRepository
     */
    private function getConfigRepository()
    {
        if (!$this->repository) {
            $this->repository = $this->manager->getRepository('Shopware\CustomModels\Bepado\Config');
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
} 