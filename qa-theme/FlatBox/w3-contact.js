(function () {
    'use strict';
    var message = document.getElementById('w3-contact-message');
    var counter = document.getElementById('w3-contact-counter');
    if (message && counter) {
        var updateCounter = function () {
            counter.textContent = message.value.length.toLocaleString('tr-TR') + ' / ' + message.maxLength.toLocaleString('tr-TR');
        };
        message.addEventListener('input', updateCounter);
        updateCounter();
    }
    var select = document.getElementById('w3-contact-topic');
    var captcha = document.querySelector('.w3-contact-card .q2apro_captcha input[name="captcha_challenge_field"]');
    var captchaQuestion = document.querySelector('.w3-contact-card .q2apro_captcha .captcha_q');
    if (captcha && captchaQuestion) {
        captchaQuestion.id = 'w3-contact-captcha-question';
        captcha.setAttribute('aria-labelledby', captchaQuestion.id);
    }
    document.querySelectorAll('[data-contact-topic]').forEach(function (button) {
        button.disabled = !select;
        button.addEventListener('click', function () {
            if (!select) return;
            select.value = button.getAttribute('data-contact-topic');
            select.dispatchEvent(new Event('change', { bubbles: true }));
            select.focus();
        });
    });
}());
