<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect\Component\Translations;

use ShopwarePlugins\Connect\Components\Translations\LocaleMapper;
use Tests\ShopwarePlugins\Connect\ConnectTestHelper;

class LocaleMapperTest extends ConnectTestHelper
{
    public function testGetIso639()
    {
        $this->assertEquals('de', LocaleMapper::getIso639('de_DE'));
        $this->assertEquals('en', LocaleMapper::getIso639('en_GB'));
        $this->assertEquals('nl', LocaleMapper::getIso639('nl_NL'));
    }

    public function testGetShopwareLocale()
    {
        $this->assertEquals('de_DE', LocaleMapper::getShopwareLocale('de'));
        $this->assertEquals('en_GB', LocaleMapper::getShopwareLocale('en'));
        $this->assertEquals('nl_NL', LocaleMapper::getShopwareLocale('nl'));
    }
}
