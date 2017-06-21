<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components;

use Shopware\Bundle\StoreFrontBundle\Struct;

interface MediaService
{
    /**
     * Wrapper method of Shopware Core ProductMediaGateway
     *
     * @see \Shopware\Bundle\StoreFrontBundle\Gateway\ProductMediaGatewayInterface::getList()
     *
     * @param Struct\BaseProduct[]        $products
     * @param Struct\ShopContextInterface $context
     *
     * @return array Indexed by the product order number. Each element contains a \Shopware\Bundle\StoreFrontBundle\Struct\Media array.
     */
    public function getProductMediaList(array $products, Struct\ShopContextInterface $context);

    /**
     * Wrapper method of Shopware Core VariantMediaGateway
     *
     * @see \Shopware\Bundle\StoreFrontBundle\Gateway\VariantMediaGatewayInterface::getList()
     *
     * @param Struct\BaseProduct[]        $products
     * @param Struct\ShopContextInterface $context
     *
     * @return array Indexed by product number. Each element contains a \Shopware\Bundle\StoreFrontBundle\Struct\Media array.
     */
    public function getVariantMediaList(array $products, Struct\ShopContextInterface $context);

    /**
     * Wrapper method of Shopware Core MediaService
     *
     * @see \Shopware\Bundle\StoreFrontBundle\Service\Core\MediaService::getProductsMedia()
     *
     * @param Struct\BaseProduct[]        $products
     * @param Struct\ShopContextInterface $context
     *
     * @return Struct\Media[]
     */
    public function getProductsMedia($products, Struct\ShopContextInterface $context);

    /**
     * Wrapper method of Shopware Core MediaService
     *
     * @see \Shopware\Bundle\StoreFrontBundle\Service\Core\MediaService::getProductMedia()
     *
     * @param Struct\BaseProduct          $product
     * @param Struct\ShopContextInterface $context
     *
     * @return Struct\Media[]
     */
    public function getProductMedia($product, Struct\ShopContextInterface $context);
}
