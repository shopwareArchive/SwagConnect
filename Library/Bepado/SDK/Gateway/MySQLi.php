<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.133
 */

namespace Bepado\SDK\Gateway;

use Bepado\SDK\Gateway;
use Bepado\SDK\Struct;
use Bepado\SDK\ShippingCosts\Rules;

/**
 * Default MySQLi implementation of the storage gateway
 *
 * @version 1.1.133
 */
class MySQLi extends Gateway
{
    /**
     * MySQLi connection
     *
     * @var \Bepado\SDK\MySQLi
     */
    protected $connection;

    /**
     * Struct classes used for operations
     *
     * @var array
     */
    protected $operationStruct = array(
        'insert' => '\\Bepado\\SDK\\Struct\\Change\\FromShop\\Insert',
        'update' => '\\Bepado\\SDK\\Struct\\Change\\FromShop\\Update',
        'delete' => '\\Bepado\\SDK\\Struct\\Change\\FromShop\\Delete',
    );

    /**
     * Construct from MySQL connection
     *
     * @param \Bepado\SDK\MySQLi $connection
     */
    public function __construct(\Bepado\SDK\MySQLi $connection)
    {
        $this->connection = $connection;
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
     * @return Struct\Change[]
     */
    public function getNextChanges($offset, $limit)
    {
        $offset = $offset ?: 0;
        // Float type cast does NOT work here, since the inaccuracy of floating
        // point representations otherwise omit changes. Yes, this actually
        // really happens.
        if (!preg_match('(^[\\d\\.]+$)', $offset)) {
            throw new \InvalidArgumentException("Offset revision must be a numeric string.");
        }

        $result = $this->connection->query(
            'SELECT
                `c_source_id`,
                `c_operation`,
                `c_revision`,
                `c_product`
            FROM
                `bepado_change`
            WHERE
                `c_revision` > ' . $offset . '
            ORDER BY `c_revision` ASC
            LIMIT
                ' . ((int) $limit)
        );

        $changes = array();
        while ($row = $result->fetch_assoc()) {
            $class = $this->operationStruct[$row['c_operation']];
            $changes[] = $change = new $class(
                array(
                    'sourceId' => $row['c_source_id'],
                    'revision' => $row['c_revision'],
                )
            );

            if ($row['c_product']) {
                $change->product = $this->ensureUtf8(unserialize($row['c_product']));
            }
        }

        return $changes;
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
        /*$this->connection->query(
            'DELETE FROM
                bepado_change
            WHERE
                c_revision <= ' . $offset
        );*/
    }

    private function ensureUtf8($product)
    {
        foreach (get_object_vars($product) as $name => $value) {
            if (!is_string($value)) {
                continue;
            }
            if (@iconv('UTF-8', 'UTF-8', $value)) {
                continue;
            }
            $product->$name = @iconv("UTF-8", "UTF-8//TRANSLIT", $value);
        }
        return $product;
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
        $result = $this->connection->query(
            'EXPLAIN SELECT
                *
            FROM
                `bepado_change`
            WHERE
                `c_revision` > ' . $this->connection->real_escape_string($offset)
        );

        $row = $result->fetch_assoc();
        return max(0, $row['rows'] - $limit);
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
        $this->connection->query(
            'INSERT INTO
                bepado_change (
                    `c_source_id`,
                    `c_operation`,
                    `c_revision`,
                    `c_product`
                )
            VALUES (
                "' . $this->connection->real_escape_string($id) . '",
                "insert",
                "' . $this->connection->real_escape_string($revision) . '",
                "' . $this->connection->real_escape_string(serialize($product)) . '"
            );'
        );

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
        $sql = 'SELECT p_hash FROM bepado_product ' .
               'WHERE p_source_id = "' . $this->connection->real_escape_string($id) . '"';

        $row = $this->connection
            ->query($sql)
            ->fetch_assoc();
        $currentHash = $row['p_hash'];

        if ($currentHash === $hash) {
            return;
        }

        $this->connection->query(
            'INSERT INTO
                bepado_change (
                    `c_source_id`,
                    `c_operation`,
                    `c_revision`,
                    `c_product`
                )
            VALUES (
                "' . $this->connection->real_escape_string($id) . '",
                "update",
                "' . $this->connection->real_escape_string($revision) . '",
                "' . $this->connection->real_escape_string(serialize($product)) . '"
            );'
        );

        $this->updateHash($id, $hash);
    }

    /**
     * Update hash for product
     *
     * Updates the hash of exisitng products or inserts the hash, if product is
     * not yet in database.
     *
     * @param string $productId
     * @param string $hash
     * @return void
     */
    protected function updateHash($productId, $hash)
    {
        $this->connection->query(
            'INSERT INTO
                bepado_product
                (p_source_id, p_hash)
            VALUES
                (
                    "' . $this->connection->real_escape_string($productId) . '",
                    "' . $this->connection->real_escape_string($hash) . '"
                )
            ON DUPLICATE KEY UPDATE
                p_hash = "' . $this->connection->real_escape_string($hash) . '";'
        );
    }

    /**
     * Record product delete
     *
     * @param string $id
     * @param string $hash
     * @param string $revision
     * @return void
     */
    public function recordDelete($id, $revision)
    {
        $this->connection->query(
            'INSERT INTO
                bepado_change (
                    `c_source_id`,
                    `c_operation`,
                    `c_revision`
                )
            VALUES (
                "' . $this->connection->real_escape_string($id) . '",
                "delete",
                "' . $this->connection->real_escape_string($revision) . '"
            );'
        );

        $this->connection->query(
            'DELETE FROM
                bepado_product
            WHERE
                p_source_id = "' . $this->connection->real_escape_string($id) . '"
            ;'
        );
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
        $result = $this->connection->query(
            'SELECT
                `p_hash`
            FROM
                `bepado_product`
            WHERE
                p_source_id = "' . $this->connection->real_escape_string($id) . '"'
        );

        $row = $result->fetch_assoc();
        return $row['p_hash'] !== $hash;
    }

    /**
     * Get IDs of all recorded products
     *
     * @return string[]
     */
    public function getAllProductIDs()
    {
        $result = $this->connection->query(
            'SELECT
                `p_source_id`
            FROM
                `bepado_product`'
        );

        return array_map(
            function ($row) {
                return $row['p_source_id'];
            },
            $result->fetch_all(\MYSQLI_ASSOC)
        );
    }

    /**
     * Get last processed import revision
     *
     * @return string
     */
    public function getLastRevision()
    {
        $result = $this->connection->query(
            'SELECT
                `d_value`
            FROM
                `bepado_data`
            WHERE
                `d_key` = "revision"'
        );

        $rows = $result->fetch_all(\MYSQLI_ASSOC);
        if (!count($rows)) {
            return null;
        }

        return $rows[0]['d_value'];
    }

    /**
     * Store last processed import revision
     *
     * @param string $revision
     * @return void
     */
    public function storeLastRevision($revision)
    {
        $this->connection->query(
            'INSERT INTO
                bepado_data (
                    `d_key`,
                    `d_value`
                )
            VALUES (
                "revision",
                "' . $this->connection->real_escape_string($revision) . '"
            )
            ON DUPLICATE KEY UPDATE
                `d_value` = "' . $this->connection->real_escape_string($revision) . '"
            ;'
        );
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
        $this->connection->query(
            'INSERT INTO
                bepado_shop_config (
                    `s_shop`,
                    `s_config`
                )
            VALUES (
                "' . $this->connection->real_escape_string($shopId) . '",
                "' . $this->connection->real_escape_string(serialize($configuration)) . '"
            )
            ON DUPLICATE KEY UPDATE
                `s_config` = "' . $this->connection->real_escape_string(serialize($configuration)) . '"
            ;'
        );
    }

    /**
     * Get configuration for the given shop
     *
     * @param string $shopId
     * @throws \RuntimeException If shop does not exist in configuration.
     * @return Struct\ShopConfiguration
     */
    public function getShopConfiguration($shopId)
    {
        $result = $this->connection->query(
            'SELECT
                `s_config`
            FROM
                `bepado_shop_config`
            WHERE
                `s_shop` = "' . $this->connection->real_escape_string($shopId) . '"'
        );

        $rows = $result->fetch_all(\MYSQLI_ASSOC);

        if (!count($rows)) {
            throw new \RuntimeException(
                'You are not connected to shop ' . $shopId . '.'
            );
        }

        return unserialize($rows[0]['s_config']);
    }

    /**
     * Set category mapping
     *
     * @param array $categories
     * @return void
     */
    public function setCategories(array $categories)
    {
        $this->connection->query(
            'INSERT INTO
                bepado_shop_config (
                    `s_shop`,
                    `s_config`
                )
            VALUES (
                "_categories_",
                "' . $this->connection->real_escape_string(serialize($categories)) . '"
            )
            ON DUPLICATE KEY UPDATE
                `s_config` = "' . $this->connection->real_escape_string(serialize($categories)) . '"
            ;'
        );
    }

    /**
     * Get category mapping
     *
     * @return array
     */
    public function getCategories()
    {
        $result = $this->connection->query(
            'SELECT
                `s_config`
            FROM
                `bepado_shop_config`
            WHERE
                `s_shop` = "_categories_"'
        );

        $rows = $result->fetch_all(\MYSQLI_ASSOC);
        if (!count($rows)) {
            return false;
        }

        return unserialize($rows[0]['s_config']);
    }

    /**
     * Set own shop ID
     *
     * @param string $shopId
     * @return void
     */
    public function setShopId($shopId)
    {
        $this->connection->query(
            'INSERT INTO
                bepado_shop_config (
                    `s_shop`,
                    `s_config`
                )
            VALUES (
                "_self_",
                "' . $this->connection->real_escape_string($shopId) . '"
            )
            ON DUPLICATE KEY UPDATE
                `s_config` = "' . $this->connection->real_escape_string($shopId) . '"
            ;'
        );

        $this->connection->query(
            'INSERT INTO
                bepado_shop_config (
                    `s_shop`,
                    `s_config`
                )
            VALUES (
                "_last_update_",
                ' . $this->connection->real_escape_string(time()) . '
            )
            ON DUPLICATE KEY UPDATE
                `s_config` = "' . $this->connection->real_escape_string(time()) . '"
            ;'
        );
    }

    /**
     * Get own shop ID
     *
     * @return string
     */
    public function getShopId()
    {
        $result = $this->connection->query(
            'SELECT
                `s_config`
            FROM
                `bepado_shop_config`
            WHERE
                `s_shop` = "_self_"'
        );

        $rows = $result->fetch_all(\MYSQLI_ASSOC);
        if (!count($rows)) {
            return false;
        }

        return $rows[0]['s_config'];
    }

    /**
     * Get last shop verification date as Unix UTC timestamp
     *
     * @return int
     */
    public function getLastVerificationDate()
    {
        $result = $this->connection->query(
            'SELECT
                `s_config`
            FROM
                `bepado_shop_config`
            WHERE
                `s_shop` = "_last_update_"'
        );

        $rows = $result->fetch_all(\MYSQLI_ASSOC);
        if (!count($rows)) {
            return false;
        }

        return $rows[0]['s_config'];
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
        $reservationId = md5(microtime());
        $this->connection->query(
            'INSERT INTO
                `bepado_reservations` (
                    `r_id`,
                    `r_state`,
                    `r_order`
                )
            VALUES (
                "' . $this->connection->real_escape_string($reservationId) . '",
                "new",
                "' . $this->connection->real_escape_string(serialize($order)) . '"
            );'
        );

        return $reservationId;
    }

    /**
     * Get order for reservation Id
     *
     * @param string $reservationId
     * @return Struct\Order
     */
    public function getOrder($reservationId)
    {
        $result = $this->connection->query(
            'SELECT
                `r_order`
            FROM
                `bepado_reservations`
            WHERE
                `r_id` = "' . $this->connection->real_escape_string($reservationId) . '";'
        );

        $rows = $result->fetch_all();
        if (!count($rows)) {
            throw new \OutOfBoundsException("Reservation $reservationId not found.");
        }

        return unserialize($rows[0][0]);
    }

    /**
     * Set reservation as bought
     *
     * @param string $reservationId
     * @param Struct\Order $order
     * @return void
     */
    public function setBought($reservationId, Struct\Order $order)
    {
        $this->connection->query(
            'UPDATE
                `bepado_reservations`
            SET
                `r_state` = "bought",
                `r_order` = "' . $this->connection->real_escape_string(serialize($order)) . '"
            WHERE
                `r_id` = "' . $this->connection->real_escape_string($reservationId) . '"
            ;'
        );

        if ($this->connection->affected_rows !== 1) {
            throw new \OutOfBoundsException("Reservation $reservationId not found.");
        }
    }

    /**
     * Set reservation as confirmed
     *
     * @param string $reservationId
     * @return void
     */
    public function setConfirmed($reservationId)
    {
        $this->connection->query(
            'UPDATE
                `bepado_reservations`
            SET
                `r_state` = "confirmed"
            WHERE
                `r_id` = "' . $this->connection->real_escape_string($reservationId) . '"
            ;'
        );

        if ($this->connection->affected_rows !== 1) {
            throw new \OutOfBoundsException("Reservation $reservationId not found.");
        }
    }

    /**
     * Get last revision
     *
     * @return string
     */
    public function getLastShippingCostsRevision()
    {
        $result = $this->connection->query(
            'SELECT
                MAX(`sc_revision`)
            FROM
                `bepado_shipping_costs`'
        );

        $rows = $result->fetch_all();
        if (!count($rows)) {
            return null;
        }

        return $rows[0][0];
    }

    /**
     * Store shop shipping costs
     *
     * @param string $fromShop
     * @param string $toShop
     * @param string $revision
     * @param \Bepado\SDK\ShippingCosts\Rules $shippingCosts
     * @return void
     */
    public function storeShippingCosts($fromShop, $toShop, $revision, Rules $shippingCosts)
    {
        $this->connection->query(
            'INSERT INTO
                bepado_shipping_costs (
                    `sc_from_shop`,
                    `sc_to_shop`,
                    `sc_revision`,
                    `sc_shipping_costs`
                )
            VALUES (
               "' . $this->connection->real_escape_string($fromShop) . '",
               "' . $this->connection->real_escape_string($toShop) . '",
               "' . $this->connection->real_escape_string($revision) . '",
               "' . $this->connection->real_escape_string(serialize($shippingCosts)) . '"
            )
            ON DUPLICATE KEY UPDATE
                `sc_revision` = "' . $this->connection->real_escape_string($revision) . '",
                `sc_shipping_costs` = "' . $this->connection->real_escape_string(serialize($shippingCosts)) . '"
            ;'
        );
    }

    /**
     * Get shop shipping costs
     *
     * @param string $fromShop
     * @param string $toShop
     * @return \Bepado\SDK\ShippingCosts\Rules
     */
    public function getShippingCosts($fromShop, $toShop)
    {
        $result = $this->connection->query(
            'SELECT
                `sc_shipping_costs`
            FROM
                `bepado_shipping_costs`
            WHERE
                `sc_from_shop` = "' . $this->connection->real_escape_string($fromShop) . '" AND
                `sc_to_shop` = "' . $this->connection->real_escape_string($toShop) . '"
            ORDER BY `sc_revision` DESC
            LIMIT 1'
        );

        $rows = $result->fetch_all();

        if (!count($rows)) {
            throw new \OutOfBoundsException("Shipping costs for shops $fromShop-$toShop not found.");
        }

        return unserialize($rows[0][0]);
    }

    /**
     * Set all the enabled features.
     *
     * @param array<string>
     */
    public function setEnabledFeatures(array $features)
    {
        $this->setConfig(
            '_features_',
            strtolower(implode(',', $features))
        );
    }

    /**
     * Is a feature enabled?
     *
     * @param string $featureName
     * @return bool
     */
    public function isFeatureEnabled($feature)
    {
        $features = $this->getConfig('_features_');

        if ($features === null) {
            return false;
        }

        return in_array($feature, explode(',', $features));
    }

    /**
     * Set the last revision of the category tree that the SDK has seen.
     *
     * @param string
     * @return void
     */
    public function setCategoriesLastRevision($revision)
    {
        $this->setConfig('_categories_revision_', $revision);
    }

    /**
     * Get the last revision of the category tree that the SDK has seen.
     *
     * @return string
     */
    public function getCategoriesLastRevision()
    {
        return $this->getConfig('_categories_revision_');
    }

    private function setConfig($name, $value)
    {
        $this->connection->query(
            'INSERT INTO
                bepado_shop_config (
                    `s_shop`,
                    `s_config`
                )
            VALUES (
                "' . $this->connection->real_escape_string($name) . '",
                "' . $this->connection->real_escape_string($value) . '"
            )
            ON DUPLICATE KEY UPDATE
                `s_config` = VALUES(`s_config`)
            ;'
        );
    }

    private function getConfig($name)
    {
        $result = $this->connection->query(
            'SELECT
                `s_config`
            FROM
                `bepado_shop_config`
            WHERE
                `s_shop` = "' . $this->connection->real_escape_string($name) . '"'
        );

        $rows = $result->fetch_all(\MYSQLI_ASSOC);
        if (!count($rows)) {
            return null;
        }

        return $rows[0]['s_config'];
    }
}
