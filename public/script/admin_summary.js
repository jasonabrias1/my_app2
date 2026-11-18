function showList(card) {
    var wrap = document.getElementById('listWrap');
    var hint = document.getElementById('sumHint');
    var wasActive = card.classList.contains('active');

    var cards = document.querySelectorAll('.sum-card');
    for (var i = 0; i < cards.length; i++) {
        cards[i].classList.remove('active');
    }

    if (wasActive) {
        wrap.style.display = 'none';
        hint.style.display = '';
        return;
    }

    card.classList.add('active');
    var all = card.getAttribute('data-all') == '1';
    var type = card.getAttribute('data-type');
    var n = 0;

   var rows = document.querySelectorAll('#listTable tr[data-type]');
   for (var j = 0; j < rows.length; j++) {
    var tr = rows[j];
    if (all || tr.getAttribute('data-type') == type) {
        n++;
        tr.style.display = '';
        tr.querySelector('.rownum').textContent = n;
    }else{
        tr.style.display = 'none';
    }
}

    hint.style.display = 'none';
    wrap.style.display = '';
    wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function downloadPDF(btn) {
    var table = document.getElementById('listTable');
    if (!table) { alert('No requests to download.'); return; }
    if (typeof html2pdf == 'undefined') { alert('PDF tool failed to load. Check your internet connection.'); return; }

    var active = document.querySelector('.sum-card.active');
    var type = null;
    if (active && active.getAttribute('data-all') != '1') {
        type = active.getAttribute('data-type');
    }

    var box = document.createElement('div');
    box.style.cssText = 'background:#fff;color:#000;font-family:Arial,sans-serif;width:700px;padding:6px;';

    box.appendChild(document.querySelector('.sum-head').cloneNode(true));
    box.appendChild(document.querySelector('.panel > hr').cloneNode(true));

    var t = table.cloneNode(true);
    t.removeAttribute('id');
    t.style.cssText = 'width:100%;border-collapse:collapse;font-size:12px;';
    var n = 0;
    var rows = t.querySelectorAll('tr[data-type]');
    for (var i = 0; i < rows.length; i++) {
        var tr = rows[i];
        if (type && tr.getAttribute('data-type') != type){
            tr.parentNode.removeChild(tr);
        }else{
            n++;
            tr.style.display = '';
            tr.querySelector('.rownum').textContent = n;
        }
    }

    var cells = t.querySelectorAll('th, td');
    for (var j = 0; j < cells.length; j++) {
        cells[j].style.cssText = 'padding:8px 6px;text-align:left;border-bottom:1px solid #accc;';
    }
    box.appendChild(t);

    var label = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    var stamp = new Date().toISOString().slice(0, 10);
    html2pdf().set({
        margin: 12,
        filename: 'Summary-of-Requests-' + stamp + '.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
        pagebreak: { mode: ['css', 'legacy'], avoid: 'tr' }
    }).from(box).save().then(function () {
        btn.disabled = false;
        btn.innerHTML = label;
    }).catch(function () {
        btn.disabled = false;
        btn.innerHTML = label;
        alert('Could not create the PDF.');
    });
}