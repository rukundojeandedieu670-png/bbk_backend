# BBK Backend Implementation Phases

This Laravel application is the JSON API for the BBK website. The frontend remains a separate Next.js application. The logo is the primary brand anchor: navy represents trust and the bridge, terracotta represents human warmth, and warm neutrals, gold, and restrained green support the sport, culture, and peace-building story.

## Delivery rules

- Complete one phase before starting the next.
- Each phase must have migrations, focused feature tests, and a clean `php artisan test` run before it is accepted.
- Public responses expose published/active content only. Admin responses must not leak passwords, tokens, or integration secrets.
- Media stores an object-storage key or URL only; Render's local filesystem is never a source of truth.
- Content publication is permission-controlled and auditable.

## Phase 1: API authentication and role foundation

**Build**

- Install and configure Laravel Sanctum for bearer-token staff authentication.
- Install `spatie/laravel-permission`.
- Add `system-owner`, `admin`, and `publisher` roles and the permissions defined in the project brief.
- Add environment-backed first-owner seeding; never hardcode an owner password.
- Add rate-limited login, token revocation, JSON error responses, and protected `/api/v1/admin` routing.
- Add the initial audit-log migration and model contract.

**Acceptance tests**

- Valid credentials return a bearer token; invalid credentials return `422` or `401` without revealing which credential failed.
- Login is throttled after repeated failures.
- A revoked token cannot access an admin endpoint.
- Each role can access only its permitted route group.
- The owner seeder is idempotent and reads credentials from environment variables.

**Exit check**

```powershell
php artisan migrate:fresh --env=testing
php artisan test --filter=Authentication
php artisan test
```

## Phase 2: Core content and media

**Build**

- Add Hub, Program, Story, Event, Partner, NewsPost, and polymorphic MediaAsset models.
- Add factories, migrations, policies, form requests, and public/admin API resources.
- Use content status values `draft`, `pending_review`, `published`, and `archived` for Program, Event, Story, and NewsPost.
- Keep Hub and Program relationships explicit, with nullable `hub_id` for national programs.

**Acceptance tests**

- Public indexes are paginated and filter correctly by hub/category.
- Public show endpoints resolve by slug and exclude unpublished content.
- Admin validation rejects invalid relationships, enums, dates, and media URLs.
- Publisher permission is required to transition content to `published`.
- Media assets can attach to every supported parent without local file paths.

**Exit check**

```powershell
php artisan migrate:fresh --env=testing
php artisan test --filter=Content
php artisan test
```

## Phase 3: Public interactions and operational inbox

**Build**

- Add VolunteerApplication, PartnershipInquiry, NewsletterSubscriber, and ContactMessage.
- Add validated, rate-limited public POST endpoints.
- Add admin inbox endpoints with status transitions, pagination, and authorization.
- Add duplicate-safe newsletter subscription behavior and notification/job boundaries where required.

**Acceptance tests**

- Valid submissions return the documented JSON shape.
- Invalid and oversized submissions return consistent validation errors.
- Public endpoints are throttled.
- Duplicate newsletter emails do not create duplicate subscribers.
- Publisher has read-only inbox access; Admin can manage inbox records.

**Exit check**

```powershell
php artisan migrate:fresh --env=testing
php artisan test --filter=Interaction
php artisan test
```

## Phase 4: Review, publishing, and audit workflow

**Build**

- Add policies and permission middleware to every admin resource.
- Enforce draft -> pending review -> published transitions server-side.
- Allow unpublish/archive actions only to Publisher or System Owner.
- Record create, update, delete, and publish actions in `audit_logs`.
- Add owner-only user management, hub/partner deletion, and protected settings metadata.

**Acceptance tests**

- Admin cannot publish, delete hubs, delete partners, or manage users.
- Publisher can review/publish content but cannot manage users, settings, hubs, partners, or inbox records.
- Owner can perform all permitted actions and cannot be deleted by an Admin.
- Audit entries contain actor, action, subject, and JSON changes.
- Unauthorized requests consistently return `403`.

**Exit check**

```powershell
php artisan migrate:fresh --env=testing
php artisan test --filter=Authorization
php artisan test
```

## Phase 5: Deployment, storage, and hardening

**Build**

- Configure CORS for the Vercel frontend and localhost development origins only.
- Configure Aiven database SSL through environment variables.
- Configure S3-compatible object storage and verify uploads never depend on Render disk.
- Add Render build/start configuration, health checks, safe migration execution, queue/cache settings, and production logging.
- Add API documentation, seed data for Kiyovu, Huye, International Alert, Adidas Foundation, and sample programs.

**Acceptance tests**

- Production configuration fails fast when required secrets or storage settings are missing.
- CORS accepts configured origins and rejects unknown origins.
- A storage integration test confirms generated URLs use object storage.
- Deployment health check returns JSON and migrations run exactly once per release.
- Seeders are idempotent and do not overwrite editorial content.

**Exit check**

```powershell
php artisan config:cache
php artisan route:list --path=api/v1
php artisan test
```

## Current blocker

The WAMP PHP executable is available at `C:\wamp64\bin\php\php8.3.28\php.exe`, but it is not on the shell `PATH`. Use that executable directly (or add it to `PATH`), then run:

```powershell
composer install
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

After those commands succeed, implement and test Phase 1 before touching Phase 2.

## Production environment contract

Set `APP_KEY`, `APP_URL`, `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, and `DB_SSLMODE=require` for Aiven. Set `CORS_ALLOWED_ORIGINS` to a comma-separated list containing only the deployed Vercel origin and approved localhost development origins. Set `FILESYSTEM_DISK=s3` plus `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, and `AWS_ENDPOINT` for R2/Spaces-compatible object storage.

The Render service uses `render.yaml`, runs migrations before starting, binds Laravel to `0.0.0.0:$PORT`, and checks `/api/v1/health`. For zero-downtime production releases, run migrations as a one-off release command or deployment pre-hook rather than concurrently from multiple web instances.