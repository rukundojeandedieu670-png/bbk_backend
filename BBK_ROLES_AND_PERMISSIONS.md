# BBK Staff Roles

The API uses bearer tokens with three staff roles. A user should have one primary role. Permissions are returned by `GET /api/v1/admin/auth/me` so an admin frontend can show only actions the current user can perform.

## System Owner

The technical and organizational owner. Has every permission.

- Manage users, roles, system settings, hubs, partners, programs, events, stories, news, media, and inbox records.
- Review, publish, archive, and restore content.
- View audit logs.
- Only another System Owner may remove a System Owner account.

## Admin

The operational content manager.

- Manage programs, events, stories, news, media, and inbox records.
- Edit hubs and partners, but hub/partner deletion remains an owner-only action.
- Move managed content between `draft` and `pending_review`.
- Cannot publish, archive, manage users, change system settings, or view audit logs.

## Publisher

The final editorial gate.

- View inbox records read-only.
- Review content and publish or archive content that has reached `pending_review`.
- Cannot create structural records, manage media, manage users, change settings, or edit inbox records.

## Content state rules

1. Admin creates or edits content as `draft`.
2. Admin requests review by moving `draft` to `pending_review`.
3. Publisher or System Owner moves `pending_review` to `published`.
4. Publisher or System Owner may move published content to `archived`.

The server enforces these transitions; the frontend must not rely on hiding buttons as its security boundary.