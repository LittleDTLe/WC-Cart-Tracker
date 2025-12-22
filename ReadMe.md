# WC All Cart Tracker

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-green.svg)
![WooCommerce](https://img.shields.io/badge/WooCommerce-5.0%2B-purple.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)
![License](https://img.shields.io/badge/license-GPL--2.0-red.svg)

A comprehensive, production-ready WooCommerce plugin that tracks **every active shopping cart** in real-time—including both registered users and guests—providing powerful analytics on cart behavior, conversion rates, and revenue potential.

---

## 📋 Table of Contents

- [Features](#-features)
- [Installation](#-installation)
- [Usage](#-usage)
- [Database Schema](#-database-schema)
- [Performance Benchmark](#-performance-benchmark)
- [Plugin Architecture](#-plugin-architecture)
- [Analytics Metrics](#-analytics-metrics)
- [Technical Details](#-technical-details)
- [Development](#-development)
- [Roadmap](#-roadmap)
- [Contributing](#-contributing)
- [License](#-license)
- [Support](#-support)

---

## 🚀 Features

### Real-Time Cart Tracking

- ✅ **Every Cart Action Tracked** - Captures add, update, remove, and cart clearing events
- ✅ **Guest & Registered Users** - Tracks both logged-in customers and anonymous shoppers
- ✅ **Session-Based Tracking** - Uses WooCommerce session IDs for reliable guest identification
- ✅ **Automatic Data Enrichment** - Captures customer email and name at checkout
- ✅ **Purchase History** - Records number of past orders for each customer

### Advanced Analytics Dashboard

- 📊 **Conversion Rate Tracking** - Monitor how many carts convert to orders
- 💰 **Revenue Potential** - See total monetary value of all active carts (with 7-day cutoff for realistic projections)
- ⏱️ **Abandoned Cart Detection** - Automatically identifies carts inactive for 24+ hours
- 🗑️ **Deleted Cart Metrics** - Separate tracking for intentionally cleared carts
- 👥 **Customer Type Analysis** - Compare conversion rates between registered vs guest users
- 📈 **Flexible Time Periods** - View analytics for 7, 30, 60, 90 days, or custom date ranges
- 🔄 **Auto-Refresh Dashboard** - Optional automatic refresh every 5 minutes with toggle control
- 📅 **Custom Date Range Selector** - Pick specific start and end dates for detailed analysis
- 💎 **Revenue Breakdown** - Separate tracking of active, abandoned, and stale cart potentials

### Comprehensive Cart History

- 📜 **Full Cart Archive** - Browse all historical cart data with pagination
- 🔍 **Advanced Filtering** - Filter by status (Converted, Deleted, Abandoned, All Inactive)
- 📅 **Date Range Filters** - View history for 7 days to 1 year
- 📋 **Detailed Cart Contents** - Expandable view of products, quantities, and prices
- 🎯 **Status Badges** - Color-coded visual indicators for cart status
- 📄 **Flexible Pagination** - Choose 10, 25, 50, 75, or 100 records per page

### Advanced Export System

- 📤 **Column Selection Modal** - Choose exactly which data fields to export
- 💾 **Export Templates** - Save and reuse custom column configurations
- 👥 **Personal & Global Templates** - Create templates for personal use or share with entire team
- 📊 **Multiple Export Formats** - CSV, Excel (XLS), or Google Sheets compatible
- 🧹 **Complete Data Sanitization** - All exports are cleaned, HTML-stripped, and Excel-ready
- 🔒 **CSV Injection Prevention** - Automatic escaping to prevent formula injection attacks
- 📈 **Analytics Integration** - Exports include comprehensive analytics overview section
- 🎯 **Context-Aware Exports** - Respects current page filters (date range, status, etc.)
- ⚡ **Quick Actions** - Select All, Deselect All, Reset to Default buttons
- 🏷️ **Grouped Column Display** - Organized by category for easy selection

### Scheduled Exports & Automation

- ⏰ **Automated Scheduling** - Set exports to run daily, weekly, or monthly at 2:00 AM
- 📧 **Email Delivery** - Send exports as attachments to multiple recipients
- 📡 **FTP/SFTP Upload** - Automatically upload exports to remote servers
- 🔧 **Column Customization** - Select specific columns for each scheduled export
- 🎛️ **Enable/Disable Toggle** - Pause schedules without deleting configuration
- 🧪 **Test Export Button** - Send test exports immediately to verify setup
- 📝 **Execution Logs** - Track last run time, next run time, and execution status
- ✉️ **Email Diagnostics Tool** - Built-in email testing to verify mail configuration
- 📊 **Configuration Dashboard** - View SMTP status, WP Cron status, and system info
- 🔄 **WordPress Cron Integration** - Reliable scheduling using WordPress cron system

### Database Optimization & Maintenance

- 🔍 **Performance Statistics** - Real-time view of table size, row counts, and index health
- ⚡ **Table Optimization Tool** - One-click defragmentation and optimization
- 🔨 **Index Rebuilding** - Recreate all indexes for optimal query performance
- 🗂️ **Smart Archiving System** - Move old carts to separate archive table (restorable)
- 🧹 **Automated Cleanup** - Configurable daily cleanup of old inactive carts
- ⚙️ **Flexible Cleanup Settings** - Choose archive or permanent delete, set retention days
- 📊 **Archive Management** - View archive statistics, restore recent archives, purge old data
- 💡 **Smart Recommendations** - Context-aware suggestions based on your data volume
- 🔄 **Cache Management** - Clear analytics and database caches on demand
- ⏱️ **Scheduled Maintenance** - Daily WordPress cron job for automatic cleanup

### Technical Excellence

- ⚡ **HPOS Compatible** - Full support for WooCommerce High-Performance Order Storage
- 🗄️ **Optimized Database** - Custom table with composite indexes for fast queries
- 🔒 **Secure** - Sanitized inputs, prepared statements, capability checks, nonce verification
- 🌐 **Translation Ready** - Full i18n support with text domain
- 📱 **Responsive Design** - Mobile-friendly admin interface
- 🎨 **Visual Indicators** - Sortable table columns with arrow indicators
- 🧩 **Modular Architecture** - Clean OOP structure with separation of concerns
- 💾 **Smart Caching** - Query result caching with 5-minute TTL for better performance
- 🔄 **AJAX Operations** - Seamless updates without page reloads
- 📊 **Efficient Queries** - Single-query analytics for optimal database performance

---

## 📦 Installation

### Requirements

- **WordPress:** 5.8 or higher
- **WooCommerce:** 5.0 or higher
- **PHP:** 7.4 or higher
- **MySQL:** 5.6 or higher

### From GitHub

1. **Download or Clone the Repository:**

   ```bash
   git clone https://github.com/LittleDTLe/WC-Cart-Tracker.git
   cd WC-Cart-Tracker
   ```

2. **Upload to WordPress:**

   - Upload the entire `wc-all-cart-tracker` folder to `/wp-content/plugins/`
   - Or ZIP the folder and upload via WordPress Admin → Plugins → Add New → Upload Plugin

3. **Activate the Plugin:**

   - Go to WordPress Admin → Plugins
   - Find "WC All Cart Tracker"
   - Click "Activate"

4. **Automatic Setup:**
   - The plugin automatically creates the database table `wp_all_carts_tracker`
   - No additional configuration required!

### Manual Installation

1. Download the latest release ZIP file
2. Go to **Plugins → Add New → Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Click **Activate Plugin**

---

## 🎯 Usage

### Viewing the Active Carts Dashboard

Navigate to **WooCommerce → Cart Tracker** to access:

#### Dashboard Controls

- **Time Period Filter** - Select 7, 30, 60, 90 days, or custom date range
- **Custom Date Range** - Pick specific start and end dates for precise analysis
- **Auto-Refresh Toggle** - Enable/disable automatic 5-minute dashboard refresh
- **Manual Refresh Button** - Update data on-demand without page reload
- **Per Page Filter** - Display 10, 25, 50, 75, or 100 active carts per page
- **Export Options** - Access advanced export system with column selection

#### Analytics Overview

- **Key Metrics Cards:**
  - **Conversion Rate** - Percentage and count of converted carts
  - **Active Carts** - Current number of active shopping carts
  - **Abandoned Carts** - Carts inactive for 24+ hours
  - **Deleted Carts** - Carts manually cleared by users
  - **Overall Revenue Potential** - Total value of carts updated in last 7 days

#### Detailed Analytics

- **Average Cart Value** - Compare active vs converted cart averages
- **Revenue Potential Breakdown:**
  - Active Cart Potential (carts updated in last 24 hours)
  - Abandoned Cart Potential (carts 24 hours to 7 days old)
  - Stale Carts (>7 days old, excluded from revenue potential)
- **Customer Type Breakdown** - Distribution and conversion rates for registered vs guest users
- **Cart Summary** - Total tracked, converted, conversion rate, and abandonment rate

#### Active Carts Table

- **Sortable Columns** - Click headers to sort by:
  - Last Updated (default)
  - Customer Email
  - Past Purchases
  - Cart Total
- **Visual Indicators** - Arrows show sort direction (↕ ↑ ↓)
- **Customer Information** - Name, email, user ID (if logged in)
- **Past Purchase Count** - Number of completed orders
- **Cart Contents** - Product list with quantities and totals
- **Pagination** - Navigate through pages with flexible per-page options

### Viewing Cart History

Navigate to **WooCommerce → Cart History** to access:

#### Advanced Filtering

- **Time Period** - 7 days, 30 days, 60 days, 90 days, or 1 year
- **Status Filter:**
  - **All** - Every cart in the selected time period
  - **Converted** - Carts that became orders (green badge)
  - **Deleted** - Carts cleared by users (yellow badge)
  - **Abandoned** - Active but inactive >24hrs (red badge)
  - **All Inactive** - Both converted and deleted carts
- **Per Page Filter** - Choose 10, 25, 50, 75, or 100 records per page

#### History Table

- **Sortable Columns** - Date, Customer, Status, Cart Total
- **Status Badges** - Color-coded indicators:
  - 🟢 Green = Converted
  - 🟡 Yellow = Deleted
  - 🔴 Red = Abandoned
  - 🔵 Blue = Active
- **Expandable Cart Contents** - Click "View Items" to see product details
- **Pagination** - Navigate through historical records efficiently

### Using the Advanced Export System

Both Dashboard and History pages feature the advanced export system:

#### Export Workflow

1. **Click Export Button** - Opens the column selection modal
2. **Select Columns** - Choose which data fields to include in export
3. **Use Templates** (Optional):
   - **Load Template** - Apply saved column configurations
   - **Save Template** - Store current selection for future use
   - **Personal Templates** - Save for your own use
   - **Global Templates** - Share with all admin users (requires admin permissions)
4. **Quick Actions:**
   - **Select All** - Check all available columns
   - **Deselect All** - Uncheck all columns
   - **Reset to Default** - Return to recommended column selection
5. **Choose Format** - CSV, Excel (XLS), or Google Sheets compatible
6. **Export** - Download starts immediately with sanitized, clean data

#### Available Export Columns

Columns are organized into logical groups:

**Basic Information:**

- Cart ID
- Date/Time (last updated)
- Cart Status (Active, Converted, Abandoned, Deleted)

**Customer Information:**

- Customer Name
- Customer Email
- User ID (WordPress user ID, 0 for guests)
- Session ID (WooCommerce session identifier)
- Past Purchases (number of completed orders)

**Cart Details:**

- Cart Total (monetary value)
- Item Count (number of unique products)
- Cart Items (full product list with quantities and prices)
- Cart Items Summary (product names only)

**Technical Details:**

- Is Active (Yes/No active status)
- Days Since Update
- Cart Age in Hours

#### Export Template Management

**Creating Templates:**

1. Select your desired columns
2. Click "Save as Template"
3. Enter template name
4. Choose personal or global (if admin)
5. Save

**Using Templates:**

1. Click template dropdown
2. Select saved template
3. Click "Load"
4. Columns are automatically selected

**Managing Templates:**

- **Update:** Modify selection and save to existing template
- **Delete:** Remove unwanted templates
- **Share:** Admins can create global templates for team

### Scheduled Exports & Automation

Navigate to **WooCommerce → Scheduled Exports** to automate your reporting:

#### Creating a Scheduled Export

1. **Basic Settings:**

   - **Schedule Name** - Descriptive name (e.g., "Daily Active Carts Report")
   - **Export Type** - Active Carts or Cart History
   - **Export Format** - CSV or Excel
   - **Frequency** - Daily (2:00 AM), Weekly (Monday 2:00 AM), or Monthly (1st at 2:00 AM)

2. **Delivery Method:**

   - **Email with Attachment:**
     - Enter recipient email addresses (one per line)
     - Supports multiple recipients
     - File sent as email attachment
   - **FTP/SFTP Upload:**
     - FTP Host address
     - FTP Username
     - FTP Password
     - FTP Path (destination folder)

3. **Column Selection:**

   - Choose which columns to include in export
   - Use quick actions (Select All, Deselect All, Select Default)
   - Organized by category for easy selection

4. **Enable/Disable:**
   - Set schedule status (Enabled runs automatically, Disabled pauses)

#### Managing Scheduled Exports

**Schedule List View:**

- See all configured schedules
- View last run timestamp
- View next scheduled run time
- Check enabled/disabled status
- Monitor delivery method

**Available Actions:**

- **Edit** - Modify schedule settings, columns, or recipients
- **Test** - Send export immediately to verify configuration
- **Delete** - Remove unwanted schedules
- **Toggle Status** - Enable or disable without deleting

#### Email Diagnostics Tool

Built-in diagnostic tool helps troubleshoot email delivery:

**Test Email Function:**

1. Enter test recipient email
2. Click "Send Test Email"
3. Check inbox for test message

**Configuration Check:**

- Admin email address display
- SMTP configuration status
- WordPress Cron status
- Debug mode status

**Troubleshooting Tips:**

- Enable WP_DEBUG for error logging
- Install SMTP plugin for reliable delivery
- Verify PHP mail() function works
- Check spam/junk folders
- Verify recipient addresses are correct

#### Scheduled Export Requirements

**WordPress Cron:**

- Must be enabled (not DISABLE_WP_CRON)
- Requires regular site traffic OR external cron job
- Times based on WordPress timezone setting

**Email Delivery:**

- wp_mail() function must work
- SMTP plugin recommended for production
- Test with diagnostics tool before scheduling

**FTP Delivery:**

- Valid FTP credentials required
- Write permissions on destination folder
- Basic implementation (SFTP recommended for production)

**Server Cron (Recommended for Production):**

```bash
*/15 * * * * curl https://yoursite.com/wp-cron.php
```

### Database Optimization & Maintenance

Navigate to **WooCommerce → Optimization** to manage database health:

#### Statistics Dashboard

**Main Table Information:**

- Total carts tracked
- Active cart count
- Inactive cart count
- Main table size (MB)

**Archive Information (if exists):**

- Archived cart count
- Archive table size (MB)
- Archive status

#### Performance Optimization Tools

**Optimize Table:**

- Defragments database table
- Reclaims unused space
- Improves query performance
- One-click operation

**Rebuild Indexes:**

- Recreates all database indexes
- Fixes index fragmentation
- Optimizes query paths
- Essential for large datasets

**Clear All Caches:**

- Clears analytics cache
- Clears database query cache
- Forces fresh calculations
- Use after major changes

#### Data Management Tools

**Safe Operations (Reversible):**

1. **Archive Old Carts:**

   - Moves old inactive carts to archive table
   - Can be restored if needed
   - Improves main table performance
   - Select retention period (30-365 days)

2. **Restore Archived Carts:**
   - Brings back recently archived data
   - Restores last 7 days of archives
   - Useful if archived too early

**Permanent Operations (Irreversible):**

1. **Permanently Delete Old Carts:**

   - ⚠️ **WARNING:** Cannot be undone
   - Requires confirmation checkbox
   - Removes data completely
   - Always backup first

2. **Purge Old Archives:**
   - ⚠️ **WARNING:** Cannot be undone
   - Deletes archives older than 1 year
   - Requires confirmation checkbox
   - Use after archiving to main table

#### Automatic Cleanup Settings

**Enable/Disable Automatic Cleanup:**

- Toggle daily cleanup on/off
- Runs via WordPress cron at 2:00 AM

**Retention Period:**

- Days to keep inactive carts (30-365)
- Applies to automatic cleanup only
- Default: 90 days

**Cleanup Method:**

- **Archive (Recommended):**
  - Moves to archive table
  - Can be restored
  - Safe option
- **Delete (Permanent):**
  - Removes completely
  - Cannot be recovered
  - Use with caution

**Purge Archives Automatically:**

- Enable to auto-delete archives older than 1 year
- Runs with daily cleanup
- Keeps archive table manageable

#### Performance Recommendations

Built-in smart recommendations based on data volume:

**For 10,000+ Carts:**

- Use Archive method
- Run cleanup monthly
- Monitor table size

**For 50,000+ Carts:**

- Archive carts after 60 days
- Optimize table weekly
- Consider external cron job

**General Best Practices:**

- Always use Archive unless space-critical
- Backup before permanent deletions
- Keep main table under 50,000 records
- Monitor archive table size
- Test restoration process periodically

---

## 🗄️ Database Schema

The plugin creates a custom table `wp_all_carts_tracker` with optimized structure:

```sql
CREATE TABLE wp_all_carts_tracker (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(191) NOT NULL DEFAULT '',
    user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    cart_content LONGTEXT NOT NULL,
    cart_total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    customer_email VARCHAR(100) NOT NULL DEFAULT '',
    customer_name VARCHAR(100) NOT NULL DEFAULT '',
    past_purchases INT(11) NOT NULL DEFAULT 0,
    last_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    cart_status VARCHAR(20) NOT NULL DEFAULT 'active',

    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active),
    INDEX idx_cart_status (cart_status),
    INDEX idx_last_updated (last_updated),
    INDEX idx_active_updated (is_active, last_updated),
    INDEX idx_status_updated (cart_status, last_updated),
    INDEX idx_user_active (user_id, is_active),
    INDEX idx_session_active (session_id, is_active)
);
```

### Column Descriptions

| Column           | Type          | Description                                   |
| ---------------- | ------------- | --------------------------------------------- |
| `id`             | BIGINT(20)    | Unique identifier (auto-increment)            |
| `session_id`     | VARCHAR(191)  | WooCommerce session ID for guests             |
| `user_id`        | BIGINT(20)    | WordPress user ID (0 for guests)              |
| `cart_content`   | LONGTEXT      | JSON array of cart items with product details |
| `cart_total`     | DECIMAL(10,2) | Current monetary value of the cart            |
| `customer_email` | VARCHAR(100)  | Customer email (captured at login/checkout)   |
| `customer_name`  | VARCHAR(100)  | Customer full name                            |
| `past_purchases` | INT(11)       | Count of completed orders for this user       |
| `last_updated`   | DATETIME      | Timestamp of last cart modification           |
| `is_active`      | TINYINT(1)    | 1 = active cart, 0 = inactive                 |
| `cart_status`    | VARCHAR(20)   | 'active', 'converted', or 'deleted'           |

### Cart Status Values

- **active** - Cart is currently in use
- **converted** - Cart became a completed order
- **deleted** - Cart was manually cleared by user

### Archive Table

When using the Archive cleanup method, old carts are moved to `wp_all_carts_tracker_archive`:

- Same structure as main table
- Additional `archived_at` timestamp column
- Separate indexes for archive queries
- Can be restored to main table
- Subject to purging after 1 year (if enabled)

---

## ⚡ Performance Benchmark

### Before Optimization

| Dataset Size    | Active Carts Query | Analytics Query | History Query |
| --------------- | ------------------ | --------------- | ------------- |
| < 1.000 records | < 50ms             | < 100ms         | < 50ms        |
| 1.000 - 10.000  | 50ms - 200ms       | 100ms - 300ms   | 50ms - 100ms  |
| 10.000 - 50.000 | 200ms - 500        | 300ms - 800ms   | 100ms - 200ms |
| > 50.000        | > 500ms            | > 800ms         | > 200ms       |

### After Optimization

| Dataset Size    | Before | After | Improvement |
| --------------- | ------ | ----- | ----------- |
| < 1.000 records | 50ms   | 20ms  | 60%         |
| 1.000 - 10.000  | 200ms  | 60ms  | 70%         |
| 10.000 - 50.000 | 500ms  | 150ms | 70%         |
| > 50.000        | 800ms  | 250ms | 69%         |

### Optimization Techniques Applied

- Composite database indexes for common query patterns
- Single-query analytics aggregation
- Query result caching (5-minute TTL)
- Efficient upsert operations
- Batch status updates
- Conditional asset loading
- AJAX partial updates

---

## 🏗️ Plugin Architecture

### File Structure

```
wc-all-cart-tracker/
├── wc-all-cart-tracker.php            # Main plugin file & loader
├── README.md                          # Documentation
├── LICENSE                            # GPL v2 License
├── uninstall.php                      # Cleanup on uninstall
├── .gitignore                         # Git exclusions
│
├── includes/
│   ├── class-wc-cart-tracker.php     # Main plugin controller
│   ├── class-wc-cart-database.php    # Database operations & optimization
│   ├── class-wc-cart-tracking.php    # Cart event tracking
│   ├── class-wc-cart-analytics.php   # Analytics calculations & caching
│   ├── class-wc-cart-data-sanitizer.php # Export data sanitization
│   ├── class-wc-cart-export.php      # Export handler (CSV/Excel)
│   ├── class-wc-cart-export-templates.php # Template management
│   ├── class-wc-cart-export-ajax.php # Export AJAX handlers
│   └── class-wc-cart-scheduled-export.php # Scheduled export system
│
└── admin/
    ├── class-wc-cart-admin.php       # Admin interface handler
    ├── class-wc-cart-optimization-admin.php # Optimization page controller
    ├── views/
    │   ├── admin-dashboard.php       # Active carts dashboard
    │   ├── admin-history.php         # Cart history page
    │   ├── admin-optimization.php    # Optimization page
    │   ├── admin-scheduled-exports.php # Scheduled exports page
    │   ├── export-modal.php          # Column selection modal
    │   ├── table-body.php            # AJAX table fragment
    │   └── optimization/
    │       ├── statistics-card.php   # Database statistics
    │       ├── settings-card.php     # Cleanup settings
    │       ├── tools-card.php        # Manual tools
    │       └── suggestions-card.php  # Recommendations
    └── assets/
        ├── css/
        │   ├── admin-styles.css      # Main admin styles
        │   ├── export-modal.css      # Export modal styles
        │   └── scheduled-exports.css # Scheduled exports styles
        └── js/
            ├── admin-scripts.js      # Dashboard AJAX & interactions
            ├── export-modal.js       # Export modal controller
            └── scheduled-exports.js  # Scheduled exports AJAX
```

### Class Structure

#### `WC_Cart_Tracker` (Main Controller)

- Singleton pattern initialization
- Dependency loading
- WooCommerce compatibility checks
- Text domain loading

#### `WC_Cart_Tracker_Database` (Database Layer)

- Table creation and migration
- CRUD operations for cart data
- Session and user-based queries
- Status updates (active, converted, deleted)
- Archive table management
- Cleanup operations (archive/delete)
- Query result caching
- Index optimization

#### `WC_Cart_Tracker_Tracking` (Event Tracking)

- WooCommerce hook integration
- Real-time cart data capture
- Guest data enrichment
- Order conversion tracking
- Cart deletion detection

#### `WC_Cart_Tracker_Analytics` (Analytics Engine)

- Conversion rate calculations
- Abandonment metrics
- Customer type segmentation
- Revenue potential aggregation
- Query result caching (5-minute TTL)
- Custom date range support
- Revenue breakdown (active/abandoned/stale)

#### `WC_Cart_Tracker_Admin` (Admin Interface)

- Menu registration
- Asset enqueueing (conditional loading)
- View rendering
- AJAX endpoint handling
- Export modal rendering

#### `WC_Cart_Tracker_Optimization_Admin` (Optimization Controller)

- Optimization page rendering
- Manual tool execution (optimize, rebuild, cleanup)
- Settings management
- Statistics retrieval
- Action handling (archive, restore, delete, purge)

#### `WC_Cart_Tracker_Data_Sanitizer` (Data Cleaning)

- HTML stripping and entity decoding
- Price cleaning and formatting
- Cart content sanitization
- CSV injection prevention
- Excel-ready data formatting
- Special character removal

#### `WC_Cart_Tracker_Export` (Export Handler)

- Export request processing
- Multiple format support (CSV/Excel/Google Sheets)
- Data preparation and sanitization
- File generation and download
- Analytics integration in exports
- Context-aware filtering

#### `WC_Cart_Tracker_Export_Templates` (Template System)

- Template CRUD operations
- Column configuration management
- Personal and global templates
- Default column definitions
- Column data extraction
- Template validation

#### `WC_Cart_Tracker_Export_AJAX` (Export AJAX)

- Template retrieval endpoint
- Template save/update/delete endpoints
- Column selection persistence
- Nonce verification
- Permission checks

#### `WC_Cart_Tracker_Scheduled_Export` (Automation)

- Cron schedule registration
- Schedule CRUD operations
- Export generation
- Email delivery with attachments
- FTP/SFTP upload
- Test export functionality
- Schedule execution tracking
- Error logging

---

## 📊 Analytics Metrics

### Conversion Rate

**Formula:** `(Converted Carts / Total Carts) × 100`

Shows the percentage of carts that successfully converted to orders within the selected time period.

### Abandonment Rate

**Formula:** `((Abandoned + Active - Converted) / Total Carts) × 100`

Indicates the percentage of carts that were not converted, including those still active.

### Revenue Potential

**Formula:** `SUM(cart_total) WHERE is_active = 1 AND last_updated >= (NOW() - 7 days)`

Total monetary value of carts updated within the last 7 days. Represents realistic revenue potential from recent activity. Excludes "stale" carts older than 7 days.

**Breakdown:**

- **Active Cart Potential:** `SUM(cart_total) WHERE last_updated >= (NOW() - 24 hours)`
- **Abandoned Cart Potential:** `SUM(cart_total) WHERE last_updated BETWEEN (NOW() - 7 days) AND (NOW() - 24 hours)`
- **Stale Cart Value:** `SUM(cart_total) WHERE last_updated < (NOW() - 7 days)` (excluded from revenue potential)

### Average Cart Value (ACV)

- **Active ACV:** `AVG(cart_total) WHERE is_active = 1`
- **Converted ACV:** `AVG(cart_total) WHERE cart_status = 'converted'`

### Customer Type Metrics

- **Distribution:** Percentage of carts from registered vs guest users
- **Conversion Rates:** Separate conversion rates for each customer type

### Abandoned Carts

**Criteria:** `is_active = 1 AND last_updated < (NOW() - 24 hours)`

Carts that have been inactive for more than 24 hours but not yet deleted or converted.

---

## 🔧 Technical Details

### WooCommerce Hooks Used

**Tracking Hooks:**

- `woocommerce_add_to_cart` - Tracks cart additions
- `woocommerce_cart_item_removed` - Tracks item removals
- `woocommerce_after_cart_item_quantity_update` - Tracks quantity changes
- `woocommerce_cart_emptied` - Tracks cart clearing
- `woocommerce_thankyou` - Marks carts as converted
- `woocommerce_order_status_completed` - Alternative conversion tracking
- `woocommerce_checkout_update_order_meta` - Captures guest data

**Cron Hooks:**

- `wc_cart_tracker_cleanup` - Daily cleanup of old carts (2:00 AM)
- `wcat_scheduled_export_daily` - Daily scheduled exports (2:00 AM)
- `wcat_scheduled_export_weekly` - Weekly scheduled exports (Monday 2:00 AM)
- `wcat_scheduled_export_monthly` - Monthly scheduled exports (1st at 2:00 AM)

**WordPress Hooks:**

- `before_woocommerce_init` - Declares HPOS compatibility
- `plugins_loaded` - Initializes main plugin and scheduled exports
- `admin_menu` - Registers admin menu pages
- `admin_init` - Handles form submissions and actions
- `admin_enqueue_scripts` - Conditionally loads assets
- `admin_notices` - Displays admin notifications
- `wp` - Schedules cleanup cron job

### AJAX Implementation

The plugin uses WordPress AJAX extensively for dynamic updates:

**Dashboard AJAX:**

- `wcat_refresh_dashboard` - Updates metrics and cart table without page reload
- `wcat_save_refresh_setting` - Saves auto-refresh toggle preference

**Export AJAX:**

- `wcat_get_templates` - Retrieves user's export templates and available columns
- `wcat_save_template` - Saves new export template (personal or global)
- `wcat_delete_template` - Deletes export template with permission check
- `wcat_update_template` - Updates existing template configuration

**Scheduled Export AJAX:**

- `wcat_test_scheduled_export` - Sends test export immediately for verification
- `wcat_delete_schedule` - Removes scheduled export and clears cron

**Security:** All AJAX requests use WordPress nonces for verification and capability checks

### HPOS Compatibility

The plugin declares compatibility with WooCommerce High-Performance Order Storage:

```php
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
});
```

### Security Measures

- ✅ Sanitized user inputs (`sanitize_text_field`, `sanitize_email`, `sanitize_textarea_field`, `absint`)
- ✅ Prepared SQL statements (all database queries use `$wpdb->prepare()`)
- ✅ Capability checks (`manage_woocommerce` for admin pages)
- ✅ Nonce verification for AJAX requests and form submissions
- ✅ Direct file access prevention (`!defined('ABSPATH')`)
- ✅ Proper data escaping in output (`esc_html`, `esc_attr`, `esc_url`, `wp_json_encode`)
- ✅ CSV injection prevention in exports
- ✅ Permission checks for global templates

### Performance Optimizations

- Composite database indexes for common query patterns
- Query result caching with 5-minute TTL
- Efficient upsert logic (check before insert/update)
- Pagination for large datasets (flexible per-page options)
- AJAX partial updates instead of full page reloads
- Conditional asset loading (only on plugin pages)
- Batch operations for bulk status updates
- Smart cache invalidation on data changes
- Optimized analytics queries with single SQL statements
- Archive system for historical data management

---

## 💻 Development

### Setting Up Development Environment

1. **Clone the repository:**

   ```bash
   git clone https://github.com/LittleDTLe/WC-Cart-Tracker.git
   cd WC-Cart-Tracker
   ```

2. **Install in local WordPress:**

   ```bash
   ln -s $(pwd) /path/to/wordpress/wp-content/plugins/wc-all-cart-tracker
   ```

3. **Activate WooCommerce** on your test site

4. **Activate the plugin** and test

### Coding Standards

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- Follow [WooCommerce Coding Standards](https://github.com/woocommerce/woocommerce/wiki/Coding-Standards)
- Use meaningful variable and function names
- Comment complex logic
- Escape all output
- Sanitize all input
- Use prepared statements for database queries

### Extending the Plugin

**Add custom analytics metrics:**

```php
add_filter('wc_cart_tracker_analytics_data', function($analytics, $days) {
    // Add custom metric
    $analytics['custom_metric'] = your_calculation();
    return $analytics;
}, 10, 2);
```

**Modify cart tracking:**

```php
add_action('wc_cart_tracker_before_save', function($cart_data) {
    // Modify cart data before saving
    // $cart_data is passed by reference
});
```

**Add custom admin columns:**

```php
add_filter('wc_cart_tracker_admin_columns', function($columns) {
    $columns['custom_field'] = 'Custom Data';
    return $columns;
});
```

**Add custom export columns:**

```php
add_filter('wcat_export_columns', function($columns) {
    $columns['custom_field'] = array(
        'label' => 'Custom Field',
        'group' => 'Custom Data',
        'default' => false,
        'description' => 'Your custom field description'
    );
    return $columns;
});
```

### Building for Production

1. Create a ZIP archive:

   ```bash
   zip -r wc-all-cart-tracker.zip wc-all-cart-tracker/ -x "*.git*" "*/node_modules/*" "*/.DS_Store"
   ```

2. Test on staging environment before production deployment

3. Verify all features work after installation:
   - Active carts tracking
   - Analytics calculations
   - Export functionality
   - Scheduled exports (if configured)
   - Database optimization tools

---

## 🗺️ Roadmap

### Planned Features

- [ ] **Advanced Cart Recovery** - Send automated recovery emails with discount codes
- [ ] **SMS Notifications** - Text message alerts for abandoned carts
- [ ] **REST API** - Full API endpoints for external integrations
- [ ] **Webhook Support** - Trigger external services on cart events
- [ ] **Advanced Charts** - Visual graphs with Chart.js integration
- [ ] **Multi-Currency** - Support for WPML/Polylang currency
- [ ] **Product Analytics** - Most abandoned products report
- [ ] **Customer Insights** - Individual customer cart history and patterns
- [ ] **Automation Rules** - Triggered actions based on cart behavior
- [ ] **Google Drive Integration** - Upload scheduled exports to Google Drive
- [ ] **Dropbox Integration** - Upload scheduled exports to Dropbox
- [ ] **Slack Notifications** - Real-time cart alerts in Slack
- [ ] **A/B Testing** - Test different recovery strategies
- [ ] **Customer Segmentation** - Create segments based on cart behavior

### Version 1.1.0 (Upcoming)

- Enhanced email templates for scheduled exports
- Advanced filtering options for scheduled exports
- Cart age-based automation rules
- Email template customization
- Advanced recovery email sequences

---

## 🤝 Contributing

Contributions are welcome! Here's how you can help:

### Reporting Bugs

1. Check [existing issues](https://github.com/LittleDTLe/WC-Cart-Tracker/issues)
2. Create a new issue with:
   - Clear description of the bug
   - Steps to reproduce
   - Expected vs actual behavior
   - WordPress, WooCommerce, PHP versions
   - Screenshots if applicable

### Suggesting Features

1. Check [existing feature requests](https://github.com/LittleDTLe/WC-Cart-Tracker/issues?q=is%3Aissue+label%3Aenhancement)
2. Create a new issue with the `enhancement` label
3. Describe the feature and use case
4. Explain how it benefits users

### Pull Requests

1. Fork the repository
2. Create a feature branch:
   ```bash
   git checkout -b feature/amazing-feature
   ```
3. Make your changes following coding standards
4. Test thoroughly
5. Commit with clear messages:
   ```bash
   git commit -m "Add amazing feature: description"
   ```
6. Push to your fork:
   ```bash
   git push origin feature/amazing-feature
   ```
7. Open a Pull Request with:
   - Description of changes
   - Related issue number
   - Testing performed

### Code Review Process

- All PRs require review before merging
- Automated tests must pass (when implemented)
- Code must follow WordPress/WooCommerce standards
- Documentation must be updated if needed

---

## 📄 License

This project is licensed under the **GNU General Public License v2.0 or later**.

```
WC All Cart Tracker
Copyright (C) 2024 Panagiotis Drougas

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

See the [LICENSE](LICENSE) file for full license text.

---

## 📞 Support

### Getting Help

- **Documentation:** This README and inline code comments
- **Issues:** [GitHub Issues](https://github.com/LittleDTLe/WC-Cart-Tracker/issues)
- **Email:** Via GitHub profile

### Before Requesting Support

1. Check this README thoroughly
2. Search [existing issues](https://github.com/LittleDTLe/WC-Cart-Tracker/issues)
3. Verify you meet minimum requirements
4. Test with default WordPress theme
5. Disable other plugins to check for conflicts

### When Reporting Issues

Please provide:

- WordPress version
- WooCommerce version
- PHP version
- MySQL version
- Active theme
- Other active plugins
- Error messages (from debug.log)
- Steps to reproduce

### Common Issues & Solutions

**Issue: Scheduled exports not running**

- Verify WP Cron is enabled (not DISABLE_WP_CRON)
- Check if site has regular traffic
- Consider setting up external cron job
- Test export manually first
- Check debug.log for errors

**Issue: Email deliveries failing**

- Use Email Diagnostics tool on Scheduled Exports page
- Install SMTP plugin (WP Mail SMTP, Easy WP SMTP)
- Verify recipient email addresses
- Check spam/junk folders
- Enable WP_DEBUG and check logs

**Issue: Slow dashboard loading**

- Run table optimization (Optimization page)
- Archive old carts to reduce main table size
- Check if auto-refresh is causing issues
- Consider increasing per-page limit
- Rebuild indexes if table is large

**Issue: Export not including all data**

- Verify column selection in export modal
- Check if filters are applied (date range, status)
- Try resetting to default columns
- Check browser console for JavaScript errors

**Issue: Database table getting too large**

- Enable automatic cleanup (Optimization page)
- Archive old inactive carts
- Adjust retention period (lower days = smaller table)
- Consider purging old archives
- Run manual optimization regularly

---

## 🙏 Acknowledgments

- **WooCommerce Team** - For excellent hooks and APIs
- **WordPress Community** - For coding standards and best practices

---

## ⭐ Show Your Support

If this plugin helps your WooCommerce store, please consider:

- ⭐ **Starring the repository** on GitHub
- 🐛 **Reporting bugs** you encounter
- 💡 **Suggesting features** you'd find useful
- 📝 **Contributing code** improvements
- 📢 **Sharing** with others who might benefit
- ✍️ **Writing a review** to help others discover the plugin
- 💬 **Providing feedback** on your experience

---

## 🔗 Useful Links

- **GitHub Repository:** [https://github.com/LittleDTLe/WC-Cart-Tracker](https://github.com/LittleDTLe/WC-Cart-Tracker)
- **Issue Tracker:** [https://github.com/LittleDTLe/WC-Cart-Tracker/issues](https://github.com/LittleDTLe/WC-Cart-Tracker/issues)
- **WooCommerce:** [https://woocommerce.com](https://woocommerce.com)
- **WordPress:** [https://wordpress.org](https://wordpress.org)

---

## 📝 Changelog

### Version 1.0.1 (Current)

- Added advanced export system with column selection
- Added export templates (personal and global)
- Added scheduled exports with email and FTP delivery
- Added database optimization tools
- Added automatic cleanup with archive system
- Added custom date range selector
- Added auto-refresh dashboard toggle
- Added flexible pagination options
- Improved analytics with revenue breakdown
- Enhanced performance with composite indexes
- Added email diagnostics tool
- Fixed various bugs and improved stability

### Version 1.0.0 (Initial Release)

- Real-time cart tracking for all users
- Guest and registered user support
- Basic analytics dashboard
- Cart history page
- Manual refresh functionality
- Basic export (CSV)
- Database table creation
- HPOS compatibility
- Security implementations

---

_Last Updated: December 2025_
