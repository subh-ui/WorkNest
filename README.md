# WorkNest

A modern PHP-based job portal that connects job seekers with hiring teams. WorkNest enables applicants to discover jobs, register accounts, submit applications, and track their progress, while HR users can manage roles, review candidates, and respond to applicants.

<div align="center">
  <img src="https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php" alt="PHP" />
  <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql" alt="MySQL" />
  <img src="https://img.shields.io/badge/Status-Open%20Demo-28a745?style=for-the-badge" alt="Status" />
</div>

## Overview

WorkNest is designed as a full-stack web application demo for recruitment and hiring workflows. It provides a simple and responsive experience for both candidates and HR staff, with a clean landing page, account system, job listing interface, and application management flow.

## Features

- User registration and login
- Admin/HR registration and login
- Job listings with seeded sample roles
- Candidate application submission with custom cover message
- Admin review and response workflow
- Responsive job portal UI for desktop and mobile
- MySQL-backed data model with automatic table setup

## Tech Stack

- PHP
- MySQL
- HTML
- CSS
- JavaScript
- XAMPP / Apache + MySQL local development environment

## Project Structure

```text
WorkNest/
├── index.php                  # Entry point for the app
├── config/
│   └── database.php          # Database connection and table initialization
├── pages/                    # User and admin pages
├── uploads/
│   └── profile-pictures/     # Profile image upload folder
├── database.sql              # Schema and seed data
├── styles.css                # Shared styling for the app
├── LICENSE                   # MIT license
├── README.md                 # Project documentation
└── .gitignore                # Git ignore rules
```

## Prerequisites

Before running the project locally, ensure you have:

- PHP installed
- MySQL running
- Apache or a local web server
- XAMPP recommended for quick setup

## Installation

1. Clone the repository:

   ```bash
   git clone https://github.com/your-username/WorkNest.git
   cd WorkNest
   ```

2. Move the project into your web server directory, for example:

   - `C:/xampp/htdocs/WorkNest`
   - `D:/xampp/htdocs/WorkNest`

3. Start Apache and MySQL using XAMPP.

4. Import the SQL schema from `database.sql` into your MySQL database.

5. Update the database credentials in `config/database.php` to match your local environment.

6. Open the app in your browser:

   ```text
   http://localhost/WorkNest/
   ```

## Usage

### For Job Seekers

1. Open the homepage.
2. Register a user account.
3. Log in to browse job opportunities.
4. Apply to suitable roles and submit a cover message.

### For HR/Admins

1. Visit the HR portal from the homepage.
2. Register an admin account.
3. Log in to manage vacancies and view applicant submissions.
4. Respond to candidates through the admin workflow.

## Database Schema

The application uses the following main tables:

- `users`
- `admins`
- `jobs`
- `applications`

The database layer also automatically creates missing columns and inserts default sample jobs when the app initializes.

## Configuration Note

The current database connection in `config/database.php` is configured for a hosted environment. For local development, replace the host, username, password, and database name with your MySQL local values.

## Demo / Development Notes

This project is intended as a practical demo for:

- learning full-stack PHP development
- building a portfolio-ready recruitment platform
- extending into more advanced hiring workflows

Possible future improvements include:

- resume upload support
- email verification
- job filtering and search
- admin analytics dashboard
- advanced role-based permissions

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome. If you would like to improve the platform, open an issue or submit a pull request with a clear description of the change.

