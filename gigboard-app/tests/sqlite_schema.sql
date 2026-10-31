CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE,
  phone TEXT, password_hash TEXT NOT NULL, role TEXT NOT NULL, created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL UNIQUE);
CREATE TABLE gigs (id INTEGER PRIMARY KEY AUTOINCREMENT, client_id INTEGER NOT NULL, category_id INTEGER NOT NULL,
  title TEXT NOT NULL, description TEXT NOT NULL, budget REAL NOT NULL, location TEXT DEFAULT 'Remote', deadline TEXT,
  status TEXT DEFAULT 'open', created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE applications (id INTEGER PRIMARY KEY AUTOINCREMENT, gig_id INTEGER NOT NULL, freelancer_id INTEGER NOT NULL,
  proposal TEXT NOT NULL, proposed_price REAL NOT NULL, status TEXT DEFAULT 'pending', created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (gig_id, freelancer_id));
CREATE TABLE payments (id INTEGER PRIMARY KEY AUTOINCREMENT, gig_id INTEGER NOT NULL UNIQUE, amount REAL NOT NULL,
  status TEXT DEFAULT 'pending', reference TEXT, updated_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE submissions (id INTEGER PRIMARY KEY AUTOINCREMENT, gig_id INTEGER NOT NULL, freelancer_id INTEGER NOT NULL, note TEXT,
  original_name TEXT NOT NULL, stored_name TEXT NOT NULL UNIQUE, mime TEXT NOT NULL, size_bytes INTEGER NOT NULL,
  status TEXT DEFAULT 'submitted', client_feedback TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP, reviewed_at TEXT);
INSERT INTO categories (name) VALUES ('Graphic Design'),('Writing & Translation'),('Data Entry'),('Photo & Video'),('Web & Software'),('Social Media'),('Errands & Delivery');
