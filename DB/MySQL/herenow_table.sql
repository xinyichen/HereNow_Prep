CREATE DATABASE IF NOT EXISTS herenow;

USE herenow;

CREATE TABLE IF NOT EXISTS users (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(250) NOT NULL,
  email varchar(255) NOT NULL,
  password_hash text NOT NULL,
  gender enum('m','f'),
  school varchar(250),
  api_key varchar(32) NOT NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY email (email)
) ENGINE=InnoDB;
 
CREATE TABLE IF NOT EXISTS geolocation (
  user_id int(11) NOT NULL,
  latitude decimal(13, 10) NOT NULL DEFAULT 0,
  longitude decimal(13, 10) NOT NULL DEFAULT 0,
  height decimal(6, 2) NOT NULL DEFAULT 0,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;