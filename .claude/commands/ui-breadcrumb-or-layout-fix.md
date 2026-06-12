---
name: ui-breadcrumb-or-layout-fix
description: Workflow command scaffold for ui-breadcrumb-or-layout-fix in sys-manajemen-v2.
allowed_tools: ["Bash", "Read", "Write", "Grep", "Glob"]
---

# /ui-breadcrumb-or-layout-fix

Use this workflow when working on **ui-breadcrumb-or-layout-fix** in `sys-manajemen-v2`.

## Goal

Fixes issues related to breadcrumb navigation or layout rendering, often involving partial views and supporting JavaScript.

## Common Files

- `app/Views/partials/layouts/home.php`
- `app/Views/truckingtradoluar/index.php`
- `public/libraries/tas-lib/js/mains.js`

## Suggested Sequence

1. Understand the current state and failure mode before editing.
2. Make the smallest coherent change that satisfies the workflow goal.
3. Run the most relevant verification for touched files.
4. Summarize what changed and what still needs review.

## Typical Commit Signals

- Update partial layout view to fix breadcrumb logic (home.php)
- Modify related page views if necessary (e.g., truckingtradoluar/index.php)
- Adjust JavaScript to correct active URL or breadcrumb behavior (mains.js)

## Notes

- Treat this as a scaffold, not a hard-coded script.
- Update the command if the workflow evolves materially.