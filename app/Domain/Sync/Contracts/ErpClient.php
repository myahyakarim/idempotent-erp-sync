<?php

namespace App\Domain\Sync\Contracts;

use App\Domain\Sync\DTO\CreateVendorBillCommand;

interface ErpClient
{
    /**
     * Remote create is NOT assumed idempotent.
     * Callers must enforce create-once semantics.
     *
     * @return array{remote_id: string, status: string}
     */
    public function createVendorBill(CreateVendorBillCommand $command): array;
}
