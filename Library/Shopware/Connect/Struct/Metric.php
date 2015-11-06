<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Shopware\Connect\Struct;

use \Shopware\Connect\Struct;

/**
 * Base class for metric structs
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
abstract class Metric extends Struct
{
    /**
     * @var string
     */
    public $name;
}
