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

# Run single test file / single test
php artisan test --filter=DeliveryEntryApiTest
php artisan test --filter=DeliveryEntryApiTest::test_method_name

# Clear all caches
php artisan cache:clear && php artisan config:clear && php artisan view:clear
```

Caches can also be cleared/rebuilt from the running app's UI — `CacheController`
(routes `cache.clear`, `cache.build-vue`, `cache.clear-and-build`) shells out to
`npm run prod` so staff can rebuild Vue assets without terminal access.

## Architecture

### Backend (Laravel 8)

- **Controllers**: Split between web (`app/Http/Controllers/`) and API (`app/Http/Controllers/Api/`)
- **Models**: Core domain models in `app/Models/`
  - `Order` - Central model with complex tag handling logic for clothing items
  - `Invoice` - Billing/invoice management with carry-over calculations
  - `Customer` - Customer management with cutoff date settings
  - `Clothes` - Clothing item catalog
  - `OrderClothes` - Pivot table linking orders to clothes with tag numbers
  - `DeliveryNote` / `DeliveryCustomer` / `DeliveryDailyEntry` / `DeliveryDepartment` / `DeliveryProduct` / `DeliveryEmailLog` - Separate delivery-note (納品書) subsystem with its own customer/product master, per-customer sequential note numbering (`DeliveryNote::createWithNumber` uses `lockForUpdate` in a transaction), PDF generation (dompdf), and email sending with logging
  - `ClientError` - Stores JS errors collected from the browser
- **Routes**: Web routes require `auth`; API routes use Sanctum (`auth:sanctum`). Exceptions: `customer_display`, `login.auto` (auto-login, `guest` only), and `client-error` (unauthenticated error ingestion) are public.
- Most domain logic lives in the **Order/Invoice models**, not controllers — tag sorting/merging, carry-over math, and cutoff handling are model methods.

### Frontend (Vue.js 2)

- Vue components in `resources/js/components/`
- Components registered globally in `resources/js/app.js`
- Key components:
  - `OrderComponent.vue` - Main order entry interface
  - `CustomerDisplayComponent.vue` - Customer-facing display
  - `InvoiceComponent.vue` - Invoice management
  - `ReturnComponent.vue` - Item return handling
  - `DeliveryNoteComponent.vue` / `DeliverySpEntryComponent.vue` - Delivery-note management and mobile (SP) entry
  - `Functions/ReceiptPrinter.vue` - Wraps the EPSON ePOS SDK (`epson.ePOSDevice`); reusable receipt-printing logic
  - `Modals/` - Shared modal dialogs (accounting, discount, change, invoice operations, etc.)
- Components are registered globally in `resources/js/app.js` with kebab-case names (e.g. `order-component`); add new components there.
- `resources/js/utils/ErrorLogger.js` hooks `window.onerror`, unhandled promise rejections, and `console.error`/`warn`, batching them to the `client-error` API endpoint.
- Real-time updates via Pusher/Laravel Echo (customer-facing display is driven by the `customer_display.broadcast` API endpoint).

### Key Domain Concepts

- **Tag Numbers**: Format `X-XXX` (e.g., `8-873`). Wraps from `9-999` to `0-001`. Critical for receipt printing and item tracking.
- **Cutoff Dates**: Customer billing cycles end on specific days (99 = end of month)
- **Carry Over**: Invoices can carry unpaid amounts to subsequent periods
- **Managers**: Store staff/managers who process orders (selected via `manager-select` flow before processing)
- **Daily Report** (日報): `DailyReportController` aggregates a day's orders/payments; generated per date.
- **Delivery Notes** (納品書): A parallel B2B billing flow distinct from the main POS order flow — recurring delivery customers, daily entries over a period, then a numbered note that can be PDF'd and emailed.

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
