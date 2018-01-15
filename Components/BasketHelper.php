<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Components;

use Shopware\Connect\Gateway\PDO;
use Shopware\Connect\Struct\CheckResult;
use Shopware\Connect\SDK;
use Shopware\Connect\Struct\Message;
use Shopware\Connect\Struct\Product;

/**
 * Handles the basket manipulation. Most of it is done by modifying the template variables shown to the user.
 * Once we have new basket and order core classes, this should be refactored.
 *
 * Class BasketHelper
 * @package ShopwarePlugins\Connect\Components
 */
class BasketHelper
{
    /**
     * The basket array decorated by this class
     * @var array
     */
    protected $basket;

    /**
     * Array of connect product structs
     * @var array
     */
    protected $connectProducts = [];

    /**
     * connect content as formated by shopware
     *
     * @var array
     */
    protected $connectContent = [];

    /**
     * Array of connect shops affected by this basket
     *
     * @var array
     */
    protected $connectShops = [];

    /**
     * @var \Shopware\Connect\Struct\CheckResult
     */
    protected $checkResult;

    /**
     * The original shopware shipping costs
     *
     * @var float
     */
    protected $originalShippingCosts = 0;

    /**
     * Should there be a connect hint in the template
     *
     * @var bool
     */
    protected $showCheckoutShopInfo;

    /**
     * @var \Shopware\Connect\SDK
     */
    protected $sdk;

    /**
     * @var \Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    protected $database;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var \Shopware\Connect\Gateway\PDO
     */
    protected $connectGateway;

    /**
     * Indicates if the basket has only connect products or not
     *
     * @var bool
     */
    protected $onlyConnectProducts = false;

    /**
     * @param \Enlight_Components_Db_Adapter_Pdo_Mysql $database
     * @param \Shopware\Connect\SDK $sdk
     * @param Helper $helper
     * @param $showCheckoutShopInfo
     */
    public function __construct(
        \Enlight_Components_Db_Adapter_Pdo_Mysql $database,
        SDK $sdk,
        Helper $helper,
        PDO $connectGateway,
        $showCheckoutShopInfo)
    {
        $this->database = $database;
        $this->sdk = $sdk;
        $this->helper = $helper;
        $this->connectGateway = $connectGateway;
        $this->showCheckoutShopInfo = $showCheckoutShopInfo;
    }

    /**
     * Prepare the basket for connect
     *
     * @return void
     */
    public function prepareBasketForConnect()
    {
        $this->buildProductsArray();
        $this->buildShopsArray();
    }

    /**
     * Build array of connect products. This will remove connect products from the 'content' array
     */
    protected function buildProductsArray()
    {
        $this->connectProducts = [];
        $this->connectContent = [];

        $this->basket['contentOrg'] = $this->basket['content'];

        foreach ($this->basket['content'] as $key => &$row) {
            if (!empty($row['mode'])) {
                continue;
            }

            $articleDetailId = $row['additional_details']['articleDetailsID'];
            if ($this->helper->isRemoteArticleDetailDBAL($articleDetailId) === false) {
                continue;
            }

            $shopProductId = $this->helper->getShopProductId($articleDetailId);
            $products = $this->getHelper()->getRemoteProducts([$shopProductId->sourceId], $shopProductId->shopId);
            if (empty($products)) {
                continue;
            }
            $product = reset($products);
            if ($product === null || $product->shopId === null) {
                continue;
            }
            $row['connectShopId'] = $product->shopId;
            $this->connectProducts[$product->shopId][$product->sourceId] = $product;
            $this->connectContent[$product->shopId][$product->sourceId] = $row;

            //if($actionName == 'cart') {
            unset($this->basket['content'][$key]);
            //}
        }
    }

    /**
     * Build array of connect remote shops
     */
    protected function buildShopsArray()
    {
        $this->connectShops = [];

        $this->basket['content'] = array_values($this->basket['content']);
        foreach ($this->connectContent as $shopId => $items) {
            $this->connectShops[$shopId] = $this->getSdk()->getShop($shopId);
        }
    }

