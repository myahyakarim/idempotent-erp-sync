# Architecture — Idempotent ERP Sync

Senior-level demo of **create-once external writes** under at-least-once delivery.

## Problem

Workers retry. Networks drop responses after the remote system has already committed.
A naive `POST /bills` on every retry creates **duplicate financial records**.

## Design goals

1. Same logical operation → **one** remote bill (and one local projection)
2. Safe under concurrent workers
3. Observable: every attempt leaves an audit trail
4. Fake LedgerBridge ERP client is swappable for a real HTTP adapter

## Flow

```
API ──► enqueue SyncVendorBillJob
              │
              ▼
        acquire lock(idempotency_key)
              │
              ▼
        existing success? ──yes──► return cached remote id
              │
              no
              ▼
        call LedgerBridge.createBill()
              │
              ▼
        persist IdempotencyRecord(SUCCEEDED) + VendorBill
```

## Idempotency key

`vendor_bill:{order_id}:{bill_type}`

Stable across retries. Never use a random UUID per attempt.

## Consistency model

- Local DB is source of truth for “did we already succeed?”
- Remote create is treated as non-idempotent → guarded by local check + lock
- Partial failure (remote OK, local write fails) is recovered by storing `remote_reference` when possible and reconciling on next run

## Tradeoffs

| Choice | Why |
|--------|-----|
| DB unique + lock | Works without Redis; clear for demos/interviews |
| Sync job after HTTP 202 | Keeps API fast; failure handling lives in the worker |
| Fake ERP | Portfolio-safe; proves the pattern without third-party accounts |

## Out of scope (intentionally)

- Multi-region active-active
- Saga orchestration across many aggregates
- Real ERP auth protocols
