<?php

namespace ShopwarePlugins\Connect\Components;


class ErrorHandler
{
    const TYPE_PRICE_ERROR = 'price';
    const TYPE_DEFAULT_ERROR = 'default';

    /**
     * @var array
     */
    private $messages = array();

    /**
     * @param \Exception $e
     * @param null $prefix
     */
    public function handle(\Exception $e, $prefix = null)
    {
        if ($e instanceof \Shopware\Connect\Exception\VerificationFailedException && $this->isPriceError($e)) {
            $this->messages[self::TYPE_PRICE_ERROR][] = " &bull; " . $prefix . $e->getMessage();
            return;
        }

        $this->messages[self::TYPE_DEFAULT_ERROR][] = " &bull; " . $prefix . $e->getMessage();
    }

    /**
     * @param \Exception $e
     * @return bool
     */
    public function isPriceError(\Exception $e)
    {
        switch($e->getMessage()){
            case 'The purchasePrice is not allowed to be 0 or smaller.':
            case 'The price is not allowed to be 0 or smaller.':
            case 'Fixed price is not allowed when export purchasePrice only':
                return true;
        }

        return false;
    }

    /**
     * @param $type
     * @return mixed
     */
    public function getMessage($type)
    {
        return $this->messages[$type];
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }
}