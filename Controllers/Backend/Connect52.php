<?php

use ShopwarePlugins\Connect\Controllers\Backend\ConnectBaseController;

class Shopware_Controllers_Backend_Connect extends ConnectBaseController implements \Shopware\Components\CSRFWhitelistAware
{
    public function getWhitelistedCSRFActions()
    {
        return ['login', 'autoLogin'];
    }
}