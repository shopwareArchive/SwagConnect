<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Gateway;

use Bepado\SDK\Gateway;
use Bepado\SDK\Struct;
use Bepado\SDK\Struct\Order;
use Bepado\SDK\Struct\Product;
use Bepado\SDK\ShippingCosts\Rules;

/**
 * Abstract base class to store SDK related data
 *
 * You may create custom extensions of this class, if the default data stores
 * do not work for you.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 * @api
 */
class InMemory extends Gateway
{
    protected $products = array();
    protected $changes = array();
    protected $lastRevision;
    protected $shopConfiguration = array();
    protected $shopId = null;
    protected $lastVerificationDate = null;
    protected $categories = array();
    protected $categoriesLastRevision = null;
    protected $reservations = array();
    protected $shippingCosts = array();
    protected $shippingCostsRevision;
    protected $features = array();
    protected $config = array();
    protected $billingAddress;

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
     * @return \Bepado\SDK\Struct\Change[]
     */
    public function getNextChanges($offset, $limit)
    {
        $record = $offset === null;
        $changes = array();
        $i = 0;
        foreach ($this->changes as $revision => $data) {
            if (strcmp($revision, $offset) > 0) {
                $record = true;
            }

            if (!$record || $revision === $offset) {
                continue;
            }

            if ($i >= $limit) {
                break;
            }

            $changes[] = $this->createChange($data);
            $i++;
        }

        return $changes;
    }

    public function cleanChangesUntil($offset)
    {
        $record = $offset === null;

        foreach ($this->changes as $revision => $data) {
            if (strcmp($revision, $offset) > 0) {
                $record = true;
            }

            if (!$record || $revision === $offset) {
                unset($this->changes[$revision]);
            }
        }
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
        $record = $offset === null;
        $changes = array();
        $i = 0;
        $count = 0;
        foreach ($this->changes as $revision => $data) {
            if (strcmp($revision, $offset) > 0) {
                $record = true;
            }

            if (!$record || $revision === $offset) {
                continue;
            }

            if ($i >= $limit) {
                $count++;
            }

            $i++;
        }

        return $count;
    }

    private function createChange(array $data)
    {
        switch ($data['type']) {
            case 'delete':
                $class = '\\Bepado\\SDK\\Struct\\Change\\FromShop\\Delete';
                break;
            case 'insert':
                $class = '\\Bepado\\SDK\\Struct\\Change\\FromShop\\Insert';
                break;
            case 'update':
                $class = '\\Bepado\\SDK\\Struct\\Change\\FromShop\\Update';
                break;
            case 'stock':
                $class = '\\Bepado\\SDK\\Struct\\Change\\FromShop\\Availability';
                $data['availability'] = intval($data['product']->availability);
                unset($data['product']);
                break;
        }

        unset($data['type']);

        return new $class($data);
    }

    /**
     * Record product insert
     *
     * @param string $id
     * @param string $hash
     * @param string $revision
     * @param \Bepado\SDK\Struct\Product $product
     * @return void
     */
    public function recordInsert($id, $hash, $revision, Product $product)
    {
        $this->checkRevisionExists($revision);

        $this->changes[$revision] = array(
            'type'     => 'insert',
            'sourceId' => $id,
            'revision' => $revision,
            'product'  => $product
        );
        $this->products[$id] = $hash;
    }

    /**
     * Record product update
     *
     * @param string $id
     * @param string $hash
     * @param string $revision
     * @param \Bepado\SDK\Struct\Product $product
     * @return void
     */
    public function recordUpdate($id, $hash, $revision, Product $product)
    {
        $this->checkRevisionExists($revision);

        $this->changes[$revision] = array(
            'type'     => 'update',
            'sourceId' => $id,
            'revision' => $revision,
            'product'  => $product
        );
        $this->products[$id] = $hash;
    }

