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

use ShopwarePlugins\Connect\Components\Config;
use Shopware\Connect\Units;
use ShopwarePlugins\Connect\Components\ConnectExport;
use ShopwarePlugins\Connect\Components\Validator\ProductAttributesValidator\ProductsAttributesValidator;
use ShopwarePlugins\Connect\Components\Utils\UnitMapper;
use ShopwarePlugins\Connect\Components\Logger;

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagConnect
 * @copyright Copyright (c) 2013, shopware AG (http://www.shopware.de)
 */
class Shopware_Controllers_Backend_ConnectConfig extends Shopware_Controllers_Backend_ExtJs
{

    /** @var  \ShopwarePlugins\Connect\Components\Config */
    private $configComponent;

    /**
     * @var \ShopwarePlugins\Connect\Components\ConnectFactory
     */
    private $factory;

    /**
     * @var \ShopwarePlugins\Connect\Components\Utils\UnitMapper
     */
    private $unitMapper;

    /**
     * @var \ShopwarePlugins\Connect\Components\Logger
     */
    private $logger;

    /**
     * The getGeneralAction function is an ExtJs event listener method of the
     * connect module. The function is used to load store
     * required in the general config form.
     * @return string
     */
    public function getGeneralAction()
    {
        $generalConfig = $this->getConfigComponent()->getGeneralConfigArrays();

        $this->View()->assign(array(
            'success' => true,
            'data' => $generalConfig
        ));
    }

    /**
     * The saveGeneralAction function is an ExtJs event listener method of the
     * connect module. The function is used to save store data.
     * @return string
     */
    public function saveGeneralAction()
    {
        try {
            $data = $this->Request()->getParam('data');
            $data = !isset($data[0]) ? array($data) : $data;

            $this->getConfigComponent()->setGeneralConfigsArrays($data);

            $this->View()->assign(array(
                'success' => true
            ));
        } catch (\Exception $e) {
            $this->View()->assign(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        }
    }

    public function changeLoggingAction()
    {
        try {
            $enableLogging = $this->Request()->getParam('enableLogging');
            $this->getConfigComponent()->setConfig('logRequest', $enableLogging, null, 'general');

            $this->View()->assign(array(
                'success' => true
            ));
        } catch (\Exception $e) {
            $this->View()->assign(array(
                'success' => false,
            ));
        }
    }

    public function getLoggingEnabledAction()
    {
        $this->View()->assign(array(
            'success' => true,
            'enableLogging' => $this->getConfigComponent()->getConfig('logRequest', 0),
        ));
    }

    /**
     * The getImportAction function is an ExtJs event listener method of the
     * connect module. The function is used to load store
     * required in the import config form.
     * @return string
     */
    public function getImportAction()
    {
        $importConfigArray = $this->getConfigComponent()->getImportConfig();

        $this->View()->assign(
            array(
                'success' => true,
                'data' => $importConfigArray
            )
        );
    }

    /**
     * The saveImportAction function is an ExtJs event listener method of the
     * connect module. The function is used to save store data.
     * @return string
     */
    public function saveImportAction()
    {
        $data = $this->Request()->getParam('data');
        $data = !isset($data[0]) ? array($data) : $data;

        $this->getConfigComponent()->setImportConfigs($data);

        $this->View()->assign(
            array(
                'success' => true
            )
        );
    }

    /**
     * The getExportAction function is an ExtJs event listener method of the
     * connect module. The function is used to load store
     * required in the export config form.
     * @return string
     */
    public function getExportAction()
    {
        $exportConfigArray = $this->getConfigComponent()->getExportConfig();
        switch ($this->getSDK()->getPriceType()) {
            case \Shopware\Connect\SDK::PRICE_TYPE_BOTH:
                $exportConfigArray['exportPriceMode'] = array('price', 'purchasePrice');
                break;
            case \Shopware\Connect\SDK::PRICE_TYPE_RETAIL:
                $exportConfigArray['exportPriceMode'] = array('price');
                break;
            case \Shopware\Connect\SDK::PRICE_TYPE_PURCHASE:
                $exportConfigArray['exportPriceMode'] = array('purchasePrice');
                break;
            default:
                $exportConfigArray['exportPriceMode'] = array();
        }

        $this->View()->assign(
            array(
                'success' => true,
                'data' => $exportConfigArray
            )
        );
    }

    /**
     * ExtJS uses this action to check is price mapping allowed.
     * If there is at least one exported product to connect,
     * price mapping cannot be changed.
     */
    public function isPricingMappingAllowedAction()
    {
        $isPriceModeEnabled = false;
        $isPurchasePriceModeEnabled = false;

        if ($this->getSDK()->getPriceType() === \Shopware\Connect\SDK::PRICE_TYPE_BOTH
        || $this->getSDK()->getPriceType() === \Shopware\Connect\SDK::PRICE_TYPE_RETAIL) {
            $isPriceModeEnabled = true;
        }

        if ($this->getSDK()->getPriceType() === \Shopware\Connect\SDK::PRICE_TYPE_BOTH
        || $this->getSDK()->getPriceType() === \Shopware\Connect\SDK::PRICE_TYPE_PURCHASE)
        {
            $isPurchasePriceModeEnabled = true;
        }

        $this->View()->assign(
            array(
                'success' => true,
                'isPricingMappingAllowed' => !count($this->getConnectExport()->getExportArticlesIds()) > 0,
                'isPriceModeEnabled' => $isPriceModeEnabled,
                'isPurchasePriceModeEnabled' => $isPurchasePriceModeEnabled,
            )
        );
    }

    /**
     * The saveExportAction function is an ExtJs event listener method of the
     * connect module. The function is used to save store data.
     * @return string
     */
    public function saveExportAction()
    {
        $data = $this->Request()->getParam('data');
        $data = !isset($data[0]) ? array($data) : $data;

        if ($data['priceFieldForPurchasePriceExport'] == $data['priceFieldForPriceExport']) {
            $this->View()->assign(array(
                'success' => false,
                'message' => Shopware()->Snippets()->getNamespace('backend/connect/view/main')->get(
                    'config/export/error/same_price_fields',
                    'Endkunden-VK und Listenverkaufspreis müssen an verschiedene Felder angeschlossen sein',
                    true
                )
            ));
            return;
        }

        $isModified = $this->getConfigComponent()->compareExportConfiguration($data);
        $this->getConfigComponent()->setExportConfigs($data);

        if ($isModified === true) {
            $connectExport = $this->getConnectExport();
            try {
                $ids = $connectExport->getExportArticlesIds();
                $sourceIds = $this->getHelper()->getArticleSourceIds($ids);
                $errors = $connectExport->export($sourceIds);
            }catch (\RuntimeException $e) {
                $this->View()->assign(array(
                        'success' => false,
                        'message' => $e->getMessage()
                    ));
                return;
            }

            if (!empty($errors)) {
                $this->View()->assign(array(
                        'success' => false,
                        'message' => implode("<br>\n", $errors)
                    ));
                return;
            }
        }

        $this->View()->assign(
            array(
                'success' => true
            )
        );
    }

    /**
     * @return ConnectExport
     */
    public function getConnectExport()
    {
        return new ConnectExport(
            $this->getHelper(),
            $this->getSDK(),
            $this->getModelManager(),
            new ProductsAttributesValidator(),
            $this->getConfigComponent()
        );
    }

    /**
     * @return \ShopwarePlugins\Connect\Components\Helper
     */
    public function getHelper()
    {
        if ($this->factory === null) {
            $this->factory = new \ShopwarePlugins\Connect\Components\ConnectFactory();
        }

        return $this->factory->getHelper();
    }

    /**
     * @return \Shopware\Connect\SDK
     */
    public function getSDK()
    {
        return Shopware()->Bootstrap()->getResource('ConnectSDK');
    }

    /**
     * @return Shopware\Components\Model\ModelManager
     */
    public function getModelManager()
    {
        return Shopware()->Models();
    }

    /**
     * The getStaticPagesAction function is an ExtJs event listener method of the
     * connect module. The function is used to load store
     * required in the general config form for static cms pages combo.
     * @return string
     */
    public function getStaticPagesAction()
    {
        $builder = $this->getModelManager()->createQueryBuilder();
        $builder->select('st.id, st.description AS name');
        $builder->from('Shopware\Models\Site\Site', 'st');

        $query = $builder->getQuery();
        $query->setFirstResult($this->Request()->getParam('start'));
        $query->setMaxResults($this->Request()->getParam('limit'));

        $total = Shopware()->Models()->getQueryCount($query);
        $data = $query->getArrayResult();

        $this->View()->assign(array(
                'success' => true,
                'data' => $data,
                'total' => $total
            ));

    }

    /**
     * Helper function to get access on the Config component
     *
     * @return \ShopwarePlugins\Connect\Components\Config
     */
    private function getConfigComponent()
    {
        if ($this->configComponent === null) {
            $modelsManager = Shopware()->Models();
            $this->configComponent = new Config($modelsManager);
        }

        return $this->configComponent;
    }

	/**
     * The getUnitsAction function is an ExtJs event listener method of the
     * connect module. The function is used to load store
     * required in the units mapping.
     * @return string
     */
    public function getUnitsAction()
    {
        $repository = Shopware()->Models()->getRepository('Shopware\Models\Article\Unit');
        $units = $repository->findAll();

        $unitsMappingArray = array();
        foreach ($units as $unit) {
            $unitsMappingArray[] = array(
                'shopwareUnitName' => $unit->getName(),
                'shopwareUnitKey' => $unit->getUnit()
            );
        }

        $this->View()->assign(
            array(
                'success' => true,
                'data' => $unitsMappingArray
            )
        );

    }

    /**
     * The saveUnitsMappingAction function is an ExtJs event listener method of the
     * connect module. The function is used to save units store data.
     * @return string
     */
    public function saveUnitsMappingAction()
    {
        $data = $this->Request()->getParam('data');
        $data = !isset($data[0]) ? array($data) : $data;

        $this->getConfigComponent()->setUnitsMapping($data);

        // update related products
        $repository = Shopware()->Models()->getRepository('Shopware\Models\Article\Unit');
        foreach ($data as $unit) {
            /** @var \Shopware\Models\Article\Unit $unitModel */
            $unitModel = $repository->findOneBy(array('unit' => $unit['shopwareUnitKey']));
            if ($unitModel) {
                continue;
            }
            $this->getHelper()->updateUnitInRelatedProducts($unitModel, $unit['connectUnit']);
        }

        $this->View()->assign(
            array(
                'success' => true
            )
        );
    }

    /**
     * The getConnectUnitsAction function is an ExtJs event listener method of the
     * connect module. The function is used to load store
     * required in the units mapping.
     * @return string
     */
    public function getConnectUnitsAction()
    {
        $connectUnits = new Units();
        $connectUnitsArray = $connectUnits->getLocalizedUnits('de');
        $unitsArray = array();
        $hideAssigned = (int)$this->Request()->getParam('hideAssignedUnits', 0);

        foreach ($this->getConfigComponent()->getUnitsMappings() as $connectUnit => $localUnit) {
            if ($hideAssigned == true && strlen($localUnit) > 0) {
                continue;
            }
            $unitsArray[] = array(
                'connectUnit' => $connectUnit,
                'name' => $connectUnitsArray[$connectUnit],
                'shopwareUnitKey' => $localUnit
            );
        }

        $this->View()->assign(
            array(
                'success' => true,
                'data' => $unitsArray
            )
        );
    }

    /**
     * Ask SocialNetwork what time is need to finish the product update
     */
    public function calculateFinishTimeAction()
    {
        $changes = $this->getConnectExport()->getChangesCount();
        $seconds = 0;
        if ($changes > 0) {
            $seconds = $this->getSDK()->calculateFinishTime($changes);
        }

        try {
            $this->View()->assign(
                array(
                    'success' => true,
                    'time' => $seconds,
                )
            );
        } catch (\Exception $e) {
            $this->View()->assign(
                array(
                    'success' => false,
                    'message' => $e->getMessage(),
                )
            );
        }
    }

    public function getMarketplaceAttributesAction()
    {
        try {
            $verified = $this->getConfigComponent()->getConfig('apiKeyVerified', false);

            if ($verified) {
                $marketplaceAttributes = $this->getSDK()->getMarketplaceProductAttributes();

                $attributes = array();
                foreach ($marketplaceAttributes as $attributeKey => $attributeLabel) {
                    $attributes[] = array(
                        'attributeKey' => $attributeKey,
                        'attributeLabel' => $attributeLabel,
                        'shopwareAttributeKey' => ''
                    );
                }
            } else {
                $attributes = array();
            }
        } catch(\Exception $e) {
            // ignore this exception because sometimes
            // connect plugin is not configured and tries to
            // read marketplace attributes
            $attributes = array();
        }

        $this->View()->assign(
            array(
                'success' => true,
                'data' => $attributes
            )
        );
    }

    public function saveProductAttributesMappingAction()
    {
        try {
            $data = $this->Request()->getParam('data');
            $data = !isset($data[0]) ? array($data) : $data;
            $marketplaceGateway = $this->getFactory()->getMarketplaceGateway();
            $marketplaceGateway->setMarketplaceMapping($data);

            $this->View()->assign(
                array(
                    'success' => true,
                )
            );
        } catch(\Exception $e) {
            $this->View()->assign(
                array(
                    'success' => false,
                    'message' => $e->getMessage()
                )
            );
        }
    }

    public function getProductAttributesMappingAction()
    {
        $marketplaceGateway = $this->getFactory()->getMarketplaceGateway();

        $mappings = array_map(function ($attribute) use ($marketplaceGateway) {
                return array(
                    'shopwareAttributeKey' => $attribute->getName(),
                    'shopwareAttributeLabel' => $attribute->getLabel(),
                    'attributeKey' => $marketplaceGateway->findMarketplaceMappingFor($attribute->getName()),
                );

            }, array_values(
                array_filter(
                    Shopware()->Models()->getRepository('Shopware\Models\Article\Element')->findAll(),
                    function ($attribute) {
                        return $attribute->getName() != 'connectProductDescription';
                    }
                )
            )
        );

        $this->View()->assign(
            array(
                'success' => true,
                'data' => $mappings
            )
        );
    }

    /**
     * Loads all customer groups where at least
     * one product with price or purchasePrice
     * greater than 0 exists
     *
     * @throws Zend_Db_Statement_Exception
     */
    public function getExportCustomerGroupsAction()
    {
        $priceField = $this->Request()->getParam('priceField', 'price');

        if ($priceField == 'purchasePrice') {
            $query = Shopware()->Db()->query('SELECT pricegroup FROM s_articles_prices WHERE baseprice > 0 GROUP BY pricegroup');
            $availableGroups = $query->fetchAll(\PDO::FETCH_COLUMN);
        } else {
            $query = Shopware()->Db()->query('SELECT pricegroup FROM s_articles_prices WHERE price > 0 GROUP BY pricegroup');
            $availableGroups = $query->fetchAll(\PDO::FETCH_COLUMN);
        }


        $repository = Shopware()->Models()->getRepository('Shopware\Models\Customer\Group');

        $builder = $repository->createQueryBuilder('groups');
        $builder->select(array(
            'groups.id as id',
            'groups.key as key',
            'groups.name as name',
            'groups.tax as tax',
            'groups.taxInput as taxInput',
            'groups.mode as mode'
        ));
        $builder->andWhere('groups.key IN (:groupKeys)')
            ->setParameter('groupKeys', $availableGroups);
        $builder->addFilter($this->Request()->getParam('filter', array()));
        $builder->addOrderBy($this->Request()->getParam('sort', array()));

        $builder->setFirstResult($this->Request()->getParam('start'))
            ->setMaxResults($this->Request()->getParam('limit'));

        $query = $builder->getQuery();

        //get total result of the query
        $total = Shopware()->Models()->getQueryCount($query);

        $data = $query->getArrayResult();

        //return the data and total count
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $data,
                'total' => $total,
            )
        );
    }

    /**
     * Loads all price groups where at least
     * one product with price greater than 0 exists
     *
     * @throws Zend_Db_Statement_Exception
     */
    public function getExportPriceGroupsAction()
    {
        $groups = array();

        $query = Shopware()->Db()->query('SELECT COUNT(id) FROM s_articles_prices WHERE price > 0');
        $priceCount = $query->fetchColumn();
        if ($priceCount > 0) {
            $groups[] = array(
                'field' => 'price',
                'name' => Shopware()->Snippets()->getNamespace('backend/article/view/main')->get(
                    'detail/price/price',
                    'Preis'
                )
            );
        }

        $query = Shopware()->Db()->query('SELECT COUNT(id) FROM s_articles_prices WHERE baseprice > 0');
        $purchasePriceCount = $query->fetchColumn();
        if ($purchasePriceCount > 0) {
            $groups[] = array(
                'field' => 'basePrice',
                'name' => Shopware()->Snippets()->getNamespace('backend/article/view/main')->get(
                    'detail/price/base_price',
                    'Einkaufspreis'
                )
            );
        }

        $query = Shopware()->Db()->query('SELECT COUNT(id) FROM s_articles_prices WHERE pseudoprice > 0');
        $pseudoPriceCount = $query->fetchColumn();
        if ($pseudoPriceCount > 0) {
            $groups[] = array(
                'field' => 'pseudoPrice',
                'name' => Shopware()->Snippets()->getNamespace('backend/article/view/main')->get(
                    'detail/price/pseudo_price',
                    'Pseudopreis'
                )
            );
        }

        $this->View()->assign(
            array(
                'success' => true,
                'data' => $groups,
                'total' => count($groups),
            )
        );
    }

    public function adoptUnitsAction()
    {
        try {
            $units = array_filter($this->getConfigComponent()->getUnitsMappings(), function($unit) {
                return strlen($unit) == 0;
            });

            $models = $this->getUnitMapper()->createUnits(array_keys($units));
            foreach ($models as $unit) {
                $this->getHelper()->updateUnitInRelatedProducts($unit, $unit->getUnit());
            }
        } catch(\Exception $e) {
            $this->getLogger()->write(true, $e->getMessage(), $e);
            $this->View()->assign(
                array(
                    'success' => false,
                )
            );
            return;
        }

        $this->View()->assign(
            array(
                'success' => true,
            )
        );
    }

    /**
     * @return \ShopwarePlugins\Connect\Components\ConnectFactory
     */
    public function getFactory()
    {
        if ($this->factory === null) {
            $this->factory = new \ShopwarePlugins\Connect\Components\ConnectFactory();
        }

        return $this->factory;
    }

    /**
     * @return UnitMapper
     */
    private function getUnitMapper()
    {
        if (!$this->unitMapper) {
            $this->unitMapper = new UnitMapper(
                $this->getConfigComponent(),
                $this->getModelManager()
            );
        }

        return $this->unitMapper;
    }

    /**
     * @return Logger
     */
    private function getLogger()
    {
        if (!$this->logger) {
            $this->logger = new Logger(Shopware()->Db());
        }

        return $this->logger;
    }
} 