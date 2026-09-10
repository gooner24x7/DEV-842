# Dashboard application guidance

## Backend conventions

- This is a Laravel 9 application using the `App\\` namespace.
- API routes use Laravel's legacy string controller syntax. Match the surrounding route style unless a task explicitly modernizes routing.
- Controllers live in `app/Http/Controllers`, business logic commonly lives in `app/Service`, persistence logic commonly lives in `app/Repository`, and Eloquent models live in `app/Models`.
- Follow the existing service and repository boundaries around the feature. Avoid moving logic between layers unless the requested change requires it.
- Preserve existing API contracts unless the user requests a breaking change. Check route consumers and Vue components before changing response shapes, field names, status codes, or validation behavior.

## Frontend conventions

- Vue code lives in `resources/js`; use Vue 2 Options API and the existing Vuex/router/component patterns.
- Reuse existing Vuetify 2 and Bootstrap 4 components and styling conventions.
- Assets are built with Laravel Mix. Do not add Vite configuration as part of ordinary feature work.

## Tests and data

- Add or update focused PHPUnit 9 tests under `tests/Unit` or `tests/Feature` when behavior changes.
- Tests are configured for an in-memory SQLite database, array cache/session/mail drivers, and a synchronous queue. Keep tests independent of external services where possible.
- When a change touches migrations or database-specific SQL, account for MariaDB 10.5 in production-like environments and SQLite differences in tests.
