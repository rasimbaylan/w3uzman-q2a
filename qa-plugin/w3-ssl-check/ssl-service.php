<?php
if (!defined('QA_VERSION')) { http_response_code(404); exit; }

class W3SslCheckService
{
    private $cache;
    private $rateDirectory;
    public function __construct($cache, $rateDirectory) { $this->cache=$cache; $this->rateDirectory=$rateDirectory; }
    public function normalize($input)
    {
        if (!is_string($input) || strlen($input)>1024 || preg_match('/[\x00-\x20\x7f]/', trim($input))) { throw new InvalidArgumentException('Geçerli bir alan adı yazın. Örnek: w3uzman.com'); }
        $input=trim($input);
        $url=parse_url(strpos($input,'://')===false?'https://'.$input:$input);
        if (!$url || empty($url['host']) || !isset($url['scheme']) || !in_array(strtolower($url['scheme']),array('http','https'),true) || isset($url['user']) || isset($url['pass']) || (isset($url['port']) && $url['port']!==443)) { throw new InvalidArgumentException('Alan adı veya HTTPS adresi yazın. Kontrol yalnızca 443 portunda yapılır.'); }
        $host=strtolower(rtrim($url['host'],'.'));
        if (filter_var(trim($host,'[]'),FILTER_VALIDATE_IP)) { throw new InvalidArgumentException('IP adresi yerine sertifikasını kontrol etmek istediğiniz alan adını yazın.'); }
        if (preg_match('/[^\x20-\x7e]/',$host)) {
            if (!function_exists('idn_to_ascii')) { throw new InvalidArgumentException('Türkçe karakterli alan adını xn-- (Punycode) biçiminde yazın.'); }
            $info=array(); $host=idn_to_ascii($host,IDNA_USE_STD3_RULES,INTL_IDNA_VARIANT_UTS46,$info);
            if (!$host || !empty($info['errors'])) { throw new InvalidArgumentException('Alan adının karakterleri geçerli değil.'); }
        }
        if (strlen($host)>253 || !preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+(?:[a-z]{2,63}|xn--[a-z0-9-]{2,59})$/D',$host)) { throw new InvalidArgumentException('Uzantısıyla birlikte geçerli bir alan adı yazın. www ve alt alan adları korunur.'); }
        return $host;
    }
    public function publicAddress($ip)
    {
        if (!is_string($ip) || filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE)===false) return false;
        $packed=inet_pton($ip);
        $ranges=strlen($packed)===4?array('0.0.0.0/8','100.64.0.0/10','192.0.0.0/24','192.0.2.0/24','192.88.99.0/24','198.18.0.0/15','198.51.100.0/24','203.0.113.0/24','224.0.0.0/4','240.0.0.0/4'):array('2001::/23','2001:db8::/32','2002::/16','3fff::/20');
        if (strlen($packed)===16 && !$this->inRange($packed,'2000::/3')) return false;
        foreach($ranges as $range) if($this->inRange($packed,$range)) return false;
        return true;
    }
    private function inRange($packed,$cidr)
    {
        list($network,$bits)=explode('/',$cidr);$network=inet_pton($network);
        if(strlen($packed)!==strlen($network))return false;
        $bytes=(int)floor($bits/8);$remainder=(int)$bits%8;
        return substr($packed,0,$bytes)===substr($network,0,$bytes) && (!$remainder || ((ord($packed[$bytes])^ord($network[$bytes]))&(255<<(8-$remainder)))===0);
    }
    public function lookup($host,$ip,$refresh=false)
    {
        $host=$this->normalize($host);$key='ssl:'.hash('sha256',$host);
        $cached=$this->cache->get($key);
        if(!$refresh && is_array($cached) && isset($cached['expires'],$cached['certificate']) && $cached['expires']>time()) {
            $cached['cached']=true;unset($cached['expires']);
            return $this->assess($cached);
        }
        if(!function_exists('openssl_x509_parse') || !function_exists('stream_socket_client') || !function_exists('stream_socket_enable_crypto')) return $this->failure($host,'Sertifika kontrol servisi şu anda kullanılamıyor. Lütfen daha sonra tekrar deneyin.');
        if(!$this->allowRequest($ip))return $this->failure($host,'Sorgu sınırına ulaşıldı veya önbellek kullanılamıyor. Bir dakika sonra tekrar deneyin.');
        $address=$this->resolve($host);
        if(!$address)return $this->failure($host,'Alan adı için erişilebilir bir genel IP adresi bulunamadı. DNS kaydını kontrol edip tekrar deneyin.');
        // Pin the validated address. SNI and hostname verification still use the original name.
        $probe=$this->probe($host,$address,true);
        $verified=$probe['verified'];
        if(!$probe['certificate'] && !$verified && $probe['connected']) {
            // Diagnostic capture only: no HTTP, credentials or application data is sent.
            // Its result can NEVER promote a failed strict verification to a valid result.
            $diagnostic=$this->probe($host,$address,false);
            if($diagnostic['certificate'])$probe=$diagnostic;
        }
        if(!$probe['certificate'])return $this->failure($host,'443 portunda sertifika okunamadı. Sunucu bağlantıyı reddediyor, zaman aşımına uğruyor veya desteklenen TLS bağlantısı kurulamıyor.');
        $parsed=@openssl_x509_parse($probe['certificate']);
        if(!is_array($parsed) || !isset($parsed['validFrom_time_t'],$parsed['validTo_time_t']))return $this->failure($host,'Sunucunun sertifikası geçerli biçimde okunamadı.');
        $sans=array();
        foreach(explode(',',isset($parsed['extensions']['subjectAltName'])?$parsed['extensions']['subjectAltName']:'') as $entry) {
            $entry=trim($entry);
            if(stripos($entry,'DNS:')===0)$sans[]=strtolower(substr($entry,4));
        }
        $sans=array_values(array_unique($sans));
        $issuer=isset($parsed['issuer'])?$parsed['issuer']:array();
        $publicKey=@openssl_pkey_get_public($probe['certificate']);
        $keyDetails=$publicKey?@openssl_pkey_get_details($publicKey):false;
        $algorithms=array(OPENSSL_KEYTYPE_RSA=>'RSA',OPENSSL_KEYTYPE_DSA=>'DSA',OPENSSL_KEYTYPE_DH=>'DH',OPENSSL_KEYTYPE_EC=>'EC');
        $fingerprint=@openssl_x509_fingerprint($probe['certificate'],'sha256');
        $result=array('host'=>$host,'state'=>'unverified','checked'=>time(),'cached'=>false,'verified'=>$verified,'address'=>$address,'protocol'=>$probe['protocol'],'cipher'=>$probe['cipher'],
            'certificate'=>array('subject'=>isset($parsed['subject']['CN'])?$this->text($parsed['subject']['CN']):'', 'issuer'=>isset($issuer['O'])?$this->text($issuer['O']):(isset($issuer['CN'])?$this->text($issuer['CN']):''),'issuer_name'=>isset($issuer['CN'])?$this->text($issuer['CN']):'', 'valid_from'=>(int)$parsed['validFrom_time_t'],'valid_to'=>(int)$parsed['validTo_time_t'],'sans'=>$sans,'signature'=>isset($parsed['signatureTypeSN'])?$parsed['signatureTypeSN']:'','serial'=>isset($parsed['serialNumberHex'])?$parsed['serialNumberHex']:'','fingerprint'=>$fingerprint?strtoupper(implode(':',str_split($fingerprint,2))):'','key'=>$keyDetails&&isset($algorithms[$keyDetails['type']])?$algorithms[$keyDetails['type']].' '.$keyDetails['bits'].' bit':''));
        $result=$this->assess($result);
        $entry=$result;$entry['expires']=time()+($verified?min(300,max(1,$result['certificate']['valid_to']-time())):60);
        $this->cache->set($key,$entry,5);
        return $result;
    }
    private function text($value) {return is_array($value)?implode(' · ',array_filter($value,'is_string')):(is_string($value)?$value:'');}
    public function matchesHost($host,$names)
    {
        foreach($names as $name) {
            if(!is_string($name))continue;$name=strtolower(rtrim($name,'.'));
            if($name===$host)return true;
            if(strpos($name,'*.')===0 && substr_count($name,'*')===1 && substr_count($host,'.')===substr_count($name,'.') && substr($host,-strlen(substr($name,1)))===substr($name,1))return true;
        }
        return false;
    }
    public function assess($result)
    {
        $cert=$result['certificate'];$now=time();
        $result['days_left']=(int)floor(($cert['valid_to']-$now)/86400);
        $result['hostname_match']=$this->matchesHost($result['host'],$cert['sans']);
        $result['time_valid']=$cert['valid_from']<=$now && $cert['valid_to']>$now;
        if($cert['valid_to']<=$now){$state='expired';$label='Sertifikanın süresi dolmuş';$message='Sertifika yenilenmeli. Tarayıcılar bu sertifikayı geçerli kabul etmez.';}
        elseif($cert['valid_from']>$now){$state='not_yet_valid';$label='Sertifika henüz geçerli değil';$message='Sertifikanın başlangıç tarihi henüz gelmemiş. Sunucu saatini ve sertifika tarihlerini kontrol edin.';}
        elseif(!$result['hostname_match']){$state='hostname_mismatch';$label='Alan adı sertifikayla uyuşmuyor';$message='Sertifikanın kapsadığı adlar, sorguladığınız alan adıyla eşleşmiyor.';}
        elseif(!$result['verified']){$state='unverified';$label='TLS doğrulaması başarısız';$message='Sertifika okunabildi, ancak sunucumuz güvenilir bir TLS bağlantısı doğrulayamadı. Güven zinciri, ara sertifikalar veya kontrol sunucusunun güven deposu incelenmeli.';}
        elseif($result['days_left']<=30){$state='expiring';$label='Sertifika geçerli, yenileme yaklaşıyor';$message='TLS doğrulaması başarılı. Sertifikanın süresi 30 gün içinde dolacak; otomatik yenilemeyi kontrol edin.';}
        else{$state='valid';$label='SSL sertifikası geçerli';$message='TLS doğrulaması, tarih aralığı ve alan adı uyumu bu kontrolde başarılı.';}
        $result['state']=$state;$result['label']=$label;$result['message']=$message;return $result;
    }
    protected function resolve($host)
    {
        foreach(array('A'=>1,'AAAA'=>28) as $type=>$number) {
            $response=$this->dnsRequest('https://dns.google/resolve?'.http_build_query(array('name'=>$host,'type'=>$number,'cd'=>'0','edns_client_subnet'=>'0.0.0.0/0'),'', '&',PHP_QUERY_RFC3986));
            $data=$response['status']===200?json_decode($response['body'],true):null;
            if(!is_array($data)||!isset($data['Status'],$data['Question'][0]['name'],$data['Question'][0]['type'])||$data['Status']!==0||!is_string($data['Question'][0]['name'])||strcasecmp(rtrim($data['Question'][0]['name'],'.'),$host)!==0||$data['Question'][0]['type']!==$number||!empty($data['TC'])||!empty($data['CD']))return null;
            $addresses=array();
            foreach(isset($data['Answer'])&&is_array($data['Answer'])?$data['Answer']:array() as $answer) {
                if(isset($answer['type'],$answer['data'])&&$answer['type']===$number) {
                    if(!is_string($answer['data'])||!$this->publicAddress($answer['data']))return null;
                    $addresses[]=inet_ntop(inet_pton($answer['data']));
                }
            }
            if($addresses)return $addresses[0];
        }
        return null;
    }
    protected function probe($host,$address,$strict)
    {
        $result=array('verified'=>false,'connected'=>false,'certificate'=>null,'protocol'=>'','cipher'=>'');
        // Recheck immediately before connecting; do not resolve the name a second time.
        if(!$this->publicAddress($address))return $result;
        $context=stream_context_create(array('ssl'=>array('peer_name'=>$host,'SNI_enabled'=>true,'verify_peer'=>$strict,'verify_peer_name'=>$strict,'capture_peer_cert'=>true,'verify_depth'=>8,'disable_compression'=>true)));
        $endpoint=strpos($address,':')!==false?'['.$address.']':$address;
        $socket=@stream_socket_client('tcp://'.$endpoint.':443',$errno,$error,3,STREAM_CLIENT_CONNECT,$context);
        if(!$socket)return $result;
        $result['connected']=true;stream_set_timeout($socket,5);
        $ok=@stream_socket_enable_crypto($socket,true,STREAM_CRYPTO_METHOD_TLS_CLIENT);
        $options=stream_context_get_options($socket);$meta=stream_get_meta_data($socket);
        $result['verified']=$strict&&$ok===true;
        if(isset($options['ssl']['peer_certificate']))$result['certificate']=$options['ssl']['peer_certificate'];
        if(isset($meta['crypto']['protocol']))$result['protocol']=$meta['crypto']['protocol'];
        if(isset($meta['crypto']['cipher_name']))$result['cipher']=$meta['crypto']['cipher_name'];
        fclose($socket);return $result;
    }
    protected function dnsRequest($url)
    {
        $status=0;$body='';
        if(function_exists('curl_init')){
            $curl=curl_init($url);curl_setopt_array($curl,array(CURLOPT_CONNECTTIMEOUT=>2,CURLOPT_TIMEOUT=>4,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_HTTPHEADER=>array('Accept: application/json'),CURLOPT_WRITEFUNCTION=>function($curl,$chunk)use(&$body){if(strlen($body)+strlen($chunk)>65536)return 0;$body.=$chunk;return strlen($chunk);}));
            if(curl_exec($curl)!==false)$status=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE);curl_close($curl);
        }else{
            $context=stream_context_create(array('http'=>array('timeout'=>4,'follow_location'=>0,'ignore_errors'=>true,'header'=>"Accept: application/json\r\n"),'ssl'=>array('verify_peer'=>true,'verify_peer_name'=>true)));
            $body=@file_get_contents($url,false,$context,0,65537);
            if(is_string($body)&&strlen($body)<=65536&&isset($http_response_header[0])&&preg_match('/\s(\d{3})\s/',$http_response_header[0],$m))$status=(int)$m[1];else$body='';
        }
        return array('status'=>$status,'body'=>$body);
    }
    protected function allowRequest($ip)
    {
        $file=@fopen($this->rateDirectory.'/w3-ssl-rate.php','c+');if(!$file)return false;
        if(!flock($file,LOCK_EX)){fclose($file);return false;}
        $raw=stream_get_contents($file,65536);$newline=strpos($raw,"\n");$rates=$newline===false?array():json_decode(substr($raw,$newline+1),true);$window=(int)floor(time()/60);
        if(!is_array($rates)||!isset($rates['window'])||$rates['window']!==$window)$rates=array('window'=>$window,'total'=>0,'ips'=>array());
        $key=hash('sha256',(string)$ip);$count=isset($rates['ips'][$key])?(int)$rates['ips'][$key]:0;$allowed=$count<10&&$rates['total']<120;
        if($allowed){$rates['ips'][$key]=$count+1;$rates['total']++;$payload='<?php http_response_code(404); exit; ?>'."\n".json_encode($rates);rewind($file);$allowed=ftruncate($file,0)&&fwrite($file,$payload)===strlen($payload)&&fflush($file);}
        flock($file,LOCK_UN);fclose($file);return $allowed;
    }
    private function failure($host,$message){return array('host'=>$host,'state'=>'error','label'=>'Kontrol tamamlanamadı','message'=>$message,'checked'=>time(),'cached'=>false);}
}
