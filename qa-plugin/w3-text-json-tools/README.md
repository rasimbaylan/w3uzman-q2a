# W3Uzman Metin ve JSON Araçları

Sayfalar: `/metin-araclari`, `/json-duzenleyici`

Her iki araç JavaScript ile tarayıcı belleğinde çalışır. Girişler URL'ye, yerel depolamaya veya sunucuya gönderilmez.
Dosya seçimi File API ile UTF-8 olarak okunur; bir yükleme formu veya ağ isteği yoktur.
Yeni API anahtarı, veritabanı tablosu, PHP eklentisi veya harici JavaScript kütüphanesi gerekmez.

## Metin Araçları

- Kelime, görünür karakter kümesi (grapheme), boşluksuz karakter, satır, paragraf ve tahmini okuma süresi.
- Intl.Segmenter (tr) ile Türkçe bölümleme; eski tarayıcılar için kelime/Unicode kod noktası alternatifi.
- Türkçe büyük/küçük harf, sözcük başlıkları, Latin harf ve URL slug dönüşümleri.
- Fazla yatay boşluklar, satır kenarları, boş satırlar, birebir yinelenen satırlar ve birleşik temizlik.
- Satır düzeni ve emoji bağlantı karakterleri korunur. Temizleme kod biçimlendirme aracı değildir.
- Son 10 dönüşüm geri alınabilir. Elle yazmaya başlanırsa dönüşüm geçmişi sıfırlanır; yazma için tarayıcının doğal geri alma işlevi kullanılabilir.
- Metin sınırı 200.000 UTF-16 kod birimi (görünür karakter sayısından farklı olabilir); TXT dosya sınırı 512 KiB.
- Sayaçlar 160 ms gecikmeyle güncellenir. Okuma süresi 200 kelime/dakika üzerinden yaklaşık değerdir.

## JSON Düzenleyici

- Katı JSON sözdizimi; satır/sütun ve imleç konumuyla hata gösterimi.
- Giriş 1 MiB, derinlik 128, çıktı 8 MiB karakter sınırı.
- 2/4 boşluk veya sekme girintisi; sıkıştırma; canlı kontrol; Ctrl/Command+Enter ile düzenleme.
- Kaynak sayı dizileri korunur: büyük tamsayılar, ondalık sıfırlar, negatif sıfır ve üs yazımı değişmez.
- Alan sırası ve string kaçış yazımları korunur. Anahtarlar sıralanmaz.
- Tekrarlanan anahtarlar uyarıyla korunur; nesnelere veri ataması yapılmadığı için `__proto__` gibi anahtarlar prototipi değiştirmez.
- `eval`/Function kullanılmaz. Metin tokenı çözmek için yalnızca JSON.parse kullanılır; sayılar JavaScript Number'a dönüştürülmez.
- Nesne/dizi dışındaki kök JSON değerleri de desteklenir. JSON Schema veya API alan kuralları doğrulanmaz.
- Başlangıç BOM işareti kabul edilir ve çıktıya alınmaz; yorumlar, tek tırnaklar ve trailing comma reddedilir.
- Girdi değişince eski çıktı/indirme devre dışı kalır. Asenkron dosya okumaları giriş revizyonu ile denetlenir.

## Ortak işlemler

Kopyalama HTTPS'te Clipboard API, yerel HTTP'te tarayıcının copy komutu ile yapılır.
İndirme kullanıcı tıklamasında yerel Blob üretir; dosya sunucuda oluşturulmaz.
Kaynak değerler HTML olarak eklenmez; textarea.value/textContent kullanılır.
PHP modülü yalnızca istenen sayfanın statik dosyalarını yükler; eksik HTML tüm siteyi durdurmaz.

## Yayın sırası

1. `qa-plugin/w3-text-json-tools/` klasörünün tamamı (bu README dahil 11 dosya).
2. `qa-theme/FlatBox/qa-styles.css` (uzayan araç menüsü için küçük ekranlarda kaydırma).
3. En son `qa-theme/FlatBox/qa-theme.php` (iki menü bağlantısı ve stil önbellek sürümü).

Mevcut tema bağımlılıklarını koruyun. qa-config.php, .htaccess veya çekirdek dosyaları değişmez.
