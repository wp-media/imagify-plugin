# Imagify — Product Overview

> Version 2.2.8 · WordPress Image Optimization

---

## Key Stats

| Metric | Value |
|--------|-------|
| Average file size reduction | Up to **80%** |
| Compression quality levels | **3** |
| Optimization contexts | **3** |
| Images on Infinite plan | **Unlimited** |

**Benefits at a glance:** Faster page loads · Better Core Web Vitals · Improved SEO rankings · Lower bandwidth costs · Zero configuration needed

---

## What is Imagify?

Imagify is a WordPress plugin that automatically compresses, converts, and delivers images in the optimal format and size — so site owners get fast-loading pages without any manual effort.

Images are the single largest contributor to page weight on most WordPress sites. Unoptimized images slow down pages, hurt Google Search rankings, drain server bandwidth, and frustrate visitors — especially on mobile. Imagify eliminates all of that with a one-time setup.

Once installed, every image uploaded to WordPress is automatically compressed and converted. Existing libraries can be processed in bulk. The result is a site that loads faster, scores higher on Core Web Vitals, and costs less to run — without the site owner touching a single file manually.

> **Core Web Vitals impact:** Google's ranking signals include Largest Contentful Paint (LCP) and Cumulative Layout Shift (CLS) — both heavily influenced by image size and format. Imagify directly improves both metrics.

### The Problem Imagify Solves

- Images account for 50–75% of a typical page's total byte weight
- Unoptimized uploads silently degrade performance over time
- Next-gen formats (WebP, AVIF) require complex server configuration without a plugin
- Manual image editing is time-consuming and inconsistent across teams
- Large original images consume unnecessary storage and bandwidth
- Poor Core Web Vitals scores directly suppress Google Search rankings

### Who is it for?

| Audience | Use case |
|----------|----------|
| 🛒 eCommerce stores | Product images need to be sharp and fast. Slow product pages lose conversions. |
| 📸 Photographers & creatives | High-resolution portfolios and galleries delivered at optimal size without visible quality loss. |
| ✍️ Content publishers & agencies | Teams uploading hundreds of images need fire-and-forget automation, not manual workflows. |
| 🏢 Enterprise & multisite networks | Centralized optimization policy across dozens of sites, with CLI and cron support for DevOps teams. |

---

## Core Features

### Auto-Optimize on Upload
The moment an image is added to the WordPress Media Library, Imagify compresses and converts it automatically. Zero friction, zero extra steps for content editors.

### Smart Image Compression
Three quality levels — Normal, Aggressive, and Ultra — let site owners choose the right balance between visual quality and file size. Save up to 80% on file size.

### Backup & One-Click Restore
Original images are always preserved in a secure backup. Restore any image — or the entire library — to its original state with a single click, no data loss risk.

### WebP & AVIF Conversion
Next-gen formats are automatically served to browsers that support them, with transparent fallback to JPEG/PNG for older environments. No manual configuration required.

### Bulk Optimization
Process an entire existing media library in one go. Ideal for sites migrating to Imagify or cleaning up years of unoptimized uploads. Runs in the background without downtime.

### Large Image Resizing
Enforce maximum dimensions on upload to prevent oversized originals from ever reaching the server. A 6000px wide photo becomes 2000px before it's even stored.

---

## Compression Levels

### 🟢 Normal
Balanced compression that preserves visual fidelity. The right choice for portfolios, editorial photography, and any context where image quality is paramount. Compression is nearly invisible to the human eye.

- **Typical savings:** 30–50%
- **Recommended for:** Most sites

### 🟡 Aggressive
Stronger compression that pushes file size down significantly while keeping the image presentable. Well suited to news sites, blogs, and product thumbnails where bandwidth efficiency outweighs perfect fidelity.

- **Typical savings:** 50–70%
- **Recommended for:** High-volume content

### 🔵 Ultra
Maximum compression powered by the Imagify API's advanced processing pipeline. Extracts every last byte from the file. Ideal for landing pages, ads, and any context where loading speed is the top priority.

- **Typical savings:** 60–80%
- **Recommended for:** Maximum performance

---

## Next-Gen Formats & Delivery

### WebP — Universal Support
WebP is now supported by all modern browsers and delivers 25–35% smaller files than JPEG at equivalent quality. Imagify generates WebP versions automatically alongside every uploaded image.

**Browser support:** Chrome · Firefox · Safari 14+ · Edge · iOS 14+

### AVIF — Next-Generation Compression
AVIF achieves 50% smaller files than JPEG with superior quality, using the latest codec technology. Imagify generates AVIF where browser support allows, with automatic fallback to WebP or the original format.

**Browser support:** Chrome 85+ · Firefox 93+ · Safari 16+ · Edge 121+

### Two Delivery Methods

| Method | How it works | Best for |
|--------|-------------|---------|
| **HTML `<picture>` tag** | Imagify rewrites image markup to use the HTML `<picture>` element with multiple format sources. The browser picks the best format it supports. | Any host — no server configuration required |
| **Server-level rewrite** | Imagify adds rewrite rules at the web server level (Apache/Nginx). The HTML stays untouched — the server transparently substitutes the WebP or AVIF file. | Performance-first stacks |

