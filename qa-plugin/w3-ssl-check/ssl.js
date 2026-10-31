(function () {
    'use strict';
    var root=document.querySelector('.w3-ssl'); if(!root)return;
    var form=root.querySelector('.w3-ssl-search form');
    var submitButton=form.querySelector('button');
    var submitMarkup=submitButton.innerHTML;
    form.addEventListener('submit',function(){var button=form.querySelector('button');button.disabled=true;button.textContent='Kontrol ediliyor…';});
    window.addEventListener('pageshow',function(){submitButton.disabled=false;submitButton.innerHTML=submitMarkup;});
    var button=root.querySelector('#w3-ssl-copy');if(!button)return;button.hidden=false;
    button.addEventListener('click',function(){
        var text=root.querySelector('#w3-ssl-fingerprint').textContent;
        function done(ok){root.querySelector('#w3-ssl-copy-status').textContent=ok?'Parmak izi kopyalandı.':'Kopyalanamadı; metni seçerek kopyalayabilirsiniz.';}
        function fallback(){var area=document.createElement('textarea');area.value=text;area.setAttribute('readonly','');area.style.position='fixed';area.style.opacity='0';document.body.appendChild(area);area.select();var ok=false;try{ok=document.execCommand('copy');}catch(_){}area.remove();button.focus();done(ok);}
        if(navigator.clipboard&&window.isSecureContext)navigator.clipboard.writeText(text).then(function(){done(true);},fallback);else fallback();
    });
}());
