# Acorn Design System Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Apply the acorn-branded design system (colors, typography, logo) to the existing shadcn-svelte component library via CSS token replacement.

**Architecture:** Replace CSS custom property values in `app.css` with acorn-derived colors, swap the font from Instrument Sans to Nunito, add a `.staff` CSS scope for the staff dashboard, and replace the logo components with the acorn SVG.

**Tech Stack:** Tailwind CSS v4, CSS custom properties, Google Fonts (Nunito), Svelte 5

**Docs to check:**
- @tailwindcss-development skill for Tailwind CSS v4 patterns
- @inertia-svelte-development skill for Svelte component patterns

---

### Task 1: Swap Font from Instrument Sans to Nunito

**Files:**
- Modify: `resources/views/app.blade.php:13-14`
- Modify: `resources/css/app.css:11-14,83-89`

**Step 1: Update the Google Fonts link in `app.blade.php`**

Replace line 13-14:

```html
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
```

With:

```html
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=nunito:400,600,700" rel="stylesheet" />
```

**Step 2: Update the font-sans variable in `app.css`**

Replace the `--font-sans` declaration in `@theme inline` (lines 11-14):

```css
--font-sans:
    Nunito, ui-sans-serif, system-ui, sans-serif,
    'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol',
    'Noto Color Emoji';
```

Replace the `--font-sans` in `@layer utilities` (lines 83-89):

```css
@layer utilities {
    body,
    html {
        --font-sans:
            'Nunito', ui-sans-serif, system-ui, sans-serif,
            'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol',
            'Noto Color Emoji';
    }
}
```

**Step 3: Verify the font loads**

Run: `npm run build`

Open the app in a browser and confirm Nunito is rendering (check DevTools → Computed → font-family).

**Step 4: Commit**

```bash
git add resources/views/app.blade.php resources/css/app.css
git commit -m "Replace Instrument Sans with Nunito font"
```

---

### Task 2: Replace `:root` Color Tokens with Acorn Palette

**Files:**
- Modify: `resources/css/app.css:92-127`

**Step 1: Replace the `:root` block**

Replace lines 92-127 with the acorn-branded color tokens. Convert all hex values to HSL for consistency with the existing token format.

Reference hex → HSL conversions:

| Hex | HSL | Token |
|-----|-----|-------|
| `#5F3124` | `hsl(13 46% 26%)` | `--primary` |
| `#FDF8F3` | `hsl(30 67% 97%)` | `--primary-foreground` |
| `#3D1F18` | `hsl(11 48% 17%)` | `--foreground` |
| `#FFFCF8` | `hsl(34 100% 98%)` | `--background` |
| `#FFFFFF` | `hsl(0 0% 100%)` | `--card`, `--popover` |
| `#F5EDE3` | `hsl(33 46% 93%)` | `--secondary`, `--muted` |
| `#8B6F5C` | `hsl(24 21% 45%)` | `--muted-foreground` |
| `#F9DFA4` | `hsl(42 88% 81%)` | `--accent` |
| `#E8DDD0` | `hsl(33 33% 86%)` | `--border` |
| `#E0D3C3` | `hsl(33 33% 82%)` | `--input` |
| `#D17A2B` | `hsl(28 67% 49%)` | `--ring` |
| `#C4432B` | `hsl(10 64% 47%)` | `--destructive` |

```css
:root {
    --background: hsl(34 100% 98%);
    --foreground: hsl(11 48% 17%);
    --card: hsl(0 0% 100%);
    --card-foreground: hsl(11 48% 17%);
    --popover: hsl(0 0% 100%);
    --popover-foreground: hsl(11 48% 17%);
    --primary: hsl(13 46% 26%);
    --primary-foreground: hsl(30 67% 97%);
    --secondary: hsl(33 46% 93%);
    --secondary-foreground: hsl(11 48% 17%);
    --muted: hsl(33 46% 93%);
    --muted-foreground: hsl(24 21% 45%);
    --accent: hsl(42 88% 81%);
    --accent-foreground: hsl(13 46% 26%);
    --destructive: hsl(10 64% 47%);
    --destructive-foreground: hsl(30 67% 97%);
    --border: hsl(33 33% 86%);
    --input: hsl(33 33% 82%);
    --ring: hsl(28 67% 49%);
    --chart-1: hsl(28 67% 49%);
    --chart-2: hsl(38 50% 52%);
    --chart-3: hsl(13 46% 26%);
    --chart-4: hsl(42 90% 65%);
    --chart-5: hsl(10 68% 36%);
    --radius: 0.5rem;
    --sidebar-background: hsl(33 33% 97%);
    --sidebar-foreground: hsl(11 48% 17%);
    --sidebar-primary: hsl(13 46% 26%);
    --sidebar-primary-foreground: hsl(30 67% 97%);
    --sidebar-accent: hsl(33 46% 93%);
    --sidebar-accent-foreground: hsl(11 48% 17%);
    --sidebar-border: hsl(33 33% 86%);
    --sidebar-ring: hsl(28 67% 49%);
    --sidebar: hsl(33 33% 97%);
}
```

**Step 2: Verify the colors render**

Run: `npm run build`

Open the app and confirm the warm brown/gold palette is visible on buttons, cards, borders, and backgrounds.

**Step 3: Commit**

```bash
git add resources/css/app.css
git commit -m "Apply acorn brand colors to root CSS tokens"
```

---

### Task 3: Remove Dark Mode Theme

