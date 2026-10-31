<?php
if (!defined('QA_VERSION')) { http_response_code(404); exit; }

// Editorial policy drafts: verify the legal operator identity, retention schedule,
// active providers and consent configuration before public deployment.
class w3_info_pages
{
    private function documents()
    {
        return array(
            'gizlilik-politikasi' => array(
                'title' => 'Gizlilik Politikası',
                'intro' => 'W3Uzman’da kişisel bilgilerin kullanımı, hesap güvenliği ve gizlilik konularında bilmeniz gerekenler.',
                'summary' => 'Gizlilik hakkında açık ve anlaşılır bilgi',
                'sections' => array(
                    array('kapsam', 'Bu politika neyi kapsar?', '<p>Bu sayfa, w3uzman.com üzerindeki haber, rehber, üyelik ve iletişim özellikleriyle ilgili genel gizlilik bilgilendirmesidir. Gizlilik konularındaki iletişim noktanız W3Uzman site yönetimidir: <a href="mailto:bilgi@w3uzman.com">bilgi@w3uzman.com</a>.</p><p>Bu genel politika, belirli bir veri işleme faaliyeti için sunulması gereken ayrı aydınlatma metninin veya gerekli olduğunda alınacak açık rızanın yerine geçmez.</p>'),
                    array('bilgiler', 'Hangi bilgiler işlenebilir?', '<ul><li><strong>Üyelik bilgileri:</strong> Kullanıcı adı, e-posta adresi ve sizin eklediğiniz profil bilgileri.</li><li><strong>İçerik ve etkileşimler:</strong> Paylaştığınız haberler, yorumlar, yanıtlar, oylar ve profil duvarı mesajları.</li><li><strong>İletişim bilgileri:</strong> Formda paylaştığınız ad, e-posta adresi, konu ve mesaj içeriği.</li><li><strong>Teknik kayıtlar:</strong> IP adresi, işlem zamanı, oturum ve güvenlik kayıtları; kullanılan sunucu ve özelliklere bağlı teknik bilgiler.</li></ul><p>Herkese açık profil ve içerik alanlarında paylaştığınız bilgiler diğer ziyaretçilerce görülebilir. Bu alanlarda hassas veri, şifre veya ödeme bilgisi paylaşmayın.</p>'),
                    array('amaclar', 'Bilgiler hangi amaçlarla kullanılır?', '<p>Üyelik özelliklerini çalıştırmak, hesabınıza erişim sağlamak, içerikleri yayımlamak, sorularınızı yanıtlamak, kötüye kullanımı önlemek ve teknik sorunları incelemek için ilgili bilgilerden yararlanılabilir.</p><p>Bir iletişim mesajı göndermek veya bülten aboneliği talebi oluşturmak, kendiliğinden reklam iletisi izni verdiğiniz anlamına gelmez. Footer’daki bülten alanı yalnızca iletişim formunda tamamlayabileceğiniz bir talep hazırlar; tek başına otomatik abonelik oluşturmaz.</p>'),
                    array('yontem', 'Toplama yöntemi ve işleme koşulları', '<p>Bilgiler, doldurduğunuz formlar, hesap ve içerik işlemleri, çerezler ve hizmetin teknik kayıtları üzerinden elde edilebilir. Her işleme faaliyeti, amacına uygun bir hukuki dayanağa sahip olmalıdır; hizmetin yürütülmesi, hukuki yükümlülükler, bir hakkın korunması veya koşulları varsa meşru menfaat gibi dayanaklar ilgili faaliyete göre değerlendirilir.</p><p>Açık rıza gereken durumlarda bilgilendirme ile izin alma süreçleri ayrı yürütülmelidir. Bir sayfayı ziyaret etmek veya bu metni okumak tek başına açık rıza sayılmaz.</p>'),
                    array('paylasim', 'Hizmet sağlayıcıları ve üçüncü taraflar', '<p>Barındırma, e-posta iletimi ve güvenlik gibi teknik hizmetler üçüncü taraf sağlayıcılardan alınabilir. Harici bir giriş, gömülü içerik veya reklam hizmetini kullandığınızda ilgili sağlayıcının kendi veri işleme koşulları da devreye girebilir.</p><p>Hizmet sağlayıcının konumuna bağlı olarak yurt dışıyla veri alışverişi gündeme gelebilir. Kullanılan sağlayıcılar, aktarım kapsamı ve uygulanacak güvenceler ilgili hizmet özelinde ayrıca değerlendirilmelidir. Hukuken geçerli talepler kapsamında yetkili kurumlarla bilgi paylaşımı söz konusu olabilir.</p>'),
                    array('saklama', 'Saklama ve güvenlik', '<p>Saklama süreleri veri türüne, işlemin amacına ve geçerli yükümlülüklere göre belirlenir; bu sayfa tüm veriler için tek bir süre veya anında silinme garantisi vermez. Hesap, içerik ve iletişim kayıtlarına ilişkin taleplerinizi bize iletebilirsiniz.</p><p>Hesabınız için güçlü ve benzersiz bir şifre kullanın. E-posta, şifre ve teknik hesap bilgileri herkese açık profil alanlarına eklenmemelidir. Herhangi bir şüpheli erişim veya bilgi ifşasını iletişim sayfasından bildirin.</p>'),
                    array('haklar', 'Haklarınız ve iletişim', '<p>Uygulanabilir mevzuatın koşulları çerçevesinde kişisel verilerinizin işlenip işlenmediğini öğrenme; bilgi isteme; eksik veya yanlış bilgilerin düzeltilmesini, şartları varsa silinmesini veya yok edilmesini talep etme ve ilgili diğer haklarınızı kullanma imkânınız bulunur.</p><p>Gizlilik sorularınız için <a href="mailto:bilgi@w3uzman.com">bilgi@w3uzman.com</a> adresine yazabilirsiniz. Resmî başvurular ilgili mevzuatın kimlik doğrulama ve başvuru usullerine uygun yapılmalıdır; sıradan bir e-posta her durumda resmî başvuru şartlarını karşılamayabilir.</p><p class="w3-policy-source">Resmî bilgi: <a href="https://www.kvkk.gov.tr/Icerik/2036/Ilgili-Kisinin-Haklari" target="_blank" rel="noopener noreferrer">KVKK — İlgili kişinin hakları</a>.</p>'),
                ),
            ),
            'kullanim-sartlari' => array(
                'title' => 'Kullanım Şartları',
                'intro' => 'W3Uzman’ı kullanırken içerikler, üyelik ve topluluk etkileşimleri hakkında temel kurallar.',
                'summary' => 'Daha güvenli ve saygılı bir topluluk',
                'sections' => array(
                    array('hizmet', 'W3Uzman hakkında', '<p>W3Uzman; teknoloji, yazılım, web ve dijital dünya üzerine haberler, rehberler ve kullanıcı katkıları sunan bir yayın platformudur. Bu şartlar, sitenin ziyaret edilmesi ve üyelik özelliklerinin kullanılması için temel çerçeveyi açıklar.</p><p>Kanundan doğan haklarınız saklıdır. Bu metin, kişisel verilerin işlenmesine ilişkin açık rıza metni değildir.</p>'),
                    array('hesap', 'Hesap ve erişim güvenliği', '<ul><li>Üyelik bilgilerinizin doğru ve güncel olmasına özen gösterin.</li><li>Hesap şifrenizi koruyun; başkalarının hesabına izinsiz erişmeye çalışmayın.</li><li>Hesabınızda izniniz dışında işlem yapıldığını düşünüyorsanız site yönetimine haber verin.</li><li>Otomatik hesap açma, spam ve güvenlik önlemlerini aşmaya yönelik işlemler yapmayın.</li></ul>'),
                    array('topluluk', 'İçerik ve topluluk kuralları', '<p>Haber, yorum, yanıt ve profil mesajlarında başkalarının haklarına saygı gösterin. Hukuka aykırı içerik, tehdit, taciz, nefret söylemi, izinsiz kişisel bilgi paylaşımı, yanıltıcı bağlantılar ve kötü amaçlı yazılım dağıtımı kabul edilmez.</p><p>Paylaşımınızdaki görsel, metin, kod ve diğer materyaller için gerekli haklara sahip olmalısınız. Reklam veya tanıtım niteliğindeki katkılar açıkça belirtilmeli ve site kurallarına uygun olmalıdır. Kurallara aykırı içerikler incelenebilir; erişim kısıtlaması veya içerik kaldırma gibi moderasyon işlemleri uygulanabilir.</p>'),
                    array('telif', 'Telif ve kullanım izinleri', '<p>İçeriklerin hakları W3Uzman’a, katkı sağlayan kullanıcılara veya belirtilen hak sahiplerine ait olabilir. Sitede bir içeriğin bulunması, sınırsız çoğaltma veya ticari kullanım izni verildiği anlamına gelmez.</p><p>İçerik paylaşırken kaynağa bağlantı verin ve ilgili hak sahibinin izin koşullarına uyun. Kullanıcı katkıları üzerindeki haklar kendiliğinden siteye devredilmiş sayılmaz. Alıntı ve yeniden kullanım, uygulanabilir mevzuat ile varsa içeriğin özel lisansı kapsamında değerlendirilmelidir.</p>'),
                    array('bilgilendirme', 'Bilgilendirme ve harici hizmetler', '<p>Haberler ve teknik rehberler yayımlandıkları tarihteki bilgi ve koşulları yansıtır; zaman içinde güncelliğini yitirebilir. Önemli kararlar almadan veya bir kodu çalıştırmadan önce bilgileri doğrulayın ve gerekli yedekleri alın. İçerikler kişiye özel hukuk, sağlık veya yatırım danışmanlığı değildir.</p><p>Harici bağlantılar, gömülü içerikler ve üçüncü taraf araçları ilgili sağlayıcının koşullarına tabidir. Whois aracı yalnızca bilgilendirme amaçlı ön kontrol sunar; bir alan adının kesin olarak satın alınabileceğini garanti etmez.</p>'),
                    array('erisilebilirlik', 'Hizmet değişiklikleri ve bildirimler', '<p>Bakım, güvenlik veya teknik nedenlerle bazı özellikler geçici olarak kullanılamayabilir. İçeriklerdeki önemli bir hata, hak ihlali ya da teknik sorun için ilgili sayfanın bağlantısıyla bize başvurabilirsiniz.</p><p>Bu şartlar güncellenirse yeni metin bu sayfada yayımlanır. Değişikliklerin geriye yürüdüğü veya kanuni haklarınızı ortadan kaldırdığı kabul edilmez. İletişim: <a href="mailto:bilgi@w3uzman.com">bilgi@w3uzman.com</a>.</p>'),
                ),
            ),
            'cerez-politikasi' => array(
                'title' => 'Çerez Politikası',
                'intro' => 'Çerezlerin ne işe yaradığını, hangi amaçlarla kullanılabildiğini ve tarayıcı tercihlerinizi öğrenin.',
                'summary' => 'Çerezler hakkında şeffaf bilgilendirme',
                'sections' => array(
                    array('nedir', 'Çerez nedir?', '<p>Çerezler, bir internet sitesini kullandığınızda tarayıcınızda saklanabilen küçük veri parçalarıdır. Oturumunuzu tanımak, güvenlik kontrollerini yürütmek veya tercihleri hatırlamak gibi işlevlerde kullanılabilirler. Tarayıcı depolaması gibi benzer teknolojiler de bazı özelliklerde kullanılabilir.</p>'),
                    array('temel', 'Temel oturum ve güvenlik çerezleri', '<p>W3Uzman’ın altyapısı, giriş durumunun korunması ve güvenlik kontrolleri için birinci taraf oturum mekanizmaları kullanır. Çerezlerin kapsamı giriş yapma durumunuza, seçtiğiniz özelliklere ve sunucu ayarlarına göre değişebilir.</p><div class="w3-policy-table-wrap"><table><thead><tr><th>Örnek / grup</th><th>Amaç</th><th>Süre hakkında</th></tr></thead><tbody><tr><td>qa_session</td><td>Üye giriş oturumunu tanımak.</td><td>Oturum; “beni hatırla” seçeneğinde altyapıda 30 güne kadar.</td></tr><tr><td>PHP oturum çerezi</td><td>Form güvenliği ve oturum tabanlı işlemler.</td><td>Adı ve süresi sunucu yapılandırmasına bağlıdır.</td></tr><tr><td>Ziyaretçi / güvenlik tanımlayıcıları</td><td>Kötüye kullanım kontrolleri ve ziyaretçi işlemleri.</td><td>İlgili çerezin tarayıcıda gösterilen süresine bağlıdır.</td></tr></tbody></table></div><p>Bu tablo doğrulanmış temel işlevlerden örnekler verir; tüm canlı sunucu çerezlerinin eksiksiz envanteri olduğu iddia edilmez.</p>'),
                    array('diger', 'Üçüncü taraf ve isteğe bağlı çerezler', '<p>Harici giriş sağlayıcıları, gömülü videolar, reklam veya ölçümleme hizmetleri etkinleştirildiğinde bu sağlayıcılar kendi çerezlerini kullanabilir. Her sayfada her hizmetin etkin olduğu varsayılmamalıdır; kullanılan hizmetin kendi gizlilik açıklaması da dikkate alınmalıdır.</p><p>Zorunlu olmayan çerezlerin kullanımı için geçerli bir hukuki dayanak ve gerektiğinde önceden aktif onay gerekir. Bu sayfa tek başına onay toplamaz, çerezleri engellemez ve bir tercih yönetim paneli yerine geçmez.</p>'),
                    array('yonetim', 'Çerezleri nasıl yönetebilirsiniz?', '<p>Tarayıcınızın gizlilik veya site verileri ayarlarından mevcut çerezleri görüntüleyebilir, silebilir, üçüncü taraf çerezlerini sınırlandırabilir veya belirli siteleri engelleyebilirsiniz. Ayarın adı kullandığınız tarayıcıya ve sürüme göre değişir.</p><ul><li>Çerezlerin silinmesi giriş oturumunuzu kapatabilir.</li><li>Temel oturum çerezlerini engellemek bazı üyelik ve form özelliklerini etkileyebilir.</li><li>Bir çerezi silmek, daha önce sunucuya iletilmiş bilgileri kendiliğinden silmez.</li></ul>'),
                    array('izin', 'Bilgilendirme ve izin birbirinden farklıdır', '<p>Siteyi kullanmaya devam etmek, sayfayı kaydırmak veya bu metni okumak tek başına çerezlere açık rıza verildiği anlamına gelmez. Kullanılacak bir çerez tercih aracının bilgilendirme, seçim ve tercih değiştirme olanakları ayrıca değerlendirilmelidir.</p><p class="w3-policy-source">Resmî rehber: <a href="https://www.kvkk.gov.tr/Icerik/7353/Cerez-Uygulamalari-Hakkinda-Rehber" target="_blank" rel="noopener noreferrer">KVKK — Çerez uygulamaları hakkında rehber</a>.</p>'),
                    array('iletisim', 'Sorularınız için', '<p>Çerezler veya gizlilikle ilgili sorularınızı <a href="mailto:bilgi@w3uzman.com">bilgi@w3uzman.com</a> adresine iletebilirsiniz. Teknik inceleme için sayfa adresini ve tarayıcı adını belirtmeniz yararlı olur; şifre veya hassas bilgi göndermeyin.</p>'),
                ),
            ),
        );
    }

