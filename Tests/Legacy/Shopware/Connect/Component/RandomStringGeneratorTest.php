<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect\Component;

use ShopwarePlugins\Connect\Components\RandomStringGenerator;
use Tests\ShopwarePlugins\Connect\ConnectTestHelper;

class RandomStringGeneratorTest extends ConnectTestHelper
{
    /**
     * @var \ShopwarePlugins\Connect\Components\RandomStringGenerator
     */
    public $randomStringGenerator;

    public function setUp()
    {
        parent::setUp();

        $this->randomStringGenerator = new RandomStringGenerator();
    }

    public function testStringUniqueness()
    {
        $string = 'Lorem ipsum';

        $this->assertNotEmpty($this->randomStringGenerator->generate($string));
        $this->assertNotEquals(
            $this->randomStringGenerator->generate($string),
            $this->randomStringGenerator->generate($string)
        );
    }
}
