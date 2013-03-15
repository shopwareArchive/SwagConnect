<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.0.0snapshot201303151129
 */

namespace Bepado\SDK\Struct;

use Bepado\SDK\Struct;

/**
 * Struct class representing a search result
 *
 * @version 1.0.0snapshot201303151129
 * @api
 */
class SearchResult extends Struct
{
    /**
     * Original search, which was executed
     *
     * @var Search
     */
    public $search;

    /**
     * Count of results
     *
     * @var int
     */
    public $resultCount;

    /**
     * Search results
     *
     * Array of SearchResult\Product structs
     *
     * @var SearchResult\Product[]
     */
    public $results;

    /**
     * Vendors, which are in the search result as an associative array.
     *
     * The vendor name is the index and the occurence count is the value of the 
     * array. Maybe used for facetting
     *
     * @var array
     */
    public $vendors;

    /**
     * Product categories of the found products as an associative array.
     *
     * The category name is the index and the occurence count is the value of
     * the array. Maybe used for facetting
     *
     * @var array
     */
    public $categories;

    /**
     * Minimum price found in the search results
     *
     * @var float
     */
    public $priceFrom;

    /**
     * Maximum price found in the search results
     *
     * @var float
     */
    public $priceTo;
}
