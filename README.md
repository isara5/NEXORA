# NEXORA — Digital Solutions

Premium, responsive NEXORA agency website for standard PHP 8+ / MySQL shared hosting. The public site uses PHP-rendered company settings and vanilla JavaScript modules; Three.js and GSAP are loaded from CDNs with reduced-motion and mobile safeguards. No Node.js runtime is required in production.

## Deployment

1. Upload this project to an Apache/PHP 8+ host with PDO MySQL, Fileinfo, and mbstring enabled. Configure environment variables `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_URL` (canonical HTTPS URL), and `APP_ENV=production` in the hosting control panel. Keep credentials outside the public web root where possible.
2. Create a UTF-8 MySQL database and restricted database user. Import [database/schema.sql](database/schema.sql) using the host's database tool.
3. Make `uploads/projects` and `uploads/gallery` writable by PHP (the application creates them on first upload). Keep uploads non-executable; [uploads/.htaccess](uploads/.htaccess) blocks script extensions.
4. Set `NEXORA_ADMIN_EMAIL` and a unique `NEXORA_ADMIN_PASSWORD` (14+ characters) in a **CLI-only** environment and run `php database/create-admin.php`. Never commit the secret or create a default admin password. Remove the environment password after setup.
5. Visit `/admin/` to sign in, update site settings, publish services and add actual portfolio projects. No fabricated portfolio entries or performance numbers are preloaded.
6. Use HTTPS in production. Confirm `.htaccess` support or apply equivalent access-denial, security-header, and upload-execution protections in the host configuration.

## Structure

- [index.php](index.php) — homepage, SEO metadata and contact form
- [css](css) and [js](js) — responsive design, GSAP/ScrollTrigger, Three.js ambient backdrop, assistant and projects
- [config/config.php](config/config.php) — centralized configuration, secure session, PDO and security helpers
- [api](api) — public project, service, settings and contact APIs
- [admin](admin) — authenticated content management
- [database/schema.sql](database/schema.sql) — MySQL tables, indexes, foreign keys and editable initial settings
- [uploads](uploads) — generated-name project cover/gallery uploads

## Security notes

- PDO prepared statements, CSRF tokens, server-side validation, session ID regeneration, secure cookie flags, password hashing/verification, login/contact IP throttles, output escaping and MIME/extension/size/image upload checks are included.
- Admin accounts are provisioned only through the CLI script. Admin login has no default credentials.
- Use HTTPS and set database credentials as host environment variables. Back up the database and uploads. Keep PHP, OS and hosting dependencies patched.
- Contact submissions are retained in `contact_messages`; define a retention policy and restrict database access to authorized staff.

## Brand asset note

No uploaded logo image was present in the project workspace when generated. [assets/images/nexora-mark.svg](assets/images/nexora-mark.svg) is an original NEXORA-styled temporary brand mark. Replace it with the official logo when supplied, preserving its aspect ratio.