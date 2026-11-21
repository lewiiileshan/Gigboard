# CHAPTER FIVE: SYSTEM DESIGN

## 5.1 Introduction

GigBoard KE is a web-based gig marketplace for Kenyan youth. It allows clients to post work, freelancers to submit proposals and qualifications, clients to hire talent and review completed work, and both parties to track payments outside the application.

The system is implemented using plain PHP 8, the Model-View-Controller architectural pattern, MySQL/MariaDB and a responsive HTML/CSS interface. Apache or PHP's built-in development server forwards HTTP requests to `public/index.php`, which acts as the front controller.

## 5.2 System Architecture

### 5.2.1 Architectural Overview

```text
Browser
   |
   | HTTP/HTTPS requests
   v
public/index.php (Front Controller)
   |
   v
Router -> Controller -> Model -> PDO -> MariaDB/MySQL
   |                         |
   |                         v
   |                       Tables
   v
View -> HTML/CSS/JavaScript -> Browser
```

Each request follows these steps:

1. The browser sends a request to a URL.
2. Apache or the development router sends the request to `public/index.php`.
3. The router matches the HTTP method and URL to a controller method.
4. The controller performs authentication, authorization and validation.
5. The model executes prepared SQL statements through PDO.
6. The view renders the result using a layout and escaped output.
7. The browser receives the HTML and client-side scripts provide progressive enhancements.

### 5.2.2 Main Components

| Component | Responsibility |
|---|---|
| Front controller | Starts sessions, loads application classes and dispatches routes |
| Router | Matches URL patterns and HTTP methods to controller actions |
| Controllers | Coordinates requests, validation, permissions and responses |
| Models | Performs database operations and business-data queries |
| Views | Builds the HTML presented to users |
| Core classes | Database access, sessions, CSRF protection, authentication and file storage |
| MySQL/MariaDB | Stores persistent application data |
| Private storage | Stores delivered work and qualification files outside the public directory |

### 5.2.3 Request Processing

The front controller defines all application routes. Examples include:

- `GET /gigs` to browse open gigs
- `GET /gigs/{id}` to view a gig
- `POST /gigs/{id}/apply` to submit an application
- `POST /applications/{id}/hire` to hire a freelancer
- `POST /gigs/{id}/submit` to deliver work
- `POST /gigs/{id}/review` to submit a review
- `GET /admin` to open the administrator dashboard

Protected operations call role guards and ownership checks before changing data. A logged-out visitor is redirected to login, while a user without the required role or ownership is denied with HTTP 403 or a not-found response.

## 5.3 Database Design

### 5.3.1 Logical Data Model

The database is normalised to approximately third normal form. Shared lookup data is stored in `categories`, while user and gig-specific records are stored in separate tables.

```text
users 1 -------- * freelancer_profiles
  |
  +------- 1 -------- * gigs
  |                      |
  |                      +--- 1 -------- * applications
  |                                          |
  |                                          +--- * application_files
  |
  +--------------------- * applications
  |
  +--------------------- * submissions
  |
  +--------------------- * payments
  |
  +--------------------- * reviews
```

A gig belongs to one client and one category. A gig may receive many applications, but a freelancer may apply only once to that gig because the database defines a unique `(gig_id, freelancer_id)` key. Hiring one application changes the gig status to `in_progress` and creates a pending payment.

A completed gig may receive at most one review from each participant because `reviews` has a unique `(gig_id, reviewer_id)` key.

### 5.3.2 Database Tables

| Table | Purpose |
|---|---|
| `users` | Stores account identity, role, password hash and suspension status |
| `freelancer_profiles` | Stores freelancer headline, location, skills, rate and portfolio |
| `categories` | Stores selectable gig categories |
| `gigs` | Stores gig details, requirements, budget, status and removal reason |
| `applications` | Stores proposals, proposed prices and application status |
| `application_files` | Stores private qualification file metadata |
| `submissions` | Stores private work-delivery metadata and review status |
| `payments` | Tracks manual payment status for a gig |
| `reviews` | Stores two-way ratings and comments after completion |
| `admin_actions` | Records moderation actions by administrators |

### 5.3.3 Entity Attributes

#### Users

| Field | Type | Constraint | Description |
|---|---|---|---|
| id | INT | Primary key | Unique account identifier |
| name | VARCHAR(100) | Not null | Display name |
| email | VARCHAR(150) | Unique, not null | Login identifier |
| phone | VARCHAR(20) | Nullable | Contact number |
| password_hash | VARCHAR(255) | Not null | Bcrypt password hash |
| role | ENUM | Not null | `client`, `freelancer` or `admin` |
| suspended | TINYINT(1) | Default 0 | Administrative suspension flag |
| created_at | TIMESTAMP | Default current time | Registration time |

#### Gigs

