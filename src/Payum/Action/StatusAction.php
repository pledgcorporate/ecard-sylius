<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Payum\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetStatusInterface;
use Pledg\SyliusPaymentPlugin\ValueObject\Status;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class StatusAction implements ActionInterface
{
    /** @var RequestStack */
    private $requestStack;

    /** @var LoggerInterface */
    private $logger;

    private const PLEDG_RESULT = 'pledg_result';

    private const PLEDG_ERROR = 'pledg_error';

    public function __construct(RequestStack $requestStack, LoggerInterface $logger)
    {
        $this->requestStack = $requestStack;
        $this->logger = $logger;
    }

    /**
     * @param GetStatusInterface|mixed $request
     *
     * @throws \JsonException
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $jsonResult = $this->getParameter(self::PLEDG_RESULT);
        $jsonError = $this->getParameter(self::PLEDG_ERROR);

        if (null !== $jsonResult) {
            $this->handlePledgResult($request, (string) $jsonResult);

            return;
        }

        if (null !== $jsonError) {
            $this->handlePledgError($request, (string) $jsonError);

            return;
        }

        $request->markNew();
    }

    private function handlePledgResult(GetStatusInterface $request, string $jsonResult): void
    {
        $result = $this->jsonDecode($jsonResult);

        $this->setPaymentDetails($request, self::PLEDG_RESULT, $result);

        $status = Status::WAITING;

        if (isset($result['transaction']['status'])) {
            $status = $result['transaction']['status'];
        } elseif (isset($result['purchase'])) {
            $status = Status::COMPLETED;
        }

        (new Status($status))->markRequest($request);
    }

    private function handlePledgError(GetStatusInterface $request, string $jsonError): void
    {
        $error = $this->jsonDecode($jsonError);

        $this->logger->error($jsonError);

        $this->setPaymentDetails($request, self::PLEDG_ERROR, $error);

        if (isset($error['type']) && $error['type'] === 'abandonment') {
            $request->markCanceled();
        } else {
            $request->markFailed();
        }
    }

    private function setPaymentDetails(GetStatusInterface $status, string $key, array $result): void
    {
        /** @var ArrayObject $arrayObject */
        $arrayObject = $status->getModel();
        $arrayObject['redirect_result'] = [
            $key => $result,
        ];
        $status->setModel($arrayObject);
    }

    /**
     * @return string|int|float|bool|null
     */
    private function getParameter(string $key)
    {
        return $this->requestStack->getCurrentRequest() instanceof Request
            ? $this->requestStack->getCurrentRequest()->query->get($key) : null;
    }

    private function jsonDecode(string $input): array
    {
        return json_decode(
            $input,
            true,
            512,
            \JSON_THROW_ON_ERROR
        );
    }

    public function supports($request)
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess
            ;
    }
}
