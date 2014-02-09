<?php

namespace Shopware\Bepado;

use Bepado\SDK;
use Shopware\Bepado;

/**
 * Handles the basket manipulation. Most of it is done by modifying the template variables shown to the user.
 * Once we have new basket and order core classes, this should be refactored.
 *
 * Class BasketHelper
 * @package Shopware\Bepado
 */
class BasketHelper
{

    /**
     * The basket array decorated by this class
     * @var array
     */
    protected $basket;

    /**
     * Array of bepado product structs
     * @var array
     */
    protected $bepadoProducts = array();

    /**
     * bepado content as formated by shopware
     *
     * @var array
     */
    protected $bepadoContent = array();

    /**
     * Array of bepado shops affected by this basket
     *
     * @var array
     */
    protected $bepadoShops = array();

    /**
     * Shipping costs of bepado products
     *
     * @var array
     */
    protected $bepadoGrossShippingCosts = array();
    protected $bepadoNetShippingCosts = array();

    /**
     * The original shopware shipping costs
     *
     * @var float
     */
    protected $originalShippingCosts;

    /**
     * Should there be a bepado hint in the template
     *
     * @var boolean
     */
    protected $showCheckoutShopInfo;

    /**
     * @var \Bepado\SDK\SDK
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
     * Indicates if the basket has only bepado products or not
     *
     * @var bool
     */
    protected $onlyBepadoProducts = false;

    /**
     * @param \Enlight_Components_Db_Adapter_Pdo_Mysql $database
     * @param SDK\SDK $sdk
     * @param Helper $helper
     * @param $showCheckoutShopInfo
     */
    public function __construct(\Enlight_Components_Db_Adapter_Pdo_Mysql $database, SDK\SDK $sdk, Bepado\Helper $helper, $showCheckoutShopInfo)
    {
        $this->database = $database;
        $this->sdk = $sdk;
        $this->helper = $helper;
        $this->showCheckoutShopInfo = $showCheckoutShopInfo;
    }

    /**
     * Prepare the basket for bepado
     *
     * @return array
     */
    public function prepareBasketForBepado()
    {
        $this->buildProductsArray();
        $this->buildShopsArray();
        $this->buildShippingCostsArray();
    }

    /**
     * Build array of bepado products. This will remove bepado products from the 'content' array
     */
    protected function buildProductsArray()
    {
        $this->bepadoProducts = array();
        $this->bepadoContent = array();

        $this->basket['contentOrg'] = $this->basket['content'];
        foreach ($this->basket['content'] as $key => &$row) {
            if (!empty($row['mode'])) {
                continue;
            }
            $product = $this->getHelper()->getProductById($row['articleID']);
            if ($product === null || $product->shopId === null) {
                continue;
            }
            $row['bepadoShopId'] = $product->shopId;
            $this->bepadoProducts[$product->shopId][$product->sourceId] = $product;
            $this->bepadoContent[$product->shopId][$product->sourceId] = $row;

            //if($actionName == 'cart') {
            unset($this->basket['content'][$key]);
            //}
        }
    }

    /**
     * Build array of bepado remote shops
     */
    protected function buildShopsArray()
    {
        $this->bepadoShops = array();

        $this->basket['content'] = array_values($this->basket['content']);
        foreach($this->bepadoContent as $shopId => $items) {
            $this->bepadoShops[$shopId] = $this->getSdk()->getShop($shopId);
        }
    }

    /**
     * Build array of shipping costs
     */
    protected function buildShippingCostsArray()
    {
        $this->bepadoGrossShippingCosts = array();
        $this->bepadoNetShippingCosts = array();
        $this->originalShippingCosts = 0;

        // Calculate bepado shipping costs
        foreach($this->bepadoProducts as $shopId => $products) {
            /** @var \Bepado\SDK\Struct\ShippingCosts $shippingCosts */
            $shippingCosts = $this->getSdk()->calculateShippingCosts($products);
            $this->bepadoGrossShippingCosts[$shopId] = $shippingCosts->grossShippingCosts;
            $this->bepadoNetShippingCosts[$shopId] = $shippingCosts->shippingCosts;
        }
    }

