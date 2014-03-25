<?php

namespace Shopware\Bepado\Components\Utils;

use Bepado\SDK\Units;

/**
 * Class UnitMapper
 * @package Shopware\Bepado\Components\Utils
 */
class UnitMapper
{

    /** @var \Shopware\Bepado\Components\Utils\Config */
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
     * Returns bepado unit
     * @param $shopwareUnit
     * @return string
     */
    public function getBepadoUnit($shopwareUnit)
    {
        // search for configured unit mapping
        $unit = $this->configComponent->getConfig($shopwareUnit);
        if ($unit) {
            return $unit;
        }

        $bepadoUnits = $this->getSdkLocalizedUnits();

        // search for same key in bepado units
        if ($bepadoUnits[$shopwareUnit]) {
            return $shopwareUnit;
        }

        // search for same label in bepado units
        $repository = $this->getUnitRepository();
        $unitModel = $repository->findOneBy(array('unit' => $shopwareUnit));

        if ($unitModel) {
            $unitName = $unitModel->getName();

            foreach ($bepadoUnits as $key => $bepadoUnit) {
                if ($bepadoUnit == $unitName) {
                    return $key;
                }
            }
        }

        return $shopwareUnit;
    }

    /**
     * Returns shopware unit
     * @param $bepadoUnit
     * @return mixed
     */
    public function getShopwareUnit($bepadoUnit)
    {
        // search for configured unit mapping
        $config = $this->configComponent->getConfigByValue($bepadoUnit);
        if ($config) {
            return $config->getName();
        }

        // search for same key in Shopware units
        $repository = $this->getUnitRepository();
        $unitModel = $repository->findOneBy(array('unit' => $bepadoUnit));

        if ($unitModel) {
            return $unitModel->getUnit();
        }

        $bepadoUnits = $this->getSdkLocalizedUnits();

        // search for same label in Shopware units
        if ($bepadoUnits[$bepadoUnit]) {
            $unitModel = $repository->findOneBy(array('name' => $bepadoUnits[$bepadoUnit]));

            if ($unitModel) {
                return $unitModel->getUnit();
            }
        }

        return $bepadoUnit;
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
     * Returns bepado units in array
     * @return array
     */
    private function getSdkLocalizedUnits()
    {
        return $this->getSdkUnits()->getLocalizedUnits();
    }

    /**
     * Returns shopware units repository instance
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