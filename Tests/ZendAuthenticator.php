<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests;

class ZendAuthenticator implements \Zend_Auth_Adapter_Interface
{
    /**
     * Performs an authentication attempt
     *
     * @throws \Zend_Auth_Adapter_Exception If authentication cannot be performed
     * @return \Zend_Auth_Result
     */
    public function authenticate()
    {
        return new \Zend_Auth_Result(\Zend_Auth_Result::SUCCESS, ['id' => 1, 'username' => 'demo']);
    }
}
