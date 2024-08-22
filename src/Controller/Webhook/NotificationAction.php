<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Controller\Webhook;

use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ObjectManager;
use Pledg\SyliusPaymentPlugin\Notification\Collector\CollectorException;
use Pledg\SyliusPaymentPlugin\Notification\Collector\ProcessorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class NotificationAction
{
    /** @var ProcessorInterface */
    private $processor;

    /** @var ObjectManager */
    private $paymentManager;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ProcessorInterface $processor,
        ObjectManager $paymentManager,
        LoggerInterface $logger
    ) {
        $this->processor = $processor;
        $this->paymentManager = $paymentManager;
        $this->logger = $logger;
    }

    public function __invoke(Request $request): JsonResponse
    {
        /** @var array $content */
        $content = json_decode(
            (string) $request->getContent(false),
            true,
            512,
            \JSON_THROW_ON_ERROR
        );

        try {
            $this->processor->process($content);
        } catch (CollectorException | ORMException $exception) {
            $this->logger->error($exception->getMessage());

            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $this->paymentManager->flush();

        return new JsonResponse([], Response::HTTP_OK);
    }
}
