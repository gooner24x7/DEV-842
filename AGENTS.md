# Repository guidance

## Project layout

- The runnable application is in `dashboard/`. Run PHP, Artisan, Composer, PHPUnit, and npm commands from that directory.
- Root-level Docker configuration mounts the repository at `/var/www`; the application path in the PHP container is `/var/www/dashboard`.
- Treat the root `composer.json` and lock files as duplicate or archival files unless a task explicitly targets them. The active Artisan application and its dependency manifests are under `dashboard/`.

## Supported stack

- Preserve compatibility with Laravel 9.52 and PHP 8.3 unless the user explicitly requests a framework upgrade.
- The frontend uses Vue 2, Vuex 3, Vue Router 3, Vuetify 2, Bootstrap 4, and Laravel Mix 6. Do not introduce Vue 3, Pinia, Vuetify 3, Vite, or Composition API conventions as incidental changes.
- MariaDB 10.5 and Redis are provided by Docker Compose.

## Working agreements

- Inspect the closest existing controller, service, repository, model, component, and test before choosing a pattern. Preserve the established architecture and naming style in the area being changed.
- Keep changes focused. Do not combine feature work with dependency modernization or broad formatting.
- Never commit `.env` values, credentials, tokens, database dumps, generated dependencies, logs, or runtime data.
- Do not run migrations, seeders, destructive Artisan commands, or writes against a non-test database unless the user explicitly asks for that operation.

## Verification

- The host PHP runtime may not have the Redis extension, so prefer the PHP container for Artisan and PHPUnit commands:
  `docker compose exec -T php-fpm sh -lc "cd /var/www/dashboard && php artisan ..."`
- Run the smallest relevant PHPUnit test or filter first. Run the full suite for broad backend changes when practical:
  `docker compose exec -T php-fpm sh -lc "cd /var/www/dashboard && php vendor/bin/phpunit"`
- For frontend changes, run `npm run dev` from `dashboard/`; use `npm run production` when a production bundle is specifically required.
- Report any verification that could not run because containers, services, extensions, or credentials were unavailable.
