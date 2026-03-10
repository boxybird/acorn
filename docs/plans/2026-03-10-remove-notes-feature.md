# Remove Notes Feature — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Remove the intake notes feature from both the parent and staff sides to reduce noise and keep the portal focused on form completion.

**Architecture:** Delete the IntakeNote model and all associated backend (controller, request, routes, factory, migration) and frontend (UI sections, Wayfinder route imports) code. Clean up references in related files (Intake model, DashboardController, IntakeController, IntakePdfController, PDF template, DemoAbout). Regenerate Wayfinder routes. Remove orphaned tests.

**Tech Stack:** Laravel 12, Pest 4, Inertia/Svelte, Wayfinder

---

## Phase 1: Remove Backend (PHP)

### Task 1: Delete note-specific files

**Files to delete:**
- `app/Models/IntakeNote.php`
- `app/Http/Controllers/Intake/NoteController.php`
- `app/Http/Requests/Staff/StoreNoteRequest.php`
- `database/factories/IntakeNoteFactory.php`
- `database/migrations/2026_03_09_030313_create_intake_notes_table.php`

**Step 1: Delete the files**

```bash
rm app/Models/IntakeNote.php
rm app/Http/Controllers/Intake/NoteController.php
rm app/Http/Requests/Staff/StoreNoteRequest.php
rm database/factories/IntakeNoteFactory.php
rm database/migrations/2026_03_09_030313_create_intake_notes_table.php
```

**Step 2: Create a migration to drop the table**

```bash
php artisan make:migration drop_intake_notes_table --no-interaction
```

Write the migration:

```php
public function up(): void
{
    Schema::dropIfExists('intake_notes');
}
```

### Task 2: Remove notes from routes

**Files:**
- Modify: `routes/intake.php`
- Modify: `routes/web.php`

**Step 1: Remove from `routes/intake.php`**

Remove the `use App\Http\Controllers\Intake\NoteController;` import and the route:
```php
Route::post('/notes', [NoteController::class, 'store'])->name('notes.store');
```

**Step 2: Remove from `routes/web.php`**

Remove the route:
```php
Route::post('/intakes/{intake}/notes', [IntakeController::class, 'storeNote'])->name('intakes.notes.store');
```

### Task 3: Remove notes from Intake model

**File:** `app/Models/Intake.php`

Remove the `use App\Models\IntakeNote;` import (if present) and the `notes()` relationship method:

```php
/** @return HasMany<IntakeNote, $this> */
public function notes(): HasMany
{
    return $this->hasMany(IntakeNote::class);
}
```

Also remove the `HasMany` import for `IntakeNote` if it's no longer used by other relationships.

### Task 4: Remove notes from Staff IntakeController

**File:** `app/Http/Controllers/Staff/IntakeController.php`

- Remove `use App\Http\Requests\Staff\StoreNoteRequest;` import
- Remove `use App\Models\IntakeNote;` import
- Remove `'notes.user', 'notes.patient'` from the `$intake->load()` call on line 68
- Remove `'notes' => $intake->notes->sortBy('created_at')->values(),` from the Inertia render on line 88
- Remove the entire `storeNote()` method (lines 127-136)

### Task 5: Remove notes from Intake DashboardController

**File:** `app/Http/Controllers/Intake/DashboardController.php`

- Remove `use App\Models\IntakeNote;` import
- Remove the `$notes` query (lines 91-93 approximately)
- Remove `'notes' => $notes,` from the Inertia render

### Task 6: Remove notes from IntakePdfController and PDF template

**Files:**
- Modify: `app/Http/Controllers/Staff/IntakePdfController.php`
- Modify: `resources/views/pdf/intake-summary.blade.php`

**Step 1:** In IntakePdfController, remove `'notes.user', 'notes.patient'` from the `$intake->load()` call on line 15.

**Step 2:** In the Blade template, remove the notes section (lines 49-55 approximately — the `@if ($intake->notes->isNotEmpty())` block).

### Task 7: Remove notes from DemoController

**File:** `app/Http/Controllers/DemoController.php`

Remove `'intake_notes'` from the table reset array on line 59.

### Task 8: Run Rector, Pint, and PHPStan

```bash
vendor/bin/rector process
vendor/bin/pint --dirty
vendor/bin/phpstan analyse
```

Fix any issues found. PHPStan will catch any remaining references we missed.

---

## Phase 2: Remove Frontend (Svelte + Wayfinder)

### Task 9: Regenerate Wayfinder routes

```bash
php artisan wayfinder:generate
```

