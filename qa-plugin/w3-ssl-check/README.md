# W3Uzman SSL Sertifika Kontrolü

Sayfa: `/ssl-sertifika-kontrolu`. Alan adı veya HTTP(S) URL'sinin HTTPS/443 sertifikasını inceler. www ve alt alan adları korunur. Özel portlar ve doğrudan IP sorguları kabul edilmez.

## Kurulum

Bu klasörün tamamını `qa-plugin/w3-ssl-check/` konumuna aktarın. Ardından en son güncel `qa-theme/FlatBox/qa-theme.php` dosyasını aktarın. SQL veya API anahtarı gerekmez. PHP OpenSSL ve stream socket desteği, yazılabilir qa-cache klasörü, dışarı HTTPS/443 erişimi ve güncel sistem CA güven deposu gerekir. cURL yoksa sabit DNS çözümleme isteği için HTTPS stream kullanılır.

## Kontrol kapsamı

- Başlangıç/bitiş tarihi, kalan tam gün ve 30 gün içinde yenileme uyarısı.
- SAN DNS adlarıyla tam veya tek seviyeli joker alan adı eşleşmesi. CN alanı bilgi olarak gösterilir; SAN yerine kullanılmaz.
- Kontrol sunucusunun OpenSSL güven deposuyla doğrulanan TLS bağlantısı.
- Düzenleyen kuruluş, imza/anahtar bilgileri, gözlenen TLS sürümü/şifre takımı, kontrol edilen IP, SHA-256 parmak izi.
- Geçerli, yenileme yaklaşan, süresi dolan, henüz geçerli olmayan, alan adı uyuşmayan, doğrulanamayan veya bağlantı hatası sonuçları.

Tek bir DNS adresi ve gözlenen TLS bağlantısı kontrol edilir. Tüm CDN uçlarını, tüm protokolleri, zafiyetleri, OCSP/CRL iptal durumunu veya sitenin içeriğini taramaz. Sonuçlar tarayıcıların farklı güven depolarıyla birebir aynı olmayabilir. Tarihler UTC'dir.

## Güvenlik ve bağlantı

Genel DNS çözümlemesi sabit `https://dns.google/resolve` üzerinden yapılır; EDNS istemci alt ağı `0.0.0.0/0` gönderilir. Ziyaretçi IP'si sorguya eklenmez. Loopback, özel, link-local, dokümantasyon, multicast, CGNAT ve IPv6 geçiş/ayrılmış adresleri engellenir. Doğrulanan IP doğrudan sokete sabitlenir; bağlantıda yeniden DNS çözülmez. SNI/peer_name orijinal alan adıdır. Yalnızca port 443 kullanılır, HTTP yönlendirmeleri veya kullanıcı URL'sinin yolu istenmez.

İlk TLS el sıkışması `verify_peer=true` ve `verify_peer_name=true` ile yapılır. Strict doğrulama başarısızsa ve sertifika yakalanamadıysa ikinci, tamamen ayrı bir tanılama el sıkışması yalnızca sertifikayı okumak için kullanılır. Bu bağlantıdan HTTP isteği, kimlik bilgisi veya uygulama verisi gönderilmez. Tanılama sonucu strict doğrulamayı hiçbir zaman başarılıya çeviremez; okunabilen süresi dolmuş/kendinden imzalı sertifikalar geçerli olarak gösterilmez. Global TLS/CA ayarları değiştirilmez.

DNS istekleri en fazla 4 saniye/64 KiB, TCP bağlantısı 3 saniye ve TLS işlemi 5 saniye ile sınırlanır. Gerekirse ikinci tanılama bağlantısı aynı süre sınırlarıyla yapılır. Sonuçlar geçerli sertifikalarda en fazla 5 dakika (bitiş zamanını aşmadan), doğrulanamayan sertifikalarda 1 dakika tutulur. Yeniden kontrol et cache'i atlar; hız sınırları devam eder. Taze sorgular IP başına dakikada 10 ve sunucu genelinde 120 ile kilitli sayaç üzerinden sınırlıdır. Sayaçta IP özeti saklanır. Sorgu sayfaları `no-store` ve `noindex, follow` kullanır.

PHP OpenSSL referansları: https://www.php.net/manual/en/context.ssl.php ve https://www.php.net/manual/en/function.openssl-x509-parse.php . Türkçe karakterli alan adları için intl yoksa Punycode istenir. Eksik servis dosyası/eklenti desteği diğer site sayfalarını etkilemez.
