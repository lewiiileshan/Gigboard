-- GigBoard KE: MySQL schema (3NF)
CREATE DATABASE IF NOT EXISTS gigboard CHARACTER SET utf8mb4;
USE gigboard;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(20),
  password_hash VARCHAR(255) NOT NULL,          -- bcrypt via password_hash()
  role ENUM('client','freelancer','admin') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE freelancer_profiles (
  user_id INT PRIMARY KEY,
  bio TEXT, skills VARCHAR(255), hourly_rate DECIMAL(10,2), portfolio_url VARCHAR(255),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL UNIQUE
);

CREATE TABLE gigs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  category_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  budget DECIMAL(10,2) NOT NULL,                -- KES
  location VARCHAR(100) DEFAULT 'Remote',
  deadline DATE,
  status ENUM('open','in_progress','completed','cancelled') DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES users(id),
  FOREIGN KEY (category_id) REFERENCES categories(id),
  INDEX (status, category_id)
);

CREATE TABLE applications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gig_id INT NOT NULL,
  freelancer_id INT NOT NULL,
  proposal TEXT NOT NULL,
  proposed_price DECIMAL(10,2) NOT NULL,
  status ENUM('pending','accepted','rejected','withdrawn') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (gig_id, freelancer_id),               -- one application per gig
  FOREIGN KEY (gig_id) REFERENCES gigs(id) ON DELETE CASCADE,
  FOREIGN KEY (freelancer_id) REFERENCES users(id)
);

CREATE TABLE payments (                          -- tracking only, no processing
  id INT AUTO_INCREMENT PRIMARY KEY,
  gig_id INT NOT NULL UNIQUE,
  amount DECIMAL(10,2) NOT NULL,
  status ENUM('pending','paid') DEFAULT 'pending',
  reference VARCHAR(50),                         -- optional M-Pesa code typed by user
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (gig_id) REFERENCES gigs(id) ON DELETE CASCADE
);

CREATE TABLE submissions (                       -- work delivered by the hired freelancer (private files)
  id INT AUTO_INCREMENT PRIMARY KEY,
  gig_id INT NOT NULL,
  freelancer_id INT NOT NULL,
  note TEXT,                                     -- message to the client
  original_name VARCHAR(150) NOT NULL,           -- name shown to the user
  stored_name CHAR(32) NOT NULL UNIQUE,          -- random name on disk (storage/deliveries/), never a URL
  mime VARCHAR(100) NOT NULL,
  size_bytes INT NOT NULL,
  status ENUM('submitted','approved','changes_requested') DEFAULT 'submitted',
  client_feedback TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  reviewed_at TIMESTAMP NULL,
  FOREIGN KEY (gig_id) REFERENCES gigs(id) ON DELETE CASCADE,
  FOREIGN KEY (freelancer_id) REFERENCES users(id),
  INDEX (gig_id)
);

CREATE TABLE reviews (                           -- two-way, revealed when both submitted
  id INT AUTO_INCREMENT PRIMARY KEY,
  gig_id INT NOT NULL,
  reviewer_id INT NOT NULL,
  reviewee_id INT NOT NULL,
  rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment VARCHAR(500),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (gig_id, reviewer_id),
  FOREIGN KEY (gig_id) REFERENCES gigs(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewer_id) REFERENCES users(id),
  FOREIGN KEY (reviewee_id) REFERENCES users(id)
);

INSERT INTO categories (name) VALUES
('Graphic Design'),('Writing & Translation'),('Data Entry'),
('Photo & Video'),('Web & Software'),('Social Media'),('Errands & Delivery');