---

## Optimization Contexts

### WordPress Media Library
The primary use case. Every image uploaded through WordPress — whether via the block editor, the classic uploader, or WooCommerce — is automatically optimized. Per-image controls appear directly in the media grid.

### Custom Folders
Any directory on the server can be registered as an optimization context — theme asset folders, plugin image directories, or client upload folders outside of WordPress. Imagify scans, tracks, and optimizes them all.

### NextGEN Gallery
Deep integration with the popular NextGEN Gallery plugin. Images managed inside NextGEN Gallery albums and galleries can be optimized directly through the Imagify interface, without switching between screens.

---

## Automation & CLI

### Automatic Workflows
- **Auto-Optimize on Upload** — Images are processed the moment they land in the Media Library. Content editors never need to remember to run optimization.
- **Scheduled Background Jobs** — Imagify uses WordPress cron to run optimization tasks in the background, ensuring no impact on the front-end experience during bulk processing.
- **Bulk Processing Dashboard** — A dedicated dashboard lets admins kick off a full-library optimization run and monitor progress in real time, with the ability to pause and resume.
- **Bulk Restore** — Restore the entire media library to original files in one operation — useful when switching compression levels or migrating to a new plan tier.

### WP-CLI Support
Imagify ships a full suite of WP-CLI commands, enabling DevOps teams to integrate image optimization into deployment scripts, staging pipelines, and CI/CD workflows.

- Bulk optimize the entire media library from the command line
- Bulk restore all images to their originals — essential for staging resets
- Optimize or restore individual images by ID
- Check optimization status and quota consumption programmatically
- Ideal for post-deployment automation and scheduled server-side jobs

---

## Subscription & Quota

Imagify uses a quota model tied to the amount of data optimized per month. Quota resets monthly, and sites can upgrade at any time.

| Plan | Quota | Notable features |
|------|-------|-----------------|
| **Free** | 20 MB / month | All 3 compression levels, WebP & AVIF, auto-optimize, backup & restore |
| **Growth** *(Most Popular)* | Larger quota / month | All Free features + purchasable extra byte packs, priority API processing |
| **Infinite** | Unlimited | All Growth features + no monthly quota cap, no overage charges |

> **How quota works:** Imagify tracks the total byte size of images sent to the API for optimization. Quota resets on the first day of each billing month. On the Growth plan, additional byte packs can be purchased at any time if the monthly allowance is exhausted. Quota consumption is visible in the plugin dashboard at all times.

---

## Compatibility & Integrations

### Plugin Integrations
- WooCommerce — product & gallery images
- WP Rocket — joint performance optimization
- Gravity Forms — form attachment images
- Yoast SEO — optimized Open Graph images
- AMP — compliant image delivery on AMP pages
- NextGEN Gallery — full gallery management

### Hosting Compatibility
- WordPress.com (Business & Commerce)
- WP Engine
- SiteGround
- Kinsta
- Cloudways
- Flywheel & other managed hosts

### WordPress Core
- WordPress Multisite networks
- Block editor (Gutenberg)
- Classic editor
- REST API compatible
- WP-CLI integration
- WordPress Cron system

### WordPress Multisite — Full Network Support
Imagify supports WordPress Multisite out of the box. Network administrators can manage a single API key and settings across the entire network, while individual site admins retain per-site configuration control. Quota is shared across the network, giving enterprises a single consolidated view of usage.

---

## Admin Experience

### Per-Image Controls in the Media Library
Each image in the WordPress Media Library shows its current optimization status, compression level, space saved, and quick-action buttons — all without leaving the media screen.

### Bulk Optimization Dashboard
A dedicated bulk optimization screen shows a summary of the entire library — how many images are optimized, how many remain, and the total space saved to date. A single button starts a full-library run, with real-time progress feedback.

### Admin Bar Quick Access
An Imagify shortcut in the WordPress admin bar gives site administrators instant access to the bulk optimization screen and quota status from any page of the dashboard.

### One-Click Reset Internal State
A troubleshooting tool in the plugin settings allows the plugin's internal database state to be reset with a single click — resolving stuck optimization queues and edge-case inconsistencies without the need to deactivate or reinstall the plugin.

---

## Developer Extensibility

### Action & Filter Hooks
Imagify fires WordPress actions and provides filters at every major stage of the optimization lifecycle — before and after optimization, on quota changes, on format conversion, and more. Developers can intercept and modify behavior without patching core code.

### Custom Optimization Contexts
The context system is open to extension. Technical partners can register entirely custom optimization contexts — pointing Imagify at any directory, data source, or image pipeline — and have those contexts managed and tracked alongside the built-in ones.

### Partner Integrations
Agencies and hosting providers can build on top of Imagify's extension points to create white-label experiences, automate optimization as part of site provisioning workflows, or surface optimization data inside their own admin interfaces.

---

*Imagify — Version 2.2.8 · Product Overview · © WP Media*
