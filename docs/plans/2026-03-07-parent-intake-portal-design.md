# Parent Intake Portal Design

## Overview

Acorn is a parent intake portal for JumpStart Autism Collective (Albuquerque, NM) that reduces friction for families completing early paperwork to enter the JumpStart system. The current process involves long, cumbersome forms and only ~33% of referrals convert to patients. This portal aims to significantly improve that conversion rate by making intake as simple and guided as possible.

## Core Principle

Every decision optimizes for reducing friction and helping parents successfully complete intake.

## User Types

- **Parents/guardians** — complete intake paperwork
- **JumpStart staff** — review submissions, verify insurance, manage pipeline via Monday.com
- Additional user types (referring physicians, therapists) may be added in the future

## Architecture

Four layers:

1. **Form Schema Layer** (PHP config files) — defines every form: fields, types, validation rules, conditional logic, section grouping, translation keys, and Monday.com field mappings
2. **Backend Engine** (Laravel) — reads schemas, generates validation dynamically, stores responses, tracks completion, handles file uploads and signatures, syncs to Monday.com
3. **Frontend Renderer** (Svelte/Inertia) — receives schema from backend, renders field components, handles conditional show/hide in real-time, auto-saves progress
4. **Integration Layer** — magic link auth, Monday.com API sync, file storage, localization

### Data Flow

```
Parent requests magic link
  -> Receives email with link
  -> Lands on dashboard showing intake checklist
  -> Selects a form section
  -> Backend sends schema + any saved progress
  -> Svelte renders form, handles conditions client-side
  -> Auto-saves as parent fills in fields
  -> Parent marks section complete
  -> Dashboard updates progress
  -> When all sections complete -> triggers Monday.com sync
```

## Form Schema Design

Forms are defined as PHP config files in `config/forms/`, one per checklist item. Each schema defines:

- **Sections and fields** with types: `text`, `textarea`, `select`, `checkbox`, `radio`, `date`, `file`, `signature`, `phone`, `email`, `address`
- **Conditional logic** via a `conditions` array on each field — the Svelte renderer shows/hides fields in real-time, backend validation respects the same conditions
- **Inline translations** — each label carries all languages (`en`, `es`) so form changes and translations stay in sync
- **Monday.com mappings** — `monday_field` on each field maps directly to Monday.com column IDs for automatic sync
- **Laravel validation rules** — standard syntax, dynamically converted to FormRequest validation by the backend engine

### Example Schema

```php
[
    'key' => 'demographics',
    'title' => ['en' => 'Family Demographics', 'es' => 'Demografía Familiar'],
    'icon' => 'users',
    'order' => 1,
    'sections' => [
        [
            'key' => 'parent_info',
            'title' => ['en' => 'Parent/Guardian Information', 'es' => '...'],
            'fields' => [
                [
                    'key' => 'first_name',
                    'type' => 'text',
                    'label' => ['en' => 'First Name', 'es' => 'Nombre'],
                    'validation' => ['required', 'string', 'max:255'],
                    'monday_field' => 'parent_first_name',
                ],
                [
                    'key' => 'has_secondary_guardian',
                    'type' => 'checkbox',
                    'label' => ['en' => 'Is there a second parent/guardian?', 'es' => '...'],
                ],
                [
                    'key' => 'secondary_guardian_name',
                    'type' => 'text',
                    'label' => ['en' => 'Second Guardian Name', 'es' => '...'],
                    'validation' => ['required_if:has_secondary_guardian,true', 'string'],
                    'conditions' => [
                        ['field' => 'has_secondary_guardian', 'equals' => true],
                    ],
                ],
            ],
        ],
    ],
]
```

## Data Model

### Tables

- **`patients`** — email, preferred language, magic link token, token expiry, `synced_at`, `sync_status`
- **`form_responses`** — patient_id, schema key, response data (encrypted JSON), status (`in_progress` / `completed`), timestamps
- **`documents`** — patient_id, form_response_id, file path, original filename, mime type, timestamps
- **`signatures`** — patient_id, form_response_id, field key, signature image path, timestamps

### Relationships

