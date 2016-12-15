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
use ShopwarePlugins\Connect\Components\SnHttpClient;
use ShopwarePlugins\Connect\Components\ErrorHandler;
use Shopware\Connect\Gateway\ChangeGateway;
use Firebase\JWT\JWT;

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
     * @var \Shopware\Components\Model\ModelRepository
     */
    private $customerGroupRepository;

    /**
     * @var \ShopwarePlugins\Connect\Components\PriceGateway
     */
    private $priceGateway;

    /**
     * @var \ShopwarePlugins\Connect\Components\SnHttpClient
     */
    private $snHttpClient;

    /**
     * The getGeneralAction function is an ExtJs event listener method of the
     * connect module. The function is used to load store
     * required in the general config form.
     * @return string
     */
    public function getGeneralAction()
    {
        $generalConfig = $this->getConfigComponent()->getGeneralConfig();

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
            unset($data['id']);
            $this->getConfigComponent()->setGeneralConfigs($data);

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
        if (!array_key_exists('exportPriceMode', $exportConfigArray)) {
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
        $isPricingMappingAllowed = $this->getSDK()->getPriceType() === \Shopware\Connect\SDK::PRICE_TYPE_NONE;

        if ($isPricingMappingAllowed) {
            $this->View()->assign(
                array(
                    'success' => true,
                    'isPricingMappingAllowed' => $isPricingMappingAllowed,
                    'isPriceModeEnabled' => true,
                    'isPurchasePriceModeEnabled' => true,
                )
            );
            return;
        }

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
                'isPricingMappingAllowed' => $isPricingMappingAllowed,
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
        $exportPrice = in_array('price', $data['exportPriceMode']);
        $exportPurchasePrice = in_array('purchasePrice', $data['exportPriceMode']);

        $isModified = $this->getConfigComponent()->compareExportConfiguration($data);

        if ($isModified === false) {
            $data = !isset($data[0]) ? array($data) : $data;
            $this->getConfigComponent()->setExportConfigs($data);
            $this->View()->assign(
                array(
                    'success' => true,
                )
            );
            return;
        }

        if ($exportPrice && $exportPurchasePrice) {
            $priceType = \Shopware\Connect\SDK::PRICE_TYPE_BOTH;
        } elseif ($exportPrice) {
            $priceType = \Shopware\Connect\SDK::PRICE_TYPE_RETAIL;
            unset($data['priceFieldForPurchasePriceExport']);
            unset($data['priceGroupForPurchasePriceExport']);
        } elseif ($exportPurchasePrice) {
            $priceType = \Shopware\Connect\SDK::PRICE_TYPE_PURCHASE;
            unset($data['priceFieldForPriceExport']);
            unset($data['priceGroupForPriceExport']);
        } else {
            $this->View()->assign(array(
                'success' => false,
                'message' => Shopware()->Snippets()->getNamespace('backend/connect/view/main')->get(
                    'config/export/priceFieldIsNotSupported',
                    'Price field is not maintained. Some of the products have price = 0',
                    true
                )
            ));
            return;
        }

        if ($priceType == \Shopware\Connect\SDK::PRICE_TYPE_BOTH
            && $data['priceFieldForPurchasePriceExport'] == $data['priceFieldForPriceExport']
            && $data['priceGroupForPurchasePriceExport'] == $data['priceGroupForPriceExport']
        ) {
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

        $detailPurchasePrice = method_exists('Shopware\Models\Article\Detail', 'setPurchasePrice');
        if ($exportPurchasePrice && $detailPurchasePrice) {
            $data['priceFieldForPurchasePriceExport'] = 'detailPurchasePrice';
        }

        if ($priceType == \Shopware\Connect\SDK::PRICE_TYPE_BOTH
            || $priceType == \Shopware\Connect\SDK::PRICE_TYPE_RETAIL
        ) {
            /** @var \Shopware\Models\Customer\Group $groupPrice */
            $groupPrice = $this->getCustomerGroupRepository()->findOneBy(array('key' => $data['priceGroupForPriceExport']));
            if (!$groupPrice) {
                $this->View()->assign(array(
                    'success' => false,
                    'message' => Shopware()->Snippets()->getNamespace('backend/connect/view/main')->get(
                        'config/export/invalid_customer_group',
                        'Ungültige Kundengruppe',
                        true
                    )
                ));
                return;
            }

            if ($this->getPriceGateway()->countProductsWithConfiguredPrice($groupPrice, $data['priceFieldForPriceExport']) === 0) {
                $this->View()->assign(array(
                    'success' => false,
                    'message' => Shopware()->Snippets()->getNamespace('backend/connect/view/main')->get(
                        'config/export/priceFieldIsNotSupported',
                        'Price field is not maintained. Some of the products have price = 0',
                        true
                    )
                ));
                return;
            }
        }

        if ($priceType == \Shopware\Connect\SDK::PRICE_TYPE_BOTH
            || $priceType == \Shopware\Connect\SDK::PRICE_TYPE_PURCHASE
        ) {
            /** @var \Shopware\Models\Customer\Group $groupPurchasePrice */
            $groupPurchasePrice = $this->getCustomerGroupRepository()->findOneBy(array(
                'key' => $data['priceGroupForPurchasePriceExport']
            ));
            if (!$groupPurchasePrice && !$detailPurchasePrice) {
                $this->View()->assign(array(
                    'success' => false,
                    'message' => Shopware()->Snippets()->getNamespace('backend/connect/view/main')->get(
                        'config/export/invalid_customer_group',
                        'Ungültige Kundengruppe',
                        true
                    )
                ));
                return;
            }

            if ($this->getPriceGateway()->countProductsWithConfiguredPrice($groupPurchasePrice, $data['priceFieldForPurchasePriceExport']) === 0) {
                $this->View()->assign(array(
                    'success' => false,
                    'message' => Shopware()->Snippets()->getNamespace('backend/connect/view/main')->get(
                        'config/export/priceFieldIsNotSupported',
                        'Price field is not maintained. Some of the products have price = 0',
                        true
                    )
                ));
                return;
            }
        }

        $connectExport = $this->getConnectExport();

        if ($this->getSDK()->getPriceType() === \Shopware\Connect\SDK::PRICE_TYPE_NONE) {
            //removes all hashes from from sw_connect_product
            //and resets all item status
            $connectExport->clearConnectItems();
        }

        try {
            $data = !isset($data[0]) ? array($data) : $data;
            $response = $this->getSnHttpClient()->sendRequestToConnect(
                array('priceType' => $priceType),
                'account/settings'
            );

            $responseBody = json_decode($response->getBody());
            if (!$responseBody->success) {
                throw new \RuntimeException($responseBody->message);
            }

            $this->getSDK()->verifySdk();
            $this->getConfigComponent()->setExportConfigs($data);

            $ids = $connectExport->getExportArticlesIds();
            $sourceIds = $this->getHelper()->getArticleSourceIds($ids);
            $errors = $connectExport->export($sourceIds);
        }catch (\RuntimeException $e) {
            $this->getLogger()->write(true, 'Save export settings', $e->getMessage() . $e->getTraceAsString());
            $this->View()->assign(array(
                    'success' => false,
                    'message' => $e->getMessage()
                ));
            return;
        }

        if (!empty($errors)) {
            $msg = null;
            foreach ($errors as $type) {
                $msg .= implode("<br>\n", $type);
            }

            $this->View()->assign(array(
                    'success' => false,
                    'message' => $msg
                ));
            return;
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
            $this->getConfigComponent(),
            new ErrorHandler()
        );
    }

    /**
     * It will make a call to SocialNetwork to reset the price type,
     * if this call return success true, then it will reset the export settings locally
     */
    public function resetPriceTypeAction()
    {
        $response = $this->getSnHttpClient()->sendRequestToConnect(array(), 'account/reset/price-type');
        $responseBody = json_decode($response->getBody());

        if(!$responseBody->success) {
            $this->View()->assign([
                'success' => false,
                'message' => $responseBody->message
            ]);

            return;
        }

        // WARNING This code remove the current product changes
        // This is a single call operation and its danger one
        // This code should not be used anywhere
        $builder = $this->getModelManager()->getConnection()->createQueryBuilder();
        $builder->delete('sw_connect_change')
            ->where('c_operation IN (:changes)')
            ->setParameter(
                'changes',
                [
                    ChangeGateway::PRODUCT_INSERT,
                    ChangeGateway::PRODUCT_UPDATE,
                    ChangeGateway::PRODUCT_DELETE,
                    ChangeGateway::PRODUCT_STOCK,
                ],
                \Doctrine\DBAL\Connection::PARAM_STR_ARRAY
            );
        $builder->execute();

        $itemRepo = $this->getModelManager()->getRepository('Shopware\CustomModels\Connect\Attribute');
        $itemRepo->resetExportedItemsStatus();

        $this->View()->assign([
            'success' => true,
        ]);
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
        return Shopware()->Container()->get('ConnectSDK');
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

        $unitName = Shopware()->Snippets()->getNamespace('backend/connect/view/main')->get(
            'import/unit/take_over_units',
            'Take over unit',
            true
        );

        $unitsMappingArray[] = array(
            'shopwareUnitName' => $unitName,
            'shopwareUnitKey' => UnitMapper::ADOPT_UNIT_KEY,
        );

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

        //prepares units for adoption
        $adoptUnitKeys = array();
        foreach ($data as $index => $unit) {
            if ($unit['shopwareUnitKey'] == UnitMapper::ADOPT_UNIT_KEY) {
                $adoptUnitKeys[] = $unit['connectUnit'];
                $data[$index]['shopwareUnitKey'] = $unit['connectUnit'];
            }
        }

        if (!empty($adoptUnitKeys)) {
            $this->getUnitMapper()->createUnits($adoptUnitKeys);
        }

        $this->getConfigComponent()->setUnitsMapping($data);

        // update related products
        $repository = Shopware()->Models()->getRepository('Shopware\Models\Article\Unit');
        foreach ($data as $unit) {
            /** @var \Shopware\Models\Article\Unit $unitModel */
            $unitModel = $repository->findOneBy(array('unit' => $unit['shopwareUnitKey']));
            if (!$unitModel) {
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
        $hideAssigned = (int)$this->Request()->getParam('hideAssignedUnits', 1);

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

            $this->View()->assign(array('success' => true));
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
     * Loads all customer groups
     *
     * @throws Zend_Db_Statement_Exception
     */
    public function getExportCustomerGroupsAction()
    {
        $builder = $this->getCustomerGroupRepository()->createQueryBuilder('groups');
        $builder->select(array(
            'groups.id as id',
            'groups.key as key',
            'groups.name as name',
            'groups.tax as tax',
            'groups.taxInput as taxInput',
            'groups.mode as mode'
        ));

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

        $customerGroupKey = $this->Request()->getParam('customerGroup', 'EK');
        $priceExportMode = $this->Request()->getParam('priceExportMode');
        /** @var \Shopware\Models\Customer\Group $customerGroup */
        $customerGroup = $this->getCustomerGroupRepository()->findOneBy(array('key' => $customerGroupKey));
        if (!$customerGroup) {
            $this->View()->assign(
                array(
                    'success' => true,
                    'data' => $groups,
                    'total' => count($groups),
                )
            );
            return;
        }

        $exportConfigArray = $this->getConfigComponent()->getExportConfig();

        if (array_key_exists('exportPriceMode', $exportConfigArray)
            && count($exportConfigArray['exportPriceMode']) > 0
            && $this->getSDK()->getPriceType() != \Shopware\Connect\SDK::PRICE_TYPE_NONE
        ) {
            $groups[] = $this->getConfigComponent()->collectExportPrice($priceExportMode, $customerGroupKey);
        } else {

            $productCount = $this->getPriceGateway()->countProducts($customerGroup);
            $priceConfiguredProducts = $this->getPriceGateway()->countProductsWithConfiguredPrice($customerGroup, 'price');
            $basePriceConfiguredProducts = $this->getPriceGateway()->countProductsWithConfiguredPrice($customerGroup, 'baseprice');
            $pseudoPriceConfiguredProducts = $this->getPriceGateway()->countProductsWithConfiguredPrice($customerGroup, 'pseudoprice');

            $groups[] = array(
                'price' => false,
                'priceAvailable' => $priceConfiguredProducts > 0,
                'priceConfiguredProducts' => $priceConfiguredProducts,
                'basePrice' => false,
                'basePriceAvailable' => $basePriceConfiguredProducts > 0,
                'basePriceConfiguredProducts' => $basePriceConfiguredProducts,
                'pseudoPrice' => false,
                'pseudoPriceAvailable' => $pseudoPriceConfiguredProducts > 0,
                'pseudoPriceConfiguredProducts' => $pseudoPriceConfiguredProducts,
                'productCount' => $productCount
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
        $connection = $this->getModelManager()->getConnection();
        $connection->beginTransaction();

        try {
            $units = array_filter($this->getConfigComponent()->getUnitsMappings(), function($unit) {
                return strlen($unit) == 0;
            });

            $models = $this->getUnitMapper()->createUnits(array_keys($units));
            foreach ($models as $unit) {
                $this->getHelper()->updateUnitInRelatedProducts($unit, $unit->getUnit());
            }
            $connection->commit();
        } catch(\Exception $e) {
            $connection->rollBack();
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
     * @return string
     */
    protected function getHost()
    {
        $host = $this->getConfigComponent()->getConfig('connectDebugHost', 'connect.shopware.com');
        if ($host) {
            $host = $this->getConfigComponent()->getSocialNetworkPrefix() . $host;
        } else {
            $host = $this->getConfigComponent()->getSocialNetworkPrefix() . $this->getConfigComponent()->getMarketplaceUrl();
        }

        return $host;
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

    private function getPriceGateway()
    {
        if (!$this->priceGateway) {
            $this->priceGateway = new \ShopwarePlugins\Connect\Components\PriceGateway(
                Shopware()->Db()
            );
        }

        return $this->priceGateway;
    }

    private function getSnHttpClient()
    {
        if (!$this->snHttpClient) {
            $this->snHttpClient = new SnHttpClient(
                $this->get('http_client'),
                new \Shopware\Connect\Gateway\PDO(Shopware()->Db()->getConnection()),
                $this->getConfigComponent()
            );
        }

        return $this->snHttpClient;
    }
}