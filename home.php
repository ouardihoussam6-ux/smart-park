<?php
declare(strict_types=1);

require_once __DIR__ . '/models/Place.php';
require_once __DIR__ . '/models/Badge.php';
require_once __DIR__ . '/models/Setting.php';
require_once __DIR__ . '/includes/layout.php';

require_login();

// (L'admin n'est plus redirigé, il peut ainsi prévisualiser la vue client)

$user = current_user();

// Fetch parking stats (read-only for clients)
try {
    $stats = Place::stats();
    $places = Place::all();
    $error = null;
} catch (Throwable) {
    $stats = ['libre' => 0, 'occupee' => 0, 'panne' => 0];
    $places = [];
    $error = 'Impossible de charger les données du parking.';
}

// Fetch user's badge
$existingBadge = null;
try {
    $st = Database::get()->prepare('SELECT * FROM badges WHERE user_id = ? LIMIT 1');
    $st->execute([$user['id']]);
    $existingBadge = $st->fetch() ?: null;
} catch (Throwable) {
    // ignore
}

// Schedule info
$scheduleEnabled = Setting::isScheduleEnabled();
$parkingOpen     = Setting::isParkingOpen();
$openHour        = Setting::openingHour();
$closeHour       = Setting::closingHour();

$totalPlaces = count($places);
$placesLibres = $stats['libre'] ?? 0;

render_header('Accueil', 'home.php');
?>

<h1>Bienvenue, <?= htmlspecialchars($user['prenom']) ?> 👋</h1>
<p class="subtitle">Voici l'état actuel du parking Smart Park.</p>

<?php if ($error): ?>
    <div class="alert err"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Parking access status banner -->
<?php if ($scheduleEnabled): ?>
    <div class="parking-status-banner <?= $parkingOpen ? 'open' : 'closed' ?>">
        <div class="status-icon">
            <?php if ($parkingOpen): ?>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            <?php else: ?>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
            <?php endif; ?>
        </div>
        <div>
            <strong><?= $parkingOpen ? 'Parking ouvert' : 'Parking fermé' ?></strong>
            <span class="status-hours">Horaires d'accès : <?= htmlspecialchars($openHour) ?> — <?= htmlspecialchars($closeHour) ?></span>
        </div>
    </div>
<?php endif; ?>

<!-- Parking availability -->
<div class="stats" id="client-stats" style="grid-template-columns:1fr">
    <span data-stat="libre">
        <span class="dot" style="background:#22c55e"></span>
        <strong class="count"><?= current(array_filter($places, fn($p) => $p['etat'] === 'libre')) ? count(array_filter($places, fn($p) => $p['etat'] === 'libre')) : 0 ?></strong> place(s) disponible(s)
    </span>
</div>

<!-- Visual parking map (client view) -->
<p class="label-section">Places disponibles à la réservation</p>
<div class="places" id="places-client-grid">
    <?php
        $myReservation = null;
        $librePlaces = 0;
        foreach ($places as $p) {
            if ($p['etat'] === 'reservee' && (int)$p['reserve_par'] === (int)$user['id']) {
                $myReservation = $p;
            }
            if ($p['etat'] === 'libre') $librePlaces++;
        }
    ?>

    <?php if ($myReservation): ?>
        <?php 
            $resEnd = new DateTimeImmutable($myReservation['reserve_jusqu_a']);
            $now = new DateTimeImmutable();
            $diffSeconds = max(0, $resEnd->getTimestamp() - $now->getTimestamp());
            $mins = floor($diffSeconds / 60);
            $secs = $diffSeconds % 60;
        ?>
        <div class="place reservee" style="grid-column:1/-1;border:2px solid var(--primary-500)">
            <div style="display:flex;justify-content:space-between;align-items:center;width:100%">
                <div>
                    <div style="font-weight:bold;color:var(--primary-700)">Vous avez réservé la Place <?= (int) $myReservation['id_place'] ?></div>
                    <div style="font-size:13px;color:#666">Valable encore <span id="res-timer" data-seconds="<?= $diffSeconds ?>" style="font-weight:bold"><?= sprintf('%02d:%02d', $mins, $secs) ?></span> min.</div>
                </div>
                <button onclick="cancelReservation(<?= (int)$myReservation['id_place'] ?>)" class="btn btn-sm btn-danger">Annuler</button>
            </div>
        </div>
    <?php endif; ?>

    <?php foreach ($places as $p): ?>
        <?php 
            $etat = $p['etat'] ?? 'libre'; 
            if ($etat !== 'libre') continue; // Only show available spots!
        ?>
        <div class="place libre" data-place="<?= (int) $p['id_place'] ?>">
            <div style="display:flex;justify-content:space-between;align-items:center;width:100%">
                <div class="place-name" style="margin:0">Place <?= (int) $p['id_place'] ?></div>
                <button <?= $myReservation ? 'disabled title="Vous avez déjà une réservation"' : '' ?> onclick="reservePlace(<?= (int) $p['id_place'] ?>)" class="btn btn-sm btn-primary">Réserver</button>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if ($librePlaces === 0 && !$myReservation): ?>
        <div class="empty" style="grid-column:1/-1">Aucune place disponible actuellement.</div>
    <?php endif; ?>
