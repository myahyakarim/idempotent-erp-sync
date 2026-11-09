<?php

namespace App\Domain\Sync\DTO;

final readonly class CreateVendorBillCommand
{
    public function __construct(
        public string $orderId,
        public string $billType,
        public string $currency,
        public int $amountCents,
        public array $lineItems,
    ) {}

    public function idempotencyKey(): string
    {
        return sprintf('vendor_bill:%s:%s', $this->orderId, $this->billType);
    }
}
