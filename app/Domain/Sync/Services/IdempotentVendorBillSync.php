<?php

namespace App\Domain\Sync\Services;

use App\Domain\Sync\Contracts\ErpClient;
use App\Domain\Sync\DTO\CreateVendorBillCommand;
use App\Models\IdempotencyRecord;
use App\Models\VendorBill;
use Illuminate\Support\Facades\DB;

final class IdempotentVendorBillSync
{
    public function __construct(private readonly ErpClient $erp) {}

    /**
     * @return array{vendor_bill_id: int, remote_id: string, replayed: bool}
     */
    public function sync(CreateVendorBillCommand $command): array
    {
        $key = $command->idempotencyKey();

        return DB::transaction(function () use ($command, $key) {
            $existing = IdempotencyRecord::query()
                ->where('key', $key)
                ->lockForUpdate()
                ->first();

            if ($existing?->status === IdempotencyRecord::STATUS_SUCCEEDED) {
                return [
                    'vendor_bill_id' => (int) $existing->resource_id,
                    'remote_id' => (string) $existing->remote_reference,
                    'replayed' => true,
                ];
            }

            $remote = $this->erp->createVendorBill($command);

            $bill = VendorBill::query()->create([
                'order_id' => $command->orderId,
                'bill_type' => $command->billType,
                'currency' => $command->currency,
                'amount_cents' => $command->amountCents,
                'line_items' => $command->lineItems,
                'remote_id' => $remote['remote_id'],
                'status' => $remote['status'],
            ]);

            IdempotencyRecord::query()->updateOrCreate(
                ['key' => $key],
                [
                    'status' => IdempotencyRecord::STATUS_SUCCEEDED,
                    'resource_id' => $bill->id,
                    'remote_reference' => $remote['remote_id'],
                    'last_error' => null,
                ],
            );

            return [
                'vendor_bill_id' => $bill->id,
                'remote_id' => $remote['remote_id'],
                'replayed' => false,
            ];
        });
    }
}
