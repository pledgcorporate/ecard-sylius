<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Controller\Webhook;

use Doctrine\Persistence\ObjectManager;
use Pledg\SyliusPaymentPlugin\Notification\Collector\ProcessorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class NotificationAction
{
    public function __construct(
        private ProcessorInterface $processor,
        private ObjectManager $paymentManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $this->logger->info('Pledg IPN received', [
            'method' => $request->getMethod(),
            'content_type' => $request->headers->get('Content-Type'),
            'ip' => $request->getClientIp(),
        ]);

        if ($request->isMethod('GET')) {
            $content = $request->query->all();
            if (empty($content)) {
                return new JsonResponse(['status' => 'ok'], Response::HTTP_OK);
            }
        } else {
            $content = $this->parseRequestContent($request);
        }

        if (empty($content)) {
            $this->logger->warning('Pledg IPN: empty body, returning OK');

            return new JsonResponse(['status' => 'ok'], Response::HTTP_OK);
        }

        try {
            $this->processor->process($content);
            $this->paymentManager->flush();
        } catch (\Throwable $e) {
            $this->logger->error('Pledg IPN processing error: ' . $e->getMessage(), [
                'exception_class' => \get_class($e),
            ]);

            return new JsonResponse(
                ['error' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }

        return new JsonResponse(['status' => 'ok'], Response::HTTP_OK);
    }

    private function parseRequestContent(Request $request): array
    {
        $raw = (string) $request->getContent(false);

        if ('' !== $raw) {
            try {
                $decoded = json_decode($raw, true, 512, \JSON_THROW_ON_ERROR);
                if (\is_array($decoded)) {
                    return $decoded;
                }
            } catch (\JsonException $e) {
                $this->logger->warning('Pledg IPN: JSON decode failed', ['error' => $e->getMessage()]);
            }
        }

        if ($request->request->count() > 0) {
            return $request->request->all();
        }

        if ($request->query->count() > 0) {
            return $request->query->all();
        }

        return [];
    }
}
