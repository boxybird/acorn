---
name: architecture-review
description: Use when auditing code against architecture conventions, after completing a feature, or before committing. Activates when reviewing controllers for business logic that should be Actions, checking enum conventions, verifying trait usage, or identifying value object candidates.
---

# Architecture Review

Audit the codebase against the project's architecture conventions (Actions, Enums, Traits, Value Objects).

## When to Use

- After completing a feature implementation
- Before committing to verify pattern compliance
- When reviewing code for architectural consistency
- When user invokes `/architecture-review`

## Process

### 1. Scan Controllers for Business Logic

Read all controller files in `app/Http/Controllers/`.

Flag any controller method that:
- Dispatches jobs directly (should be in an Action)
- Sends notifications directly (should be in an Action)
- Has more than one model write operation (should be in an Action)
- Contains conditional business logic beyond simple guards/aborts (should be in an Action)

**Acceptable in controllers:** validation via Form Requests, single model create/update for CRUD, abort_if guards, returning responses.

### 2. Verify Action Conventions

Read all files in `app/Actions/` (exclude `app/Actions/Fortify/` which follows Fortify's own conventions).

For each Action, verify:
- Named as `VerbNoun` (not `NounVerber` or `NounAction`)
- Has a single public `handle()` method
- Dependencies injected via constructor (not passed to `handle()`)
- Runtime data passed to `handle()` (not injected via constructor)
- Return type is declared
- No direct HTTP concerns (no `request()`, no `redirect()`, no `back()`)

### 3. Verify Enum Conventions

Read all files in `app/Enums/`.

For each Enum, verify:
- String-backed (`: string`)
- TitleCase keys
- Has a `label(): string` method
- Group-query helpers are defined on the enum, not scattered in controllers

Then search the codebase for hardcoded status strings that should be enum cases.

### 4. Verify Trait Conventions

Read all files in `app/Concerns/`.

For each Trait, verify:
- Named as `HasX` or `VerbsNoun`
- Used by 2+ classes (search for `use TraitName` across `app/`)
- Does not import/use other traits from `app/Concerns/`

### 5. Check for Value Object Candidates

Search for patterns that suggest a Value Object is needed:
- Same group of 3+ related parameters passed together in multiple methods
- Repeated validation of the same structure (phone format, address components)

This is advisory only — flag as suggestions, not violations.

### 6. Report

Present findings grouped by category:

```
## Architecture Review Results

### Actions
- [VIOLATION] app/Http/Controllers/Foo.php:42 — dispatches job directly, extract to Action
- [OK] All Actions follow conventions

### Enums
- [VIOLATION] app/Http/Controllers/Bar.php:15 — hardcoded string 'approved' should use enum
- [OK] All Enums follow conventions

### Traits
- [WARNING] app/Concerns/SomeTrait.php — only used by one class, consider inlining
- [OK] All Traits follow conventions

### Value Object Candidates
- [SUGGESTION] PhoneNumber appears in 3 methods with formatting logic

### Summary
N violations, N warnings, N suggestions
```
