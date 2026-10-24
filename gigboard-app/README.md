# GigBoard KE: backend (PHP + MySQL)

Plain PHP in an MVC layout (no framework).

- **Step 1:** database connection, router, register, login, logout.
- **Step 2 (this version):** post gigs, browse and search them, filter by category / budget / location, sort,
  edit and cancel your own gigs, "My gigs" page, dashboard counts.
- **Next:** step 3, applications and hiring.

## Run it on XAMPP (Windows)

1. Copy this whole folder to `C:\xampp\htdocs\gigboard-app`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open http://localhost/phpmyadmin, click **Import**, choose `database/schema.sql`, click **Import**.
   This creates the `gigboard` database and its tables.
4. Open http://localhost/gigboard-app/public/ and register an account.

If your MySQL root user has a password, edit `config/config.php`.

## Folder map

| Folder / file | What it does |
|---|---|
| `public/index.php` | Front controller. Every request starts here and the routes are listed in it. |
| `public/css/` | Stylesheet (the same one as the prototype). |
| `app/Core/` | Plumbing: Router, Database (PDO), Session + CSRF, Auth, View, helpers. |
| `app/Controllers/` | One method per page action (what happens on GET/POST). |
| `app/Models/` | Database queries for one table each. |
| `app/Views/` | HTML templates. |
| `config/config.php` | Database and app settings. |
| `database/schema.sql` | MySQL tables. |
| `tests/smoke.sh` | 16 checks for register / login / logout. |
| `tests/gigs_test.sh` | 41 checks for gigs: validation, search, filters, sorting, permissions, cancelling, paging. |

Both test scripts need only `php` and `curl` (they use a temporary SQLite database, so no MySQL is needed). Run them with `bash tests/smoke.sh`.

## Pages

| URL | Who | What |
|---|---|---|
| `/gigs` | any logged-in user | browse and search open gigs |
| `/gigs/{id}` | any logged-in user | gig details (cancelled gigs are visible only to their owner) |
| `/gigs/create` | clients | post a gig |
| `/gigs/{id}/edit` | the owner, while the gig is open | edit a gig |
| `/my-gigs` | clients | all of your gigs with their status |

## Security built in
Prepared statements (SQL injection), output escaping with `e()` (XSS), CSRF token on every form,
bcrypt password hashes, session id regenerated on login and logout, role guard `Auth::requireRole()`, owner checks on edit and cancel, and the sort option is looked up in a fixed list so it can never carry SQL.


## What the app looks like (Step 2b: Tasko-style restyle)

- **Website**: `/` is a public welcome page (hero, how it works, categories, sign-up button). Logged-in users skip it and land on their dashboard.
- **App**: a light theme with a navy brand. On a phone you get a bottom tab bar (Home, Gigs, My gigs, Account); on a laptop the same tabs sit at the top.
- **Account** tab (`/account`): your details and the Log out button.
- It is one responsive web app: open it on a phone or a laptop. Tests: `bash tests/smoke.sh` and `bash tests/gigs_test.sh`.
