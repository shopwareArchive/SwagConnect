<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.133
 */

namespace Bepado\SDK;

/**
 * Base class for logger implementations
 *
 * @version 1.1.133
 */
abstract class Logger
{
    /**
     * Log order
     *
     * @param Struct\Order $order
     * @return void
     */
    public function log(Struct\Order $order)
    {
        foreach (array('orderShop', 'providerShop', 'reservationId') as $property) {
            if (!isset($order->$property)) {
                throw new \InvalidArgumentException("Required order property \$$property not set.");
            }
        }

        return $this->doLog($order);
    }

    /**
     * Log order
     *
     * @param Struct\Order $order
     * @return void
     */
    abstract protected function doLog(Struct\Order $order);

    /**
     * Confirm logging
     *
     * @param string $logTransactionId
     * @return void
     */
    abstract public function confirm($logTransactionId);
}
