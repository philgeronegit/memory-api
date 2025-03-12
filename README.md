# Memory REST API

## Overview

The Memory project is a note-taking application that allows users to create notes, tag them, and add comments.

The primary goal is to facilitate the sharing of information, ideas, technical issues, and suggestions in a centralized and structured manner. The software enables each developer to leave notes for other team members, whether to report a problem, share a tip, or seek advice.

This project is built using PHP and follows a REST API architecture.

## Features

- Create, read, update, and delete notes
- Tag notes with multiple tags
- Add comments to notes
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

5. Run the database migrations to set up the database schema:

   ```sh
   php vendor/bin/phinx migrate
   ```

## Usage

To start the application, you can use the built-in PHP server:

```sh
php -S localhost:8000 -t public
```

The API will be accessible at http://localhost:8000.

## API Endpoints

### Notes

- GET /api/notes - Retrieve all notes
- GET /api/notes/{id} - Retrieve a single note by ID
- POST /api/notes - Create a new note
- PUT /api/notes/{id} - Update a note by ID
- DELETE /api/notes/{id} - Delete a note by ID

### Tags

- GET /api/tags - Retrieve all tags
- GET /api/tags/{id} - Retrieve a single tag by ID
- POST /api/tags - Create a new tag
- PUT /api/tags/{id} - Update a tag by ID
- DELETE /api/tags/{id} - Delete a tag by ID

### Comments

- GET /api/comments - Retrieve all comments
- GET /api/comments/{id} - Retrieve a single comment by ID
- POST /api/comments - Create a new comment
- PUT /api/comments/{id} - Update a comment by ID
- DELETE /api/comments/{id} - Delete a comment by ID

## Environment Variables

This project uses vlucas/phpdotenv to manage environment variables. Make sure to create a .env file in the root directory and configure the following variables:

- DB_HOST - Database host
- DB_NAME - Database name
- DB_USER - Database user
- DB_PASS - Database password
- APP_ENV - Application environment (e.g., local, production)
