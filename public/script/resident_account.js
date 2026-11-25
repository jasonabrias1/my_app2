
    function initMobileMenu() {
        var toggle = document.getElementById('menu-toggle');
        var nav = document.getElementById('nav-section');
        if (toggle && nav) {
            toggle.addEventListener('click', function () {
                nav.classList.toggle('open');
            });
        }
    }

    function selectTab() {
        var key = this.getAttribute('data-tab');
        var tabs = document.querySelectorAll('.tab');
        for (var i = 0; i < tabs.length; i++) {
            if (tabs[i] == this) {
                tabs[i].setAttribute('aria-selected', 'true');
            }else{
                tabs[i].setAttribute('aria-selected', 'false');
            }
        }
        var panels = document.querySelectorAll('.tab-panel');
        for (var j = 0; j < panels.length; j++) {
            if (panels[j].id == 'panel-' + key) {
                panels[j].classList.add('active');
            }else{
                panels[j].classList.remove('active');
            }
        }
    }

    function initTabs() {
        var tabs = document.querySelectorAll('.tab');
        for (var i = 0; i < tabs.length; i++) {
            tabs[i].addEventListener('click', selectTab);
        }
    }

    function initProfileEditor() {
        var toggle = document.getElementById('profile-edit-toggle');
        var cancel = document.getElementById('profile-edit-cancel');
        var panel = document.getElementById('profile-edit-panel');
        if (!toggle || !panel) return;

        function setOpen(open) {
            panel.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) {
                var firstField = panel.querySelector('input[name="first_name"]');
                if (firstField) firstField.focus();
            }
        }

        toggle.addEventListener('click', function () {
            setOpen(panel.hidden);
        });
        if (cancel) {
            cancel.addEventListener('click', function () {
                setOpen(false);
            });
        }
    }


    var STRENGTH_LABELS = ['', 'Weak', 'Fair', 'Good', 'Strong'];

        function getLevel(v) {
            if (!v) return 0;
            if (v.length < 8) return 1;

            var score = 1;
            if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
            if (/\d/.test(v)) score++;
            if (/[^A-Za-z0-9]/.test(v)) score++;
            return score;
        }

        function showStrength() {
            var np = document.getElementById('new_password');
            var out = document.getElementById('strength');
            var level = getLevel(np.value);
            out.setAttribute('data-level', level);
            out.textContent = '';
            if (level == 0) return;

            var label = document.createElement('span');
            label.textContent = 'Password strength:';
            var word = document.createElement('span');
            word.className = 'strength-word';
            word.textContent = STRENGTH_LABELS[level];
            out.appendChild(label);
            out.appendChild(word);
        }

        function showMatchHint() {
            var np = document.getElementById('new_password');
            var cp = document.getElementById('confirm_password');
            var hint = document.getElementById('match-hint');

            if (!cp.value) {
                hint.textContent = '';
                hint.className = 'field-hint';
                return;
            }
            if (np.value === cp.value) {
                hint.textContent = 'Passwords match.';
                hint.className = 'field-hint ok';
            }else{
                hint.textContent = 'Passwords do not match yet.';
                hint.className = 'field-hint bad';
            }
        }

       function initPasswordChecks() {
    var np = document.getElementById('new_password');
    var cp = document.getElementById('confirm_password');
    var out = document.getElementById('strength');
    if (!np || !cp || !out) return;

    np.addEventListener('input', function () {
        showStrength();
        showMatchHint();
    });
    cp.addEventListener('input', showMatchHint);
    showStrength();
}


function clearPasswords() {
    var inputs = document.querySelectorAll('.pw-wrap input');
    for (var i = 0; i < inputs.length; i++) {
        inputs[i].value = '';
    }

    if (document.getElementById('new_password')) {
        showStrength();
        showMatchHint();
    }
}


function togglePassword() {
    var input = document.getElementById(this.getAttribute('data-target'));
    if (input.type == 'password') {
        input.type = 'text';
        this.setAttribute('aria-pressed', 'true');
        this.setAttribute('aria-label', 'Hide password');
        this.querySelector('i').className = 'ti ti-eye-off';
    } else {
        input.type = 'password';
        this.setAttribute('aria-pressed', 'false');
        this.setAttribute('aria-label', 'Show password');
        this.querySelector('i').className = 'ti ti-eye';
    }
}

function initEyeButtons() {
    var buttons = document.querySelectorAll('.pw-toggle');
    for (var i = 0; i < buttons.length; i++) {
        buttons[i].addEventListener('click', togglePassword);
    }
}


    function initResendCountdown() {
        var btn = document.getElementById('resend-btn');
        var label = document.getElementById('resend-label');
        if (!btn || !label) return;
        var wait = parseInt(btn.getAttribute('data-wait'), 10) || 0;
        if (wait <= 0) return;
            label.textContent = 'Resend code in ' + wait + 's';
    var timer = setInterval(function () {
        wait--;
        if (wait <= 0) {
            clearInterval(timer);
            btn.disabled = false;
            label.textContent = 'Resend code';
        } else {
            label.textContent = 'Resend code in ' + wait + 's';
        }
    }, 1000);
}

document.addEventListener('DOMContentLoaded', function () {
    initMobileMenu();
    initTabs();
    initProfileEditor();
    initEyeButtons();
    initPasswordChecks();
    initResendCountdown();

    setTimeout(clearPasswords, 100);
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) clearPasswords();
    });

});
