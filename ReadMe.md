# WC All Cart Tracker

**Contributors:** Panagiotis Drougas
**Tags:** woocommerce, cart, analytics, conversion, tracking, guest, live carts, abandoned carts
**Requires at least:** 5.8
**Tested up to:** 6.5.5
**Requires PHP:** 7.4
**WC requires at least:** 5.0
**WC tested up to:** 9.0
**License:** GPL v2 or later
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

Tracks every active user cart—including logged-in users and guests—providing live cart data, powerful analytics, conversion rates, and revenue potential in a dedicated dashboard.

---

## 🚀 Description

The **WC All Cart Tracker** is a high-performance analytics plugin for WooCommerce that goes beyond standard abandoned cart solutions. It captures real-time data on every customer interaction with their shopping cart from the moment the first product is added until the order is placed or the cart is cleared.

### Key Features

- **Real-Time Cart Tracking:** Logs every `add_to_cart`, quantity change, and item removal event.
- **Comprehensive Customer Data:** Captures Customer Name, Email, and WordPress User ID (if logged in).
- **Purchase History Integration:** Immediately shows the customer's **Number of Past Purchases** next to their active cart, offering powerful sales context.
- **In-Depth Analytics Dashboard:** Features conversion rates, abandonment rates, average cart value (ACV), and revenue potential.
- **Guest Cart Resolution:** Updates guest cart entries with name and email data as soon as they reach the checkout page.
- **Modular & Performant:** Designed using an object-oriented, modular structure (`Database`, `Tracking`, `Analytics`, `Admin`) to ensure fast performance and maintainability.

---

## ⚙️ Installation

### Standard WordPress Installation

1.  Download the plugin files from the GitHub repository or the WordPress Plugin Directory (once published).
2.  Go to your WordPress Admin dashboard and navigate to **Plugins > Add New**.
3.  Click **Upload Plugin** and select the ZIP file you downloaded.
4.  Activate the plugin through the **Plugins** menu.

### Database Setup

Upon activation, the plugin automatically creates a new custom table in your database named `wp_all_carts_tracker` (prefixed with your site's table prefix) to store cart data efficiently.

---

## 📊 Usage and Screenshots

Once activated, you can find the cart tracking dashboard under the WooCommerce menu:

1.  Navigate to **WooCommerce > Active Carts**.
2.  **Analytics Overview:** View the top metrics (Conversion Rate, Revenue Potential, Abandonment Rate) for the selected time period.
3.  **Active Carts Table:** See a live list of all currently active carts, sorted by last updated time or cart total.
4.  **WooCommerce > Cart History:** Browse a full history of all carts (Converted, Deleted, or Abandoned) over various time frames, complete with filtering options.

---

## 🛠️ Technical Details and Development

The plugin is built with a clean, modular structure:

| Component       | Responsibility                                                                                                  | Files                                                  |
| :-------------- | :-------------------------------------------------------------------------------------------------------------- | :----------------------------------------------------- |
| **Loader/Main** | Handles instantiation, dependencies, and WooCommerce compatibility checks.                                      | `wc-all-cart-tracker.php`, `class-wc-cart-tracker.php` |
| **Tracking**    | Hooks into WooCommerce actions (`woocommerce_add_to_cart`, `woocommerce_thankyou`) to gather and sanitize data. | `includes/class-wc-cart-tracking.php`                  |
| **Database**    | Manages custom table creation, dropping, and all `INSERT`/`UPDATE`/`SELECT` operations.                         | `includes/class-wc-cart-database.php`                  |
| **Analytics**   | Contains static methods for querying the database and calculating all key metrics (CR, ACV, Abandonment Rate).  | `includes/class-wc-cart-analytics.php`                 |
| **Admin**       | Manages the admin menu registration and loads the view templates.                                               | `admin/class-wc-cart-admin.php`, `admin/views/*.php`   |

### Database Schema

The plugin uses a dedicated, indexed table for performance: `wp_all_carts_tracker`.

| Column           | Type             | Description                                          |
| :--------------- | :--------------- | :--------------------------------------------------- |
| `id`             | `BIGINT`         | Primary Key.                                         |
| `session_id`     | `VARCHAR`        | WooCommerce Session ID (for guests).                 |
| `user_id`        | `BIGINT`         | WordPress User ID (0 for guests).                    |
| `cart_content`   | `LONGTEXT`       | JSON array of products, quantities, and line totals. |
| `cart_total`     | `DECIMAL(10, 2)` | Current value of the cart.                           |
| `customer_email` | `VARCHAR`        | Captured at login or checkout.                       |
| `past_purchases` | `INT(11)`        | Number of completed orders for this user.            |
| `last_updated`   | `DATETIME`       | Timestamp of the last cart modification.             |
| `cart_status`    | `VARCHAR(20)`    | 'active', 'converted', or 'deleted'.                 |

---

## 📝 Changelog

### 1.0.0

- Initial Release.
- Implemented core cart tracking logic via `woocommerce_add_to_cart` and checkout hooks.
- Established modular class structure (`Tracking`, `Database`, `Analytics`).
- Created Active Carts Dashboard and Cart History view pages.
- Added core analytics: CR, Abandonment Rate, ACV.
