<?php

namespace Shopware\Bepado\Components\CategoryQuery;

/**
 * The sorter will sort categories by their relevance.
 * Currently the most specific category (deepest category) will be considered most relevant
 * If multiple categories have the same depth, the one with the longest leaf category will be more relevant
 *
 * The sorter returns the category by ascending relevance
 *
 * Class RelevanceSorter
 * @package Shopware\Bepado\Components\CategoryQuery
 */
class RelevanceSorter
{
    /**
     * Sort a list of bepado
     *
     * @param $a
     * @param $b
     * @return int
     */
    public function sortBepadoCategoriesByRelevance($a, $b) {
        if ($a == $b) {
            return 0;
        }

        $count_a = substr_count($a, '/');
        $count_b = substr_count($b, '/');

        if ($count_a < $count_b) {
            return -1;
        } elseif ($count_b < $count_a) {
            return 1;
        } else {
            $parts_a = explode('/', $a);
            $parts_b = explode('/', $b);

            $last_element_length_a = strlen(array_pop($parts_a, -1));
            $last_element_length_b = strlen(array_pop($parts_b, -1));

            if ($last_element_length_a == $last_element_length_b) {
                return 0;
            }

            return ($last_element_length_a < $last_element_length_b) ? -1 : 1;

        }
    }
}