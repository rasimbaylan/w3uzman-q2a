<?php
if (!defined('QA_VERSION')) { http_response_code(404); exit; }
class w3_ssl_check_page
{
    private $directory; private $url;
    public function load_module($directory,$urltoroot){$this->directory=$directory;$this->url=$urltoroot;}
    public function suggest_requests(){return array(array('title'=>'SSL Sertifika Kontrolü','request'=>'ssl-sertifika-kontrolu','nav'=>null));}
    public function match_request($request){return $request==='ssl-sertifika-kontrolu';}
    public function process_request($request)
    {
        if(!$this->match_request($request))return null;
        $input=qa_get('domain');$host='';$error='';$result=null;
        if($input!==null){header('Cache-Control: private, no-store, max-age=0');header('CDN-Cache-Control: no-store');header('X-Robots-Tag: noindex, follow');header('Referrer-Policy: no-referrer');}
        if($input!==null){
            if(is_readable($this->directory.'ssl-service.php')){
                require_once $this->directory.'ssl-service.php';
                $cache=new Q2A_Storage_FileCacheDriver(array('enabled'=>true,'dir'=>QA_BASE_DIR.'qa-cache','keyprefix'=>'w3-ssl-v1:'));
                $service=new W3SslCheckService($cache,QA_BASE_DIR.'qa-cache');
                try{$host=$service->normalize($input);$result=$service->lookup($host,isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'unknown',qa_get('refresh')==='1');}
                catch(InvalidArgumentException $exception){$error=$exception->getMessage();}
            }else{$error='Sertifika kontrol servisi şu anda kullanılamıyor. Lütfen daha sonra tekrar deneyin.';}
        }
        $content=qa_content_prepare();$content['title']='SSL Sertifika Kontrolü';
        $content['description']='SSL sertifikasının geçerliliğini, bitiş tarihini, kalan gününü, alan adı uyumunu ve TLS bağlantısını kontrol edin.';
        $content['canonical']=qa_path_html('ssl-sertifika-kontrolu',null,qa_opt('site_url'));$content['w3_tool_page']=true;$content['widgets']=array();
        $content['sidebar']=$content['sidepanel']=$content['suggest_next']='';unset($content['navigation']['cat'],$content['navigation']['sub']);
        $content['css_src'][]=$this->url.'ssl.css?v=1';$content['script'][]='<script defer src="'.qa_html($this->url.'ssl.js?v=2').'"></script>';
        $html='<main class="w3-ssl"><header class="w3-ssl-heading"><h1>SSL Sertifika Kontrolü</h1><p>Sertifikanızın geçerliliğini ve yenileme zamanını görün.</p></header><section class="w3-ssl-search" aria-label="SSL sertifika sorgusu"><form action="'.qa_path_html('ssl-sertifika-kontrolu').'" method="get"><div><label for="w3-ssl-domain">Alan adı veya HTTPS adresi</label><input id="w3-ssl-domain" name="domain" value="'.qa_html(is_string($input)?substr($input,0,1024):'').'" placeholder="ornek.com" maxlength="253" required autocomplete="off" autocapitalize="none" spellcheck="false" aria-describedby="w3-ssl-help'.($error?' w3-ssl-error':'').'"'.($error?' aria-invalid="true" autofocus':'').'></div><button type="submit">Sertifikayı kontrol et <span aria-hidden="true">→</span></button></form><p id="w3-ssl-help">Örnek: <a href="'.qa_path_html('ssl-sertifika-kontrolu',array('domain'=>'w3uzman.com')).'">w3uzman.com</a> · www ve alt alan adları korunur. Kontrol HTTPS / 443 üzerinden yapılır.</p>';
        if($error)$html.='<p id="w3-ssl-error" class="w3-ssl-error" role="alert">'.qa_html($error).'</p>';
        $html.='</section>';
        if($result){
            $state=$result['state'];$html.='<section class="w3-ssl-result" aria-labelledby="w3-ssl-result-title"><header class="w3-ssl-result-heading"><h2 id="w3-ssl-result-title">'.qa_html($host).'</h2><a class="w3-ssl-refresh" href="'.qa_path_html('ssl-sertifika-kontrolu',array('domain'=>$host,'refresh'=>'1')).'">Yeniden kontrol et ↻</a></header><div class="w3-ssl-status w3-ssl-status--'.qa_html($state).'" role="status"><span class="w3-ssl-status-icon" aria-hidden="true">'.($state==='valid'?'✓':($state==='expiring'?'!':'×')).'</span><div><h3>'.qa_html($result['label']).'</h3><p>'.qa_html($result['message']).'</p></div></div>';
            if(isset($result['certificate'])){
                $cert=$result['certificate'];$days=$result['days_left'];
                $elapsed=time()-$cert['valid_to'];
                $timeLeft=$elapsed>=0?($elapsed<86400?'24 saatten az önce doldu':(int)floor($elapsed/86400).' gün önce doldu'):($days<1?'24 saatten az':$days.' gün');
                $html.='<div class="w3-ssl-metrics"><div><span>Kalan süre</span><strong>'.qa_html($timeLeft).'</strong><small>'.($days<=30?'Yenileme zamanını kontrol edin':'Bitiş tarihine göre').'</small></div><div><span>Başlangıç tarihi</span><strong>'.gmdate('d.m.Y',$cert['valid_from']).'</strong><small>'.gmdate('H:i:s',$cert['valid_from']).' UTC</small></div><div><span>Bitiş tarihi</span><strong>'.gmdate('d.m.Y',$cert['valid_to']).'</strong><small>'.gmdate('H:i:s',$cert['valid_to']).' UTC</small></div><div><span>Düzenleyen kuruluş</span><strong class="w3-ssl-issuer">'.qa_html($cert['issuer']?:'Belirtilmemiş').'</strong><small>'.qa_html($cert['issuer_name']).'</small></div></div><div class="w3-ssl-columns"><section class="w3-ssl-card"><h3>Kontrol sonuçları</h3><ul class="w3-ssl-checks">';
                foreach(array(array('Tarih aralığı',$result['time_valid']),array('Alan adı uyumu',$result['hostname_match']),array('TLS doğrulaması',$result['verified'])) as $check){$html.='<li><span>'.qa_html($check[0]).'</span><b class="'.($check[1]?'is-pass':'is-fail').'">'.($check[1]?'✓ Başarılı':'× Başarısız').'</b></li>';}
                $html.='</ul><p>Doğrulama, kontrol sunucumuzun güven deposuyla yapılır.</p></section><section class="w3-ssl-card"><h3>Bağlantı ve sertifika</h3><dl>';
                foreach(array('TLS sürümü'=>$result['protocol']?:'Belirlenemedi','Şifre takımı'=>$result['cipher']?:'Belirlenemedi','Ortak ad (CN)'=>$cert['subject']?:'Belirtilmemiş','İmza / anahtar'=>trim($cert['signature'].' · '.$cert['key'],' ·'),'Kontrol edilen IP'=>$result['address']) as $label=>$value){$html.='<div><dt>'.qa_html($label).'</dt><dd>'.qa_html($value).'</dd></div>';}
                $html.='</dl></section></div><section class="w3-ssl-card w3-ssl-domains"><h3>Kapsanan alan adları <span>'.count($cert['sans']).'</span></h3><div class="w3-ssl-domain-tags">';
                foreach(array_slice($cert['sans'],0,12) as $name){$html.='<span>'.qa_html($name).'</span>';}
                if(!$cert['sans'])$html.='<p>Sertifikada DNS türünde alternatif ad (SAN) bulunamadı.</p>';
                $html.='</div>';
                if(count($cert['sans'])>12){$html.='<details><summary>Tüm alan adlarını göster</summary><div class="w3-ssl-domain-tags">';foreach(array_slice($cert['sans'],12) as $name)$html.='<span>'.qa_html($name).'</span>';$html.='</div></details>';}
                $html.='</section><details class="w3-ssl-technical"><summary>Teknik kimlik bilgileri</summary><dl><div><dt>SHA-256 parmak izi</dt><dd><code id="w3-ssl-fingerprint">'.qa_html($cert['fingerprint']).'</code><button id="w3-ssl-copy" type="button" hidden>Parmak izini kopyala</button><span id="w3-ssl-copy-status" role="status"></span></dd></div><div><dt>Seri numarası</dt><dd><code>'.qa_html($cert['serial']).'</code></dd></div></dl></details>';
            }
            $html.='<p class="w3-ssl-source">Kontrol: '.gmdate('d.m.Y H:i:s',$result['checked']).' UTC'.(!empty($result['cached'])?' · Önbellekten':'').' · Sonuçlar en fazla 5 dakika önbellekte tutulabilir.</p><div class="w3-ssl-related"><a href="'.qa_path_html('dns-kayit-sorgulama',array('domain'=>$host)).'">DNS kayıtlarını incele ↗</a><a href="'.qa_path_html('whois-sorgulama',array('domain'=>$host)).'">Whois bilgileri ↗</a></div></section>';
        }else{
            $html.='<section class="w3-ssl-empty"><span aria-hidden="true">TLS</span><h2>Sertifikanızın durumunu tek ekranda öğrenin.</h2><p>Geçerlilik tarihleri, alan adı uyumu, düzenleyen kuruluş ve TLS bağlantı bilgileri.</p><div><b>Geçerli sertifika</b><b>Yenileme uyarısı</b><b>Alan adı kontrolü</b></div></section>';
        }
        $html.='<section class="w3-ssl-guide"><div><h2>Geçerli bir sertifika neyi gösterir?</h2><p>Bu araç sunucunun sunduğu sertifikanın tarihlerini, sorgulanan alan adıyla uyumunu ve TLS doğrulamasını kontrol eder. *.ornek.com gibi joker adlar yalnızca bir alt alan adı düzeyini kapsar.</p><p>Sertifikanın geçerli olması, sitenin içeriğinin güvenilir veya zararlı yazılımdan arınmış olduğunu göstermez.</p></div><div><h2>Yenileme ve kontrolün kapsamı</h2><p>Kalan süre 30 gün veya altındaysa yenileme uyarısı görünür. Otomatik yenilenen sertifikalarda da sonucun yeniden kontrol edilmesi yararlıdır.</p><p>Tek bir sunucu adresi ve gözlenen TLS bağlantısı incelenir. Bu araç kapsamlı protokol, zafiyet, OCSP veya sertifika iptal taraması yapmaz. Süreler UTC ile gösterilir.</p></div></section></main>';
        $content['custom']=$html;return $content;
    }
}
