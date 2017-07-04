<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Commands;

use Shopware\Commands\ShopwareCommand;
use Shopware\Components\ConfigWriter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ApiEndpointCommand extends ShopwareCommand
{
    protected function configure()
    {
        $this
            ->setName('connect:endpoint:set')
            ->setDescription('Set the API endpoint to a custom location')
            ->addArgument(
                'api-endpoint',
                InputArgument::REQUIRED,
                'URL to of the API endpoint.'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ConfigWriter $configWriter */
        $configWriter = $this->container->get('config_writer');
        $symfonyStyle = new SymfonyStyle($input, $output);
        $apiEndpoint = $input->getArgument('api-endpoint');

        $host = parse_url($apiEndpoint, PHP_URL_HOST);
        if (!$host) {
            $host = $apiEndpoint;
        }

        $configWriter->save('connectDebugHost', $host);
        $symfonyStyle->success('Endpoint was updated successfully to ' . $host);
    }
}
