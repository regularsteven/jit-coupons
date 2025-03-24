# Just-In-Time Coupons (JIT Coupons)

This plugin dynamically creates WooCommerce coupons based on reference “template coupons” when a user enters specific coupon codes at checkout. It allows you to maintain a concise list of potential codes (with optional JSON metadata for dynamic placeholders), without flooding your system with hundreds of coupons until they are actually used.

## Features

### Multiple Reference Templates

Create or select existing WooCommerce coupons (called “template coupons”) that define discount type, amount, usage limits, etc.

### Child Codes

Assign one or more child coupon codes to each template. When someone enters a child code at checkout, this plugin automatically clones the template coupon with the child code – on demand.

### Optional JSON Placeholders

Child codes can include JSON key-value pairs (e.g., `{ "presentername" : "John Smith" }`). The plugin then replaces placeholders like `{presentername}` in the template coupon’s excerpt with the JSON values.

### Lightweight Admin UI

A single settings page under **WooCommerce → JIT Coupons** lets you manage multiple template references and child codes.

## Requirements

- WordPress 5.0+
- WooCommerce 3.0+ (or higher)
- PHP 7.0+ recommended

## Installation

### Upload the Plugin

1. Copy the `jit-coupons` folder into `wp-content/plugins/`.
2. Ensure the `jit-coupons.php` file is in the top level of that directory.

### Activate

1. In your WordPress admin, go to **Plugins → Installed Plugins**.
2. Find **Just-In-Time Coupons** and click **Activate**.

### Verify WooCommerce

Make sure WooCommerce is installed and active on the site.

## Configuration / Usage

### Create Template Coupons (if needed)

In **WooCommerce → Coupons**, create “template” coupons with the discount details you want.

Example: A coupon code called `SpeakerCouponTemplate` giving 15% off with certain usage restrictions, etc.

Optionally, in the “Description” (excerpt) field, include placeholders like:

```15% discount for speaker {presentername}```

or

```15% discount for {person}```

– depending on your logic.

### Go to JIT Coupons Settings

In **WooCommerce → JIT Coupons**, click to open the admin page.

### Add a Reference

1. **Template Coupon (existing):** Enter the exact coupon code name of your template (e.g., `SpeakerCouponTemplate`).
2. **Child Codes (one per line):** List each potential code.

Examples:

- Simple: `John25`

- With JSON: `John25 {"presentername":"John Smith"}`

Add as many references (rows) as you need, each linking a different template to its child codes.

### Save

Click **Save References**. The plugin stores them in the `jit_references` option.

### Tip

Add a custom JSON object called "ref": "NameOfRefCoupon" and put this insde of the reference coupon as {ref} - this will allow tracking back if required as an admin user.

### Test

At checkout, if a user enters one of the child codes (e.g., `John25`), the plugin looks up which template is referenced, clones that template coupon, and substitutes any placeholders in the coupon’s excerpt.

The coupon is then created in WooCommerce and behaves like a normal coupon.

## Example

### Template Coupon

**Code:** `SpeakerCouponTemplate`  
**Excerpt:**  

``` 15% discount for speaker {presentername} ```

### JIT Coupons Admin Settings

```Template Coupon (existing): SpeakerCouponTemplate
Child Codes:
John25 {"presentername":"John Smith"}
Elis25 {"presentername":"Elis Smith"}
Erin25
```

The line `Erin25` has no JSON, so `{presentername}` is removed if the user enters `Erin25`.

### At Checkout

If a user applies `John25`, the plugin:

1. Finds it in the child list under `SpeakerCouponTemplate`.
2. Decodes `{"presentername":"John Smith"}` and replaces `{presentername}` with John Smith.”
3. Creates a new WooCommerce coupon with code `John25` and excerpt `15% discount for speaker John Smith.`

## Notes / Troubleshooting

- **Escaped Quotes:** If you see `\"` in your child codes after saving, you may need to adjust how data is sanitized or unslashed in the plugin’s admin logic.
- **Exact Code Matching:** The entered coupon code must exactly match the child code in your references (case-sensitive, no extra spaces).
- **Templated Meta Fields:** If you need placeholders inside other fields (like usage limit messages), you can implement a custom function in the plugin to replace placeholders in `_coupon_description` or other meta keys.

## License

This is distributed under same licensing as WordPress itself:  
**GPLv2 or later.**

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

## Credits

**Author:** Steven Wright
