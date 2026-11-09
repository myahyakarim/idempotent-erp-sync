# Architecture — Idempotent ERP Sync

Create-once external writes under at-least-once delivery.

## Problem

Workers retry. Networks can drop responses after the remote system has already committed.
A naive `POST /bills` on every retry creates duplicate financial records.

## Design goals

1. Same logical operation → one remote bill (and one local projection)
2. Safe under concurrent workers
3. Observable: every attempt leaves an audit trail
4. LedgerBridge client is an interface — swap the fake for HTTP in production

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

- Local DB answers “did we already succeed?”
- Remote create is treated as non-idempotent → guarded by local check + lock
- Partial failure (remote OK, local write fails) is recovered via `remote_reference` and reconcile-on-retry

## Tradeoffs

| Choice | Why |
|--------|-----|
| DB unique + `lockForUpdate` | Strong create-once without requiring Redis |
| Sync job after HTTP `202` | Keeps the API fast; failure handling lives in the worker |
| Interface + in-process client | Fast tests locally; replace with HTTP adapter for real LedgerBridge |

## Out of scope

- Multi-region active-active
- Saga orchestration across many aggregates
- Full ERP auth protocol surface
