<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.142
 */

namespace Bepado\SDK\Struct\Verificator;

use Bepado\SDK\Struct\Verificator;
use Bepado\SDK\Struct\VerificatorDispatcher;
use Bepado\SDK\Struct;

/**
 * Visitor verifying integrity of struct classes
 *
 * @version 1.1.142
 */
class Reservation extends Verificator
{
    /**
     * Method to verify a structs integrity
     *
     * Throws a RuntimeException if the struct does not verify.
     *
     * @param VerificatorDispatcher $dispatcher
     * @param Struct $struct
     * @return void
     */
    public function verify(VerificatorDispatcher $dispatcher, Struct $struct)
    {
        if (!is_array($struct->messages)) {
            throw new \RuntimeException('$messages MUST be an array.');
        }
        foreach ($struct->messages as $shopId => $messages) {
            if (!is_array($messages)) {
                throw new \RuntimeException('$messages MUST be an array.');
            }

            foreach ($messages as $message) {
                if (!$message instanceof Struct\Message) {
                    throw new \RuntimeException('$message MUST be an instance of \\Bepado\\SDK\\Struct\\Message.');
                }
                $dispatcher->verify($message);
            }
        }

        if (!is_array($struct->orders)) {
            throw new \RuntimeException('$orders MUST be an array.');
        }
        foreach ($struct->orders as $order) {
            if (!$order instanceof Struct\Order) {
                throw new \RuntimeException('$orders MUST be an instance of \\Bepado\\SDK\\Struct\\Order.');
            }
            $dispatcher->verify($order);
        }
    }
}
