<?php
/**
 * Shopware 4.0
 * Copyright © 2013 shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\Bepado\Components;

use Shopware\Bepado\Components\Gateway\ProductTranslationsGateway;
use Shopware\Bepado\Components\Translations\LocaleMapper;
use Shopware\Components\Model\ModelManager;
use Bepado\SDK\Struct\Product;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Configurator\Group;
use Shopware\Models\Article\Configurator\Option;
use Shopware\Models\Article\Configurator\Set;

/**
 * @category  Shopware
 * @package   Shopware\Plugins\SwagBepado
 */
class VariantConfigurator
{
    /**
     * @var ModelManager
     */
    private $manager;

    private $translationGateway;

    private $localeRepository;

    private $shopRepository;

    public function __construct(ModelManager $manager, ProductTranslationsGateway $translationsGateway)
    {
        $this->manager = $manager;
        $this->translationGateway = $translationsGateway;
    }

    /**
     * Configure variant group, options and configurator set
     *
     * @param \Bepado\SDK\Struct\Product $product
     * @param \Shopware\Models\Article\Detail $detail
     */
    public function configureVariantAttributes(Product $product, Detail $detail)
    {
        $article = $detail->getArticle();
        $detailOptions = $detail->getConfiguratorOptions();
        if (!$article->getConfiguratorSet()) {
            $configSet = new Set();
            $configSet->setName('Set-' . $article->getName());
            $configSet->setArticles(array($article));
            $article->setConfiguratorSet($configSet);
        } else {
            $configSet = $article->getConfiguratorSet();
        }

        foreach ($product->variant as $key => $value) {
            $group = $this->getGroupByName($configSet, $key);
            $option = $this->getOrCreateOptionByName($configSet, $group, $value);

            $configSet = $this->addGroupToConfiguratorSet($configSet, $group);
            $configSet = $this->addOptionToConfiguratorSet($configSet, $option);

            $this->manager->persist($option);
            $this->manager->persist($group);
            $this->manager->persist($configSet);
            $detailOptions[] = $option;

            //translate configurator groups and configurator options
            $this->addGroupTranslation($group, $product);
            $this->addOptionTranslation($option, $product);
        }

        if (count($product->variant) > 0) {
            $detail->setConfiguratorOptions($detailOptions);
            $this->manager->persist($article);
            $this->manager->persist($detail);
            $this->manager->flush();
        }
    }

    /**
     * Creates variant configurator group
     *
     * @param string $name
     * @return \Shopware\Models\Article\Configurator\Group
     */
    public function createConfiguratorGroup($name)
    {
        $latestGroup = $this->manager
            ->getRepository('Shopware\Models\Article\Configurator\Group')
            ->findOneBy(array(), array('position' => 'DESC'));

        $position = $latestGroup ? $latestGroup->getPosition() + 1 : 1;

        $group = new Group();
        $group->setName($name);
        $group->setPosition($position);

        return $group;
    }

    /**
     * Adds group to configurator set if it does not exist
     *
     * @param Set $set
     * @param Group $group
     * @return Set
     */
    private function addGroupToConfiguratorSet(Set $set, Group $group)
    {
        $configuratorGroups = $set->getGroups();
        $isExists = false;
        /** @var \Shopware\Models\Article\Configurator\Group $configuratorGroup */
        foreach ($configuratorGroups as $configuratorGroup) {
            if ($configuratorGroup->getName() === $group->getName()) {
                $isExists = true;
            }
        }

        if ($isExists === false) {
            $configuratorGroups[] = $group;
            $set->setGroups($configuratorGroups);
            $this->manager->persist($set);
        }

        return $set;
    }

    /**
     * Adds option to configurator set if it does not exist
     *
     * @param Set $set
     * @param Option $option
     * @return Set
     */
    private function addOptionToConfiguratorSet(Set $set, Option $option)
    {
        $configSetOptions = $set->getOptions();
        $isExists = false;
        /** @var \Shopware\Models\Article\Configurator\Option $option */
        foreach($configSetOptions as $configSetOption) {
            if ($configSetOption->getName() === $option->getName()) {
                $isExists = true;
            }
        }

        if ($isExists === false) {
            $configSetOptions[] = $option;
            $set->setOptions($configSetOptions);
        }

        return $set;
    }

