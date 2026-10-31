# W3Uzman WhatIsMyIP

PHP 7.4+ / Question2Answer 1.8+. Install the complete directory, including data and lib.

Geolocation is resolved locally. No API key or paid account is needed. The IP used for the visitor is the validated web-server REMOTE_ADDR. Forwarding headers are never trusted directly. Behind a reverse proxy, configure trusted IP restoration at the web-server level.

DB-IP City Lite and ASN Lite, October 2026, are licensed under CC BY 4.0. The results page contains the required DB-IP attribution. Accuracy and coverage are limited; the map is an approximate IP-block location, not a person's exact location. ASN organization is an announcing network, not necessarily the retail ISP.

Sources:

- https://db-ip.com/db/lite.php
- https://db-ip.com/db/download/ip-to-city-lite
- https://db-ip.com/db/download/ip-to-asn-lite
- https://download.db-ip.com/free/dbip-city-lite-2026-10.mmdb.gz
- https://download.db-ip.com/free/dbip-asn-lite-2026-10.mmdb.gz
- MaxMind DB Reader PHP v1.14.0: https://github.com/maxmind/MaxMind-DB-Reader-php/tree/v1.14.0 (Apache 2.0; license retained in lib).

The two uncompressed MMDB files total about 130 MiB. They are read using file seeks, not loaded entirely into PHP memory. Refresh monthly: download the latest month's official City Lite / ASN Lite MMDB gzip archives, decompress them, validate with the reader, and replace data/dbip-city-lite.mmdb and data/dbip-asn-lite.mmdb. Keep the attribution and licenses. The version displayed in the UI comes from each database's build metadata.

The data/lib .htaccess files prevent direct HTTP downloads on Apache-compatible hosting. PHP can still read those files. Missing geolocation data degrades to the IP-only result. The map contacts OpenStreetMap only when the visitor clicks the map button; it receives map coordinates, not the lookup-IP query parameter. Private addresses do not get a map. Public-IP lookup results are noindex and all IP pages use private/no-store cache headers.
