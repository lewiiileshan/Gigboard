# GigBoard KE - front-end prototype

A responsive, multi-page prototype of GigBoard KE. It runs by opening the files in a browser. No server, PHP or database is needed yet.

## How to run

1. Unzip the folder and open it in VS Code (File > Open Folder).
2. Open `index.html` in your browser, or install the **Live Server** extension, right-click `index.html` and choose "Open with Live Server".

Demo accounts (password for all: `demo1234`):

| Role | Email |
|---|---|
| Client | client@demo.com |
| Freelancer | freelancer@demo.com |

The login page also has one-click demo buttons. Settings > Demo tools > **Reset demo data** restores the sample data before you present.

## Files

| File | What it is |
|---|---|
| `css/style.css` | All styling. Colours are variables at the top (`:root`). |
| `js/app.js` | Shared logic: sample data, login, gig actions, page frame. |
| `index.html` | Landing page |
| `login.html`, `register.html` | Authentication |
| `dashboard.html` | Dashboard (different for clients and freelancers) |
| `gigs.html` | Browse gigs with search, category, location and sort |
| `gig.html` | Gig details: apply, hire, complete, payment status, reviews |
| `post-gig.html` | Client posts a gig |
| `nearby.html` | Map of on-site gigs by area |
| `activity.html` | My gigs (client) or applications and jobs (freelancer) |
| `payments.html` | Payment status tracker (no real money moves) |
| `profile.html` | Your profile, or another user's with `?id=3` |
| `settings.html` | Account, password, notifications, demo tools |

## How this maps to the Laravel build

| Prototype | Laravel |
|---|---|
| `db` arrays in `app.js` | MySQL tables from `gigboard_schema.sql` |
| Functions like `createGig`, `hireApplicant` | Controller methods (`GigController@store`, `ApplicationController@hire`) |
| `renderShell()` | Blade layout (`layouts/app.blade.php`) |
| Each `.html` page | A Blade view in `resources/views` |
| `login()` / `register()` | Laravel Auth with bcrypt-hashed passwords |
| `css/style.css` | Copied to `public/css/` unchanged |

## Known limits (demo only)

- Data is stored in the browser (localStorage), so it is per browser and per device.
- Passwords are stored as plain text. The real build must hash them.
- Payments are status tracking only: pending or paid.
