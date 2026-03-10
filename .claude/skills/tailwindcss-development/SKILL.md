---
name: tailwindcss-development
description: "Styles applications using Tailwind CSS v4 utilities. Activates when adding styles, restyling components, working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors, typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle, hero section, cards, buttons, or any visual/UI changes."
license: MIT
metadata:
  author: laravel
---

# Tailwind CSS Development

## When to Apply

Activate this skill when:

- Adding styles to components or pages
- Working with responsive design
- Implementing dark mode
- Extracting repeated patterns into components
- Debugging spacing or layout issues

## Documentation

Use `search-docs` for detailed Tailwind CSS v4 patterns and documentation.

## Basic Usage

- Use Tailwind CSS classes to style HTML. Check and follow existing Tailwind conventions in the project before introducing new patterns.
- Offer to extract repeated patterns into components that match the project's conventions (e.g., Blade, JSX, Vue).
- Consider class placement, order, priority, and defaults. Remove redundant classes, add classes to parent or child elements carefully to reduce repetition, and group elements logically.

## shadcn/ui Design Token System (CRITICAL)

This project uses shadcn/ui which defines semantic color tokens in `resources/css/app.css`. **Always use semantic tokens — never use raw Tailwind palette colors or opacity tints of semantic colors as surface backgrounds.**

### Token Reference

| Purpose | Use | NEVER use |
|---------|-----|-----------|
| Page backgrounds | `bg-background` | `bg-primary/5`, `bg-white` |
| Card/panel surfaces | `bg-card` / `text-card-foreground` | `bg-white`, `bg-neutral-50` |
| Sidebar surfaces | `bg-sidebar` / `text-sidebar-foreground` | `bg-primary/5`, `bg-gray-50` |
| Muted/subdued surfaces | `bg-muted` / `text-muted-foreground` | `bg-neutral-100`, `bg-gray-100` |
| Secondary surfaces | `bg-secondary` / `text-secondary-foreground` | `bg-neutral-200`, `bg-gray-200` |
| Accent highlights | `bg-accent` / `text-accent-foreground` | `bg-primary/10`, `bg-primary/20` |
| Primary actions/brand | `bg-primary` / `text-primary-foreground` | Direct — this one is correct |
| Destructive/error | `bg-destructive` / `text-destructive-foreground` | `bg-red-*`, `text-red-*` |
| Popover surfaces | `bg-popover` / `text-popover-foreground` | `bg-white` |
| Page text | `text-foreground` | `text-black`, `text-neutral-900` |
| Subdued text | `text-muted-foreground` | `text-neutral-500`, `text-neutral-600`, `text-gray-500` |
| Borders | `border-border` | `border-neutral-200`, `border-gray-200` |
| Input borders | `border-input` | `border-neutral-300`, `border-gray-300` |
| Focus rings | `ring-ring` | `ring-primary`, `ring-blue-*` |

### Rules

1. **NEVER use Tailwind default palette colors** (`neutral-*`, `gray-*`, `zinc-*`, `slate-*`, `red-*`, `blue-*`, etc.) for colors that have semantic tokens. These bypass theming and won't adapt to theme variants (e.g., the `.staff` class).
2. **NEVER use `bg-primary/[opacity]` as a surface color.** `bg-primary/5` or `bg-primary/10` creates an opacity tint of the brand color. Use `bg-background`, `bg-muted`, `bg-secondary`, `bg-sidebar`, or `bg-accent` instead — these are purpose-built surface colors.
3. **NEVER use `text-white` or `text-black`.** Use `text-foreground`, `text-primary-foreground`, `text-destructive-foreground`, etc. These semantic tokens ensure correct contrast in all theme contexts.
4. **Opacity tints of semantic colors ARE acceptable for:**
   - Decorative elements (background blobs, gradients) where theming isn't critical
   - Borders on alerts/badges: `border-destructive/20`, `border-primary/20`
   - Very specific micro-interactions, not surfaces
5. **Hover states** should use semantic tokens: `hover:bg-muted` (subtle), `hover:bg-accent` (stronger), not `hover:bg-primary/5`.
6. **Before writing any color class**, check `resources/css/app.css` for the available tokens. If a semantic token exists for your use case, you must use it.

### Quick Decision Tree

```
Need a background color?
├── Full page/layout → bg-background
├── Card or elevated surface → bg-card
├── Sidebar or navigation panel → bg-sidebar
├── Muted/disabled/placeholder → bg-muted
├── Secondary container → bg-secondary
├── Highlighted/active element → bg-accent
├── Primary button/badge → bg-primary
├── Error/danger surface → bg-destructive
└── Popover/dropdown → bg-popover

Need a text color?
├── Primary body text → text-foreground
├── Subdued/helper text → text-muted-foreground
├── Text on primary bg → text-primary-foreground
├── Brand-colored text → text-primary
├── Error text → text-destructive
└── Text on card → text-card-foreground
```

## Tailwind CSS v4 Specifics

- Always use Tailwind CSS v4 and avoid deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.

### CSS-First Configuration

In Tailwind v4, configuration is CSS-first using the `@theme` directive — no separate `tailwind.config.js` file is needed:

<!-- CSS-First Config -->
```css
@theme {
  --color-brand: oklch(0.72 0.11 178);
}
```

### Import Syntax

In Tailwind v4, import Tailwind with a regular CSS `@import` statement instead of the `@tailwind` directives used in v3:

<!-- v4 Import Syntax -->
```diff
- @tailwind base;
- @tailwind components;
- @tailwind utilities;
+ @import "tailwindcss";
```

### Replaced Utilities

Tailwind v4 removed deprecated utilities. Use the replacements shown below. Opacity values remain numeric.

| Deprecated | Replacement |
|------------|-------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |

## Spacing

Use `gap` utilities instead of margins for spacing between siblings:

<!-- Gap Utilities -->
```html
<div class="flex gap-8">
    <div>Item 1</div>
    <div>Item 2</div>
</div>
```

## Dark Mode

If existing pages and components support dark mode, new pages and components must support it the same way, typically using the `dark:` variant. **Always use semantic tokens for dark mode — they adapt automatically.**

<!-- Dark Mode -->
```html
<!-- CORRECT: semantic tokens adapt to themes -->
<div class="bg-card text-card-foreground">
    Content adapts to color scheme
</div>

<!-- WRONG: hardcoded colors require manual dark: overrides -->
<div class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
    Fragile — breaks with theme changes
</div>
```

## Common Patterns

### Flexbox Layout

<!-- Flexbox Layout -->
```html
<div class="flex items-center justify-between gap-4">
    <div>Left content</div>
    <div>Right content</div>
</div>
```

### Grid Layout

<!-- Grid Layout -->
```html
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div>Card 1</div>
    <div>Card 2</div>
    <div>Card 3</div>
</div>
```

## Common Pitfalls

- **Using `bg-primary/5` or `bg-primary/10` as surface colors** — use `bg-background`, `bg-muted`, `bg-sidebar`, or `bg-accent` instead
- **Using `text-white`, `text-black`, `text-neutral-*`** — use semantic foreground tokens
- **Using Tailwind default palette** (`neutral-*`, `gray-*`, `zinc-*`) — use design tokens from `app.css`
- Using deprecated v3 utilities (bg-opacity-*, flex-shrink-*, etc.)
- Using `@tailwind` directives instead of `@import "tailwindcss"`
- Trying to use `tailwind.config.js` instead of CSS `@theme` directive
- Using margins for spacing between siblings instead of gap utilities
- Forgetting to add dark mode variants when the project uses dark mode
- **Using `ring-primary` for focus** — use `ring-ring` which is the dedicated focus token