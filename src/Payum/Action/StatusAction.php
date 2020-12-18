<?php


namespace Pledg\SyliusPaymentPlugin\Payum\Action;


use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetStatusInterface;
use Pledg\SyliusPaymentPlugin\ValueObject\Status;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class StatusAction implements ActionInterface
{
    /** @var RequestStack */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param GetStatusInterface|mixed $request
     * @throws \JsonException
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $pledgResult = $this->requestStack->getCurrentRequest() instanceof Request
            ? $this->requestStack->getCurrentRequest()->query->get('pledg_result') : null;

        if (null !== $pledgResult) {
            $status = json_decode(
                $pledgResult,
                true,
                512,
                JSON_THROW_ON_ERROR
            )['transaction']['status'] ?? null;

            if (null !== $status) {
                (new Status($status))->markRequest($request);
            }
        }
    }

    public function supports($request)
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess
            ;
    }

}
