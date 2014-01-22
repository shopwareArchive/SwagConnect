<?php

namespace Shopware\Bepado\Subscribers;

/**
 * Cronjob callback
 *
 * Class CronJob
 * @package Shopware\Bepado\Subscribers
 */
class CronJob extends BaseSubscriber
{
    public function getSubscribedEvents()
    {
        return array(
            'Shopware_CronJob_ImportImages' => 'importImages',
        );
    }

    /**
     * Import images of new products
     *
     * @param \Shopware_Components_Cron_CronJob $job
     * @return bool
     */
    public function importImages(\Shopware_Components_Cron_CronJob $job)
    {
        $helper = $this->getHelper();

        $helper->importImages();

        return true;
    }
}