    /**
     * Record product availability update
     *
     * @param string $id
     * @param string $hash
     * @param string $revision
     * @param \Bepado\SDK\Struct\Product $product
     * @return void
     */
    public function recordAvailabilityUpdate($id, $hash, $revision, Product $product)
    {
        $this->checkRevisionExists($revision);

        $this->changes[$revision] = array(
            'type'     => 'stock',
            'sourceId' => $id,
            'revision' => $revision,
            'product'  => $product
        );
        $this->products[$id] = $hash;
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
        $this->checkRevisionExists($revision);

        $this->changes[$revision] = array(
            'type'     => 'delete',
            'sourceId' => $id,
            'revision' => $revision
        );
        unset($this->products[$id]);
    }

    /**
     * @param string $revision
     * @throws \InvalidArgumentException
     */
    private function checkRevisionExists($revision)
    {
        if (isset($this->changes[$revision])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Revision %s already exists for shop %s.',
                    $revision,
                    $this->shopId
                )
            );
        }
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
        return $this->products[$id] !== $hash;
    }

    /**
     * Get Ids of all recorded products
     *
     * @return string[]
     */
    public function getAllProductIds()
    {
        return array_keys($this->products);
    }

    /**
     * Get last processed import revision
     *
     * @return string
     */
    public function getLastRevision()
    {
        return $this->lastRevision;
    }

    /**
     * Store last processed import revision
     *
     * @param string $revision
     * @return void
     */
    public function storeLastRevision($revision)
    {
        $this->lastRevision = $revision;
    }

    /**
     * Update shop configuration
     *
     * @param string $shopId
     * @param \Bepado\SDK\Struct\ShopConfiguration $configuration
     * @return void
     */
    public function setShopConfiguration($shopId, Struct\ShopConfiguration $configuration)
    {
        $this->shopConfiguration[$shopId] = $configuration;
    }

    /**
     * Get configuration for the given shop
     *
     * @param string $shopId
     * @throws \RuntimeException If shop does not exist in configuration.
     * @return \Bepado\SDK\Struct\ShopConfiguration
     */
    public function getShopConfiguration($shopId)
    {
        if (!isset($this->shopConfiguration[$shopId])) {
            throw new \RuntimeException(
                sprintf(
                    'You are not connected to shop %s. Known shops are: %s.',
                    $shopId,
                    implode(", ", array_keys($this->shopConfiguration))
                )
            );
        }

        return $this->shopConfiguration[$shopId];
    }

    /**
     * Set category mapping
     *
     * @param array $categories
     * @return void
     */
    public function setCategories(array $categories)
    {
        $this->categories = $categories;
    }

    /**
     * Get category mapping
     *
     * @return array
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Set own shop ID
     *
     * @param string $shopId
     * @return void
     */
    public function setShopId($shopId)
    {
        $this->shopId = $shopId;
        $this->lastVerificationDate = time();
    }

    /**
     * Get own shop ID
     *
     * @return string
     */
    public function getShopId()
    {
        return $this->shopId ?: false;
    }

    /**
     * Get all connected shop ids.
     *
     * @return array<string>
     */
    public function getConnectedShopIds()
    {
        return array_keys($this->shopConfiguration);
    }

    /**
     * Get last shop verification date as Unix UTC timestamp
     *
     * @return int
     */
    public function getLastVerificationDate()
    {
        return $this->lastVerificationDate;
    }

    /**
     * Create and store reservation
     *
     * Returns the reservation Id
     *
     * @param \Bepado\SDK\Struct\Order $order
     * @return string
     */
    public function createReservation(Order $order)
    {
        $reservationId = md5(microtime());
        $this->reservations[$reservationId] = array(
            'order' => $order,
            'state' => 'new',
        );

        return $reservationId;
    }

    /**
     * Get order for reservation Id
     *
     * @param string $reservationId
     * @return \Bepado\SDK\Struct\Order
     */
    public function getOrder($reservationId)
    {
        if (!isset($this->reservations[$reservationId])) {
            throw new \RuntimeException("Unknown reservation '$reservationId'");
        }

        return $this->reservations[$reservationId]['order'];
    }

    /**
     * Set reservation as bought
     *
     * @param string $reservationId
     * @param \Bepado\SDK\Struct\Order $order
     * @return void
     */
    public function setBought($reservationId, Order $order)
    {
        if (!isset($this->reservations[$reservationId])) {
            throw new \RuntimeException("Unknown reservation $reservationId");
        }

        $this->reservations[$reservationId]['order'] = $order;
        $this->reservations[$reservationId]['state'] = 'bought';
    }

    /**
     * Set reservation as confirmed
     *
     * @param string $reservationId
     * @return void
     */
    public function setConfirmed($reservationId)
    {
        if (!isset($this->reservations[$reservationId])) {
            throw new \RuntimeException("Unknown reservation $reservationId");
        }

        $this->reservations[$reservationId]['state'] = 'confirmed';
    }

    /**
     * Get last revision
     *
     * @return string
     */
    public function getLastShippingCostsRevision()
    {
        return $this->shippingCostsRevision;
    }

    /**
     * Store shop shipping costs
     *
     * @param string $fromShop
     * @param string $toShop
     * @param string $revision
     * @param \Bepado\SDK\ShippingCosts\Rules $intershopCosts
     * @param \Bepado\SDK\ShippingCosts\Rules $customerCosts
     * @return void
     */
    public function storeShippingCosts($fromShop, $toShop, $revision, Rules $intershopCosts, Rules $customerCosts)
    {
        $this->shippingCostsRevision = max($this->shippingCostsRevision, $revision);
        $this->shippingCosts[$fromShop][$toShop][self::SHIPPING_COSTS_INTERSHOP] = $intershopCosts;
        $this->shippingCosts[$fromShop][$toShop][self::SHIPPING_COSTS_CUSTOMER] = $customerCosts;
    }

    /**
     * Get shop shipping costs
     *
     * @param string $fromShop
     * @param string $toShop
     * @return \Bepado\SDK\ShippingCosts\Rules
     */
    public function getShippingCosts($fromShop, $toShop, $type = self::SHIPPING_COSTS_INTERSHOP)
    {
        if (!isset($this->shippingCosts[$fromShop][$toShop])) {
            $pairs = array_map(function ($fromShop) {
                return $fromShop . ": " . implode(", ", array_keys($this->shippingCosts[$fromShop]));
            }, array_keys($this->shippingCosts));

            throw new \RuntimeException("Unknown shops $fromShop-$toShop, knowing " . implode(', ', $pairs));
        }

        return $this->shippingCosts[$fromShop][$toShop][$type];
    }

    /**
     * Restores an in-memory gateway from a previously stored state array.
     *
     * @param array $state
     * @return \Bepado\SDK\Gateway\InMemory
     */
    public static function __set_state(array $state)
    {
        $gateway = new InMemory();
        $gateway->setInternalState($state);
        return $gateway;
    }

    protected function setInternalState(array $state)
    {
        foreach ($state as $name => $value) {
            $this->$name = $value;
        }
    }

    /**
     * Set all the enabled features.
     *
     * @param array<string>
     */
    public function setEnabledFeatures(array $features)
    {
        $this->features = array_map('strtolower', $features);
    }

    /**
     * Is a feature enabled?
     *
     * @param string $featureName
     * @return bool
     */
    public function isFeatureEnabled($feature)
    {
        return in_array(strtolower($feature), $this->features);
    }

    /**
     * Set the last revision of the category tree that the SDK has seen.
     *
     * @param string
     * @return void
     */
    public function setCategoriesLastRevision($revision)
    {
        $this->categoriesLastRevision = $revision;
    }

    /**
     * Get the last revision of the category tree that the SDK has seen.
     *
     * @return string
     */
    public function getCategoriesLastRevision()
    {
        return $this->categoriesLastRevision;
    }

    /**
     * Set the shops billing address used in orders.
     *
     * @param \Bepado\SDK\Struct\Address $address
     */
    public function setBillingAddress(Struct\Address $address)
    {
        $this->billingAddress = $address;
    }

    /**
     * @return \Bepado\SDK\Struct\Address
     */
    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    public function setConfig($shopId, $config)
    {
        $this->config[$shopId] = $config;
    }

    public function getConfig($shopId)
    {
        return isset($this->config[$shopId])
            ? $this->config[$shopId]
            : null;
    }
}
