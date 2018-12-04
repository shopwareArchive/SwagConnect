<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Commands;

use Shopware\Commands\ShopwareCommand;
use ShopwarePlugins\Connect\Components\ConnectFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use ShopwarePlugins\Connect\Components\Helper;


class DeleteProductsCommand extends ShopwareCommand
{
    protected function configure()
    {
        $this
            ->setName('connect:delete:products')
            ->setDescription('Deletes the Products from Helukabel');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        $shopId = 5095;
        $connectFactory = new ConnectFactory();
        $productToShop = $connectFactory->getProductToShop();
        $builder = Shopware()->Models()->getConnection()->createQueryBuilder();
        $builder->select('source_id')
            ->from('s_plugin_connect_items', 'spci')
            ->where('spci.shop_id = :shopId')
            ->setParameter('shopId', $shopId, \PDO::PARAM_INT);

        $sourceIds = $builder->execute()->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($sourceIds as $sourceId) {
            $productToShop->delete($shopId, $sourceId);
        }

        $symfonyStyle->success('Products successfully deleted.');
    }
}
