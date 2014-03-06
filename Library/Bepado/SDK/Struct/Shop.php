<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.133
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Struct class for shops
 *
 * @version 1.1.133
 * @api
 */
class Shop extends Struct
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $url;
}
