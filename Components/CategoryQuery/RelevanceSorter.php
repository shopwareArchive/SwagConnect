<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components\CategoryQuery;

/**
 * The sorter will sort categories by their relevance.
 * Currently the most specific category (deepest category) will be considered most relevant
 * If multiple categories have the same depth, the one with the longest leaf category will be more relevant
 *
 * The sorter returns the category by ascending relevance
 *
 * Class RelevanceSorter
 * @package ShopwarePlugins\Connect\Components\CategoryQuery
 */
class RelevanceSorter
{
    /**
     * Sort a list of connect
     *
     * @param $a
     * @param $b
     * @return int
     */
    public function sortConnectCategoriesByRelevance($a, $b)
    {
        if ($a == $b) {
            return 0;
        }

        $count_a = substr_count($a, '/');
        $count_b = substr_count($b, '/');

        if ($count_a < $count_b) {
            return -1;
        } elseif ($count_b < $count_a) {
            return 1;
        }
        $parts_a = explode('/', $a);
        $parts_b = explode('/', $b);

        $last_element_length_a = strlen(array_pop($parts_a));
        $last_element_length_b = strlen(array_pop($parts_b));

        if ($last_element_length_a == $last_element_length_b) {
            return 0;
        }

        return ($last_element_length_a < $last_element_length_b) ? -1 : 1;
    }
}
