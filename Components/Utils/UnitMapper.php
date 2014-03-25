<?php

namespace Shopware\Bepado\Components\Utils;

use Bepado\SDK\Units;
/**
 * Class UnitMapper
 * @package Shopware\Bepado\Components\Utils
 */
class UnitMapper
{

    private $configComponent;

    private $manager;

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

        /** @var \Bepado\SDK\Units $bepadoUnits */
        $units = new Units();
        $bepadoUnits = $units->getLocalizedUnits();

        // search for same key in bepado units
        if ($bepadoUnits[$shopwareUnit]) {
            return $shopwareUnit;
        }

        // search for same label in bepado units
        $repository = $this->manager->getRepository('Shopware\Models\Article\Unit');
        $unitModel = $repository->findOneBy(array('unit' => $shopwareUnit));
        $unitName = $unitModel->getName();

        foreach ($bepadoUnits as $key => $bepadoUnit) {
            if ($bepadoUnit == $unitName) {
                return $key;
            }
        }

        return $shopwareUnit;
    }
}