<?php

namespace ShopwarePlugins\Connect\Components\ProductStream;

use Shopware\Models\ProductStream\ProductStream;
use Shopware\Bundle\SearchBundle\ProductSearchResult;
use Shopware\Bundle\SearchBundle\Criteria;
use Shopware\Bundle\SearchBundle\Condition\CustomerGroupCondition;
use Shopware\Bundle\SearchBundle\Condition\CategoryCondition;
use Shopware\Bundle\SearchBundle\ProductSearchInterface;
use Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface;
use ShopwarePlugins\Connect\Components\Config;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContext;
use Shopware\Components\ProductStream\Repository as StreamRepository;

class ProductSearch
{
    /**
     * @var StreamRepository
     */
    private $productStreamRepository;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ProductSearchInterface
     */
    private $productSearchService;

    /**
     * @var ContextServiceInterface
     */
    private $contextService;

    public function __construct(
        StreamRepository $productStreamRepository,
        Config $config,
        ProductSearchInterface $productSearchService,
        ContextServiceInterface $contextService
    )
    {
        $this->productStreamRepository = $productStreamRepository;
        $this->config = $config;
        $this->productSearchService = $productSearchService;
        $this->contextService = $contextService;
    }

    /**
     * @param ProductStream $stream
     * @return ProductSearchResult
     */
    public function getProductFromConditionStream(ProductStream $stream)
    {
        $criteria = new Criteria();

        $conditions = json_decode($stream->getConditions(), true);
        $conditions = $this->productStreamRepository->unserialize($conditions);

        foreach ($conditions as $condition) {
            $criteria->addCondition($condition);
        }

        $sorting = json_decode($stream->getSorting(), true);
        $sorting = $this->productStreamRepository->unserialize($sorting);

        foreach ($sorting as $sort) {
            $criteria->addSorting($sort);
        }

        /** @var ShopContext $context */
        $context = $this->contextService->createShopContext($this->config->getDefaultShopId());

        $criteria->addBaseCondition(
            new CustomerGroupCondition([$context->getCurrentCustomerGroup()->getId()])
        );

        $criteria->addBaseCondition(
            new CategoryCondition([$context->getShop()->getCategory()->getId()])
        );

        return $this->productSearchService->search($criteria, $context);
    }
}