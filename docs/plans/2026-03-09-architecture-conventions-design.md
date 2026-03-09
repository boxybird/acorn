# Architecture Conventions Design

## Purpose

Establish scalability patterns to guard against tight coupling as the application takes on features. Defines conventions for Actions, Enums, Traits, and Value Objects, plus a `/architecture-review` skill to enforce them.

## Deliverables

1. Systematic refactor of existing business logic into Actions
2. CLAUDE.md updates with concise architecture conventions
3. `/architecture-review` skill for on-demand auditing

---

## Action Pattern

**Location:** `app/Actions/`

**Structure:**
- One class per business operation
- Constructor receives dependencies via injection
- Single public `handle()` method receives runtime data, returns a typed result
- No base class or interface unless a need emerges

**Naming:** `VerbNoun` — e.g., `ApproveIntake`, `FlagFormResponse`, `GenerateMagicLink`

**Example:**

```php
class ApproveIntake
{
    public function __construct(
        private SyncIntakeToMonday $syncJob,
    ) {}

    public function handle(Intake $intake): Intake
    {
        $intake->update(['status' => IntakeStatus::Approved]);

        $this->syncJob::dispatch($intake);

        return $intake;
    }
}
```

**When to create an Action:**
- Business logic beyond simple CRUD
- Could be triggered from multiple entry points (controller, command, job, test)
- Has side effects (notifications, status changes, external syncs)

**When NOT to create an Action:**
- Simple persistence — controller + Form Request
- Pure queries — Eloquent scopes or controller
- Async work — use a Job (a Job can call an Action internally)

**Refactor targets:**

| Action | Extracted from |
|---|---|
| `ApproveIntake` | `Staff\IntakeController@approve` |
| `FlagFormResponse` | `Staff\IntakeController@flag` |
| `ResolveIntakeFlag` | `Staff\IntakeController@resolveFlag` |
| `CompleteForm` | `Intake\FormController` completion logic |
| `GenerateMagicLink` | `MagicLinkService` |

---

## Enum Convention

**Location:** `app/Enums/`

**Rules:**
- Always string-backed
- TitleCase keys: `UnderReview`, not `UNDER_REVIEW`
- Human-readable labels via a `label(): string` method
- Group-query helpers live on the enum itself (e.g., `staffActionable()`)
- No external mapping arrays — the enum is the single source of truth

**Convention test:** If you find `if ($status === 'approved')` anywhere, that string should be an enum case.

---

## Trait Convention

**Location:** `app/Concerns/`

**Rules:**
- Provides a reusable capability mixable into unrelated classes
- Named as `HasX` or `VerbsNoun` — e.g., `HasEncryptedPhi`, `PasswordValidationRules`
- Traits should not depend on other traits
- Define expected host properties/methods via PHPDoc or abstract methods

**When to create a Trait:**
- Multiple unrelated classes need the same capability
- Shared validation rule sets across Form Requests and Actions

**When NOT to create a Trait:**
- The behavior is a business operation — use an Action
- Only one class uses it — inline it
- The classes share a parent — use a base class

---

## Value Object Convention

**Location:** `app/ValueObjects/`

**Rules:**
- Immutable — all properties `readonly`, set via constructor
- No identity (no ID field)
- Encapsulate validation in a static factory method or constructor
- Named as a noun: `PhoneNumber`, `Address`, `DateRange`

**When to create a Value Object:**
- A concept has structure + validation but isn't a database entity
- Same group of related primitives passed together repeatedly
- A primitive has domain rules (a phone number isn't just a string)

**When NOT to create a Value Object:**
- A plain scalar or array is sufficient
- The data maps directly to a model
- Only used in one place — wait for a second use

**Convention only for now** — introduce when a clear need arises.

---

## CLAUDE.md Updates

Add concise architecture conventions after the `</laravel-boost-guidelines>` closing tag under `# Architecture Conventions`. Rules only, no examples — this doc serves as the detailed reference.

Add a one-line skill reference so Claude knows the skill exists and when to activate it.

---

## `/architecture-review` Skill

**Purpose:** On-demand audit against these conventions.

**Checks:**
- **Controllers** — flags business logic that should be an Action
- **Actions** — verifies `handle()` method, constructor injection, `app/Actions/`, `VerbNoun` naming
- **Enums** — verifies string-backed, TitleCase keys, `label()` method, no string comparisons against enum values
- **Traits** — verifies `app/Concerns/`, used by 2+ unrelated classes, no trait-to-trait dependencies
- **Value Objects** — flags repeated groups of related primitives (suggests VO candidate)
- **General** — flags hardcoded status strings that should be enum cases

**Output:** Findings grouped by category with file paths, line numbers, and actionable recommendations.

**Trigger:** Manual via `/architecture-review`, or suggested by Claude after a major feature is completed.
