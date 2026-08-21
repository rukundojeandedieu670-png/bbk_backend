# Bridging Borders Kigali (BBK) — Website Rebuild
## Copilot Prompt Pack (Backend First) + Design Direction

---

## 1. The Pivot, in One Paragraph

BBK is repositioning from a Kigali-only migrant-storytelling project into a **national Sport, Culture & Entertainment for Peace-Building initiative**, developed as part of the Adidas Foundation "Moving for Change" Sport-for-Development (S4D) proposal alongside International Alert Rwanda. Instead of a single-city focus, the project now runs through **two anchor hubs — Kiyovu and Huye** — using football, athletics, music, and cultural events as tools to rebuild trust between host communities, migrants, and refugees, with girls' and women's participation as a core thread. The website needs to read as a national peace-building organization with local roots, not a neighborhood project.

---

## 2. Architecture (Laravel + Vercel + Render + Aiven)

Laravel cannot be deployed to Vercel directly — Vercel has no persistent PHP runtime. The working split:

| Layer | Where | What |
|---|---|---|
| **Backend** | Render (Web Service, Docker or native PHP) | Laravel 11 REST API, Sanctum auth, no Blade views — JSON only |
| **Database** | Aiven (MySQL or PostgreSQL) | Connected via `DB_URL`/env vars, SSL required |
| **Media storage** | Aiven-compatible object storage or a S3-style bucket (Cloudflare R2 / DigitalOcean Spaces) | Render's disk is ephemeral — never store uploaded images/video on the Render filesystem |
| **Frontend** | Vercel | Next.js (or Nuxt) SPA/SSR app, fetches from the Laravel API over HTTPS, handles all rendering/SEO |
| **Queue/cache (optional)** | Render or Aiven Redis | For email sending, image processing jobs |

Your Laravel project becomes a **pure API**: no `resources/views` for the public site, CORS configured for your Vercel domain, Sanctum for stateless token auth (admin dashboard + any partner/staff logins), and public read-only endpoints for everything the frontend needs to render.

---

## 3. Color & Type Direction (Deliberately Not the Logo)

The logo's navy/terracotta bridge-and-handshake mark stays as your emblem, but the *site* should feel like movement, sport, and warmth — not a corporate skyline. Suggested direction:

