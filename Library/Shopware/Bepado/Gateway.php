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

namespace Shopware\Bepado;
use Bepado\SDK\Struct\Order,
    Bepado\SDK\Struct\ShopConfiguration,
    Bepado\SDK\Struct\Product,
    Bepado\SDK\Struct\Change;

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagBepado
 * @copyright Copyright (c) 2013, shopware AG (http://www.shopware.de)
 * @author    Heiner Lohaus
 */
class Gateway extends\Bepado\SDK\Gateway
{

    /**
     * @var \Shopware\Components\Model\ModelManager
     */
    protected $manager;

    public function __constructor()
    {
        $this->manager = \Shopware()->Models();
    }

    /**
     * Get next changes
     *
     * The offset specified the revision to start from
     *
     * May remove all pending changes, which are prior to the last requested
     * revision.
     *
     * @param string $offset
     * @param int $limit
     * @return Change[]
     */
    public function getNextChanges($offset, $limit)
    {
        $repository = $this->manager->getRepository(
            'Shopware\Models\Article\Article'
        );
        $builder = $repository->createQueryBuilder('a');
        $builder->select(array(
            'a.id as sourceId',
            'a.changed as revision'
        ));
        $builder->where('changed >= ?');
        $builder->setParameters(array(
            $offset
        ));
        $builder->setMaxResults($limit);

        $query = $builder->getQuery();
        $result = $query->getArrayResult();
        array_map(function($row) {
            //return new Change($row);
        }, $result);

        return $result;
    }

    /**
     * Get unprocessed changes count
     *
     * The offset specified the revision to start from
     *
     * @param string $offset
     * @param int $limit
     * @return int
     */
    public function getUnprocessedChangesCount($offset, $limit)
    {

    }

    /**
     * Record product insert
     *
     * @param string $id
     * @param string $hash
     * @param string $revision
     * @param Product $product
     * @return void
     */
    public function recordInsert($id, $hash, $revision, Product $product)
    {

    }

    /**
     * Record product update
     *
     * @param string $id
     * @param string $hash
     * @param string $revision
     * @param Product $product
     * @return void
     */
    public function recordUpdate($id, $hash, $revision, Product $product)
    {

    }

    /**
     * Record product delete
     *
     * @param string $id
     * @param string $revision
     * @return void
     */
    public function recordDelete($id, $revision)
    {

    }

    /**
     * Check if product has changed
     *
     * Return true, if product chenged since last check.
     *
     * @param string $id
     * @param string $hash
     * @return boolean
     */
    public function hasChanged($id, $hash)
    {

    }

    /**
     * Get IDs of all recorded products
     *
     * @return string[]
     */
    public function getAllProductIDs()
    {

    }

    /**
     * Create and store reservation
     *
     * Returns the reservation Id
     *
     * @param Order $order
     * @return string
     */
    public function createReservation(Order $order)
    {

    }

    /**
     * Get order for reservation Id
     *
     * @param string $reservationId
     * @return Order
     */
    public function getOrder($reservationId)
    {

        return new Order();
    }

    /**
     * Set reservation as bought
     *
     * @param string $reservationId
     * @param Order $order
     * @return void
     */
    public function setBought($reservationId, Order $order)
    {

    }

    /**
     * Set reservation as confirmed
     *
     * @param string $reservationId
     * @return void
     */
    public function setConfirmed($reservationId)
    {

    }

    /**
     * Get last processed import revision
     *
     * @return string
     */
    public function getLastRevision()
    {

    }

    /**
     * Store last processed import revision
     *
     * @param string $revision
     * @return void
     */
    public function storeLastRevision($revision)
    {

    }

    /**
     * Update shop configuration
     *
     * @param string $shopId
     * @param ShopConfiguration $configuration
     * @return void
     */
    public function setShopConfiguration($shopId, ShopConfiguration $configuration)
    {
        $shop = $this->manager->find(
            'Shopware\Models\Shop\Shop',
            $shopId
        );
    }

    /**
     * Get configuration for the given shop
     *
     * @param string $shopId
     * @return ShopConfiguration
     */
    public function getShopConfiguration($shopId)
    {
        return new ShopConfiguration(array(
            'name' => '',
            'serviceEndpoint' => ''
        ));
    }

    /**
     * Set category mapping
     *
     * @param array $categories
     * @return void
     */
    public function setCategories(array $categories)
    {

    }

    /**
     * Get category mapping
     *
     * @return array
     */
    public function getCategories()
    {

    }

    /**
     * Set own shop ID
     *
     * @param string $shopId
     * @return void
     */
    public function setShopId($shopId)
    {

    }

    /**
     * Get last shop verification date as Unix UTC timestamp
     *
     * @return int
     */
    public function getLastVerificationDate()
    {

    }

    /**
     * Get own shop ID
     *
     * @return string
     */
    public function getShopId()
    {

    }
}