```
Patient
  |-- has many FormResponses (one per form schema)
  |-- has many Documents
  |-- has many Signatures

FormResponse
  |-- belongs to Patient
  |-- has many Documents
  |-- has many Signatures
```

### Key Decisions

- Responses stored as encrypted JSON — schema-driven fields don't map to fixed columns
- No separate `children` table — child info lives inside form responses; can extract later if needed
- Monday.com sync status tracked on the patient level

## Parent Experience

### Landing Page
- Simple, welcoming page with JumpStart branding
- "Enter your email to get started" — one field, one button
- Language toggle (EN/ES) auto-detected from browser with manual override

### Magic Link Email
- Clean, branded email with a prominent "Continue your intake" button
- Link valid for 30 minutes, single-use, cryptographically random tokens

### Dashboard
- Warm greeting with progress indicator ("You've completed 2 of 6 sections")
- Checklist of form sections showing icon, title, status badge, and estimated time
- Parents click any section in any order

### Form Experience
- One section at a time, clean and spacious
- Auto-saves on every field blur
- Conditional fields animate in/out smoothly
- Progress within the form ("Section 2 of 4")
- "Save & Exit" always available
- "Mark as Complete" runs validation and highlights anything missing

### Completion
- Congratulations message when all sections are complete
- "Your information has been submitted. A team member will be in touch soon."
- Data syncs to Monday.com automatically

### Returning Parent
- Requests a new magic link from the landing page
- Lands back on their dashboard with all progress intact

## Staff Experience

### Staff Dashboard (Lightweight v1)
- Standard email/password login (Fortify)
- List of all patients with status: New / In Progress / Completed / Synced
- Click into a patient to view submitted responses in a read-only layout
- Ability to see what's missing if a parent is stuck
- No editing of patient data — that happens in Monday.com

## Monday.com Integration

- First-class citizen, not an afterthought
- On parent completion, a queued Laravel job:
  1. Creates a new item in a Monday.com board
  2. Maps schema fields to Monday.com columns via `monday_field` mappings
  3. Uploads documents as file attachments
  4. Records `synced_at` timestamp
- Failed syncs queue for retry via Laravel's job system
- Sync status visible on staff dashboard: Synced / Pending / Failed
- Syncs on completion (not incrementally) to keep Monday.com clean

## Security & HIPAA Readiness

### Data Protection
- Form responses encrypted at rest (Laravel `encrypted` cast)
- HTTPS enforced
- Magic links: single-use, 30-minute expiry, cryptographically random
- Sessions expire after 60 minutes of inactivity

### File Storage
- S3 in production with private visibility
- Signed temporary URLs for staff viewing
- Insurance cards, signatures, consent forms never publicly accessible

### Access Control
- Parents scoped to their own data via magic link session
- Staff gated by authentication + authorization policies
- No cross-patient data access possible

### HIPAA Notes
- Application-layer HIPAA readiness built from the start
- Full compliance also requires infrastructure decisions (hosting provider, BAAs with AWS/Monday.com) before production
- Audit logging and data retention policies to be added before production launch

## Technical Stack

- **Backend:** Laravel 12, PHP 8.4
- **Frontend:** Svelte 5, Inertia.js v2, Tailwind CSS v4
- **Auth:** Magic links (parents), Fortify (staff)
- **Routing:** Wayfinder for type-safe frontend route calls
- **Testing:** Pest 4
- **Code Quality:** Rector, Larastan (level 9), Pint
- **Integration:** Monday.com API
- **i18n:** English and Spanish, browser detection with manual override

## Mobile

- Mobile-first responsive design — no native app or PWA needed
- Parents primarily complete intake on their phones
- Camera access for document uploads (insurance cards)
- PWA can be added later if needed (trivial retrofit)

## Future Considerations

- Additional user types (referring physicians, therapists)
- Incremental Monday.com sync (per-section instead of on-completion)
- Central Reach integration as an alternative to Monday.com
- Staff form builder UI (Approach B evolution)
- Automated insurance verification
- Full audit logging for HIPAA
- Data retention and deletion policies
- Additional languages beyond English and Spanish
