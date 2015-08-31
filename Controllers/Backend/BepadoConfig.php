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

use Shopware\Bepado\Components\Config;
use Bepado\SDK\Units;
use Shopware\Bepado\Components\BepadoExport;
use Shopware\Bepado\Components\Validator\ProductAttributesValidator\ProductsAttributesValidator;

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagBepado
 * @copyright Copyright (c) 2013, shopware AG (http://www.shopware.de)
 */
class Shopware_Controllers_Backend_BepadoConfig extends Shopware_Controllers_Backend_ExtJs
{

    /** @var  \Shopware\Bepado\Components\Config */
    private $configComponent;

    /**
     * @var \Shopware\Bepado\Components\BepadoFactory
     */
    private $factory;

    /**
     * The getGeneralAction function is an ExtJs event listener method of the
     * bepado module. The function is used to load store
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
     * bepado module. The function is used to save store data.
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

    /**
     * The getImportAction function is an ExtJs event listener method of the
     * bepado module. The function is used to load store
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
     * bepado module. The function is used to save store data.
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
     * bepado module. The function is used to load store
     * required in the export config form.
     * @return string
     */
    public function getExportAction()
    {
        $exportConfigArray = $this->getConfigComponent()->getExportConfig();

        $this->View()->assign(
            array(
                'success' => true,
                'data' => $exportConfigArray
            )
        );
    }

    /**
     * ExtJS uses this action to check is price mapping allowed.
     * If there is at least one exported product to bepado,
     * price mapping cannot be changed.
     */
    public function isPricingMappingAllowedAction()
    {
        $this->View()->assign(
            array(
                'success' => true,
                'isPricingMappingAllowed' => !count($this->getBepadoExport()->getExportArticlesIds()) > 0
            )
        );
    }

    /**
     * The saveExportAction function is an ExtJs event listener method of the
     * bepado module. The function is used to save store data.
     * @return string
     */
    public function saveExportAction()
    {
        $data = $this->Request()->getParam('data');
        $data = !isset($data[0]) ? array($data) : $data;

        $isModified = $this->getConfigComponent()->compareExportConfiguration($data);
        $this->getConfigComponent()->setExportConfigs($data);

        if ($isModified === true) {
            $bepadoExport = $this->getBepadoExport();
            try {
                $ids = $bepadoExport->getExportArticlesIds();
                $sourceIds = $this->getHelper()->getArticleSourceIds($ids);
                $errors = $bepadoExport->export($sourceIds);
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
     * @return BepadoExport
     */
    public function getBepadoExport()
    {
        return new BepadoExport(
            $this->getHelper(),
            $this->getSDK(),
            $this->getModelManager(),
            new ProductsAttributesValidator()
        );
    }

    /**
     * @return \Shopware\Bepado\Components\Helper
     */
    public function getHelper()
    {
        if ($this->factory === null) {
            $this->factory = new \Shopware\Bepado\Components\BepadoFactory();
        }

        return $this->factory->getHelper();
    }

    /**
     * @return \Bepado\SDK\SDK
     */
    public function getSDK()
    {
        return Shopware()->Bootstrap()->getResource('BepadoSDK');
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
     * bepado module. The function is used to load store
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
     * @return \Shopware\Bepado\Components\Config
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
     * bepado module. The function is used to load store
     * required in the units mapping.
     * @return string
     */
    public function getUnitsAction()
    {
        $repository = Shopware()->Models()->getRepository('Shopware\Models\Article\Unit');
        $units = $repository->findAll();

        $bepadoUnits = new Units();

        $unitsMappingArray = array();
        foreach ($units as $unit) {
            $unitsMappingArray[] = array(
                'shopwareUnitName' => $unit->getName(),
                'shopwareUnitKey' => $unit->getUnit(),
                'bepadoUnit' => $this->getConfigComponent()->getConfig($unit->getUnit())
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
     * bepado module. The function is used to save units store data.
     * @return string
     */
    public function saveUnitsMappingAction()
    {
        $data = $this->Request()->getParam('data');
        $data = !isset($data[0]) ? array($data) : $data;

        $this->getConfigComponent()->setUnitsMapping($data);

        $this->View()->assign(
            array(
                'success' => true
            )
        );
    }

    /**
     * The getBepadoUnitsAction function is an ExtJs event listener method of the
     * bepado module. The function is used to load store
     * required in the units mapping.
     * @return string
     */
    public function getBepadoUnitsAction()
    {
        $bepadoUnits = new Units();

        $unitsArray = array();

        foreach ($bepadoUnits->getLocalizedUnits() as $key => $bepadoUnit) {
            $unitsArray[] = array(
                'key' => $key,
                'name' => $bepadoUnit
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
        $changes = $this->getBepadoExport()->getChangesCount();
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
            // bepado plugin is not configured and tries to
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
                        return $attribute->getName() != 'bepadoProductDescription';
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
     * @return \Shopware\Bepado\Components\BepadoFactory
     */
    public function getFactory()
    {
        if ($this->factory === null) {
            $this->factory = new \Shopware\Bepado\Components\BepadoFactory();
        }

        return $this->factory;
    }
} 