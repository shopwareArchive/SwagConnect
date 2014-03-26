<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */

namespace Bepado\SDK\Struct;

use \Bepado\SDK\Struct;

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
