<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components;

class RandomStringGenerator
{
    /**
     * @param string $prefix
     *
     * @return string
     */
    public function generate($prefix = '')
    {
        return uniqid($prefix);
    }
}