    /**
     * This method will check, if any *real* products from the local shop are in the basket. If this is not the
     * case, this method will:
     *
     * - remove the original shipping costs from the basket
     * - set the first bepado shop as content of the default basket ($basket['content'])
     * - remove any surcharges, vouchers and  discount from the original basket(!)
     *
     * @return bool|mixed
     */
    public function fixBasket()
    {
        // Filter out basket items which cannot be purchased on their own
        $content = array_filter($this->basket['content'], function($item) {
                switch ((int)$item['modus']) {
                    case 0: // Default products
                    case 1: // Premium products
                        return true;
                    default:
                        return false;
                }
        });

        // If only bepado products are in the basket, do the basket fix
        if(empty($content)) {
            $this->onlyBepadoProducts = true;

            $this->removeNonProductsFromBasket();

            $bepadoContent = $this->getBepadoContent();

            // Make the first bepado shop the default basket-content
            reset($bepadoContent);
            $shopId = current(array_keys($bepadoContent));
            $this->basket['content'] = $bepadoContent[$shopId];
            unset($this->bepadoContent[$shopId]);

            // Remove original shop's shipping costs
            $shippingCostsOrg = $this->basket['sShippingcosts'];
            $shippingCostsOrgNet = $this->basket['sShippingcostsNet'];
            $this->basket['sShippingcosts'] = 0;
            $this->basket['sShippingcostsWithTax'] = 0;
            $this->basket['sShippingcostsNet'] = 0;
            $this->basket['AmountNumeric'] -= $shippingCostsOrg;
            $this->basket['AmountNetNumeric'] -= $shippingCostsOrgNet;
            $this->basket['sAmount'] -= $shippingCostsOrg;
            $rate = number_format($this->basket['sShippingcostsTax'], 2, '.', '');
            $this->basket['sTaxRates'][$rate] -= $shippingCostsOrg - $shippingCostsOrgNet;
            if(!empty($this->basket['sAmountWithTax'])) {
                $this->basket['sAmountWithTax'] -= $shippingCostsOrg;
            }

            return $shopId;
        }
        
        return false;
    }

