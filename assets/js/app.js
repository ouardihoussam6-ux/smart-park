/* Smart Park — app.js */

/* ── Dashboard live refresh ─────────────────────────────────── */
(function dashboardRefresh() {
    const grid    = document.getElementById('places-grid');
    const logsEl  = document.getElementById('logs-body');
    const statsEl = document.getElementById('stats-row');
    if (!grid && !logsEl) return;          // not on dashboard

    const ACTION_LABEL = {
        lecture:          'Lecture',
        proposition_slot: 'Proposition',
        slot_valide:      'Validée',
        slot_libere:      'Libérée',
        slot_defaut:      'Défaut',
    };

    const STATE_COLOR = { libre: '#22c55e', occupee: '#ef4444', panne: '#f59e0b' };
    const STATE_LABEL = { libre: 'Libre',   occupee: 'Occupée', panne: 'En panne' };

    function h(s) {
        return String(s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function renderPlaces(places) {
        if (!grid) return;
        places.forEach(function(p) {
            const card = grid.querySelector('[data-place="' + p.id_place + '"]');
            if (!card) return;
            const etat = p.etat || 'libre';
            card.className = 'place ' + etat;
            const chip = card.querySelector('.place-chip');
            if (chip) {
                chip.className = 'place-chip ' + etat;
                chip.innerHTML =
                    '<span class="dot" style="background:' + STATE_COLOR[etat] + '"></span>' +
                    '<span class="label">' + (STATE_LABEL[etat] || h(etat)) + '</span>';
            }
            const uid = card.querySelector('.place-uid');
            if (uid) uid.textContent = p.uid_actuel || '';
        });
    }

    function renderStats(stats) {
        if (!statsEl) return;
        ['libre', 'occupee', 'panne'].forEach(function(k) {
            const span = statsEl.querySelector('[data-stat="' + k + '"]');
            if (!span) return;
            const count = stats[k] || 0;
            const strong = span.querySelector('strong');
            if (strong) strong.textContent = count;
        });
    }

    function renderLogs(logs) {
        if (!logsEl) return;
        if (!logs.length) {
            logsEl.innerHTML = '<tr><td colspan="4" class="empty">Aucune activité.</td></tr>';
            return;
        }
        logsEl.innerHTML = logs.map(function(log) {
            const d   = new Date(log.date_heure);
            const day = String(d.getDate()).padStart(2, '0');
            const mon = String(d.getMonth() + 1).padStart(2, '0');
            const hh  = String(d.getHours()).padStart(2, '0');
            const mm  = String(d.getMinutes()).padStart(2, '0');
            const dt  = day + '/' + mon + ' ' + hh + ':' + mm;
            const lbl = h(ACTION_LABEL[log.action] || log.action);
            const slotHtml = log.slot > 0
                ? '<span class="num">' + parseInt(log.slot) + '</span>'
                : '<span class="num empty">—</span>';
            return '<tr>' +
                '<td data-label="Date" style="color:#888">' + dt + '</td>' +
                '<td data-label="Badge"><span class="mono">' + h(log.tag_id) + '</span></td>' +
                '<td data-label="Action"><span class="chip ' + h(log.action) + '">' + lbl + '</span></td>' +
                '<td data-label="Place">' + slotHtml + '</td>' +
                '</tr>';
        }).join('');
    }

    function refresh() {
        fetch('/api/status.php')
            .then(function(r) { return r.ok ? r.json() : null; })
            .then(function(data) {
                if (!data) return;
                if (data.places) renderPlaces(data.places);
                if (data.stats)  renderStats(data.stats);
                if (data.recent) renderLogs(data.recent);
            })
            .catch(function() {});
    }

    setInterval(refresh, 5000);
})();


/* ── Reset ESP32 button ─────────────────────────────────────── */
(function resetButton() {
    const btn = document.getElementById('btn-reset');
    if (!btn) return;

    btn.addEventListener('click', function() {
        btn.disabled = true;
        btn.textContent = 'Envoi…';

        fetch('/api/set_reset.php', { method: 'POST' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btn.textContent = data.ok ? 'Ordre envoyé' : 'Erreur';
                setTimeout(function() {
                    btn.disabled    = false;
                    btn.innerHTML   =
                        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">' +
                        '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>' +
                        ' Redémarrer l\'ESP32';
                }, 3000);
            })
            .catch(function() {
                btn.disabled  = false;
                btn.textContent = 'Erreur réseau';
            });
    });
})();


/* ── RFID USB HID scanner (inscription.php) ─────────────────── */
(function rfidScanner() {
    const zone    = document.getElementById('scan-zone');
    const input   = document.getElementById('uid');
    const mainTxt = document.getElementById('scan-main');
    const subTxt  = document.getElementById('scan-sub');
    if (!zone || !input) return;

    let active  = false;
    let buffer  = '';
    let timer   = null;

    function setState(s) {
        zone.className = 'scan-zone' + (s ? ' ' + s : '');
    }

    function finish(uid) {
        active = false;
        buffer = '';
        clearTimeout(timer);

        uid = uid.trim().toUpperCase();
        if (!uid) { reset(); return; }

        input.value      = uid;
        mainTxt.textContent = uid;
        subTxt.textContent  = 'Badge détecté — cliquez pour scanner à nouveau';
        setState('done');
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function reset() {
        active = false;
        buffer = '';
        clearTimeout(timer);
        setState('');
        mainTxt.textContent = 'Cliquez pour scanner un badge';
        subTxt.textContent  = 'Approchez le badge du lecteur après avoir cliqué';
    }

    function activate() {
        if (active) return;
        active = true;
        buffer = '';
        setState('active');
        mainTxt.textContent = 'En attente du badge…';
        subTxt.textContent  = 'Approchez maintenant le badge du lecteur USB';
        input.focus();
    }

    zone.addEventListener('click', function() {
        if (zone.classList.contains('done')) { reset(); return; }
        activate();
    });

    /* Intercept keyboard input when scanner active.
       USB HID readers send keystrokes very fast (< 30 ms apart) then Enter.
       We capture all printable chars + Enter, suppress them from the input field,
       buffer them, and commit on Enter or after 400 ms of silence. */
    document.addEventListener('keydown', function(e) {
        if (!active) return;

        if (e.key === 'Enter') {
            e.preventDefault();
            finish(buffer);
            return;
        }

        if (e.key.length === 1) {          // printable character
            e.preventDefault();
            buffer += e.key;
            clearTimeout(timer);
            timer = setTimeout(function() { finish(buffer); }, 400);
        }
    });

    /* If user clicks somewhere else, deactivate */
    document.addEventListener('click', function(e) {
        if (active && !zone.contains(e.target) && e.target !== input) {
            reset();
        }
    });

    /* Also allow manual typing in the input — clear scan UI */
    input.addEventListener('input', function() {
        if (zone.classList.contains('done') || zone.classList.contains('active')) {
            setState('');
            mainTxt.textContent = 'Cliquez pour scanner un badge';
            subTxt.textContent  = 'Approchez le badge du lecteur après avoir cliqué';
        }
        active = false;
        buffer = '';
    });
})();