    /**
     * Returns the quantity of a given product in the sw basket
     *
     * @param \Shopware\Connect\Struct\Product $product
     * @return mixed
     */
    public function getQuantityForProduct(Product $product)
    {
        if (isset($this->connectContent[$product->shopId]) &&
            isset($this->connectContent[$product->shopId][$product->sourceId])
        ) {
            return (int) $this->connectContent[$product->shopId][$product->sourceId]['quantity'];
        } elseif (isset($this->basket['content'][$product->sourceId])) {
            return (int) $this->basket['content'][$product->sourceId]['quantity'];
        }

        return 1;
    }

    /**
     * This method will check, if any *real* products from the local shop are in the basket. If this is not the
     * case, this method will:
     *
     * - set the first connect shop as content of the default basket ($basket['content'])
     * - remove any surcharges, vouchers and  discount from the original basket(!)
     *
     * @return bool|mixed
     */
    public function fixBasket()
    {
        // Filter out basket items which cannot be purchased on their own
        $content = array_filter($this->basket['content'], function ($item) {
            switch ((int) $item['modus']) {
                    case 0: // Default products
                    case 1: // Premium products
                        return true;
                    default:
                        return false;
                }
        });

        // If only connect products are in the basket, do the basket fix
        if (empty($content)) {
            $this->onlyConnectProducts = true;

            $this->removeNonProductsFromBasket();
            $this->basket = $this->removeDefaultShipping($this->basket);

            $connectContent = $this->getConnectContent();

            // Make the first connect shop the default basket-content
            reset($connectContent);
            $shopId = current(array_keys($connectContent));
            $this->basket['content'] = $connectContent[$shopId];
            unset($this->connectContent[$shopId]);

            return $shopId;
        }

        return false;
    }

    /**
     * Remove shipping costs from given basket
     *
     * @param array $basket
     * @return array
     */
    private function removeDefaultShipping(array $basket)
    {
        $basket['AmountNumeric'] -= $basket['sShippingcosts'];
        $basket['AmountNetNumeric'] -= $basket['sShippingcostsNet'];

        $basketHasTax = $this->hasTax();
        if (!empty($this->basket['sAmountWithTax'])) {
            if ($basketHasTax) {
                $this->basket['sAmountWithTax'] -= $basket['sShippingcosts'];
            } else {
                $this->basket['sAmountWithTax'] -= $basket['sShippingcostsNet'];
            }
        }

        if ($basketHasTax) {
            $basket['sAmount'] -= $basket['sShippingcosts'];
        } else {
            $basket['sAmount'] -= $basket['sShippingcostsNet'];
        }

        $basket['sShippingcosts'] = 0;
        $basket['sShippingcostsNet'] = 0;
        $basket['sShippingcostsWithTax'] = 0;

        return $basket;
    }

    /**
     * Removes non-connect products from the database and fixes the basket variables
     */
    protected function removeNonProductsFromBasket()
    {
        $removeItems = [
            'ids' => [],
            'price' => 0,
            'netprice' => 0,
            'sessionId' => null
        ];

        // Build array of ids and amount to fix the basket later
        foreach ($this->basket['content'] as  $key => $product) {
            $removeItems['ids'][] = $product['id'];
            $removeItems['price'] += $product['price'] * $product['quantity'];
            $removeItems['amountWithTax'] += $product['amountWithTax'] * $product['quantity'];
            $removeItems['netprice'] += $product['netprice'] * $product['quantity'];
            $removeItems['tax'] += str_replace(',', '.', $product['tax']) * $product['quantity'];
            $removeItems['sessionId'] = $product['sessionID'];

            // Remove surcharge, cannot be deleted with SQL
            if ($product['modus'] == 4) {
                unset($this->basket['content'][$key]);
            }
        }

        if (empty($removeItems['ids'])) {
            return;
        }

        // Fix basket prices
        $this->basket['AmountNumeric'] -= $removeItems['price'];
        $this->basket['AmountNetNumeric'] -= $removeItems['netprice'];
        $this->basket['sAmount'] -= $removeItems['price'];
        $this->basket['Amount'] = str_replace(',', '.', $this->basket['Amount']) - $removeItems['price'];

        $this->basket['sAmountTax'] -= $removeItems['tax'];
        if (!empty($this->basket['sAmountWithTax'])) {
            if ($this->hasTax()) {
                $this->basket['sAmountWithTax'] -= $removeItems['price'];
            } else {
                $this->basket['sAmountWithTax'] -= $removeItems['amountWithTax'];
                $this->basket['AmountWithTaxNumeric'] -= $removeItems['amountWithTax'];
                $this->basket['AmountWithTax'] = $this->basket['AmountWithTaxNumeric'];
                $this->basket['amountnet'] = $this->basket['amount'];
            }
        }

        // Remove items from basket
        $this->getDatabase()->query(
            'DELETE FROM s_order_basket WHERE sessionID = ? and id IN (?)',
            [
                $removeItems['sessionId'],
                implode(',', $removeItems['ids'])
            ]
        );

        // Filter out basket items - surcharge
        $this->basket['contentOrg'] = array_filter($this->basket['contentOrg'], function ($item) {
            switch ((int) $item['modus']) {
                case 4: // Surcharge
                    return false;
                default:
                    return true;
            }
        });
    }

