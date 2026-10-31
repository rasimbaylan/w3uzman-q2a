# W3Uzman Domain Sorgulama

Sayfa: `/domain-sorgulama`. Bir kelime veya desteklenen bir alan adı girilir; aynı isim `.com`, `.net`, `.org`, `.com.tr`, `.tr`, `.info`, `.biz`, `.ai`, `.io`, `.co` uzantılarında sorgulanır.

## Kurulum

Bu klasörün tamamını canlıdaki `qa-plugin/w3-domain-search/` klasörüne aktarın. Ardından güncel `qa-plugin/w3-whois/w3-whois-service.php` dosyasını (.io ve .co Whois detay desteği), en son `qa-theme/FlatBox/qa-theme.php` dosyasını (Araçlar menüsü) aktarın. SQL aktarımı veya API anahtarı gerekmez. `qa-cache` PHP tarafından yazılabilir olmalıdır.

## Sonuçlar

- **Dolu / kırmızı:** yetkili serviste tam alan adına ait kayıt doğrulandı. Whois bağlantısı mevcut yerel Whois aracını açar.
- **Boş / yeşil:** yetkili serviste kayıt bulunamadı. Satın alınabilirlik garantisi değildir; rezerve, premium ve özel tahsis kuralları kayıt kuruluşunda doğrulanmalıdır.
- **Doğrulanamadı / gri:** bağlantı, hız sınırı, beklenmeyen veya eksik yanıt. Hata hiçbir zaman boş sonucu üretmez. Kısa, bilinen rezerve ve IDN adlarda kayıt yokluğu tek başına yeşil sonuç üretmez.

## Kaynaklar ve sınırlar

RDAP uçları 18 Ekim 2026 tarihinde https://data.iana.org/rdap/dns.json üzerinden doğrulandı. .com/.net: Verisign; .org: PIR; .info/.ai: Identity Digital; .biz: nic.biz. .tr/.com.tr: `whois.trabis.gov.tr:43`; .io: `whois.nic.io:43`; .co: `whois.registry.co:43`. Ülke uzantılarının yetkili Whois adresleri IANA kök bölge kayıtlarından doğrulandı: https://www.iana.org/domains/root/db/ . Servisler değişirse sabit uç listesi güncellenmelidir.

PHP sunucusunun dışarı HTTPS 443 ve Whois 43 bağlantılarına erişimi gerekir. Güvenlik duvarı veya servis kısıtı olduğunda gri sonuç gösterilir. TLS doğrulaması açıktır; kullanıcı tarafından belirtilen sunucuya, URL'ye veya Whois yönlendirmesine bağlantı kurulmaz. Yanıtlar sınırlı boyut ve sürede okunur.

Tarayıcı aynı anda en fazla iki uzantıyı sorgular; iptal ve sayfaya geri dönüş desteklenir. Sonuç önbelleği dolu adlarda 5, boş adlarda 1 dakikadır. Önbellek dışı sorgular IP başına dakikada 60, sunucu genelinde 600 ile sınırlandırılır. Eşzamanlı sorgularda sayaç dosya kilidiyle korunur; IP'ler sayaçta SHA-256 ile tutulur. Ziyaretçi IP'si kayıt servislerine gönderilmez. Sorgulanan alan adı ilgili yetkili kayıt servisine iletilir. Sorgu sayfaları ve JSON çıktısı `no-store` / `noindex` kullanır; ana araç sayfasının canonical adresi sabittir.

Yerel PHP'de intl yoksa Türkçe karakterli adlar için kullanıcıdan Punycode istenir. DNS kontrolü müsaitlik ölçütü olarak kullanılmaz. API çıktısı yalnızca kısa kayıt durumudur; ham Whois kişi bilgileri bu eklentide saklanmaz/gösterilmez.
