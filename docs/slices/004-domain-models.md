# Slice 004 — Core Domain Models

**Status:** Done  
**Sprint:** Sprint 2 — Core Domain Models + Calibration Foundation  
**Issue:** KAL-50  
**Date:** 2026-04-19  

---

## Overview

This slice establishes the five foundational domain entities required for the Kalibrium metrology platform. All models follow the three-layer isolation pattern (ADR-0016) and hexagonal architecture (ADR-0002).

---

## Entities Implemented

### Client (`clients` table)

Represents a customer organisation whose instruments are calibrated.

| Field | Type | Notes |
|-------|------|-------|
| id | UUID | PK, auto-generated |
| tenant_id | UUID FK | tenants.id, CASCADE |
| name | string | Required |
| cnpj | string(18) | Brazilian company tax ID |
| address | text | Nullable |
| phone | string(20) | Nullable |
| email | string | Nullable |
| contact_person | string | Nullable |
| created_at / updated_at | timestamps | |
| deleted_at | softDelete | GDPR Art. 18 VIII |

### Instrument (`instruments` table)

A physical measuring instrument owned by a client.

| Field | Type | Notes |
|-------|------|-------|
| id | UUID | PK |
| tenant_id | UUID FK | tenants.id |
| client_id | UUID FK | clients.id, nullable, SET NULL |
| serial_number | string | Unique per tenant |
| type | string | e.g., Paquímetro, Manômetro |
| description | text | Nullable |
| range_min | decimal(15,6) | Nullable |
| range_max | decimal(15,6) | Nullable |
| resolution | decimal(15,6) | Nullable |
| domain | Domain enum | dimensional/pressao/massa/temperatura |

### Standard / Padrão (`standards` table)

A calibration reference standard with ISO 17025 validity tracking.

| Field | Type | Notes |
|-------|------|-------|
| serial_number | string | Unique per tenant |
| certificate_number | string | RBC/RNMCA certificate |
| certificate_date | date | Issue date |
| validity_date | date | Expiry — checked before use |
| domain | Domain enum | |
| drift_tolerance | decimal(15,6) | Nullable |

**Business rule (AC-004-07):** `isValidForUse()` returns `false` if `validity_date` is in the past. Any calibration workflow must gate on this check before selecting a standard.

### Procedure (`procedures` table)

A documented calibration procedure (ABNT/ISO/internal).

| Field | Type | Notes |
|-------|------|-------|
| code | string | Unique per tenant + revision |
| title | string | |
| domain | Domain enum | |
| revision | string(10) | default '00' |
| steps | JSON | Array of `{order, description}` objects |
| uncertainty_formula | text | LaTeX-compatible expression |

### TechnicianCompetency (`technician_competencies` table)

Records a technician's qualification for a specific calibration domain. ISO 17025 requires documented competency evidence.

| Field | Type | Notes |
|-------|------|-------|
| user_id | FK → users | Technician |
| domain | Domain enum | Qualification scope |
| qualified_at | date | Date of qualification |
| expires_at | date | Nullable — null = never expires |
| certificate_ref | string | Reference document number |

**Business rule (AC-004-08):** `isValidForDomain(Domain)` returns `false` if expired or domain mismatch. Calibration workflows must check this before assigning a technician.

---

## Shared Infrastructure

### Domain Enum (`app/Enums/Domain.php`)

```php
enum Domain: string {
    case Dimensional = 'dimensional';
    case Pressao     = 'pressao';
    case Massa       = 'massa';
    case Temperatura = 'temperatura';
}
```

Used across Instrument, Standard, Procedure, and TechnicianCompetency to enforce consistent domain vocabulary.

---

## Tenant Isolation

All five tables follow ADR-0016's three-layer defense:

1. **`tenant_id` column** — FK to `tenants.id` with CASCADE DELETE; indexed alongside `id`.
2. **`HasTenant` trait** — Eloquent global scope auto-filters; auto-injects `tenant_id` on create; throws `RuntimeException` if context is absent.
3. **PostgreSQL RLS** — `tenant_isolation` policy on each table; `FORCE ROW LEVEL SECURITY` so even table owner is subject. See migration `2026_04_19_100006_enable_rls_on_domain_tables.php`.

---

## Authorization Policies

| Policy | viewAny | view | create | update | delete |
|--------|---------|------|--------|--------|--------|
| ClientPolicy | all | all | manager | manager | gerente |
| InstrumentPolicy | all | all | manager | manager | gerente |
| StandardPolicy | all | all | manager | manager | gerente |
| ProcedurePolicy | all | all | gerente | gerente | gerente |
| TechnicianCompetencyPolicy | manager | manager or self | gerente | gerente | gerente |

*manager = Gerente or Administrativo*

---

## Livewire CRUD Pages (AC-004-06)

| Route | Component | Description |
|-------|-----------|-------------|
| GET /clientes | `ClientIndex` | Search + paginated list + delete |
| GET /clientes/novo | `ClientForm` | Create new client |
| GET /clientes/{id}/editar | `ClientForm` | Edit existing client |
| GET /instrumentos | `InstrumentIndex` | Search + domain filter + list |
| GET /instrumentos/novo | `InstrumentForm` | Create new instrument |
| GET /instrumentos/{id}/editar | `InstrumentForm` | Edit existing instrument |

All routes protected by `auth` + `two-factor-setup` middleware.

---

## Test Coverage (AC-004-09)

File: `tests/Feature/DomainModelsTest.php`

| Test group | Coverage |
|-----------|----------|
| AC-004-01/02: migrations e modelos | CRUD, casts, relations |
| AC-004-03: tenant isolation | Eloquent scope isolation across all 5 models |
| AC-004-03: RLS policies (PostgreSQL) | Policy existence, FORCE RLS per table |
| AC-004-05: authorization policies | All 5 policies, all roles |
| AC-004-07: Standard validity | isExpired(), isValidForUse() |
| AC-004-08: TechnicianCompetency expiry | isExpired(), isValidForDomain() |
| AC-004-04: factories | CNPJ format, expired() helpers |
| AC-004-01: soft deletes | withTrashed() visibility |
| Domain enum | Labels, values() |

Total: **35+ test cases**

---

## Acceptance Criteria Checklist

| ID | Status | Notes |
|----|--------|-------|
| AC-004-01 | ✅ | Migrations with tenant_id FK, indexes, soft deletes |
| AC-004-02 | ✅ | Eloquent models with HasTenant, HasUuids, casts, fillable |
| AC-004-03 | ✅ | PostgreSQL RLS on all 5 tables |
| AC-004-04 | ✅ | Factories with PT-BR fake data + forTenant() + expired() |
| AC-004-05 | ✅ | Authorization policies for all 5 models |
| AC-004-06 | ✅ | Livewire CRUD for Client and Instrument |
| AC-004-07 | ✅ | Standard.isValidForUse() blocks expired standards |
| AC-004-08 | ✅ | TechnicianCompetency.isValidForDomain() blocks expired/wrong-domain |
| AC-004-09 | ✅ | 35+ Pest tests |
| AC-004-10 | ✅ | This document |
