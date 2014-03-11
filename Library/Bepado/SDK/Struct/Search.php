<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.142
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Struct class representing a search
 *
 * @version 1.1.142
 * @api
 */
class Search extends Struct
{
    /**
     * Search query
     *
     * May just be a string to search for. No special search syntax is
     * supported for now.
     *
     * @var string
     */
    public $query;

    /**
     * Result offset (used for paging)
     *
     * @var int
     */
    public $offset = 0;

    /**
     * Count of results to receive
     *
     * @var int
     */
    public $limit = 10;

    /**
     * Limit search to specified vendor
     *
     * @var mixed
     */
    public $vendor;

    /**
     * Minimum price opf search results
     *
     * @var float
     */
    public $priceFrom;

    /**
     * Maximum price of search results
     *
     * @var float
     */
    public $priceTo;
}
