---
name: hipaa-compliance
description: "Use when auditing, reviewing, or enforcing HIPAA compliance on the codebase. Invoke with /hipaa to run a full audit. Activates when user mentions HIPAA, PHI, protected health information, compliance audit, security audit, healthcare data, patient data, or data sensitivity review."
---

# HIPAA Compliance Audit

A strict, structured audit of the codebase against HIPAA Security Rule, Privacy Rule, and Breach Notification Rule requirements. This skill is RIGID — follow every step, skip nothing, rationalize nothing away.

## The Iron Law

**Every item in the checklist MUST be verified with evidence.** Do not mark an item as compliant based on assumption. Read the file. Run the command. Confirm the value. If you cannot verify, mark it as UNVERIFIED (treat as non-compliant).

**Evidence means output.** "I searched and found nothing" is not evidence. Show the grep command and its empty result. Show the file contents. Quote the config value. If you cannot produce output, the verdict is UNVERIFIED.

## Audit Structure

Follow the three HIPAA safeguard categories in order. For each item:
1. **Check** — What to look for (file, config, code pattern)
2. **Verify** — Run a command or read a file to confirm
3. **Verdict** — PASS / FAIL / UNVERIFIED
4. **Evidence** — The exact file:line or command output proving the verdict

## Phase 1: Technical Safeguards (Security Rule SS 164.312)

### 1.1 Access Control (SS 164.312(a)(1))

```
CHECK: Role-Based Access Control exists
VERIFY: Search for policies, gates, roles, permissions in app/
  - Grep for Gate::define, Policy classes, role/permission models
  - Check for authorization middleware on routes
VERDICT: PASS only if RBAC is implemented AND enforced on PHI routes
```

```
CHECK: Minimum Necessary principle on data exposure
VERIFY: Read HandleInertiaRequests.php shared data
  - Confirm user object uses explicit field selection (only/DTO), not full model
  - Grep for ->toArray(), ->toJson() on models with PHI fields
  - Check API resources for over-exposure
VERDICT: PASS only if PHI fields are never sent unless explicitly needed
```

```
CHECK: Automatic logoff after inactivity
VERIFY: Read SESSION_LIFETIME in .env — must be <= 15 minutes
  - Grep JS/Svelte/Vue/React files for idle, inactivity, timeout, mousemove, keydown listeners
  - Read config/session.php expire_on_close setting
VERDICT: PASS only if session timeout <= 15min AND frontend idle detection exists
  - Backend timeout alone is NOT sufficient — browser tabs stay open showing PHI
```

```
CHECK: Emergency access & session termination procedures
VERIFY: Check for admin ability to revoke sessions, deactivate users
  - Search for session invalidation, force-logout mechanisms
VERDICT: PASS only if admins can immediately terminate user access
```

```
CHECK: Unique user identification — no credential sharing
VERIFY: Check for concurrent session limits
  - Search for session binding (IP, user-agent)
VERDICT: PASS only if concurrent sessions are limited or monitored
```

### 1.2 Audit Controls (SS 164.312(b))

```
CHECK: Comprehensive audit logging exists
VERIFY: Search for audit log implementation
  - Check for login/logout event logging
  - Check for PHI access logging (read, create, update, delete)
  - Check for admin action logging
  - Verify logs include: who, what, when, where (IP), outcome
VERDICT: PASS only if ALL PHI access is logged with full context
```

```
CHECK: Audit logs are tamper-proof and retained
VERIFY: Check log storage mechanism
  - Logs must not be editable by application users
  - Retention must be >= 6 years
VERDICT: PASS only if logs are immutable and retention policy exists
```

### 1.3 Integrity Controls (SS 164.312(c)(1))

```
CHECK: PHI integrity protection
VERIFY: Check for database constraints, validation rules on PHI fields
  - Form requests validate PHI input
  - Database has appropriate constraints
VERDICT: PASS only if PHI data integrity is enforced at both layers
```

### 1.4 Person or Entity Authentication (SS 164.312(d))

```
CHECK: Multi-factor authentication enforced
VERIFY: Read config/fortify.php for 2FA feature
  - Check if 2FA is REQUIRED (not just available)
  - Search for middleware enforcing 2FA enrollment
VERDICT: PASS only if MFA is mandatory for all users accessing PHI
```

