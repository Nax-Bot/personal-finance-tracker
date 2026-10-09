# Personal Finance Tracker

A personal finance tracking application with a static web interface, a PHP API, and a MariaDB database.

> This project is prepared for WAMP64 on Windows. Run the commands below in the Command Prompt from the project root directory (the folder that contains `composer.json`).

## Features

- Create, view, update, and delete accounts and categories.
- Record income and expenses and link them to an account and a category.
- Update the account balance when a transaction is created, updated, or deleted.
- View transactions for a specific month and display a monthly summary.
- Display financial data in the web interface.

If a monthly limit is exceeded, the application shows a warning but does not block the transaction.

The application does not support multiple users or user authentication. Do not store real financial data on a public server.

## Technologies

- PHP 8.3, PDO, and MariaDB/MySQL
- Composer and `vlucas/phpdotenv`
- PHPUnit 12.5
- HTML, CSS, and JavaScript, with no frontend build step

## Project Structure

```text
frontend/                  Static web interface (HTML, CSS, JavaScript)
public/                    PHP API entry point and Apache rules
routes/api.php             HTTP route definitions
src/Controllers/           Request handling and JSON responses
src/Commands/              Write operations and business rules
src/Queries/               Data reading and summaries
src/Repositories/           MariaDB access using PDO
src/Database/               PDO database connection
tests/Unit/                Unit tests without MariaDB
tests/Integration/         MariaDB and HTTP integration tests
tests/fixtures/             Test database schema and HTTP test router
database/schema.sql        Main database schema (must be added before release)
```

The frontend and API must be served from the same domain.

The frontend uses the API path configured in `frontend/assets/js/config.js`, pointing to `../public`.

## Install the Project with WAMP64

### Requirements

Make sure you have:

- WAMP64 installed, with Apache and MariaDB running.
- PHP 8.3 and Composer available in the Command Prompt.
- The PHP extensions `pdo_mysql` and `mbstring` enabled.

Check your PHP and Composer versions:

```bash
php -v
composer --version
```

### Install the Project Files

1. Copy the project folder into `C:\wamp64\www\`.
2. Open the Command Prompt in the project folder.
3. Install the project dependencies:

```bash
composer install
```

Composer creates the `vendor/` folder. Do not publish this folder to GitHub.

### Create and Configure the Main Database

1. Open phpMyAdmin and create a database named `finance_tracker`.
2. Make sure the project contains `database/schema.sql`. This file must contain the `CREATE TABLE` statements needed to create the database tables, without any personal data.
3. In phpMyAdmin, select the `finance_tracker` database, click **Import**, select `database/schema.sql`, and start the import.

If `database/schema.sql` is missing from the project, ask for the installation schema before continuing.

The file `tests/fixtures/schema.test.sql` is only for the test database. Do not import it into the main database.

Copy the example configuration file:

```bash
copy .env.example .env
```

Open the `.env` file in the project root directory and update the database settings for your MariaDB installation:

```dotenv
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=finance_tracker
DB_USERNAME=root
DB_PASSWORD=
```

The default WAMP port is often `3306`. Use the username and password configured on your computer.

The `.env` file contains private configuration settings. Never publish it to GitHub.

### Open the Application

Make sure WAMP64 is running and its icon is green.

Open the following URL in your browser:

```text
http://localhost/PROJECT_FOLDER_NAME/frontend/
```

Replace `PROJECT_FOLDER_NAME` with the name of your project folder inside `C:\wamp64\www\`.

For example:

```text
http://localhost/personal_finance_tracker/frontend/
```

To check the API, open:

```text
http://localhost/PROJECT_FOLDER_NAME/public/accounts
```

Replace `PROJECT_FOLDER_NAME` with your actual project folder name.

## Available HTTP Routes

The following paths are relative to the `public` directory.

| Method | Path | Description |
|---|---|---|
| GET | `/accounts` | Get all accounts |
| POST | `/accounts` | Create an account |
| GET | `/accounts/{id}` | Get an account by ID |
| PATCH | `/accounts/{id}` | Update an account |
| DELETE | `/accounts/{id}` | Delete an account |
| GET | `/categories` | Get all categories |
| POST | `/categories` | Create a category |
| GET | `/categories/{id}` | Get a category by ID |
| PATCH | `/categories/{id}` | Update a category |
| DELETE | `/categories/{id}` | Delete a category |
| GET | `/transaction` | Get all transactions |
| POST | `/transaction` | Create a transaction |
| GET | `/transaction/{id}` | Get a transaction by ID |
| PATCH | `/transaction/{id}` | Update a transaction |
| DELETE | `/transaction/{id}` | Delete a transaction |
| GET | `/transaction/month/YYYY-MM` | Get transactions for a specific month |
| GET | `/account/{id}/transactions` | Get transactions for an account |
| GET | `/summary?month=YYYY-MM` | Get the monthly summary |

## Run Unit Tests

Open the Command Prompt in the project root directory and run:

```bash
composer test
```

These tests check the business rules for accounts, categories, and transactions.

Unit tests do not require MariaDB or an HTTP server.

## Run MariaDB and HTTP Integration Tests

Integration tests use a separate database named `finance_tracker_test`.

**Never use the main `finance_tracker` database for integration tests.** These tests create and delete test data.

### Step 1: Create the Test Database

1. Open phpMyAdmin.
2. Create an empty database named `finance_tracker_test`.
3. Select the database, click **Import**, and import `tests/fixtures/schema.test.sql`.

### Step 2: Create the Test Configuration

In the project root directory, run:

```bash
copy .env.testing.example .env.testing
```

Open `.env.testing` and check the settings. Update the username and password if necessary:

```dotenv
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=finance_tracker_test
DB_USERNAME=root
DB_PASSWORD=
TEST_BASE_URL=http://127.0.0.1:8000/personal_finance_tracker/public
```

Never publish `.env.testing` to GitHub.

Keep the `TEST_BASE_URL` value shown above when running the following commands.

### Step 3: Start the Test HTTP Server

Open a first Command Prompt window in the project root directory and run:

```bash
php -S 127.0.0.1:8000 tests/fixtures/http-router.php
```

Leave this window open.

This is a separate PHP development server. It is not the Apache server provided by WAMP64.

### Step 4: Run the Integration Tests

Open a second Command Prompt window in the same project directory and run:

```bash
composer test:integration
```

These tests check the connection to MariaDB and send real HTTP requests to the API.

If the database configuration, MariaDB, or the HTTP server is unavailable, some integration tests may be skipped.

You can still run the unit tests at any time with:

```bash
composer test
```

## Publish the Project on GitHub

You can publish the source code, tests, `composer.json`, `composer.lock`, example configuration files, documentation, and SQL scripts that contain no personal data.

Make sure your `.gitignore` file contains at least:

```gitignore
.env
.env.testing
/vendor/
```

Do not upload `.env` or `.env.testing` to GitHub because these files contain settings specific to your computer.

The `.env.example` and `.env.testing.example` files are public templates. They must not contain private passwords.

Do not upload SQL backups containing real personal or financial data.

## Architecture and Limitations

- Read operations (`Queries`) and write operations (`Commands`) are separated. This is a simplified approach inspired by CQRS (Command Query Responsibility Segregation).
- Unit tests run independently. Integration tests require a database and an HTTP server.
- The frontend and API must be served from the same domain. CORS is not configured.
- The application does not support multiple users and is not yet suitable for managing real personal finances.
- Future improvements could include stronger security, user authentication, and deployment testing on a PHP/MariaDB hosting provider.

## Author

This project was created as part of a software development assessment and a learning exercise focused on building a personal finance management application.
