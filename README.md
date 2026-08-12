# Visitor Sentinel

**Real-time visitor monitoring, bot detection and progressive defense for WordPress.**

![Version](https://img.shields.io/badge/version-3.0.0-blue)
![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759b)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4)
![License](https://img.shields.io/badge/license-GPLv2-green)

Visitor Sentinel watches every request to your WordPress site, scores it for risk, and automatically blocks bots, scanners, brute-force attempts and spam — all from one lightweight dashboard, no external service required.

## Download

Grab the latest release (zip, ready to upload from **Plugins → Add New → Upload Plugin**):

**[⬇ Download Visitor Sentinel v3.0.0](../../releases/latest)**

See the [Releases page](../../releases) for the full version history and changelogs.

## Features

- **Live visitor dashboard** — see who's online right now (updated in real time via Server-Sent Events), daily visit trends, top pages, top referrers, device breakdown, geographic threat map
- **Rules-based threat detection** — catches SQL injection, XSS, path traversal, known scanner tools (sqlmap, nikto, wpscan), headless browsers (Selenium, Puppeteer), automated HTTP libraries, and more
- **Honeypot suite** — invisible trap fields on login/comment forms, a decoy admin login URL, a decoy REST API endpoint, a spam-trap email, and a honeyfile — anything that touches them gets banned instantly
- **Progressive defense** — medium-risk visitors get a JS browser-verification challenge before a full block, instead of an instant ban
- **Threat intelligence** — optional AbuseIPDB lookups, Tor exit-node detection, and automatic /24 subnet banning when multiple IPs from the same range get blocked
- **Device-aware banning** — bans are tied to a device cookie and browser fingerprint, not just an IP address, making them harder to bypass
- **Accountable unbanning** — lifting a permanent block requires a signed declaration from the admin, with a full audit trail and printable record
- **Brute-force & spam protection** — login attempt throttling, credential-stuffing detection, comment spam filtering (link count + keyword list)
- **Email & webhook alerts** — get notified the moment an IP is blocked
- **GDPR tools** — built-in data export/erase hooks for WordPress's privacy tools
- **CSV export** of all blocked IPs
- **Zero required dependencies** — every external integration (AbuseIPDB, Tor list, geolocation) is opt-in

## Requirements

- WordPress 5.8 or newer
- PHP 7.4 or newer

## Installation

1. In your WordPress admin, go to **Plugins → Add New → Upload Plugin**
2. Select the downloaded zip file and click **Install Now**, then **Activate**
3. Configure detection thresholds, honeypots and alerts under **Visitor Sentinel → Settings**

## Screenshots

_Add screenshots of the dashboard, blocked IPs list and settings page here._

## License

GPL v2 or later — see [LICENSE](LICENSE).

---

<sub>
#wordpress #wordpress-plugin #wordpress-security #security #bot-detection #honeypot #firewall #waf #anti-spam #brute-force-protection #ip-blocking #visitor-tracking #analytics #php #gdpr #threat-intelligence #website-security #anti-bot #rate-limiting #cybersecurity
</sub>