```
CHECK: Password complexity meets NIST 800-63B / HIPAA standards
VERIFY: Read password validation rules
  - Minimum 12 characters
  - Compromised password check enabled
  - Rules enforced in ALL environments (not just production)
VERDICT: PASS only if password rules are strong in every environment
```

```
CHECK: Email verification enforced
VERIFY: Read User model — must implement MustVerifyEmail
  - Check middleware enforces verified email before PHI access
VERDICT: PASS only if MustVerifyEmail is active (not commented out)
```

```
CHECK: Password confirmation timeout is short
VERIFY: Read AUTH_PASSWORD_TIMEOUT — must be <= 300 seconds (5 min)
VERDICT: PASS only if timeout <= 300s
```

### 1.5 Transmission Security (SS 164.312(e)(1))

```
CHECK: HTTPS enforced everywhere
VERIFY: Check APP_URL scheme, middleware for HTTPS redirect
  - Check for HSTS headers
  - Check TrustProxies middleware configuration
VERDICT: PASS only if HTTPS is enforced with HSTS
```

```
CHECK: Session cookies are secure
VERIFY: Read SESSION_SECURE_COOKIE — must be true in production
  - Check SESSION_SAME_SITE — should be "strict" for PHI apps
  - Check SESSION_ENCRYPT — must be true
VERDICT: PASS only if all three are properly configured
```

```
CHECK: Security headers present
VERIFY: Check for Content-Security-Policy, X-Frame-Options,
  X-Content-Type-Options, Referrer-Policy, Permissions-Policy
  - Check middleware or web server config
VERDICT: PASS only if all security headers are configured
```

### 1.6 Encryption (SS 164.312(a)(2)(iv))

```
CHECK: PHI encrypted at rest in database
VERIFY: Check model casts for 'encrypted' on PHI columns
  - Check database engine supports encryption at rest
  - SQLite without SQLCipher = automatic FAIL
VERDICT: PASS only if PHI columns use encrypted casts AND db supports encryption
```

```
CHECK: Session data encrypted
VERIFY: SESSION_ENCRYPT=true in .env
VERDICT: PASS only if explicitly true
```

```
CHECK: Application key exists and is secure
VERIFY: APP_KEY is set and not committed to version control
  - Read .gitignore and confirm .env is listed
  - Run: grep -c "APP_KEY=" .env (must return 1 with a value)
VERDICT: PASS only if key exists and .env is gitignored
```

## Phase 2: Administrative Safeguards (Security Rule SS 164.308)

### 2.1 PHI Data Inventory (SS 164.308(a)(1)(ii)(A))

```
CHECK: PHI fields are identified and classified
VERIFY: Search all models and migrations for PHI-candidate fields
  - Names like: ssn, social_security, date_of_birth, dob, medical_record,
    diagnosis, medication, insurance, health, patient, condition, treatment,
    provider, physician, lab_result, blood, allergy, prescription
  - Any field that COULD be PHI must be documented and encrypted
VERDICT: PASS only if a PHI inventory exists or no PHI fields are present yet
```

### 2.2 Business Associate Agreements (SS 164.308(b)(1))

```
CHECK: Third-party services have BAA coverage
VERIFY: Check for external service usage — grep ALL of these:
  - config/services.php — mail providers, notification channels
  - Grep blade/view files for external URLs (fonts.bunny.net, cdn.*, googleapis.com, etc.)
  - Grep for Http::, Guzzle, curl, or any outbound HTTP calls in app/
  - Check config/mail.php for third-party mail drivers
  - Error tracking services (Sentry, Bugsnag) in config/ or .env
  - Check package.json for analytics/tracking scripts
VERDICT: FAIL if ANY external service loads user data without BAA documentation
  - Self-hosted fonts/assets = PASS for that item
  - Each external service must be listed individually with BAA status
```

### 2.3 Contingency Plan (SS 164.308(a)(7))

```
CHECK: Backup and disaster recovery
VERIFY: Check for backup configuration
  - Scheduled backup commands
  - Backup encryption
  - Recovery testing documentation
VERDICT: PASS only if encrypted backups are configured and tested
```

### 2.4 Access Termination (SS 164.308(a)(3)(ii)(C))

