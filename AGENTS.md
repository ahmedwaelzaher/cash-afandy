# Redot Dashboard — Agent Rules

- Laravel 13 admin dashboard.
- PHP 8.3+, MySQL/MariaDB.
- Blade, Livewire datatables, Tabler UI, jQuery.
- Core packages:
    - `redot/core`
    - `spatie/laravel-translatable`
    - `laravel/sanctum`
    - `maatwebsite/excel`
    - `carlos-meneses/laravel-mpdf`
- Tests: Pest 4.
- Formatting: `.editorconfig`, `pint.json`.

## The Three Rules

1. **Don't change the architecture.**
    - No SPA, no Inertia, no Vue/React.
    - No new build tools, no new packages, no new abstraction layers.
    - Use the layers that exist: controllers, models, Blade, Livewire datatables, routes, jobs, notifications, components, seeders, tests.
2. **Reuse before you add.**
    - If a helper, component, CSS class, route pattern, or JS initializer already exists, use it.
    - Don't create a parallel version under a new name.
    - No duplicates: never define the same style, value, or logic twice. If the website and the dashboard both need it, keep it once in the shared file (e.g. `overrides.css`) and delete the other copy.
3. **Make the smallest safe change.**
    - Don't refactor, rename, or restructure anything the task didn't ask for.
    - Match nearby naming, validation, and formatting.

## Where Things Live

| Layer                 | Path                                                                                           |
| --------------------- | ---------------------------------------------------------------------------------------------- |
| Dashboard controllers | `app/Http/Controllers/Dashboard` → `routes/dashboard.php`                                      |
| Website controllers   | `app/Http/Controllers/Website` → `routes/website.php`                                          |
| API controllers       | `app/Http/Controllers/Api` → `routes/api/*.php`                                                |
| Models                | `app/Models`                                                                                   |
| Migrations            | `database/migrations`                                                                          |
| Dashboard views       | `resources/views/dashboard`                                                                    |
| Website views         | `resources/views/website`                                                                      |
| Blade components      | `resources/components` + `app/View/Components`                                                 |
| Datatables            | `app/Livewire/Datatables` (extend `Redot\Datatables\Datatable`)                                |
| Helpers               | `app/helpers.php`                                                                              |
| JS                    | `public/assets/js`, `public/assets/plugins`, `public/assets/inits`                             |
| CSS                   | `public/assets/css` (`dashboard.css`, `website.css`, `app.css`, `themer.css`, `overrides.css`) |

- Don't touch the following unless explicitly asked:
    - `vendor`
    - `public/vendor`
    - `storage/framework/views`
    - `bootstrap/cache`

## Patterns To Follow

- **Controllers**
    - Inline `$request->validate([...])`.
    - Return Redot response helpers from `Redot\Http\Controllers\Controller`: `created()`, `updated()`, `deleted()`, etc.
- **Routes**
    - Dashboard routes use `auth:admins` + `Redot\Http\Middleware\RoutePermission`.
    - Check `route_allowed()` usage in datatables/views when adding actions.
- **Models**
    - Standard Laravel: casts, fillable, factories, soft deletes, roles, translatable attributes where appropriate.
- **Blade**
    - Reuse `x-form`, `x-input`, `x-select`, `x-toggle`, `x-uploader`, `x-rich-editor`, `x-alert`.
    - Match existing `partials/form.blade.php` files.
    - Wrap UI text in `__()`.
    - Use Tabler classes before writing CSS.
- **Datatables**
    - Use existing Redot column/filter/action classes.
    - Keep actions permission-aware (`route_allowed()`) and aware of soft-deleted state.
    - Query methods return an Eloquent `Builder`.
- **JS/CSS**
    - jQuery and the existing plugin init patterns.
    - Only add CSS to the app stylesheets when no existing class works.

## Database

- Credentials come from `.env` / Laravel config. Never hardcoded.
- Read-only by default. Use:
    - `SHOW TABLES`
    - `DESCRIBE`
    - `EXPLAIN`
    - `SELECT`
- **Never** run any of the following without explicit approval:
    - `INSERT`, `UPDATE`, `DELETE`
    - `DROP`, `ALTER`, `TRUNCATE`, `CREATE`
    - Migrations, seeders, or destructive Artisan commands
- Schema changes go through migrations in `database/migrations` following existing timestamp/naming conventions.
- If a DB change is needed, show the migration/SQL and wait.
- Don't expose sensitive user data unless debugging requires it.

## Workflow

- **Before editing**
    - Read the relevant routes, controllers, models, views, components, datatables, JS, CSS, migrations, and tests.
    - Read referenced files in full.
    - Trace bugs end-to-end before deciding the cause: validation → binding → permissions → model → view → JS → tests.
- **While editing**
    - Keep changes localized.
    - Follow nearby patterns.
    - Comment only non-obvious reasoning.
    - Preserve validation, authorization, translation, and escaping.
    - Add focused Pest tests when behavior changes.
- **After editing**
    - Run the narrowest useful check first.
    - Full suite: `composer test`.
    - Lint: `composer lint`.
    - If you can't run either, say so and flag the remaining risk.

## Ask First If The Change Touches

- Architecture
- Business logic
- Database structure or mutation
- Permissions/guards/impersonation
- Security
- Public API responses
- Route names or URLs
- UX behavior
- Translation strategy
- File organization
- Backward compatibility

When asking:

- State what's unclear.
- List options.
- Recommend the safest one.
- Note side effects.

**Don't ask** when the change is:

- Small
- Isolated
- Low-risk
- Follows a nearby pattern
- Touches none of the above

State any meaningful assumption.

## Response Style

- Brief explanation of the issue, then the fix.
- Name the files/functions changed.
- Flag side effects.
- If multiple solutions exist, recommend the safest one for this codebase.

## Project Context

- Cash Afandy is a personal product: cashbacks, vouchers, coupons, and expense tools. Favor clean, maintainable code and UI/UX polish.
- Every view must be responsive and support both RTL (Arabic) and LTR (English). Use logical CSS properties.
- After adding `__()` tokens, run `php artisan lang:extract` then `php artisan lang:publish`. Don't hand-edit `lang/*.json`.
- Don't start a dev server or take screenshots to verify UI. Run non-visual checks and tell the user what changed.
- Porting from the old Waffrlly repo, or working from the Figma design? Read `docs/project-context.md` first.

---

This is production code. Stability, permission safety, and consistency with existing Redot/Laravel patterns beat modernizing or refactoring unrelated code.
