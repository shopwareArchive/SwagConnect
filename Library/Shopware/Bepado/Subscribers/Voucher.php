<?php

namespace Shopware\Bepado\Subscribers;

class Voucher extends BaseSubscriber
{

    public function getSubscribedEvents()
    {
        return array(
            'Shopware_Modules_Basket_AddVoucher_Start' => 'preventPercentagedVoucher'
        );
    }

    /**
     * Will not allow percentaged vouchers if bepado products are in the basket
     *
     * @event Shopware_Modules_Basket_AddVoucher_Start
     *
     * @param \Enlight_Event_EventArgs $args
     * @return bool|null
     */
    public function preventPercentagedVoucher(\Enlight_Event_EventArgs $args)
    {
        $code = $args->getCode();
        /** @var \sBasket $basketInstance */
        $basketInstance = $args->getSubject();

        if (!$this->isBepadoBasket($basketInstance)) {
            return null;
        }

        $message = Shopware()->Snippets()->getNamespace('frontend/bepado/checkout')->get(
            'noPercentagedVoucherAllowed',
            'In Kombination mit bepado-Produkten sind keine prozentualen Gutscheine möglich.',
            true
        );

        // Exclude general percentaged vouchers
        $result = $this->findPercentagedVouchers($code);
        if (!empty($result)) {
            Shopware()->Template()->assign('sVoucherError', $message);
            return true;
        }

        // Exclude individual percentaged vouchers
        $result = $this->findPercentagedIndividualVouchers($code);
        if (!empty($result)) {
            Shopware()->Template()->assign('sVoucherError', $message);
            return true;
        }
    }

    /**
     * Check for bepado products in the basket
     *
     * @param $basketInstance \sBasket
     * @return bool
     */
    public function isBepadoBasket($basketInstance)
    {
        $basket = $basketInstance->sGetBasket();
        $basketHelper = $this->getBasketHelper();

        $basketHelper->setBasket($basket);
        $basketContent = $basketHelper->getBepadoContent();

        return !empty($basketContent);
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




}