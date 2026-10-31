# W3Uzman Araçlar dizini

Adres: `/araclar`

Mevcut 9 araca doğrudan bağlantı: Whois, Domain, DNS, SSL, WhatisMyip, Responsive Kontrol, QR, Metin, JSON.
Renkli yerel SVG ikonları; kısa açıklama; Türkçe arama; Alan adı ve ağ / Geliştirme / İçerik filtreleri.
Arama yalnızca sayfadaki kartları filtreler. JavaScript kapalıyken tüm araç bağlantıları kullanılabilir.
Harici ikon paketi, CDN, API, veritabanı veya yeni PHP modülü gerekmez.
Diğer araçların sorgulama işlevleri değiştirilmedi.

Header'daki “Araçlar” yazısı native bağlantıdır ve bu sayfaya gider.
Yanındaki ok native details açılır menüsünü açar. Mevcut masaüstü hover ve Escape davranışı korunur.
Menünün ilk satırı “Tüm araçlar” da dizine gider. Mobilde aynı bağlantı ve ok ayrımı vardır.

## Canlıya yükleme sırası

1. `qa-plugin/w3-tools-directory/` klasörünün tamamı (5 dosya).
2. `qa-theme/FlatBox/qa-styles.css` (başlık bağlantısı ve ayrı ok alanı stilleri).
3. En son `qa-theme/FlatBox/qa-theme.php` (dizin bağlantısı ve stil önbellek sürümü).

Yeni veritabanı kaydı, .htaccess veya qa-config.php değişikliği gerekmez.
Mevcut tema bağımlılıklarını ve diğer araç eklentilerini koruyun.
