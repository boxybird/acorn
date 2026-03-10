# Demo Panel Redesign Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Redesign the demo panel from a two-column FAQ layout to a three-column pitch/features/accounts layout that sells decision-makers on Acorn.

**Architecture:** Replace `DemoAbout.svelte` content with three concise pitch sections. Create new `DemoFeatures.svelte` with feature cards using Badge and inline SVG icons. Update `DemoPanel.svelte` grid from two to three columns. Account switcher unchanged.

**Tech Stack:** Svelte 5 (runes), shadcn-svelte Badge component, Tailwind CSS v4

**Design doc:** `docs/plans/2026-03-10-demo-panel-redesign-design.md`

---

### Task 1: Create DemoFeatures.svelte

**Files:**
- Create: `resources/js/components/demo/DemoFeatures.svelte`

**Step 1: Create the feature cards component**

```svelte
<script lang="ts">
    import Badge from '@/components/ui/badge/Badge.svelte';

    type Feature = {
        icon: string;
        title: string;
        description: string;
    };

    const liveFeatures: Feature[] = [
        {
            icon: 'link',
            title: 'Magic Link Auth',
            description: 'No passwords, no friction',
        },
        {
            icon: 'languages',
            title: 'Bilingual',
            description: 'Full English & Spanish support',
        },
        {
            icon: 'save',
            title: 'Auto-Save',
            description: 'Parents can stop and resume anytime',
        },
        {
            icon: 'clipboard-check',
            title: 'Staff Dashboard',
            description: 'Review, flag, and approve intakes',
        },
        {
            icon: 'calendar-sync',
            title: 'Monday.com Sync',
            description: 'Completed intakes flow into your workflow',
        },
    ];

    const comingSoonFeatures: Feature[] = [
        {
            icon: 'shield-check',
            title: 'Insurance Verification',
            description: 'Automated eligibility checks',
        },
        {
            icon: 'message-circle',
            title: 'SMS Notifications',
            description: 'Text reminders and status updates',
        },
        {
            icon: 'puzzle',
            title: 'Software Integrations',
            description: 'Connect with existing JumpStart tools',
        },
        {
            icon: 'bar-chart',
            title: 'Analytics & Reporting',
            description: 'Track intake metrics and outcomes',
        },
    ];
</script>
```

Use inline SVG icons for each feature (Lucide-style, matching existing project patterns). Each card is a small row with icon, text, and badge.

The markup structure:

```svelte
<div class="space-y-6">
    <!-- Live Features -->
    <div>
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Features</h3>
        <div class="space-y-2">
            {#each liveFeatures as feature}
                <div class="flex items-start gap-3 rounded-lg border p-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                        <!-- inline SVG per feature.icon -->
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium">{feature.title}</span>
                            <Badge variant="default" class="text-[10px] px-1.5 py-0">Live</Badge>
                        </div>
                        <p class="text-xs text-muted-foreground">{feature.description}</p>
                    </div>
                </div>
            {/each}
        </div>
    </div>

    <!-- Coming Soon -->
    <div>
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Coming Soon</h3>
        <div class="space-y-2">
            {#each comingSoonFeatures as feature}
                <div class="flex items-start gap-3 rounded-lg border p-3 opacity-60">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground">
                        <!-- inline SVG per feature.icon -->
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium">{feature.title}</span>
                            <Badge variant="outline" class="text-[10px] px-1.5 py-0">Coming Soon</Badge>
                        </div>
                        <p class="text-xs text-muted-foreground">{feature.description}</p>
                    </div>
                </div>
            {/each}
        </div>
    </div>
</div>
```

Key details for the SVG icons (all 16x16, stroke-based, Lucide style):
- **link:** Link chain icon
- **languages:** Globe with translate symbol
- **save:** Floppy disk / cloud save
- **clipboard-check:** Clipboard with checkmark
- **calendar-sync:** Calendar with sync arrows
- **shield-check:** Shield with checkmark
- **message-circle:** Chat bubble
- **puzzle:** Puzzle piece
- **bar-chart:** Bar chart

Use an `{#if feature.icon === 'link'}` block or a helper snippet to render icons inline. Do NOT install an icon library — use inline SVGs matching the existing pattern in `DemoPanel.svelte` and `DemoAbout.svelte`.

**Step 2: Verify the file has no TypeScript or Svelte errors**

Run: `npx svelte-check --threshold error 2>&1 | head -20`

**Step 3: Commit**

```
git add resources/js/components/demo/DemoFeatures.svelte
git commit -m "feat: add DemoFeatures component with feature cards and badges"
```

---

### Task 2: Rewrite DemoAbout.svelte as The Pitch

**Files:**
- Modify: `resources/js/components/demo/DemoAbout.svelte`

