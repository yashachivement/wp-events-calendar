# WP Events Calendar – Event Management & Booking by Yash

[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759B?style=flat-square&logo=wordpress&logoColor=white)](https://wordpress.org/)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg?style=flat-square)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Release-v1.0.0-green.svg?style=flat-square)](https://github.com/yashachivement/wp-events-calendar/releases)
[![Author](https://img.shields.io/badge/Author-Yash-orange?style=flat-square&logo=github)](https://github.com/yashachivement)
[![Website](https://img.shields.io/badge/Website-yashwebdesigner.in-brightgreen?style=flat-square)](https://yashwebdesigner.in)

A modern, high-performance, and feature-rich WordPress event management and booking plugin. Built with modern PHP 8.3 and WordPress best practices, it provides complete event scheduling, ticket bookings, payment gateway processing (PayPal & Stripe), bidirectional Google Calendar synchronization, 6 responsive views, 3 clean design templates, and iCal/CSV import & export.

---

## 📑 Table of Contents

- [Features](#-features)
- [Requirements](#-requirements)
- [Installation](#-installation)
- [Quick Start & Usage](#-quick-start--usage)
  - [Shortcodes](#shortcodes)
- [Configuration & Settings](#-configuration--settings)
  - [Booking & Payments Setup](#booking--payments-setup)
  - [Google Calendar Sync](#google-calendar-sync)
  - [Import & Export](#import--export)
- [Project Architecture](#-project-architecture)
- [Author & Credits](#-author--credits)
- [License](#-license)

---

## ✨ Features

### 📅 Calendar Views & Templates
- **6 Display Views:** Month, Week, Day, List, Summary, and Photo Grid.
- **3 Visual Templates:** Classic, Modern, and Minimalist styling.
- **Dynamic Filtering:** Filter events by category, date, or search query.
- **Responsive Layout:** 100% mobile-friendly and touch-enabled.
- **Color Customization:** Built-in color picker for Primary, Secondary, Background, and Text colors to match any WordPress theme.

### 🎟️ Booking & Capacity Management
- Enable or disable booking on a per-event basis.
- Set total ticket capacities and automatic real-time sold-out tracking.
- Collect attendee contact details (Name, Email, Phone, Number of tickets, Notes).
- Admin booking management dashboard with booking statuses (Confirmed, Pending, Cancelled).

### 💳 Integrated Payment Gateways
- **Stripe:** Direct credit/debit card checkout with secure token handling.
- **PayPal Standard:** Seamless redirection and instant payment notification.
- **Multi-Currency Support:** Over 30 world currencies supported with customizable symbol placement (before/after).

### 🔄 Google Calendar 2-Way Sync
- Connect via Google Cloud OAuth2 client credentials.
- Export WordPress events to your Google Calendar automatically.
- Import Google Calendar events into WordPress.
- Synchronize updates bi-directionally with a single click.

### 🗺️ Venue & Multiple Organizers
- Full venue management with street address, city, state, postal code, and country.
- Integrated Google Maps embed with a direct "Get Directions" link.
- Support for multiple organizers per event with website, email, and phone contact details.

### 📤 Import & Export Tools
- Import events from CSV and standard iCal (`.ics`) files.
- Export all events to CSV or `.ics` formats.
- **Live iCal Feed URL:** Enable users to subscribe to your events feed directly via Apple Calendar, Google Calendar, or Microsoft Outlook.

### 🛡️ Security & Performance
- Built-in WordPress Transient Caching with a one-click admin cache clear utility.
- Strict WordPress security standards: nonces on all forms and AJAX requests, capability checks (`edit_posts`, `manage_options`), output escaping, and input sanitization.

---

## ⚙️ Requirements

| Requirement | Minimum Version | Recommended |
| :--- | :--- | :--- |
| **WordPress** | 6.0+ | 6.5+ / 7.0 |
| **PHP** | 8.3+ | 8.3+ |
| **Database** | MySQL 8.0+ / MariaDB 10.5+ | MySQL 8.0+ |

---

## 🚀 Installation

### Method 1: Via Git (Recommended for Developers)

1. Navigate to your WordPress plugins directory:
   ```bash
   cd wp-content/plugins/
   ```
2. Clone this repository:
   ```bash
   git clone https://github.com/yashachivement/wp-events-calendar.git
   ```
3. Activate the plugin in your WordPress Admin:
   - Go to **Plugins > Installed Plugins**
   - Locate **WP Events Calendar – Event Management & Booking by Yash** and click **Activate**.

### Method 2: Manual ZIP Upload

1. Download the repository as a ZIP file from GitHub (`Code > Download ZIP`).
2. Log into your WordPress Admin Dashboard.
3. Go to **Plugins > Add New Plugin > Upload Plugin**.
4. Choose the downloaded ZIP file and click **Install Now**.
5. Click **Activate Plugin**.

---

## 📖 Quick Start & Usage

Once activated, you will see the **Events Calendar** menu in your WordPress Admin sidebar:
1. Go to **Events Calendar > Add New Event** to create your first event.
2. Fill in the event title, description, start/end dates, venue, organizers, and ticket settings.
3. Publish the event.
4. Display the calendar on any page or post using the shortcodes below.

### Shortcodes

#### 1. Full Calendar View
Display the interactive event calendar:
```text
[wpec_calendar]
```

**Attributes:**
```text
[wpec_calendar view="month" template="modern" per_page="10" category="tech-events"]
```
- `view`: `month` | `week` | `day` | `list` | `summary` | `photo` (Default: `month`)
- `template`: `classic` | `modern` | `minimal` (Default: `modern`)
- `per_page`: Number of events to show (Default: `10`)
- `category`: Category slug to filter events (Optional)

#### 2. Compact Upcoming Events List
Ideal for widget areas, sidebars, or landing pages:
```text
[wpec_events_list limit="5" upcoming="true"]
```

---

## 🔧 Configuration & Settings

Navigate to **Events Calendar > Settings** in the WordPress admin menu:

### Booking & Payments Setup
1. Go to **Settings > Booking & Payment**.
2. Toggle **Enable Bookings**.
3. Choose your active payment gateway (**PayPal** or **Stripe**).
4. For Stripe: Enter your **Stripe Publishable Key** and **Secret Key**.
5. For PayPal: Enter your **PayPal Merchant Email** and set environment (**Sandbox** or **Live**).
6. Set your default currency and currency symbol position.

### Google Calendar Sync
1. Create a project in the [Google Cloud Console](https://console.cloud.google.com/).
2. Enable the **Google Calendar API**.
3. Create OAuth 2.0 Credentials (Web Application) and add the Redirect URI shown in **Events Calendar > Settings > Google Calendar**.
4. Enter your **Client ID** and **Client Secret**, then click **Connect Google Calendar**.

### Import & Export
- Head to **Events Calendar > Import / Export** to import existing events from `.csv` or `.ics` files.
- Copy your unique public iCal subscription URL to distribute to users.

---

## 📁 Project Architecture

```text
wp-events-calendar/
├── admin/                     # Admin backend pages, assets, and settings
│   ├── css/                   # Admin CSS styles
│   ├── js/                    # Admin AJAX & UI JavaScript
│   └── class-wpec-admin.php   # Admin controller & menus
├── assets/                    # Public static assets (CSS, JS, images)
├── includes/                  # Core logic and classes
│   ├── class-wpec-activator.php       # DB tables & activation hooks
│   ├── class-wpec-booking.php         # Booking logic & capacity
│   ├── class-wpec-cache.php           # Transient cache management
│   ├── class-wpec-google-calendar.php # Google OAuth2 & sync
│   ├── class-wpec-helpers.php         # Date, currency, sanitization helpers
│   ├── class-wpec-import-export.php   # CSV & iCal parsers/exporters
│   ├── class-wpec-meta-boxes.php      # Custom event fields & meta boxes
│   ├── class-wpec-payment.php         # Stripe & PayPal processing
│   ├── class-wpec-post-type.php       # Custom Post Type 'wpec_event'
│   ├── class-wpec-security.php        # Nonces, capabilities, sanitization
│   ├── class-wpec-settings.php        # Plugin settings API
│   ├── class-wpec-shortcode.php       # Shortcode renderers
│   └── class-wpec-taxonomies.php      # Categories & tags taxonomies
├── languages/                 # Translation files (.pot, .po, .mo)
├── public/                    # Frontend templates & views
│   ├── views/                 # Calendar templates (month, week, day, list, photo)
│   └── class-wpec-public.php  # Public hooks & assets enqueue
├── index.php                  # Security silence index
├── LICENSE                    # GPL v2 License
├── README.md                  # GitHub documentation (this file)
├── readme.txt                 # WordPress.org standard readme
└── wp-events-calendar.php     # Main plugin entry point & bootstrap
```

---

## 👨‍💻 Author & Credits

Developed with ❤️ by **Yash**.

- **Portfolio & Website:** [yashwebdesigner.in](https://yashwebdesigner.in)
- **GitHub Profile:** [@yashachivement](https://github.com/yashachivement)
- **Project Repository:** [github.com/yashachivement/wp-events-calendar](https://github.com/yashachivement/wp-events-calendar)

If you find this project helpful, please consider giving it a ⭐️ on GitHub!

---

## 📄 License

This project is licensed under the **GNU General Public License v2.0 or later** (GPLv2+) in compliance with WordPress licensing guidelines. See the [LICENSE](LICENSE) file for complete details.

