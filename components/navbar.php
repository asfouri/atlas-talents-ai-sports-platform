<?php
// components/navbar.php
// $navType: 'public' | 'teacher' | 'student' | 'manager' | 'recruiter' | 'coach' | 'admin'
// $activeNav: current section
if (!isset($navType)) {
    $navType = 'public';
}

if (!isset($activeNav)) {
    $activeNav = '';
}

$user = Auth::isLoggedIn() ? Auth::user() : null;

$avatarColors = [
    'teacher' => 'avatar-red',
    'student' => 'avatar-gold',
    'manager' => 'avatar-purple',
    'recruiter' => 'avatar-blue',
    'coach' => 'avatar-green',
    'admin' => 'avatar-purple',
];
$avatarColor = $avatarColors[$user['role'] ?? ''] ?? 'avatar-red';
$notificationCount = $user ? atlasGetUnreadNotificationCount((int) ($user['id'] ?? 0)) : 0;

$clubNames = [
    'teacher' => atlasRoleLabel(ROLE_TEACHER),
    'student' => atlasRoleLabel(ROLE_STUDENT),
    'manager' => atlasRoleLabel(ROLE_MANAGER),
    'recruiter' => $user['club'] ?? 'Recruteur',
    'coach' => $user['club'] ?? 'Coach sportif',
    'admin' => $user['club'] ?? 'Cellule recrutement',
];
?>
<nav class="at-nav" id="mainNav">
  <div class="at-nav__inner">
    <a href="<?= APP_URL ?>/index.php" class="at-nav__brand">
      <div class="at-nav__logo">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="12" cy="12" r="9" stroke="white" stroke-width="2.5"/>
          <circle cx="12" cy="12" r="4" stroke="white" stroke-width="2.5"/>
          <circle cx="12" cy="12" r="1.5" fill="white"/>
        </svg>
      </div>
      <div class="at-nav__brand-text">
        <span class="at-nav__name">ATLAS TALENTS</span>
        <?php if ($navType !== 'public'): ?>
        <span class="at-nav__sub"><?= htmlspecialchars($clubNames[$navType] ?? '') ?></span>
        <?php endif; ?>
      </div>
    </a>

    <?php if ($navType === 'public'): ?>
    <div class="at-nav__links">
      <a href="#features" class="at-nav__link <?= $activeNav === 'features' ? 'active' : '' ?>">Fonctionnalités</a>
      <a href="#solution" class="at-nav__link <?= $activeNav === 'solution' ? 'active' : '' ?>">Solution</a>
      <a href="#tarifs" class="at-nav__link <?= $activeNav === 'tarifs' ? 'active' : '' ?>">Tarifs</a>
      <a href="#contact" class="at-nav__link <?= $activeNav === 'contact' ? 'active' : '' ?>">Contact</a>
    </div>
    <div class="at-nav__actions">
      <a href="<?= APP_URL ?>/pages/auth/login.php" class="btn btn-outline btn-sm">Se connecter</a>
      <a href="<?= APP_URL ?>/pages/auth/login.php?tab=register" class="btn btn-primary btn-sm">Commencer gratuit</a>
    </div>
    <?php else: ?>
    <div class="at-nav__dash-center">
      <div class="search-bar-wrap" style="width:280px;">
        <span class="search-icon">🔍</span>
        <input type="text" class="search-bar" placeholder="Rechercher...">
      </div>
    </div>
    <div class="at-nav__actions">
      <button class="notif-btn" type="button" data-count="<?= (int) $notificationCount ?>" onclick="toggleNotifs(this)">
        🔔<span class="notif-badge"><?= (int) $notificationCount ?></span>
      </button>
      <?php if ($user): ?>
      <div class="at-nav__user" onclick="toggleUserMenu()">
        <div class="avatar avatar-sm <?= $avatarColor ?>"><?= htmlspecialchars($user['avatar']) ?></div>
        <div class="at-nav__user-info">
          <span class="at-nav__user-name"><?= htmlspecialchars($user['name']) ?></span>
        </div>
        <span style="font-size:12px;color:var(--muted);">▾</span>
      </div>
      <div class="at-nav__user-dropdown" id="userDropdown">
        <a href="#" class="dropdown-item" onclick="openModal('profileModal'); toggleUserMenu(); return false;">👤 Mon profil</a>
        <a href="#" class="dropdown-item" onclick="openModal('settingsModal'); toggleUserMenu(); return false;">⚙ Paramètres</a>
        <div class="dropdown-divider"></div>
        <a href="#" class="dropdown-item text-red" onclick="return submitLogout(event);">🚪 Déconnexion</a>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</nav>

