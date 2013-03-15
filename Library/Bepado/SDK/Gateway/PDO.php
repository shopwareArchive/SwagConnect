<?php
/**
 * This file is part of the Bepado SDK Component.
 */

namespace Bepado\SDK\Gateway;

use Bepado\SDK\Gateway;
use Bepado\SDK\Struct;

/**
 *
 * @author Heiner Lohaus
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
        'insert' => '\\Bepado\\SDK\\Struct\\Change\\FromShop\\Insert',
        'update' => '\\Bepado\\SDK\\Struct\\Change\\FromShop\\Update',
        'delete' => '\\Bepado\\SDK\\Struct\\Change\\FromShop\\Delete',
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
            LIMIT
                ' . ((int) $limit)
        );

        $changes = array();
        while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {
            $class = $this->operationStruct[$row['c_operation']];
            $changes[] = $change = new $class(
                array(
                    'sourceId' => $row['c_source_id'],
                    'revision' => $row['c_revision'],
                )
            );

            if ($row['c_product']) {
                $change->product = unserialize($row['c_product']);
            }
        }

        $this->connection->exec(
            'DELETE FROM
                bepado_change
            WHERE
                c_revision <= ' . $offset
        );

        return $changes;
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
        $result = $this->connection->prepare(
            'SELECT
                COUNT(*) `changes`
            FROM
                `bepado_change`
            WHERE
                `c_revision` > ?'
        );
        $result->execute(array($offset));
        $changes = $result->fetchColumn();
        return max(0, $changes - $limit);
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
                bepado_change (
                    `c_source_id`,
                    `c_operation`,
                    `c_revision`,
                    `c_product`
                )
            VALUES (
                ?, ?, ?, ?
            );'
        );
        $query->execute(array(
            $id,
            'insert',
            $revision,
            serialize($product)
        ));

        $query =  $this->connection->prepare(
            'INSERT INTO
                bepado_product
            VALUES (
                ?,
                ?,
                null
            );'
        );
        $query->execute(array(
            $id,
            $hash
        ));
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
        $query = $this->connection->prepare(
            'INSERT INTO
                bepado_change (
                    `c_source_id`,
                    `c_operation`,
                    `c_revision`,
                    `c_product`
                )
            VALUES (
                ?, ?, ? ,?
            );'
        );
        $query->execute(array(
            $id,
            'update',
            $revision,
            serialize($product)
        ));

        $query = $this->connection->query(
            'UPDATE
                bepado_product
            SET
                p_hash = ?
            WHERE
                p_source_id = ?
            ;'
        );
        $query->execute(array(
            $hash,
            $id
        ));
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
                bepado_change (
                    `c_source_id`,
                    `c_operation`,
                    `c_revision`
                )
            VALUES (
                ?, ?, ?
            );'
        );
        $query->execute(array(
            $id,
            'delete',
            $revision
        ));

        $query = $this->connection->prepare(
            'DELETE FROM
                bepado_product
            WHERE
                p_source_id = ?
            ;'
        );
        $query->execute(array(
            $id
        ));
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
                `bepado_product`
            WHERE
                p_source_id = ?'
        );
        $query->execute(array(
            $id
        ));

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
                `bepado_product`'
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
                `bepado_data`
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
                bepado_data (
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
     * Set category mapping
     *
     * @param array $categories
     * @return void
     */
    public function setCategories(array $categories)
    {
        $this->setConfig('_categories_', serialize($categories));
    }

    /**
     * Get category mapping
     *
     * @return array
     */
    public function getCategories()
    {
        $result = $this->getConfig('_categories_');
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
    }

    /**
     * Get own shop ID
     *
     * @return string
     */
    public function getShopId()
    {
        $query = $this->connection->query(
            'SELECT
                `s_config`
            FROM
                `bepado_shop_config`
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
     * Get last shop verification date as Unix UTC timestamp
     *
     * @return int
     */
    public function getLastVerificationDate()
    {
        $query = $this->connection->query(
            'SELECT
                `changed`
            FROM
                `bepado_shop_config`
            WHERE
                `s_shop` = "_self_"'
        );

        $result = $query->fetchColumn();
        if ($result === null) {
            return null;
        }

        return strtotime($result);
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
        $query = $this->connection->prepare(
            'INSERT INTO
                `bepado_reservations` (
                    `r_id`,
                    `r_state`,
                    `r_order`
                )
            VALUES (
                ?, ?, ?
            );'
        );
        $query->execute(array(
            $reservationId,
            'new',
            serialize($order)
        ));
        return $reservationId;
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
                `bepado_reservations`
            WHERE
                `r_id` = ?;'
        );
        $query->execute(array($reservationId));

        $result = $query->fetchColumn();
        if ($result !== false) {
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
                `bepado_reservations`
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
                `bepado_reservations`
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
    private function setConfig($shopId, $config)
    {
        $query = $this->connection->prepare(
            'INSERT INTO
                bepado_shop_config (
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
    private function getConfig($shopId)
    {
        $query = $this->connection->prepare(
            'SELECT
                `s_config`
            FROM
                `bepado_shop_config`
            WHERE
                `s_shop` = ?'
        );
        $query->execute(array(
            $shopId
        ));

        $config = $query->fetchColumn();
        if ($config === false) {
            return null;
        }

        return $config;
    }
}
