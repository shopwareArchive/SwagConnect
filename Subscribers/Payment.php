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

    public function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatch_Backend_Payment' => 'extendBackendPayment',
            'Shopware_Modules_Admin_GetPaymentMeans_DataFilter' => 'onFilterPaymentMethods',
        );
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function extendBackendPayment(\Enlight_Event_EventArgs $args)
    {
        /** @var $subject \Enlight_Controller_Action */
        $subject = $args->getSubject();
        $request = $subject->Request();

        if ($request->getActionName() == 'load') {
            $this->registerMyTemplateDir();

            $subject->View()->extendsTemplate(
                'backend/payment/model/connect_attribute.js'
            );

            $subject->View()->extendsTemplate(
                'backend/payment/view/payment/connect_form.js'
            );
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