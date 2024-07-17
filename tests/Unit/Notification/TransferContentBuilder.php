<?php

declare(strict_types=1);

namespace Tests\Pledg\SyliusPaymentPlugin\Unit\Notification;

class TransferContentBuilder
{
    /** @var string */
    protected $jsonContent;

    public function withValidContent(): self
    {
        $this->jsonContent = '{"signature":"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJyZWZlcmVuY2UiOiJQTEVER0JZU09GSU5DT18xMjZfMTU4IiwiY3JlYXRlZCI6IjIwMjAtMTItMjggMTA6Mzc6MDYuMzM3NjQwIiwidHJhbnNmZXJfb3JkZXJfaXRlbV91aWQiOiJ0cmlfODk0NWM3NzAtYmZiNi00MDFlLTgzNDYtYzIzOTllNzYwM2Q5IiwiYW1vdW50X2NlbnRzIjoyODY3LCJtZXRhZGF0YSI6eyJwbGVkZ19zZXNzaW9uIjp7ImlwIjoiOTEuMTYxLjE4MS4zMiIsInVzZXJfYWdlbnQiOnsic3RyaW5nIjoiTW96aWxsYS81LjAgKFgxMTsgVWJ1bnR1OyBMaW51eCB4ODZfNjQ7IHJ2Ojg0LjApIEdlY2tvLzIwMTAwMTAxIEZpcmVmb3gvODQuMCIsInBsYXRmb3JtIjoibGludXgiLCJicm93c2VyIjoiZmlyZWZveCIsInZlcnNpb24iOiI4NC4wIiwibGFuZ3VhZ2UiOm51bGx9fX19.T2bkZS0zoimyE753Xn8Nn4htDmkeF2vdLFITdLcrXxc"}';

        return $this;
    }

    public function withInvalidSignature(): self
    {
        $this->jsonContent = '{"signature":"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJyZWZlcmVuY2UiOiJQTEVER0JZU09GSU5DT18xMjZfMTU4IiwiY3JlYXRlZCI6IjIwMjAtMTItMjggMTA6Mzc6MDYuMzM3NjQwIiwidHJhbnNmZXJfb3JkZXJfaXRlbV91aWQiOiJ0cmlfODk0NWM3NzAtYmZiNi00MDFlLTgzNDYtYzIzOTllNzYwM2Q5IiwiYW1vdW50X2NlbnRzIjoyODY3LCJtZXRhZGF0YSI6eyJwbGVkZ19zZXNzaW9uIjp7ImlwIjoiOTEuMTYxLjE4MS4zMiIsInVzZXJfYWdlbnQiOnsic3RyaW5nIjoiTW96aWxsYS81LjAgKFgxMTsgVWJ1bnR1OyBMaW51eCB4ODZfNjQ7IHJ2Ojg0LjApIEdlY2tvLzIwMTAwMTAxIEZpcmVmb3gvODQuMCIsInBsYXRmb3JtIjoibGludXgiLCJicm93c2VyIjoiZmlyZWZveCIsInZlcnNpb24iOiI4NC4wIiwibGFuZ3VhZ2UiOm51bGx9fX19.WRONGxSIGNATUREx3Xn8Nn4htDmkeF2vdLFITdLcrXxc"}';

        return $this;
    }

    public function build(): array
    {
        return json_decode($this->jsonContent, true, 512, \JSON_THROW_ON_ERROR);
    }
}
