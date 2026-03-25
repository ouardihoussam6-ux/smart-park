<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

if (is_logged_in()) {
    header('Location: /' . (is_admin() ? 'index.php' : 'home.php'));
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom      = trim($_POST['nom'] ?? '');
    $prenom   = trim($_POST['prenom'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nom === '' || $prenom === '' || $email === '' || $password === '') {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } elseif (strlen($password) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif (User::findByEmail($email)) {
        $error = 'Un compte existe déjà avec cette adresse email.';
    } else {
        try {
            User::create($nom, $prenom, $email, $password, 'user');
            $success = 'Inscription réussie ! Vous pouvez maintenant vous connecter.';
        } catch (Throwable $e) {
            $error = 'Une erreur est survenue lors de l\'enregistrement.';
        }
    }
}

render_header('Inscription', 'register.php');
?>
<div style="max-width:440px;margin:2rem auto">
    <h1>Créer un compte</h1>
    <p class="subtitle">Inscrivez-vous pour obtenir votre accès Smart Park.</p>

    <?php if ($error): ?>
        <div class="alert err"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="alert ok"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="surface" style="padding:20px">
        <form method="post" id="register-form">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:14px">
                <div class="field">
                    <label for="prenom">Prénom</label>
                    <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>" required>
                </div>
                <div class="field">
                    <label for="nom">Nom</label>
                    <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
                </div>
            </div>
            <div class="field" style="margin-bottom:14px">
                <label for="email">Adresse email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="field" style="margin-bottom:18px">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" minlength="8" required>
                <span class="hint">Minimum 8 caractères.</span>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                S'inscrire
            </button>
        </form>
        
        <?php if ($success): ?>
            <div style="text-align:center;margin-top:15px">
                <a href="/login.php" class="btn" style="width:100%;justify-content:center">Aller à la page de connexion</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php render_footer(); ?>
