<?php
$settingsUser = $user ?? ['name' => 'Utilisateur', 'role' => '', 'club' => '', 'ville' => '', 'avatar' => 'U'];
$settingsRoleLabel = match ($settingsUser['role'] ?? '') {
    ROLE_TEACHER => 'Professeur d\'EPS',
    ROLE_STUDENT => 'Eleve',
    ROLE_MANAGER => atlasRoleLabel(ROLE_MANAGER),
    ROLE_RECRUITER => 'Recruteur / Club',
    ROLE_COACH => 'Coach sportif',
    ROLE_ADMIN => 'Administrateur',
    default => 'Utilisateur',
};
$settingsOrg = userSubtitle($settingsUser);
$settingsStorageKey = 'atlasTalents.settings.' . ($settingsUser['role'] ?? 'guest');
?>
<div class="modal-overlay" id="settingsModal">
  <div class="modal-box settings-modal">
    <button class="modal-close" type="button" onclick="closeModal('settingsModal')">×</button>
    <div class="settings-modal__header">
      <div>
        <div class="heading-xl" style="margin-bottom:4px;">Paramètres</div>
        <p class="text-sm text-muted">Préférences de compte et d'interface pour votre session.</p>
      </div>
      <div class="avatar avatar-lg <?= match ($settingsUser['role'] ?? '') {
          ROLE_STUDENT => 'avatar-gold',
          ROLE_MANAGER => 'avatar-purple',
          ROLE_RECRUITER => 'avatar-blue',
          ROLE_COACH => 'avatar-green',
          ROLE_ADMIN => 'avatar-purple',
          default => 'avatar-red',
      } ?>"><?= htmlspecialchars($settingsUser['avatar'] ?? 'U') ?></div>
    </div>

    <div class="settings-modal__grid">
      <section class="settings-panel">
        <div class="heading-md" style="margin-bottom:14px;">Compte</div>
        <div class="settings-summary">
          <div class="settings-summary__row"><span>Nom</span><strong><?= htmlspecialchars($settingsUser['name'] ?? 'Utilisateur') ?></strong></div>
          <div class="settings-summary__row"><span>Rôle</span><strong><?= htmlspecialchars($settingsRoleLabel) ?></strong></div>
          <div class="settings-summary__row"><span>Structure</span><strong><?= htmlspecialchars($settingsOrg) ?></strong></div>
          <?php if (!empty($settingsUser['ville'])): ?>
          <div class="settings-summary__row"><span>Ville</span><strong><?= htmlspecialchars($settingsUser['ville']) ?></strong></div>
          <?php endif; ?>
        </div>
      </section>

      <section class="settings-panel">
        <div class="heading-md" style="margin-bottom:14px;">Préférences</div>
        <form id="settingsForm">
          <label class="settings-check">
            <input type="checkbox" name="compact_mode">
            <span>Mode compact</span>
          </label>
          <label class="settings-check">
            <input type="checkbox" name="reduce_motion">
            <span>Réduire les animations</span>
          </label>
          <label class="settings-check">
            <input type="checkbox" name="email_notifications" checked>
            <span>Recevoir les résumés par email</span>
          </label>
          <label class="settings-check">
            <input type="checkbox" name="toast_notifications" checked>
            <span>Afficher les notifications dans l'interface</span>
          </label>

          <div class="settings-modal__actions">
            <button type="button" class="btn btn-outline" onclick="closeModal('settingsModal')">Fermer</button>
            <button type="button" class="btn btn-primary" id="saveSettingsBtn">Enregistrer</button>
          </div>
        </form>
      </section>
    </div>
  </div>
</div>

<style>
.settings-modal { width: 680px; max-width: 94vw; }
.settings-modal__header { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:26px; }
.settings-modal__grid { display:grid; grid-template-columns: 1fr 1.2fr; gap:18px; }
.settings-panel {
  background: linear-gradient(180deg, rgba(255,255,255,.86), rgba(247,243,239,.72));
  border: 1px solid rgba(255,255,255,.82);
  border-radius: 24px;
  padding: 20px;
  box-shadow: var(--shadow-xs);
}
.settings-summary { display:flex; flex-direction:column; gap:10px; }
.settings-summary__row {
  display:flex;
  justify-content:space-between;
  gap:12px;
  font-size:13px;
  color:var(--muted);
  padding: 10px 0;
  border-bottom: 1px solid rgba(10,10,15,.05);
}
.settings-summary__row:last-child { border-bottom: none; }
.settings-summary__row strong { color: var(--ink); text-align:right; }
.settings-check { display:flex; align-items:center; gap:10px; padding:14px 0; font-size:14px; color:var(--ink-80); border-bottom:1px solid var(--border-soft); }
.settings-check:last-of-type { border-bottom:none; }
.settings-check input { accent-color: var(--red); width:16px; height:16px; }
.settings-modal__actions { display:flex; justify-content:flex-end; gap:10px; margin-top:20px; }
.settings-compact-mode .sidebar { width: 224px; }
.settings-compact-mode .dash-main { margin-left: 224px; padding: 24px 28px; }
.settings-compact-mode .stat-card,
.settings-compact-mode .card { padding: 18px; }
.settings-reduce-motion *,
.settings-reduce-motion *::before,
.settings-reduce-motion *::after { animation: none !important; transition: none !important; scroll-behavior: auto !important; }
@media (max-width: 1024px) {
  .settings-compact-mode .dash-main { margin-left: 0; }
}
@media (max-width: 720px) {
  .settings-modal__grid { grid-template-columns: 1fr; }
}
</style>

<script>
(function() {
  const storageKey = <?= json_encode($settingsStorageKey) ?>;

  function getSettingsForm() {
    return document.getElementById('settingsForm');
  }

  function readSettings() {
    try {
      const raw = localStorage.getItem(storageKey);
      return raw ? JSON.parse(raw) : {};
    } catch (error) {
      return {};
    }
  }

  function applyDashboardSettings(settings) {
    document.body.classList.toggle('settings-compact-mode', Boolean(settings.compact_mode));
    document.body.classList.toggle('settings-reduce-motion', Boolean(settings.reduce_motion));
  }

  function hydrateSettingsForm() {
    const form = getSettingsForm();
    if (!form) {
      return;
    }

    const settings = readSettings();
    Array.from(form.elements).forEach(field => {
      if (!field.name) {
        return;
      }
      field.checked = Boolean(settings[field.name]);
    });

    if (!('email_notifications' in settings)) {
      form.elements.email_notifications.checked = true;
    }
    if (!('toast_notifications' in settings)) {
      form.elements.toast_notifications.checked = true;
    }

    applyDashboardSettings(settings);
  }

  function saveSettings() {
    const form = getSettingsForm();
    if (!form) {
      return;
    }

    const settings = {};
    Array.from(form.elements).forEach(field => {
      if (!field.name) {
        return;
      }
      settings[field.name] = Boolean(field.checked);
    });

    localStorage.setItem(storageKey, JSON.stringify(settings));
    applyDashboardSettings(settings);
    closeModal('settingsModal');

    if (typeof showToast === 'function' && settings.toast_notifications !== false) {
      showToast('Paramètres enregistrés.', 'success');
    }
  }

  document.addEventListener('DOMContentLoaded', hydrateSettingsForm);
  document.addEventListener('click', function(event) {
    if (event.target && event.target.id === 'saveSettingsBtn') {
      saveSettings();
    }
  });
})();
</script>