**Step 1: Replace the entire content of DemoAbout.svelte**

Remove the FAQ sections array and all existing markup. Replace with three bold pitch sections:

```svelte
<div class="space-y-8">
    <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-accent">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 3h6l1 7H8L9 3z" />
                <path d="M8 10h8v2a4 4 0 0 1-8 0v-2z" />
                <path d="M12 14v8" />
                <path d="M8 22h8" />
            </svg>
        </div>
        <div>
            <h3 class="font-semibold">Acorn</h3>
            <p class="text-xs text-muted-foreground">Parent Intake Portal</p>
        </div>
    </div>

    <div>
        <h4 class="mb-2 text-sm font-bold uppercase tracking-wide text-destructive">The Problem</h4>
        <p class="text-sm leading-relaxed text-muted-foreground">
            Only 1 in 3 referred families complete intake. The current paper-based process is long, confusing, and loses families before they ever get help.
        </p>
    </div>

    <div>
        <h4 class="mb-2 text-sm font-bold uppercase tracking-wide text-primary">The Solution</h4>
        <p class="text-sm leading-relaxed text-muted-foreground">
            Acorn is a digital intake portal that meets parents where they are &mdash; on their phone, in their language, on their schedule. No accounts, no passwords. Just a link in their email.
        </p>
    </div>

    <div>
        <h4 class="mb-2 text-sm font-bold uppercase tracking-wide text-green-600 dark:text-green-400">The Result</h4>
        <p class="text-sm leading-relaxed text-muted-foreground">
            Completed intake packages land directly in your workflow. Staff reviews, flags corrections, and approves &mdash; all from one dashboard.
        </p>
    </div>
</div>
```

Key design notes:
- Keep the acorn icon header (matches existing pattern) but update text to "Acorn / Parent Intake Portal"
- Color-code the three section headings: destructive (problem), primary (solution), green (result) — creates visual progression
- No `<script>` tag needed — this is now pure markup
- Use `&mdash;` for em dashes

**Step 2: Verify no errors**

Run: `npx svelte-check --threshold error 2>&1 | head -20`

**Step 3: Commit**

```
git add resources/js/components/demo/DemoAbout.svelte
git commit -m "feat: redesign DemoAbout as three-section pitch for decision-makers"
```

---

### Task 3: Update DemoPanel.svelte to three-column layout

**Files:**
- Modify: `resources/js/components/demo/DemoPanel.svelte`

**Step 1: Add DemoFeatures import and update grid**

Add import at top of script:
```ts
import DemoFeatures from './DemoFeatures.svelte';
```

Replace the body section (the `<!-- Body -->` div, lines 93-104) with a three-column grid:

```svelte
<!-- Body -->
<div class="grid flex-1 grid-cols-1 overflow-hidden md:grid-cols-3">
    <!-- Left: The Pitch -->
    <div class="overflow-y-auto border-r p-6">
        <DemoAbout />
    </div>

    <!-- Middle: Features & Integrations -->
    <div class="overflow-y-auto border-r p-6">
        <DemoFeatures />
    </div>

    <!-- Right: Account Switcher -->
    <div class="overflow-y-auto p-6">
        <DemoAccountSwitcher {patients} {users} />
    </div>
</div>
```

Key changes:
- `flex flex-1` → `grid flex-1 grid-cols-1 md:grid-cols-3`
- All three columns get `overflow-y-auto` for independent scrolling
- First two columns get `border-r`
- Remove `max-w-sm` / `max-w-md` constraints from account switcher — grid handles sizing
- Stacks vertically on mobile (`grid-cols-1`), three columns at `md` breakpoint

**Step 2: Verify no errors and build succeeds**

Run: `npx svelte-check --threshold error 2>&1 | head -20`
Run: `npm run build 2>&1 | tail -5`

**Step 3: Commit**

```
git add resources/js/components/demo/DemoPanel.svelte
git commit -m "feat: update DemoPanel to three-column grid layout"
```

---

### Task 4: Visual QA and polish

**Step 1: Build the frontend**

Run: `npm run build`

**Step 2: Visual check with Playwright**

Navigate to the app's landing page, click the FAB to open the demo panel, and take a screenshot to verify:
- Three columns render at desktop width
- Pitch sections have color-coded headings
- Feature cards show icons, text, and badges
- Live cards are full opacity, coming soon cards are muted
- Account switcher looks unchanged
- Columns stack on mobile viewport

**Step 3: Fix any visual issues found**

Adjust spacing, sizing, or colors as needed.

**Step 4: Run code quality checks**

Run: `composer check`

This runs Rector, Pint, PHPStan, and tests. No PHP files changed so this is a sanity check.

**Step 5: Final commit if any polish changes were made**

```
git add -A
git commit -m "fix: polish demo panel layout and spacing"
```
