**🔴 Live Demo:** https://reliefx.stratifyxglobal.com/

---

# Relief Distribution Duplicate Prevention System — Setup Guide

A full-stack project ready to run directly on XAMPP: **HTML + CSS + JavaScript + PHP + MySQL**.

## 0. Problem Definition, Stakeholders & USP (DBMS guideline — Component A)

**Problem:** Two real problems keep recurring in post-flood relief distribution in Bangladesh:
1. The same family receives the same type of relief multiple times from different NGOs, while the neighboring family gets nothing at all.
2. Sensitive information like NID is written in open registers, creating a privacy risk.

This system solves both problems at the database level (via trigger + procedure + constraint) — not just with frontend validation.

**Stakeholders / System Users:** District Relief Officer (admin), NGO operator (ngo_operator), affected families (end beneficiary — doesn't use the system directly, but is the subject of the data).

**Constraints / Complexity:** During a disaster, multiple NGOs enter data in parallel (concurrency race condition — see §7.1 Bug #2), NID cannot be stored in plain text (privacy), and spelling variations in Bengali names (e.g. "Rahim/Rahiim") can cause duplicate registrations (fuzzy matching is needed).

**USP (Unique Selling Point):**
1. NID is never stored in plain text — only a salted SHA-256 hash (`nid_hash`).
2. 7-day category-based duplicate blocking, enforced at two levels — trigger + stored procedure (defense-in-depth).
3. Custom Unicode-aware fuzzy matching for Bengali names (`mb_levenshtein()`, because PHP's built-in `levenshtein()` and MySQL's `SOUNDEX()` don't work for Bengali).
4. Population-proportional fairness dashboard, based on the `v_area_fairness` VIEW.

## 1. Setup

### Option A: Using XAMPP (Apache + MySQL)

1. Copy the entire `relief_system` folder into XAMPP's `htdocs` folder:
   - Windows: `C:\xampp\htdocs\relief_system\` (or create a directory junction: `mklink /J C:\xampp\htdocs\relief_system <project_path>`)
   - macOS: `/Applications/XAMPP/htdocs/relief_system/`
2. From the XAMPP Control Panel, start both **Apache** and **MySQL**
3. Open `http://localhost/phpmyadmin` in your browser → **Import** tab → select `database.sql` (ensure character set is set to **utf8mb4**) → click **Go**
   - Alternatively via terminal (prevents Windows ANSI/CP1252 encoding issues):
     ```bash
     mysql --default-character-set=utf8mb4 -u root -p relief_db < database.sql
     ```
   *(this creates `relief_db` and loads all tables, triggers, stored procedures, views, and initial seed data)*
4. Open `http://localhost/relief_system/install.php` in your browser — this creates the initial admin and operator accounts (run this only once)
5. Open `http://localhost/relief_system/` to access the application.

### Option B: Using PHP Built-in Development Server

1. Ensure MySQL is running (e.g. from XAMPP or standalone service).
2. Configure `.env` if your MySQL port differs from standard 3306 (e.g. `DB_HOST=127.0.0.1;port=3307`).
3. Import `database.sql` with utf8mb4 character set:
   ```bash
   mysql --default-character-set=utf8mb4 -u root -P 3307 -h 127.0.0.1 -e "source database.sql"
   ```
4. Run the installer:
   ```bash
   php install.php
   ```
5. Start the server from the project directory:
   ```bash
   php -S localhost:8000
   ```
6. Open **`http://localhost:8000`** in your browser.

> **Environment Variables (`.env`):**
> DB credentials, ports, and the NID hashing salt are read from `.env` (see §7.2).
> The repository includes `.env.example` as a template — copy it to `.env` and adjust as needed. If `.env` is absent, the system seamlessly falls back to default localhost parameters.

## 2. Login credentials

| Username | Password | Role | Description |
|---|---|---|---|
| `admin` | `Admin@123` | District Relief Officer | Full admin access (Dashboard, Audit Log, All Families) |
| `asha_operator` | `Ngo@123` | Operator for Asha Foundation | Relief entry & family registration |
| `relief_operator` | `Ngo@123` | Operator for Relief Bangladesh | Relief entry & family registration |
| `sfv_operator` | `Ngo@123` | Operator for Save the Flood Victims | Relief entry & family registration |

## 3. Complete File Structure

```
relief_system/
├── .env                              ← real environment variables & secrets (gitignored)
├── .env.example                       ← committed template for environment variables
├── .gitignore                          ← excludes sensitive and cache files from git
├── .htaccess                             ← blocks direct web access to sensitive dotfiles
├── Project_Report.docx                   ← comprehensive academic/DBMS project documentation
├── README.md                             ← complete system guide and technical documentation
├── database.sql                         ← schema + triggers + procedures + views + seed data (UTF-8)
├── queries_for_report.sql                ← analytic SQL queries for DBMS reporting
├── roles_optional.sql                    ← optional database-level user privilege grants
├── er_diagram.png                        ← high-resolution Entity-Relationship diagram
├── config.php                            ← DB PDO connection, hashNID(), mb_levenshtein(), env loader
├── install.php                           ← one-time account setup script (seeds admin & operators)
├── index.php                             ← landing page and login portal
├── logout.php                            ← terminates session and redirects to login
├── distribute.php                        ← core relief distribution workflow & live duplicate check
├── register_family.php                   ← beneficiary registration with fuzzy match & cross-check
├── family_list.php                       ← admin-only: complete registry of all enrolled families
├── dashboard.php                         ← admin-only: fairness gauge, NGO performance & live map
├── duplicate_log.php                     ← admin-only: audit log of blocked duplicates & lookup trail
├── stock.php                             ← NGO inventory tracking & restock management
├── register_ngo.php                      ← public self-registration for partner NGOs
├── verify_email.php                      ← 6-digit verification code entry for new NGOs
├── forgot_password.php                   ← automated email password reset workflow
├── profile.php                           ← NGO organization profile & custom logo upload
├── switch_lang.php                       ← language switcher (BN / EN) with session & cookie memory
├── api/                                  ← AJAX endpoints (CSRF-protected & session-verified)
│   ├── check_duplicate.php               ← live family & 7-day category duplicate check
│   ├── fuzzy_check.php                   ← Unicode-aware Levenshtein duplicate name detector
│   ├── save_family.php                   ← family registration with cross-check identity protection
│   ├── save_distribution.php             ← atomic relief distribution caller for sp_distribute_relief
│   ├── save_distribution_point.php       ← registers new distribution points associated with an upazila
│   ├── save_ngo_profile.php              ← updates NGO contact information & logo file
│   └── save_stock.php                    ← adds inventory quantity to an NGO-item pair
├── css/
│   └── style.css                         ← responsive dark/light glassmorphic UI stylesheet
├── js/
│   ├── app.js                            ← frontend logic, AJAX lookups, Leaflet map & UI animations
│   ├── auth.js                           ← authentication validations, tabs, and login helpers
│   └── theme.js                          ← dark/light mode toggle with localStorage persistence
├── img/
│   ├── logo-icon.png                     ← circular ReliefX brand emblem & favicon
│   ├── logo-full.jpeg                    ← full ReliefX emblem and typography wordmark
│   ├── dashboard-preview.png             ← fairness dashboard UI mock preview
│   └── distribute-preview.png            ← relief entry workflow UI mock preview
└── includes/
    ├── auth.php                          ← RBAC enforcement, session authentication, CSRF protection
    ├── auth_brand.php                    ← unified layout wrapper for authentication pages
    ├── env.php                           ← lightweight .env parser without external dependencies
    ├── header.php                        ← global navigation bar, language switcher & CSRF bridge
    ├── footer.php                        ← global footer & base script inclusions
    ├── icons.php                         ← lightweight SVG icon renderer (Feather icon set)
    ├── lang.php                          ← bilingual translation engine (UI + dynamic database entities)
    ├── mailer.php                        ← SMTP email helper for verification & password reset codes
    └── PHPMailer/                        ← embedded PHPMailer library
        ├── Exception.php
        ├── PHPMailer.php
        └── SMTP.php
```

## 4. What to Demonstrate in the Viva

1. **Duplicate blocking:** Log in as `asha_operator` → on distribute.php, enter NID `1985011234501`
   (Rahim Uddin, received rice 15 days ago — outside the 7-day window, so should show as eligible now) → submit with item "Blanket" (a different category)
   → should succeed. Now submit again with the same NID + same item → 🚫 blocked, and
   an entry will appear in `duplicate_log.php` (when logged in as admin).
   (NID pattern for the 20 demo families: family #N ↔ `19850112345` + N in two digits, e.g. family #10 ↔
   `1985011234510` — any of them can be used for testing, see §7.1 Bug #6.)
2. **Fuzzy matching:** On register_family.php, try registering a name close to "Rahim Uddin" like "Rahiim Uddeen"
   → a warning modal will appear.
3. **Role-based access:** Logging in as `admin` shows the Dashboard + Duplicate Log tabs and lets you browse
   the full family list; logging in as `asha_operator` hides these and locks the NGO field on distribute.php.
4. **Fairness dashboard:** Open dashboard.php as `admin` — a gauge bar shows which area received less relative
   to its population, along with NGO Performance and Weekly Trend bar charts.
5. **Identity conflict (Version 2.0 — new):** On register_family.php, try registering a new family with an NID
   that's already registered as a member of another family (in family_member) → it immediately shows an error
   "This NID is already registered under family_id #X", and the insert is blocked. This is the core bug that was fixed —
   details in §7.
6. **Distributing relief using a member's NID (Version 2.0 — new):** On distribute.php, instead of the
   family head's NID, enter a family_member's NID/birth registration number → the family is still found correctly,
   and a note appears: "ℹ️ This NID does not belong to the family head...". Previously this showed "not_found".
7. **Stock enforcement (Version 2.0 — new):** As admin, go to `stock.php` and reduce the quantity_available
   for some NGO-Item (or leave a small quantity and try to distribute more) → distribute.php shows
   "ERROR: Insufficient stock for this NGO/item" when that amount is requested, and the distribution is not inserted.
8. **CSRF protection (Version 2.0 — new):** Try changing the `CSRF_TOKEN` variable from browser DevTools and
   calling any api/*.php endpoint → you'll get a 403 with a "session expired" message, and nothing gets inserted.

## 5. Mapping to Guideline Components

| Guideline component | Where it is |
|---|---|
| Problem statement / stakeholders / USP | §0 of this file |
| ER diagram / 3NF | `er_diagram.png` (see §0.1 for how it was built) + table design in `database.sql`, mermaid ER in §7.3 |
| CREATE TABLE + constraints | `database.sql` — all PK/FK/CHECK/UNIQUE present |
| ≥20 records/table | family: 20, distribution: 28, district: 64, upazila: ~487 |
| INSERT/UPDATE/DELETE/SELECT (WHERE/GROUP BY/ORDER BY/LIMIT/HAVING)/JOIN/Aggregate (COUNT/SUM/AVG)/Subquery | used throughout the app (see the api files) + individually runnable examples in `queries_for_report.sql` |
| TRIGGER | `trg_block_duplicate`, `trg_family_no_cross_dup`, `trg_family_member_no_cross_dup` (Version 2.0) |
| PROCEDURE | `sp_distribute_relief` (Version 2.0 added transaction + row lock + stock deduction) |
| VIEW | `v_area_fairness`, `v_person_registry` (Version 2.0 — identity resolution) |
| Transaction | `START TRANSACTION`/`COMMIT`/`ROLLBACK` in `sp_distribute_relief` + PDO transactions in `api/save_family.php`, `register_ngo.php` |
| System roles/privileges (optional) | session-based role check (`includes/auth.php`) + each NGO operator restricted to their own ngo_id + CSRF token (Version 2.0); example DB-level `GRANT` statements in `roles_optional.sql` (optional, not run) |
| Investigation & Analysis (Component D) | questions D1–D3 in `queries_for_report.sql` (underserved area, category demand, severity-wise fairness) |

## 6. Key Engineering Decisions (worth writing up in the report)

- **PHP's built-in `levenshtein()` gives incorrect results on Bengali text** (it's byte-based, but Bengali characters are multi-byte UTF-8).
  So a custom `mb_levenshtein()` was written in `config.php` that compares Unicode characters instead.
- **MySQL's `SOUNDEX()` is designed for English phonetics** and doesn't give meaningful codes for Bengali names — so fuzzy matching uses
  a LIKE-based candidate filter (matching the first 2 characters) + confirmation via `mb_levenshtein()` in PHP — this is a
  good example of EP2 (performance vs. correctness trade-off, a conflicting requirement for non-Latin scripts).
- **Defense-in-depth:** the submit button is deliberately not disabled after a duplicate warning is shown — because real
  enforcement shouldn't live in client-side JS, it should live in the database's trigger and procedure. Even if the client is
  bypassed, the database will still block it.

## 7. Version 2.0 — Bug Fixes & New Features (detailed log)

This section is kept so that **we can remember for ourselves in the future why a bug happened and how it was fixed.**
Each fix has a `BUG FIX (README "Bug #X")` comment in the relevant file, so you can come back to this log while reading the code.

### 7.1 Bugs Found & Fixed

**Bug #1 — the same person could register twice (the core problem that started this whole update)**
- **Problem:** The `family` table only stores the family head's NID (`nid_hash`), while the `family_member` table stores
  the other members' NID/birth registration (`id_hash`). During registration (`api/save_family.php`), a new head's NID was
  checked only against the `family` table — not against `family_member`. As a result, someone already registered as a member
  of another family could register again as a separate family head — creating two separate relief entitlements for the same
  person. During relief distribution too (`api/check_duplicate.php`, `sp_distribute_relief`), the family was looked up only
  by the head's NID — entering a member's NID/birth registration number would show "not_found", even though that person was
  actually part of a registered family.
- **Fix:**
  1. New VIEW `v_person_registry` — unions family (head) and family_member (members) into one place,
     telling you which `family_id` an `id_hash` belongs to, and whether it's the head or a member.
  2. Two new triggers — `trg_family_no_cross_dup` (checks before inserting as head whether the NID already exists
     as a member) and `trg_family_member_no_cross_dup` (the reverse) — a hard safety net at the DB level.
  3. `api/save_family.php` now shows a friendly app-level error (including which family_id it's already under),
     so the operator understands before it even reaches the trigger. **Deliberately, no "force/override" option
     was added here** — because an identity conflict always means either a genuine duplicate or a data-entry mistake;
     the correct fix for both is to correct the earlier record, not create a new parallel identity.
  4. `api/check_duplicate.php` and `sp_distribute_relief` now resolve via `v_person_registry` — entering the head's
     or any member's NID/birth registration number correctly finds the family, and the 7-day duplicate-check
     applies to that family.
- **Test:** See README §4 items 5 and 6.

**Bug #2 — Race condition: two parallel requests could let the same family receive relief twice**
- **Problem:** In the old `sp_distribute_relief`, "SELECT COUNT(*) recent" and "INSERT INTO distribution" were two
  separate auto-committed statements with no row lock in between. If two operators submitted for the same family at
  nearly the same moment, both calls could see recent_count = 0 (because each other's INSERT wasn't committed/visible
  yet), so both would pass the duplicate-check and the family would end up receiving relief twice.
- **Fix:** The whole procedure is now wrapped in an explicit `START TRANSACTION`, and before the duplicate-check,
  `SELECT ... FROM family WHERE family_id=? FOR UPDATE` locks that family's row — so a second concurrent call for the
  same family is blocked here until the first one's COMMIT/ROLLBACK completes. The same logic applies to `ngo_stock`
  (see below), so that two parallel distributions can't push stock below zero together.
- **Test (hard to demonstrate manually, show in code review):** Show the
  `START TRANSACTION` ... `FOR UPDATE` ... `COMMIT`/`ROLLBACK` pattern inside `sp_distribute_relief` in `database.sql`.

**Bug #3 — DB password and NID salt were hardcoded directly in the code**
- **Problem:** `DB_PASS`, `NID_SALT`, etc. were written as literal strings in `config.php` — once committed to
  version control, they'd remain in history forever.
- **Fix:** `includes/env.php` (a small `.env` parser, no composer) + `.env` (the real secrets, gitignored) +
  `.env.example` (a committed template) + `.htaccess` (blocks direct browser access to dotfiles). If `.env` is
  missing, it falls back to the previous hardcoded defaults, so existing setups don't break.

**Bug #4 — the demo accounts created by `install.php` could never log in**
- **Problem:** The schema default for the `users.is_verified` column is `0`. `install.php` didn't explicitly
  set `is_verified` when creating accounts, so on a completely fresh install neither admin nor any operator could
  log in (`index.php` showed a "please verify your email first" error, even though no verification email is ever
  sent for these accounts). This bug was caught during live testing for this update.
- **Fix:** The INSERT in `install.php` now explicitly sets `is_verified = 1` — because these accounts are created
  by a trusted installer, not through `register_ngo.php`'s public self-registration flow, so email verification
  doesn't apply.

**Bug #6 — none of the 20 demo families could ever be found by NID (the most important discovery)**
- **Problem:** Caught during live testing for this update — calling `check_duplicate.php` with the documented demo
  NID from README §4 (`1985011234501`) returned "not_found", even though family_id 1 definitely exists in the
  database. The cause was traced: every `nid_hash` seeded for the 20 families in `database.sql` was actually
  **62–63 characters long, not 64** (confirmed in the DB using `CHAR_LENGTH()`) — whereas a SHA-256 hash is always
  exactly 64 hex characters. For family 1, it turned out the very last character of the correct hash was missing
  (likely a copy-paste error while pre-computing the hashes). This meant **this flagship duplicate-blocking demo
  had never worked from day one** — not a single one of the 20 could be matched with its actual NID.
- **Fix:** The correct SHA-256(NID + salt) was recomputed for the sequential demo NIDs `1985011234501` through
  `1985011234520`, and all 20 families' `nid_hash` values were replaced. Family 1's demo NID was kept unchanged
  (it was already documented in the README), and the remaining 19 were assigned new NIDs following the same
  pattern (sequential, like `phone`) — now **for any family_id, the NID can be built from the last 2 digits of
  `phone`**: family #N ↔ NID `19850112345` + N (2 digits, e.g. family #7 ↔ `1985011234507`).
- **Test:** Entering any `19850112345XX` (XX = 01–20) as the NID on distribute.php will now find the matching
  family — previously this wasn't possible at all.

**Bug #5 — expired sessions caused a 404 loop when calling `api/*.php`**
- **Problem:** `requireLogin()` sent `header('Location: index.php')` when not logged in — a *relative* path.
  This worked fine when called from a page controller (like `distribute.php`), but when called from a file like
  `api/save_family.php`, the browser resolved it as `/relief_system/api/index.php` — which doesn't exist, hence
  404. Also, sending an HTML redirect for an AJAX/fetch() call is the wrong approach in general — the frontend
  expects JSON.
- **Fix:** A new `isApiRequest()` helper in `includes/auth.php` — checks whether `/api/` appears in `SCRIPT_NAME`.
  If so, `requireLogin()`/`requireRole()` now return a clean JSON 401/403 response (with an `api_session_expired`
  key), instead of a plain HTML redirect.

### 7.2 New Features (not bugs, new additions)

- **CSRF protection** — `csrfToken()`/`requireCsrf()` in `includes/auth.php`, a session-based token. All
  state-changing APIs (`save_family`, `save_distribution`, `save_distribution_point`, `save_stock`) and the
  `register_ngo.php` form now verify the token, or return 403. Previously no form had CSRF protection.
- **NGO Stock tracking** — new table `ngo_stock` (ngo_id, item_id, quantity_available). `sp_distribute_relief`
  now deducts from stock on every successful distribution and blocks the distribution if stock is insufficient
  (previously there was no concept of stock at all — an NGO could theoretically give out an infinite quantity).
  A new `stock.php` page (admin-only) shows the current stock for every NGO-Item and lets you add to it via
  `api/save_stock.php`.
- **Dashboard charts** — `dashboard.php` now shows NGO Performance and an 8-week Weekly Trend bar chart next
  to the fairness gauge (with no external JS chart library — just CSS, so it also works in an offline viva).
- **Richer audit trail** — new columns `matched_family_id`, `matched_member_id` in the `query_log` table —
  recording which family/member each lookup matched. `duplicate_log.php` now has a new "🔍 Lookup Audit Trail"
  section showing this to the admin (previously only blocked attempts were visible, not successful lookups).

### 7.3 ER Diagram — identity- and stock-related tables

```mermaid
erDiagram
    FAMILY ||--o{ FAMILY_MEMBER : "has members"
    FAMILY ||--o{ DISTRIBUTION : "receives"
    FAMILY ||--o{ QUERY_LOG : "matched_family_id"
    FAMILY_MEMBER ||--o{ QUERY_LOG : "matched_member_id"
    NGO ||--o{ DISTRIBUTION : "gives"
    NGO ||--o{ NGO_STOCK : "stocks"
    NGO ||--o{ DUPLICATE_LOG : "attempted_ngo_id"
    RELIEF_ITEM ||--o{ NGO_STOCK : "tracked as"
    RELIEF_ITEM ||--o{ DISTRIBUTION : "is"

    FAMILY {
        int family_id PK
        char nid_hash UK "SHA-256 of HEAD's NID"
        varchar head_name
        int family_size
    }
    FAMILY_MEMBER {
        int member_id PK
        int family_id FK
        char id_hash UK "SHA-256 of NID or Birth Certificate"
        varchar member_name
        enum id_type "NID or Birth Certificate"
    }
    NGO_STOCK {
        int stock_id PK
        int ngo_id FK
        int item_id FK
        decimal quantity_available "deducted by sp_distribute_relief"
    }
    QUERY_LOG {
        int log_id PK
        int ngo_id FK
        int matched_family_id FK "nullable"
        int matched_member_id FK "nullable — set only if matched via a member"
    }
```

> **Note:** `v_person_registry` is a VIEW (a UNION of family + family_member), so it doesn't appear as a
> separate entity in this ER diagram — the flowchart below shows how it's used.

### 7.4 Identity Resolution Flow — during registration

```mermaid
flowchart TD
    A["New head/member's NID/birth registration is submitted"] --> B{"Does this id_hash\nalready exist in v_person_registry?"}
    B -- "Yes (matched)" --> C["❌ Error: already registered\nunder family_id #X (app-level check,\nsave_family.php)"]
    B -- "No" --> D["INSERT INTO family / family_member"]
    D --> E{"DB trigger cross-check:\ntrg_family_no_cross_dup /\ntrg_family_member_no_cross_dup"}
    E -- "conflict (if app-check was\nbypassed by a race condition)" --> C
    E -- "ok" --> F["✅ Successfully registered"]
```

### 7.5 During Relief Distribution — resolution + race-condition fix + stock check

```mermaid
sequenceDiagram
    participant App as PHP (save_distribution.php)
    participant Proc as sp_distribute_relief()
    participant DB as MySQL rows

    App->>Proc: CALL sp_distribute_relief(nid_hash, ngo_id, item_id, ...)
    Proc->>DB: SELECT family_id FROM v_person_registry WHERE id_hash = ?
    Note over Proc,DB: Resolves via head or member — any hash (Bug #1 fix)
    Proc->>DB: SELECT ... FROM family WHERE family_id = ? FOR UPDATE
    Note over Proc,DB: Row lock — a 2nd concurrent call for the same family waits here (Bug #2 fix)
    Proc->>DB: COUNT recent same-category distribution (7 days)
    alt duplicate found
        Proc->>DB: INSERT duplicate_log
        Proc-->>App: BLOCKED
    else eligible
        Proc->>DB: SELECT quantity_available FROM ngo_stock FOR UPDATE
        alt insufficient stock
            Proc-->>App: ERROR: Insufficient stock
        else stock available
            Proc->>DB: UPDATE ngo_stock (deduct)
            Proc->>DB: INSERT distribution
            Proc-->>App: SUCCESS
        end
    end
```

### 7.6 Frontend Polish — logo, animations, map

- **Logo:** Two variants were made from `Logo.jpeg` — `img/logo-full.jpeg` (full, with wordmark, used on login/registration pages) and `img/logo-icon.png` (just the circular emblem, wordmark cropped out, used for the header badge + favicon).
- **CSS animations:** card/KPI fade-up entrance; gauge/bar fills now animate from 0 to their target value (`animateFillBars()` in `js/app.js`); KPI numbers count up (`animateKpiCounters()`); button hover/active states get lift+scale feedback; a floating gradient blob background on the login page.
- **🗺️ Relief Zone Map (dashboard.php):** Leaflet.js + OpenStreetMap (no API key needed) shows the 8 relief zones on a map of Bangladesh — marker color reflects the fairness ratio (red/yellow/green), marker size reflects population. Each area's lat/lng is hardcoded as a PHP array in `dashboard.php` (no new DB column was added, since these 8 areas are fixed reference data).
- **Accent color:** The logo's orange color (`--accent`) was added as the new brand accent (used for the active tab underline, `.btn-accent`, etc.).
- **Code-based email verification:** `register_ngo.php` used to send a verification link (which had to be clicked); it now sends a 6-digit code instead (via `includes/mailer.php`), and `verify_email.php` requires entering email+code to verify — direct code entry instead of a phishing-prone link-click flow.

### 7.7 Dynamic Bilingual Localization & Character Encoding Fix (Version 2.1)

- **Dynamic Entity Translation:** Previously, switching between Bengali (`বাংলা`) and English (`EN`) only translated static interface labels, while stored database values (relief items, NGO names, distribution points, relief zones, units, and family head names) remained in Bengali. Version 2.1 introduces `tEntity()` and `tCategory()` in `includes/lang.php` with an entity dictionary. When English is selected, items like `চাল (Food)` dynamically render as `Rice (Food)`, `আশা ফাউন্ডেশন` becomes `Asha Foundation`, and table records adapt to the active language.
- **Language Preference Persistence:** Language selection in `switch_lang.php` now saves to both `$_SESSION['lang']` and a long-lived browser cookie (`reliefx_lang`), ensuring language preferences persist across reloads, new tabs, and browser restarts.
- **UTF-8mb4 Character Encoding Protection:** Windows command shell redirection previously caused `database.sql` to import using ANSI/CP1252 codepages, producing Mojibake corruption on Bengali Unicode strings. The database is now strictly imported with `--default-character-set=utf8mb4` to ensure Bengali characters render in clean Hind Siliguri typography.
