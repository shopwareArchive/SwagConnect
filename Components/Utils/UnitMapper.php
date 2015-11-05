<?php

namespace Shopware\Connect\Components\Utils;

use Bepado\SDK\Units;
use Shopware\Connect\Components\Config;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Unit;


/**
 * Class UnitMapper
 * @package Shopware\Connect\Components\Utils
 */
class UnitMapper
{

    /** @var \Shopware\Connect\Components\Config */
    private $configComponent;

    private $manager;

    /** @var  \Bepado\SDK\Units */
    private $sdkUnits;

    private $repository;

    /**
     * @param Config $configComponent
     * @param ModelManager $manager
     */
    public function __construct(
        Config $configComponent,
        ModelManager $manager
    )
    {
        $this->configComponent = $configComponent;
        $this->manager = $manager;
    }

    /**
     * Returns connect unit
     * @param $shopwareUnit
     * @return string
     */
    public function getConnectUnit($shopwareUnit)
    {
        // search for configured unit mapping
        $unit = $this->configComponent->getConfig($shopwareUnit);
        if ($unit) {
            return $unit;
        }

        $connectUnits = $this->getSdkLocalizedUnits();

        // search for same key in connect units
        if ($connectUnits[$shopwareUnit]) {
            return $shopwareUnit;
        }

        // search for same label in connect units
        $repository = $this->getUnitRepository();
        $unitModel = $repository->findOneBy(array('unit' => $shopwareUnit));

        if ($unitModel) {
            $unitName = $unitModel->getName();

            foreach ($connectUnits as $key => $connectUnit) {
                if ($connectUnit == $unitName) {
                    return $key;
                }
            }

            // search in "de" connect units
            $deConnectUnits = $this->getSdkLocalizedUnits('de');
            foreach ($deConnectUnits as $key => $connectUnit) {
                if ($connectUnit == $unitName) {
                    return $key;
                }
            }
        }

        return $shopwareUnit;
    }

    /**
     * Returns shopware unit
     * @param $connectUnit
     * @return mixed
     */
    public function getShopwareUnit($connectUnit)
    {
        // search for configured unit mapping
        $config = $this->configComponent->getConfigByValue($connectUnit);
        if ($config) {
            return $config->getName();
        }

        // search for same key in Shopware units
        $repository = $this->getUnitRepository();
        $unitModel = $repository->findOneBy(array('unit' => $connectUnit));

        if ($unitModel) {
            return $unitModel->getUnit();
        }

        $connectUnits = $this->getSdkLocalizedUnits();

        // search for same label in Shopware units
        if ($connectUnits[$connectUnit]) {
            $unitModel = $repository->findOneBy(array('name' => $connectUnits[$connectUnit]));

            if ($unitModel) {
                return $unitModel->getUnit();
            }
        }

        // search for same label in "de" Shopware units
        $deConnectUnits = $this->getSdkLocalizedUnits('de');
        if ($deConnectUnits[$connectUnit]) {
            $unitModel = $repository->findOneBy(array('name' => $deConnectUnits[$connectUnit]));

            if ($unitModel) {
                return $unitModel->getUnit();
            }
        }

        if ($this->configComponent->getConfig('createUnitsAutomatically', false) == true) {
            // only german units for now
            $unit = new Unit();
            $unit->setName($deConnectUnits[$connectUnit]);
            $unit->setUnit($connectUnit);
            $this->manager->persist($unit);
            $this->manager->flush();

            return $unit->getUnit();
        }

        return $connectUnit;
    }

    /**
     * @return Units
     */
    private function getSdkUnits()
    {
        if ($this->sdkUnits === null) {
            $this->sdkUnits = new Units();
        }

        return $this->sdkUnits;
    }

    /**
     * Returns connect units
     * @param string $locale
     * @return array\
     */
    private function getSdkLocalizedUnits($locale = 'en')
    {
            return $this->getSdkUnits()->getLocalizedUnits($locale);
    }

    /**
     * Returns Shopware units repository instance
     * @return mixed
     */
    private function getUnitRepository()
    {
        if ($this->repository === null) {
            $this->repository = $this->manager->getRepository('Shopware\Models\Article\Unit');
        }

        return $this->repository;
    }

}