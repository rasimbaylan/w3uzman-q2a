# W3Uzman DNS Kayıt Sorgulama

Sayfa: `/dns-kayit-sorgulama`. A, AAAA, CNAME, MX, NS, TXT, SOA, CAA ve SRV kayıtlarını sorgular. Alan adı, alt alan adı veya HTTP(S) adresi kabul edilir. `www`, DKIM seçicileri, `_dmarc` ve `_sip._tcp` gibi hizmet adları korunur.

## Canlıya aktarım

1. Bu klasörün tamamını `qa-plugin/w3-dns-records/` konumuna yükleyin.
2. En son güncel `qa-theme/FlatBox/qa-theme.php` dosyasını yükleyin; Araçlar menüsüne DNS bağlantısını ekler ve mevcut hover davranışını korur.

SQL aktarımı veya API anahtarı gerekmez. PHP'nin `qa-cache` klasörüne yazabilmesi ve dışarı HTTPS 443 bağlantısı kurabilmesi gerekir. TLS doğrulaması açıktır. Eski `qa-theme2.php` veya `acil-acilis` dosyaları kullanılmaz.

## Kaynak ve sonuçların anlamı

Google Public DNS JSON DoH API kullanılır: https://developers.google.com/speed/public-dns/docs/doh/json . Bağlantı yalnızca sabit `https://dns.google/resolve` adresine kurulur. Kullanıcının girdiği siteye HTTP bağlantısı kurulmaz; özel resolver/port seçimi ve yönlendirme takibi yoktur. İstemci IP'si eklenmez ve EDNS Client Subnet `0.0.0.0/0` olarak gönderilir. Sorgulanan DNS adı çözümleyiciye iletilir.

Tüm kayıtlar, ANY yerine dokuz ayrı sorguyla alınır; tarayıcı en fazla üçünü eşzamanlı yürütür. Tek tür seçilirse sonuç PHP tarafından da oluşturulur; JavaScript kapalıyken çalışır. Tümünü sorgulama, filtreleme ve kopyalama JavaScript kullanır. CNAME zincirleri saklanır; sorgulanan türün adedi ayrıca gösterilir.

- **Kayıt bulundu:** istenen kayıt türü yanıtlandı.
- **Kayıt yok (NOERROR/NODATA):** DNS sorgusu başarılı, istenen türde kayıt bulunmadı. Her alan adında her tür gerekli değildir.
- **NXDOMAIN:** ad veya yönlendiği hedef çözümlenemedi; alan adı müsaitlik garantisi değildir.
- **Sorgu hatası:** ağ hatası, eksik/uyumsuz yanıt, zaman aşımı, SERVFAIL, REFUSED veya hız sınırı. Hata hiçbir zaman kayıt yokmuş gibi sunulmaz.

DNSSEC etiketi yalnızca çözümleyicinin `AD=true` bildirdiği yanıtta gösterilir. Diğer yanıtlar için güvensizlik sonucu çıkarılmaz. TTL saniye cinsindedir. TXT değerleri çözümleyicinin döndürdüğü metin biçimiyle korunur. MX önceliği/SOA/SRV alanları DNS metnindeki sırayla gösterilir.

Sonuç tek bir genel çözümleyicinin görünümüdür; dünya genelinde yayılım veya yetkili sunucuların birbirleriyle tutarlılık testi yapılmaz.

## Önbellek ve sınırlar

Pozitif sonuçlar en kısa cevap TTL'ini ve 5 dakika üst sınırını aşmadan saklanır. TTL=0 önbelleğe alınmaz. Olumsuz sonuçlarda mevcut SOA TTL/MINIMUM ve 60 saniye üst sınırı kullanılır; SOA yoksa olumsuz önbellek yapılmaz. Kayıt dosyası dakika bazında saklansa bile saniye bazlı `expires` kontrolü erken eskimeyi uygular. Önbellekten gelen değerlerde kalan TTL azaltılır.

Önbellek dışı sorgular ziyaretçi IP'si başına dakikada 90, sunucu genelinde 900 ile sınırlıdır. Sayaç dosyası kilitlidir ve IP'ler SHA-256 özetiyle tutulur. Kaynak yanıtı 256 KiB ve 8 saniye ile sınırlıdır; kayıt adedi en fazla 100'dür. Hatalı yanıtlar önbelleğe alınmaz. Sorgu sayfaları ve JSON çıktısı `private, no-store` ve `noindex, follow` kullanır; ana sayfanın canonical adresi sabittir.

PHP 7.4 / 8.3 hedeflenir. `intl` olmayan PHP kurulumlarında Türkçe karakterli adlar için Punycode biçimi istenir. Kaynak kod dosyaları doğrudan HTTP erişiminde 404 döndürür.
