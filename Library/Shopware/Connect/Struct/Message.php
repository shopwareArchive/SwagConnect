<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct;

use Shopware\Connect\Struct;

/**
 * Struct class representing a message
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 * @api
 */
class Message extends Struct
{
    /**
     * Message that might contain placeholders.
     *
     * Placeholders are identified by "%foo" (without quotes). They will be
     * replaced by values from $values.
     *
     * @var string
     */
    public $message;

    /**
     * Message variables
     *
     * @var array
     */
    public $values = array();
}
