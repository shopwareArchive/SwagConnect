<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests;

use Shopware\Components\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;

trait CommandTestCaseTrait
{
    use DatabaseTestCaseTrait;

    /**
     * @param string $command
     * @return array
     */
    protected function runCommand($command)
    {
        $application = new Application(Shopware()->Container()->get('kernel'));
        $application->setAutoExit(true);

        $fp = tmpfile();
        $input = new StringInput($command);
        $output = new StreamOutput($fp);
        $application->doRun($input, $output);

        $consoleOutput = $this->readConsoleOutput($fp);

        return explode(PHP_EOL, $consoleOutput);
    }

    /**
     * @param $fp
     * @return string
     */
    private function readConsoleOutput($fp)
    {
        fseek($fp, 0);
        $output = '';
        while (!feof($fp)) {
            $output = fread($fp, 4096);
        }
        fclose($fp);

        return $output;
    }
}
