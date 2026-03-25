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

    const STATE_COLOR = { libre: '#22c55e', occupee: '#ef4444', reservee: '#3b82f6', panne: '#f59e0b' };
    const STATE_LABEL = { libre: 'Libre',   occupee: 'Occupée', reservee: 'Réservée', panne: 'En panne' };

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
        ['libre', 'occupee', 'reservee', 'panne'].forEach(function(k) {
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
                    if (data.ok) window.location.reload();
                }, 1000);
            })
            .catch(function() {
                btn.disabled  = false;
                btn.textContent = 'Erreur réseau';
            });
    });
})();


/* ── Scanner RFID via ESP32 (inscription.php) ───────────────── */
(function rfidScanner() {
    const zone    = document.getElementById('scan-zone');
    const input   = document.getElementById('uid');
    const mainTxt = document.getElementById('scan-main');
    const subTxt  = document.getElementById('scan-sub');
    if (!zone || !input) return;

    let pollTimer = null;

    function setState(s) {
        zone.className = 'scan-zone' + (s ? ' ' + s : '');
    }

    function stopPoll() {
        clearInterval(pollTimer);
        pollTimer = null;
    }

    function reset() {
        stopPoll();
        setState('');
        mainTxt.textContent = 'Cliquez pour scanner un badge';
        subTxt.textContent  = 'Approchez le badge du lecteur de l\'ESP32';
    }

    function onUidReceived(uid) {
        stopPoll();
        input.value         = uid;
        mainTxt.textContent = uid;
        subTxt.textContent  = 'Badge détecté — cliquez pour scanner à nouveau';
        setState('done');
    }

    function startPoll() {
        pollTimer = setInterval(function() {
            fetch('/api/inscription_poll.php')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.uid) onUidReceived(data.uid);
                })
                .catch(function() {});
        }, 800);
    }

    function activate() {
        setState('active');
        mainTxt.textContent = 'En attente du badge…';
        subTxt.textContent  = 'Approchez le badge du lecteur de l\'ESP32';

        fetch('/api/inscription_start.php', { method: 'POST' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.ok) {
                    startPoll();
                } else {
                    alert(data.error || 'Erreur lecteur occupé.');
                    reset();
                }
            })
            .catch(function() { reset(); });
    }

    zone.addEventListener('click', function() {
        if (zone.classList.contains('done')) { reset(); return; }
        if (zone.classList.contains('active')) return;
        activate();
    });

    /* Saisie manuelle : annuler le mode scan */
    input.addEventListener('input', function() {
        if (pollTimer) reset();
    });
})();


/* ── Schedule form (admin dashboard) ────────────────────────── */
(function scheduleForm() {
    var form      = document.getElementById('schedule-form');
    var toggle    = document.getElementById('schedule_enabled');
    var timesDiv  = document.getElementById('schedule-times');
    var msgDiv    = document.getElementById('schedule-msg');
    var statusDiv = document.getElementById('schedule-status');
    if (!form) return;

    // Toggle enable/disable times section
    toggle.addEventListener('change', function() {
        if (toggle.checked) {
            timesDiv.style.opacity = '';
            timesDiv.style.pointerEvents = '';
        } else {
            timesDiv.style.opacity = '0.4';
            timesDiv.style.pointerEvents = 'none';
        }
        // Auto-save toggle state
        var body = new FormData();
        body.append('schedule_enabled', toggle.checked ? '1' : '0');
        fetch('/api/save_schedule.php', { method: 'POST', body: body })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.ok) {
                    updateStatus(data);
                    showMsg('ok', toggle.checked ? 'Contrôle horaire activé.' : 'Contrôle horaire désactivé — accès libre.');
                }
            })
            .catch(function() { showMsg('err', 'Erreur réseau.'); });
    });

    // Save schedule times
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var body = new FormData(form);
        body.set('schedule_enabled', toggle.checked ? '1' : '0');

        var btn = document.getElementById('btn-save-schedule');
        btn.disabled = true;
        btn.textContent = 'Enregistrement…';

        fetch('/api/save_schedule.php', { method: 'POST', body: body })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btn.disabled = false;
                btn.textContent = 'Enregistrer';
                if (data.ok) {
                    updateStatus(data);
                    showMsg('ok', 'Horaires enregistrés avec succès.');
                } else {
                    showMsg('err', data.error || 'Erreur inconnue.');
                }
            })
            .catch(function() {
                btn.disabled = false;
                btn.textContent = 'Enregistrer';
                showMsg('err', 'Erreur réseau.');
            });
    });

    function updateStatus(data) {
        if (!statusDiv) return;
        if (!data.schedule_enabled) {
            statusDiv.innerHTML = '<span class="auth on">Accès libre</span>';
        } else if (data.is_open) {
            statusDiv.innerHTML = '<span class="auth on">Ouvert</span>';
        } else {
            statusDiv.innerHTML = '<span class="auth off">Fermé</span>';
        }
    }

    function showMsg(type, text) {
        msgDiv.className = 'alert ' + type;
        msgDiv.textContent = text;
        msgDiv.style.display = '';
        setTimeout(function() { msgDiv.style.display = 'none'; }, 4000);
    }
})();
