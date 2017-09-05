<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Subscribers;

use Enlight\Event\SubscriberInterface;
use ShopwarePlugins\Connect\Components\Helper;
use Shopware\Models\Payment\Repository as PaymentRepository;
use ShopwarePlugins\Connect\Services\PaymentService;

class PaymentSubscriber implements SubscriberInterface
{
    /**
     * @var \ShopwarePlugins\Connect\Services\PaymentService
     */
    private $paymentService;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var PaymentRepository
     */
    private $paymentRepository;

    /**
     * @param Helper $helper
     * @param PaymentRepository $paymentRepository
     */
    public function __construct(Helper $helper, PaymentRepository $paymentRepository)
    {
        $this->helper = $helper;
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Backend_Payment' => 'extendBackendPayment',
            'Shopware_Modules_Admin_GetPaymentMeans_DataFilter' => 'onFilterPaymentMethods',
        ];
    }

    /**
     * @return PaymentService
     */
    public function getPaymentService()
    {
        if ($this->paymentService == null) {
            $this->paymentService = Shopware()->Container()->get('swagconnect.payment_service');
        }

        return $this->paymentService;
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function extendBackendPayment(\Enlight_Event_EventArgs $args)
    {
        /** @var $subject \Enlight_Controller_Action */
        $subject = $args->getSubject();
        $request = $subject->Request();

        switch ($request->getActionName()) {
            case 'load':
                $subject->View()->extendsTemplate(
                    'backend/payment/model/connect_attribute.js'
                );

                $subject->View()->extendsTemplate(
                    'backend/payment/view/payment/connect_form.js'
                );
                break;
            case 'getPayments':
                $subject->View()->data = $this->getPaymentService()->allowConnect(
                    $subject->View()->data
                );
                break;
            case 'updatePayments':
                $paymentId = (int) $request->getParam('id', null);
                $isAllowed = (bool) $request->getParam('connectIsAllowed', false);

                $this->getPaymentService()->updateConnectAllowed($paymentId, $isAllowed);
                break;
            default:
                break;
        }
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function onFilterPaymentMethods(\Enlight_Event_EventArgs $args)
    {
        $paymentMeans = $args->getReturn();

        $sessionId = Shopware()->SessionID();
        $hasConnectProduct = $this->helper->hasBasketConnectProducts($sessionId);

        if ($hasConnectProduct === true) {
            foreach ($paymentMeans as $key => &$payment) {
                /** @var \Shopware\Models\Payment\Payment $payment */
                $payment = $this->paymentRepository->find($payment['id']);
                if (!$payment) {
                    unset($paymentMeans[$key]);
                    continue;
                }

                if (!$payment->getAttribute()) {
                    unset($paymentMeans[$key]);
                    continue;
                }

                $attribute = $payment->getAttribute();
                if (method_exists($attribute, 'getConnectIsAllowed') === true
                    && $attribute->getConnectIsAllowed() == 0) {
                    unset($paymentMeans[$key]);
                    continue;
                }
            }

            $args->setReturn($paymentMeans);
        }
    }
}