**Files:**
- Modify: `resources/css/app.css:129-163` (remove `.dark` block)
- Modify: `resources/views/app.blade.php:2` (remove dark class binding)

**Step 1: Remove the `.dark` CSS block**

Delete lines 129-163 (the entire `.dark { ... }` block) from `app.css`.

**Step 2: Remove dark class from HTML tag**

In `app.blade.php`, replace line 2:

```html
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
```

With:

```html
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
```

**Step 3: Verify no dark mode artifacts**

Run: `npm run build`

Open app with OS in dark mode — confirm the app stays light-themed.

**Step 4: Commit**

```bash
git add resources/css/app.css resources/views/app.blade.php
git commit -m "Remove dark mode theme"
```

---

### Task 4: Add `.staff` Scoped Theme

**Files:**
- Modify: `resources/css/app.css` (add after `:root` block)

**Step 1: Add the `.staff` scope**

Add this block after the `:root` block in `app.css`:

```css
.staff {
    --background: hsl(0 0% 98%);
    --card: hsl(0 0% 100%);
    --card-foreground: hsl(11 48% 17%);
    --secondary: hsl(0 0% 96%);
    --muted: hsl(0 0% 96%);
    --muted-foreground: hsl(0 0% 45%);
    --border: hsl(0 0% 90%);
    --input: hsl(0 0% 87%);
}
```

Note: Only override the tokens that differ. `--primary`, `--accent`, `--ring`, `--destructive` stay the same for brand cohesion.

**Step 2: Verify the scope works**

Temporarily add `class="staff"` to a wrapper in a test page and confirm the cooler background/borders render.

**Step 3: Commit**

```bash
git add resources/css/app.css
git commit -m "Add .staff scoped theme for utilitarian staff views"
```

---

### Task 5: Replace Logo Components with Acorn SVG

**Files:**
- Modify: `resources/js/components/AppLogoIcon.svelte`
- Modify: `resources/js/components/AppLogo.svelte`

**Step 1: Replace `AppLogoIcon.svelte`**

Replace the entire Laravel logo SVG with a simplified acorn icon derived from the logo at `storage/app/public/logos/acorn-logo.svg`. Use the logo's existing `viewBox="0 0 250 250"` and include the key paths with their gradient fills. Keep the `class` prop binding for sizing.

```svelte
<script lang="ts">
    let {
        class: className = '',
        ...rest
    }: {
        class?: string;
        [key: string]: unknown;
    } = $props();
</script>

<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 250 250"
    fill="none"
    class={className}
    {...rest}
>
    <!-- Acorn logo paths and gradients from storage/app/public/logos/acorn-logo.svg -->
    <!-- Copy the full SVG contents (paths + defs) from the logo file -->
</svg>
```

Copy all `<path>` elements and the `<defs>` block from the acorn logo SVG into this component.

**Step 2: Update `AppLogo.svelte`**

Replace the content to use the acorn logo and app name:

```svelte
<script lang="ts">
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
</script>

<div class="flex aspect-square size-8 items-center justify-center rounded-md">
    <AppLogoIcon class="size-8" />
</div>
<div class="ml-1 grid flex-1 text-left text-sm">
    <span class="mb-0.5 truncate leading-tight font-semibold">Acorn</span>
</div>
```

Note: Remove the `bg-sidebar-primary text-sidebar-primary-foreground` from the icon wrapper since the acorn SVG has its own gradient colors and doesn't use `currentColor`. Also remove the `fill-current text-white dark:text-black` classes from AppLogoIcon usage.

**Step 3: Verify the logo renders**

Run: `npm run build`

Open the app and confirm the acorn logo appears in the sidebar header and anywhere else `AppLogo`/`AppLogoIcon` is used.

**Step 4: Commit**

```bash
git add resources/js/components/AppLogoIcon.svelte resources/js/components/AppLogo.svelte
git commit -m "Replace Laravel logo with acorn brand logo"
```

---

### Task 6: Update App Name and Final Cleanup

**Files:**
- Modify: `.env` (APP_NAME)
- Modify: `resources/js/components/AppLogo.svelte` (if not already updated)

**Step 1: Update APP_NAME in `.env`**

```
APP_NAME=Acorn
```

**Step 2: Search for any remaining "Laravel" references in frontend**

Run: `grep -r "Laravel" resources/js/ --include="*.svelte" --include="*.ts" -l`

Update any that reference "Laravel Starter Kit" or similar to "Acorn".

**Step 3: Run full build and verify**

Run: `npm run build`

Visually verify:
- Warm brown/gold color palette throughout
- Nunito font rendering
- Acorn logo in header/sidebar
- App name shows "Acorn"
- No dark mode artifacts
- Buttons, inputs, cards all use the new warm tokens

**Step 4: Run `composer check`**

Run: `composer check`
Expected: PASS (no PHP changes, but validates nothing is broken)

**Step 5: Commit**

```bash
git add -A
git commit -m "Update app name to Acorn and clean up remaining Laravel references"
```

---

## Summary of Tasks

| # | Task | Files Modified |
|---|------|----------------|
| 1 | Swap font to Nunito | `app.blade.php`, `app.css` |
| 2 | Replace color tokens | `app.css` |
| 3 | Remove dark mode | `app.css`, `app.blade.php` |
| 4 | Add `.staff` scoped theme | `app.css` |
| 5 | Replace logo with acorn SVG | `AppLogoIcon.svelte`, `AppLogo.svelte` |
| 6 | Update app name and cleanup | `.env`, frontend files |
