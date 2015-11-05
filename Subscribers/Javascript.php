<?php

namespace Shopware\Connect\Subscribers;

use Doctrine\Common\Collections\ArrayCollection;

class Javascript extends BaseSubscriber
{
	public function getSubscribedEvents()
	{
		return array(
            'Theme_Compiler_Collect_Plugin_Javascript' =>  'addJsFiles'
		);
	}

	/**
	 * Provide the needed javascript files
	 *
	 * @param \Enlight_Event_EventArgs $args
	 * @return Doctrine\Common\Collections\ArrayCollection
	 */
	public function addJsFiles(\Enlight_Event_EventArgs $args)
	{
		$jsPath = array(
            dirname(__DIR__) . '/Views/responsive/frontend/_public/src/js/jquery.connect.js'
		);
		return new ArrayCollection($jsPath);
	}
}