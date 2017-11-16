<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests;

use Doctrine\DBAL\Connection;
use ShopwarePlugins\Connect\Tests\InitResourceDbSubscriber;
use Shopware\Kernel;

trait KernelTestCaseTrait
{
    /**
     * @var Kernel
     */
    public static $kernel;

    /**
     * @var bool
     */
    private $inTransaction = false;

    /**
     * @var array
     */
    private $importedFiles = [];

    /**
     * @var bool
     */
    private $autoReboot = false;

    /**
     * @var bool
     */
    private $commonFixturesImported = false;

    /**
     * @var bool
     */
    private $disableCommonFixtures = false;

    /**
     * @var array
     */
//    private $commonTestFixtures = [
//        __DIR__ . '/../../common_user_test_fixtures.sql',
//    ];

    /**
     * @param bool $set
     */
    public function setAutoReboot($set = true)
    {
        $this->autoReboot = $set;
    }

    /**
     * @param bool $set
     */
    public function disableCommonFixtures($set = true)
    {
        $this->disableCommonFixtures = $set;
    }

    /**
     * @before
     */
    protected function startTransactionBefore()
    {
        \Zend_Session::$_unitTestEnabled = true;

        $this->inTransaction = true;
        $connection = self::getKernel()->getContainer()->get('dbal_connection');
        $connection->beginTransaction();

        $files = array_keys($this->importedFiles);
        $this->importedFiles = [];

        foreach ($files as $filePath) {
            $this->importFixturesFileOnce($filePath);
        }
    }

    /**
     * @after
     */
    protected function stopTransactionAfter()
    {
        $this->disableCommonFixtures(false);
        $this->commonFixturesImported = false;

        if (!$this->inTransaction) {
            if ($this->autoReboot) {
                self::terminateKernel();
                $this->autoReboot = false;
            }

            return;
        }

        $this->inTransaction = false;
        /** @var Connection $connection */
        $connection = self::getKernel()->getContainer()->get('dbal_connection');

        if (!$connection->isTransactionActive()) {
            if ($this->autoReboot) {
                self::terminateKernel();
                $this->autoReboot = false;
            }

            return;
        }

        $connection->rollBack();

        if ($this->autoReboot) {
            self::terminateKernel();
            $this->autoReboot = false;
        }
    }

    public static function bootKernel($environment = 'test')
    {
        self::$kernel = new TestKernel($environment, true);
        self::$kernel->boot();

        restore_error_handler();
        restore_exception_handler();

        self::getKernel()->getContainer()->get('events')->addSubscriber(new InitResourceDbSubscriber());
        self::getKernel()->getContainer()->reset('db');
    }

    public static function terminateKernel()
    {
        if (self::$kernel) {
            self::$kernel->free();
            self::$kernel = null;
            gc_collect_cycles();
        }
    }

    /**
     * @param string $environment
     * @return Kernel
     */
    public static function getKernel($environment = 'swagconnecttest')
    {
        if (!self::$kernel) {
            self::bootKernel($environment);
        }

        return self::$kernel;
    }

    /**
     * @param string $filePath
     */
    protected function importFixturesFileOnce($filePath)
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException('Could not find file ' . $filePath);
        }

        if (array_key_exists($filePath, $this->importedFiles)) {
            return;
        }

        $this->importedFiles[$filePath] = true;

        if (!$this->inTransaction) {
            return;
        }

        $sql = file_get_contents($filePath);

        if (!$sql) {
            return;
        }

        $this->importFixtures($sql);
    }

    /**
     * @return bool
     */
    public function isShopware53()
    {
        return self::getKernel()->getContainer()->has('customer_search.dbal.number_search');
    }

    /**
     * @param string $sql
     * @throws \Exception
     */
    public function importFixtures($sql)
    {
        $defaultSql = $this->loadCommonFixtureSql();

        if ($defaultSql) {
            $sql = $defaultSql . $sql;
        }

        $connection = self::getKernel()->getContainer()->get('dbal_connection');

        $sqlStatements = explode(";\n", $sql);

        foreach ($sqlStatements as $sqlStatement) {
            if (!trim($sqlStatement)) {
                continue;
            }

            $connection->exec(trim($sqlStatement));
        }

        if (! (int) $connection->errorCode()) {
            return;
        }

        throw new \Exception('unable to import fixtures ' . print_r($connection->errorInfo(), true));
    }

    public function performApiLogin()
    {
        \Zend_Session::$_unitTestEnabled = true;

        $adapter = new class implements \Zend_Auth_Adapter_Interface {
            /**
             * Performs an authentication attempt
             *
             * @throws \Zend_Auth_Adapter_Exception If authentication cannot be performed
             * @return \Zend_Auth_Result
             */
            public function authenticate()
            {
                return new \Zend_Auth_Result(\Zend_Auth_Result::SUCCESS, ['id' => 1, 'username' => 'demo']);
            }
        };

        $auth = \Shopware_Components_Auth::getInstance();
        $auth->setBaseAdapter($adapter);
        $auth->addAdapter($adapter);

        self::getKernel()->getContainer()->set('auth', $auth);
    }

    public function performB2bContactLogin()
    {
        $this->performB2bLogin(DebtorFactoryTrait::createContactIdentity());
    }

    public function performB2bDebtorLogin()
    {
        $this->performB2bLogin(DebtorFactoryTrait::createDebtorIdentity());
    }

    public function performB2bSalesRepresentativeLogin()
    {
        $this->performB2bLogin(SalesRepresentativeFactoryTrait::createSalesRepresentativeIdentity());
    }

    public function performB2bSalesRepresentativeWithClientLogin()
    {
        $this->performB2bLogin(SalesRepresentativeFactoryTrait::createSalesRepresentativeIdentityWithClient());
    }

    public function performB2bSalesRepresentativeDebtorLogin()
    {
        $this->performB2bLogin(SalesRepresentativeFactoryTrait::createDebtorSalesRepresentativeIdentity());
    }

    /**
     * @return string
     */
    protected function loadCommonFixtureSql()
    {
        $sql = '';

        if (!$this->commonFixturesImported && !$this->disableCommonFixtures) {
            $this->commonFixturesImported = true;
            $this->disableCommonFixtures(false);
            foreach ($this->commonTestFixtures as $commonFile) {
                $sql .= file_get_contents($commonFile);
            }
        }

        return $sql;
    }
}
