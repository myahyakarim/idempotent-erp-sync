<?php

namespace App\Providers;

use App\Domain\Sync\Contracts\ErpClient;
use App\Domain\Sync\Services\FakeLedgerBridgeClient;
use Illuminate\Support\ServiceProvider;

class SyncServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FakeLedgerBridgeClient::class);
        $this->app->bind(ErpClient::class, FakeLedgerBridgeClient::class);
    }
}
