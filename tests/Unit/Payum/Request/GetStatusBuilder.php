<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Payum\Request;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Request\GetStatusInterface;
use Sylius\Bundle\PayumBundle\Request\GetStatus;

class GetStatusBuilder extends RequestBuilder
{
    public function __construct()
    {
        $this->withModel(new ArrayObject());
        parent::__construct();
    }

    public function build(): GetStatusInterface
    {
        $request = new GetStatus($this->token);
        $request->setModel($this->model);

        return $request;
    }
}
