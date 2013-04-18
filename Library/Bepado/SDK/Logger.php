<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK;

/**
 * Base class for logger implementations
 *
 * @version $Revision$
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
}
