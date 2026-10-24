CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE,
  phone TEXT, password_hash TEXT NOT NULL, role TEXT NOT NULL, created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL UNIQUE);
CREATE TABLE gigs (id INTEGER PRIMARY KEY AUTOINCREMENT, client_id INTEGER NOT NULL, category_id INTEGER NOT NULL,
  title TEXT NOT NULL, description TEXT NOT NULL, budget REAL NOT NULL, location TEXT DEFAULT 'Remote', deadline TEXT,
  status TEXT DEFAULT 'open', created_at TEXT DEFAULT CURRENT_TIMESTAMP);
INSERT INTO categories (name) VALUES ('Graphic Design'),('Writing & Translation'),('Data Entry'),('Photo & Video'),('Web & Software'),('Social Media'),('Errands & Delivery');
