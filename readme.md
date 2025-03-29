# WooCommerce B2B Invoice Gateway

Custom payment gateway for WooCommerce that allows approved business customers to pay by invoice. Ideal for B2B e-commerce shops that need to offer invoicing to specific users or companies.

---

## 🚀 Features

- Restrict invoice payments to approved users
- Disable invoicing on a per-user basis
- Require users to select their company and add a reference at checkout
- Define allowed shipping methods per gateway instance
- Fully integrated into WooCommerce payment settings

---

## 🔧 Installation

1. Upload the plugin to your WordPress site (`wp-content/plugins/woocommerce-b2b-invoice`).
2. Activate the plugin via **Plugins > Installed Plugins**.
3. Go to **WooCommerce > Settings > Payments** and enable **Pay by Invoice**.
4. Configure settings such as allowed users, shipping methods, and labels.

---

## ⚙️ Settings Overview

In the WooCommerce payment settings for "Pay by Invoice", you can:

- Enable/disable the gateway
- Set the title and description shown at checkout
- Choose allowed shipping methods per zone
- Control visibility based on user meta

---

## 👥 User Meta Keys

These user meta fields control access:

| Meta Key                          | Description                               |
| --------------------------------- | ----------------------------------------- |
| `wc_b2b_ic_can_invoice_order`     | Set to `yes` to allow invoice payments    |
| `wc_b2b_ic_disable_invoice_order` | Set to `yes` to temporarily block invoice |
| `wc_b2b_ic_invoice_companies`     | Array of invoice companies (see below)    |

Example structure for `wc_b2b_ic_invoice_companies`:

```php
[
    [
        'company_name' => 'Acme Oy',
        'y_tunnus' => '1234567-8',
        'verkkolaskutusosoite' => '003712345678',
        'valittaja' => '003721291126'
    ]
]
```
