=== WP Events Calendar – Event Management & Booking by Yash ===
Contributors: yashachivement
Donate link: https://yashwebdesigner.in
Tags: events, calendar, event calendar, booking, tickets, stripe, paypal, google-calendar
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.3
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A powerful, full-featured Events Calendar plugin with bookings, payment gateways, Google Calendar sync, multiple views, and import/export.

== Description ==

WP Events Calendar is a complete event management solution for WordPress. It is built for WordPress 7.0+ and PHP 8.3+.

**Key Features:**

* Add / Edit / Delete events from the WordPress admin
* Feature image per event (Add, Edit, Remove via Media Library)
* Rich event meta: Start & End Date/Time, All-Day toggle, Recurrence
* Venue: Name, Street Address, City, State/Province, Country
* Optional Map with Show/Hide toggle and direct Google Maps link
* Event Website URL and Phone
* Multiple Organizers per event — create new organizers inline or pick existing ones
* Optional Event Cost with 30+ currency support and currency symbol position
* Optional Booking system with ticket capacity management
* Optional Payment Gateways: PayPal Standard, Stripe (Credit/Debit Card)
* 6 Calendar Views: Month, Week, Day, List, Summary, Photo
* 3 Calendar Templates: Classic, Modern, Minimal
* Theme colour picker (Primary, Secondary, Text, Background)
* Date & Time format settings, timezone selector, week start day
* Global Settings page with tabbed interface
* Shortcode Generator: [wpec_calendar] and [wpec_events_list]
* Google Calendar OAuth2 integration (sync events bi-directionally)
* Import & Export: CSV and iCal (.ics) — with public iCal feed URL
* Security: nonce verification, capability checks, data sanitization, secure payment tokens
* Cache management with one-click Clear Cache button
* Compatible with WordPress 6.0+, PHP 8.3+, MySQL 8.0+ / MariaDB 10.5+

**Shortcode Usage:**

`[wpec_calendar]`
`[wpec_calendar view="month" template="modern" per_page="10" category="music"]`
`[wpec_events_list limit="5" upcoming="true"]`

== Installation ==

1. Upload the `wp-events-calendar` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Navigate to **Events Calendar → Settings** to configure
4. Add the shortcode `[wpec_calendar]` to your events page
5. Add events via **Events Calendar → Add New Event**

== Frequently Asked Questions ==

= How do I set up payments? =
Go to **Events Calendar → Settings → Booking & Payment**, enable booking and payment, then enter your PayPal email or Stripe API keys.

= How do I connect Google Calendar? =
Go to **Settings → Google Calendar**, enter your OAuth2 Client ID and Secret from Google Cloud Console, then click **Connect Google Calendar**.

= How do I import events? =
Go to **Events Calendar → Import / Export** and upload a CSV or iCal (.ics) file.

= Can I override templates? =
Yes — copy `public/views/single-event.php` to your theme's `wpec/single-event.php` directory.

== Changelog ==

= 1.0.1 =
* Fix: Display settings tab rendering and template selection card active states
* Fix: Prevent checkbox settings overwrite across different settings tabs
* Feature: Added event slug customization, comments toggle, and element visibility controls

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.1 =
Fixes display settings and template card selection.

= 1.0.0 =
Initial release.
