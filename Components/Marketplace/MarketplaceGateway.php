<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components\Marketplace;

use Shopware\Components\Model\ModelManager;
use Shopware\CustomModels\Connect\MarketplaceAttribute;

/**
 * @category  Shopware
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
        array_walk($attributes, function ($attribute) {
            $mappingModel = $this->getMarketplaceAttributeRepository()->findOneBy(
                [
                    'marketplaceAttribute' => $attribute['attributeKey'],
                ]
            );

            if (!$mappingModel) {
                $mappingModel = $this->getMarketplaceAttributeRepository()->findOneBy(
                    [
                        'localAttribute' => $attribute['shopwareAttributeKey'],
                    ]
                );
            }

            if (!$mappingModel) {
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
        return array_map(function ($mapping) {
            return [
                'shopwareAttributeKey' => $mapping->getLocalAttribute(),
                'attributeKey' => $mapping->getMarketplaceAttribute(),
            ];
        }, $this->getMarketplaceAttributeRepository()->findAll());
    }

    /**
     * Return marketplace product attribute name
     *
     * @param $shopwareAttributeName
     */
    public function findMarketplaceMappingFor($shopwareAttributeName)
    {
        $mappingModel = $this->getMarketplaceAttributeRepository()->findOneBy(
            [
                'localAttribute' => $shopwareAttributeName,
            ]
        );

        if (!$mappingModel) {
            return null;
        }

        return $mappingModel->getMarketplaceAttribute();
    }

    /**
     * Returns shopware product attribute name
     *
     * @param $marketplaceAttribute
     */
    public function findShopwareMappingFor($marketplaceAttribute)
    {
        $mappingModel = $this->getMarketplaceAttributeRepository()->findOneBy(
            [
                'marketplaceAttribute' => $marketplaceAttribute,
            ]
        );

        if (!$mappingModel) {
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
