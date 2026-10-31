<?php
if (!defined('QA_VERSION')) { http_response_code(404); exit; }
require_once __DIR__ . '/safe-http.php';
require_once __DIR__ . '/seo-analyzer.php';

class W3SeoService
{
    private $http;
    private $directory;
    public function __construct($directory, $http = null) { $this->directory=$directory; $this->http=$http ?: new W3SeoHttpClient(); }
    public function analyze($input, $ip)
    {
        $url=$this->http->normalize($input);
        if (!$this->allowRequest($ip)) { throw new RuntimeException('Sorgu sınırına ulaşıldı veya kontrol alanı kullanılamıyor. Bir dakika sonra tekrar deneyin.'); }
        $slot=$this->slot(); if (!$slot) { throw new RuntimeException('Kontrol kapasitesi şu anda dolu. Biraz sonra tekrar deneyin.'); }
        try {
            $response=$this->http->get($url); $robots=null;
            if ($response['status']===200) {
                $origin=parse_url($response['url']); $robotUrl=$origin['scheme'].'://'.$origin['host'].'/robots.txt';
                try {
                    $f=$this->http->get($robotUrl,512000,2);
                    if ($f['status']>=200 && $f['status']<300 && !preg_match('~^\s*(?:<!doctype\s+html|<html)~i',$f['body']) && (!isset($f['headers']['content-type'][0]) || stripos($f['headers']['content-type'][0],'text/html')===false)) { $robots=array('body'=>$f['body']); }
                    elseif (in_array($f['status'],array(404,410),true)) { $robots=array('note'=>'robots.txt HTTP '.$f['status'].' döndürdü. Bu durum tek başına Googlebot taramasına engel değildir.'); }
                    else { $robots=array('note'=>'robots.txt doğrulanamadı (HTTP '.$f['status'].'). HTML yanıtı, erişim engeli veya sunucu hatasında tarama izni varsayılmadı.'); }
                } catch (RuntimeException | InvalidArgumentException $e) { $robots=array('note'=>'robots.txt kontrolü tamamlanamadı: '.$e->getMessage()); }
            }
            $report=(new W3SeoAnalyzer($this->http))->analyze($response,$robots); $report['requested']=$url;
            return $report;
        } finally { flock($slot,LOCK_UN); fclose($slot); }
    }

    protected function allowRequest($ip)
    {
        $file=@fopen($this->directory.'/w3-seo-rate.php','c+'); if (!$file) { return false; }
        if (!flock($file,LOCK_EX)) { fclose($file); return false; }
        $raw=stream_get_contents($file,65536); $newline=strpos($raw,"\n"); $data=$newline===false?null:json_decode(substr($raw,$newline+1),true); $window=(int)floor(time()/60);
        if (!is_array($data) || !isset($data['window']) || $data['window']!==$window) { $data=array('window'=>$window,'total'=>0,'ips'=>array()); }
        $key=hash('sha256',(string)$ip); $count=isset($data['ips'][$key])?(int)$data['ips'][$key]:0; $ok=$count<5 && $data['total']<30;
        if ($ok) { $data['ips'][$key]=$count+1; $data['total']++; $payload='<?php http_response_code(404); exit; ?>'."\n".json_encode($data); rewind($file); $ok=ftruncate($file,0) && fwrite($file,$payload)===strlen($payload) && fflush($file); }
        flock($file,LOCK_UN); fclose($file); return $ok;
    }
    private function slot()
    {
        for ($i=0;$i<2;$i++) { $f=@fopen($this->directory.'/w3-seo-slot-'.$i.'.php','c+'); if ($f && flock($f,LOCK_EX|LOCK_NB)) { return $f; } if ($f) { fclose($f); } }
        return null;
    }
}
