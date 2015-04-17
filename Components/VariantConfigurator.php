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

    public function __construct(ModelManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Configure variant group, options and configurator set
     *
     * @param \Bepado\SDK\Struct\Product $product
     * @param \Shopware\Models\Article\Detail $detail
     */
    public function configureVariantAttributes(Product $product, Detail $detail)
    {
        $repository = $this->manager->getRepository('Shopware\Models\Article\Configurator\Group');
        $optionsRepository = $this->manager->getRepository('Shopware\Models\Article\Configurator\Option');
        foreach ($product->variant as $key => $value) {
            $group = $repository->findOneBy(array('name' => $key));
            if (empty($group)) {
                $group = $this->createConfiguratorGroup($key);
            }

            $option = $optionsRepository->findOneBy(array('name' => $value, 'groupId' => $group->getId()));
            $optionPositionsCount = count($group->getOptions());
            if (empty($option)) {
                $option = new Option();
                $option->setName($value);
                $option->setGroup($group);
                $optionPositionsCount++;
                $option->setPosition($optionPositionsCount);
                $groupOptions = $group->getOptions();
                $groupOptions->add($option);
                $group->setOptions($groupOptions);
                $this->manager->persist($group);
                $this->manager->persist($option);
            }
        }

        $article = $detail->getArticle();
        if (!$article->getConfiguratorSet()) {
            $configSet = new Set();
            $configSet->setName('Set-' . $article->getName());
            $configSet->setArticles(array($article));
            $article->setConfiguratorSet($configSet);
            $configSet->setGroups(array($group));
            $configSet->setOptions(array($option));
            $this->manager->persist($configSet);
            $this->manager->persist($article);
        } else {
            $configSetOptions = $article->getConfiguratorSet()->getOptions();
            $configSetOptions[] = $option;
            $article->getConfiguratorSet()->setOptions($configSetOptions);
        }

        $detailOptions = $detail->getConfiguratorOptions();
        $detailOptions[] = $option;
        $detail->setConfiguratorOptions($detailOptions);

        $this->manager->persist($detail);
        $this->manager->flush();
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

        $this->manager->persist($group);
        $this->manager->flush();

        return $group;
    }
} 