```
CHECK: User deactivation without data destruction
VERIFY: Check User model for SoftDeletes
  - Check for account deactivation (not just deletion)
  - Hard delete of user records = FAIL (violates 6-year retention)
VERDICT: PASS only if soft deletes are used and retention is >= 6 years
```

## Phase 3: Code-Level PHI Protection

### 3.1 PHI Leakage Vectors

Run ALL of these searches. Do not skip any.

```
CHECK: PHI not leaked in logs
VERIFY:
  - Grep for Log::, logger(), info(), debug(), error() calls
  - Check if any log calls could include PHI variables
  - Check LOG_LEVEL — must be "error" or "warning" in production config
  - Check log channel — must use rotation (daily, not single)
VERDICT: FAIL if any log call could contain PHI or debug logging is default
```

```
CHECK: PHI not leaked in exceptions/error pages
VERIFY:
  - Check APP_DEBUG — must be false in .env.example
  - Check exception handler for PHI scrubbing
  - Check for custom error pages (not default Laravel debug pages)
VERDICT: FAIL if debug mode defaults to true or exceptions are unsanitized
```

```
CHECK: PHI not leaked via dd(), dump(), var_dump(), print_r()
VERIFY: Grep entire codebase for dd(, dump(, var_dump(, print_r(
VERDICT: FAIL if any are found outside of test files
```

```
CHECK: PHI not exposed in URL parameters
VERIFY: Check routes for PHI in query strings or URL segments
  - PHI should never appear in URLs (logged by web servers, browser history)
VERDICT: FAIL if routes contain PHI-candidate parameters in GET requests
```

```
CHECK: PHI not cached insecurely
VERIFY: Check cache driver configuration
  - Check for Cache::put/remember calls with PHI data
  - File-based cache with PHI = FAIL
VERDICT: PASS only if cache is encrypted or PHI is never cached
```

## Phase 4: Output Report

After completing ALL checks, produce a structured report:

### Report Format

```
# HIPAA Compliance Audit Report
Date: [current date]
Auditor: Claude (automated)

## Executive Summary
- Total checks: [N]
- PASS: [N] | FAIL: [N] | UNVERIFIED: [N]
- Critical findings: [list]
- Overall status: COMPLIANT / NON-COMPLIANT / PARTIALLY COMPLIANT

## Critical Findings (address immediately)
[Ordered by severity, with file:line and evidence]

## High Findings (address before PHI enters system)
[Ordered by severity, with file:line and evidence]

## Medium/Low Findings (address in next sprint)
[Ordered by severity, with file:line and evidence]

## Remediation Priority
1. [Highest priority item with specific fix]
2. [Next priority...]
...

## Missing Controls
[Controls that don't exist yet and must be built]
```

## Red Flags — STOP and Investigate

If you encounter ANY of these during an audit, flag them as CRITICAL immediately:

- PHI in log files or log calls
- dd()/dump() in non-test code
- Full model serialization to frontend without field filtering
- PHI in URL parameters
- Unencrypted PHI columns in database
- Debug mode enabled in production-like configs
- Hard deletion of records containing PHI
- External services loading without BAA documentation
- No audit logging whatsoever

## Rationalization Table

| Excuse | Reality |
|--------|---------|
| "This is just a dev environment" | Dev environments often get production data. Enforce controls everywhere. |
| "We'll add encryption later" | PHI without encryption is a breach. Add it before the data arrives. |
| "Only admins can see the logs" | HIPAA doesn't care who. Unauthorized PHI in logs = violation. |
| "The database is behind a firewall" | Defense in depth. Encrypt at rest regardless. |
| "We don't have PHI yet" | Build the controls before the data. Retrofitting is harder and riskier. |
| "2FA is optional per our policy" | If users access PHI, MFA should be mandatory. Optional = someone won't use it. |
| "Session timeout hurts UX" | Patient privacy outweighs convenience. 15 minutes is the standard. |
| "SQLite is fine for now" | SQLite has no access control or encryption. Not acceptable for PHI. |
| "We'll document the risk analysis later" | Risk analysis must precede PHI handling. No analysis = no compliance. |
| "This check seems excessive" | HIPAA auditors won't think so. Check it anyway. |
