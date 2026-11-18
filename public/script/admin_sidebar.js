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
    var toggleBtn = submenu.previousElementSibling;
    var isOpen = submenu.classList.toggle('open');
    toggleBtn.classList.toggle('expanded', isOpen);
    toggleBtn.setAttribute('aria-expanded', String(isOpen));
}