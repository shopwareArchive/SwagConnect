<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components;

class ConfigFactory
{
    public static function getConfigInstance()
    {
        return new Config(Shopware()->Models(), Shopware()->Container()->get('shopware.plugin.cached_config_reader'));
    }
}
