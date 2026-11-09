<?php

namespace App\Domain\Sync\Services;

use App\Domain\Sync\Contracts\ErpClient;
use App\Domain\Sync\DTO\CreateVendorBillCommand;
use Illuminate\Support\Str;

/**
 * In-memory stand-in for an external ERP.
 * Tracks creates so tests can assert "how many times did remote create run?".
 */
final class FakeLedgerBridgeClient implements ErpClient
{
    /** @var list<array{key: string, remote_id: string}> */
    private array $creates = [];

    private bool $failNext = false;

    public function failNextRequest(): void
    {
        $this->failNext = true;
    }

    public function createVendorBill(CreateVendorBillCommand $command): array
    {
        if ($this->failNext) {
            $this->failNext = false;
            throw new \RuntimeException('LedgerBridge temporarily unavailable');
        }

        $remoteId = 'LB-'.Str::upper(Str::random(10));

        $this->creates[] = [
            'key' => $command->idempotencyKey(),
            'remote_id' => $remoteId,
        ];

        return [
            'remote_id' => $remoteId,
            'status' => 'posted',
        ];
    }

    public function createCount(): int
    {
        return count($this->creates);
    }

    /** @return list<array{key: string, remote_id: string}> */
    public function creates(): array
    {
        return $this->creates;
    }
}
