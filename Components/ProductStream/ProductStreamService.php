<?php

namespace ShopwarePlugins\Connect\Components\ProductStream;

use Shopware\Models\ProductStream\ProductStream;

class ProductStreamService
{
    const DYNAMIC_STREAM = 1;
    const STATIC_STREAM = 2;

    /**
     * @var ProductStreamRepository
     */
    private $productStreamQuery;

    /**
     * ProductStreamService constructor.
     * @param ProductStreamRepository $productStreamQuery
     */
    public function __construct(ProductStreamRepository $productStreamQuery)
    {

        $this->productStreamQuery = $productStreamQuery;
    }

    /**
     * @param array $streamIds
     * @return array
     */
    public function getArticlesIds(array $streamIds)
    {
        $sourceIds = array();

        $streams = $this->productStreamQuery->findByIds($streamIds);

        foreach ($streams as $stream) {
            if ($this->isStatic($stream)) {
                $sourceIds = array_merge($sourceIds, $this->extractSourceIdsFromStaticStream($stream));
            } else {
                //todo: extract product from dynamic stream
            }
        }

        return array_unique($sourceIds);
    }

    /**
     * @param ProductStream $productStream
     * @return bool
     */
    public function isStatic(ProductStream $productStream)
    {
        if ($productStream->getType() == self::STATIC_STREAM) {
            return true;
        }

        return false;
    }

    public function extractSourceIdsFromStaticStream(ProductStream $productStream)
    {
        return $this->productStreamQuery->fetchArticlesIds($productStream->getId());
    }
}