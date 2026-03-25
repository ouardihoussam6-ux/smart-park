<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

if (is_logged_in()) {
    header('Location: /' . (is_admin() ? 'index.php' : 'home.php'));
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        $user = User::authenticate($email, $password);
        if ($user) {
            login_user($user);
            header("Location: /" . ($user['role'] === 'admin' ? 'index.php' : 'home.php'));
            exit;
        } else {
            $error = 'Email ou mot de passe incorrect.';
        }
    }
}

render_header('Connexion', 'login.php');
?>
<div style="max-width:440px;margin:2rem auto">
    <h1>Connexion</h1>
    <p class="subtitle">Connectez-vous à votre compte Smart Park.</p>

    <?php if ($error): ?>
        <div class="alert err"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="surface" style="padding:20px">
        <form method="post" id="login-form">
            <div class="field" style="margin-bottom:14px">
                <label for="email">Adresse email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="field" style="margin-bottom:18px">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                Se connecter
            </button>
        </form>
    </div>
</div>
<?php render_footer(); ?>
