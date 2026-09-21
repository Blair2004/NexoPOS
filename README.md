[![Latest Stable Version](https://poser.pugx.org/blair2004/nexopos/v)](https://packagist.org/packages/blair2004/nexopos) [![Total Downloads](https://poser.pugx.org/blair2004/nexopos/downloads)](https://packagist.org/packages/blair2004/nexopos) [![Latest GitHub Release](https://img.shields.io/github/v/release/Blair2004/NexoPOS)](https://github.com/Blair2004/NexoPOS/releases) [![License](https://img.shields.io/github/license/Blair2004/NexoPOS)](LICENSE)

<p align="center">
  <img src="https://user-images.githubusercontent.com/5265663/162700085-40ed00ca-9154-42cb-850a-ccf1c2db2d5d.png" alt="NexoPOS"/>
</p>

# NexoPOS

NexoPOS is a modern, extensible point-of-sale platform for retail stores, restaurants, cafés, warehouses, and other product- or service-based businesses. It brings sales, inventory, customers, purchasing, accounting, reporting, and staff operations together in one application.

The current ecosystem is built around the NexoPOS 6.2.x core and includes first-party modules, Windows applications, cloud hosting, developer tools, and the [My NexoPOS](https://my.nexopos.com/en) marketplace.

## What the core provides

The NexoPOS core includes:

- Product, category, unit, tax, and customer management
- POS sales, orders, payments, receipts, refunds, and registers
- Inventory, procurement, stock history, and low-stock workflows
- Accounting and operational reporting, including configurable stock and cost visibility
- Customer groups, rewards, coupons, and store configuration
- User roles, permissions, activity tracking, and audit-friendly workflows
- Barcode and scale barcode support
- Media management, dashboard widgets, and extensibility APIs
- A module system for adding business-specific features without changing the core

NexoPOS is designed to start as a single-store POS and grow with the business. Multi-store, restaurant, service, workforce, rental, AI, printing, and other workflows are delivered through modules.

## Marketplace modules and ecosystem

The modules below are distributed separately from the NexoPOS core through the [NexoPOS Marketplace](https://my.nexopos.com/en/marketplace). They are not included in this repository; install them from the marketplace when they match your NexoPOS version.

<p align="center">
<img src="https://my.nexopos.com/storage/2026/09/neoxpos-marketplace.jpg">
</p>

| Module | Adds |
| --- | --- |
| [**BellSera**](https://my.nexopos.com/en/marketplace/item/bellsera-spa-salon-booking-management-for-nexopos) | Service appointments, public booking, staff and room availability, walk-in and booking POS order types, service queues, and reporting |
| [**Bulk Editor**](https://my.nexopos.com/en/marketplace/item/bulk-editor-for-nexopos) | Bulk editing tools for supported NexoPOS resources |
| [**Gastro**](https://my.nexopos.com/en/marketplace/item/gastro-restaurant-extension-for-nexopos) | Restaurant tables, waiter and chef workflows, kitchen screens, order routing, and product modifiers |
| [**Google Authenticator**](https://my.nexopos.com/en/marketplace/item/google-authenticator) | Time-based two-factor authentication for user accounts |
| [**MultiStore**](https://my.nexopos.com/en/marketplace/item/multistore-for-nexopos) | Multiple isolated stores managed from one NexoPOS installation and database |
| [**Options Export/Import**](https://my.nexopos.com/en/marketplace/item/options-importexport) | Exporting and importing module settings between installations |
| [**Oxen**](https://my.nexopos.com/en/marketplace/item/oxen-ai-agent-for-nexopos) | Permission-aware AI assistance, approved store-management actions, reports, and MCP access |
| [**PIN Login**](https://my.nexopos.com/en/marketplace/item/pin-login-module-for-nexopos) | Fast POS PIN login and inactivity security locking |
| [**NPS Adapter**](https://my.nexopos.com/en/marketplace/item/nps-adapter-for-nexopos) | NexoPOS-side integration for local and network printing workflows |
| [**QR Menu**](https://my.nexopos.com/en/marketplace/item/qr-menu-digital-restaurant-menu-for-gastro) | Branded, mobile-friendly digital menus generated from the NexoPOS catalog |
| [**Quick Store Configuration**](https://my.nexopos.com/en/marketplace) | Guided setup for essential store settings |
| [**Rental**](https://my.nexopos.com/en/marketplace/item/nexopos-rental) | Rental reservations, agreements, inventory movements, returns, maintenance, and notifications |
| [**Sales Commissions**](https://my.nexopos.com/en/marketplace/item/sales-commissions) | Commission rules and sales-staff commission tracking |
| [**WorkForce**](https://my.nexopos.com/en/marketplace) | Kiosk attendance, breaks, schedules, timesheets, corrections, and private reports |

Some modules have optional integrations, such as SMS, email, Nexo Print Server, or PIN Login. Check the marketplace listing for each module's supported NexoPOS core range and dependencies.

The [NexoPOS Marketplace](https://my.nexopos.com/en/marketplace) is the authoritative catalog for compatible extensions, native applications, bundles, releases, and licenses. Alongside the modules above, examples of currently available ecosystem products include:

- **[Raw Material Tracker](https://my.nexopos.com/en/marketplace/item/raw-material-tracker)** — recipes, ingredient stock deduction, unit conversion, cost tracking, and consumption history.
- **[Racks Manager](https://my.nexopos.com/en/marketplace/item/racks-manager)** — structured warehouse storage with racks, shelves, and stock movement controls.
- **[Hotel Booking](https://my.nexopos.com/en/marketplace/item/hotel-booking)** — reservations, rooms, pricing, invoices, POS payments, and calendar synchronization.
- **[NexoPOS Rental](https://my.nexopos.com/en/marketplace/item/nexopos-rental)** — rental operations for businesses that need reservations and returns.
- **[QR Menu](https://my.nexopos.com/en/marketplace/item/qr-menu-digital-restaurant-menu-for-gastro)** — customer-facing digital menus for restaurants, cafés, bars, hotels, and bakeries.
- **[Oxen](https://my.nexopos.com/en/marketplace/item/oxen-ai-agent-for-nexopos)** — AI assistance grounded in store data with permission checks and approval-controlled mutations.
- **[Options Import/Export](https://my.nexopos.com/en/marketplace/item/options-importexport)** — portable module configuration.

For installation and updates, use **Modules → Upload Module** in NexoPOS and confirm that the module supports your core version. Download modules from trusted sources such as [My NexoPOS](https://my.nexopos.com/en/marketplace).

## Cloud, self-hosted, and Windows deployments

Choose the operating model that fits your business:

- **[NexoPOS Cloud](https://nexopos.cloud)** — managed hosting with the infrastructure handled for you.
- **Self-hosted NexoPOS** — deploy on your own server or hosting provider for full control. The supported environment includes PHP 8.2+, MariaDB 10.11 or MySQL 8.4, Nginx or Apache, Composer, scheduled tasks, and a queue worker such as Supervisor.
- **[NexoPOS for Windows](https://my.nexopos.com/en/marketplace/item/nexopos-for-windows)** — an all-in-one local Windows environment with NexoPOS, Nginx, HTTPS, SQLite, and printing tools preconfigured.
- **[NexoPOS Client for Windows](https://my.nexopos.com/en/marketplace/item/nexopos-client-for-windows)** — securely connects additional computers on the same local network to a NexoPOS for Windows server.
- **[Nexo Print Server](https://my.nexopos.com/en/marketplace/item/nexo-print-server)** — direct Windows printing for ESC/POS-compatible thermal printers, used with the NPS Adapter module.
- **[Nexo SaaS](https://my.nexopos.com/en/marketplace/item/nexo-saas-build-and-manage-a-nexopos-hosting-business/details)** — deploy and manage multiple NexoPOS installations, subscriptions, domains, backups, and module bundles as a hosted service.

See the [official documentation](https://my.nexopos.com/en/documentation) for environment, installation, Windows, cloud, and local-network guides.

## Quick start for developers

NexoPOS is a Laravel application using Vue, Vite, Tailwind CSS, and a first-class module architecture. The root project currently targets PHP 8.2+ and Node.js 24.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan storage:link
npm install
npm run build
```

Configure the database and application URL in `.env`, then run the normal Laravel migrations and workers required by your deployment. The exact production setup depends on whether you use queues, scheduled reports, Reverb, multi-store, printing, or other modules.

Create a new module with the built-in generator:

```bash
php artisan make:module
```

Custom module source normally lives under `modules/` and contains its own routes, providers, migrations, resources, assets, settings, and tests. Start with the [module creation guide](https://my.nexopos.com/en/documentation/developers-guide/module-creation/creating-module), then see the [module config guide](https://my.nexopos.com/en/documentation/developers-guide/module-creation/module-config-file), [dashboard menu guide](https://my.nexopos.com/en/documentation/developers-guide/module-creation/dashboard-menus), and [NexoPOS API documentation](https://docs.api.nexopos.com).

NexoPOS also documents extension points for dashboard menus and widgets, Vue components, POS cart buttons and product mutation, receipts and invoices, settings, events, and MCP integrations.

## Demo and resources

- [Try NexoPOS](https://demo.nexopos.com)
- [My NexoPOS](https://my.nexopos.com/en)
- [Official documentation](https://my.nexopos.com/en/documentation)
- [Marketplace](https://my.nexopos.com/en/marketplace)
- [NexoPOS REST API](https://docs.api.nexopos.com)
- [GitHub releases](https://github.com/Blair2004/NexoPOS/releases)

## License

NexoPOS is open source software licensed under the [GNU General Public License v3.0](LICENSE).
