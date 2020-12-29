<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\JWT;

use PHPUnit\Framework\TestCase;
use Pledg\SyliusPaymentPlugin\JWT\HS256Handler;

class HS256HandlerTest extends TestCase
{
    /** @test */
    public function it_encode_correctly_redictUrl_parameters(): void
    {
        $handler = new HS256Handler();
        $parameters = [
            'foo' => 'bar',
        ];

        $token = $handler->encode($parameters, 'secret');

        self::assertSame($parameters, $handler->decode($token));
    }

    /** @test */
    public function it_verify_valid_token(): void
    {
        $handler = new HS256Handler();
        $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWUsImp0aSI6ImUwMTg4MjFjLTVkNjUtNDZjNS1hMDAzLWJkNjY1ZDVjZjg2ZiIsImlhdCI6MTYwOTIzMzYxNCwiZXhwIjoxNjA5MjM3MjE0fQ.gGle2IrMhwha0DLZLjktXhEW76AmF06jzyLwtE7ZaU0';
        $secret = 'secret';

        self::assertTrue($handler->verify($token, $secret));
    }

    /** @test */
    public function it_verify_invalid_token(): void
    {
        $handler = new HS256Handler();
        $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWUsImp0aSI6ImUwMTg4MjFjLTVkNjUtNDZjNS1hMDAzLWJkNjY1ZDVjZjg2ZiIsImlhdCI6MTYwOTIzMzYxNCwiZXhwIjoxNjA5MjM3MjE0fQ.gGle2IrMhwha0DLZLjktXhEW76AmF06jzyLwtE7ZaU';
        $secret = 'secret';

        self::assertFalse($handler->verify($token, $secret));
    }

    /** @test */
    public function it_verify_invalid_secret(): void
    {
        $handler = new HS256Handler();
        $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWUsImp0aSI6ImUwMTg4MjFjLTVkNjUtNDZjNS1hMDAzLWJkNjY1ZDVjZjg2ZiIsImlhdCI6MTYwOTIzMzYxNCwiZXhwIjoxNjA5MjM3MjE0fQ.gGle2IrMhwha0DLZLjktXhEW76AmF06jzyLwtE7ZaU0';
        $secret = 'secre';

        self::assertFalse($handler->verify($token, $secret));
    }
}
