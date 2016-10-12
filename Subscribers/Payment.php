<?php


namespace ShopwarePlugins\Connect\Subscribers;

/**
 * Class Payment
 * @package ShopwarePlugins\Connect\Subscribers
 */
class Payment extends BaseSubscriber
{

    /**
     * @var \Shopware\Models\Payment\Repository
     */
    private $repository;

    /**
     * @var \ShopwarePlugins\Connect\Services\PaymentService
     */
    private $paymentService;

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatch_Backend_Payment' => 'extendBackendPayment',
            'Shopware_Modules_Admin_GetPaymentMeans_DataFilter' => 'onFilterPaymentMethods',
        );
    }

    /**
     * @return mixed
     */
    public function getPaymentService()
    {
        if ($this->paymentService == null) {
            $this->paymentService = $this->Application()->Container()->get('swagconnect.payment_service');
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

        switch($request->getActionName()) {
            case 'load':
                $this->registerMyTemplateDir();

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
                $isAllowed = (int) $request->getParam('connectIsAllowed', false);

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
        $hasConnectProduct = $this->getHelper()->hasBasketConnectProducts($sessionId);

        if ($hasConnectProduct === true) {
            foreach ($paymentMeans as $key => &$payment) {
                /** @var \Shopware\Models\Payment\Payment $model */
                $model = $this->getPaymentRepository()->find($payment['id']);
                if (!$model) {
                    unset($paymentMeans[$key]);
                    continue;
                }

                if (!$model->getAttribute()) {
                    unset($paymentMeans[$key]);
                    continue;
                }

                $attribute = $model->getAttribute();
                if (method_exists($attribute, 'getConnectIsAllowed') === true
                    && $attribute->getConnectIsAllowed() == 0) {
                    unset($paymentMeans[$key]);
                    continue;
                }
            }

            $args->setReturn($paymentMeans);
        }
    }

    /**
     * @return \Shopware\Models\Payment\Repository
     */
    public function getPaymentRepository()
    {
        if (!$this->repository) {
            $this->repository = Shopware()->Models()->getRepository('Shopware\Models\Payment\Payment');
        }
        return $this->repository;
    }
} 