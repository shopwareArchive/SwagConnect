<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Gateway;

use Shopware\Connect\Gateway;
use Shopware\Connect\Struct;
use Shopware\Connect\ShippingCosts\Rules;

/**
 * PDO implementation of the storage gateway
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
class PDO extends Gateway
{
    /**
     * MySQLi connection
     *
     * @var \PDO
     */
    protected $connection;

    /**
     * Struct classes used for operations
     *
     * @var array
     */
    protected $operationStruct = array(
        self::PRODUCT_INSERT => '\\Shopware\\Connect\\Struct\\Change\\FromShop\\Insert',
        self::PRODUCT_UPDATE => '\\Shopware\\Connect\\Struct\\Change\\FromShop\\Update',
        self::PRODUCT_DELETE => '\\Shopware\\Connect\\Struct\\Change\\FromShop\\Delete',
        self::PRODUCT_STOCK => '\\Shopware\\Connect\\Struct\\Change\\FromShop\\Availability',
        self::STREAM_ASSIGNMENT => '\\Shopware\\Connect\\Struct\\Change\\FromShop\\StreamAssignment',
        self::STREAM_DELETE => '\\Shopware\\Connect\\Struct\\Change\\FromShop\\StreamDelete',
        self::MAIN_VARIANT => '\\Shopware\\Connect\\Struct\\Change\\FromShop\\MakeMainVariant',
        self::PAYMENT_UPDATE => '\\Shopware\\Connect\\Struct\\Change\\FromShop\\UpdatePaymentStatus',
    );

    /**
     * @var array
     */
    protected $types = array(
        self::TYPE_PRODUCT => array(
            self::PRODUCT_INSERT,
            self::PRODUCT_UPDATE,
            self::PRODUCT_DELETE,
            self::PRODUCT_STOCK,
            self::STREAM_ASSIGNMENT,
            self::STREAM_DELETE,
            self::MAIN_VARIANT,
        ),
        self::TYPE_PAYMENT => array(
            self::PAYMENT_UPDATE
        ),
    );

    /**
     * Construct from MySQL connection
     *
     * @param \PDO $connection
     */
    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Returns the full table name with $suffix.
     *
     * @param string $suffix
     * @return string
     */
    protected function tableName($suffix)
    {
        return 'sw_connect_' . $suffix;
    }

    /**
     * @param $offset
     * @param $limit
     * @param array $types
     * @return array
     */
    protected function doNextChange($offset, $limit, array $types)
    {
        $offset = $offset ?: 0;
        // Float type cast does NOT work here, since the inaccuracy of floating
        // point representations otherwise omit changes. Yes, this actually
        // really happens.
        if (!preg_match('(^[\\d\\.]+$)', $offset)) {
            throw new \InvalidArgumentException("Offset revision must be a numeric string.");
        }

        $inStatement = implode("','", $types);

        $result = $this->connection->query(
            "SELECT
                `c_entity_id`,
                `c_operation`,
                `c_revision`,
                `c_payload`
            FROM
                `" . $this->tableName('change') . "`
            WHERE
                `c_revision` > $offset
            AND
                `c_operation` IN('$inStatement')
            ORDER BY `c_revision` ASC
            LIMIT
                " . ((int) $limit)
        );

        $changes = array();
        while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {
            $class = $this->operationStruct[$row['c_operation']];
            $changes[] = $change = new $class(
                array(
                    'sourceId' => $row['c_entity_id'],
                    'revision' => $row['c_revision'],
                )
            );

            if ($row['c_payload'] !== null) {
                switch($row['c_operation']) {
                    case self::PRODUCT_STOCK:
                        $change->availability = intval($row['c_payload']);
                        break;
                    case self::STREAM_ASSIGNMENT:
                        $payload = unserialize($row['c_payload']);
                        $change->supplierStreams = $payload['supplierStreams'];
                        $change->groupId = $payload['groupId'];
                        break;
                    case self::PAYMENT_UPDATE:
                        $change->paymentStatus = unserialize($row['c_payload']);
                        break;
                    case self::MAIN_VARIANT:
                        $payload = unserialize($row['c_payload']);
                        $change->groupId = $payload['groupId'];
                        break;
                    default:
                        $change->product = unserialize($row['c_payload']);
                }
            }
        }

        return $changes;
    }

    /**
     * Get next change
     *
     * The offset specified the revision to start from
     *
     * May remove all pending changes, which are prior to the last requested
     * revision.
     *
     * @param string $offset
     * @param int $limit
     * @throws \InvalidArgumentException
     * @return Struct\Change[]
     */
    public function getNextChanges($offset, $limit)
    {
        return $this->doNextChange($offset, $limit, $this->types[self::TYPE_PRODUCT]);
    }

    /**
     * @param $offset
     * @param $limit
     * @return Struct\Change[]
     */
    public function getNextPaymentStatusChanges($offset, $limit)
    {
        return $this->doNextChange($offset, $limit, $this->types[self::TYPE_PAYMENT]);
    }

    public function cleanChangesUntil($offset)
    {
        $offset = $offset ?: 0;
        // Float type cast does NOT work here, since the inaccuracy of floating
        // point representations otherwise omit changes. Yes, this actually
        // really happens.
        if (!preg_match('(^[\\d\\.]+$)', $offset)) {
            throw new \InvalidArgumentException("Offset revision must be a numeric string.");
        }

        // Disable cleanup for the first betas for debuggability and easier re-runs.
        $this->connection->exec(
            'DELETE FROM
                sw_connect_change
            WHERE
                c_revision <= ' . $offset
        );
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
        $offset = $offset ?: 0;

        $inStatement = implode("','", $this->types['product']);

        $result = $this->connection->prepare(
            "EXPLAIN SELECT
                *
            FROM
                `" . $this->tableName('change') . "`
            WHERE
                `c_revision` > ?
            AND
                `c_operation` IN('$inStatement')"
        );
        $result->execute(array($offset));
        $changes = $result->fetch(\PDO::FETCH_ASSOC);

        return max(0, $changes['rows'] - $limit);
    }

    /**
     * Record product insert
     *
     * @param string $id
     * @param string $hash
     * @param string $revision
     * @param Struct\Product $product
     * @return void
     */
    public function recordInsert($id, $hash, $revision, Struct\Product $product)
    {
        $query = $this->connection->prepare(
            'INSERT INTO
                sw_connect_change (
                    `c_entity_id`,
                    `c_operation`,
                    `c_revision`,
                    `c_payload`
                )
            VALUES (
                ?, ?, ?, ?
            );'
        );
        $query->execute(array($id, self::PRODUCT_INSERT, $revision, serialize($product)));

        $this->updateHash($id, $hash);
    }

    /**
     * Record product update
     *
     * @param string $id
     * @param string $hash
     * @param string $revision
     * @param Struct\Product $product
     * @return void
     */
    public function recordUpdate($id, $hash, $revision, Struct\Product $product)
    {
        $stmt = $this->connection
            ->prepare('SELECT p_hash FROM sw_connect_product WHERE p_source_id = ?');
        $stmt->execute(array($id));

        $currentHash = $stmt->fetchColumn();

        if ($currentHash === $hash) {
            return;
        }

        $query = $this->connection->prepare(
            'INSERT INTO
                sw_connect_change (
                    `c_entity_id`,
                    `c_operation`,
                    `c_revision`,
                    `c_payload`
                )
            VALUES (
                ?, ?, ? ,?
            );'
        );
        $query->execute(array($id, self::PRODUCT_UPDATE, $revision, serialize($product)));

        $this->updateHash($id, $hash);
    }

    /**
     * Record product availability update
     *
     * @param string $id
     * @param string $hash
     * @param string $revision
     * @param Struct\Product $product
     * @return void
     */
    public function recordAvailabilityUpdate($id, $hash, $revision, Struct\Product $product)
    {
        $stmt = $this->connection
            ->prepare('SELECT p_hash FROM sw_connect_product WHERE p_source_id = ?');
        $stmt->execute(array($id));

        $currentHash = $stmt->fetchColumn();

        if ($currentHash === $hash) {
            return;
        }

        $query = $this->connection->prepare(
            'INSERT INTO
                sw_connect_change (
                    `c_entity_id`,
                    `c_operation`,
                    `c_revision`,
                    `c_payload`
                )
            VALUES (
                ?, ?, ? ,?
            );'
        );
        $query->execute(array($id, self::PRODUCT_STOCK, $revision, $product->availability));

        $this->updateHash($id, $hash);
    }

    /**
     * Record stream assignment
     *
     * @param string $productId
     * @param string $revision
     * @param array $supplierStreams
     * @param string|null $groupId
     */
    public function recordStreamAssignment($productId, $revision, array $supplierStreams, $groupId = null)
    {
        $query = $this->connection->prepare(
            'INSERT INTO
                sw_connect_change (
                    `c_entity_id`,
                    `c_operation`,
                    `c_revision`,
                    `c_payload`
                )
            VALUES (
                ?, ?, ?, ?
            );'
        );
        $query->execute(
            array(
                $productId,
                self::STREAM_ASSIGNMENT,
                $revision,
                serialize(array('groupId' => $groupId, 'supplierStreams' => $supplierStreams))
            )
        );
    }

    /**
     * @param $streamId
     * @param $revision
     */
    public function recordStreamDelete($streamId, $revision)
    {
        $query = $this->connection->prepare(
            'INSERT INTO
                sw_connect_change (
                    `c_entity_id`,
                    `c_operation`,
                    `c_revision`
                )
            VALUES (
                ?, ?, ?
            );'
        );
        $query->execute(array($streamId, self::STREAM_DELETE, $revision));
    }

    /**
     * @param $productId
     * @param $revision
     * @param $groupId
     */
    public function makeMainVariant($productId, $revision, $groupId)
    {
        $query = $this->connection->prepare(
            'INSERT INTO
                 sw_connect_change (
                     `c_entity_id`,
                     `c_operation`,
                     `c_revision`,
                     `c_payload`
                 )
             VALUES (
                 ?, ?, ?, ?
             );'
        );
        $query->execute(array($productId, self::MAIN_VARIANT, $revision, serialize(array('groupId' => $groupId))));
    }

    /**
     * @param $revision
     * @param Struct\PaymentStatus $paymentStatus
     */
    public function updatePaymentStatus($revision, Struct\PaymentStatus $paymentStatus)
    {
        $query = $this->connection->prepare(
            'INSERT INTO
                sw_connect_change (
                    `c_entity_id`,
                    `c_operation`,
                    `c_revision`,
                    `c_payload`
                )
            VALUES (
                ?, ?, ?, ?
            );'
        );

        $query->execute(array(
            $paymentStatus->localOrderId,
            self::PAYMENT_UPDATE,
            $revision,
            serialize($paymentStatus)
        ));
    }

    /**
     * Update hash for product
     *
     * Updates the hash of existing products or inserts the hash, if product is
     * not yet in database.
     *
     * @param string $productId
     * @param string $hash
     * @return void
     */
    protected function updateHash($productId, $hash)
    {
        $query =  $this->connection->prepare(
            'INSERT INTO
                sw_connect_product
                (p_source_id, p_hash)
            VALUES
                (?, ?)
            ON DUPLICATE KEY UPDATE
                p_hash = ?;'
        );
        $query->execute(array($productId, $hash, $hash));
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
        $query = $this->connection->prepare(
            'INSERT INTO
                sw_connect_change (
                    `c_entity_id`,
                    `c_operation`,
                    `c_revision`
                )
            VALUES (
                ?, ?, ?
            );'
        );
        $query->execute(array($id, self::PRODUCT_DELETE, $revision));

        $query = $this->connection->prepare(
            'DELETE FROM
                sw_connect_product
            WHERE
                p_source_id = ?
            ;'
        );
        $query->execute(array($id));
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
        $query = $this->connection->prepare(
            'SELECT
                `p_hash`
            FROM
                `' . $this->tableName('product') . '`
            WHERE
                p_source_id = ?'
        );
        $query->execute(array($id));

        $result = $query->fetchColumn();
        return $result !== $hash;
    }

    /**
     * Get IDs of all recorded products
     *
     * @return string[]
     */
    public function getAllProductIDs()
    {
        $query = $this->connection->query(
            'SELECT
                `p_source_id`
            FROM
                `' . $this->tableName('product') . '`'
        );

        return $query->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Get last processed import revision
     *
     * @return string
     */
    public function getLastRevision()
    {
        $query = $this->connection->query(
            'SELECT
                `d_value`
            FROM
                `' . $this->tableName('data') . '`
            WHERE
                `d_key` = "revision"'
        );

        $result = $query->fetchColumn();
        if ($result === false) {
            return null;
        }

        return $result;
    }

    /**
     * Store last processed import revision
     *
     * @param string $revision
     * @return void
     */
    public function storeLastRevision($revision)
    {
        $query = $this->connection->prepare(
            'INSERT INTO
                sw_connect_data (
                    `d_key`,
                    `d_value`
                )
            VALUES (
                "revision",
                ?
            )
            ON DUPLICATE KEY UPDATE
                `d_value` = VALUES(`d_value`)
            ;'
        );
        $query->execute(array($revision));
    }

    /**
     * Update shop configuration
     *
     * @param string $shopId
     * @param Struct\ShopConfiguration $configuration
     * @return void
     */
    public function setShopConfiguration($shopId, Struct\ShopConfiguration $configuration)
    {
        $this->setConfig($shopId, serialize($configuration));
    }

    /**
     * Get configuration for the given shop
     *
     * @param string $shopId
     * @return Struct\ShopConfiguration
     */
    public function getShopConfiguration($shopId)
    {
        $result = $this->getConfig($shopId);
        if ($result === null) {
            return null;
        }

        return unserialize($result);
    }

    /**
     * Set own shop ID
     *
     * @param string $shopId
     * @return void
     */
    public function setShopId($shopId)
    {
        $this->setConfig('_self_', $shopId);
        $this->setConfig('_last_update_', time());
    }

    /**
     * Get own shop ID
     *
     * Returns null if the shop ID is not set, yet.
     *
     * @return string|null
     */
    public function getShopId()
    {
        $query = $this->connection->query(
            'SELECT
                `s_config`
            FROM
                `' . $this->tableName('shop_config') . '`
            WHERE
                `s_shop` = "_self_"'
        );

        $result = $query->fetchColumn();
        if ($result === null) {
            return null;
        }

        return $result;
    }

    /**
     * Get all connected shop ids.
     *
     * @return array<string>
     */
    public function getConnectedShopIds()
    {
        $stmt = $this->connection->query(
            'SELECT
                `s_shop`
            FROM
                `' . $this->tableName('shop_config') . '`'
        );

        $shopIds = array();

        while ($shopId = $stmt->fetchColumn()) {
            if (is_numeric($shopId)) {
                $shopIds[] = $shopId;
            }
        }

        return $shopIds;
    }

    /**
     * Get last shop verification date as Unix UTC timestamp
     *
     * @return int
     */
    public function getLastVerificationDate()
    {
        $query = $this->connection->query(
            'SELECT
                `s_config`
            FROM
                `' . $this->tableName('shop_config') . '`
            WHERE
                `s_shop` = "_last_update_"'
        );

        $result = $query->fetchColumn();
        if ($result === null) {
            return false;
        }

        return $result;
    }

    /**
     * Create and store reservation
     *
     * Returns the reservation Id
     *
     * @param Struct\Order $order
     * @return string
     */
    public function createReservation(Struct\Order $order)
    {
        $order->reservationId = md5(microtime());
        $query = $this->connection->prepare(
            'INSERT INTO
                `' . $this->tableName('reservations') . '` (
                    `r_id`,
                    `r_state`,
                    `r_order`
                )
            VALUES (
                ?, ?, ?
            );'
        );
        $query->execute(array($order->reservationId, 'new', serialize($order)));
        return $order->reservationId;
    }

    /**
     * Get order for reservation Id
     *
     * @param string $reservationId
     * @throws \OutOfBoundsException
     * @return Struct\Order
     */
    public function getOrder($reservationId)
    {
        $query = $this->connection->prepare(
            'SELECT
                `r_order`
            FROM
                `' . $this->tableName('reservations') . '`
            WHERE
                `r_id` = ?;'
        );
        $query->execute(array($reservationId));

        $result = $query->fetchColumn();
        if ($result === false) {
            throw new \OutOfBoundsException("Reservation $reservationId not found.");
        }

        return unserialize($result);
    }

    /**
     * Set reservation as bought
     *
     * @param string $reservationId
     * @param Struct\Order $order
     * @throws \OutOfBoundsException
     * @return void
     */
    public function setBought($reservationId, Struct\Order $order)
    {
        $query = $this->connection->prepare(
            'UPDATE
                `' . $this->tableName('reservations') . '`
            SET
                `r_state` = "bought",
                `r_order` = ?
            WHERE
                `r_id` = ?
            ;'
        );
        $query->execute(array(serialize($order), $reservationId));

        if ($query->rowCount() !== 1) {
            throw new \OutOfBoundsException("Reservation $reservationId not found.");
        }
    }

    /**
     * Set reservation as confirmed
     *
     * @param string $reservationId
     * @throws \OutOfBoundsException
     * @return void
     */
    public function setConfirmed($reservationId)
    {
        $query = $this->connection->prepare(
            'UPDATE
                `' . $this->tableName('reservations') . '`
            SET
                `r_state` = "confirmed"
            WHERE
                `r_id` = ?
            ;'
        );
        $query->execute(array($reservationId));

        if ($query->rowCount() !== 1) {
            throw new \OutOfBoundsException("Reservation $reservationId not found.");
        }
    }

    /**
     * @param mixed $shopId
     * @param mixed $config
     * @return bool
     */
    public function setConfig($shopId, $config)
    {
        $query = $this->connection->prepare(
            'INSERT INTO
                sw_connect_shop_config (
                    `s_shop`,
                    `s_config`
                )
            VALUES (
               ?,
               ?
            )
            ON DUPLICATE KEY UPDATE
                `s_config` = VALUES(`s_config`)
            ;'
        );
        return $query->execute(array($shopId, $config));
    }

    /**
     * @param $shopId
     * @return null|string
     */
    public function getConfig($shopId)
    {
        $query = $this->connection->prepare(
            'SELECT
                `s_config`
            FROM
                `' . $this->tableName('shop_config') . '`
            WHERE
                `s_shop` = ?'
        );
        $query->execute(array($shopId));

        $config = $query->fetchColumn();
        if ($config === false) {
            return null;
        }

        return $config;
    }

    /**
     * Get last revision
     *
     * @return string
     */
    public function getLastShippingCostsRevision()
    {
        $query = $this->connection->prepare(
            'SELECT
                MAX(`sc_revision`)
            FROM
                `' . $this->tableName('shipping_costs') . '`'
        );
        $query->execute();

        $revision = $query->fetchColumn();
        if ($revision === false) {
            return null;
        }

        return $revision;
    }

    /**
     * Store shop shipping costs
     *
     * @param string $fromShop
     * @param string $toShop
     * @param string $revision
     * @param \Shopware\Connect\ShippingCosts\Rules $intershopCosts
     * @param \Shopware\Connect\ShippingCosts\Rules $customerCosts
     * @return void
     */
    public function storeShippingCosts($fromShop, $toShop, $revision, Rules $intershopCosts, Rules $customerCosts)
    {
        $query = $this->connection->prepare(
            'INSERT INTO
                ' . $this->tableName('shipping_costs') . ' (
                    `sc_from_shop`,
                    `sc_to_shop`,
                    `sc_revision`,
                    `sc_shipping_costs`,
                    `sc_customer_costs`
                )
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                `sc_revision` = VALUES(`sc_revision`),
                `sc_shipping_costs` = VALUES(`sc_shipping_costs`),
                `sc_customer_costs` = VALUES(`sc_customer_costs`)
            ;'
        );
        $query->execute(array(
            $fromShop,
            $toShop,
            $revision,
            serialize($intershopCosts),
            serialize($customerCosts),
        ));
    }

    /**
     * Get shop shipping costs
     *
     * @param string $fromShop
     * @param string $toShop
     * @param string $type
     * @return \Shopware\Connect\ShippingCosts\Rules
     */
    public function getShippingCosts($fromShop, $toShop, $type = self::SHIPPING_COSTS_INTERSHOP)
    {
        $column = ($type === self::SHIPPING_COSTS_CUSTOMER)
            ? 'sc_customer_costs'
            : 'sc_shipping_costs';

        $query = $this->connection->prepare(
            'SELECT `' . $column . '`
            FROM
                `' . $this->tableName('shipping_costs') . '`
            WHERE
                `sc_from_shop` = ? AND `sc_to_shop` = ?
            ORDER BY `sc_revision` DESC
            LIMIT 1'
        );
        $query->execute(array($fromShop, $toShop));

        $costs = $query->fetchColumn();
        if ($costs === false) {
            return array();
        }

        return unserialize($costs);
    }

    /**
     * Set the shops billing address used in orders.
     *
     * @param \Shopware\Connect\Struct\Address $address
     */
    public function setBillingAddress(Struct\Address $address)
    {
        $this->setConfig('_billing_address_', serialize($address));
    }

    /**
     * @return \Shopware\Connect\Struct\Address
     */
    public function getBillingAddress()
    {
        $address = $this->getConfig('_billing_address_');

        if ($address) {
            return unserialize($address);
        }

        return null;
    }
}
