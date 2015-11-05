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
namespace Shopware\Connect\Components\Marketplace;

use Shopware\Components\Model\ModelManager;
use Shopware\CustomModels\Connect\MarketplaceAttribute;

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagConnect
 */
class MarketplaceGateway
{
    /**
     * @var ModelManager
     */
    private $manager;

    /**
     * @var \Shopware\CustomModels\Connect\MarketplaceAttributeRepository
     */
    private $marketplaceAttributeRepository;

    /**
     * @param ModelManager $manager
     */
    public function __construct($manager)
    {
        $this->manager = $manager;
    }

    /**
     * Stores mappings to DB
     *
     * @param array $attributes
     */
    public function setMarketplaceMapping(array $attributes)
    {
        array_walk($attributes, function($attribute) {
            $mappingModel = $this->getMarketplaceAttributeRepository()->findOneBy(
                array(
                    'marketplaceAttribute' => $attribute['attributeKey'],
                )
            );

            if (! $mappingModel) {
                $mappingModel = $this->getMarketplaceAttributeRepository()->findOneBy(
                    array(
                        'localAttribute' => $attribute['shopwareAttributeKey'],
                    )
                );
            }

            if (! $mappingModel) {
                $mappingModel = new MarketplaceAttribute();
            }

            if (strlen($attribute['attributeKey']) > 0 && strlen($attribute['shopwareAttributeKey']) > 0) {
                $mappingModel->setMarketplaceAttribute($attribute['attributeKey']);
                $mappingModel->setLocalAttribute($attribute['shopwareAttributeKey']);
                $this->manager->persist($mappingModel);
            } else {
                $this->manager->remove($mappingModel);
            }
        });

        $this->manager->flush();
    }

    /**
     * Returns all mappings as array
     *
     * @return array
     */
    public function getMappings()
    {
        return array_map(function($mapping) {
            return array(
                'shopwareAttributeKey' => $mapping->getLocalAttribute(),
                'attributeKey' => $mapping->getMarketplaceAttribute(),
            );
        }, $this->getMarketplaceAttributeRepository()->findAll());
    }

    /**
     * Return marketplace product attribute name
     *
     * @param $shopwareAttributeName
     * @return null
     */
    public function findMarketplaceMappingFor($shopwareAttributeName)
    {
        $mappingModel = $this->getMarketplaceAttributeRepository()->findOneBy(
            array(
                'localAttribute' => $shopwareAttributeName,
            )
        );

        if (! $mappingModel) {
            return null;
        }

        return $mappingModel->getMarketplaceAttribute();
    }

    /**
     * Returns shopware product attribute name
     *
     * @param $marketplaceAttribute
     * @return null
     */
    public function findShopwareMappingFor($marketplaceAttribute)
    {
        $mappingModel = $this->getMarketplaceAttributeRepository()->findOneBy(
            array(
                'marketplaceAttribute' => $marketplaceAttribute,
            )
        );

        if (! $mappingModel) {
            return null;
        }

        return $mappingModel->getLocalAttribute();
    }

    /**
     * @return \Shopware\CustomModels\Connect\MarketplaceAttributeRepository
     */
    private function getMarketplaceAttributeRepository()
    {
        if (!$this->marketplaceAttributeRepository) {
            $this->marketplaceAttributeRepository = $this->manager->getRepository('Shopware\CustomModels\Connect\MarketplaceAttribute');
        }

        return $this->marketplaceAttributeRepository;
    }
} 