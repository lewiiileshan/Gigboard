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

## Step 3: applying, hiring and private work delivery

1. A **freelancer** opens an open gig and sends an application (proposal + price). One application per gig.
2. The **client** sees all applicants on the gig page and presses **Hire**. That accepts one application, declines the rest, moves the gig to *In progress* and records the agreed price as a pending payment.
3. Only the **hired freelancer** can upload the finished work (PDF, Office files, TXT, CSV, PNG, JPG or ZIP, up to 10 MB).
4. Only the **gig's client** (and the freelancer who uploaded) can download it. Anyone else gets a "not found" page.
5. The client **approves** the work (gig becomes *Completed*) or **requests changes** with feedback; the freelancer then uploads a new version.

How the files are kept private:
- Saved in `storage/deliveries/`, outside `public/`, under a random name with no extension. There is no URL to the file.
- `storage/.htaccess` also blocks direct web access if someone points Apache at the project folder.
- `SubmissionController::download()` checks who is asking, then sends the file as a forced download with `nosniff`.
- File type is checked twice: by extension (whitelist) and by the file's real content.

On XAMPP, the default PHP upload limit (`upload_max_filesize`, 40M) is higher than the app's 10 MB limit, so no change is needed.

Tests: `bash tests/delivery_test.sh` (51 checks) alongside `smoke.sh` and `gigs_test.sh`.

## Step 5: requirements and qualification files

- When posting (or editing) a gig, the **client** can write **requirements** for applicants, for example "Diploma in graphic design and a portfolio". They show on the gig page.
- When applying, the **freelancer** can attach up to **3 qualification files**, each with a title (certificate, CV, portfolio sample). Same allowed types and 10 MB limit as delivered work.
- The client sees each applicant's files next to their proposal and price, and uses them to decide whom to hire.
- The files are private, stored like delivered work. Only the freelancer who uploaded them and the client of that gig can download them (`QualificationController`). Everyone else gets "not found".
- If any file fails the checks, nothing is saved: no application and no leftover files.

Database change: `gigs.requirements` and the new `application_files` table (both in `database/schema.sql`). Tests: `bash tests/qualifications_test.sh` (28 checks).
