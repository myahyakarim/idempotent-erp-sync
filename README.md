# Idempotent ERP Sync

PHP / Laravel service that syncs vendor bills to **LedgerBridge** with **create-once** semantics.

Retries are normal. Duplicate financial records are not.

## Problem

Queue workers deliver at-least-once. An ERP `create bill` call often is not idempotent.
If the remote write succeeds and the response is lost, a blind retry creates a second bill.

## Approach

- Stable idempotency key: `vendor_bill:{order_id}:{bill_type}`
- DB row lock + uniqueness where the bill is stored
- LedgerBridge client interface (in-memory adapter for local/test; swappable for HTTP)
- API accepts work with `202` and exposes a status read model

Details: [ARCHITECTURE.md](./ARCHITECTURE.md)

## Quick start

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan test
```

```bash
php artisan serve
# QUEUE_CONNECTION=sync is fine locally
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

## Stack

PHP 8.3+ · Laravel · SQLite (local) · PHPUnit
