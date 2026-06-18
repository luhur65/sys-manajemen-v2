```markdown
# sys-manajemen-v2 Development Patterns

> Auto-generated skill from repository analysis

## Overview

This skill teaches the core development patterns, coding conventions, and common workflows used in the `sys-manajemen-v2` repository. The project is a JavaScript-based system management application, with no detected framework, and focuses on authentication flows, UI consistency, and robust bugfixing. Learn how to implement features, fix bugs, and maintain code quality in this codebase.

## Coding Conventions

**File Naming**
- Use PascalCase for file names.
  - Example: `LoginController.js`, `UserModel.js`

**Import Style**
- Use relative imports for modules.
  ```js
  import { doSomething } from './utils/helper.js';
  ```

**Export Style**
- Use named exports.
  ```js
  // utils/helper.js
  export function doSomething() { ... }
  ```

**Commit Patterns**
- Use prefixes like `fix` or `feat` in commit messages.
  - Example: `fix: correct password reset token validation`
  - Example: `feat: add WebAuthn quick login support`

## Workflows

### Feature Implementation: Auth or WebAuthn
**Trigger:** When you want to add a new authentication feature or enhance existing auth flows.  
**Command:** `/new-auth-feature`

1. Update or add controller logic (e.g., `Login.php`, `Webauthn.php`).
2. Modify or add model(s) if new data is needed (e.g., `MuserModel.php`, `PasswordResetModel.php`).
3. Update routes to expose new endpoints (`Routes.php`).
4. Update or create relevant views for user interaction (e.g., `reset_password.php`, `home.php`).
5. Integrate new fields into user management UI if needed.

**Example: Adding a WebAuthn Quick Login**
```php
// app/Controllers/Webauthn.php
public function quickLogin() {
    // Controller logic for WebAuthn quick login
}

// app/Config/Routes.php
$routes->post('auth/webauthn/quick-login', 'Webauthn::quickLogin');
```

### Bugfix: Auth Flow or UI
**Trigger:** When you need to resolve an issue in the authentication process or its UI components.  
**Command:** `/fix-auth-bug`

1. Update controller logic to fix validation, error handling, or data processing (e.g., `Login.php`, `Webauthn.php`).
2. Modify views to correct UI/UX or integrate CSRF tokens (e.g., `login.php`).
3. Adjust filters to allow or restrict routes (`Filters.php`).
4. Update JavaScript for UI behavior (`mains.js`).
5. Test to ensure the fix resolves the issue.

**Example: Fixing CSRF Token in Login**
```php
// app/Views/login.php
<form method="post">
    <?= csrf_field() ?>
    <!-- login fields -->
</form>
```

### UI: Breadcrumb or Layout Fix
**Trigger:** When you encounter problems with breadcrumb display or layout consistency.  
**Command:** `/fix-breadcrumb`

1. Update partial layout view to fix breadcrumb logic (`home.php`).
2. Modify related page views if necessary (e.g., `truckingtradoluar/index.php`).
3. Adjust JavaScript to correct active URL or breadcrumb behavior (`mains.js`).

**Example: Fixing Breadcrumb in Layout**
```php
// app/Views/partials/layouts/home.php
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <!-- Breadcrumb logic here -->
  </ol>
</nav>
```

## Testing Patterns

- Test files use the pattern `*.test.*` (e.g., `Login.test.js`).
- The specific testing framework is unknown, but tests are colocated with related modules.
- To add a test, create a file like `FeatureName.test.js` and use named exports for test suites.

**Example:**
```js
// Login.test.js
export function testLoginSuccess() {
  // Test logic here
}
```

## Commands

| Command            | Purpose                                                    |
|--------------------|------------------------------------------------------------|
| /new-auth-feature  | Start implementing or enhancing authentication features    |
| /fix-auth-bug      | Fix bugs in authentication flow or related UI              |
| /fix-breadcrumb    | Resolve breadcrumb or layout rendering issues               |
```
