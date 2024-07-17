<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Controller\Webhook;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class NotificationActionTest extends TestCase
{
    /** @test */
    public function it_process_unknown_payment_reference(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getContent(false)->willReturn('{"created_at": "2019-04-04T12:20:34.97138Z","id": "test-valid","additional_data": {"xx": "yy"},"metadata": {"foo":"bar"},"status": "completed","sandbox": "true","error": "","reference": "PLEDGBYSOFINCO_1086986_786391","signature": "B1C777835C01CA96AC4C3097FD46A7CA49B92BE157EDE0CB3552880D12A15359"}');

        $response = (new NotificationActionBuilder())->build()->__invoke($request->reveal());

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }
}
