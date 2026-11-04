# PS Stats

[Deutsch](README.md) | **English**

[![Version](https://img.shields.io/badge/Version-1.0.1-2271b1?style=flat-square)](readme.txt)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4?style=flat-square&logo=php&logoColor=white)
![WordPress](https://img.shields.io/badge/WordPress-tested%20up%20to%207.1.4-21759b?style=flat-square&logo=wordpress&logoColor=white)
![ClassicPress](https://img.shields.io/badge/ClassicPress-2.7.4-03768e?style=flat-square)
[![License](https://img.shields.io/badge/License-GPL--2.0--or--later-2ea44f?style=flat-square)](https://www.gnu.org/licenses/gpl-2.0.html)

PS Stats is an analytics plugin for ClassicPress that records page views locally as daily aggregates. It requires neither cookies nor external analytics services.

## Features

- Monthly and yearly charts with daily details and statistics
- Reports for popular content and referrer domains
- Date-range and target-path filters, plus CSV export for content and referrer tables
- Configurable data retention, exclusion of logged-in users, and path exclusions
- Dashboard widget, the “PS-Stats Widget” frontend widget, and the `[psstats-count]` and `[psstats-count-sum]` shortcodes
- Optional, read-only monthly reports based on existing MarketPress statistics
- Local data storage without sending PS Stats statistics to external services

## Requirements

- ClassicPress 2.7 or later
- PHP 8.0 or later
- InnoDB database tables for safe migrations and data maintenance

## Installation

1. Install the plugin in `wp-content/plugins/ps-stats` or upload it through the plugin administration screen.
2. Activate PS Stats under **Plugins**. On activation, the statistics tables are created and, if applicable, existing legacy PS Stats data is prepared for migration.
3. Open the reports from the **PS Stats** menu.
4. Configure data retention, path exclusions, and optional referrer collection under **Settings → PS Stats**.

Migrations of legacy data run in batches. The old source table is not automatically deleted after a successful migration. Check it for potentially personal legacy data before removing it separately, if appropriate.

## Reports and settings

The **Overview** provides monthly and yearly views. Under **Content** and **Referrers**, you can select date ranges and target paths; the tables can be exported as CSV. Reports include all aggregates that are still stored.

Under **Settings → PS Stats**, you can:

- Set a retention period of 7, 14, 30, 90, 180, or 365 days, or disable automatic deletion;
- Exclude logged-in users from tracking (enabled by default);
- Exclude local paths from tracking;
- Enable referrer collection and explicitly allow external domains;
- Optionally display existing MarketPress statistics as a read-only source.

Shortening the retention period requires explicit confirmation. Automatic cleanup runs on a schedule; a properly configured server cron is recommended for reliable execution. The system status displays the last successful cleanup and detected issues.

### MarketPress

The optional MarketPress report displays monthly aggregates of existing order values and free-download events to administrators. PS Stats does not record additional shop events or store copies of MarketPress source data. The MarketPress statistics module and its download table must already be enabled or initialized.

Order value totals reflect the existing source data and are not necessarily confirmed payment revenue; refunds are not adjusted separately. Downloads are events, not unique users or confirmed file transfers. MarketPress data and its retention rules must be reviewed independently.

## What is—and is not—counted

PS Stats counts eligible **GET requests** that reach ClassicPress and PHP; it does not count unique visitors. It records the homepage and canonical paths of published, public, non-password-protected content. Dynamic or private areas such as accounts, orders, checkout, membership areas, and downloads are excluded. You can also exclude additional paths.

Plain permalinks such as `?p=` or `?page_id=` are not counted as homepage views. Pretty permalinks are required for content-specific path statistics.

No cookies or visitor or session identifiers are created. The PS Stats statistics tables do not store IP addresses, user agents, or complete referrer URLs. HTTP headers may be processed during a request for bot detection and—when enabled—referrer attribution. Referrer collection is disabled by default; when enabled, only explicitly allowed external domains are stored.

Full-page caches, reverse proxies, and CDNs may serve requests without reaching PHP. Those views will not appear in the statistics; PS Stats does not automatically install a cache bypass, tracking pixel, or browser beacon. Bot filtering is heuristic, so the figures are neither visitor counts nor guaranteed to be complete.

## Privacy

Local storage and the absence of cookies do not guarantee GDPR compliance or exemption from consent requirements. Public URL paths may still contain names or other sensitive information. Review your site and configure appropriate path exclusions. Disabling collection options does not delete data already stored; separate deletion functions are available for referrers and statistics.

A configuration-dependent privacy notice suggestion is provided under **Settings → PS Stats**. It is not legal advice and must be adapted to your specific setup. In particular, review the purpose, legal basis, possible consent requirements, hosting, server logs, legacy data, data-subject rights, and backups. Backups and optional MarketPress source data have their own retention rules.

## Developers

Other trusted plugins can use the `ps_stats_provider()` PHP function to query aggregated view data. It is not a public REST endpoint. Calling code is responsible for permissions and privacy when using the data further. Invalid date ranges and database errors are reported as exceptions.

The existing test scripts can be run with WP-CLI in a ClassicPress installation:

```sh
wp --skip-plugins --skip-themes eval-file tests/evaluation.php
wp --skip-plugins --skip-themes eval-file tests/storage-privacy.php
wp --skip-plugins --skip-themes eval-file tests/marketpress-source.php
```

## Report an issue

Please report bugs and suggestions in the [GitHub issue tracker](https://github.com/Power-Source/ps-stats/issues). Do not post personal data, credentials, or confidential statistics exports in issues.

## License

PS Stats is licensed under **GPLv3 or later**. See [LICENSE](./LICENSE).
