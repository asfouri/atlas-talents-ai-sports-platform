<?php
$chatPanelId = $chatPanelId ?? 'atlasChatPanel';
$chatPanelTitle = $chatPanelTitle ?? 'Messagerie';
$chatPanelSubtitle = $chatPanelSubtitle ?? 'Echanges relies aux talents, suivis et recrutements en cours.';
$chatPanelData = atlasGetChatDataForUser($user ?? Auth::user());
$chatPanelApiUrl = APP_URL . '/api/index.php';
$chatPanelToken = Auth::csrfToken();
?>
<div class="smart-chat-root" id="<?= htmlspecialchars($chatPanelId) ?>">
  <section class="smart-chat-shell" aria-label="<?= htmlspecialchars($chatPanelTitle) ?>">
    <aside class="smart-chat-sidebar">
      <header class="smart-chat-sidebar__header">
        <div>
          <div class="smart-chat-sidebar__eyebrow">Atlas Talents</div>
          <div class="smart-chat-sidebar__title">Messagerie</div>
        </div>
        <button class="smart-chat-refresh-btn" type="button" data-chat-refresh aria-label="Actualiser">Actualiser</button>
      </header>

      <div class="smart-chat-sidebar__search">
        <span class="search-icon" aria-hidden="true">&#128269;</span>
        <input type="text" class="search-bar" placeholder="Rechercher un contact ou un talent" data-chat-search>
      </div>

      <div class="smart-chat-sidebar__summary" data-chat-stats></div>

      <div class="smart-chat-sidebar__filters">
        <button class="smart-chat-filter is-active" type="button" data-chat-filter="all">Tous</button>
        <button class="smart-chat-filter" type="button" data-chat-filter="unread">Non lus</button>
        <button class="smart-chat-filter" type="button" data-chat-filter="staff">Staff</button>
        <button class="smart-chat-filter" type="button" data-chat-filter="talent">Talents</button>
      </div>

      <div class="smart-chat-contacts" data-chat-contacts></div>
    </aside>

    <section class="smart-chat-thread">
      <header class="smart-chat-thread__head" data-chat-thread-head></header>
      <div class="smart-chat-thread__prompts" data-chat-prompts></div>
      <div class="smart-chat-thread__messages" data-chat-messages></div>

      <form class="smart-chat-composer" data-chat-form>
        <div class="smart-chat-composer__context">
          <div class="smart-chat-composer__context-group">
            <span class="smart-chat-composer__label">Contexte</span>
            <select class="form-control" id="<?= htmlspecialchars($chatPanelId) ?>Student" data-chat-student></select>
          </div>
          <div class="smart-chat-composer__hint" data-chat-hint>Selectionnez un contact pour commencer.</div>
        </div>

        <div class="smart-chat-composer__bar">
          <textarea class="form-control smart-chat-composer__textarea" rows="3" placeholder="Ecrire un message utile et concret..." data-chat-body></textarea>
          <button class="smart-chat-send-btn" type="submit" data-chat-send aria-label="Envoyer">Envoyer</button>
        </div>
      </form>
    </section>
  </section>
</div>

<style>
.smart-chat-root {
  width: 100%;
  height: 100%;
}

.smart-chat-shell {
  height: 100%;
  min-height: 760px;
  display: grid;
  grid-template-columns: 360px minmax(0, 1fr);
  background: linear-gradient(180deg, rgba(255,255,255,.98), rgba(255,255,255,.94));
  border: 1px solid var(--line);
  border-radius: 28px;
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  backdrop-filter: blur(18px);
}

.smart-chat-sidebar {
  min-width: 0;
  min-height: 0;
  display: flex;
  flex-direction: column;
  background: linear-gradient(180deg, rgba(255,255,255,.96), rgba(247,243,239,.72));
  border-right: 1px solid var(--border-soft);
}

.smart-chat-sidebar__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 22px 22px 18px;
  border-bottom: 1px solid var(--border-soft);
}

.smart-chat-sidebar__eyebrow {
  font-size: 10px;
  font-weight: 800;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: var(--muted);
  margin-bottom: 6px;
}

