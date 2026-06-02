<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

Auth::init();

if (Auth::isLoggedIn()) {
    header('Location: ' . atlasDashboardUrlForRole(Auth::user()['role']));
    exit;
}

$error = '';
$success = '';
$tab = $_GET['tab'] ?? 'login';
$role = atlasCanonicalRole($_GET['role'] ?? ROLE_TEACHER);
$demoModeEnabled = atlasDemoModeEnabled();
$ownerDemoUnlocked = atlasOwnerDemoAccessActive();

if (isset($_GET['demo_key']) && atlasOwnerDemoSecretMatches($_GET['demo_key'])) {
    atlasOwnerDemoAccessGrant();
    $ownerDemoUnlocked = true;
    header('Location: ' . APP_URL . '/pages/auth/login.php?msg=' . urlencode('Acces demo proprietaire active.'));
    exit;
}

if (isset($_GET['disable_demo']) && $ownerDemoUnlocked) {
    atlasOwnerDemoAccessRevoke();
    $ownerDemoUnlocked = false;
    header('Location: ' . APP_URL . '/pages/auth/login.php?msg=' . urlencode('Acces demo proprietaire desactive.'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['_token'] ?? '')) {
        $error = 'Session expiree. Veuillez reessayer.';
    } elseif (isset($_POST['demo_login'])) {
        if (!$ownerDemoUnlocked && !APP_ALLOW_DEMO_MODE) {
            $error = 'Acces demo indisponible.';
        } else {
            $result = Auth::loginDemoRole($_POST['role'] ?? '');

            if ($result['success']) {
                header('Location: ' . atlasDashboardUrlForRole($result['role']) . '?msg=' . urlencode('Session demo ouverte.'));
                exit;
            }

            $error = $result['message'];
            $tab = 'login';
        }
    } elseif (isset($_POST['login'])) {
        $result = Auth::login($_POST['email'] ?? '', $_POST['password'] ?? '', $_POST['role'] ?? '');

        if ($result['success']) {
            header('Location: ' . atlasDashboardUrlForRole($result['role']) . '?msg=' . urlencode('Bienvenue !'));
            exit;
        }

        $error = $result['message'];
        $tab = 'login';
        $role = atlasCanonicalRole($_POST['role'] ?? $role);
    } elseif (isset($_POST['register'])) {
        if (strlen($_POST['password'] ?? '') < 8) {
            $error = 'Le mot de passe doit contenir au moins 8 caracteres.';
        } else {
            $result = Auth::register([
                'name' => $_POST['name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'password' => $_POST['password'] ?? '',
                'role' => $_POST['role'] ?? ROLE_TEACHER,
                'club' => $_POST['club'] ?? '',
                'ville' => $_POST['ville'] ?? '',
            ]);

            if ($result['success']) {
                $success = $result['message'];
                $tab = 'login';
            } else {
                $error = $result['message'];
                $tab = 'register';
            }
        }
    }
}

$loginRoles = [
    ['role' => ROLE_TEACHER, 'label' => 'Professeur EPS', 'sub' => 'Upload et suivi de classe', 'icon' => '🎓', 'tone' => 'red', 'email' => 'teacher@demo.com'],
    ['role' => ROLE_STUDENT, 'label' => 'Eleve', 'sub' => 'Progression personnelle', 'icon' => '👟', 'tone' => 'gold', 'email' => 'student@demo.com'],
    ['role' => ROLE_MANAGER, 'label' => 'Manager recrutement', 'sub' => 'Prospection et coordination', 'icon' => '🧭', 'tone' => 'purple', 'email' => 'manager@demo.com'],
    ['role' => ROLE_RECRUITER, 'label' => 'Recruteur / Club', 'sub' => 'Scouting et favoris', 'icon' => '🏢', 'tone' => 'blue', 'email' => 'recruiter@demo.com'],
    ['role' => ROLE_COACH, 'label' => 'Coach', 'sub' => 'Suivi des athletes', 'icon' => '🏆', 'tone' => 'green', 'email' => 'coach@demo.com'],
];

$registerRoleLabels = [
    ROLE_TEACHER => 'Professeur d EPS',
    ROLE_MANAGER => 'Manager recrutement',
    ROLE_RECRUITER => 'Recruteur / Club',
    ROLE_COACH => 'Coach sportif',
];
$registerRoles = [];

foreach (atlasAllowedPublicRegistrationRoles() as $allowedRole) {
    if (isset($registerRoleLabels[$allowedRole])) {
        $registerRoles[$allowedRole] = $registerRoleLabels[$allowedRole];
    }
}

$pageTitle = 'Connexion';
$bodyClass = 'auth-body';
include __DIR__ . '/../../components/head.php';
?>

<div class="auth-shell">
  <section class="auth-hero">
    <a href="<?= APP_URL ?>/index.php" class="auth-back">← Retour accueil</a>
    <div class="auth-badge">Atlas Talents</div>
    <h1 class="auth-title">Une seule plateforme pour detecter, suivre et recruter les talents sportifs.</h1>
    <p class="auth-copy">Chaque espace metier dispose maintenant de sa propre logique: professeur, eleve, manager recrutement, recruteur et coach.</p>

    <div class="auth-role-preview">
      <?php foreach ($loginRoles as $card): ?>
      <div class="auth-role-chip auth-role-chip--<?= htmlspecialchars($card['tone']) ?>">
        <span><?= htmlspecialchars($card['icon']) ?></span>
        <strong><?= htmlspecialchars($card['label']) ?></strong>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="auth-stats">
      <div><strong>500+</strong><span>Talents</span></div>
      <div><strong>120+</strong><span>Ecoles</span></div>
      <div><strong>40+</strong><span>Clubs</span></div>
    </div>
  </section>

  <section class="auth-panel">
    <div class="auth-card">
      <div class="auth-tabs">
        <button type="button" class="auth-tab <?= $tab === 'login' ? 'active' : '' ?>" onclick="switchTab('login')">Connexion</button>
        <button type="button" class="auth-tab <?= $tab === 'register' ? 'active' : '' ?>" onclick="switchTab('register')">Inscription</button>
      </div>

      <?php if ($error): ?><div class="auth-alert auth-alert--error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php if ($success): ?><div class="auth-alert auth-alert--success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
      <?php if ($ownerDemoUnlocked): ?>
      <div class="auth-alert auth-alert--success">
        Acces demo proprietaire actif.
        <a href="<?= APP_URL ?>/pages/auth/login.php?disable_demo=1" style="margin-left:8px;color:inherit;text-decoration:underline;">Desactiver</a>
      </div>
      <?php endif; ?>

      <form method="POST" id="loginForm" class="<?= $tab !== 'login' ? 'is-hidden' : '' ?>">
        <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">

        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" name="email" id="loginEmail" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="votre@email.com" required>
        </div>

        <div class="form-group">
          <label class="form-label">Mot de passe</label>
          <div class="auth-password">
            <input type="password" name="password" id="loginPwd" class="form-control" placeholder="password" required>
            <button type="button" class="pwd-toggle" onclick="togglePwd('loginPwd')">Voir</button>
          </div>
        </div>

        <div class="role-select__label">Je me connecte en tant que</div>
        <div class="auth-role-grid">
          <?php foreach ($loginRoles as $card): ?>
          <label class="auth-role-option auth-role-option--<?= htmlspecialchars($card['tone']) ?> <?= $role === $card['role'] ? 'active' : '' ?>"<?php if ($demoModeEnabled): ?> data-demo-email="<?= htmlspecialchars($card['email']) ?>" data-demo-password="password"<?php endif; ?>>
            <input type="radio" name="role" value="<?= htmlspecialchars($card['role']) ?>" <?= $role === $card['role'] ? 'checked' : '' ?>>
            <span class="auth-role-icon"><?= htmlspecialchars($card['icon']) ?></span>
            <strong><?= htmlspecialchars($card['label']) ?></strong>
            <small><?= htmlspecialchars($card['sub']) ?></small>
          </label>
          <?php endforeach; ?>
        </div>

        <?php if ($demoModeEnabled): ?><div class="auth-demo-hint">Compte demo: utilisez le mot de passe <strong>password</strong>.</div><?php endif; ?>
        <div class="auth-inline-help" id="authInlineHelp" hidden></div>

        <button type="submit" name="login" class="btn btn-primary" style="width:100%;justify-content:center;">Se connecter</button>
      </form>

      <form method="POST" id="registerForm" class="<?= $tab !== 'register' ? 'is-hidden' : '' ?>">
        <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">

        <div class="grid-2" style="gap:14px;">
          <div class="form-group">
            <label class="form-label">Nom complet</label>
            <input type="text" name="name" class="form-control" placeholder="Votre nom complet" required>
          </div>
          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" placeholder="email@exemple.com" required>
          </div>
        </div>

        <div class="grid-2" style="gap:14px;">
          <div class="form-group">
            <label class="form-label">Mot de passe</label>
            <div class="auth-password">
              <input type="password" name="password" id="regPwd" class="form-control" placeholder="Minimum 8 caracteres" required>
              <button type="button" class="pwd-toggle" onclick="togglePwd('regPwd')">Voir</button>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Je suis</label>
            <select name="role" class="form-control">
              <?php foreach ($registerRoles as $registerRole => $label): ?>
              <option value="<?= htmlspecialchars($registerRole) ?>"><?= htmlspecialchars($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="grid-2" style="gap:14px;">
          <div class="form-group">
            <label class="form-label">Structure</label>
            <input type="text" name="club" class="form-control" placeholder="College, club, organisation">
          </div>
          <div class="form-group">
            <label class="form-label">Ville</label>
            <select name="ville" class="form-control">
              <?php foreach (VILLES as $key => $city): ?>
              <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($city) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <p class="text-xs text-muted" style="margin-bottom:18px;">Les comptes eleves sont volontairement crees via rattachement interne pour eviter les profils orphelins.</p>
        <button type="submit" name="register" class="btn btn-primary" style="width:100%;justify-content:center;">Creer mon compte</button>
      </form>
    </div>
  </section>
</div>

<style>
.auth-body {
  min-height: 100vh;
  background:
    radial-gradient(circle at top left, rgba(200,16,46,.12), transparent 28%),
    radial-gradient(circle at bottom right, rgba(91,45,142,.1), transparent 26%),
    linear-gradient(180deg, #fffaf7 0%, #f7f1ec 100%);
}
.auth-shell { min-height: 100vh; height: 100vh; display:grid; grid-template-columns: 1.05fr .95fr; overflow:hidden; }
.auth-hero { min-height: 100vh; padding: clamp(36px, 5vw, 64px); background: linear-gradient(145deg, #5a0715 0%, var(--red) 40%, var(--red-dark) 100%); color:#fff; display:flex; flex-direction:column; justify-content:flex-start; gap:22px; overflow:auto; }
.auth-back { display:inline-flex; width:max-content; padding:10px 16px; border-radius:999px; background:rgba(255,255,255,.1); color:rgba(255,255,255,.8); border:1px solid rgba(255,255,255,.14); }
.auth-badge { display:inline-flex; width:max-content; padding:8px 14px; border-radius:999px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.16); font-size:12px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
.auth-title { font-family:var(--font-display); font-size:clamp(42px, 4vw, 58px); line-height:.98; letter-spacing:-2px; margin:0; max-width:720px; }
.auth-copy { max-width:540px; font-size:16px; line-height:1.8; color:rgba(255,255,255,.78); }
.auth-role-preview { display:flex; flex-wrap:wrap; gap:10px; }
.auth-role-chip { display:flex; align-items:center; gap:8px; padding:10px 14px; border-radius:999px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.14); font-size:13px; font-weight:700; }
.auth-role-chip--gold { color:#ffe08a; }
.auth-role-chip--purple { color:#e2d1ff; }
.auth-role-chip--blue { color:#d7ebff; }
.auth-role-chip--green { color:#d8f7dd; }
.auth-stats { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:16px; max-width:460px; }
.auth-stats div { padding:18px; border-radius:24px; background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.14); }
.auth-stats strong { display:block; font-size:28px; font-family:var(--font-display); }
.auth-stats span { font-size:12px; color:rgba(255,255,255,.62); text-transform:uppercase; letter-spacing:.08em; }
.auth-panel { min-height: 100vh; display:flex; align-items:center; justify-content:center; padding:24px 32px; overflow:auto; }
.auth-card { width:100%; max-width:620px; max-height:calc(100vh - 48px); overflow:auto; padding:26px; border-radius:34px; background:rgba(255,255,255,.82); border:1px solid rgba(255,255,255,.82); box-shadow:var(--shadow-lg); backdrop-filter:blur(18px); scrollbar-gutter:stable; }
.auth-tabs { display:flex; background:rgba(247,243,239,.9); border-radius:999px; padding:6px; margin-bottom:24px; border:1px solid var(--border); }
.auth-tab { flex:1; padding:11px; text-align:center; border:none; border-radius:999px; background:transparent; color:var(--muted); font-weight:700; cursor:pointer; }
.auth-tab.active { background:#fff; color:var(--ink); box-shadow:var(--shadow-xs); }
.auth-alert { padding:12px 16px; border-radius:18px; font-size:13px; font-weight:700; margin-bottom:20px; }
.auth-alert--error { background:var(--red-light); color:var(--red-dark); border:1px solid rgba(200,16,46,.2); }
.auth-alert--success { background:var(--green-light); color:var(--green); border:1px solid rgba(27,110,58,.2); }
.auth-role-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:12px; margin-bottom:18px; }
.auth-role-option { display:flex; flex-direction:column; gap:8px; padding:12px; border-radius:22px; border:1.5px solid var(--border); cursor:pointer; background:rgba(255,255,255,.68); min-height:108px; }
.auth-role-option input { display:none; }
.auth-role-option.active { transform:translateY(-1px); box-shadow:var(--shadow-xs); }
.auth-role-option--red.active { border-color:var(--red); background:var(--red-light); }
.auth-role-option--gold.active { border-color:var(--gold); background:var(--gold-light); }
.auth-role-option--purple.active { border-color:var(--purple); background:var(--purple-light); }
.auth-role-option--blue.active { border-color:var(--blue); background:var(--blue-light); }
.auth-role-option--green.active { border-color:var(--green); background:var(--green-light); }
.auth-role-icon { width:44px; height:44px; border-radius:16px; display:flex; align-items:center; justify-content:center; background:#fff; box-shadow:var(--shadow-xs); font-size:20px; }
.auth-role-option strong { font-size:14px; }
.auth-role-option small { font-size:12px; color:var(--muted); line-height:1.5; }
.auth-password { position:relative; }
.pwd-toggle { position:absolute; top:50%; right:10px; transform:translateY(-50%); border:none; background:none; color:var(--muted); font-size:12px; font-weight:700; cursor:pointer; }
.auth-demo-hint, .auth-inline-help { font-size:12px; border-radius:16px; padding:11px 13px; margin-bottom:18px; }
.auth-demo-hint { color:var(--muted); background:var(--surface); }
.auth-inline-help { color:var(--blue); background:var(--blue-light); border:1px solid rgba(12,74,143,.15); }
.is-hidden { display:none; }
@media (max-width: 1120px) {
  .auth-shell { height:auto; grid-template-columns: 1fr; overflow:visible; }
  .auth-hero { min-height:auto; padding: 40px 24px; overflow:visible; }
  .auth-panel { min-height:auto; padding:32px 24px; overflow:visible; }
  .auth-card { max-height:none; overflow:visible; }
}
@media (max-width: 640px) {
  .auth-panel { padding:20px 16px 28px; }
  .auth-card { padding:20px; border-radius:26px; }
  .auth-role-grid { grid-template-columns:1fr; }
  .auth-stats { grid-template-columns:1fr; }
}
</style>

<script>
function switchTab(tab) {
  document.querySelectorAll('.auth-tab').forEach((button, index) => {
    button.classList.toggle('active', (tab === 'login' && index === 0) || (tab === 'register' && index === 1));
  });
  document.getElementById('loginForm').classList.toggle('is-hidden', tab !== 'login');
  document.getElementById('registerForm').classList.toggle('is-hidden', tab !== 'register');
}

function togglePwd(id) {
  const input = document.getElementById(id);
  if (!input) return;
  input.type = input.type === 'password' ? 'text' : 'password';
}

function setInlineHelp(message) {
  const node = document.getElementById('authInlineHelp');
  if (!node) return;
  node.textContent = message;
  node.hidden = !message;
}

function fillDemoCredentials(option) {
  const emailInput = document.getElementById('loginEmail');
  const passwordInput = document.getElementById('loginPwd');
  if (!option || !emailInput || !passwordInput) return;
  if (!option.dataset.demoEmail || !option.dataset.demoPassword) return;
  emailInput.value = option.dataset.demoEmail || '';
  passwordInput.value = option.dataset.demoPassword || '';
  setInlineHelp('Identifiants demo remplis automatiquement.');
}

document.querySelectorAll('.auth-role-option').forEach(option => {
  option.addEventListener('click', function() {
    document.querySelectorAll('.auth-role-option').forEach(item => item.classList.remove('active'));
    this.classList.add('active');
    const input = this.querySelector('input');
    if (input) input.checked = true;
    fillDemoCredentials(this);
  });
});

<?php if ($tab === 'register'): ?>
switchTab('register');
<?php elseif ($demoModeEnabled): ?>
fillDemoCredentials(document.querySelector('.auth-role-option.active') || document.querySelector('.auth-role-option'));
<?php endif; ?>
</script>
