<?php
declare(strict_types=1);

require_once __DIR__ . '/models/Log.php';
require_once __DIR__ . '/includes/layout.php';

require_admin();

const PER_PAGE = 30;

$page = max(1, (int) ($_GET['page'] ?? 1));

$actionLabel = [
    'lecture'          => 'Lecture',
    'proposition_slot' => 'Proposition',
    'slot_valide'      => 'Validée',
    'slot_libere'      => 'Libérée',
    'slot_defaut'      => 'Défaut',
];

try {
    $logs  = Log::paginate($page, PER_PAGE);
    $total = Log::count();
    $pages = (int) ceil($total / PER_PAGE);
    $error = null;
} catch (Throwable) {
    $logs  = [];
    $total = 0;
    $pages = 1;
    $error = 'Impossible de charger les journaux.';
}

render_header('Journaux', 'logs.php');
?>

<p class="page-title">Journaux</p>
<p class="page-sub"><?= number_format($total) ?> événement<?= $total !== 1 ? 's' : '' ?> enregistré<?= $total !== 1 ? 's' : '' ?>.</p>

<?php if ($error): ?>
    <div class="alert alert-err"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Date et heure</th>
                <th>Badge</th>
                <th>Action</th>
                <th>Place</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="4" class="empty">Aucun journal disponible.</td></tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <?php $dt = new DateTimeImmutable($log['date_heure']); ?>
                    <tr>
                        <td data-label="Date" style="color:var(--neutral-600)"><?= $dt->format('d/m/Y H:i:s') ?></td>
                        <td data-label="Badge"><span class="mono"><?= htmlspecialchars($log['tag_id']) ?></span></td>
                        <td data-label="Action">
                            <span class="action-badge <?= htmlspecialchars($log['action']) ?>">
                                <?= htmlspecialchars($actionLabel[$log['action']] ?? $log['action']) ?>
                            </span>
                        </td>
                        <td data-label="Place">
                            <span class="slot-circle <?= $log['slot'] == 0 ? 'empty' : '' ?>">
                                <?= $log['slot'] > 0 ? (int) $log['slot'] : '—' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($pages > 1): ?>
    <nav class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>">&lsaquo;</a>
        <?php endif; ?>
        <?php for ($i = max(1, $page - 3); $i <= min($pages, $page + 3); $i++): ?>
            <?php if ($i === $page): ?>
                <span class="cur"><?= $i ?></span>
            <?php else: ?>
                <a href="?page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $pages): ?>
            <a href="?page=<?= $page + 1 ?>">&rsaquo;</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>

<?php render_footer(); ?>
