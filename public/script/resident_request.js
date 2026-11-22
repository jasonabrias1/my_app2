document.addEventListener('DOMContentLoaded', function () {

    var links = document.querySelectorAll('.filter-tabs a');
    for (var i = 0; i < links.length; i++) {
        links[i].addEventListener('click', function () {
            this.classList.add('loading');
        });
    }
    var copyButtons = document.querySelectorAll('.copy-btn');
    for (var j = 0; j < copyButtons.length; j++) {
        copyButtons[j].addEventListener('click', copyTrackingCode);
    }


    function copyText(text) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            var copied = document.execCommand('copy');
            document.body.removeChild(ta);
            return copied;
    }

    function copyTrackingCode() {
        var btn = this;
            var code = btn.getAttribute('data-code');
            if (!code) return;

            if (copyText(code)) {
                var icon = btn.querySelector('i');
                btn.classList.add('copied');
                icon.className = 'ti ti-check';
                setTimeout(function () {
                    btn.classList.remove('copied');
                    icon.className = 'ti ti-copy';
                }, 1400);
            }else{
                window.prompt('Copy your tracking code:', code);
            }
    }
});
