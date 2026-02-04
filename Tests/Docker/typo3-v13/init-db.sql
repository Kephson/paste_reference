-- Initialize TYPO3 v13 test database
-- This script sets up the basic database structure for testing

-- Create test user if not exists
CREATE USER IF NOT EXISTS 'typo3_test'@'%' IDENTIFIED BY 'typo3_test';
GRANT ALL PRIVILEGES ON typo3_test.* TO 'typo3_test'@'%';

-- Use the test database
USE typo3_test;

-- Basic TYPO3 tables for testing (minimal structure)
-- These will be properly created by TYPO3 installation process

-- Backend users table
CREATE TABLE IF NOT EXISTS be_users (
    uid int(11) NOT NULL AUTO_INCREMENT,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    disable tinyint(4) DEFAULT '0' NOT NULL,
    username varchar(50) DEFAULT '' NOT NULL,
    password varchar(100) DEFAULT '' NOT NULL,
    admin tinyint(4) DEFAULT '0' NOT NULL,
    PRIMARY KEY (uid),
    KEY parent (pid)
);

-- Insert test admin user (password: 'password')
INSERT IGNORE INTO be_users (uid, username, password, admin, tstamp, crdate) VALUES 
(1, 'admin', '$argon2i$v=19$m=65536,t=16,p=1$UnlOcXhBbEFJUWNHWnVOZg$4QWLmPOYhYnx0hdGt4/DbhzFtJF/Z8/xJqKwGqmn4pY', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- Pages table for content testing
CREATE TABLE IF NOT EXISTS pages (
    uid int(11) NOT NULL AUTO_INCREMENT,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    title varchar(255) DEFAULT '' NOT NULL,
    doktype int(11) DEFAULT '1' NOT NULL,
    PRIMARY KEY (uid),
    KEY parent (pid)
);

-- Insert test pages
INSERT IGNORE INTO pages (uid, pid, title, doktype, tstamp, crdate) VALUES 
(1, 0, 'Test Root Page', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, 1, 'Test Content Page', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- Content elements table
CREATE TABLE IF NOT EXISTS tt_content (
    uid int(11) NOT NULL AUTO_INCREMENT,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    CType varchar(255) DEFAULT '' NOT NULL,
    header varchar(255) DEFAULT '' NOT NULL,
    bodytext mediumtext,
    colPos int(11) DEFAULT '0' NOT NULL,
    PRIMARY KEY (uid),
    KEY parent (pid)
);

-- Insert test content elements
INSERT IGNORE INTO tt_content (uid, pid, CType, header, bodytext, colPos, tstamp, crdate) VALUES 
(1, 2, 'text', 'Test Content Element', 'This is a test content element for paste-reference testing.', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, 2, 'text', 'Another Test Element', 'This is another test content element.', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

FLUSH PRIVILEGES;