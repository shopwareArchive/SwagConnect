<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Subscribers;

use Doctrine\Common\Collections\ArrayCollection;
use Enlight\Event\SubscriberInterface;
use Shopware\Components\Theme\LessDefinition;

class Less implements SubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Theme_Compiler_Collect_Plugin_Less' =>  'addLessFiles'
        ];
    }

    /**
     * Provide the needed less files
     *
     * @param \Enlight_Event_EventArgs $args
     * @return LessDefinition[]|ArrayCollection
     */
    public function addLessFiles(\Enlight_Event_EventArgs $args)
    {
        $less = new LessDefinition(
        //configuration
            [],

            //less files to compile
            [
                dirname(__DIR__) . '/Views/frontend/_public/src/less/all.less'
            ],

            //import directory
            dirname(__DIR__)
        );

        return new ArrayCollection([$less]);
    }
}
