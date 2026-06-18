---
name: feature-implementation-auth-or-webauthn
description: Workflow command scaffold for feature-implementation-auth-or-webauthn in sys-manajemen-v2.
allowed_tools: ["Bash", "Read", "Write", "Grep", "Glob"]
---

# /feature-implementation-auth-or-webauthn

Use this workflow when working on **feature-implementation-auth-or-webauthn** in `sys-manajemen-v2`.

## Goal

Implements or enhances authentication-related features, such as forgot/reset password or WebAuthn quick login, involving controllers, routes, models, and views.

## Common Files

- `app/Controllers/Login.php`
- `app/Controllers/Webauthn.php`
- `app/Models/MuserModel.php`
- `app/Models/PasswordResetModel.php`
- `app/Config/Routes.php`
- `app/Views/auth/reset_password.php`

## Suggested Sequence

1. Understand the current state and failure mode before editing.
2. Make the smallest coherent change that satisfies the workflow goal.
3. Run the most relevant verification for touched files.
4. Summarize what changed and what still needs review.

## Typical Commit Signals

- Update or add controller logic (e.g., Login.php, Webauthn.php)
- Modify or add model(s) if new data is needed (e.g., MuserModel.php, PasswordResetModel.php)
- Update routes to expose new endpoints (Routes.php)
- Update or create relevant views for user interaction (e.g., reset_password.php, home.php)
- Integrate new fields into user management UI if needed

## Notes

- Treat this as a scaffold, not a hard-coded script.
- Update the command if the workflow evolves materially.