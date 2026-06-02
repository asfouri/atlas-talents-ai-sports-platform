# Atlas Talents

PHP/MySQL platform for sports talent detection, role-based dashboards, messaging, and AI-assisted video analysis.

## Production Notes

- `schema.sql` is now schema-only. It does not create demo accounts.
- Optional demo seed data lives in `seed_demo.sql`.
- Demo mode is disabled by default. Enable it only for local development with `APP_ALLOW_DEMO_MODE=1`.
- Uploaded videos are stored outside the public web root in `storage/uploads/` by default and are served through authenticated requests in `media.php`.
- Public self-registration is restricted by `APP_ALLOWED_PUBLIC_REGISTRATION_ROLES` and defaults to `teacher`.

## Environment

Configure these variables in your web server or PHP environment:

```text
APP_ENV=production
APP_URL=https://your-domain.example
APP_DEBUG=0
APP_ALLOW_DEMO_MODE=0
APP_DEMO_ACCESS_SECRET=
APP_ALLOWED_PUBLIC_REGISTRATION_ROLES=teacher

DB_HOST=localhost
DB_NAME=atlas_talents
DB_USER=atlas_user
DB_PASS=change-me
DB_CHARSET=utf8mb4

OPENAI_API_KEY=
OPENAI_MODEL=gpt-4.1-mini
OPENAI_API_URL=https://api.openai.com/v1/responses
OPENAI_TIMEOUT=90
AI_ALLOW_DEMO_FALLBACK=0
```

Optional:

```text
APP_STORAGE_ROOT=/absolute/path/outside/webroot
```

## Owner Demo Access

If you want fast personal demo access on a public deployment without restoring public demo credentials:

1. Set `APP_DEMO_ACCESS_SECRET` to a strong private value.
2. Open:

```text
https://your-domain.example/pages/auth/login.php?demo_key=your-secret
```

3. The login page will unlock one-click demo session buttons for your browser session only.

This is safer than permanent public demo logins, but the secret still grants privileged demo access. Treat it like a password and rotate it if exposed.

## Setup

1. Import the schema:

```bash
mysql -u your_user -p < schema.sql
```

2. Optionally load demo data in a non-production environment:

```bash
mysql -u your_user -p atlas_talents < seed_demo.sql
```

3. Ensure PHP can write to:

- `storage/uploads/`
- `storage/private/`

4. Serve the project through Apache or another PHP-capable web server with HTTPS enabled.

## Security Defaults

- Session cookies use `HttpOnly`, `SameSite=Lax`, and `Secure` when the request is HTTPS.
- Logout requires `POST` plus CSRF validation.
- API errors are generic by default unless `APP_DEBUG=1`.
- Legacy files under `public/uploads/` are protected by `.htaccess`.

## Development

If you explicitly want demo behavior locally:

```text
APP_ENV=development
APP_ALLOW_DEMO_MODE=1
APP_ALLOWED_PUBLIC_REGISTRATION_ROLES=teacher,manager,recruiter,coach
AI_ALLOW_DEMO_FALLBACK=1
```
