<?php

namespace Shopware\Connect\Subscribers;

use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Components\Theme\LessDefinition;

class Less extends BaseSubscriber
{
	public function getSubscribedEvents()
	{
		return array(
            'Theme_Compiler_Collect_Plugin_Less' =>  'addLessFiles'
		);
	}

	/**
	 * Provide the needed less files
	 *
	 * @param \Enlight_Event_EventArgs $args
	 * @return Doctrine\Common\Collections\ArrayCollection
	 */
	public function addLessFiles(\Enlight_Event_EventArgs $args)
	{
		$less = new LessDefinition(
		//configuration
			array(),

			//less files to compile
			array(
                dirname(__DIR__) . '/Views/responsive/frontend/_public/src/less/all.less'
			),

			//import directory
			dirname(__DIR__)
		);

		return new ArrayCollection(array($less));
	}
}