<button class="mobile-menu-btn" id="mobileMenuBtn" type="button" aria-label="Ouvrir le menu" aria-expanded="false">☰</button>
<div class="at-nav__mobile-panel" id="mobileNavPanel">
  <?php if ($navType === 'public'): ?>
  <a href="#features" class="at-nav__mobile-link">Fonctionnalités</a>
  <a href="#solution" class="at-nav__mobile-link">Solution</a>
  <a href="#tarifs" class="at-nav__mobile-link">Tarifs</a>
  <a href="#contact" class="at-nav__mobile-link">Contact</a>
  <div class="at-nav__mobile-actions">
    <a href="<?= APP_URL ?>/pages/auth/login.php" class="btn btn-outline btn-sm">Se connecter</a>
    <a href="<?= APP_URL ?>/pages/auth/login.php?tab=register" class="btn btn-primary btn-sm">Commencer</a>
  </div>
  <?php elseif ($user): ?>
  <div class="at-nav__mobile-user">
    <div class="avatar avatar-sm <?= $avatarColor ?>"><?= htmlspecialchars($user['avatar']) ?></div>
    <div>
      <div class="at-nav__mobile-user-name"><?= htmlspecialchars($user['name']) ?></div>
      <div class="at-nav__mobile-user-sub"><?= htmlspecialchars($clubNames[$navType] ?? '') ?></div>
    </div>
  </div>
  <a href="<?= APP_URL ?>/index.php" class="at-nav__mobile-link">Accueil</a>
  <a href="#" class="at-nav__mobile-link" onclick="openModal('profileModal'); toggleMobileMenu(false); return false;">Mon profil</a>
  <a href="#" class="at-nav__mobile-link" onclick="openModal('settingsModal'); toggleMobileMenu(false); return false;">Paramètres</a>
  <a href="#" class="at-nav__mobile-link at-nav__mobile-link--danger" onclick="return submitLogout(event);">Déconnexion</a>
  <?php endif; ?>
</div>

<?php if ($navType !== 'public'): ?>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
<button class="sidebar-toggle-btn" id="sidebarToggleBtn" type="button" aria-label="Menu" onclick="toggleSidebar()">☰</button>
<?php endif; ?>

<?php if ($user): ?>
<div class="modal-overlay" id="profileModal">
  <div class="modal-box profile-modal">
    <button class="modal-close" type="button" onclick="closeModal('profileModal')">✕</button>
    <div class="profile-modal__header">
      <div class="avatar avatar-xl <?= $avatarColor ?>"><?= htmlspecialchars($user['avatar']) ?></div>
      <div>
        <h2 class="heading-xl" style="margin-bottom:6px;"><?= htmlspecialchars($user['name']) ?></h2>
        <p class="text-sm text-muted"><?= htmlspecialchars($clubNames[$navType] ?? 'Utilisateur') ?></p>
      </div>
    </div>
    <div class="profile-modal__grid">
      <div class="profile-modal__card">
        <div class="profile-modal__label">Rôle</div>
        <div class="profile-modal__value"><?= htmlspecialchars($user['role'] ? atlasRoleLabel($user['role']) : 'guest') ?></div>
      </div>
      <div class="profile-modal__card">
        <div class="profile-modal__label">Ville</div>
        <div class="profile-modal__value"><?= htmlspecialchars($user['ville'] ?: 'Non renseignée') ?></div>
      </div>
      <div class="profile-modal__card">
        <div class="profile-modal__label">Structure</div>
        <div class="profile-modal__value"><?= htmlspecialchars($user['club'] ?: 'Atlas Talents') ?></div>
      </div>
      <div class="profile-modal__card">
        <div class="profile-modal__label">Session</div>
        <div class="profile-modal__value">Active</div>
      </div>
    </div>
    <div class="profile-modal__actions">
      <button class="btn btn-outline" type="button" onclick="closeModal('profileModal')">Fermer</button>
      <button class="btn btn-primary" type="button" onclick="closeModal('profileModal'); openModal('settingsModal');">Modifier mes préférences</button>
    </div>
  </div>
