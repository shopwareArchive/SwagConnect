<?php

namespace Shopware\Bepado\Subscribers;
use Shopware\Bepado\Components\Config;
use Shopware\Bepado\Components\ImageImport;

/**
 * Cronjob callback
 *
 * Class CronJob
 * @package Shopware\Bepado\Subscribers
 */
class CronJob extends BaseSubscriber
{
    /**
     * @var \Shopware\Bepado\Components\Config
     */
    private $configComponent;

    public function getSubscribedEvents()
    {
        return array(
            'Shopware_CronJob_ImportImages' => 'importImages',
        );
    }

    /**
     * @return ImageImport
     */
    public function getImageImport()
    {
        return new ImageImport(
            Shopware()->Models(),
            $this->getHelper()
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
        $limit = $this->getConfigComponent()->getConfig('articleImagesLimitImport', 10);
        $this->getImageImport()->import($limit);

        return true;
    }

    private function getConfigComponent()
    {
        if (!$this->configComponent) {
            $this->configComponent = new Config(Shopware()->Models());
        }

        return $this->configComponent;
    }
}