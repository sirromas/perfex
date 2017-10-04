<?php
namespace Omnipay\Mollie\Message;

class CompletePurchaseResponse extends FetchTransactionResponse
{

    /**
     *
     * @ERROR!!!
     *
     */
    public function isSuccessful()
    {
        return parent::isSuccessful() && $this->isPaid();
    }
}
