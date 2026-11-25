function setSidebar(open) {
    var sb = document.getElementById('sidebar');
    var ov = document.querySelector('.sidebar-overlay');
    var btn = document.querySelector('.menu-toggle');
    if (!sb) return;
    if (open) {
        sb.classList.add('open');
        if (ov) ov.classList.add('show');
        if (btn) btn.setAttribute('aria-expanded', 'true');
        document.body.classList.add('nav-open');
    }else{
        sb.classList.remove('open');
        if (ov) ov.classList.remove('show');
        if (btn) btn.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('nav-open');
    }
}
function openSidebar() { setSidebar(true); }
function closeSidebar() { setSidebar(false); }
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeSidebar(); });
window.addEventListener('resize', function () { if (window.innerWidth > 1000) closeSidebar(); });

function toggleNavGroup(groupId) {
    var submenu = document.getElementById(groupId + '-submenu');
    var toggleBtn = document.querySelector('.nav-parent-toggle[aria-controls="' + groupId + '-submenu"]');
    if (!submenu || !toggleBtn) return;

    var isOpen = toggleBtn.getAttribute('aria-expanded') !== 'true';
    submenu.classList.toggle('nav-submenu-expanded', isOpen);
    submenu.setAttribute('aria-hidden', String(!isOpen));
    toggleBtn.classList.toggle('nav-parent-expanded', isOpen);
    toggleBtn.setAttribute('aria-expanded', String(isOpen));
}