    public function suggest_requests()
    {
        $requests = array();
        foreach ($this->documents() as $slug => $document) { $requests[] = array('title' => $document['title'], 'request' => $slug, 'nav' => null); }
        return $requests;
    }

    public function match_request($request) { $documents = $this->documents(); return isset($documents[$request]); }

    public function process_request($request)
    {
        $documents = $this->documents();
        if (!isset($documents[$request])) { return null; }
        $document = $documents[$request];
        $content = qa_content_prepare();
        $content['title'] = $document['title'];
        $content['description'] = $document['intro'];
        $content['canonical'] = qa_path_html($request, null, qa_opt('site_url'));
        $content['w3_info_page'] = true;
        $content['widgets'] = array();
        $content['sidebar'] = $content['sidepanel'] = $content['suggest_next'] = '';
        unset($content['navigation']['cat'], $content['navigation']['sub']);
        $html = '<div class="w3-policy"><nav class="w3-policy-breadcrumb" aria-label="İçerik yolu"><a href="' . qa_path_html('') . '">Anasayfa</a><span aria-hidden="true">›</span><span>' . qa_html($document['title']) . '</span></nav><header class="w3-policy-heading"><div><span class="w3-policy-kicker">W3UZMAN / BİLGİLENDİRME</span><h1>' . qa_html($document['title']) . '</h1><p>' . qa_html($document['intro']) . '</p></div><span class="w3-policy-date">Son güncelleme<time datetime="2026-10-18">18 Ekim 2026</time></span></header><nav class="w3-policy-tabs" aria-label="Bilgilendirme sayfaları">';
        foreach ($documents as $slug => $other) { $html .= '<a href="' . qa_path_html($slug) . '"' . ($slug === $request ? ' aria-current="page"' : '') . '>' . qa_html($other['title']) . '</a>'; }
        $html .= '</nav><div class="w3-policy-layout"><article class="w3-policy-article"><div class="w3-policy-summary"><span aria-hidden="true">✓</span>' . qa_html($document['summary']) . '</div>';
        foreach ($document['sections'] as $index => $section) { $html .= '<section class="w3-policy-section" id="' . $section[0] . '"><h2><span>' . sprintf('%02d', $index + 1) . '</span>' . qa_html($section[1]) . '</h2>' . $section[2] . '</section>'; }
        $html .= '</article><aside class="w3-policy-sidebar"><nav aria-label="Bu sayfada"><h2>Bu sayfada</h2><ol>';
        foreach ($document['sections'] as $section) { $html .= '<li><a href="#' . $section[0] . '">' . qa_html($section[1]) . '</a></li>'; }
        $html .= '</ol></nav><section class="w3-policy-help"><span aria-hidden="true">✉</span><h2>Bir sorunuz mu var?</h2><p>Bilgilendirme metinleriyle ilgili sorularınız için bize ulaşın.</p><a href="' . qa_path_html('feedback') . '">Bize ulaşın <span aria-hidden="true">→</span></a><a href="mailto:bilgi@w3uzman.com">bilgi@w3uzman.com</a></section></aside></div></div>';
        $content['custom'] = $html;
        return $content;
    }
}
