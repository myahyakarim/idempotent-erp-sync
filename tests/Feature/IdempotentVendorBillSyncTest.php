<?php

namespace Tests\Feature;

use App\Domain\Sync\DTO\CreateVendorBillCommand;
use App\Domain\Sync\Services\FakeLedgerBridgeClient;
use App\Domain\Sync\Services\IdempotentVendorBillSync;
use App\Models\VendorBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdempotentVendorBillSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_retry_does_not_create_a_second_remote_bill(): void
    {
        $erp = $this->app->make(FakeLedgerBridgeClient::class);
        $sync = $this->app->make(IdempotentVendorBillSync::class);

        $command = new CreateVendorBillCommand(
            orderId: 'ord_1001',
            billType: 'vendor_payable',
            currency: 'USD',
            amountCents: 12500,
            lineItems: [
                ['sku' => 'SKU-1', 'qty' => 1, 'unit_cents' => 12500],
            ],
        );

        $first = $sync->sync($command);
        $second = $sync->sync($command);

        $this->assertFalse($first['replayed']);
        $this->assertTrue($second['replayed']);
        $this->assertSame($first['remote_id'], $second['remote_id']);
        $this->assertSame(1, $erp->createCount());
        $this->assertSame(1, VendorBill::query()->count());
    }

    public function test_accepts_sync_via_api(): void
    {
        $response = $this->postJson('/api/vendor-bills/sync', [
            'order_id' => 'ord_2002',
            'bill_type' => 'vendor_payable',
            'currency' => 'usd',
            'amount_cents' => 5000,
            'line_items' => [
                ['sku' => 'SKU-9', 'qty' => 2, 'unit_cents' => 2500],
            ],
        ]);

        $response->assertAccepted()
            ->assertJsonPath('idempotency_key', 'vendor_bill:ord_2002:vendor_payable');
    }
}
