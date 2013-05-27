<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version $Revision$
 */

namespace Bepado\SDK\ChangeVisitor;

use Bepado\SDK\ChangeVisitor;
use Bepado\SDK\Struct;

/**
 * Visits intershop changes ito messages
 *
 * @version $Revision$
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
                        'price' => $change->product->price,
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