    /**
     * @todo: This function is basically a copy of the same function in Controllers/Frontend/Checkout.
     * As that function cannot be called, I copied it for the time being - this should be refactored
     *
     * @param  $basket array returned from this->getBasket
     * @return array
     */
    public function getTaxRates($basket)
    {
        $result = [];

        // The original method also calculates the tax rates of the shipping costs - this
        // is done in a separate methode here

        if (empty($basket['content'])) {
            ksort($result, SORT_NUMERIC);

            return $result;
        }

        foreach ($basket['content'] as $item) {
            if (!empty($item['tax_rate'])) {
            } elseif (!empty($item['taxPercent'])) {
                $item['tax_rate'] = $item['taxPercent'];
            } elseif ($item['modus'] == 2) {
                // Ticket 4842 - dynamic tax-rates
                $resultVoucherTaxMode = Shopware()->Db()->fetchOne(
                    'SELECT taxconfig FROM s_emarketing_vouchers WHERE ordercode=?
                ', [$item['ordernumber']]);
                // Old behaviour
                if (empty($resultVoucherTaxMode) || $resultVoucherTaxMode == 'default') {
                    $tax = Shopware()->Config()->get('sVOUCHERTAX');
                } elseif ($resultVoucherTaxMode == 'auto') {
                    // Automatically determinate tax
                    $tax = Shopware()->Modules()->Basket()->getMaxTax();
                } elseif ($resultVoucherTaxMode == 'none') {
                    // No tax
                    $tax = '0';
                } elseif (intval($resultVoucherTaxMode)) {
                    // Fix defined tax
                    $tax = Shopware()->Db()->fetchOne('
					SELECT tax FROM s_core_tax WHERE id = ?
					', [$resultVoucherTaxMode]);
                }
                $item['tax_rate'] = $tax;
            } else {
                // Ticket 4842 - dynamic tax-rates
                $taxAutoMode = Shopware()->Config()->get('sTAXAUTOMODE');
                if (!empty($taxAutoMode)) {
                    $tax = Shopware()->Modules()->Basket()->getMaxTax();
                } else {
                    $tax = Shopware()->Config()->get('sDISCOUNTTAX');
                }
                $item['tax_rate'] = $tax;
            }

            // Ignore 0 % tax
            if (empty($item['tax_rate']) || empty($item['tax'])) {
                continue;
            }
            $taxKey = number_format(floatval($item['tax_rate']), 2);
            $result[$taxKey] += str_replace(',', '.', $item['tax']);
        }

        ksort($result, SORT_NUMERIC);

        return $result;
    }

    /**
     * Returns an array of tax positions in the same way, as shopware does in the sTaxRates.
     * This will only take connect products returned by getConnectContent() into account,
     * so that connect positions moved into basket['content'] earlier are not calculated twice.
     *
     * @return array
     */
    public function getConnectTaxRates()
    {
        $taxes = [];

        foreach ($this->getConnectContent() as $shopId => $products) {
            foreach ($products as $product) {
                $vat = (string) number_format($product['tax_rate'], 2);
                if (!isset($taxes[$vat])) {
                    $taxes[$vat] = 0;
                }

                if ($this->hasTax()) {
                    $taxes[$vat] += $product['priceNumeric'] - $product['netprice'];
                } else {
                    $taxes[$vat] += $product['amountWithTax'] - ($product['netprice'] * $product['quantity']);
                }
            }
        }

        return $taxes;
    }

    /**
     * Will merge/add various arrays of "sTaxRates" like arrays into one array
     *
     * @param array $taxRates
     * @return array
     */
    public function getMergedTaxRates(array $taxRates)
    {
        $result = [];

        foreach ($taxRates as $taxRate) {
            foreach ($taxRate as $vat => $amount) {
                if (!isset($result[$vat])) {
                    $result[$vat] = 0;
                }
                $result[$vat] += $amount;
            }
        }

        return $result;
    }

    /**
     * Increase the basket's shipping costs and amount by the total value of connect shipping costs
     *
     * @param \Shopware\Connect\Struct\CheckResult $checkResult
     */
    public function recalculate(CheckResult $checkResult)
    {
        $this->checkResult = $checkResult;
        $this->basket['sAmount'] = number_format($this->basket['sAmount'], 2, '.', '');

        $shippingCostsNet = 0;
        $shippingCostsWithTax = 0;

        /** @var \Shopware\Connect\Struct\Shipping $shipping */
        foreach ($this->checkResult->shippingCosts as $shipping) {
            $shopConfiguration = $this->connectGateway->getShopConfiguration($shipping->shopId);
            if ($shopConfiguration->merchantShippingCostType == 'remote') {
                $shippingCostsNet += $shipping->shippingCosts;
                $shippingCostsWithTax += $shipping->grossShippingCosts;
            }
        }
        $shippingCostsNet = number_format($shippingCostsNet, 2, '.', '');
        $shippingCostsWithTax = number_format($shippingCostsWithTax, 2, '.', '');

        $basketHasTax = $this->hasTax();

        // Set the shipping cost tax rate for shopware

        $this->setOriginalShippingCosts($this->basket['sShippingcosts']);

        // Update shipping costs
        if ($basketHasTax) {
            $this->basket['sShippingcosts'] += $shippingCostsWithTax;
        } else {
            $this->basket['sShippingcosts'] += $shippingCostsNet;
        }
        $this->basket['sShippingcostsNet'] += $shippingCostsNet;
        $this->basket['sShippingcostsWithTax'] += $shippingCostsWithTax;

        $this->basket['AmountNetNumeric'] += $shippingCostsNet;

        if (!empty($this->basket['sAmountWithTax'])) {
            if ($basketHasTax) {
                $this->basket['sAmountWithTax'] += $this->basket['sShippingcostsWithTax'];
            } else {
                $this->basket['sAmountWithTax'] += $shippingCostsWithTax;
            }
        }

        if ($basketHasTax) {
            $this->basket['sAmount'] += $shippingCostsWithTax;
        } else {
            $this->basket['sAmount'] += $shippingCostsNet;
        }

        $this->basket['sAmountTax'] += $this->basket['sShippingcostsWithTax'] - $shippingCostsNet;

        // Core workaround: Shopware tries to re-calculate the shipping tax rate from the net price
        // \Shopware_Models_Document_Order::processOrder
        // Therefore we need to round the net price
        $this->basket['sShippingcostsNet'] = round($this->basket['sShippingcostsNet'], 2);


        // Recalculate the tax rates
        $this->basket['sTaxRates'] = $this->getMergedTaxRates(
            [
                $this->getTaxRates($this->basket),
                $this->getConnectTaxRates(),
                $this->getShippingCostsTaxRates()
            ]
        );

        //@todo:stefan Check for better solution
        $this->basket['AmountWithTaxNumeric'] = $this->basket['sAmountWithTax'];
        $this->basket['AmountNumeric'] = $this->basket['sAmount'];
    }

    /**
     * Returns the tax rate of the shipping costs and also sets the the net shipping cost amount(!)
     */
    public function getShippingCostsTaxRates()
    {
        $taxAmount = $this->basket['sShippingcostsWithTax'] - $this->basket['sShippingcostsNet'];

        $taxRate = number_format($this->getMaxTaxRate(), 2, '.', '');
        $this->basket['sShippingcostsNet'] = $this->basket['sShippingcostsWithTax'] / (($taxRate/100)+1);

        return [
            (string) $taxRate => $taxAmount
        ];
    }

    /**
     * Get the highest tax rate from basket - currently only this is supported by SW
     *
     * @return int
     */
    public function getMaxTaxRate()
    {
        $taxRate = 0;
        foreach ($this->getConnectContent() as $shopId => $products) {
            foreach ($products as $product) {
                if ($product['tax_rate'] > $taxRate) {
                    $taxRate = $product['tax_rate'];
                }
            }
        }

        foreach ($this->basket['content'] as $product) {
            if ($product['tax_rate'] > $taxRate) {
                $taxRate = $product['tax_rate'];
            }
        }

        return $taxRate;
    }

    /**
     * Return array of variables which need to be available in the default template
     *
     * @return array
     */
    public function getDefaultTemplateVariables()
    {
        return [
            'sBasket' => $this->basket,
            'sShippingcosts' => $this->basket['sShippingcosts'],
            'sAmount' => $this->basket['sAmount'],
            'sAmountWithTax' => $this->basket['sAmountWithTax'],
            'sAmountNet' => $this->basket['AmountNetNumeric']
        ];
    }

    /**
     * Return array of connect specific template variables
     *
     * @param $connectMessages array Messages to show
     * @return array
     */
    public function getConnectTemplateVariables(array $connectMessages)
    {
        $snippets = Shopware()->Snippets()->getNamespace('frontend/checkout/error_messages');
        /** @var Message $message */
        foreach ($connectMessages as $message) {
            if ($message->message == 'Availability of product %product changed to %availability.') {
                if ($message->values['availability'] == 0) {
                    $message->message = $snippets->get(
                        'connect_product_out_of_stock_message',
                        'Produkte in Ihrer Bestellung sind aktuell nicht lieferbar, bitte entfernen Sie die Produkte um fortzufahren.'
                    );
                } else {
                    $message->message = $snippets->get(
                        'connect_product_lower_stock_message',
                        'Der Lagerbestand von Produkt "%product" hat sich auf %availability geändert'
                    );
                }
            }
        }

        return [
            'connectContent' => $this->getConnectContent(),
            'connectShops' => $this->getConnectShops(),
            'connectMessages' => $connectMessages,
            'connectShippingCosts' => $this->getConnectGrossShippingCosts(),
            'connectShippingCostsOrg' => $this->getOriginalShippingCosts(),
            'connectShopInfo' => $this->showCheckoutShopInfo,
            'addBaseShop' => $this->onlyConnectProducts ? 0 : 1,
        ];
    }

    /**
     * Modifies a given OrderVariables ArrayObject
     *
     * @param $variables \ArrayObject
     * @return \ArrayObject
     */
    public function getOrderVariablesForSession($variables)
    {
        // Get a copy of the basket array in order to not mess up the state of the basket array
        $basket = $this->basket;
        $newVariables = $this->getDefaultTemplateVariables();

        // We need the manipulated content as the order is created from the session
         $basket['content'] = $basket['contentOrg'];
        unset($basket['contentOrg']);


        // Replace the original session array with the new one
        $variables->exchangeArray(array_merge(
            $variables->getArrayCopy(), $newVariables, ['sBasket' => $basket]
        ));

        return $variables;
    }

    /**
     * Find all percentaged vouchers for a given individual code
     *
     * @param $voucherCode
     * @return mixed
     */
    public function findPercentagedIndividualVouchers($voucherCode)
    {
        $builder = Shopware()->Models()->createQueryBuilder();

        $builder->select('voucher')
            ->from('Shopware\Models\Voucher\Voucher', 'voucher')
            ->innerJoin('voucher.codes', 'codes', 'WITH', 'codes.code LIKE :voucherCode')
            ->where('voucher.percental = true')
            ->setParameter('voucherCode', $voucherCode);

        return $builder->getQuery()->getResult();
    }

    /**
     * Find all vouchers matching the code
     *
     * @param $voucherCode
     * @return mixed
     */
    public function findPercentagedVouchers($voucherCode)
    {
        $builder = Shopware()->Models()->createQueryBuilder();

        $builder->select('voucher')
            ->from('Shopware\Models\Voucher\Voucher', 'voucher')
            ->where('voucher.voucherCode LIKE :voucherCode')
            ->andWhere('voucher.percental = true')
            ->setParameter('voucherCode', $voucherCode);

        return $builder->getQuery()->getResult();
    }

    /**
     * @return \Shopware\Connect\SDk
     */
    public function getSdk()
    {
        return $this->sdk;
    }

    /**
     * @return Helper
     */
    public function getHelper()
    {
        return $this->helper;
    }

    /**
     * @param mixed $basket
     */
    public function setBasket($basket)
    {
        $this->basket = $basket;
        $this->prepareBasketForConnect();
    }

    /**
     * @return mixed
     */
    public function getBasket()
    {
        return $this->basket;
    }

    /**
     * @param array $connectContent
     */
    public function setConnectContent($connectContent)
    {
        $this->connectContent = $connectContent;
    }

    /**
     * @return array
     */
    public function getConnectContent()
    {
        return $this->connectContent;
    }

    /**
     * @param array $connectProducts
     */
    public function setConnectProducts($connectProducts)
    {
        $this->connectProducts = $connectProducts;
    }

    /**
     * @return array
     */
    public function getConnectProducts()
    {
        return $this->connectProducts;
    }

    /**
     * @param array $connectShops
     */
    public function setConnectShops($connectShops)
    {
        $this->connectShops = $connectShops;
    }

    /**
     * @return array
     */
    public function getConnectShops()
    {
        return $this->connectShops;
    }

    /**
     * @return array
     */
    public function getConnectGrossShippingCosts()
    {
        $result = [];
        if (!$this->checkResult instanceof CheckResult) {
            return $result;
        }

        foreach ($this->checkResult->shippingCosts as $shipping) {
            if ($this->hasTax()) {
                $result[$shipping->shopId] = $shipping->grossShippingCosts;
            } else {
                $result[$shipping->shopId] = $shipping->shippingCosts;
            }
        }

        return $result;
    }

    /**
     * @param mixed $originalShippingCosts
     */
    public function setOriginalShippingCosts($originalShippingCosts)
    {
        $this->originalShippingCosts = $originalShippingCosts;
    }

    /**
     * @return mixed
     */
    public function getOriginalShippingCosts()
    {
        return $this->originalShippingCosts;
    }

    /**
     * @param \Enlight_Components_Db_Adapter_Pdo_Mysql $database
     */
    public function setDatabase($database)
    {
        $this->database = $database;
    }

    /**
     * @return \Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Returns "Gross price displayed in frontend" value
     * @return bool
     */
    protected function hasTax()
    {
        $customerGroup = Shopware()->Session()->sUserGroup;
        if (!$customerGroup) {
            $customerGroup = 'EK';
        }

        $repository = Shopware()->Models()->getRepository('Shopware\Models\Customer\Group');
        $groupModel = $repository->findOneBy(['key' => $customerGroup]);

        return $groupModel->getTax();
    }

    /**
     * @return \Shopware\Connect\Struct\CheckResult
     */
    public function getCheckResult()
    {
        return $this->checkResult ?: new CheckResult();
    }

    /**
     * @param \Shopware\Connect\Struct\CheckResult $checkResult
     */
    public function setCheckResult(CheckResult $checkResult)
    {
        $this->checkResult = $checkResult;
    }
}
