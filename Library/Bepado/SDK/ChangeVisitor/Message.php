<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.133
 */

namespace Bepado\SDK\ChangeVisitor;

use Bepado\SDK\ChangeVisitor;
use Bepado\SDK\Struct;

/**
 * Visits intershop changes ito messages
 *
 * @version 1.1.133
 */
class Message extends ChangeVisitor
{
    /**
     * Verificator
     *
     * @var VerificatorDispatcher
     */
    protected $verificator;

    public function __construct(Struct\VerificatorDispatcher $verificator)
    {
        $this->verificator = $verificator;
    }

    /**
     * Visit changes
     *
     * @param array $changes
     * @return array
     */
    public function visit(array $changes)
    {
        $messages = array();
        foreach ($changes as $shop => $change) {
            $this->verificator->verify($change);

            switch (true) {
                case $change instanceof Struct\Change\InterShop\Update:
                    $messages = array_merge(
                        $messages,
                        $this->visitUpdate($change)
                    );
                    break;
                case $change instanceof Struct\Change\InterShop\Delete:
                    $messages = array_merge(
                        $messages,
                        $this->visitDelete($change)
                    );
                    break;
                default:
                    throw new \RuntimeException(
                        'No visitor found for ' . get_class($change)
                    );
            }
        }

        return $messages;
    }

    /**
     * Visit update change
     *
     * Note: Why no check on purchase price here? The Change\Message visitor
     * is only used by the buyer shop to translate changes into error messages.
     * When the seller shop sees a purchase price change, he will reduce
     * the availability to "0", hence triggering the availability error message.
     *
     * With the current setup the purchase price check would be impossible here,
     * because we don't have access to the price group margin.
     *
     * @param Struct\Change\InterShop\Update $change
     * @return void
     */
    protected function visitUpdate(Struct\Change\InterShop\Update $change)
    {
        $messages = array();

        if ($change->product->availability !== $change->oldProduct->availability) {
            $messages[] = new Struct\Message(
                array(
                    'message' => 'Availability of product %product changed to %availability.',
                    'values' => array(
                        'product' => $change->product->title,
                        'availability' => $change->product->availability,
                    ),
                )
            );
        }

        if ($change->product->price !== $change->oldProduct->price) {
            $messages[] = new Struct\Message(
                array(
                    'message' => 'Price of product %product changed to %price.',
                    'values' => array(
                        'product' => $change->product->title,
                        'price' => round($change->product->price * (1 + $change->product->vat), 2),
                    ),
                )
            );
        }

        return $messages;
    }

    /**
     * Visit delete change
     *
     * @param Struct\Change\InterShop\Delete $change
     * @return void
     */
    protected function visitDelete(Struct\Change\InterShop\Delete $change)
    {
        return array(
            new Struct\Message(
                array(
                    'message' => 'Product %product does not exist anymore.',
                    'values' => array(
                        'product' => $change->sourceId
                    ),
                )
            )
        );
    }
}
