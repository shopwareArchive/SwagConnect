<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Commands;

use Shopware\Commands\ShopwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use ShopwarePlugins\Connect\Components\ImageImport;
use ShopwarePlugins\Connect\Components\Logger;
use ShopwarePlugins\Connect\Components\Helper;


class ImageImportCommand extends ShopwareCommand
{
    /**
     * @var Helper
     */
    private $helper;

    public function __construct(Helper $helper) {
        parent::__construct();
        $this->helper = $helper;
    }

    protected function configure()
    {
        $this
            ->setName('connect:image:import')
            ->setDescription('Import images from connect')
            ->addArgument(
                'limit',
                InputArgument::OPTIONAL,
                'Amount of Images to import at once'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $symfonyStyle = new SymfonyStyle($input, $output);
        $limit = $input->getArgument('limit') | 10;

        $this->getImageImport()->import($limit);

        $symfonyStyle->success('Images were imported.');
    }

    /**
     * @return ImageImport
     */
    private function getImageImport()
    {
        // do not use thumbnail_manager as a dependency!!!
        // MediaService::__construct uses Shop entity
        // this also could break the session in backend when it's used in subscriber
        return new ImageImport(
            Shopware()->Models(),
            $this->helper,
            $this->container->get('thumbnail_manager'),
            new Logger(Shopware()->Db())
        );
    }
}