    /**
     * Finds group in already assigned configurator set groups.
     * If it does not exist, then create it.
     *
     * @param Set $set
     * @param $groupName
     * @return Group
     */
    private function getGroupByName(Set $set, $groupName)
    {
        /** @var \Shopware\Models\Article\Configurator\Group $group */
        foreach ($set->getGroups() as $group) {
            if ($group->getName() === $groupName) {
                return $group;
            }
        }

        $repository = $this->manager->getRepository('Shopware\Models\Article\Configurator\Group');
        $group = $repository->findOneBy(array('name' => $groupName));

        if (empty($group)) {
            $group = $this->createConfiguratorGroup($groupName);
        }

        return $group;
    }

    /**
     * Find option in already assigned configurator set options.
     * If it does not exist, then create it.
     *
     * @param Set $set
     * @param Group $group
     * @param $optionName
     * @return null|object|Option
     */
    private function getOrCreateOptionByName(Set $set, Group $group, $optionName)
    {
        $configSetOptions = $set->getOptions();

        /** @var \Shopware\Models\Article\Configurator\Option $configSetOption */
        foreach ($configSetOptions as $configSetOption) {
            if ($configSetOption->getName() === $optionName
                && $configSetOption->getGroup()->getId() == $group->getId()
            ) {
                return $configSetOption;
            }
        }

        $optionsRepository = $this->manager->getRepository('Shopware\Models\Article\Configurator\Option');
        $option = $optionsRepository->findOneBy(array('name' => $optionName, 'group' => $group));

        if (empty($option)) {
            $option = new Option();
            $option->setName($optionName);
            $option->setGroup($group);
            $optionPositionsCount = count($group->getOptions());
            $optionPositionsCount++;
            $option->setPosition($optionPositionsCount);
            $groupOptions = $group->getOptions();
            $groupOptions->add($option);
            $group->setOptions($groupOptions);
            $this->manager->persist($group);
            $this->manager->persist($option);
        }

        return $option;
    }

    private function addGroupTranslation(Group $group, Product $product)
    {
        /** @var \Bepado\SDK\Struct\Translation $translation */
        foreach ($product->translations as $key => $translation) {
            if (!array_key_exists($group->getName(), $translation->variantLabels)) {
                continue;
            }

            /** @var \Shopware\Models\Shop\Locale $locale */
            $locale = $this->getLocaleRepository()->findOneBy(array('locale' => LocaleMapper::getShopwareLocale($key)));

            /** @var \Shopware\Models\Shop\Shop $shop */
            $shop = $this->getShopRepository()->findOneBy(array('locale' => $locale));
            if (!$shop) {
                continue;
            }

            foreach ($translation->variantLabels as $groupKey => $groupTranslation) {
                if ($groupKey === $group->getName()) {
                    $this->translationGateway->addGroupTranslation($groupTranslation, $group->getId(), $shop->getId());
                }
            }
        }
    }

    private function addOptionTranslation(Option $option, Product $product)
    {
        /** @var \Bepado\SDK\Struct\Translation $translation */
        foreach ($product->translations as $key => $translation) {
            if (!array_key_exists($option->getName(), $translation->variantValues)) {
                continue;
            }

            /** @var \Shopware\Models\Shop\Locale $locale */
            $locale = $this->getLocaleRepository()->findOneBy(array('locale' => LocaleMapper::getShopwareLocale($key)));
var_dump(LocaleMapper::getShopwareLocale($key));
            /** @var \Shopware\Models\Shop\Shop $shop */
            $shop = $this->getShopRepository()->findOneBy(array('locale' => $locale));
            if (!$shop) {
                var_dump('Shop not found');
                continue;
            }

            foreach ($translation->variantValues as $optionKey => $optionTranslation) {
                if ($optionKey === $option->getName()) {
                    $this->translationGateway->addOptionTranslation($optionTranslation, $option->getId(), $shop->getId());
                }
            }
        }
    }

    private function getShopRepository()
    {
        if (!$this->shopRepository) {
            $this->shopRepository = $this->manager->getRepository('Shopware\Models\Shop\Shop');
        }

        return $this->shopRepository;
    }

    private function getLocaleRepository()
    {
        if (!$this->localeRepository) {
            $this->localeRepository = $this->manager->getRepository('Shopware\Models\Shop\Locale');
        }

        return $this->localeRepository;
    }
} 