</div>

<script>
function reservePlace(id) {
    fetch('/api/reserve.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=reserve&id_place=' + id
    }).then(r => r.json()).then(data => {
        if (data.ok) window.location.reload();
        else alert(data.error);
    }).catch(e => alert('Erreur de connexion.'));
}
function cancelReservation(id) {
    fetch('/api/reserve.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=cancel&id_place=' + id
    }).then(r => r.json()).then(data => {
        if (data.ok) window.location.reload();
        else alert(data.error);
    }).catch(e => alert('Erreur de connexion.'));
}
// Timer tick
setInterval(function() {
    let t = document.getElementById('res-timer');
    if (!t) return;
    let s = parseInt(t.getAttribute('data-seconds')) - 1;
    if (s <= 0) {
        window.location.reload();
    } else {
        t.setAttribute('data-seconds', s);
        let m = Math.floor(s / 60);
        let sec = s % 60;
        t.innerText = m.toString().padStart(2, '0') + ':' + sec.toString().padStart(2, '0');
    }
}, 1000);

// Auto-refresh the page every 15 seconds to update availability
setTimeout(function() {
    window.location.reload();
}, 15000);
</script>

<!-- Badge status -->
<p class="label-section">Mon Badge</p>
<div class="surface" style="padding:20px">
    <?php if ($existingBadge): ?>
        <div style="display:flex;flex-direction:column;gap:15px">
            <div style="display:flex;align-items:center;gap:14px">
                <div>
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none"
                         stroke="var(--primary-600)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="5" width="20" height="14" rx="2"/>
                        <line x1="2" y1="10" x2="22" y2="10"/>
                    </svg>
                </div>
                <div>
                    <div style="font-weight:600;margin-bottom:2px">Badge enregistré</div>
                    <span class="mono" style="color:var(--primary-600);font-size:1.1em"><?= htmlspecialchars($existingBadge['tag_uid']) ?></span>
                    <span class="auth <?= $existingBadge['autorise'] ? 'on' : 'off' ?>" style="margin-left:8px">
                        <?= $existingBadge['autorise'] ? 'Autorisé' : 'Bloqué' ?>
                    </span>
                </div>
            </div>
            
            <div style="border-top:1px solid #eee;padding-top:15px">
                <h3 style="font-size:15px;margin-bottom:8px">État de mon vélo</h3>
                <?php
                    $mySlot = null;
                    foreach ($places as $p) {
                        if (strtoupper((string)($p['uid_actuel'] ?? '')) === strtoupper($existingBadge['tag_uid'])) {
                            $mySlot = (int)$p['id_place'];
                            break;
                        }
                    }
                ?>
                <?php if ($mySlot): ?>
                    <div class="alert ok" style="margin:0;display:flex;align-items:center;gap:10px">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                        <strong>Votre vélo est sécurisé.</strong> Il est stationné sur la <strong>Place <?= $mySlot ?></strong>.
                    </div>
                <?php else: ?>
                    <div class="alert info" style="margin:0;display:flex;align-items:center;gap:10px">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        Votre vélo n'est actuellement pas stationné dans le parking.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div style="text-align:center">
            <p style="color:#888;margin-bottom:12px">Vous n'avez pas encore de badge associé à votre compte.</p>
            <a href="/inscription.php" class="btn btn-primary">Enregistrer mon badge</a>
        </div>
    <?php endif; ?>
</div>

<?php render_footer(); ?>
