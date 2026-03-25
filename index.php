<?php
declare(strict_types=1);

require_once __DIR__ . '/models/Place.php';
require_once __DIR__ . '/models/Log.php';
require_once __DIR__ . '/models/Setting.php';
require_once __DIR__ . '/includes/layout.php';

require_admin();

$actionLabel = [
    'lecture'          => 'Lecture',
    'proposition_slot' => 'Proposition',
    'slot_valide'      => 'Validée',
    'slot_libere'      => 'Libérée',
    'slot_defaut'      => 'Défaut',
];

try {
    $places = Place::all();
    $stats  = Place::stats();
    $logs   = Log::recent(15);
    $error  = null;
} catch (Throwable) {
    $places = [];
    $stats  = ['libre' => 0, 'occupee' => 0, 'panne' => 0];
    $logs   = [];
    $error  = 'Impossible de se connecter à la base de données.';
}

render_header('Tableau de bord', 'index.php');
?>

<h1>Tableau de bord</h1>
<p class="subtitle">État du parking — actualisation toutes les 5 secondes.</p>

<?php if ($error): ?>
    <div class="alert err"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="stats" id="stats-row">
    <span data-stat="libre">
        <span class="dot" style="background:#22c55e"></span>
        <strong class="count"><?= $stats['libre'] ?></strong> libre<?= $stats['libre'] !== 1 ? 's' : '' ?>
    </span>
    <span data-stat="occupee">
        <span class="dot" style="background:#ef4444"></span>
        <strong class="count"><?= $stats['occupee'] ?></strong> occupée<?= $stats['occupee'] !== 1 ? 's' : '' ?>
    </span>
    <span data-stat="panne">
        <span class="dot" style="background:#f59e0b"></span>
        <strong class="count"><?= $stats['panne'] ?></strong> en panne
    </span>
    <span data-stat="reservee">
        <span class="dot" style="background:#3b82f6"></span>
        <strong class="count"><?= $stats['reservee'] ?? 0 ?></strong> réservée<?= ($stats['reservee'] ?? 0) !== 1 ? 's' : '' ?>
    </span>
</div>

<div class="places" id="places-grid">
    <?php foreach ($places as $p): ?>
        <?php $etat = $p['etat'] ?? 'libre'; ?>
        <div class="place <?= htmlspecialchars($etat) ?>" data-place="<?= (int) $p['id_place'] ?>">
            <div class="place-name">Place <?= (int) $p['id_place'] ?></div>
            <span class="place-chip <?= htmlspecialchars($etat) ?>">
                <span class="dot" style="background:<?= $etat === 'libre' ? '#22c55e' : ($etat === 'occupee' ? '#ef4444' : ($etat === 'reservee' ? '#3b82f6' : '#f59e0b')) ?>"></span>
                <span class="label"><?= ['libre' => 'Libre', 'occupee' => 'Occupée', 'reservee' => 'Réservée', 'panne' => 'En panne'][$etat] ?? $etat ?></span>
            </span>
            <div class="place-uid"><?= htmlspecialchars($p['uid_actuel'] ?? '') ?></div>
        </div>
    <?php endforeach; ?>
    <?php if (empty($places)): ?>
        <div class="empty" style="grid-column:1/-1">Aucune place. Lancez setup.php.</div>
    <?php endif; ?>
</div>

<p class="label-section">Activité récente</p>
<div class="surface">
    <table>
        <thead>
            <tr><th>Date</th><th>Badge</th><th>Action</th><th>Place</th></tr>
        </thead>
        <tbody id="logs-body">
            <?php if (empty($logs)): ?>
                <tr><td colspan="4" class="empty">Aucune activité.</td></tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <?php $dt = new DateTimeImmutable($log['date_heure']); ?>
                    <tr>
                        <td data-label="Date" style="color:#888"><?= $dt->format('d/m H:i') ?></td>
                        <td data-label="Badge"><span class="mono"><?= htmlspecialchars($log['tag_id']) ?></span></td>
                        <td data-label="Action">
                            <span class="chip <?= htmlspecialchars($log['action']) ?>">
                                <?= htmlspecialchars($actionLabel[$log['action']] ?? $log['action']) ?>
                            </span>
                        </td>
                        <td data-label="Place">
                            <span class="num <?= $log['slot'] == 0 ? 'empty' : '' ?>">
                                <?= $log['slot'] > 0 ? (int) $log['slot'] : '—' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<p class="label-section">Plage horaire d'accès</p>
<div class="schedule-panel surface" style="padding:20px">
    <div class="schedule-header">
        <div>
            <h3 style="font-size:14px;font-weight:600;margin-bottom:2px">Contrôle des horaires</h3>
            <p style="font-size:12.5px;color:#888">Définissez les heures d'ouverture et de fermeture du parking pour les clients.</p>
        </div>
        <div class="schedule-status" id="schedule-status">
            <?php if (Setting::isScheduleEnabled()): ?>
                <?php if (Setting::isParkingOpen()): ?>
                    <span class="auth on">Ouvert</span>
                <?php else: ?>
                    <span class="auth off">Fermé</span>
                <?php endif; ?>
            <?php else: ?>
                <span class="auth on">Accès libre</span>
            <?php endif; ?>
        </div>
    </div>

    <form id="schedule-form" class="schedule-form">
        <div class="schedule-toggle">
            <label class="toggle-label" for="schedule_enabled">
                <input type="checkbox" id="schedule_enabled" name="schedule_enabled"
                       <?= Setting::isScheduleEnabled() ? 'checked' : '' ?>>
                <span class="toggle-switch"></span>
                <span>Activer le contrôle horaire</span>
            </label>
        </div>

        <div class="schedule-times" id="schedule-times" style="<?= Setting::isScheduleEnabled() ? '' : 'opacity:0.4;pointer-events:none' ?>">
            <div class="field">
                <label for="parking_open">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:4px">
                        <circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                    </svg>
                    Ouverture
                </label>
                <input type="time" id="parking_open" name="parking_open"
                       value="<?= htmlspecialchars(Setting::openingHour()) ?>">
            </div>
            <div class="field">
                <label for="parking_close">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:4px">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                    </svg>
                    Fermeture
                </label>
                <input type="time" id="parking_close" name="parking_close"
                       value="<?= htmlspecialchars(Setting::closingHour()) ?>">
            </div>
            <button type="submit" class="btn btn-primary" style="align-self:flex-end" id="btn-save-schedule">
                Enregistrer
            </button>
        </div>
    </form>
    <div id="schedule-msg" style="margin-top:10px;display:none" class="alert"></div>
</div>

<div class="reset-bar">
    <div>
        <h3>Reset distant ESP32</h3>
        <p>Envoie un ordre de redémarrage pris en compte dans les 3 secondes.</p>
    </div>
    <button id="btn-reset" class="btn btn-warn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>
        </svg>
        Redémarrer l'ESP32
    </button>
</div>

<?php render_footer(); ?>
