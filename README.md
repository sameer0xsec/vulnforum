# VulnForum — Vulnerable Classic LAMP-Stack Forum Lab

[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker&logoColor=white)](https://www.docker.com/)
[![PHP](https://img.shields.io/badge/Backend-PHP%208.2-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Apache](https://img.shields.io/badge/Web%20Server-Apache%202.4-D22128?logo=apache&logoColor=white)](https://httpd.apache.org/)
[![MySQL](https://img.shields.io/badge/Database-MySQL%208.0-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Lab](https://img.shields.io/badge/Security-Lab%20Only-red.svg)](#disclaimer)

**VulnForum** is an intentionally vulnerable classic LAMP-stack discussion forum application built without frameworks to simulate legacy enterprise web applications, insecure coding practices, and classic PHP-specific vulnerability classes.

> [!CAUTION]
> **DISCLAIMER: FOR EDUCATIONAL AND LAB USE ONLY**
> This application contains intentionally critical vulnerabilities (Direct Remote Code Execution via `eval()`, Webshell upload, PHP Object Injection, Local File Inclusion, SQL Injection, and more). **Never deploy or expose this application to the public internet or untrusted networks.** Run only within isolated local or lab environments.

---

## Architecture & Technology Stack

| Component | Technology | Container Name | Internal Port | Host Port |
| :--- | :--- | :--- | :--- | :--- |
| **Web Server / Application** | PHP 8.2 (`mod_php`) + Apache 2.4 | `vulnforum-web` | `80` | `8082` (Primary Entry Point) |
| **Database** | MySQL 8.0 | `vulnforum-db` | `3306` | `3307` |

```text
Browser / Attacker
       │
       ▼
 [ Apache / PHP 8.2 ] (Port 8082)
       ├───► /?page=pages/home (Dynamic Include Router)
       ├───► /uploads/* (Publicly Executable Uploads Directory)
       ├───► /admin/* (Administrative Interface)
       └───► /file.php (Download Endpoint)
               │
               ▼
          [ MySQL 8 ] (Port 3307)
```

---

## Quick Start

### Prerequisites
- [Docker](https://docs.docker.com/get-docker/) & [Docker Compose](https://docs.docker.com/compose/)

### Deployment
Clone the repository and launch the container cluster:

```bash
git clone git@github.com:sameer0xsec/vulnforum.git
cd vulnforum
docker compose up -d --build
```

Access the application in your browser at:
👉 **`http://localhost:8082`**

To stop and remove containers:
```bash
docker compose down -v
```

---

## Seeded Credentials

The database is pre-seeded with sample user accounts (passwords hashed using unsalted MD5):

| Username | Password | Role | Purpose |
| :--- | :--- | :--- | :--- |
| `admin` | `admin123` | `admin` | Administrator account |
| `alice` | `password123` | `user` | Forum member account |
| `bob` | `letmein` | `user` | Forum member account |

---

## Deliberate Vulnerabilities Overview

VulnForum features **19+ classic PHP security vulnerabilities** documented with inline code annotations (`// VULN:`). Full exploit walkthroughs and remediation strategies are provided in [VULNERABILITIES.md](VULNERABILITIES.md).

### 1. Remote Code Execution (RCE)
- **Direct `eval()` Injection**: Administrative template preview tool (`admin/eval.php`) executes arbitrary input via PHP `eval()`.
- **Arbitrary File Upload**: Profile avatar upload (`pages/profile.php`) permits uploading `.php` scripts directly into a web-accessible directory with Apache execution enabled.
- **PHP Object Injection**: Insecure `unserialize()` on unauthenticated "remember me" cookies (`includes/auth.php`) allowing property-oriented programming (POP) gadget chain exploitation.

### 2. File Inclusion & Traversal
- **Local File Inclusion (LFI)**: Dynamic routing via `?page=` in `index.php` allows arbitrary file inclusion.
- **PHP Stream Wrapper Exploitation**: Reading source code (such as `config.php` credentials) using `php://filter/convert.base64-encode/resource=...`.
- **Directory Traversal**: Arbitrary file read in `file.php?name=../../../../etc/passwd`.

### 3. PHP-Specific Logic & Authentication Bypasses
- **PHP Type-Juggling (`==` vs `===`)**: Loose hash comparison in `legacy_password_ok()` permits authentication bypass using MD5 "magic hashes" (`0e...`).
- **Session Fixation**: Insecure session handling without ID regeneration upon authentication (`session.use_strict_mode = 0`).
- **Missing CSRF Protection**: All state-modifying actions lack anti-CSRF tokens.

### 4. Injection Flaws
- **SQL Injection (Login Bypass)**: Unescaped string concatenation in `includes/auth.php`.
- **SQL Injection (UNION Exfiltration)**: Forum search and thread views allow dumping full database tables.
- **Cross-Site Scripting (Stored & Reflected)**: Unescaped thread posts, replies, and search queries vulnerable to stored and reflected XSS.

### 5. Information Disclosure & Environment Misconfiguration
- **Directory Indexing**: Apache `Options Indexes` exposes all directory listings (including `/uploads/` and `/includes/`).
- **Server Status Leak**: Publicly exposed `/server-status` endpoint.
- **Verbose Error Disclosure**: `display_errors = On` leaks database structure, line numbers, and full stack traces.
- **Fingerprinting**: `expose_php = On` and `ServerTokens Full` disclose precise PHP and Apache versions.

---

## Example Attack Chain

1. **Source Code Disclosure (LFI)**: Use `php://filter` on `http://localhost:8082/?page=php://filter/convert.base64-encode/resource=config` to extract backend database credentials and secret keys.
2. **Authentication Bypass**: Authenticate as `admin` using SQL Injection (`admin' -- `) on the login page or manipulate the serialized "remember me" cookie.
3. **Privilege Escalation & RCE**:
   - **Path A**: Navigate to the admin template tool (`admin/eval.php`) and execute system commands (`system('id');`).
   - **Path B**: Upload a PHP webshell via the avatar upload feature and execute commands directly via HTTP request (`/uploads/shell.php?c=id`).

---

## Practice Workflow

1. **Reconnaissance**: Map all application pages and inspect HTTP responses for server signatures and disclosed source files.
2. **Exploit Verification**: Execute the exploit scenarios detailed in [VULNERABILITIES.md](VULNERABILITIES.md).
3. **Defensive Hardening**: Apply input validation, prepared statements, strict typing (`===`), disabled PHP execution in upload folders, and hardened server configurations.

---

## License & Legal Notice

This project is strictly for defensive security education, ethical hacking laboratories, and authorized research. Misuse of these tools and techniques is strictly prohibited.
