<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests;

/**
 * Every unit test should depend on this abstract class. It is only used to prevent a call to the Shopware() singleton.
 */
abstract class AbstractConnectUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Shopware|null
     */
    private static $appState = null;

    protected function setUp()
    {
        @trigger_error('Please use @before annotation');
    }

    protected function tearDown()
    {
        @trigger_error('Please use @after annotation');
    }

    /**
     * @before
     */
    public function killShopwareFunctionBefore()
    {
        self::$appState = Shopware();
        Shopware(new EmptyShopwareApplication());
    }

    /**
     * @after
     */
    public function restoreShopwareFunctionAfter()
    {
        Shopware(self::$appState);
    }
}

class EmptyShopwareApplication
{
    public function __call($name, $arguments)
    {
        throw new RestrictedCallException('Restricted to call ' . $name . ' because you should not have a test kernel in this test case.');
    }
}

class RestrictedCallException extends \RuntimeException
{
}