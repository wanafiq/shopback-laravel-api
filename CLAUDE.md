# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Primary Development Workflow
```bash
# Start full development environment (server, queue, logs, assets)
composer run dev

# Run tests
composer run test

# Asset compilation
npm run dev    # Development mode
npm run build  # Production build
```

### Individual Laravel Commands
```bash
# Start development server
php artisan serve

# Run migrations
php artisan migrate

# View logs in real-time
php artisan pail --timeout=0

# Process background jobs
php artisan queue:listen --tries=1
```

## Project Architecture

### Laravel API Structure
- **Laravel 12.0** with **PHP 8.2+** requirements
- **API-first approach**: Routes defined in `routes/api.php` with `/api` prefix
- **SQLite database**: Default with MySQL support configured
- **Health check endpoint**: `/api/health` returning JSON status with timestamp

### Key Configuration Files
- `bootstrap/app.php`: Application bootstrap with routing configuration
- API routes must be explicitly registered in bootstrap configuration
- Standard Laravel MVC pattern with controllers in `app/Http/Controllers/`

### Database Setup
- **Default**: SQLite (`database/database.sqlite`)
- **Testing**: In-memory SQLite for test suite
- **Migrations**: Standard Laravel user/cache/jobs tables

### Development Tooling
- **Laravel Pint**: Code styling and linting
- **Laravel Sail**: Docker development environment (configured but not active)
- **Laravel Pail**: Real-time log viewer
- **Vite + TailwindCSS**: Asset compilation and styling
- **Concurrently**: Multi-process development server

## Setup Requirements

### Initial Setup (Automated)
The project includes post-install scripts that automatically:
- Copy `.env.example` to `.env`
- Generate application key
- Create SQLite database file
- Run initial migrations

### Manual Setup Steps
```bash
composer install
npm install
composer run dev  # Starts full development environment
```

### API Routes Registration
When adding new API routes to `routes/api.php`, ensure the bootstrap configuration includes:
```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',  // Required for API routes
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
```

## Testing Configuration

- **PHPUnit**: Configured for Unit and Feature tests
- **Test Database**: In-memory SQLite (separate from development)
- **Test Environment**: Automatically uses `.env.testing` values
- Run with `composer run test` which includes config cache clearing

## Environment Configuration

### Key Environment Variables
```bash
APP_NAME=Laravel
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost
DB_CONNECTION=sqlite  # Default database connection
```

### Development Server
- Default Laravel server runs on `http://localhost:8000`
- Health check available at `/api/health`
- Returns JSON: `{"status":"ok","message":"Service is healthy","timestamp":"..."}`

## ShopBack Integration

### HMAC Signature Service
- **Service**: `App\Services\ShopBackHmacService`
- **Configuration**: `config/services.php` with credentials from environment variables
- **Environment Variables**: 
  - `SHOPBACK_ACCESS_KEY` - ShopBack API access key
  - `SHOPBACK_ACCESS_KEY_SECRET` - ShopBack API secret key

### HMAC Implementation
- **Algorithm**: HMAC-SHA256 following ShopBack API specifications
- **Authorization Format**: `SB1-HMAC-SHA256 {accessKey}:{signature}`
- **Content Digest**: SHA256 hash of alphabetically sorted request body
- **Date Format**: ISO-8601 UTC format (`Y-m-d\TH:i:s.v\Z`)

### Key Methods
```php
// Generate complete signature data
$service->generateSignature($method, $path, $body, $contentType, $date)

// Get authorization header only
$service->getAuthorizationHeader($method, $path, $body, $contentType, $date)

// Validate incoming signatures
$service->validateSignature($providedSignature, $method, $path, $body, $contentType, $date)
```

### Usage Example
```php
$hmacService = new ShopBackHmacService();
$authHeader = $hmacService->getAuthorizationHeader('POST', '/api/endpoint', ['data' => 'value']);
// Returns: "SB1-HMAC-SHA256 {accessKey}:{hmacSignature}"
```