| Field | Type | Constraint | Description |
|---|---|---|---|
| id | INT | Primary key | Gig identifier |
| client_id | INT | Foreign key | Account that posted the gig |
| category_id | INT | Foreign key | Gig category |
| title | VARCHAR(150) | Not null | Gig title |
| description | TEXT | Not null | Full work description |
| requirements | TEXT | Nullable | Qualification requirements |
| budget | DECIMAL(10,2) | Not null | Budget in Kenyan shillings |
| location | VARCHAR(100) | Default Remote | Work location |
| deadline | DATE | Nullable | Completion deadline |
| status | ENUM | Not null | `open`, `in_progress`, `completed` or `cancelled` |
| removal_reason | VARCHAR(255) | Nullable | Reason supplied by an administrator |

#### Applications

| Field | Type | Constraint | Description |
|---|---|---|---|
| id | INT | Primary key | Application identifier |
| gig_id | INT | Foreign key | Target gig |
| freelancer_id | INT | Foreign key | Applicant |
| proposal | TEXT | Not null | Application proposal |
| proposed_price | DECIMAL(10,2) | Not null | Freelancer's proposed fee |
| status | ENUM | Not null | `pending`, `accepted`, `rejected` or `withdrawn` |

#### Payments

| Field | Type | Constraint | Description |
|---|---|---|---|
| id | INT | Primary key | Payment record identifier |
| gig_id | INT | Unique foreign key | Gig represented by the record |
| amount | DECIMAL(10,2) | Not null | Agreed amount |
| status | ENUM | Not null | `pending`, `paid`, `confirmed` or `disputed` |
| reference | VARCHAR(50) | Nullable | Optional manually entered M-Pesa code |
| paid_at | TIMESTAMP | Nullable | Time marked by the client |
| confirmed_at | TIMESTAMP | Nullable | Time confirmed by the freelancer |

### 5.3.4 Relationships and Cardinality

| Relationship | Cardinality | Rule |
|---|---|---|
| User to gigs | 1:M | A client may post many gigs |
| Gig to applications | 1:M | A gig may receive many applications |
| Gig to one freelancer | M:1 through hiring | Only one application becomes accepted |
| Application to qualification files | 1:M | A maximum of three files is enforced by the application |
| Gig to payment | 1:1 | Payment is created when a freelancer is hired |
| Gig to submissions | 1:M | Multiple versions may support a change-request cycle |
| User to reviews | 1:M | A participant may review many gigs |
| Gig to reviews | 1:M | Maximum one review per participant per gig |
| Admin to admin actions | 1:M | Every moderation action is attributable to an account |

## 5.4 Process Design

### 5.4.1 Registration and Authentication

The visitor selects a client or freelancer role, enters a name, email, optional phone number and password, and accepts the terms. Server-side validation verifies field lengths, email format, phone format, password confirmation and uniqueness. The password is hashed using bcrypt.

Login requires a matching email and password. The response is intentionally identical for an unknown email and an incorrect password. A suspended account is rejected after the password is verified and may not continue into the application.

### 5.4.2 Gig Posting and Browsing

A client can create a gig with a category, title, description, requirements, budget, location and optional deadline. Gig listing supports text search, category, budget range and location filters, sorting and pagination.

An open gig can be edited or cancelled only by its owner while it remains open. Suspended clients' open gigs are excluded from public browsing.

### 5.4.3 Application and Hiring

A freelancer submits a proposal and proposed price, optionally attaching up to three qualification files. All input and file checks complete before database records and files are saved.

When the client hires an applicant, the system uses a transaction to:

1. Verify that the gig is open.
2. Accept the selected application.
3. Reject all remaining pending applications.
4. Change the gig to `in_progress`.
5. Create a pending payment for the accepted price.

If a database step fails, the transaction is rolled back.

### 5.4.4 Work Delivery and Review

The hired freelancer uploads work and an optional note. The client may download the file, approve it or request changes with feedback. Approval completes the gig. A requested change leaves the gig in progress and permits a revised upload.

Only the gig's client and the submitting freelancer are allowed to download private files.

### 5.4.5 Payment Tracking

GigBoard tracks the fact of payment; it does not transfer money. After work approval:

1. The client marks the payment as paid and may enter an M-Pesa reference.
2. The freelancer confirms or disputes receipt.
3. A dispute returns the record to the client for rechecking.
4. Confirmation makes the payment immutable through normal application routes.

### 5.4.6 Reviews

After completion, the client and hired freelancer may each rate the other from 1 to 5 and add an optional 500-character comment. Server-side and unique-database constraints enforce one review per reviewer per gig.

### 5.4.7 Administration

Administrators can view platform totals, suspend or restore accounts and remove open gigs with a reason. Administrative accounts can only be created outside the browser using `bin/make-admin.php`. Each action is written to `admin_actions`.

## 5.5 Input and Output Design

### 5.5.1 Registration Form

| Field | Input Control | Validation |
|---|---|---|
| Full name | Text | 2 to 100 characters |
| Email | Email | Valid unique email |
| Phone | Tel | Optional, 9 to 15 digits/spaces with optional plus |
| Password | Password | Minimum 8 characters |
| Confirm password | Password | Must match password |
| Role | Radio | Client or freelancer |
| Terms | Checkbox | Required |

