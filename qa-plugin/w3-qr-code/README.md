# W3Uzman QR Kod Oluşturucu

Adres: `/qr-kod-olusturucu`

Bağlantı, Wi-Fi (WPA/WPA2, WEP, şifresiz), WhatsApp ve vCard 3.0.
Canlı SVG önizleme, 512/1024/2048 piksel PNG ve SVG indirme.
Koyu lacivert/siyah modüller, beyaz zemin, en az 4 modül sessiz alan.
UTF-8 ECI 26 ile Türkçe metin; otomatik boyut ve en az Medium hata düzeltme.
1800 UTF-8 bayt uygulama sınırı. Modül kenarları PNG'de tam piksele oturur.
Dinamik yönlendirme, API anahtarı, üyelik, veritabanı veya yeni sunucu eklentisi gerekmez.

## Yayına alma sırası

1. Bu **klasörün tamamını**, `vendor` alt klasörü dahil, sunucudaki `qa-plugin/w3-qr-code/` yoluna yükleyin.
2. Son olarak `qa-theme/FlatBox/qa-theme.php` dosyasının güncel sürümünü aynı canlı yola yükleyin (Araçlar menüsü bağlantısı).
3. `/qr-kod-olusturucu` adresini açıp gerekirse Ctrl+F5 ile yenileyin.

Mevcut tema bağımlılıklarını koruyun. `qa-config.php`, `.htaccess`, çekirdek dosyaları ve veritabanı bu araç için değişmez.
PHP modülü yalnızca sayfa başlığını ve yerel dosyaları yükler; kullanıcı girdilerini işlemez.
Tarayıcıda JavaScript ve güncel Canvas/Blob/TextEncoder desteği gerekir.

## Veri davranışı

Girişler form gönderimiyle, URL sorgusuyla, fetch/XHR ile veya yerel depolamayla aktarılmaz/kaydedilmez.
QR verisi yalnızca tarayıcı belleğinde işlenir; SVG XML'ine girilen metin eklenmez, yalnızca geometrik modüller yazılır.
Alanların `name` özniteliği ve sunucuya gönderilen bir formu yoktur. QR oluşturmak için harici CDN veya servis çağrılmaz.
Wi-Fi şifresi ve kişi bilgisi **indirilen kodun içindedir**. Wi-Fi paneli bu konuda kullanıcıyı bilgilendirir.
Girdi değiştiği anda eski dosya bağlantıları geçersizleştirilir; yeni kod hazır olmadan indirilemez.
Yavaş PNG callback'leri revision sayacıyla elenir; eski bir kod yeni dosyanın yerine geçemez.

QR taranabilirliğini ve hedef sayfanın/telefonun/ağın çalışmasını ayrı değerlendirin.
Statik QR kod değişmez ve servis tarafından sona erdirilmez; hedef adresin çalışacağı garanti edilmez.

## Üçüncü taraf kod

Project Nayuki QR Code generator — MIT; kaynak/lisans başlığı `vendor/qrcodegen.ts` ve derlenen `vendor/qrcodegen.js` içinde korunur.
Kaynak: https://github.com/nayuki/QR-Code-generator
Sabit commit: `3c6d0b3cefb4e049dc337e82237c9644399716a8`
Dosya: `typescript-javascript/qrcodegen.ts`
Derleyici: TypeScript 5.9.3; `tsc qrcodegen.ts --target ES2017 --lib ES2017,DOM --strict --outFile qrcodegen.js`
Üretimde TypeScript, npm veya Node gerekmez.

Payload biçimleri: https://github.com/zxing/zxing/wiki/Barcode-Contents