**Palette — "Amber Track" (energetic, pan-African, peace-building warmth without clichés):**
- **Ember Orange** `#E8571F` — primary CTA, energy, sport
- **Deep Teal** `#0B3D3A` — grounding, trust, contrast to orange (not navy, so it doesn't compete with the logo)
- **Sunlit Gold** `#F2A93B` — highlights, badges, achievement/medal feel
- **Warm Sand** `#F7EFE4` — background, softness, culture/textile reference
- **Charcoal Ink** `#1E1E1E` — body text
- Optional accent: **Millet Green** `#5B7B4B` for tags/categories (culture vs. sport vs. peace-building)

**Typography:**
- **Headings:** *Clash Display* or *Space Grotesk* — bold, geometric, contemporary African-design feel, good for big impact statements
- **Body:** *Inter* or *Work Sans* — clean, highly legible, works well for bilingual (English/Kinyarwanda/French) content
- **Optional accent font** for quotes/stories: *Fraunces* (a warm serif) to set personal testimonies apart from institutional copy

This gives you orange/teal/gold energy instead of the logo's navy/red, while still feeling like it belongs to the same organization.

---

## 4. Site Content Direction (No Timeline Section)

Per your instruction, leave the month-by-month timeline out of the public site entirely (keep it internal/for the funder application only). Suggested public structure instead:

1. **Home** — mission statement, hub map (Kiyovu + Huye), impact numbers, CTA to get involved
2. **Our Approach** — Sport for Peace, Culture & Entertainment, Digital Storytelling (folded in as a supporting pillar, not the headline anymore)
3. **Hubs** — Kiyovu page, Huye page (each with local partners, activities, photos)
4. **Programs** — e.g., football-for-peace leagues, cultural exchange events, entertainment nights (concerts/film screenings), girls' sport initiatives
5. **Partners** — International Alert, Adidas Foundation, local partners
6. **Stories/Impact** — testimonial and media gallery (photos/video)
7. **News/Blog**
8. **Get Involved** — volunteer, donate, partner-with-us forms
9. **Contact**

---

## 5. Roles & Permissions Design

Three roles, each with a distinct purpose. Design principle: **System Owner controls who has access; Admin controls what content structure exists; Publisher controls what goes live.** No role should be able to accidentally lock everyone else out or publish something without oversight it isn't meant to have.

### System Owner
The technical/organizational super-user — likely you, or whoever holds ultimate accountability for BBK's platform.
- Full access to everything Admin and Publisher can do, plus:
- Create, edit, deactivate, and delete **Admin** and **Publisher** accounts (user management)
- Assign/change roles on any account
- View system-level settings: API keys, integrations, environment/config-facing data exposed through the admin API (e.g. active storage disk, mail settings)
- View audit logs of who changed/published what
- Manage Partners, Hubs (create new hubs beyond Kiyovu/Huye if BBK expands), and organizational-level records
- Only System Owner can delete a Hub or a Partner (high-blast-radius actions)
- Cannot be deleted by an Admin — only by another System Owner, or self, with a confirmation step

### Admin
Day-to-day operational manager — runs programs, manages the content structure, but isn't necessarily the final word on public content or on who gets system access.
- Full CRUD on: Programs, Events, Stories (drafts and edits), News Posts (drafts and edits), Media assets
- Full CRUD on: Volunteer Applications, Partnership Inquiries, Contact Messages, Newsletter Subscribers (review/respond/manage status)
- Can create **Publisher** accounts, but cannot create/edit/delete Admin or System Owner accounts
- Cannot change site-level/system settings
- Cannot delete Hubs or Partners (can propose/edit content under them, not remove the record itself)
- Can set content to "pending review" but publishing to "live" requires Publisher (or System Owner) sign-off — this keeps a two-person check between drafting and going public

### Publisher
Content/communications lead — the final gate before anything is public-facing.
- Can view all content (Programs, Events, Stories, News Posts) in any status
- Can change a content item's status from **draft → pending review → published** (and unpublish/archive)
- Can edit copy on any content item prior to publishing (typo/tone fixes) but cannot create new Programs, Events, or Hubs from scratch — that's Admin's job, keeping structural changes separate from the publishing decision
- Full control over the public-facing newsletter send and social/media queue if you build that later
- No access to user management, system settings, Hub/Partner records, or the interaction inboxes (Volunteer/Partnership/Contact) beyond read-only visibility if needed for context

### Summary Table

| Capability | System Owner | Admin | Publisher |
|---|:---:|:---:|:---:|
| Manage user accounts & roles | ✅ full | Publisher accounts only | ❌ |
| System/integration settings | ✅ | ❌ | ❌ |
| Create/delete Hubs & Partners | ✅ | edit only | ❌ |
| Create/edit Programs, Events, Stories, News (draft) | ✅ | ✅ | copy edits only |
| Move content draft → pending → published | ✅ | request review only | ✅ |
| Manage Volunteer/Partnership/Contact inbox | ✅ | ✅ | read-only |
| View audit log | ✅ | ❌ | ❌ |

### Implementation approach
Use **`spatie/laravel-permission`** rather than hand-rolling a roles table — it gives you roles + granular permissions, middleware (`->middleware('permission:publish-content')`), and Blade/API-friendly checks (`$user->can('...')`), and it's the de facto standard for this in Laravel so Copilot will have strong training signal on it.

Suggested permission names (fine-grained, then grouped into the three roles above):
`manage-users`, `manage-system-settings`, `manage-hubs`, `manage-partners`, `manage-programs`, `manage-events`, `manage-stories`, `manage-news`, `manage-media`, `manage-inbox` (volunteer/partnership/contact/newsletter), `review-content`, `publish-content`, `view-audit-log`.

---

## 6. The Copilot Prompt (Backend)

Paste the block below into GitHub Copilot Chat inside your already-scaffolded Laravel project. It's written so Copilot has full context and can generate migrations, models, controllers, and routes in one coherent pass.

```
CONTEXT:
I'm building the backend for "Bridging Borders Kigali" (BBK), a Rwandan peace-building
organization. The project has pivoted from a Kigali-only migrant storytelling initiative
into a national Sport, Culture & Entertainment for Peace-Building program, delivered
through two hubs: Kiyovu and Huye. It supports an Adidas Foundation "Moving for Change"
Sport-for-Development proposal in partnership with International Alert Rwanda.

This Laravel project is a PURE JSON API (no Blade views for the public site). It will be
deployed to Render. The database is PostgreSQL/MySQL hosted on Aiven, connected via env
vars with SSL enabled. The frontend is a separate Next.js app deployed on Vercel and will
consume this API over HTTPS — so CORS and stateless auth matter a lot.

GOAL:
Scaffold the backend for me, in this order, explaining each step:

1. AUTH & ROLES
   - Install and configure Laravel Sanctum for stateless API token auth (staff login
     only — the public site has no user accounts, only internal team members log in).
   - Install spatie/laravel-permission and set up three roles: "system-owner", "admin",
     "publisher", with these permissions:
       manage-users, manage-system-settings, manage-hubs, manage-partners,
       manage-programs, manage-events, manage-stories, manage-news, manage-media,
       manage-inbox, review-content, publish-content, view-audit-log
     Assign permissions to roles as follows:
       - system-owner: ALL permissions
       - admin: manage-hubs (edit only, not delete), manage-partners (edit only, not
         delete), manage-programs, manage-events, manage-stories, manage-news,
         manage-media, manage-inbox, review-content (can request review, cannot
         publish)
       - publisher: review-content, publish-content, plus read-only access to
         manage-inbox
   - Seed one System Owner user (from env vars, not hardcoded) on first migrate.
   - Add route middleware so /api/v1/admin/users/* requires manage-users,
     /api/v1/admin/settings/* requires manage-system-settings, and every
     admin content route checks the relevant manage-* or publish-content permission.
   - A content model (Program, Event, Story, NewsPost) should have a `status` enum:
     draft, pending_review, published, archived — with a policy so only users with
     publish-content can move something into "published", while manage-* permission
     holders can move draft <-> pending_review.
   - Add a simple audit log (table: audit_logs — user_id, action, subject_type,
     subject_id, changes json, created_at) that records every create/update/delete/
     publish action, visible only to view-audit-log.

2. CORE CONTENT MODELS (with migrations, models, factories, and API resource classes)
   - Hub (Kiyovu, Huye, and future hubs): name, slug, district, description, cover_image,
     lat/lng, is_active
   - Program: title, slug, hub_id (nullable = national), category (enum: sport, culture,
     entertainment, peace_building, storytelling), summary, body (rich text), cover_image,
     is_featured
   - Story (impact/testimonial stories): title, slug, hub_id, program_id (nullable),
     author_name, body, media (one-to-many images/video), is_published, published_at
   - Event: title, slug, hub_id, program_id (nullable), event_type (match, concert,
     screening, workshop, exhibition), location, starts_at, ends_at, description,
     cover_image, is_public
   - Partner: name, logo, website_url, partner_type (funder, implementing_partner,
     local_partner), description
   - NewsPost (blog/news): title, slug, body, cover_image, is_published, published_at
   - MediaAsset: polymorphic (imageable/videoable) table so Program, Story, Event, Hub,
     NewsPost can all attach multiple images/videos cleanly — store only the object-storage
     URL/key, never a local file path, since Render's disk is ephemeral.

3. PUBLIC-FACING INTERACTION MODELS
   - VolunteerApplication: name, email, phone, hub_of_interest, message, status
   - PartnershipInquiry: organization_name, contact_name, email, message, status
   - NewsletterSubscriber: email, subscribed_at, is_confirmed
   - ContactMessage: name, email, subject, message

4. API ROUTES (routes/api.php), versioned under /api/v1
   - Public GET endpoints (no auth) for: hubs, programs, stories, events, partners,
     news posts — each with index (paginated, filterable by hub/category) and show
     (by slug)
   - Public POST endpoints (no auth, but rate-limited + validated) for: volunteer
     applications, partnership inquiries, newsletter signup, contact messages
   - Auth-protected (Sanctum) CRUD endpoints under /api/v1/admin/* for all content
     models above, so a future admin dashboard can manage everything

5. VALIDATION & FORM REQUESTS
   - Create FormRequest classes for every POST/PUT endpoint with clear validation rules
     and custom error messages.

6. API RESOURCES
   - Create API Resource classes for every model so responses are consistent, camelCase
     where appropriate, and never leak internal fields (timestamps formatting, hiding
     admin-only fields from public resources).

7. CORS & ENV CONFIG
   - Configure config/cors.php to allow only my Vercel frontend domain(s) (I'll supply
     them) plus localhost for dev, with credentials support disabled (we're using
     Bearer tokens, not cookies, since this is a fully decoupled SPA).
   - Show me the .env variables I'll need for Aiven (DB_CONNECTION, DB_HOST, DB_PORT,
     DB_DATABASE, DB_USERNAME, DB_PASSWORD, DB_SSLMODE or equivalent) and for object
     storage (FILESYSTEM_DISK, and S3-compatible keys).

8. SEEDERS
   - Seed a Kiyovu and a Huye Hub record, 2-3 sample Programs (one sport, one culture,
     one entertainment/peace-building), and International Alert + Adidas Foundation as
     Partner records, so the frontend team has real data to build against immediately.

9. DEPLOYMENT NOTES FOR RENDER
   - Give me the render.yaml or manual build/start commands (composer install,
     php artisan migrate --force, php artisan serve or php-fpm + nginx setup) needed
     to run this as a Render Web Service, plus how to run migrations safely on deploy.

Go model-by-model rather than dumping everything at once — after each model, pause and
show me the migration, model, and resource together so I can review before moving to
the next one. Start with Hub and Program.
```

---

## 7. Suggested Next Prompt (Frontend, once backend is running)

Once the API above is live on Render and returning data, the natural follow-up prompt (for a separate Next.js repo, Vercel-deployed) would cover: fetching from the versioned API, statically generating Hub/Program/Story pages, the color and font system above translated into Tailwind config, and SEO metadata per page. Happy to write that one next whenever you're ready for it.