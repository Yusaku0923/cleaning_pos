# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Cleaning POS is a point-of-sale system for dry cleaning businesses built with Laravel 8 and Vue.js 2. It manages customers, orders, clothing items, invoicing, and receipt printing via EPSON ePOS SDK.

## Development Commands

```bash
# Start development server (requires Docker/Sail)
./vendor/bin/sail up -d

# Run database migrations
php artisan migrate

# Seed database with default data
php artisan db:seed

# Build frontend assets (development)
npm run dev

# Build frontend assets (production)
npm run prod

# Watch for frontend changes with BrowserSync
npm run watch

# Run tests
php artisan test
# or
./vendor/bin/phpunit

# Run single test file
php artisan test --filter=ExampleTest

# Clear all caches
php artisan cache:clear && php artisan config:clear && php artisan view:clear
```

## Architecture

### Backend (Laravel 8)

- **Controllers**: Split between web (`app/Http/Controllers/`) and API (`app/Http/Controllers/Api/`)
- **Models**: Core domain models in `app/Models/`
  - `Order` - Central model with complex tag handling logic for clothing items
  - `Invoice` - Billing/invoice management with carry-over calculations
  - `Customer` - Customer management with cutoff date settings
  - `Clothes` - Clothing item catalog
  - `OrderClothes` - Pivot table linking orders to clothes with tag numbers
- **Routes**: Web routes require auth; API routes use Sanctum authentication

### Frontend (Vue.js 2)

- Vue components in `resources/js/components/`
- Components registered globally in `resources/js/app.js`
- Key components:
  - `OrderComponent.vue` - Main order entry interface
  - `CustomerDisplayComponent.vue` - Customer-facing display
  - `InvoiceComponent.vue` - Invoice management
  - `ReturnComponent.vue` - Item return handling
- Real-time updates via Pusher/Laravel Echo

### Key Domain Concepts

- **Tag Numbers**: Format `X-XXX` (e.g., `8-873`). Wraps from `9-999` to `0-001`. Critical for receipt printing and item tracking.
- **Cutoff Dates**: Customer billing cycles end on specific days (99 = end of month)
- **Carry Over**: Invoices can carry unpaid amounts to subsequent periods
- **Managers**: Store staff/managers who process orders

### Database

- MySQL 8.0 via Laravel Sail
- Migrations in `database/migrations/`
- Seeders for stores, managers, and clothing categories

### Receipt Printing

- Uses EPSON ePOS SDK (JavaScript) for receipt printing
- SDK documentation: `ePOS_SDK_JavaScript_um_ja_revAC.pdf`
- Printer IP configuration via `Ipaddress` model

### Build System

- Laravel Mix (Webpack) configured in `webpack.mix.js`
- BrowserSync proxies to `laravel.test` on port 3000
- SCSS in `resources/sass/`, compiled to `public/css/`