    /**
     * Removes non-bepado products from the database and fixes the basket variables
     */
    protected function removeNonProductsFromBasket()
    {
        $removeItems = array(
            'ids' => array(),
            'price' => 0,
            'netprice' => 0,
            'sessionId' => null
        );

        // Build array of ids and amount to fix the basket later
        foreach ($this->basket['content'] as  $product) {
            $removeItems['ids'][] = $product['id'];
            $removeItems['price'] += $product['price'] * $product['quantity'];
            $removeItems['netprice'] += $product['netprice'] * $product['quantity'];
            $removeItems['sessionId'] = $product['sessionID'];
        }

        if (empty($removeItems['ids'])) {
            return;
        }

        // Fix basket prices
        $this->basket['AmountNumeric'] -= $removeItems['price'];
        $this->basket['AmountNetNumeric'] -= $removeItems['netprice'];
        $this->basket['sAmount'] -= $removeItems['price'];
        $this->basket['Amount'] = str_replace(',', '.', $this->basket['Amount']) - $removeItems['price'];
        if(!empty($this->basket['sAmountWithTax'])) {
            $this->basket['sAmountWithTax'] -= $removeItems['price'];
        }

        // Remove items from basket
        $this->getDatabase()->query(
            'DELETE FROM s_order_basket WHERE sessionID = ? and id IN (?)',
            array(
                $removeItems['sessionId'],
                implode(',', $removeItems['ids'])
            )
        );
        
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
        $result = array();

        
        if (!empty($basket['sShippingcostsTax']))
        {
            $basket['sShippingcostsTax'] = number_format(floatval($basket['sShippingcostsTax']),2);

            $result[$basket['sShippingcostsTax']] = $basket['sShippingcostsWithTax']-$basket['sShippingcostsNet'];
            if (empty($result[$basket['sShippingcostsTax']])) unset($result[$basket['sShippingcostsTax']]);
        }
        elseif ($basket['sShippingcostsWithTax'])
        {
            $result[number_format(floatval(Shopware()->Config()->get('sTAXSHIPPING')),2)] = $basket['sShippingcostsWithTax']-$basket['sShippingcostsNet'];
            
            if (empty($result[number_format(floatval(Shopware()->Config()->get('sTAXSHIPPING')),2)])) unset($result[number_format(floatval(Shopware()->Config()->get('sTAXSHIPPING')),2)]);
        }

        if(empty($basket['content'])){
            ksort($result, SORT_NUMERIC);
            return $result;
        }

        foreach ($basket['content'] as $item) {

            if (!empty($item["tax_rate"])) {

            } elseif (!empty($item['taxPercent'])) {
                $item['tax_rate'] = $item["taxPercent"];
            } elseif ($item['modus'] == 2) {
                // Ticket 4842 - dynamic tax-rates
                $resultVoucherTaxMode = Shopware()->Db()->fetchOne(
                    "SELECT taxconfig FROM s_emarketing_vouchers WHERE ordercode=?
                ", array($item["ordernumber"]));
                // Old behaviour
                if (empty($resultVoucherTaxMode) || $resultVoucherTaxMode == "default") {
                    $tax = Shopware()->Config()->get('sVOUCHERTAX');
                } elseif ($resultVoucherTaxMode == "auto") {
                    // Automatically determinate tax
                    $tax = $this->basket->getMaxTax();
                } elseif ($resultVoucherTaxMode == "none") {
                    // No tax
                    $tax = "0";
                } elseif (intval($resultVoucherTaxMode)) {
                    // Fix defined tax
                    $tax = Shopware()->Db()->fetchOne("
					SELECT tax FROM s_core_tax WHERE id = ?
					", array($resultVoucherTaxMode));
                }
                $item['tax_rate'] = $tax;
            } else {
                // Ticket 4842 - dynamic tax-rates
                $taxAutoMode = Shopware()->Config()->get('sTAXAUTOMODE');
                if (!empty($taxAutoMode)) {
                    $tax = $this->basket->getMaxTax();
                } else {
                    $tax = Shopware()->Config()->get('sDISCOUNTTAX');
                }
                $item['tax_rate'] = $tax;
            }

            if (empty($item['tax_rate']) || empty($item["tax"])) continue; // Ignore 0 % tax

            $taxKey = number_format(floatval($item['tax_rate']), 2);
            $result[$taxKey] += str_replace(',', '.', $item['tax']);
        }

        ksort($result, SORT_NUMERIC);

        return $result;
    }

    /**
     * Returns an array of tax positions in the same way, as shopware does in the sTaxRates.
     * This will only take bepado products returned by getBepadoContent() into account,
     * so that bepado positions moved into basket['content'] earlier are not calculated twice.
     *
     * @return array
     */
    public function getBepadoTaxRates()
    {
        $taxes = array();

        foreach ($this->getBepadoContent() as $shopId => $products) {
            foreach ($products as $product) {
                $vat = (string) number_format($product['tax_rate'], 2);
                if (!isset($taxes[$vat])) {
                    $taxes[$vat] = 0;
                }
                $taxes[$vat] += $product['priceNumeric'] - $product['netprice'];
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
        $result = array();

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
     * Increase the basket's shipping costs and amount by the total value of bepado shipping costs
     */
    public function recalculate()
    {
        $shippingCostsNet = array_sum($this->getBepadoNetShippingCosts());
        $shippingCosts = array_sum($this->getBepadoGrossShippingCosts());

        $this->setOriginalShippingCosts($this->basket['sShippingcosts']);

        // Update shipping costs
        $this->basket['sShippingcosts'] += $shippingCosts;
        $this->basket['sShippingcostsNet'] += $shippingCostsNet;
        $this->basket['sShippingcostsWithTax'] += $shippingCosts;

        // Update total amount
        $this->basket['AmountNumeric'] += $shippingCosts;
        $this->basket['AmountNetNumeric'] += $shippingCostsNet;
        if(!empty($this->basket['sAmountWithTax'])) {
            $this->basket['sAmountWithTax'] += $shippingCosts;
        }
        $this->basket['sAmount'] += $shippingCosts;

        // Core workaround: Shopware tries to re-calculate the shipping tax rate from the net price
        // \Shopware_Models_Document_Order::processOrder
        // Therefore we need to round the net price
        $this->basket['sShippingcostsNet'] = round($this->basket['sShippingcostsNet'], 2);

        // Recalculate the tax rates
        $this->basket['sTaxRates'] = $this->getMergedTaxRates(array($this->getTaxRates($this->basket), $this->getBepadoTaxRates()));
    }

    /**
     * Return array of variables which need to be available in the default template
     *
     * @return array
     */
    public function getDefaultTemplateVariables()
    {
        return array(
            'sBasket' => $this->basket,
            'sShippingcosts' => $this->basket['sShippingcosts'],
            'sAmount' => $this->basket['sAmount'],
            'sAmountWithTax' => $this->basket['sAmountWithTax'],
            'sAmountNet' => $this->basket['AmountNetNumeric']
        );
    }

    /**
     * Return array of bepado specific template variables
     *
     * @param $bepadoMessages array Messages to show
     * @return array
     */
    public function getBepadoTemplateVariables($bepadoMessages)
    {
        return array(
            'bepadoContent' => $this->getBepadoContent(),
            'bepadoShops' => $this->getBepadoShops(),
            'bepadoMessages' => $bepadoMessages,
            'bepadoShippingCosts' => $this->getBepadoGrossShippingCosts(),
            'bepadoShippingCostsOrg' => $this->getOriginalShippingCosts(),
            'bepadoShopInfo' => $this->showCheckoutShopInfo,
            'addBaseShop' => $this->onlyBepadoProducts ? 0 : 1
        );
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
        $basket = array_merge($this->basket);
        $newVariables = $this->getDefaultTemplateVariables();

        // We need the manipulated content as the order is created from the session
         $basket['content'] = $basket['contentOrg'];
        unset($basket['contentOrg']);


        // Replace the original session array with the new one
        $variables->exchangeArray(array_merge(
            $variables->getArrayCopy(), $newVariables, array('sBasket' => $basket)
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


        return $builder->getQuery()->getResult();    }

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
     * @return mixed
     */
    public function getSdk()
    {
        return $this->sdk;
    }

    /**
     * @return mixed
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
        $this->prepareBasketForBepado();

    }

    /**
     * @return mixed
     */
    public function getBasket()
    {
        return $this->basket;
    }

    /**
     * @param array $bepadoContent
     */
    public function setBepadoContent($bepadoContent)
    {
        $this->bepadoContent = $bepadoContent;
    }

    /**
     * @return array
     */
    public function getBepadoContent()
    {
        return $this->bepadoContent;
    }

    /**
     * @param array $bepadoProducts
     */
    public function setBepadoProducts($bepadoProducts)
    {
        $this->bepadoProducts = $bepadoProducts;
    }

    /**
     * @return array
     */
    public function getBepadoProducts()
    {
        return $this->bepadoProducts;
    }

    /**
     * @param array $bepadoShops
     */
    public function setBepadoShops($bepadoShops)
    {
        $this->bepadoShops = $bepadoShops;
    }

    /**
     * @return array
     */
    public function getBepadoShops()
    {
        return $this->bepadoShops;
    }

    /**
     * @param array $bepadoShippingCosts
     */
    public function setBepadoShippingCosts($bepadoShippingCosts)
    {
        $this->bepadoGrossShippingCosts = $bepadoShippingCosts;
    }

    /**
     * @return array
     */
    public function getBepadoGrossShippingCosts()
    {
        return $this->bepadoGrossShippingCosts;
    }

    /**
     * @return array
     */
    public function getBepadoNetShippingCosts()
    {
        return $this->bepadoNetShippingCosts;
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



}