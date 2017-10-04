<?php
namespace Omnipay\TwoCheckoutPlus\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\ResponseInterface;

/**
 * Response.
 */
class StopRecurringResponse extends AbstractResponse implements ResponseInterface
{

    /**
     *
     * @ERROR!!!
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return ! isset($this->data['errors']) && $this->data['response_code'] == 'OK';
    }

    /**
     *
     * @ERROR!!!
     *
     * @return bool
     */
    public function isRedirect()
    {
        return false;
    }

    /**
     *
     * @ERROR!!!
     *
     * @return int|null
     */
    public function getCode()
    {
        return isset($this->data['response_code']) ? $this->data['response_code'] : null;
    }

    public function getLineItems()
    {
        return $this->data['sale']['invoices'][0]['lineitems'];
    }

    /**
     *
     * @ERROR!!!
     *
     */
    public function getMessage()
    {
        return isset($this->data['response_message']) ? $this->data['response_message'] : json_encode($this->data['errors']);
    }
}
