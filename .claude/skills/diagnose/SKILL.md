---
name: diagnose
description: Use when debugging runtime errors, checking application health, or when user reports something isn't working. Invoke with /diagnose to check Laravel logs, browser logs, and auto-fix issues found.
---

# Diagnose

Check Laravel and browser logs for errors, diagnose root causes, and fix them.

## When to Use

- User reports something isn't working
- After deploying frontend or backend changes to verify no runtime errors
- Proactive health check during development

## Process

### 1. Gather Errors

Run these in parallel using MCP tools:

- `last-error` — Get the most recent Laravel exception
- `read-log-entries` (10 entries) — Recent Laravel log entries
- `browser-logs` (20 entries) — Recent frontend console errors

### 2. Triage

For each error found:

| Priority | Type | Action |
|----------|------|--------|
| Critical | 500 errors, uncaught exceptions, TypeError | Fix immediately |
| High | Svelte runtime errors (props_invalid_value, etc.) | Fix immediately |
| Medium | Warnings, deprecations | Note, fix if quick |
| Low | Info logs, expected behavior | Ignore |

### 3. Fix

- Read the relevant source files before making changes
- Fix the root cause, not the symptom
- Run `composer check` after PHP changes
- Run `npm run build` after frontend changes
- Re-check logs after fixes to confirm resolution

### 4. Report

Summarize what was found and fixed:

```
Found N error(s):
1. [file:line] — description — FIXED
2. [file:line] — description — FIXED
```

## Common Error Patterns

| Error | Likely Cause |
|-------|-------------|
| `props_invalid_value` | Svelte component receiving wrong type (undefined instead of string/boolean) |
| `bind:value` not working | shadcn-svelte Input component missing `$bindable()` declaration |
| `__construct() null argument` | Config returning null from `env()` without default |
| `ViteException: manifest` | Frontend not built — run `npm run build` |
| CSRF token mismatch | Missing `X-XSRF-TOKEN` header or expired session |
