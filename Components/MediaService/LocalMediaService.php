<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components\MediaService;

use Shopware\Bundle\StoreFrontBundle\Struct;
use Shopware\Bundle\StoreFrontBundle\Gateway;
use Shopware\Bundle\StoreFrontBundle\Service\Core\MediaService as CoreMediaService;
use ShopwarePlugins\Connect\Components\MediaService;

class LocalMediaService implements MediaService
{
    private $productMediaGateway;
    private $variantMediaGateway;
    private $coreMediaService;

    public function __construct(
        Gateway\ProductMediaGatewayInterface $productMedia,
        Gateway\VariantMediaGatewayInterface $variantMedia,
        CoreMediaService $coreMediaService
    ) {
        $this->productMediaGateway = $productMedia;
        $this->variantMediaGateway = $variantMedia;
        $this->coreMediaService = $coreMediaService;
    }

    /**
     * @inheritdoc
     */
    public function getProductMediaList(array $products, Struct\ShopContextInterface $context)
    {
        return $this->productMediaGateway->getList($products, $context);
    }

    /**
     * @inheritdoc
     */
    public function getVariantMediaList(array $products, Struct\ShopContextInterface $context)
    {
        return $this->variantMediaGateway->getList($products, $context);
    }

    /**
     * @inheritdoc
     */
    public function getProductsMedia($products, Struct\ShopContextInterface $context)
    {
        return $this->coreMediaService->getProductsMedia($products, $context);
    }

    /**
     * @inheritdoc
     */
    public function getProductMedia($product, Struct\ShopContextInterface $context)
    {
        return $this->coreMediaService->getProductMedia($product, $context);
    }
}