</div>
<?php endif; ?>

<style>
.at-nav {
  position: fixed; top: 0; left: 0; right: 0; z-index: 500;
  padding: 14px 18px 0;
  pointer-events: none;
}
.at-nav__inner,
.mobile-menu-btn,
.at-nav__mobile-panel,
.at-nav__user-dropdown {
  pointer-events: auto;
}
.at-nav__inner {
  max-width: 1400px; margin: 0 auto; padding: 0 26px; min-height: calc(var(--nav-height) - 14px);
  display: flex; align-items: center; gap: 24px;
  background: rgba(255,255,255,.72);
  border: 1px solid rgba(255,255,255,.78);
  border-radius: 999px;
  backdrop-filter: blur(20px) saturate(180%);
  box-shadow: 0 18px 42px rgba(10,10,15,.08);
}
.at-nav__brand {
  display: flex; align-items: center; gap: 11px; flex-shrink: 0; text-decoration: none;
}
.at-nav__logo {
  width: 44px; height: 44px; background: linear-gradient(135deg, var(--red) 0%, var(--red-mid) 100%); border-radius: 14px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
  box-shadow: 0 14px 28px rgba(200,16,46,.24);
}
.at-nav__name { font-family: var(--font-heading); font-weight: 800; font-size: 16px; letter-spacing: -.3px; color: var(--ink); display: block; }
.at-nav__sub  { font-size: 10px; color: var(--muted); font-weight: 600; letter-spacing: .12em; text-transform: uppercase; display: block; margin-top: 1px; }
.at-nav__links { display: flex; align-items: center; gap: 8px; margin: 0 auto; padding: 6px; background: rgba(255,255,255,.52); border-radius: 999px; border: 1px solid rgba(10,10,15,.05); }
.at-nav__link { font-size: 13px; font-weight: 700; color: var(--ink-60); transition: color .2s, background-color .2s, transform .2s; padding: 10px 14px; border-radius: 999px; }
.at-nav__link:hover, .at-nav__link.active { color: var(--red); background: rgba(200,16,46,.08); transform: translateY(-1px); }
.at-nav__dash-center { flex: 1; display: flex; justify-content: center; }
.at-nav__actions { display: flex; align-items: center; gap: 10px; margin-left: auto; flex-shrink: 0; }
.at-nav__user { display: flex; align-items: center; gap: 9px; cursor: pointer; padding: 6px 10px 6px 6px; border-radius: 999px; transition: background .2s, transform .2s, box-shadow .2s; position: relative; background: rgba(255,255,255,.7); border: 1px solid rgba(10,10,15,.06); }
.at-nav__user:hover { background: white; transform: translateY(-1px); box-shadow: var(--shadow-xs); }
.at-nav__user-name { font-size: 13px; font-weight: 600; color: var(--ink); white-space: nowrap; }
.at-nav__user-dropdown {
  position: absolute; top: calc(var(--nav-height) + 6px); right: 24px; background: rgba(255,255,255,.94);
  border: 1px solid rgba(10,10,15,.06); border-radius: 20px; padding: 8px;
  min-width: 200px; box-shadow: var(--shadow-lg); z-index: 600; backdrop-filter: blur(16px);
  display: none;
}
.at-nav__user-dropdown.open { display: block; animation: scaleIn .2s var(--ease) both; }
.dropdown-item { display: block; padding: 10px 12px; border-radius: 14px; font-size: 13px; font-weight: 600; color: var(--ink-60); transition: background .15s, color .15s; }
.dropdown-item:hover { background: var(--surface); color: var(--ink); }
.dropdown-item.text-red { color: var(--red); }
.dropdown-divider { height: 1px; background: var(--border-soft); margin: 4px 0; }
.mobile-menu-btn { display: none; position: fixed; top: 18px; right: 18px; z-index: 501; background: linear-gradient(135deg, var(--red) 0%, var(--red-mid) 100%); color: white; border: none; border-radius: 14px; width: 44px; height: 44px; font-size: 18px; box-shadow: var(--shadow-red); }
.at-nav__mobile-panel {
  position: fixed; top: calc(var(--nav-height) + 4px); left: 16px; right: 16px; z-index: 499;
  background: rgba(255,255,255,.94); border: 1px solid rgba(10,10,15,.06); border-radius: 24px;
  box-shadow: var(--shadow-lg); padding: 16px; display: none; flex-direction: column; gap: 10px; backdrop-filter: blur(18px);
}
.at-nav__mobile-panel.open { display: flex; animation: scaleIn .2s var(--ease) both; }
.at-nav__mobile-link { padding: 12px 14px; border-radius: 16px; font-size: 14px; font-weight: 700; color: var(--ink-60); background: rgba(255,255,255,.58); }
.at-nav__mobile-link:hover { background: var(--surface); color: var(--ink); }
.at-nav__mobile-link--danger { color: var(--red); }
.at-nav__mobile-actions { display: flex; gap: 10px; flex-wrap: wrap; padding-top: 6px; }
.at-nav__mobile-user { display: flex; align-items: center; gap: 10px; padding: 0 0 10px; border-bottom: 1px solid var(--border-soft); margin-bottom: 4px; }
.at-nav__mobile-user-name { font-size: 14px; font-weight: 700; color: var(--ink); }
.at-nav__mobile-user-sub { font-size: 12px; color: var(--muted); }
.profile-modal { width: 620px; max-width: 94vw; }
.profile-modal__header { display:flex; align-items:center; gap:18px; margin-bottom:26px; }
.profile-modal__grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:14px; }
.profile-modal__card { padding:16px; border-radius:20px; background:rgba(255,255,255,.72); border:1px solid rgba(10,10,15,.06); }
.profile-modal__label { font-size:11px; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:.08em; margin-bottom:6px; }
.profile-modal__value { font-size:15px; font-weight:700; color:var(--ink); }
.profile-modal__actions { display:flex; justify-content:flex-end; gap:10px; margin-top:24px; flex-wrap:wrap; }
@media (max-width: 768px) {
  .at-nav { padding: 12px 16px 0; }
  .at-nav__links, .at-nav__dash-center, .at-nav__actions .btn { display: none; }
  .at-nav__inner { padding: 0 16px; min-height: 62px; }
  .at-nav__brand-text { max-width: calc(100vw - 120px); }
  .mobile-menu-btn { display: flex; align-items: center; justify-content: center; }
  .profile-modal__grid { grid-template-columns:1fr; }
}
</style>
<script>
function toggleUserMenu() {
  document.getElementById('userDropdown')?.classList.toggle('open');
}

function toggleMobileMenu(forceOpen = null) {
  const panel = document.getElementById('mobileNavPanel');
  const button = document.getElementById('mobileMenuBtn');

  if (!panel || !button) {
    return;
  }

  const nextState = forceOpen === null ? !panel.classList.contains('open') : forceOpen;
  panel.classList.toggle('open', nextState);
  button.setAttribute('aria-expanded', nextState ? 'true' : 'false');
}

document.getElementById('mobileMenuBtn')?.addEventListener('click', function() {
  toggleMobileMenu();
});

document.querySelectorAll('#mobileNavPanel a').forEach(link => {
  link.addEventListener('click', () => toggleMobileMenu(false));
});

document.addEventListener('click', function(e) {
  if (!e.target.closest('.at-nav__user') && !e.target.closest('#userDropdown')) {
    document.getElementById('userDropdown')?.classList.remove('open');
  }

  if (!e.target.closest('#mobileNavPanel') && !e.target.closest('#mobileMenuBtn')) {
    toggleMobileMenu(false);
  }
});
</script>
