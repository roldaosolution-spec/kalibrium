# Slice 005 — Service Order + Calibration State Machine

**Status:** Done
**Sprint:** Sprint 2 — Core Domain Models + Calibration Foundation
**Issue:** KAL-51
**Date:** 2026-04-19

---

## Overview

This slice implements the core operational workflow for the Kalibrium metrology platform: Service Orders (OS) and Calibrations with full ISO 17025-compliant state machines. It builds on the Slice 004 domain models (Client, Instrument, Standard, Procedure, TechnicianCompetency).

---

## Entities Implemented

### ServiceOrder (`service_orders` table)

Represents a work order grouping one or more instrument calibrations for a client.

| Field | Type | Notes |
|-------|------|-------|
| id | UUID | PK, auto-generated |
| tenant_id | UUID FK | tenants.id, CASCADE |
| number | string(20) | Auto: OS-{year}-{seq}, unique per tenant |
| client_id | UUID FK | clients.id, nullable, SET NULL |
| mode | enum | bench / field / umc |
| status | enum | see state machine below |
| sla_date | date | Nullable service-level deadline |
| assigned_technician_id | bigint FK | users.id, nullable |
| notes | text | Nullable |
| created_at / updated_at | timestamps | |
| deleted_at | softDelete | |

### Calibration (`calibrations` table)

A calibration event for a single instrument within a ServiceOrder.

| Field | Type | Notes |
|-------|------|-------|
| id | UUID | PK |
| tenant_id | UUID FK | tenants.id |
| service_order_id | UUID FK | service_orders.id |
| instrument_id | UUID FK | instruments.id |
| standard_id | UUID FK | standards.id, nullable |
| procedure_id | UUID FK | procedures.id, nullable |
| executor_id | bigint FK | users.id (executor technician) |
| verifier_id | bigint FK | users.id (verifier technician) |
| status | enum | see state machine below |
| started_at | timestamp | Nullable |
| completed_at | timestamp | Nullable |
| certificate_number | string(30) | Auto: CERT-{year}-{seq}, nullable until issued |

### CalibrationPoint (`calibration_points` table)

An individual measurement point recorded during a calibration.

| Field | Type | Notes |
|-------|------|-------|
| id | UUID | PK |
| calibration_id | UUID FK | calibrations.id, CASCADE |
| nominal_value | decimal(15,6) | Expected value |
| measured_value | decimal(15,6) | Actual measured value |
| unit | string(50) | e.g., mm, kPa, g, °C |
| deviation | decimal(15,6) | measured - nominal |
| uncertainty | decimal(15,6) | Measurement uncertainty |
| pass | boolean | abs(deviation) ≤ uncertainty |

---

## State Machines

### ServiceOrder States

```
draft → open → in_progress → pending_review → completed → invoiced → closed
                                ↑               |
                                └───────────────┘ (re-open for rework)
```

| State | Description |
|-------|-------------|
| draft | Created, not yet dispatched |
| open | Dispatched, awaiting pickup |
| in_progress | Technician actively working |
| pending_review | Awaiting supervisor sign-off |
| completed | All calibrations done |
| invoiced | Billed to client |
| closed | Fully finalized |

### Calibration States

```
draft → in_progress → pending_review → approved → issued
  ↓           ↓              ↓            ↓
cancelled  cancelled     cancelled    cancelled
                ↑             |
                └─────────────┘ (reject for remeasurement)
```

| State | Description |
|-------|-------------|
| draft | Created, no measurements |
| in_progress | Technician entering measurements |
| pending_review | Awaiting verifier |
| approved | Dual sign-off complete (ISO 17025) |
| issued | Certificate generated |
| cancelled | Voided |

---

## Business Rules

### AC-005-04: State Machine (no state skipping)
`changeStatus()` throws `LogicException` for any invalid transition. The enum `allowedTransitions()` method defines the DAG.

### AC-005-05: Dual Sign-off (ISO 17025 §5.10.2)
`Calibration::approve(User $verifier)` throws `LogicException` if `$verifier->id === $executor_id`. Two distinct qualified technicians must sign off before a certificate can be issued.

### AC-005-06: Competency Gate (ISO 17025 §6.2)
`Calibration::start(User $executor)` checks `TechnicianCompetency` for the instrument's domain. Blocks if:
- No competency record exists for that user+domain, or
- The competency has `expires_at` in the past

The same check applies to the verifier in `approve()`.

### AC-005-07: Standard Validity Gate
`Calibration::start()` checks `Standard::isValidForUse()`. If the standard's `validity_date` is in the past, calibration is blocked.

### AC-005-02: Auto-numbering
- **ServiceOrder:** `OS-{year}-{0001}` — sequential per tenant per calendar year
- **Calibration certificate:** `CERT-{year}-{0001}` — sequential per tenant per year, assigned at `issue()`

---

## PostgreSQL RLS

Tables `service_orders` and `calibrations` have `tenant_isolation` RLS policies enforcing tenant isolation at the database level (ADR-0016 Layer 3). `calibration_points` inherits isolation via its `calibration_id` foreign key.

---

## Deliverables Completed

| AC | Description | Status |
|----|-------------|--------|
| AC-005-01 | Migrations: service_orders, calibrations, calibration_points | ✅ |
| AC-005-02 | Eloquent models with tenant scope, state enums, relationships, auto-numbering | ✅ |
| AC-005-03 | PostgreSQL RLS policies for service_orders and calibrations | ✅ |
| AC-005-04 | State machine transitions with validation | ✅ |
| AC-005-05 | Dual sign-off enforcement (executor ≠ verifier) | ✅ |
| AC-005-06 | Competency gate for executor and verifier | ✅ |
| AC-005-07 | Standard validity gate | ✅ |
| AC-005-08 | Livewire pages: OS list, OS detail, Calibration form, CalibrationPoint grid | ✅ |
| AC-005-09 | Authorization policies (role-based + tenant-scoped) | ✅ |
| AC-005-10 | Model factories with realistic data | ✅ |
| AC-005-11 | 55 Pest tests — 108 assertions, all passing | ✅ |
| AC-005-12 | Slice spec doc at docs/slices/005-service-order-calibration.md | ✅ |
