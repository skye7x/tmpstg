// Dashboard: walidacja przed wysłaniem (tylko UX; prawdziwe limity egzekwuje upload.php) i odliczanie wygasania.
(function () {
    const form = document.getElementById('uploadForm');
    const input = document.getElementById('fileInput');
    const box = document.getElementById('clientMsg');

    function showError(text) {
        box.textContent = text;
        box.hidden = false;
    }
    function fmt(b) {
        if (b >= 1048576) return (b / 1048576).toFixed(2) + ' MB';
        if (b >= 1024) return (b / 1024).toFixed(1) + ' KB';
        return b + ' B';
    }

    if (form && input) {
        const maxFiles = Number(form.dataset.maxFiles);
        const maxTotal = Number(form.dataset.maxTotal);
        const usedFiles = Number(form.dataset.usedFiles);
        const usedBytes = Number(form.dataset.usedBytes);

        form.addEventListener('submit', function (e) {
            box.hidden = true;
            const list = Array.from(input.files);
            const bytes = list.reduce((s, f) => s + f.size, 0);
            let err = '';
            if (list.length === 0) err = 'Choose at least one file.';
            else if (list.some(f => f.size === 0)) err = 'Empty files cannot be uploaded.';
            else if (usedFiles + list.length > maxFiles) err = 'Too many files. Limit: ' + maxFiles + ' files.';
            else if (usedBytes + bytes > maxTotal) err = 'Not enough space. Limit: ' + fmt(maxTotal) + ' in total.';
            if (err) {
                e.preventDefault();
                showError(err);
            }
        });
    }

    // Odliczanie: po upływie czasu przeładuj stronę (serwer usunie plik)
    const timers = Array.from(document.querySelectorAll('.ttl')).map(el => ({
        el: el,
        end: Date.now() + Number(el.dataset.ttl) * 1000
    }));
    let reloaded = false;
    function tick() {
        const now = Date.now();
        timers.forEach(t => {
            const left = Math.max(0, Math.ceil((t.end - now) / 1000));
            const m = Math.floor(left / 60);
            const s = String(left % 60).padStart(2, '0');
            t.el.textContent = m + ':' + s;
            t.el.classList.toggle('soon', left <= 30);
            if (left === 0 && !reloaded) {
                reloaded = true;
                setTimeout(() => location.reload(), 1000);
            }
        });
    }
    if (timers.length) {
        tick();
        setInterval(tick, 1000);
    }
})();
