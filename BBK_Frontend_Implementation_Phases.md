# BBK Frontend — Phased Implementation Plan

Mirrors the backend rhythm: implement one slice, validate it narrowly (build + lint + tests),
then run the full check before moving to the next phase. Built against the completed Laravel
API (Render), using the "Horizon Blue" design system already defined.

Give Copilot this whole file as context, then work phase by phase — pause after each phase's
validation step before starting the next.

---

## Phase 1: Project Setup & Design System (1/5)

**Build:**
- Next.js (App Router) + TypeScript + Tailwind CSS project
- `tailwind.config.ts` wired with the Horizon Blue tokens (brand, brand-dark, accent,
  surface-tint, surface-sand, ink, success, muted)
- Space Grotesk (headings) and Inter (body) loaded via `next/font/google`; Fraunces loaded
  for testimonial pull-quotes
- `.env.local` + `.env.example` with `NEXT_PUBLIC_API_BASE_URL`
- `lib/api.ts`: typed API client with a base `fetchApi()` helper (handles base URL, JSON
  parsing, and returns `{ data, error }` rather than throwing), plus one typed function per
  public GET resource (hubs, programs, stories, events, partners, newsPosts) and one per
  POST interaction endpoint
- `types/` folder: TypeScript interfaces for Hub, Program, Story, Event, Partner, NewsPost,
  and the four interaction payloads, matching the backend's actual field names exactly —
  confirm field names against the API rather than assuming
- ESLint + Prettier config

**Validate before moving on:**
- `npm run build` succeeds with no type errors
- `npm run lint` clean
- A throwaway test page that calls `getHubs()` and renders raw JSON, to confirm the API
  client actually reaches the live Render backend and CORS is happy — delete this page
  once confirmed

---

## Phase 2: Layout, Navigation & Home Page (2/5)

**Build:**
- Root layout: header (logo, nav, mobile menu below `md`), footer (partner strip,
  newsletter form, contact info, socials) — brand-dark background on footer
- Home page: hero (brand gradient/duotone), three-pillar section (Sport / Culture &
  Entertainment / Peace-Building) on surface-tint, hub teaser cards (Kiyovu, Huye),
  featured stories carousel with Fraunces pull-quotes, partner logo strip, newsletter
  signup wired to `POST /interactions/newsletter-subscribers`

**Validate before moving on:**
- `npm run build` + `npm run lint` clean
- Component/unit tests (Jest + React Testing Library) for: header nav renders all links,
  mobile menu toggles, newsletter form shows validation error on invalid email and success
  state on valid submit (mock the API call)
- Manual check: Lighthouse or `next dev` visual pass on mobile width (this is where a
  blue-heavy design most often breaks contrast — check text-on-brand and text-on-accent
  contrast explicitly)

---

## Phase 3: Public Content Pages (3/5)

**Build, one resource type at a time, in this order — do not start the next until the
current one's tests pass:**
1. Hubs: `/hubs`, `/hubs/[slug]` (404 on missing/inactive)
2. Programs: `/programs` (category + hub filter via real query params, not client-only
   filtering), `/programs/[slug]`
3. Events: `/events` (upcoming-first), `/events/[slug]`
4. Stories: `/stories`, `/stories/[slug]` with Fraunces pull-quote treatment
5. Partners: `/partners`, grouped by `partner_type`
6. News: `/news`, `/news/[slug]`

**Validate after each resource, then again after all six:**
- Type-check + lint clean per slice
- Test: page renders a loading state, an empty state (API returns zero items), and a
  populated state, for each list page
- Test: detail pages 404 gracefully on an unknown slug rather than crashing
- Full check: `npm run build` (catches any static-generation errors across all new
  routes at once)

---

## Phase 4: Interaction Forms, SEO & Accessibility (4/5)

**Build:**
- `/get-involved`: Volunteer Application, Partnership Inquiry, and newsletter forms,
  client-side validation matching backend rules, loading/success/error states, honest
  messaging for the 10-per-minute rate limit (don't let a throttled request look like a
  silent failure — surface a clear "please wait a moment and try again" message)
- `/contact`: contact form to `POST /interactions/contact-messages`
- `generateMetadata` per route (title, description, OG image from each resource's cover
  image where present), `sitemap.xml`, `robots.txt`
- Accessibility pass: form labels, focus states in brand/accent colors, alt text on all
  images, keyboard-navigable nav and carousel

**Validate before moving on:**
- Tests: each form rejects invalid input client-side, submits correct payload shape on
  valid input (mock the POST), and surfaces the rate-limit message on a 429 mock response
- Automated a11y check (e.g. `eslint-plugin-jsx-a11y` + a manual keyboard-only pass)
- `npm run build` clean

---

## Phase 5: Performance, Error Handling & Deployment (5/5)

**Build:**
- Global error boundary + a friendly 500/offline state if the Render API is unreachable
- Image optimization via `next/image` for all cover images/media assets
- Optional: a small footer/staging-only status indicator pinging `GET /api/v1/health`
- `vercel.json` if any redirects/headers are needed; document required Vercel env vars
  (`NEXT_PUBLIC_API_BASE_URL` pointing at the Render backend)
- Confirm CORS on the backend already allow-lists the Vercel production + preview domains
  (preview deployments get random subdomains — flag this as a real gap if the backend
  only allow-lists one fixed origin)

**Validate — final gate before calling the frontend done:**
- Full `npm run build` + `npm run lint` + full test suite green
- Manual pass against the live Render API in a Vercel preview deployment, not just
  localhost — this is where the CORS preview-domain gap above would actually surface
- Lighthouse pass (performance, accessibility, SEO) on Home, a Program detail page, and
  the Get Involved page

---

## One Cross-Cutting Flag

Vercel preview deployments use a new random subdomain per PR/branch. If the backend's CORS
config only allow-lists one fixed production origin, every preview deployment will silently
fail its API calls. Worth deciding now: either allow-list a wildcard pattern for your Vercel
preview domains on the backend, or plan to test previews against a local/staging API URL
instead of the live Render one.
