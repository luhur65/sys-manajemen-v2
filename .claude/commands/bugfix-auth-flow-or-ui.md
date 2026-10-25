---
name: bugfix-auth-flow-or-ui
description: Workflow command scaffold for bugfix-auth-flow-or-ui in sys-manajemen-v2.
allowed_tools: ["Bash", "Read", "Write", "Grep", "Glob"]
---

# /bugfix-auth-flow-or-ui

Use this workflow when working on **bugfix-auth-flow-or-ui** in `sys-manajemen-v2`.

## Goal

Fixes bugs in authentication flow or related UI, typically involving controllers, views, filters, or JavaScript.

## Common Files

- `app/Controllers/Login.php`
- `app/Controllers/Webauthn.php`
- `app/Config/Filters.php`
- `app/Views/login.php`
- `public/libraries/tas-lib/js/mains.js`

## Suggested Sequence

1. Understand the current state and failure mode before editing.
2. Make the smallest coherent change that satisfies the workflow goal.
3. Run the most relevant verification for touched files.
4. Summarize what changed and what still needs review.

## Typical Commit Signals

- Update controller logic to fix validation, error handling, or data processing (e.g., Login.php, Webauthn.php)
- Modify views to correct UI/UX or integrate CSRF tokens (e.g., login.php)
- Adjust filters to allow or restrict routes (Filters.php)
- Update JavaScript for UI behavior (mains.js)
- Test to ensure the fix resolves the issue

## Notes

- Treat this as a scaffold, not a hard-coded script.
- Update the command if the workflow evolves materially.