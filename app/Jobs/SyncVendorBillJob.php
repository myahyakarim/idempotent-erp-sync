<?php

namespace App\Jobs;

use App\Domain\Sync\DTO\CreateVendorBillCommand;
use App\Domain\Sync\Services\IdempotentVendorBillSync;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncVendorBillJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public CreateVendorBillCommand $command) {}

    public function backoff(): array
    {
        return [1, 5, 15, 30];
    }

    public function handle(IdempotentVendorBillSync $sync): void
    {
        $result = $sync->sync($this->command);

        Log::info('vendor_bill.sync.completed', [
            'order_id' => $this->command->orderId,
            'bill_type' => $this->command->billType,
            'remote_id' => $result['remote_id'],
            'replayed' => $result['replayed'],
        ]);
    }
}
