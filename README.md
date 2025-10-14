# Memory REST API

[![CI](https://github.com/philgeronegit/memory-api/workflows/CI/badge.svg)](https://github.com/philgeronegit/memory-api/actions)

## Overview

The Memory project is a note-taking application that allows users to create notes, tag them, and add comments.

The primary goal is to facilitate the sharing of information, ideas, technical issues, and suggestions in a centralized and structured manner. The software enables each developer to leave notes for other team members, whether to report a problem, share a tip, or seek advice.

This project is built using PHP and follows a REST API architecture.

## Features

- Create, read, update, and delete notes
- Tag notes with multiple tags
- Add comments to notes
- JWT token-based authentication
- RESTful API endpoints

## Requirements

- PHP 7.4 or higher
- Composer
- MySQL database

## Installation

1. Clone the repository:

   ```sh
   git clone https://github.com/yourusername/memory.git
   cd memory
   ```

2. Install dependencies using Composer:

   ```sh
   composer install
   ```

3. Create a `.env` file in the root directory and configure your environment variables. You can use the `.env-example` file as a template:

   ```sh
   cp .env-example .env
   ```

4. Update the `.env` file with your database credentials and other configuration settings.

5. Create the database and set up the database schema by running the SQL files in order:

   ```sh
   mysql -u your_username -p -e "CREATE DATABASE IF NOT EXISTS your_database;"
   mysql -u your_username -p your_database < SQL/tables.sql
   mysql -u your_username -p your_database < SQL/functions.sql
   mysql -u your_username -p your_database < SQL/views.sql
   mysql -u your_username -p your_database < SQL/utility-views.sql
   mysql -u your_username -p your_database < SQL/triggers.sql
   mysql -u your_username -p your_database < SQL/insert.sql
   ```

## Testing

This project includes PHPUnit tests to ensure code quality.

### Running Tests Locally

```sh
# Install dependencies (including dev dependencies)
composer install

# Run the test suite
./vendor/bin/phpunit
```

### Continuous Integration

This project uses GitHub Actions for continuous integration with two workflow options:

#### Standard Workflow
- Uses GitHub Actions services for PHP and MySQL setup
- Simple and straightforward
- Good for most projects

#### Docker Workflow
- Uses Docker for consistent environment
- Faster for projects with many dependencies
- Includes a `Dockerfile` for containerized builds

Both workflows:
- Run on every push to `main` branch and pull requests targeting `main`
- Set up PHP 8.3 and MySQL 8.0
- Install Composer dependencies
- Create a test database with the schema
- Run the PHPUnit test suite

### Branch Protection

To prevent merging code with failing tests, set up branch protection rules:

1. Go to your repository settings
2. Navigate to "Branches" → "Branch protection rules"
3. Add a rule for the `main` branch
4. Enable "Require status checks to pass before merging"
5. Select the "test" status check from the CI workflow

## Usage

To start the application, you can use the built-in PHP server:

```sh
php -S localhost:8000 -t public
```

The API will be accessible at http://localhost:8000.

## Authentication

This API uses JWT (JSON Web Token) for authentication. All endpoints except `/login` require a valid JWT token.

### Getting a JWT Token

First, authenticate with your credentials:

```sh
curl -X POST http://localhost:8000/memory/login \
  -H "Content-Type: application/json" \
  -d '{"username": "your_username", "password": "your_password"}'
```

This will return a response containing an `access_token`:

```json
{
  "id_user": 1,
  "username": "your_username",
  "email": "user@example.com",
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

### Using the JWT Token

Include the token in the Authorization header for all subsequent requests:

```sh
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" http://localhost:8000/memory/notes
```

## API Endpoints

### Authentication

- POST /memory/login - Authenticate user and get JWT token

### Comments

- GET /memory/comment - Retrieve all comments
- GET /memory/comment/{id} - Retrieve a single comment by ID
- POST /memory/comment - Create a new comment
- PUT /memory/comment/{id} - Update a comment by ID
- DELETE /memory/comment/{id} - Delete a comment by ID

### Developers

- GET /memory/developer - Retrieve all developers
- GET /memory/developer/{id} - Retrieve a single developer by ID
- POST /memory/developer - Create a new developer
- PUT /memory/developer/{id} - Update a developer by ID
- DELETE /memory/developer/{id} - Delete a developer by ID

### Messages

- GET /memory/message - Retrieve all messages
- GET /memory/message/{id} - Retrieve a single message by ID
- POST /memory/message - Create a new message
- PUT /memory/message/{id} - Update a message by ID
- DELETE /memory/message/{id} - Delete a message by ID

### Notes

- GET /memory/note - Retrieve all notes
- GET /memory/note/{id} - Retrieve a single note by ID
- POST /memory/note - Create a new note
- PUT /memory/note/{id} - Update a note by ID
- DELETE /memory/note/{id} - Delete a note by ID

### Programming Languages

- GET /memory/programming-language - Retrieve all programming languages
- GET /memory/programming-language/{id} - Retrieve a single programming language by ID
- POST /memory/programming-language - Create a new programming language
- PUT /memory/programming-language/{id} - Update a programming language by ID
- DELETE /memory/programming-language/{id} - Delete a programming language by ID

### Projects

- GET /memory/project - Retrieve all projects
- GET /memory/project/{id} - Retrieve a single project by ID
- POST /memory/project - Create a new project
- PUT /memory/project/{id} - Update a project by ID
- DELETE /memory/project/{id} - Delete a project by ID

### Roles

- GET /memory/role - Retrieve all roles
- GET /memory/role/{id} - Retrieve a single role by ID
- POST /memory/role - Create a new role
- PUT /memory/role/{id} - Update a role by ID
- DELETE /memory/role/{id} - Delete a role by ID

### Tags

- GET /memory/tag - Retrieve all tags
- GET /memory/tag/{id} - Retrieve a single tag by ID
- POST /memory/tag - Create a new tag
- PUT /memory/tag/{id} - Update a tag by ID
- DELETE /memory/tag/{id} - Delete a tag by ID

### Tasks

- GET /memory/task - Retrieve all tasks
- GET /memory/task/{id} - Retrieve a single task by ID
- POST /memory/task - Create a new task
- PUT /memory/task/{id} - Update a task by ID
- DELETE /memory/task/{id} - Delete a task by ID
- PUT /memory/task/order - Reorder tasks

### Users

- GET /memory/user - Retrieve all users
- GET /memory/user/{id} - Retrieve a single user by ID
- POST /memory/user - Create a new user
- PUT /memory/user/{id} - Update a user by ID
- DELETE /memory/user/{id} - Delete a user by ID

### Technical Skills

- GET /memory/technical-skill - Retrieve all technical skills
- GET /memory/technical-skill/{id} - Retrieve a single technical skill by ID
- POST /memory/technical-skill - Create a new technical skill
- PUT /memory/technical-skill/{id} - Update a technical skill by ID
- DELETE /memory/technical-skill/{id} - Delete a technical skill by ID

### Uploads

- GET /memory/upload - Retrieve all uploads
- GET /memory/upload/{id} - Retrieve a single upload by ID
- POST /memory/upload - Create a new upload
- PUT /memory/upload/{id} - Update an upload by ID
- DELETE /memory/upload/{id} - Delete an upload by ID

### Nested Endpoints

#### User Resources

- GET /memory/user/{id}/project - Retrieve projects for a specific user
- GET /memory/user/{id}/upload - Retrieve uploads for a specific user
- GET /memory/user/{id}/message - Retrieve messages for a specific user
- GET /memory/user/{id}/note - Retrieve notes for a specific user
- GET /memory/user/{id}/task - Retrieve tasks for a specific user
- GET /memory/user/{id}/technical-skill - Retrieve technical skills for a specific user

#### Note Resources

- GET /memory/note/{id}/comment - Retrieve comments for a specific note
- GET /memory/note/{id}/share - Retrieve shares for a specific note
- GET /memory/note/{id}/tag - Retrieve tags for a specific note
- POST /memory/note/{id}/tag - Add a tag to a specific note
- PUT /memory/note/{id}/tag - Update a tag on a specific note
- DELETE /memory/note/{id}/tag - Remove a tag from a specific note
- POST /memory/note/{id}/score - Add a score to a specific note
- PUT /memory/note/{id}/score - Update the score for a specific note
- POST /memory/note/{id}/share - Share a note with a user

## Environment Variables

This project uses vlucas/phpdotenv to manage environment variables. Make sure to create a .env file in the root directory and configure the following variables:

### Database Configuration

- DB_HOST - Database host
- DB_NAME - Database name
- DB_USER - Database user
- DB_PASS - Database password

### JWT Configuration

- JWT_SECRET_KEY - Secret key for signing JWT tokens (use a strong, random string)
- JWT_ALGORITHM - Algorithm for JWT signing (default: HS256)
- JWT_EXPIRATION_TIME - Token expiration time in seconds (default: 3600)
- JWT_ISSUER - Token issuer identifier
- JWT_AUDIENCE - Token audience identifier

### Application Configuration

- APP_ENV - Application environment (e.g., local, production)
