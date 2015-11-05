<?php

namespace Shopware\Connect\Subscribers;
use Shopware\Connect\Components\Config;
use Shopware\Connect\Components\ImageImport;
use Shopware\Connect\Components\Logger;

/**
 * Cronjob callback
 *
 * Class CronJob
 * @package Shopware\Connect\Subscribers
 */
class CronJob extends BaseSubscriber
{
    /**
     * @var \Shopware\Connect\Components\Config
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
            $this->getHelper(),
            new Logger(Shopware()->Db())
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