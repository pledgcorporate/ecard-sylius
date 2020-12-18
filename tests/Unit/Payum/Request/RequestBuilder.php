<?php


namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Payum\Request;

use Payum\Core\Model\Identity;
use Sylius\Bundle\PayumBundle\Model\PaymentSecurityToken;
use Sylius\Component\Core\Model\Payment;

class RequestBuilder
{
    protected $token;

    protected $model;

    public function __construct()
    {
        $this->buildToken();
    }

    public function withModel($model): self
    {
        $this->model = $model;

        return $this;
    }

    public function buildToken()
    {
        $this->token = new PaymentSecurityToken();
        $this->token->setDetails(new Identity(1, Payment::class));
        $this->token->setAfterUrl('http://127.0.0.1/en_US/order/after-pay?payum_token=PhTV3g6s49-BwYZ1KLoi5Qn5JKdNhnj-p1C3YC_sCQQ');
        $this->token->setTargetUrl('http://127.0.0.1/payment/capture/WvQBW2IDpmDwKkhTbPt17LZD6hTybiqQ9eeNGh9U8ms');
        $this->token->setGatewayName('pledg');
    }
}
