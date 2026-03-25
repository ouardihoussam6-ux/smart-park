<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';

function render_header(string $title, string $active = ''): void
{
    $nav = [];
    if (is_admin()) {
        $nav['index.php']       = 'Tableau de bord';
        $nav['home.php']        = 'Vue Client';
        $nav['badges.php']      = 'Gestion Badges';
        $nav['logs.php']        = 'Journaux';
        $nav['logout.php']      = 'Déconnexion';
    } elseif (is_logged_in()) {
        $nav['home.php']        = 'Accueil';
        $nav['inscription.php'] = 'Mon Badge';
        $nav['logout.php']      = 'Déconnexion';
    } else {
        $nav['login.php']       = 'Connexion';
        $nav['register.php']    = 'Inscription';
    }
    $brandLink = is_admin() ? '/index.php' : (is_logged_in() ? '/home.php' : '/login.php');
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?> — Smart Park</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<nav>
    <div class="nav-inner">
        <a href="<?= $brandLink ?>" class="brand">
            <span class="brand-dot"></span>
            Smart Park
        </a>
        <ul class="nav-links">
            <?php foreach ($nav as $href => $label): ?>
                <li>
                    <a href="/<?= $href ?>"<?= $active === $href ? ' class="active"' : '' ?>>
                        <?= $label ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>
<main>
    <?php
}

function render_footer(): void
{
    ?>
</main>
<script src="/assets/js/app.js"></script>
</body>
</html>
    <?php
}