This removes the deleted route TypeScript files automatically.

### Task 10: Remove notes from parent Dashboard.svelte

**File:** `resources/js/pages/intake/Dashboard.svelte`

- Remove `import { store as storeNote } from '@/routes/intake/notes';` (line 10)
- Remove the `NoteItem` type definition (lines 40-48)
- Remove `notes` from the props destructure (lines 50-53) — keep only `intakes`
- Remove the `noteForm` and `submitNote` function and `getNoteAuthor` function (lines 57-73)
- Remove the entire notes section in the template (lines 116-162 — the `<!-- Notes Section -->` block)

### Task 11: Remove notes from staff IntakeDetail.svelte

**File:** `resources/js/pages/staff/IntakeDetail.svelte`

- Remove `import { store as storeNote } from '@/routes/staff/intakes/notes';` (line 11)
- Remove the `NoteItem` type and `notes` from props
- Remove the notes display section and note creation form from the template

### Task 12: Update DemoAbout.svelte description

**File:** `resources/js/components/demo/DemoAbout.svelte`

Update the staff description on line 21 to remove the "add notes" mention. Change to something like:
```
'Staff see submitted intakes in their dashboard. They can review each form, flag items that need correction, and approve completed intakes. Once approved, intake data will sync to Monday.com boards so staff can continue managing the family in their existing workflow.'
```

### Task 13: Build frontend and verify

```bash
npm run build
```

Fix any broken imports or references.

---

## Phase 3: Clean Up Tests

### Task 14: Delete note-specific test files

**Files to delete:**
- `tests/Feature/Models/IntakeNoteTest.php`
- `tests/Feature/Http/Controllers/Intake/NoteControllerTest.php`

```bash
rm tests/Feature/Models/IntakeNoteTest.php
rm tests/Feature/Http/Controllers/Intake/NoteControllerTest.php
```

### Task 15: Clean up note references in remaining tests

**Files to check and modify:**
- `tests/Feature/Http/Controllers/Staff/IntakeControllerTest.php` — remove the "allows staff to add a note" test
- `tests/Feature/Intake/DashboardTest.php` — remove any note-related assertions
- `tests/Feature/Services/MondayServiceTest.php` — check if notes are referenced (likely false positive from grep)

### Task 16: Run full verification

```bash
composer check
```

This runs Rector → Pint → PHPStan → Tests (with 100% coverage). Everything must pass.

If coverage drops below 100%, identify what new code is uncovered (e.g., modified DashboardController, IntakeController) and write tests to cover it.

---

## File Change Summary

### Delete (7 files):
| File | Reason |
|------|--------|
| `app/Models/IntakeNote.php` | Model removed |
| `app/Http/Controllers/Intake/NoteController.php` | Controller removed |
| `app/Http/Requests/Staff/StoreNoteRequest.php` | Form request removed |
| `database/factories/IntakeNoteFactory.php` | Factory removed |
| `database/migrations/2026_03_09_030313_create_intake_notes_table.php` | Migration removed |
| `tests/Feature/Models/IntakeNoteTest.php` | Test for deleted model |
| `tests/Feature/Http/Controllers/Intake/NoteControllerTest.php` | Test for deleted controller |

### Modify (11 files):
| File | Change |
|------|--------|
| `app/Models/Intake.php` | Remove `notes()` relationship |
| `app/Http/Controllers/Staff/IntakeController.php` | Remove storeNote method, notes loading/rendering |
| `app/Http/Controllers/Intake/DashboardController.php` | Remove notes query and prop |
| `app/Http/Controllers/Staff/IntakePdfController.php` | Remove notes eager loading |
| `app/Http/Controllers/DemoController.php` | Remove intake_notes from reset |
| `resources/views/pdf/intake-summary.blade.php` | Remove notes section |
| `resources/js/pages/intake/Dashboard.svelte` | Remove notes UI, imports, types |
| `resources/js/pages/staff/IntakeDetail.svelte` | Remove notes UI, imports, types |
| `resources/js/components/demo/DemoAbout.svelte` | Update description text |
| `routes/intake.php` | Remove note route |
| `routes/web.php` | Remove staff note route |

### Test modifications (2-3 files):
| File | Change |
|------|--------|
| `tests/Feature/Http/Controllers/Staff/IntakeControllerTest.php` | Remove note test |
| `tests/Feature/Intake/DashboardTest.php` | Remove note assertions |

### Create (1 file):
| File | Reason |
|------|--------|
| `database/migrations/XXXX_drop_intake_notes_table.php` | Drop the table |
