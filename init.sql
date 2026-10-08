CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(100) NOT NULL,     -- VULN: unsalted MD5, see includes/auth.php
  role VARCHAR(20) DEFAULT 'user',
  reset_token VARCHAR(50),
  bio TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE threads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  title VARCHAR(200),
  body TEXT,                          -- VULN: rendered unsanitized -> stored XSS
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE replies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  thread_id INT,
  user_id INT,
  body TEXT,                          -- VULN: stored XSS sink too
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (thread_id) REFERENCES threads(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- MD5("admin123") etc — unsalted, instantly crackable.
-- Note: password '0e462097431906509019562988736854' style hashes are the
-- classic PHP type-juggling demo (loose == treats "0e..." hex-digit-only
-- strings as equal floats, i.e. 0 == 0). The seed below is a real MD5 of
-- "admin123" (not a magic-hash) so the SQLi/type-juggling writeup explains
-- both bypass routes separately rather than pre-baking a collision.
INSERT INTO users (username, email, password, role, bio) VALUES
('admin', 'admin@vulnforum.local', MD5('admin123'), 'admin', 'Forum administrator'),
('alice', 'alice@vulnforum.local', MD5('password123'), 'user', 'Just here to post'),
('bob', 'bob@vulnforum.local', MD5('letmein'), 'user', 'Bob from the forums');

INSERT INTO threads (user_id, title, body) VALUES
(1, 'Welcome to VulnForum', 'Post anything here. <b>HTML renders fine.</b>'),
(2, 'Best programming language?', 'I vote PHP, obviously.');

INSERT INTO replies (thread_id, user_id, body) VALUES
(1, 2, 'Glad to be here!'),
(1, 3, 'Hello everyone.');