.smart-chat-sidebar__title {
  font-family: var(--font-heading);
  font-size: 24px;
  font-weight: 700;
  letter-spacing: -.02em;
  color: var(--ink);
}

.smart-chat-refresh-btn {
  border: 1px solid rgba(200, 16, 46, 0.16);
  background: rgba(255,255,255,.82);
  color: var(--red);
  border-radius: 999px;
  padding: 10px 16px;
  font-size: 12px;
  font-weight: 700;
  transition: background-color .2s ease, border-color .2s ease, transform .2s ease;
}

.smart-chat-refresh-btn:hover {
  background: var(--red-light);
  border-color: rgba(200, 16, 46, 0.28);
  transform: translateY(-1px);
}

.smart-chat-sidebar__search {
  position: relative;
  padding: 18px 22px 14px;
}

.smart-chat-sidebar__search .search-bar {
  width: 100%;
  min-height: 48px;
  padding: 12px 16px 12px 42px;
  background: rgba(255,255,255,.9);
  border: 1px solid rgba(10, 10, 15, 0.08);
  border-radius: 18px;
  box-shadow: none;
}

.smart-chat-sidebar__search .search-icon {
  position: absolute;
  left: 36px;
  top: 50%;
  transform: translateY(-36%);
  color: var(--muted);
  font-size: 15px;
}

.smart-chat-sidebar__summary {
  display: flex;
  gap: 10px;
  padding: 0 22px 14px;
  flex-wrap: wrap;
}

.smart-chat-summary-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border-radius: 999px;
  background: rgba(255,255,255,.86);
  border: 1px solid rgba(10, 10, 15, 0.06);
  font-size: 12px;
  color: var(--ink-60);
}

.smart-chat-summary-pill strong {
  color: var(--ink);
}

.smart-chat-sidebar__filters {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  padding: 0 22px 14px;
}

.smart-chat-filter {
  border: 1px solid rgba(10, 10, 15, 0.08);
  background: rgba(255,255,255,.72);
  color: var(--ink-60);
  border-radius: 999px;
  padding: 8px 14px;
  font-size: 12px;
  font-weight: 700;
  transition: background-color .2s ease, border-color .2s ease, color .2s ease;
}

.smart-chat-filter:hover {
  background: rgba(255,255,255,.92);
  border-color: rgba(10, 10, 15, 0.12);
}

.smart-chat-filter.is-active {
  background: linear-gradient(135deg, rgba(200,16,46,.12), rgba(255,255,255,.95));
  color: var(--red);
  border-color: rgba(200,16,46,.16);
}

.smart-chat-contacts {
  flex: 1;
  min-height: 0;
  overflow: auto;
  display: flex;
  flex-direction: column;
  padding: 6px 10px 12px;
}

.smart-chat-contact {
  width: 100%;
  text-align: left;
  border: 0;
  background: transparent;
  padding: 14px 12px;
  border-radius: 20px;
  cursor: pointer;
  transition: background-color .2s ease, box-shadow .2s ease, transform .2s ease;
}

.smart-chat-contact + .smart-chat-contact {
  margin-top: 4px;
}

.smart-chat-contact:hover {
  background: rgba(255,255,255,.74);
}

.smart-chat-contact.is-active {
  background: linear-gradient(135deg, rgba(200,16,46,.08), rgba(255,255,255,.96));
  box-shadow: var(--shadow-xs);
}

.smart-chat-contact__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.smart-chat-contact__main {
  display: flex;
  gap: 12px;
  min-width: 0;
}

.smart-chat-contact__avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
}

.smart-chat-contact__body {
  min-width: 0;
}

.smart-chat-contact__name {
  font-size: 17px;
  font-weight: 700;
  line-height: 1.2;
  color: var(--ink);
}

