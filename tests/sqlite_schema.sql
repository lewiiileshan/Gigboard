CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE,
  phone TEXT, password_hash TEXT NOT NULL, role TEXT NOT NULL, suspended INTEGER NOT NULL DEFAULT 0, created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE freelancer_profiles (user_id INTEGER PRIMARY KEY, headline TEXT, town TEXT, bio TEXT, skills TEXT, hourly_rate REAL, portfolio_url TEXT);
CREATE TABLE categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL UNIQUE);
CREATE TABLE gigs (id INTEGER PRIMARY KEY AUTOINCREMENT, client_id INTEGER NOT NULL, category_id INTEGER NOT NULL,
  title TEXT NOT NULL, description TEXT NOT NULL, requirements TEXT, budget REAL NOT NULL, location TEXT DEFAULT 'Remote', deadline TEXT,
  status TEXT DEFAULT 'open', removal_reason TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE applications (id INTEGER PRIMARY KEY AUTOINCREMENT, gig_id INTEGER NOT NULL, freelancer_id INTEGER NOT NULL,
  proposal TEXT NOT NULL, proposed_price REAL NOT NULL, status TEXT DEFAULT 'pending', created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (gig_id, freelancer_id));
CREATE TABLE payments (id INTEGER PRIMARY KEY AUTOINCREMENT, gig_id INTEGER NOT NULL UNIQUE, amount REAL NOT NULL,
  status TEXT DEFAULT 'pending', reference TEXT, paid_at TEXT, confirmed_at TEXT, updated_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE submissions (id INTEGER PRIMARY KEY AUTOINCREMENT, gig_id INTEGER NOT NULL, freelancer_id INTEGER NOT NULL, note TEXT,
  original_name TEXT NOT NULL, stored_name TEXT NOT NULL UNIQUE, mime TEXT NOT NULL, size_bytes INTEGER NOT NULL,
  status TEXT DEFAULT 'submitted', client_feedback TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP, reviewed_at TEXT);
CREATE TABLE application_files (id INTEGER PRIMARY KEY AUTOINCREMENT, application_id INTEGER NOT NULL, freelancer_id INTEGER NOT NULL,
  title TEXT NOT NULL, original_name TEXT NOT NULL, stored_name TEXT NOT NULL UNIQUE, mime TEXT NOT NULL, size_bytes INTEGER NOT NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE admin_actions (id INTEGER PRIMARY KEY AUTOINCREMENT, admin_id INTEGER NOT NULL, action TEXT NOT NULL, target_type TEXT NOT NULL,
  target_id INTEGER NOT NULL, detail TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE reviews (id INTEGER PRIMARY KEY AUTOINCREMENT, gig_id INTEGER NOT NULL, reviewer_id INTEGER NOT NULL, reviewee_id INTEGER NOT NULL,
  rating INTEGER NOT NULL, comment TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP, UNIQUE (gig_id, reviewer_id));
INSERT INTO categories (name) VALUES ('Graphic Design'),('Writing & Translation'),('Data Entry'),('Photo & Video'),('Web & Software'),('Social Media'),('Errands & Delivery');
