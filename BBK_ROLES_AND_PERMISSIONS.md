# BBK Staff Roles

The API uses bearer tokens with three staff roles. Every staff role receives the full capability set so the frontend can expose the complete admin workspace to any authenticated staff user. Permissions are returned by `GET /api/v1/admin/auth/me`.

## System Owner

The technical and organizational owner. Has every permission.

- Manage users, roles, system settings, hubs, partners, programs, events, stories, news, media, and inbox records.
- Review, publish, archive, and restore content.
- View audit logs.
- Only another System Owner may remove a System Owner account.

## Admin and Publisher

These operational roles have the same API capabilities as the System Owner. They can manage all content, media, inbox records, users, settings, publication workflow, and audit history through the frontend.

## Content state rules

1. Any authenticated staff role may create or edit content.
2. Any authenticated staff role may move content to `pending_review`.
3. Any authenticated staff role may publish content that is `pending_review`.
4. Any authenticated staff role may move published content to `archived`.

The server enforces these transitions; the frontend must not rely on hiding buttons as its security boundary.