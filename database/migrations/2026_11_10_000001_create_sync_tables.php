<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_bills', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->index();
            $table->string('bill_type');
            $table->string('currency', 3);
            $table->unsignedBigInteger('amount_cents');
            $table->json('line_items');
            $table->string('remote_id')->unique();
            $table->string('status');
            $table->timestamps();

            $table->unique(['order_id', 'bill_type']);
        });

        Schema::create('idempotency_records', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('status');
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->string('remote_reference')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_records');
        Schema::dropIfExists('vendor_bills');
    }
};
