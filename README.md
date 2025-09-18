# Charity Fund Collection

This is a simple WordPress plugin. It lets you run charity campaigns. You can collect donations too. It works with offline or test donations. Razorpay is supported. PayPal Donate is there as well.

## Features

You can create campaigns. Manage them using the Campaigns post type. Track all donations with the Donations post type. Set goals for fundraising. Track the raised amount. Add deadlines. There's a progress bar for each campaign. Use shortcodes to embed campaigns. Put donation forms on pages. Payment gateways include offline mode. No real charges there. Razorpay Checkout with webhooks. PayPal Donate using your business email. Admin settings page helps configure gateways. Set currency too.

## Installation

Download the repository. Or clone it. Put the folder in wp-content/plugins. Go to WordPress Admin. Find Plugins page. Activate Charity Fund Collection. Then configure under Settings. Look for Charity Fundraising.

## Shortcodes

Use [cfCampaigns]. It shows all active campaigns. For one campaign, try [cfCampaign id="123"]. That displays details. Includes donation option. If you skip id, it uses the current post. For just the form, [cfDonate id="123"]. That's for a specific campaign.

## Admin Options

Pick payment gateway. Options are Offline, Razorpay, or PayPal. Set currency. Use 3-letter code like INR, USD, EUR. For Razorpay, add Key ID. Secret too. Needed if you select it. PayPal needs business email. Required for that option.

## Campaign Meta Fields

Every campaign gets extra fields. Goal Amount is one. Raised Amount tracks progress. Deadline in YYYY-MM-DD format.

## Webhooks (Razorpay)

Razorpay users set up webhooks. Point them to the plugin endpoint.
