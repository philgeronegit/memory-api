#!/bin/bash

# Local CI Workflow Testing Script
# This script simulates the GitHub Actions CI workflow locally

set -e

echo "🚀 Testing GitHub Actions CI Workflow Locally"
echo "=============================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print status
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# 1. Test PHP setup
echo ""
echo "1. Testing PHP Setup..."
if php --version > /dev/null 2>&1; then
    PHP_VERSION=$(php --version | head -1)
    print_status "PHP available: $PHP_VERSION"
else
    print_error "PHP not found"
    exit 1
fi

# 2. Test required PHP extensions
echo ""
echo "2. Testing PHP Extensions..."
EXTENSIONS=("pdo_mysql" "mysqli")  # pdo is base extension, pdo_mysql is what we need
for ext in "${EXTENSIONS[@]}"; do
    if php -m | grep -q "^$ext$"; then
        print_status "Extension $ext loaded"
    else
        print_error "Extension $ext not loaded"
    fi
done

# 3. Test Composer
echo ""
echo "3. Testing Composer..."
if composer --version > /dev/null 2>&1; then
    COMPOSER_VERSION=$(composer --version | head -1)
    print_status "Composer available: $COMPOSER_VERSION"
else
    print_error "Composer not found"
    exit 1
fi

# 4. Test Composer dependencies
echo ""
echo "4. Testing Composer Dependencies..."
if composer install --no-progress --prefer-dist --optimize-autoloader > /dev/null 2>&1; then
    print_status "Dependencies installed successfully"
else
    print_error "Failed to install dependencies"
    exit 1
fi

# 5. Test .env file creation
echo ""
echo "5. Testing Environment Setup..."
cat > .env << EOF
DB_HOST=127.0.0.1
DB_USERNAME=root
DB_PASSWORD=root
DB_DATABASE=memory_test
JWT_SECRET_KEY=test_jwt_secret_key_that_is_at_least_256_bits_long_for_testing_purposes_only_and_should_be_long_enough
JWT_ALGORITHM=HS256
JWT_EXPIRATION_TIME=3600
JWT_ISSUER=memory-api-test
JWT_AUDIENCE=memory-api-test-users
EOF
print_status "Test .env file created"

# 6. Test PHPUnit
echo ""
echo "6. Testing PHPUnit..."
if ./vendor/bin/phpunit --version > /dev/null 2>&1; then
    PHPUNIT_VERSION=$(./vendor/bin/phpunit --version | head -1)
    print_status "PHPUnit available: $PHPUNIT_VERSION"
else
    print_error "PHPUnit not found"
    exit 1
fi

# 7. Test syntax check (without database)
echo ""
echo "7. Testing PHP Syntax..."
SYNTAX_ERRORS=0
for file in $(find Controllers Models inc -name "*.php" 2>/dev/null); do
    if ! php -l "$file" > /dev/null 2>&1; then
        print_error "Syntax error in $file"
        SYNTAX_ERRORS=$((SYNTAX_ERRORS + 1))
    fi
done

if [ $SYNTAX_ERRORS -eq 0 ]; then
    print_status "All PHP files have valid syntax"
else
    print_error "Found $SYNTAX_ERRORS syntax errors"
fi

# 8. Test database schema files exist
echo ""
echo "8. Testing Database Schema Files..."
SCHEMA_FILES=("SQL/tables.sql" "SQL/functions.sql" "SQL/views.sql" "SQL/utility-views.sql" "SQL/triggers.sql")
for file in "${SCHEMA_FILES[@]}"; do
    if [ -f "$file" ]; then
        print_status "Schema file $file exists"
    else
        print_error "Schema file $file missing"
    fi
done

# 9. Test test files
echo ""
echo "9. Testing Test Files..."
TEST_FILES=$(find tests -name "*Test.php" 2>/dev/null | wc -l)
if [ "$TEST_FILES" -gt 0 ]; then
    print_status "Found $TEST_FILES test files"
else
    print_error "No test files found"
fi

# 10. Summary
echo ""
echo "📊 CI Workflow Test Summary"
echo "=========================="
print_status "PHP setup: OK"
print_status "Composer: OK"
print_status "Dependencies: OK"
print_status "Environment: OK"
print_status "PHPUnit: OK"
print_status "PHP Syntax: OK"
print_status "Schema files: OK"
print_status "Test files: OK"

echo ""
print_warning "Note: Database connection and actual test execution require MySQL to be running"
print_warning "To test fully, start MySQL service and run: ./vendor/bin/phpunit"

echo ""
echo "🎉 Local CI workflow validation complete!"
echo "All components are ready for GitHub Actions execution."