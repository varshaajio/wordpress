# Charity Fund Collection
## Plugin name: Outreach Foundation 

This is a simple WordPress plugin designed for charity fundraising and campaign management. This project lets you run charity campaigns. You can collect donations too. It works with offline or test donations. 
### Payment options: Razorpay and PayPal Donate are supported.

## Features

* **Campaign Management:** Dedicated custom post type to manage individual causes.
* **Progress Tracking:** Built-in fields for funding goals, amount raised, and deadlines.
* **Multi-Gateway Support:** * **Razorpay:** Integrated popup checkout using `checkout.js`.
    * **PayPal:** Standard redirect-based donation flow.
    * **Offline:** Test mode for recording donations without actual charges.
* **Donation Logging:** Automatically creates a "Donation" entry for every successful transaction.
* **Frontend Components:** Clean, grid-based campaign listings and a preset-based donation form.

## Installation

1. Download the repository or clone it.
2. Put the folder in the `wp-content/plugins` directory of your WordPress installation.
3. Go to the WordPress Admin.
4. Find the **Plugins** page.
5. Activate **Charity Fund Collection**.
6. Configure the plugin under **Settings** > **Charity Fundraising**.

## Usage Guide

Follow these steps to implement the functionality on your site:

### 1. Configure Global Settings
Before creating campaigns, set up your payment preferences:
* **Gateway:** Choose between Offline, PayPal, or Razorpay.
* **Currency Code:** Enter your ISO currency code (e.g., `USD` or `INR`).
* **PayPal Email:** Required if PayPal is selected.
* **Razorpay Key ID:** Required if Razorpay is selected.


### 2. Create a Campaign
* Go to the **Campaigns** menu and click **Add New**.
* Enter the title and description of your cause.
* Set a **Featured Image** to serve as the campaign thumbnail.
* Locate the **Campaign Details** metabox.
* Fill in the **Goal Amount**, **Raised Amount**, and the **Deadline** (YYYY-MM-DD).
* Publish the post.

### 3. Display Campaigns on Your Site
Use the following shortcodes to display content on your pages or posts:

* **Display all campaigns:** `[of_all]`
* **Display a specific donation form:** `[of_one id="POST_ID"]`


### 4. Manage Donations
* Donations are recorded under the **Donations** post type.
* You can monitor donor names and amounts in the admin dashboard.
* If using Offline mode, manually update the **Raised Amount** in the Campaign settings to progress the bar.

## Shortcodes Reference

| Shortcode | Attribute | Description |
| :--- | :--- | :--- |
| `[of_all]` | None | Displays a grid of all published campaigns with progress bars. |
| `[of_one]` | `id` | Displays a donation form with preset amounts ($25, $50, $100) and custom input. |

## Admin Options

* **Payment Gateway:** Pick from Offline, Razorpay, or PayPal.
* **Currency:** Set 3-letter code like INR, USD, EUR.
* **Razorpay Keys:** Add Key ID and Secret. Required for Razorpay integration.
* **PayPal Email:** Enter your PayPal business email. Required for PayPal Donate.

## Webhooks (Razorpay)

Razorpay users must set up webhooks to automate donation tracking.
1. Copy the Webhook URL found in the plugin settings.
2. In your Razorpay Dashboard, go to **Settings** > **Webhooks**.
3. Paste the URL and subscribe to the `payment.captured` event.

## Technical Details

* **Post Types:** `cp` (Campaigns), `dn` (Donations).
* **Metadata Keys:** * `gl`: Goal amount.
    * `rs`: Amount raised.
    * `dl`: Campaign deadline.
* **Razorpay Integration:** The plugin automatically enqueues the Razorpay SDK and handles the `amount * 100` conversion for sub-unit processing (e.g., cents or paise).

## Demo Link

The PHP file does not support a live demo directly on GitHub. A sample HTML file is provided to visualize the output.

**[View Live Demo of website here](https://varshaajio.github.io/wordpress/)**



