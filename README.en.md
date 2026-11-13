# PS Stats

[Deutsch](README.md) | **English**

[![Version](https://img.shields.io/badge/Version-1.0.2-2271b1?style=flat-square)](readme.txt)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4?style=flat-square&logo=php&logoColor=white)
![WordPress](https://img.shields.io/badge/WordPress-tested%20up%20to%207.1.4-21759b?style=flat-square&logo=wordpress&logoColor=white)
![ClassicPress](https://img.shields.io/badge/ClassicPress-2.7.4-03768e?style=flat-square)
[![License](https://img.shields.io/badge/License-GPL--3.0--or--later-2ea44f?style=flat-square)](https://www.gnu.org/licenses/gpl-3.0.html)

PS Stats records page views locally and displays them in the dashboard and extended reports.

## Features and settings

- Dashboard widget with daily views, popular targets, and referrers
- Monthly and yearly reports under **PS-Stats**, plus content and referrer reports with date selection
- The "PS-Stats Widget" frontend widget and `[psstats-count]` / `[psstats-count-sum]` shortcodes
- Configurable retention, dashboard display, tracking method, and exclusion of logged-in users
- Integrated referrer blacklist

Settings are available under **Settings → PS-Stats**. The default retention period is 14 days; logged-in users are excluded by default. Older views are automatically deleted daily. Your site's scheduled tasks need to run reliably for this to work.

## Installation

1. Copy the plugin to `wp-content/plugins/ps-stats`.
2. Activate PS Stats under **Plugins**.
3. Review settings, retention, and tracking method.

## PSOURCE Manager

Use the [PSOURCE Manager](https://psource.eimen.net/psource-manager/) to install and update plugins and themes and access help resources.

## Tracking and privacy

By default, PS Stats counts views as pages load. If you use a page cache, you can switch to JavaScript tracking in the settings. Bot detection is not perfect. The statistics show page views, not the number of individual visitors.

For each view, the date, requested address, and, where applicable, the complete external referring address are stored. With plain permalinks, requested addresses may include query parameters. Tracking does not set visitor cookies. IP addresses and browser identifiers are not stored in the statistics; browser identifiers are processed for bot detection.

Requested and referring addresses may contain personal data. Local storage does not guarantee GDPR compliance or exemption from consent requirements. Review the legal basis for your site, update your privacy notice, and choose an appropriate retention period. Consider your backups as well.

## Changelog

### 1.0.2

- Missing statistics storage is created automatically without reactivating the plugin. Existing views and settings are preserved.
- Fixed storage setup for databases with strict date validation.
- Failed setup displays a clear error notice and is retried the next time the plugin loads.
- Missing daily cleanup is restored automatically without creating duplicate tasks.
- Outdated cached statistics are cleared after storage is recreated.
- Removed unused components while preserving existing storage and reports.
- Simplified the documentation and clarified features, setup, and privacy information.
- Added information about the PSOURCE Manager for installation, updates, and help resources, plus ways to support PSOURCE.

## Support and license

Report bugs in the [GitHub issue tracker](https://github.com/Power-Source/ps-stats/issues), without credentials or personal statistics data.

Want to support PSOURCE? Your [donation](https://psource.eimen.net/spendenaktionen/psource-unterstuetzen/) helps us continue developing and maintaining our projects. Thank you!

Licensed under **GPLv3 or later**; see [LICENSE](./LICENSE).
