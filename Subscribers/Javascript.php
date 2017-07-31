<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Subscribers;

use Doctrine\Common\Collections\ArrayCollection;

class Javascript extends BaseSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            'Theme_Compiler_Collect_Plugin_Javascript' =>  'addJsFiles'
        ];
    }

    /**
     * Provide the needed javascript files
     *
     * @param \Enlight_Event_EventArgs $args
     * @return ArrayCollection
     */
    public function addJsFiles(\Enlight_Event_EventArgs $args)
    {
        $jsPath = [
            dirname(__DIR__) . '/Views/responsive/frontend/_public/src/js/jquery.connect.js'
        ];

        return new ArrayCollection($jsPath);
    }
}
