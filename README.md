# Idempotent ERP Sync

> Senior backend demo: **retries are normal — duplicate financial records are not.**

PHP / Laravel service that syncs a vendor bill to a fictional ERP (**LedgerBridge**) with **create-once** semantics.

This is a clean-room portfolio project. No employer code or brands.

## Why this exists

Interviewers and hiring managers for senior / staff backend roles care whether you can design:

- at-least-once workers without double side effects
- stable idempotency keys
- locks + uniqueness where money lands
- clear API contracts (`202 Accepted` + status read model)

## Quick start

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan test
```

Run the API (sync driver for local demos):

```bash
php artisan serve
# QUEUE_CONNECTION=sync is fine for local demos
```

```bash
curl -X POST http://127.0.0.1:8000/api/vendor-bills/sync \
  -H 'Content-Type: application/json' \
  -d '{
    "order_id": "ord_1001",
    "bill_type": "vendor_payable",
    "currency": "USD",
    "amount_cents": 12500,
    "line_items": [{"sku":"SKU-1","qty":1,"unit_cents":12500}]
  }'
```

## Architecture

See [ARCHITECTURE.md](./ARCHITECTURE.md).

## What “senior” looks like here

- Domain folder (`app/Domain/Sync`) separate from HTTP
- Interface + fake ERP for deterministic tests
- Feature test proving **retry ≠ second remote create**
- Job backoff policy documented in code

## Stack

PHP 8.3+ · Laravel 13 · SQLite (demo) · PHPUnit
