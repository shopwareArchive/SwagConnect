<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\ChangeVisitor;

use Bepado\SDK\ChangeVisitor;
use Bepado\SDK\Struct;

/**
 * Visits intershop changes ito messages
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
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
     * @return Struct\Message[]
     */
    public function visit(array $changes)
    {
        $messages = array();
        foreach ($changes as $shop => $change) {
            $this->verificator->verify($change);

            switch (true) {
                case ($change instanceof Struct\Change\InterShop\Update):
                case ($change instanceof Struct\Change\InterShop\Unavailable):
                    $messages[] = new Struct\Message(array(
                        'message' => 'Availability of product %product changed to %availability.',
                        'values' => array(
                            'product' => $change->sourceId,
                            'availability' => 0,
                        )
                    ));
                    break;

                case ($change instanceof Struct\Change\InterShop\Delete):
                    $messages[] = new Struct\Message(array(
                        'message' => 'Product %product does not exist anymore.',
                        'values' => array(
                            'product' => $change->sourceId
                        )
                    ));
                    break;


                default:
                    throw new \RuntimeException(
                        'No visitor found for ' . get_class($change)
                    );
            }
        }

        return $messages;
    }
}