.smart-chat-contact__sub {
  margin-top: 4px;
  font-size: 13px;
  color: var(--muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.smart-chat-contact__preview {
  margin-top: 8px;
  font-size: 13px;
  color: var(--ink-60);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.smart-chat-contact__meta {
  margin-top: 10px;
  font-size: 12px;
  color: var(--muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.smart-chat-contact__side {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 10px;
  flex-shrink: 0;
}

.smart-chat-contact__time {
  font-size: 12px;
  font-weight: 700;
  color: var(--muted);
}

.smart-chat-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 24px;
  height: 24px;
  padding: 0 7px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 800;
}

.smart-chat-badge--unread {
  background: var(--red);
  color: var(--white);
}

.smart-chat-thread {
  min-width: 0;
  min-height: 0;
  display: grid;
  grid-template-rows: auto auto minmax(0, 1fr) auto;
  background: linear-gradient(180deg, rgba(255,255,255,.98), rgba(247,243,239,.38));
}

.smart-chat-thread__head {
  padding: 24px 28px 20px;
  border-bottom: 1px solid var(--border-soft);
  background: rgba(255,255,255,.84);
}

.smart-chat-thread__identity {
  display: flex;
  align-items: center;
  gap: 16px;
  min-width: 0;
}

.smart-chat-thread__avatar {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  flex-shrink: 0;
}

.smart-chat-thread__meta {
  min-width: 0;
}

.smart-chat-thread__name {
  font-family: var(--font-heading);
  font-size: 18px;
  font-weight: 700;
  letter-spacing: -.02em;
  color: var(--ink);
}

.smart-chat-thread__status {
  margin-top: 4px;
  font-size: 13px;
  color: var(--muted);
}

.smart-chat-thread__context {
  margin-top: 8px;
  font-size: 13px;
  color: var(--ink-60);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.smart-chat-thread__prompts {
  display: none;
}

.smart-chat-thread__messages {
  min-height: 0;
  overflow: auto;
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding: 24px 28px;
}

.smart-chat-day {
  display: flex;
  justify-content: center;
}

.smart-chat-day span {
  padding: 6px 12px;
  border-radius: 999px;
  background: rgba(255,255,255,.9);
  border: 1px solid rgba(10, 10, 15, 0.06);
  color: var(--muted);
  font-size: 11px;
  font-weight: 700;
}

.smart-chat-message-row {
  display: flex;
  align-items: flex-end;
  gap: 10px;
}

.smart-chat-message-row.is-mine {
  justify-content: flex-end;
}

.smart-chat-message-avatar {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
  color: var(--white);
  flex-shrink: 0;
}

.smart-chat-message {
  max-width: min(68%, 660px);
  padding: 12px 16px;
  border-radius: 18px;
  font-size: 14px;
  line-height: 1.55;
  white-space: pre-wrap;
}

.smart-chat-message.is-mine {
  background: linear-gradient(135deg, var(--red) 0%, var(--red-mid) 100%);
  color: var(--white);
  border-bottom-right-radius: 8px;
  box-shadow: 0 12px 24px rgba(200, 16, 46, 0.16);
}

.smart-chat-message:not(.is-mine) {
  background: rgba(255,255,255,.92);
  color: var(--ink);
  border: 1px solid rgba(10, 10, 15, 0.06);
  border-bottom-left-radius: 8px;
}

.smart-chat-empty {
  flex: 1;
  min-height: 280px;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.smart-chat-empty__card {
  max-width: 420px;
  padding: 28px;
  border-radius: 28px;
  background: rgba(255,255,255,.82);
  border: 1px solid rgba(10, 10, 15, 0.06);
  box-shadow: var(--shadow-xs);
}

.smart-chat-empty__icon {
  width: 54px;
  height: 54px;
  margin: 0 auto 16px;
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, rgba(200,16,46,.14), rgba(255,255,255,.95));
  color: var(--red);
  font-size: 22px;
}

.smart-chat-empty__title {
  font-family: var(--font-heading);
  font-size: 20px;
  font-weight: 700;
  color: var(--ink);
}

.smart-chat-empty__text {
  margin-top: 8px;
  font-size: 14px;
  line-height: 1.6;
  color: var(--muted);
}

.smart-chat-composer {
  border-top: 1px solid var(--border-soft);
  background: rgba(255,255,255,.9);
  padding: 18px 22px 22px;
}

.smart-chat-composer__context {
  display: grid;
  grid-template-columns: 280px minmax(0, 1fr);
  gap: 16px;
  align-items: end;
  margin-bottom: 14px;
}

.smart-chat-composer__context-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.smart-chat-composer__label {
  font-size: 11px;
  font-weight: 800;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: var(--muted);
}

.smart-chat-composer__hint {
  font-size: 13px;
  color: var(--ink-60);
  padding-bottom: 10px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.smart-chat-composer__bar {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 14px;
  align-items: end;
  padding: 12px;
  border-radius: 24px;
  background: rgba(247,243,239,.82);
  border: 1px solid rgba(10, 10, 15, 0.06);
}

.smart-chat-composer__textarea {
  min-height: 88px;
  max-height: 180px;
  resize: none;
  border: 0;
  background: transparent;
  box-shadow: none;
  padding: 8px 10px;
  font-size: 15px;
  line-height: 1.6;
}

.smart-chat-composer__textarea:hover,
.smart-chat-composer__textarea:focus {
  border: 0;
  box-shadow: none;
  background: transparent;
}

.smart-chat-send-btn {
  min-width: 120px;
  min-height: 48px;
  padding: 0 22px;
  border: 0;
  border-radius: 999px;
  background: linear-gradient(135deg, var(--red) 0%, var(--red-mid) 100%);
  color: var(--white);
  font-size: 14px;
  font-weight: 700;
  box-shadow: var(--shadow-red);
  transition: transform .2s ease, box-shadow .2s ease, opacity .2s ease;
}

.smart-chat-send-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 20px 40px rgba(200, 16, 46, 0.2);
}

.smart-chat-send-btn:disabled {
  opacity: .55;
  cursor: not-allowed;
  box-shadow: none;
}

@media (max-width: 1180px) {
  .smart-chat-shell {
    grid-template-columns: 320px minmax(0, 1fr);
  }
}

@media (max-width: 960px) {
  .smart-chat-shell {
    grid-template-columns: 1fr;
    min-height: 0;
    height: auto;
  }

  .smart-chat-sidebar {
    border-right: 0;
    border-bottom: 1px solid var(--border-soft);
    max-height: 420px;
  }

  .smart-chat-composer__context {
    grid-template-columns: 1fr;
  }

  .smart-chat-composer__hint {
    padding-bottom: 0;
  }
}

@media (max-width: 720px) {
  .smart-chat-sidebar__header,
  .smart-chat-sidebar__search,
  .smart-chat-sidebar__summary,
  .smart-chat-sidebar__filters,
  .smart-chat-thread__head,
  .smart-chat-thread__messages,
  .smart-chat-composer {
    padding-left: 16px;
    padding-right: 16px;
  }

  .smart-chat-contact {
    padding-left: 10px;
    padding-right: 10px;
  }

  .smart-chat-composer__bar {
    grid-template-columns: 1fr;
  }

  .smart-chat-send-btn {
    width: 100%;
  }

  .smart-chat-message {
    max-width: 86%;
  }
}
</style>

<script>
(function() {
  const root = document.getElementById(<?= json_encode($chatPanelId) ?>);

  if (!root) {
    return;
  }

  const apiUrl = <?= json_encode($chatPanelApiUrl) ?>;
  const csrfToken = <?= json_encode($chatPanelToken) ?>;
  const persistKey = `atlasTalents.chat.<?= htmlspecialchars($chatPanelId, ENT_QUOTES) ?>`;
  const panelSubtitle = <?= json_encode($chatPanelSubtitle) ?>;
  const statsNode = root.querySelector('[data-chat-stats]');
  const contactsNode = root.querySelector('[data-chat-contacts]');
  const threadHeadNode = root.querySelector('[data-chat-thread-head]');
  const promptsNode = root.querySelector('[data-chat-prompts]');
  const messagesNode = root.querySelector('[data-chat-messages]');
  const form = root.querySelector('[data-chat-form]');
  const bodyInput = root.querySelector('[data-chat-body]');
  const studentSelect = root.querySelector('[data-chat-student]');
  const hintNode = root.querySelector('[data-chat-hint]');
  const sendButton = root.querySelector('[data-chat-send]');
  const refreshButton = root.querySelector('[data-chat-refresh]');
  const searchInput = root.querySelector('[data-chat-search]');
  const filterButtons = Array.from(root.querySelectorAll('[data-chat-filter]'));

  let state = <?= json_encode($chatPanelData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  let drafts = {};
  let search = '';
  let filter = 'all';
  let activeContactId = state.active_contact_id || (state.contacts[0] ? state.contacts[0].id : null);
  let isRefreshing = false;
  const pendingRead = new Set();

  function safeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function normalizeText(value) {
    return String(value ?? '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '');
  }

  function notify(message, type = 'default') {
    if (typeof showToast === 'function') {
      showToast(message, type);
    }
  }

  function readPersistedState() {
    try {
      const parsed = JSON.parse(sessionStorage.getItem(persistKey) || '{}');
      if (parsed && typeof parsed === 'object') {
        drafts = parsed.drafts && typeof parsed.drafts === 'object' ? parsed.drafts : {};
        search = typeof parsed.search === 'string' ? parsed.search : '';
        filter = typeof parsed.filter === 'string' ? parsed.filter : 'all';
        activeContactId = parsed.activeContactId || activeContactId;
      }
    } catch (error) {
      drafts = {};
    }
  }

  function persistState() {
    try {
      sessionStorage.setItem(persistKey, JSON.stringify({
        drafts,
        search,
        filter,
        activeContactId,
      }));
    } catch (error) {
      // Ignore storage failures.
    }
  }

  function getContactById(contactId) {
    return (state.contacts || []).find(contact => String(contact.id) === String(contactId)) || null;
  }

  function getVisibleContacts() {
    const normalizedSearch = normalizeText(search);

    return (state.contacts || []).filter(contact => {
      if (filter === 'unread' && Number(contact.unread_count || 0) <= 0) {
        return false;
      }

      if (filter === 'staff' && contact.role_bucket !== 'staff') {
        return false;
      }

      if (filter === 'talent' && contact.role_bucket !== 'talent') {
        return false;
      }

      if (normalizedSearch === '') {
        return true;
      }

      const haystack = [
        contact.name,
        contact.subtitle,
        contact.context_label,
        contact.latest_message,
        ...(contact.related_students || []).map(student => student.name),
      ].join(' ');

      return normalizeText(haystack).includes(normalizedSearch);
    });
  }

  function ensureActiveContact() {
    const visibleContacts = getVisibleContacts();
    const activeContact = getContactById(activeContactId);

    if (activeContact && visibleContacts.some(contact => String(contact.id) === String(activeContact.id))) {
      return activeContact;
    }

    activeContactId = visibleContacts[0]?.id || state.contacts?.[0]?.id || null;
    return getContactById(activeContactId);
  }

  function getDraft(contactId) {
    const key = String(contactId || '');
    if (!drafts[key] || typeof drafts[key] !== 'object') {
      drafts[key] = { body: '', studentId: '' };
    }

    return drafts[key];
  }

  function updateDraft(contactId, patch) {
    drafts[String(contactId || '')] = { ...getDraft(contactId), ...patch };
    persistState();
  }

  function renderStats() {
    if (!statsNode) {
      return;
    }

    statsNode.innerHTML = `
      <span class="smart-chat-summary-pill"><strong>${safeHtml(state.contact_count || 0)}</strong> conversations</span>
      <span class="smart-chat-summary-pill"><strong>${safeHtml(state.unread_total || 0)}</strong> non lus</span>
    `;
  }

  function renderContacts() {
    if (!contactsNode) {
      return;
    }

    const contacts = getVisibleContacts();

    if (!contacts.length) {
      contactsNode.innerHTML = `
        <div class="smart-chat-empty">
          <div class="smart-chat-empty__card">
            <div class="smart-chat-empty__icon">&#128269;</div>
            <div class="smart-chat-empty__title">Aucun contact trouve</div>
            <div class="smart-chat-empty__text">Ajustez votre recherche ou changez de filtre pour afficher les conversations liees a vos talents.</div>
          </div>
        </div>
      `;
      return;
    }

    contactsNode.innerHTML = contacts.map(contact => `
      <button class="smart-chat-contact ${String(contact.id) === String(activeContactId) ? 'is-active' : ''}" type="button" data-contact-id="${safeHtml(contact.id)}">
        <div class="smart-chat-contact__head">
          <div class="smart-chat-contact__main">
            <div class="smart-chat-contact__avatar avatar avatar-sm ${safeHtml(contact.avatar_class || 'avatar-blue')}">${safeHtml(contact.avatar || 'U')}</div>
            <div class="smart-chat-contact__body">
              <div class="smart-chat-contact__name">${safeHtml(contact.name)}</div>
              <div class="smart-chat-contact__sub">${safeHtml(contact.role_label || '')}${contact.subtitle ? ` · ${safeHtml(contact.subtitle)}` : ''}</div>
              <div class="smart-chat-contact__preview">${safeHtml(contact.latest_message || 'Aucun message pour le moment.')}</div>
              <div class="smart-chat-contact__meta">${safeHtml(contact.context_label || '')}</div>
            </div>
          </div>
          <div class="smart-chat-contact__side">
            <span class="smart-chat-contact__time">${safeHtml(contact.latest_time_label || '')}</span>
            ${Number(contact.unread_count || 0) > 0
              ? `<span class="smart-chat-badge smart-chat-badge--unread">${safeHtml(contact.unread_count)}</span>`
              : ''}
          </div>
        </div>
      </button>
    `).join('');
  }

  function renderThreadHead(contact) {
    if (!threadHeadNode) {
      return;
    }

    if (!contact) {
      threadHeadNode.innerHTML = `
        <div class="smart-chat-empty">
          <div class="smart-chat-empty__card">
            <div class="smart-chat-empty__icon">&#9993;</div>
            <div class="smart-chat-empty__title">Choisissez une conversation</div>
            <div class="smart-chat-empty__text">Selectionnez un contact dans la colonne de gauche pour ouvrir l'espace de discussion.</div>
          </div>
        </div>
      `;
      return;
    }

    const contextText = (contact.related_students || []).length
      ? `Autour de ${contact.related_students.map(student => student.name).join(', ')}`
      : (contact.context_label || panelSubtitle);

    threadHeadNode.innerHTML = `
      <div class="smart-chat-thread__identity">
        <div class="smart-chat-thread__avatar avatar avatar-md ${safeHtml(contact.avatar_class || 'avatar-blue')}">${safeHtml(contact.avatar || 'U')}</div>
        <div class="smart-chat-thread__meta">
          <div class="smart-chat-thread__name">${safeHtml(contact.name)}</div>
          <div class="smart-chat-thread__status">${safeHtml(contact.role_label || 'Contact')} · ${safeHtml(contact.subtitle || 'Messagerie Atlas')}</div>
          <div class="smart-chat-thread__context">${safeHtml(contextText)}</div>
        </div>
      </div>
    `;
  }

  function renderPrompts() {
    if (!promptsNode) {
      return;
    }

    promptsNode.innerHTML = '';
  }

  function renderMessages(contact) {
    if (!messagesNode) {
      return;
    }

    if (!contact) {
      messagesNode.innerHTML = '';
      return;
    }

    const messages = contact.messages || [];

    if (!messages.length) {
      messagesNode.innerHTML = `
        <div class="smart-chat-empty">
          <div class="smart-chat-empty__card">
            <div class="smart-chat-empty__icon">&#128172;</div>
            <div class="smart-chat-empty__title">Aucun message pour le moment</div>
            <div class="smart-chat-empty__text">Lancez une premiere conversation claire et concrete pour coordonner le suivi de ce profil.</div>
          </div>
        </div>
      `;
      return;
    }

    let currentDay = '';
    const markup = [];

    messages.forEach(message => {
      if (message.day_label !== currentDay) {
        currentDay = message.day_label;
        markup.push(`<div class="smart-chat-day"><span>${safeHtml(currentDay)}</span></div>`);
      }

      markup.push(`
        <div class="smart-chat-message-row ${message.is_mine ? 'is-mine' : ''}">
          ${message.is_mine ? '' : `<div class="smart-chat-message-avatar avatar avatar-sm ${safeHtml(contact.avatar_class || 'avatar-blue')}">${safeHtml(contact.avatar || 'U')}</div>`}
          <article class="smart-chat-message ${message.is_mine ? 'is-mine' : ''}">${safeHtml(message.body || '')}</article>
        </div>
      `);
    });

    messagesNode.innerHTML = markup.join('');
    messagesNode.scrollTop = messagesNode.scrollHeight;
  }

  function renderComposer(contact) {
    if (!studentSelect || !bodyInput || !hintNode || !sendButton) {
      return;
    }

    if (!contact) {
      studentSelect.innerHTML = '<option value="">Sans contexte specifique</option>';
      studentSelect.disabled = true;
      bodyInput.disabled = true;
      bodyInput.value = '';
      sendButton.disabled = true;
      hintNode.textContent = 'Selectionnez un contact pour commencer.';
      return;
    }

    const draft = getDraft(contact.id);
    const relatedStudents = contact.related_students || [];
    const validStudentIds = relatedStudents.map(student => String(student.id));
    let selectedStudentId = String(draft.studentId || '');

    if (selectedStudentId && !validStudentIds.includes(selectedStudentId)) {
      selectedStudentId = '';
    }

    if (!selectedStudentId && relatedStudents.length === 1) {
      selectedStudentId = String(relatedStudents[0].id);
    }

    studentSelect.innerHTML = `
      <option value="">Sans contexte specifique</option>
      ${relatedStudents.map(student => `
        <option value="${safeHtml(student.id)}" ${String(student.id) === selectedStudentId ? 'selected' : ''}>${safeHtml(student.name)}</option>
      `).join('')}
    `;

    studentSelect.disabled = false;
    bodyInput.disabled = false;
    bodyInput.value = draft.body || '';
    sendButton.disabled = false;
    hintNode.textContent = relatedStudents.length
      ? `Contexte partage : ${relatedStudents.map(student => student.name).join(', ')}`
      : 'Sans contexte partage';

    if (selectedStudentId !== String(draft.studentId || '')) {
      updateDraft(contact.id, { studentId: selectedStudentId });
    }
  }

  function render() {
    const activeContact = ensureActiveContact();

    if (searchInput && searchInput.value !== search) {
      searchInput.value = search;
    }

    filterButtons.forEach(button => {
      button.classList.toggle('is-active', button.dataset.chatFilter === filter);
    });

    renderStats();
    renderContacts();
    renderThreadHead(activeContact);
    renderPrompts(activeContact);
    renderMessages(activeContact);
    renderComposer(activeContact);
    persistState();
  }

  async function fetchChatState(action, payload = null) {
    const options = { credentials: 'same-origin' };
    const requestUrl = `${apiUrl}?action=${encodeURIComponent(action)}`;

    if (payload) {
      options.method = 'POST';
      options.body = payload;
    }

    const response = await fetch(requestUrl, options);
    const data = await response.json().catch(() => ({}));

    if (!response.ok || !data.success) {
      throw new Error(data.error || 'Une erreur est survenue.');
    }

    return data.data || {};
  }

  function applyChatState(nextState) {
    state = nextState || { contacts: [] };

    if (!getContactById(activeContactId)) {
      activeContactId = state.active_contact_id || state.contacts?.[0]?.id || null;
    }

    render();
  }

  async function refreshChat(showFeedback = false) {
    if (isRefreshing) {
      return;
    }

    isRefreshing = true;
    if (refreshButton) {
      refreshButton.disabled = true;
    }

    try {
      applyChatState(await fetchChatState('chat_bootstrap'));
      if (showFeedback) {
        notify('Messagerie actualisee.', 'success');
      }
    } catch (error) {
      notify(error.message || 'Actualisation impossible.', 'error');
    } finally {
      isRefreshing = false;
      if (refreshButton) {
        refreshButton.disabled = false;
      }
    }
  }

  async function markConversationRead(contactId) {
    const contact = getContactById(contactId);

    if (!contact || Number(contact.unread_count || 0) <= 0 || pendingRead.has(String(contactId))) {
      return;
    }

    pendingRead.add(String(contactId));

    try {
      const formData = new FormData();
      formData.append('action', 'chat_read');
      formData.append('_token', csrfToken);
      formData.append('contact_id', String(contactId));
      applyChatState(await fetchChatState('chat_read', formData));
    } catch (error) {
      notify(error.message || 'Impossible de marquer la conversation comme lue.', 'error');
    } finally {
      pendingRead.delete(String(contactId));
    }
  }

  async function sendMessage() {
    const contact = ensureActiveContact();

    if (!contact || !bodyInput) {
      return;
    }

    const body = String(bodyInput.value || '').trim();

    if (body === '') {
      notify('Le message ne peut pas etre vide.', 'warning');
      bodyInput.focus();
      return;
    }

    const draft = getDraft(contact.id);
    const selectedStudentId = String(studentSelect?.value || draft.studentId || '');

    if (sendButton) {
      sendButton.disabled = true;
    }

    try {
      const formData = new FormData();
      formData.append('action', 'chat_send');
      formData.append('_token', csrfToken);
      formData.append('recipient_id', String(contact.id));
      formData.append('body', body);

      if (selectedStudentId !== '') {
        formData.append('student_id', selectedStudentId);
      }

      applyChatState(await fetchChatState('chat_send', formData));
      drafts[String(contact.id)] = { body: '', studentId: selectedStudentId };
      render();
      bodyInput.focus();
      notify('Message envoye.', 'success');
    } catch (error) {
      notify(error.message || 'Envoi impossible.', 'error');
    } finally {
      if (sendButton) {
        sendButton.disabled = false;
      }
    }
  }

  refreshButton?.addEventListener('click', () => refreshChat(true));

  searchInput?.addEventListener('input', event => {
    search = event.target.value || '';
    render();
  });

  filterButtons.forEach(button => {
    button.addEventListener('click', () => {
      filter = button.dataset.chatFilter || 'all';
      render();
    });
  });

  contactsNode?.addEventListener('click', event => {
    const button = event.target.closest('[data-contact-id]');
    if (!button) {
      return;
    }

    activeContactId = button.dataset.contactId || null;
    render();

    if (activeContactId) {
      markConversationRead(activeContactId);
    }
  });

  studentSelect?.addEventListener('change', event => {
    const contact = ensureActiveContact();
    if (!contact) {
      return;
    }

    updateDraft(contact.id, { studentId: event.target.value || '' });
  });

  bodyInput?.addEventListener('input', event => {
    const contact = ensureActiveContact();
    if (!contact) {
      return;
    }

    updateDraft(contact.id, { body: event.target.value || '' });
  });

  bodyInput?.addEventListener('keydown', event => {
    if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
      event.preventDefault();
      sendMessage();
    }
  });

  form?.addEventListener('submit', event => {
    event.preventDefault();
    sendMessage();
  });

  root.openSmartChat = function() {
    root.scrollIntoView({ behavior: 'smooth', block: 'start' });
    window.setTimeout(() => {
      const contact = ensureActiveContact();
      render();
      if (contact && Number(contact.unread_count || 0) > 0) {
        markConversationRead(contact.id);
      }
      if (bodyInput && !bodyInput.disabled) {
        bodyInput.focus();
      } else if (searchInput) {
        searchInput.focus();
      }
    }, 40);
  };

  readPersistedState();
  render();
})();
</script>
