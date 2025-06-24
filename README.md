# ShopBack Laravel API

Laravel API integration for ShopBack's In-Store Payments system, providing secure HMAC-authenticated endpoints for QR code payment processing, order management, and transaction handling.

## Project Description

This Laravel application serves as a robust middleware API that integrates with ShopBack's In-Store Payments platform, enabling merchants to process QR code-based payments seamlessly. The project implements all core ShopBack API endpoints with proper HMAC-SHA256 authentication, request validation, and error handling.

### What This Application Does

- **QR Code Payment Processing**: Create dynamic QR codes for merchant-presented payments and process consumer-presented QR codes
- **Order Management**: Complete order lifecycle management including status tracking, refunds, and cancellations
- **Secure Authentication**: Implements ShopBack's HMAC-SHA256 signature authentication with proper content digest validation
- **API Standardization**: Provides consistent REST API endpoints that follow Laravel best practices and ShopBack's response formats

### Technologies Used

- **Laravel 12.0** with **PHP 8.2+**: Modern PHP framework for robust API development
- **SQLite Database**: Lightweight database solution for development and testing
- **GuzzleHTTP**: HTTP client for reliable API communication with ShopBack services
- **Carbon**: Advanced date/time handling for ISO-8601 timestamp formatting
- **Laravel Pint**: Code styling and linting for consistent code quality
- **Vite + TailwindCSS**: Asset compilation and styling framework

## Installation and Setup

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js and npm
- SQLite3

### Step-by-Step Installation

1. **Clone the Repository**
   ```bash
   git clone git@github.com:wanafiq/shopback-laravel-api.git
   cd shopback-laravel-api
   ```

2. **Install Dependencies**
   ```bash
   # Install PHP dependencies
   composer install
   
   # Install Node.js dependencies
   npm install
   ```

3. **Environment Configuration**
   ```bash
   # Copy environment file (automatically done by post-install script)
   cp .env.example .env
   
   # Generate application key (automatically done by post-install script)
   php artisan key:generate
   ```

4. **Configure ShopBack Credentials**
   
   Update your `.env` file with ShopBack API credentials:
   ```bash
   SHOPBACK_ACCESS_KEY=your_access_key_here
   SHOPBACK_ACCESS_KEY_SECRET=your_secret_key_here
   SHOPBACK_BASE_URL=https://integrations-sandbox.shopback.com/posi-sandbox
   ```
   
5. **Start Development Environment**
   ```bash
   # Start full development environment (server, queue, logs, assets)
   composer run dev
   ```

   This command starts:
   - Laravel development server on `http://localhost:8000`
   - Queue worker for background jobs
   - Real-time log viewer
   - Asset compilation in watch mode

## How to Use the Project

### Available API Endpoints

The API provides five core endpoints for ShopBack integration:

#### 1. Health Check
```bash
GET /api/health
```
Returns service status and timestamp for monitoring.

#### 2. Create Dynamic QR Order
```bash
POST /api/shopback/orders/create
Content-Type: application/json

{
    "posId": "110e6f8b-20a8-42fe-9577-8b48b3bd19d1",
    "country": "MY",
    "amount": 1234,
    "currency": "MYR",
    "referenceId": "ref-1",
    "qrType": "payload",
    "partner": {
        "merchantId": "TEST_MERCHANT",
        "merchantCategoryCode": 5411,
        "merchantTradingName": "Test Merchant Sdn Bhd",
        "merchantEntityId": "123456789"
    },
    "orderMetadata": {
        "terminalReference": "T000001",
        "merchantOrderReference": "mref-1"
    }
}
```

#### 3. Scan Consumer QR
```bash
POST /api/shopback/orders/scan
Content-Type: application/json

{
    "posId": "110e6f8b-20a8-42fe-9577-8b48b3bd19d1",
    "country": "MY",
    "amount": 1234,
    "currency": "MYR",
    "referenceId": "ref-scan-1",
    "consumerQrPayload": "00020101021226370011sg.shopback-consumer-qr",
    "partner": {
        "merchantId": "TEST_MERCHANT",
        "merchantCategoryCode": 5411,
        "merchantTradingName": "Test Merchant Sdn Bhd",
        "merchantEntityId": "123456789"
    }
}
```

#### 4. Get Order Status
```bash
GET /api/shopback/orders/ref-1
```

#### 5. Refund Order
```bash
POST /api/shopback/orders/ref-1/refund
Content-Type: application/json

{
    "amount": 1234,
    "reason": "Customer requested refund",
    "referenceId": "ref-1",
    "posId": "110e6f8b-20a8-42fe-9577-8b48b3bd19d1",
    "refundMetadata": {
        "terminalReference": "T000001"
    }
}
```

#### 6. Cancel Order
```bash
POST /api/shopback/orders/ref-1/cancel
Content-Type: application/json

{
    "reason": "Customer requested cancellation"
}
```

### Development Commands

#### Primary Development Workflow
```bash
# Start full development environment
composer run dev

# Run tests
composer run test

# Asset compilation
npm run dev    # Development mode
npm run build  # Production build
```

#### Individual Commands
```bash
# Start development server only
php artisan serve

# Run database migrations
php artisan migrate

# View real-time logs
php artisan pail --timeout=0

# Process background jobs
php artisan queue:listen --tries=1
```

### Authentication

All ShopBack API endpoints automatically include:
- `Authorization` header with HMAC-SHA256 signature
- `Date` header in ISO-8601 format
- Proper content digest validation

The HMAC service includes debug logging to help troubleshoot signature issues during development.

### Error Handling

All endpoints return consistent error responses following ShopBack's format:
```json
{
    "statusCode": 400,
    "message": "Error description",
}
```

### Monitoring and Debugging

- **Real-time logs**: Use `php artisan pail --timeout=0` to monitor application logs
- **HMAC debugging**: Signature generation details are logged for troubleshooting
- **Health check**: Monitor service status at `/api/health`

For additional configuration and advanced usage, refer to the `CLAUDE.md` file in the project root.

## Project Structure

```
shopback-laravel-api/
├── app/
│   ├── Http/Controllers/
│   │   └── ShopBackOrderController.php    # Main API controller
│   └── Services/
│       └── ShopBackHmacService.php        # HMAC authentication service
├── routes/
│   └── api.php                            # API route definitions
├── config/
│   └── services.php                       # ShopBack configuration
├── CLAUDE.md                              # Development guidelines
└── README.md                              # This file
```
