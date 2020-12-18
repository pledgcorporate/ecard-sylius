<?php


namespace Pledg\SyliusPaymentPlugin\Payum\Action;


use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Reply\HttpRedirect;
use Pledg\SyliusPaymentPlugin\Payum\Request\RedirectUrl;

class RedirectUrlAction implements ActionInterface
{
    /**
     * @param RedirectUrl|mixed $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        throw new HttpRedirect('https://staging.front.ecard.pledg.co/purchase?' . http_build_query($request->buildParameters()));
    }

    public function supports($request)
    {
        return $request instanceof RedirectUrl;
    }

}
