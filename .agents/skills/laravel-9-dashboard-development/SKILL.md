---
name: laravel-9-dashboard-development
description: Develop, debug, refactor, or test backend and Vue features in this repository's Laravel 9 dashboard application. Use for application code under dashboard/; do not use for standalone infrastructure work or framework-major upgrades.
---

# Laravel 9 Dashboard Development

Work from `dashboard/` and keep the implementation compatible with the versions pinned in its Composer and npm lock files.

## Trace the feature

Before editing, identify the relevant entry point and follow the existing flow:

- For HTTP work, trace the route through its controller and any service, repository, model, resource, job, or event it calls.
- For frontend work, trace the route or page through the Vue component, Vuex module, API call, and corresponding backend endpoint.
- Search for all consumers before changing a public method, response payload, database field, event, or component prop.

Use the nearest comparable feature as the primary implementation example. This codebase has established legacy patterns that may differ from current Laravel documentation.

## Implement compatibly

- Use Laravel 9 APIs and the project's existing controller/service/repository boundaries.
- Use Vue 2 Options API, Vuex 3, Vue Router 3, Vuetify 2, and Laravel Mix 6 conventions.
- Preserve existing API behavior unless a contract change is part of the request.
- Keep schema changes backward-aware and avoid assumptions that only work in SQLite; production-like databases use MariaDB 10.5.
- Mock or fake external integrations in tests, including Microsoft, Zoho, Stripe, Google, OpenAI, mail, queues, and remote HTTP services.

## Validate proportionately

Prefer the Docker PHP service because the host PHP runtime may lack required extensions such as Redis.

- Run focused PHPUnit tests or a `--filter` for the changed behavior first.
- Run the full PHPUnit suite when the change crosses multiple backend areas.
- Run `npm run dev` for Vue, JavaScript, Sass, or asset changes.
- If validation cannot run, report the exact missing container, service, extension, or configuration rather than treating it as a passing check.
