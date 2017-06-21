<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components;

use Shopware\Components\Model\ModelManager;
use Shopware\Connect\Struct\Product;
use Shopware\Models\Article\Configurator\Group;
use Shopware\Models\Article\Configurator\Option;
use Shopware\Models\Article\Configurator\Set;
use Shopware\Models\Article\Detail;
use ShopwarePlugins\Connect\Components\Gateway\ProductTranslationsGateway;
use ShopwarePlugins\Connect\Components\Translations\LocaleMapper;

/**
 * @category  Shopware
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
     * @param \Shopware\Connect\Struct\Product $product
     * @param \Shopware\Models\Article\Detail  $detail
     */
    public function configureVariantAttributes(Product $product, Detail $detail)
    {
        if (count($product->variant) === 0) {
            return;
        }

        $article = $detail->getArticle();
        $detailOptions = $detail->getConfiguratorOptions();
        if (!$article->getConfiguratorSet()) {
            $configSet = new Set();
            $configSet->setName('Set-' . $article->getName());
            $configSet->setArticles([$article]);
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
        }

        $detail->setConfiguratorOptions($detailOptions);
        $this->manager->persist($detail);
        $this->manager->persist($article);

        $this->manager->flush();

        foreach ($product->variant as $key => $value) {
            $group = $this->getGroupByName($configSet, $key);
            $option = $this->getOrCreateOptionByName($configSet, $group, $value);

            //translate configurator groups and configurator options
            $this->addGroupTranslation($group, $product);
            $this->addOptionTranslation($option, $product);
        }
    }

    /**
     * Creates variant configurator group
     *
     * @param string $name
     *
     * @return \Shopware\Models\Article\Configurator\Group
     */
    public function createConfiguratorGroup($name)
    {
        $latestGroup = $this->manager
            ->getRepository('Shopware\Models\Article\Configurator\Group')
            ->findOneBy([], ['position' => 'DESC']);

        $position = $latestGroup ? $latestGroup->getPosition() + 1 : 1;

        $group = new Group();
        $group->setName($name);
        $group->setPosition($position);

        return $group;
    }

    /**
     * Adds group to configurator set if it does not exist
     *
     * @param Set   $set
     * @param Group $group
     *
     * @return Set
     */
    private function addGroupToConfiguratorSet(Set $set, Group $group)
    {
        $configuratorGroups = $set->getGroups();
        /** @var \Shopware\Models\Article\Configurator\Group $configuratorGroup */
        foreach ($configuratorGroups as $configuratorGroup) {
            if ($configuratorGroup->getName() === $group->getName()) {
                return $set;
            }
        }

        $configuratorGroups[] = $group;
        $set->setGroups($configuratorGroups);
        $this->manager->persist($set);

        return $set;
    }

    /**
     * Adds option to configurator set if it does not exist
     *
     * @param Set    $set
     * @param Option $option
     *
     * @return Set
     */
    private function addOptionToConfiguratorSet(Set $set, Option $option)
    {
        $configSetOptions = $set->getOptions();
        /* @var \Shopware\Models\Article\Configurator\Option $option */
        foreach ($configSetOptions as $configSetOption) {
            if ($configSetOption->getName() === $option->getName()
                && $configSetOption->getGroup()->getName() === $option->getGroup()->getName()) {
                return $set;
            }
        }

        $configSetOptions[] = $option;
        $set->setOptions($configSetOptions);

        return $set;
    }

    /**
     * Finds group in already assigned configurator set groups.
     * If it does not exist, then create it.
     *
     * @param Set $set
     * @param $groupName
     *
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
        $group = $repository->findOneBy(['name' => $groupName]);

        if (empty($group)) {
            $group = $this->createConfiguratorGroup($groupName);
        }

        return $group;
    }

    /**
     * Find option in already assigned configurator set options.
     * If it does not exist, then create it.
     *
     * @param Set   $set
     * @param Group $group
     * @param $optionName
     *
     * @return null|object|Option
     */
    private function getOrCreateOptionByName(Set $set, Group $group, $optionName)
    {
        $configSetOptions = $set->getOptions();

        /** @var \Shopware\Models\Article\Configurator\Option $configSetOption */
        foreach ($configSetOptions as $configSetOption) {
            if ($configSetOption->getName() === $optionName
                && $configSetOption->getGroup()->getName() == $group->getName()
            ) {
                return $configSetOption;
            }
        }

        $optionsRepository = $this->manager->getRepository('Shopware\Models\Article\Configurator\Option');
        $option = $optionsRepository->findOneBy(['name' => $optionName, 'group' => $group]);

        if (empty($option)) {
            $option = new Option();
            $option->setName($optionName);
            $option->setGroup($group);
            $optionPositionsCount = count($group->getOptions());
            ++$optionPositionsCount;
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
        /** @var \Shopware\Connect\Struct\Translation $translation */
        foreach ($product->translations as $key => $translation) {
            if (!array_key_exists($group->getName(), $translation->variantLabels)) {
                continue;
            }

            /** @var \Shopware\Models\Shop\Locale $locale */
            $locale = $this->getLocaleRepository()->findOneBy(['locale' => LocaleMapper::getShopwareLocale($key)]);

            /** @var \Shopware\Models\Shop\Shop $shop */
            $shop = $this->getShopRepository()->findOneBy(['locale' => $locale]);
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
        /** @var \Shopware\Connect\Struct\Translation $translation */
        foreach ($product->translations as $key => $translation) {
            if (!array_key_exists($option->getName(), $translation->variantValues)) {
                continue;
            }

            /** @var \Shopware\Models\Shop\Locale $locale */
            $locale = $this->getLocaleRepository()->findOneBy(['locale' => LocaleMapper::getShopwareLocale($key)]);

            /** @var \Shopware\Models\Shop\Shop $shop */
            $shop = $this->getShopRepository()->findOneBy(['locale' => $locale]);
            if (!$shop) {
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
