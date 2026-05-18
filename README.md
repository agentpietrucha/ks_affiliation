# KS Affiliation

Affiliate link tracking for PrestaShop 8. Generate unique tracking URLs, set a visitor cookie on click, and attribute completed orders to the link that brought them in.

## Prerequisites

- PrestaShop 8.0 – 8.9.x
- PHP 8.0+

## Installation

1. Upload the `ks_affiliation/` folder to `/modules/`
2. Go to **Modules > Module Manager** and search for "KS Affiliation"
3. Click **Install**

## Configuration

Navigate to **Catalog > Affiliate Links** in the back office.

| Setting | Description | Default |
|---------|-------------|---------|
| Cookie lifetime (days) | How long the tracking cookie persists after a click | 30 |

## Usage

1. Create an affiliate link with a human-readable description.
2. Copy the generated URL (`/module/ks_affiliation/redirect?token=...`) and share it.
3. When a visitor clicks the URL, a tracking cookie is set and they are redirected to the homepage.
4. If the visitor places an order within the cookie lifetime, the order is attributed to the link.
5. Click **View Orders** on a link row to see all orders tracked through that link.
