<?php

namespace App\Http\Controllers\Api;

use App\Domain\Sync\DTO\CreateVendorBillCommand;
use App\Http\Controllers\Controller;
use App\Jobs\SyncVendorBillJob;
use App\Models\IdempotencyRecord;
use App\Models\VendorBill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorBillSyncController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'string', 'max:64'],
            'bill_type' => ['required', 'string', 'max:32'],
            'currency' => ['required', 'string', 'size:3'],
            'amount_cents' => ['required', 'integer', 'min:1'],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.sku' => ['required', 'string'],
            'line_items.*.qty' => ['required', 'integer', 'min:1'],
            'line_items.*.unit_cents' => ['required', 'integer', 'min:0'],
        ]);

        $command = new CreateVendorBillCommand(
            orderId: $data['order_id'],
            billType: $data['bill_type'],
            currency: strtoupper($data['currency']),
            amountCents: $data['amount_cents'],
            lineItems: $data['line_items'],
        );

        SyncVendorBillJob::dispatch($command);

        return response()->json([
            'status' => 'accepted',
            'idempotency_key' => $command->idempotencyKey(),
            'message' => 'Sync queued. Retries are safe; create-once is enforced in the worker.',
        ], 202);
    }

    public function show(string $orderId, string $billType): JsonResponse
    {
        $bill = VendorBill::query()
            ->where('order_id', $orderId)
            ->where('bill_type', $billType)
            ->first();

        $record = IdempotencyRecord::query()
            ->where('key', sprintf('vendor_bill:%s:%s', $orderId, $billType))
            ->first();

        if (! $bill) {
            return response()->json([
                'status' => $record?->status ?? 'pending',
                'bill' => null,
            ]);
        }

        return response()->json([
            'status' => 'succeeded',
            'bill' => $bill,
            'idempotency' => $record,
        ]);
    }
}
