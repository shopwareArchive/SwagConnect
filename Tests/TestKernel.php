<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Tests {

    use Shopware\Kernel;
    use Symfony\Component\Config\FileLocator;
    use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
    use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
    use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
    use Symfony\Component\HttpKernel\HttpKernelInterface;
    use Symfony\Component\HttpKernel\TerminableInterface;

    class TestKernel extends Kernel implements TerminableInterface
    {
        /**
         * This wraps Shopware:run execution and does not execute
         * the default dispatching process from symfony.
         * Therefore:
         * Arguments are currently ignored. No dispatcher, no response handling.
         * Shopware instance returns currently the rendered response directly.
         *
         * {@inheritdoc}
         *
         * @return SymfonyResponse
         */
        public function handle(SymfonyRequest $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
        {
            if (false === $this->booted) {
                $this->boot();
            }

            if ($this->getContainer()->has('shop')) {
                $this->getContainer()->get('shop')->setHost($request->getHost());
            }

            /** @var $front \Enlight_Controller_Front */
            $front = $this->container->get('front');

            $front->Response()->setHttpResponseCode(200);

            $request = $this->transformSymfonyRequestToEnlightRequest($request);
            $front->setRequest($request);
            $response = $front->dispatch();
            $response = $this->transformEnlightResponseToSymfonyResponse($response);

            return $response;
        }

        //TODO Remove this function when Shopware 5.2 is no longer supported
        public function transformEnlightResponseToSymfonyResponse($response)
        {
            $rawHeaders = $response->getHeaders();
            $headers = [];
            foreach ($rawHeaders as $header) {
                if (!isset($headers[$header['name']]) || !empty($header['replace'])) {
                    header_remove($header['name']);
                    $headers[$header['name']] = [$header['value']];
                } else {
                    $headers[$header['name']][] = $header['value'];
                }
            }

            $symfonyResponse = new SymfonyResponse(
                $response->getBody(),
                $response->getHttpResponseCode(),
                $headers
            );

            foreach ($response->getCookies() as $cookieContent) {
                $sfCookie = new Cookie(
                    $cookieContent['name'],
                    $cookieContent['value'],
                    $cookieContent['expire'],
                    $cookieContent['path'],
                    $cookieContent['domain'],
                    (bool) $cookieContent['secure'],
                    (bool) $cookieContent['httpOnly']
                );

                $symfonyResponse->headers->setCookie($sfCookie);
            }

            return $symfonyResponse;
        }

        public function boot($skipDatabase = false)
        {
            $result = parent::boot($skipDatabase);

            /** @var $repository \Shopware\Models\Shop\Repository */
            $repository = $this->getContainer()->get('models')->getRepository('Shopware\Models\Shop\Shop');
            $shop = $repository->getActiveDefault();
            @$shop->registerResources();

            return $result;
        }

        protected function buildContainer()
        {
            $containerBuilder = parent::buildContainer();

            $loader = new XmlFileLoader($containerBuilder, new FileLocator(__DIR__));
            $loader->load('test-services.xml');

            return $containerBuilder;
        }

        public function free()
        {
            if (\Zend_Session::isStarted() && \Zend_Session::isWritable()) {
                $this->getContainer()->get('session')->unsetAll();
                \Zend_Session::writeClose();

                unset($_SESSION);
            }

            $this->getContainer()->get('dbal_connection')->close();
            $this->getContainer()->reset('dbal_connection');

            \Smarty_Resource::$sources = [];
            \Smarty_Resource::$compileds = [];

            Shopware(new EmptyShopwareApplication());
        }

        /**
         * Terminates a request/response cycle.
         *
         * Should be called after sending the response and before shutting down the kernel.
         *
         * @param SymfonyRequest $request A Request instance
         * @param SymfonyResponse $response A Response instance
         */
        public function terminate(SymfonyRequest $request, SymfonyResponse $response)
        {
            \Smarty_Resource::$sources = [];
            \Smarty_Resource::$compileds = [];

            $this->container->reset('front');
            \Enlight_Template_Manager::$_smarty_vars = [];
        }
    }

    class EmptyShopwareApplication
    {
        public function __isset($name)
        {
            $this->throwException('isset', $name);
        }

        public function __set($name, $value)
        {
            $this->throwException('set', $name);
        }

        public function __get($name)
        {
            $this->throwException('get', $name);
        }

        public function __call($name, $arguments)
        {
            $this->throwException('call', $name);
        }

        /**
         * @param string $type
         * @param string $name
         */
        private function throwException($type, $name)
        {
            throw new \DomainException('Restricted to ' . $type . ' ' . $name . ' on Shopware() , because you should not have a kernel in this test case.');
        }
    }
}

namespace {
    class Measurement
    {
        private static $lastPingTime;

        /**
         * @param string $message
         */
        public static function ping($message)
        {
            if (self::$lastPingTime) {
                $now = time();
                $dif = $now - self::$lastPingTime;
                echo $message . ": $dif\n";
                self::$lastPingTime = $now;

                return;
            }

            self::$lastPingTime = time();
            echo "Starting with: $message\n";
        }
    }
}
