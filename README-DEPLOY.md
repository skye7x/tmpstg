# tmpStg: deploy on Hostido (cPanel)

Target: `https://YOURDOMAIN/tmpStg/`  ->  `/public_html/tmpStg`

## What is in this package

```
upload/                      <- upload the CONTENTS of this folder to your FTP root
├── tmpstg_private/          -> /tmpstg_private        (next to public_html, NOT inside it)
│   ├── config.php           <- DB credentials (edit this!)
│   ├── bootstrap.php connect.php storage.php cleanup.php
│   └── media/               <- uploaded files live here
└── public_html/tmpStg/      -> /public_html/tmpStg    (the only web-visible part)
database/schema.sql          <- import in phpMyAdmin (do NOT upload)
.gitignore                   <- for your git repo (do NOT upload)
```

Your FTP root should end up looking like: `awstats  logs  public_ftp  public_html  tmpstg_private`.

## Steps

1. **Database** (cPanel -> MySQL Databases)
   - Create a database and a user (cPanel adds your account prefix, e.g. `abcd1234_tmpstg`).
   - Use a NEW long random password (not the old one from the repo).
   - Give the user only **SELECT, INSERT, UPDATE, DELETE** (untick "ALL PRIVILEGES").
   - Leave "Remote MySQL" off.
2. **Schema** (cPanel -> phpMyAdmin) -> click your database -> Import -> `database/schema.sql`.
   `schema.sql` was reconstructed from the code. If your XAMPP database has extra columns,
   export its structure ("Structure only") and import that instead.
3. **Credentials**: edit `upload/tmpstg_private/config.php` and fill in the real user, password and DB name
   (host stays `localhost`).
4. **Upload** with SFTP/FTPS (not plain FTP): upload the *contents* of `upload/` to the FTP root.
5. **Permissions** (FileZilla -> right click -> File permissions):
   `tmpstg_private` = 750, `tmpstg_private/media` = 750, `tmpstg_private/config.php` = 600.
6. **PHP** (cPanel -> MultiPHP Manager): pick PHP 8.2 or newer for the domain.
   Make sure the `mysqli` and **`mbstring`** extensions are enabled (Select PHP Version / extensions).
7. **PHP limits**: `.user.ini` in `tmpStg/` sets 5 MB uploads. If uploads between 2 and 5 MB fail,
   the panel ignored it: set `upload_max_filesize=5M`, `post_max_size=6M`, `max_file_uploads=20`
   in cPanel -> MultiPHP INI Editor.
8. **HTTPS**: make sure AutoSSL issued a valid certificate for the domain BEFORE the first visit.
   `.htaccess` redirects HTTP to HTTPS. (Cloudflare "Flexible SSL" is handled, but prefer "Full".)
9. **Cron** (cPanel -> Cron Jobs), every minute (or every 5 minutes if the host limits it):
   ```
   * * * * * /usr/local/bin/php /home/YOURCPANELUSER/tmpstg_private/cleanup.php >/dev/null 2>&1
   ```
   The php path can differ (check the hint in the cron page). The app also cleans up on page loads,
   so the cron is a safety net, not a hard requirement.

## Test after deploy (all must pass)

- `https://YOURDOMAIN/tmpStg/` loads, with icons showing (Font Awesome is self-hosted).
- `http://YOURDOMAIN/tmpStg/` redirects to https.
- `https://YOURDOMAIN/tmpStg/.user.ini` -> 403/404 (also `.htaccess`).
- `https://YOURDOMAIN/tmpStg/connect.php` -> 404 (the file is not in public_html).
- Register -> log in -> upload a ~4 MB file -> download it -> it disappears after 5 minutes.
- Log in with a wrong password 5 times -> "Too many attempts".

## What changed vs. the repo

- Shared PHP files and `media/` moved out of the web root; includes use `__DIR__`-based paths.
- DB password moved to `config.php` (git-ignored).
- **Bug fixed:** `bootstrap.php` began with a blank line before `<?php`. It works on XAMPP (output buffering
  is on) but on hosts with buffering off it causes "headers already sent" and breaks sessions/redirects.
- Font Awesome 7.3.1 self-hosted (solid set only); CSP no longer allows any external host; added
  `form-action`, `base-uri`, `Permissions-Policy`.
- Sessions: strict mode, cookies only. HTTPS detection also honours `X-Forwarded-Proto`.
- Login limits: 5 per email+IP, 30 per email (distributed), 20 per IP per 15 min. A stranger can no longer
  lock someone else's account from his own IP.
- Registration limit: 5 attempts per IP per hour (new table `register_attempts`).
- Old rows in `login_attempts` / `register_attempts` are purged (cron + randomly on requests).
- `GET_LOCK` name is prefixed with the DB name (lock names are server-wide on shared MySQL).
- Added `.htaccess`, `.user.ini`, `schema.sql`, `.gitignore`.
- Not shipped: `old!index.html`, `docs/`, `README.md`, `.git`.

## Still open (not done on purpose)

- No CAPTCHA and no e-mail verification (registration is limited per IP only). Cloudflare Turnstile is free.
- "Recover" (password reset) is still a dead link; no 2FA.
- bcrypt only uses the first 72 bytes of a password (the form allows 128).
- Hostido's terms may restrict "file hosting / sharing" services. Check before launch.
- The old DB password is still in the public repo's git history. Never reuse it; make the repo private.
