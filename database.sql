
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone_number VARCHAR(20) NULL,
    phone_verified TINYINT(1) NOT NULL DEFAULT 1,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    designation VARCHAR(120) NOT NULL,
    company_name VARCHAR(160) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone_number VARCHAR(20) NULL,
    phone_verified TINYINT(1) NOT NULL DEFAULT 1,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Apply this migration when the admins table already exists.
ALTER TABLE admins ADD COLUMN IF NOT EXISTS company_name VARCHAR(160) NOT NULL AFTER full_name;
ALTER TABLE admins ADD COLUMN IF NOT EXISTS designation VARCHAR(120) NOT NULL DEFAULT 'HR manager' AFTER full_name;
ALTER TABLE admins DROP COLUMN IF EXISTS profile_picture;
ALTER TABLE users DROP COLUMN IF EXISTS profile_picture;
ALTER TABLE users ADD COLUMN IF NOT EXISTS phone_number VARCHAR(20) NULL AFTER email;
ALTER TABLE users ADD COLUMN IF NOT EXISTS phone_verified TINYINT(1) NOT NULL DEFAULT 1 AFTER phone_number;
ALTER TABLE admins ADD COLUMN IF NOT EXISTS phone_number VARCHAR(20) NULL AFTER email;
ALTER TABLE admins ADD COLUMN IF NOT EXISTS phone_verified TINYINT(1) NOT NULL DEFAULT 1 AFTER phone_number;

CREATE TABLE IF NOT EXISTS jobs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NULL,
    title VARCHAR(160) NOT NULL,
    company_name VARCHAR(160) NOT NULL,
    department VARCHAR(100) NOT NULL,
    location VARCHAR(100) NOT NULL,
    salary_range VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (created_at),
    INDEX (admin_id),
    UNIQUE KEY role_identity (title, company_name)
);

INSERT IGNORE INTO jobs (title, company_name, department, location, salary_range) VALUES
('Product Designer', 'Northstar Labs', 'Design', 'Remote', '$90k - $120k'),
('Frontend Engineer', 'Vertex Systems', 'Engineering', 'Hybrid', '$105k - $145k'),
('Customer Success Lead', 'Orbit Collective', 'Operations', 'Remote', '$75k - $98k'),
('Marketing Manager', 'Monument Studio', 'Marketing', 'On-site', '$82k - $110k'),
('Data Analyst', 'Clearpath Health', 'Analytics', 'Remote', '$78k - $105k'),
('Content Strategist', 'Lantern Media', 'Content', 'Hybrid', '$70k - $92k'),
('Product Manager', 'Pivot Works', 'Product', 'Remote', '$115k - $150k'),
('Backend Engineer', 'Brightline Cloud', 'Engineering', 'Remote', '$110k - $150k'),
('People Operations Partner', 'Greenhouse Co.', 'People', 'Hybrid', '$88k - $115k'),
('Sales Development Rep', 'Summit Networks', 'Sales', 'On-site', '$55k - $78k'),
('UX Researcher', 'Atlas Learning', 'Research', 'Remote', '$95k - $125k'),
('Finance Associate', 'Fieldstone Capital', 'Finance', 'Hybrid', '$68k - $88k'),
('Recruiter', 'Ripple Ventures', 'People', 'Remote', '$72k - $100k'),
('QA Engineer', 'Kite Software', 'Engineering', 'Hybrid', '$85k - $115k'),
('Community Manager', 'Harbor House', 'Community', 'Remote', '$62k - $84k'),
('Technical Writer', 'TrueNorth API', 'Content', 'Remote', '$80k - $108k'),
('Account Executive', 'Evergreen Commerce', 'Sales', 'Hybrid', '$90k - $135k'),
('Mobile Developer', 'Daybreak Apps', 'Engineering', 'Remote', '$100k - $140k'),
('Operations Coordinator', 'Waypoint Travel', 'Operations', 'On-site', '$58k - $76k'),
('Security Analyst', 'Quarry Data', 'Security', 'Hybrid', '$98k - $132k');

CREATE TABLE IF NOT EXISTS applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    cover_message TEXT NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'New',
    admin_reply TEXT NULL,
    responded_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY one_application_per_job (job_id, user_id),
    INDEX (created_at),
    INDEX (user_id),
    INDEX (job_id)
);

-- Do not insert plaintext passwords. Register through pages/register.php so
-- PHP creates the password_hash value with password_hash().
-- Verify saved users in phpMyAdmin with:
-- SELECT id, full_name, email, created_at FROM users ORDER BY created_at DESC;
-- HR accounts are created through pages/admin-register.php.
