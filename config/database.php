<?php
declare(strict_types=1);

function get_database_connection(): mysqli
{
    $connection = new mysqli('sql303.infinityfree.com', 'if0_42973974', 'Pj3jz0HwKR', 'if0_42973974_worknest');

    if ($connection->connect_errno) {
        throw new RuntimeException('Database connection failed. Import database.sql in phpMyAdmin first.');
    }

    $connection->set_charset('utf8mb4');
    ensure_admins_designation_column($connection);
    ensure_phone_verification_columns($connection);
    ensure_jobs_table($connection);
    return $connection;
}

function ensure_phone_verification_columns(mysqli $connection): void
{
    $queries = [
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS phone_number VARCHAR(20) NULL AFTER email",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS phone_verified TINYINT(1) NOT NULL DEFAULT 1 AFTER phone_number",
        "ALTER TABLE admins ADD COLUMN IF NOT EXISTS phone_number VARCHAR(20) NULL AFTER email",
        "ALTER TABLE admins ADD COLUMN IF NOT EXISTS phone_verified TINYINT(1) NOT NULL DEFAULT 1 AFTER phone_number",
    ];

    foreach ($queries as $query) {
        if (!$connection->query($query)) {
            throw new RuntimeException('Unable to prepare phone verification: ' . $connection->error);
        }
    }
}

function ensure_admins_designation_column(mysqli $connection): void
{
    if (!$connection->query("ALTER TABLE admins ADD COLUMN IF NOT EXISTS designation VARCHAR(120) NOT NULL DEFAULT 'HR manager' AFTER full_name")) {
        throw new RuntimeException('Unable to prepare the HR accounts table: ' . $connection->error);
    }

    if (!$connection->query('ALTER TABLE admins DROP COLUMN IF EXISTS profile_picture')) {
        throw new RuntimeException('Unable to remove the HR profile pictures column: ' . $connection->error);
    }
}

function ensure_jobs_table(mysqli $connection): void
{
    $createTable = <<<'SQL'
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
)
SQL;

    if (!$connection->query($createTable)) {
        throw new RuntimeException('Unable to create the jobs table: ' . $connection->error);
    }

    $createApplicationsTable = <<<'SQL'
CREATE TABLE IF NOT EXISTS applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    cover_message TEXT NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'New',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY one_application_per_job (job_id, user_id),
    INDEX (created_at),
    INDEX (user_id),
    INDEX (job_id)
)
SQL;

    if (!$connection->query($createApplicationsTable)) {
        throw new RuntimeException('Unable to create the applications table: ' . $connection->error);
    }

    $connection->query('ALTER TABLE applications ADD COLUMN IF NOT EXISTS admin_reply TEXT NULL, ADD COLUMN IF NOT EXISTS responded_at TIMESTAMP NULL DEFAULT NULL');

    $seedRoles = [
        ['Product Designer', 'Northstar Labs', 'Design', 'Remote', '$90k - $120k'],
        ['Frontend Engineer', 'Vertex Systems', 'Engineering', 'Hybrid', '$105k - $145k'],
        ['Customer Success Lead', 'Orbit Collective', 'Operations', 'Remote', '$75k - $98k'],
        ['Marketing Manager', 'Monument Studio', 'Marketing', 'On-site', '$82k - $110k'],
        ['Data Analyst', 'Clearpath Health', 'Analytics', 'Remote', '$78k - $105k'],
        ['Content Strategist', 'Lantern Media', 'Content', 'Hybrid', '$70k - $92k'],
        ['Product Manager', 'Pivot Works', 'Product', 'Remote', '$115k - $150k'],
        ['Backend Engineer', 'Brightline Cloud', 'Engineering', 'Remote', '$110k - $150k'],
        ['People Operations Partner', 'Greenhouse Co.', 'People', 'Hybrid', '$88k - $115k'],
        ['Sales Development Rep', 'Summit Networks', 'Sales', 'On-site', '$55k - $78k'],
        ['UX Researcher', 'Atlas Learning', 'Research', 'Remote', '$95k - $125k'],
        ['Finance Associate', 'Fieldstone Capital', 'Finance', 'Hybrid', '$68k - $88k'],
        ['Recruiter', 'Ripple Ventures', 'People', 'Remote', '$72k - $100k'],
        ['QA Engineer', 'Kite Software', 'Engineering', 'Hybrid', '$85k - $115k'],
        ['Community Manager', 'Harbor House', 'Community', 'Remote', '$62k - $84k'],
        ['Technical Writer', 'TrueNorth API', 'Content', 'Remote', '$80k - $108k'],
        ['Account Executive', 'Evergreen Commerce', 'Sales', 'Hybrid', '$90k - $135k'],
        ['Mobile Developer', 'Daybreak Apps', 'Engineering', 'Remote', '$100k - $140k'],
        ['Operations Coordinator', 'Waypoint Travel', 'Operations', 'On-site', '$58k - $76k'],
        ['Security Analyst', 'Quarry Data', 'Security', 'Hybrid', '$98k - $132k'],
    ];
    $statement = $connection->prepare('INSERT IGNORE INTO jobs (title, company_name, department, location, salary_range) VALUES (?, ?, ?, ?, ?)');
    if (!$statement) {
        throw new RuntimeException('Unable to prepare the default jobs query: ' . $connection->error);
    }

    foreach ($seedRoles as $role) {
        $statement->bind_param('sssss', $role[0], $role[1], $role[2], $role[3], $role[4]);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Unable to add the default jobs: ' . $statement->error);
        }
    }
    $statement->close();
}