### 5.5.2 Gig Form

| Field | Input Control | Validation |
|---|---|---|
| Title | Text | 5 to 150 characters |
| Category | Select | Existing category |
| Description | Textarea | 20 to 5000 characters |
| Requirements | Textarea | Optional, maximum 1500 characters |
| Budget | Number | KES 100 to KES 1,000,000 |
| Location | Text | Maximum 100 characters |
| Deadline | Date | Optional valid future date |

### 5.5.3 Application Form

| Field | Input Control | Validation |
|---|---|---|
| Proposal | Textarea | 20 to 2000 characters |
| Proposed price | Number | KES 100 to KES 1,000,000 |
| Qualification title | Text | 3 to 80 characters per file |
| Qualification file | File | Maximum three; 10 MB each; allowed type and detected content |

### 5.5.4 Profile Form

Client profiles edit name and phone. Freelancers additionally enter a headline, town, bio, up to eight skills, hourly rate and optional HTTP/HTTPS portfolio URL.

### 5.5.5 Delivery Form

| Field | Input Control | Validation |
|---|---|---|
| Work file | File | 10 MB maximum; whitelisted extension and detected MIME |
| Note | Textarea | Optional delivery information |

### 5.5.6 Review Form

| Field | Input Control | Validation |
|---|---|---|
| Rating | 1 to 5 radio/selection | Whole number from 1 to 5 |
| Comment | Textarea | Optional, maximum 500 characters |

### 5.5.7 Main Output Screens

| Screen | Key Output |
|---|---|
| Public welcome page | Platform introduction, categories and latest open gigs |
| Dashboard | Gig status totals and account role navigation |
| Gig list | Filterable, sortable rows with budgets and applicant counts |
| Gig detail | Description, requirements, applicants, delivery and payment status |
| My applications | Application state and gig information |
| Payments | Totals and payment status for each gig |
| Profile | Freelancer headline, skills, hourly rate and review summary |
| Admin overview | Platform totals, disputed payments and suspended users |
| Admin users | Searchable accounts with suspend/restore controls |
| Admin gigs | Searchable gig moderation and removal controls |

All dynamic values displayed in HTML are passed through an escaping helper. Flash messages are announced through the interface and removed after display.

## 5.6 Security Design

| Requirement | Implementation |
|---|---|
| Authentication | Bcrypt password hashes and server-side password verification |
| Session security | Session identifiers are regenerated on login and logout |
| CSRF protection | Every state-changing form carries a session CSRF token |
| SQL injection | PDO prepared statements with parameters |
| Cross-site scripting | Dynamic output escaped with `e()` |
| Role control | Server-side role guards |
| Ownership control | Server-side gig, application, delivery and payment ownership checks |
| Admin isolation | Admin role cannot be chosen through registration |
| File privacy | Files stored outside `public/` and delivered through permission-checked routes |
| File validation | Extension, file size and detected MIME type checks |
| Password disclosure | Login failures use a generic email/password message |
| Account suspension | Suspended accounts are denied login and signed out on next request |
| Rate-limiting | Not implemented; identified as Phase 14 work |
| Email verification | Not implemented; identified as Phase 14 work |

## 5.7 Interface and Responsiveness

GigBoard uses one responsive interface with a desktop header and a mobile bottom tab bar. Forms use visible labels, keyboard focus styles, error summaries, confirmation dialogs and submit-state protection.

At desktop width, gig listings use a filter sidebar and job rows. Gig details use a two-column layout with a sticky summary panel. On narrow screens, filters fold behind a control and navigation moves to the bottom tab bar.

## 5.8 Error and Recovery Design

Server-side validation preserves submitted text through session flash data where appropriate and redirects to the relevant form. Upload failures remove already-saved files when a later validation or database step fails.

Database-dependent operations use transactions for hiring and other multi-table changes. The setup checker at `bin/check-setup.php` verifies the PHP version, required extensions, storage permissions, database access and table availability.

## 5.9 Testing and Verification

GigBoard uses shell-based integration tests that start a PHP server and issue HTTP requests. Tests use a temporary SQLite database by default or a clean MariaDB database through `GB_TEST_MYSQL=1`.

The eight suites cover accounts, gigs, application delivery, qualification files, profiles, payment tracking, reviews and administration. The current project records 293 checks.

Test summary:

| Suite | Checks |
|---|---|
| Accounts | 16 |
| Gigs | 41 |
| Delivery | 51 |
| Qualifications | 28 |
| Profiles | 28 |
| Payments | 41 |
| Reviews | 31 |
| Administration | 57 |
| **Total** | **293** |

## 5.10 Deployment Design

For the university deployment, the application is hosted through XAMPP on Apache and MySQL/MariaDB. Database settings may be overridden using `config/config.local.php`, which is excluded from Git.

The production transition requires HTTPS hosting, verified database backups, monitoring, email verification, password reset, login rate limiting, bot protection and review of applicable Kenyan data-protection requirements.
