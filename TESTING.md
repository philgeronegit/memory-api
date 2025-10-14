# Testing

This project includes PHPUnit tests to ensure code quality.

## Running Tests Locally

```sh
# Install dependencies (including dev dependencies)
composer install

# Run the test suite
./vendor/bin/phpunit
```

## Continuous Integration

This project uses GitHub Actions for continuous integration with two workflow options:

### Standard Workflow (ci.yml)
- **Pros**: Simple, fast for small projects, easy to debug
- **Cons**: Installs PHP and dependencies on each run
- **Best for**: Projects with few dependencies or when you want maximum flexibility

### Docker Workflow (ci-docker.yml)
- **Pros**: Faster builds, consistent environment, better caching
- **Cons**: More complex setup, requires Docker knowledge
- **Best for**: Larger projects or when build speed is critical

Both workflows automatically:
- Run on every push to `main` branch and pull requests targeting `main`
- Set up PHP 8.3 and MySQL 8.0
- Install Composer dependencies
- Create a test database with the complete schema
- Run the PHPUnit test suite

### Choosing Between Workflows

**Use the standard workflow (`ci.yml`) if:**
- Your project is relatively small
- You want simplicity and easy debugging
- Build time isn't a major concern

**Use the Docker workflow (`ci-docker.yml`) if:**
- You have many dependencies
- Build speed is important
- You want reproducible builds
- You're already using Docker in your deployment pipeline

### Branch Protection

To prevent merging code with failing tests:

1. Go to repository Settings → Branches
2. Click "Add rule" for the `main` branch
3. Enable "Require status checks to pass before merging"
4. Select the "test" check from the CI workflow

## Testing CI/CD Locally

### Automated Local Testing

Use the provided script to test your CI workflow locally:

```sh
# Make the script executable (first time only)
chmod +x test-ci-local.sh

# Run the local CI validation
./test-ci-local.sh
```

This script validates:
- ✅ PHP version and extensions
- ✅ Composer installation
- ✅ Dependency installation
- ✅ PHPUnit availability
- ✅ PHP syntax validation
- ✅ Database schema files existence
- ✅ Test file discovery

### Manual Testing Steps

If you prefer to test components individually:

```sh
# 1. Test PHP setup
php --version

# 2. Test required extensions
php -m | grep -E "(pdo_mysql|mysqli)"

# 3. Test Composer
composer --version

# 4. Install dependencies
composer install --no-progress --prefer-dist --optimize-autoloader

# 5. Test PHPUnit
./vendor/bin/phpunit --version

# 6. Validate PHP syntax
find Controllers Models inc -name "*.php" -exec php -l {} \;

# 7. Check schema files
ls -la *.sql

# 8. Run syntax-only test (without database)
./vendor/bin/phpunit --check-syntax
```

### Full Integration Testing

For complete testing with database:

```sh
# 1. Start MySQL service (WAMP/XAMPP/MAMP)
# 2. Create test database
mysql -u root -p -e "CREATE DATABASE memory_test;"

# 3. Run schema setup
mysql -u root -p memory_test < SQL/tables.sql
mysql -u root -p memory_test < SQL/functions.sql
mysql -u root -p memory_test < SQL/views.sql
mysql -u root -p memory_test < SQL/utility-views.sql
mysql -u root -p memory_test < SQL/triggers.sql
mysql -u root -p memory_test < SQL/insert.sql

# 4. Create .env file
cat > .env << EOF
DB_HOST=127.0.0.1
DB_USERNAME=root
DB_PASSWORD=your_password
DB_DATABASE=memory_test
JWT_SECRET_KEY=test_jwt_secret_key_that_is_at_least_256_bits_long_for_testing_purposes_only_and_should_be_long_enough
JWT_ALGORITHM=HS256
JWT_EXPIRATION_TIME=3600
JWT_ISSUER=memory-api-test
JWT_AUDIENCE=memory-api-test-users
EOF

# 5. Run full test suite
./vendor/bin/phpunit
```

### Using Docker for Testing

If you want to test the Docker-based workflow:

```sh
# Build the Docker image
docker build -t memory-api-test .

# Test PHP in container
docker run --rm memory-api-test php -v

# Test PHPUnit in container (with mounted code)
docker run --rm -v $(pwd):/app memory-api-test ./vendor/bin/phpunit --check-syntax
```

### Using act CLI (Advanced)

For the most accurate local simulation of GitHub Actions:

```sh
# Install act (if not already installed)
# On Windows with Chocolatey:
choco install act-cli

# Or download from: https://github.com/nektos/act/releases

# Run the workflow locally
act -v

# Run specific workflow
act -j test

# Run with secrets (if needed)
act -s GITHUB_TOKEN=your_token
```

### Troubleshooting

**Common Issues:**

1. **MySQL not running**: Start your MySQL service (WAMP/XAMPP)
2. **Extensions missing**: Install PHP extensions via your PHP manager
3. **Permission denied**: Run `chmod +x test-ci-local.sh`
4. **Docker issues**: Ensure Docker Desktop is running

**Environment Variables:**
- Ensure `.env` file has correct database credentials
- JWT secret must be at least 256 bits long
- Database host should be `127.0.0.1` for local testing

## Test Structure

- `tests/MemoryTestCase.php` - Base test case that sets up the environment
- `tests/BaseControllerTest.php` - Tests for the BaseController class
- `tests/UserModelTest.php` - Tests for the UserModel class
- `tests/LoginControllerTest.php` - Tests for the LoginController class
- `tests/NoteModelTest.php` - Tests for the NoteModel class

## Configuration

The PHPUnit configuration is in `phpunit.xml` which defines:
- Test directory: `tests/`
- Bootstrap file: `tests/MemoryTestCase.php`
- Code coverage for Controllers and Models directories

## Test Database

Tests assume a MySQL database named "memory" is available with the schema loaded. The database connection uses the environment variables defined in `.env`.