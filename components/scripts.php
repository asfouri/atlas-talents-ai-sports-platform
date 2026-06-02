<!-- components/scripts.php -->
<div class="toast" id="globalToast">
  <span id="toastIcon">&#10003;</span>
  <span id="toastMsg">Message</span>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
const atlasCsrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

function showToast(msg, type = 'default') {
  const t = document.getElementById('globalToast');
  const icons = { success: '✓', error: '✕', warning: '⚠', default: 'ℹ' };
  document.getElementById('toastIcon').textContent = icons[type] || icons.default;
  document.getElementById('toastMsg').textContent = msg;
  t.className = 'toast show ' + type;
  clearTimeout(t._timer);
  t._timer = setTimeout(() => t.classList.remove('show'), 3500);
}

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function openModal(id) {
  document.getElementById(id)?.classList.add('open');
}

function closeModal(id) {
  document.getElementById(id)?.classList.remove('open');
}

function setFilesOnInput(input, files) {
  if (!input || !files || !files.length || typeof DataTransfer !== 'function') {
    return false;
  }

  const transfer = new DataTransfer();
  Array.from(files).forEach(file => transfer.items.add(file));
  input.files = transfer.files;
  return true;
}

function exportDashboardPdf(title, subtitle = '', sections = []) {
  const printWindow = window.open('', '_blank', 'width=980,height=760');

  if (!printWindow) {
    showToast('Autorisez les popups pour exporter le PDF.', 'error');
    return;
  }

  const renderedSections = sections.map(section => `
    <section class="print-section">
      <h2>${escapeHtml(section.heading || '')}</h2>
      <div>${section.content || ''}</div>
    </section>
  `).join('');

  printWindow.document.write(`<!DOCTYPE html>
  <html lang="fr">
  <head>
    <meta charset="UTF-8">
    <title>${escapeHtml(title)}</title>
    <style>
      body { font-family: Arial, sans-serif; margin: 32px; color: #111; }
      h1 { margin: 0 0 6px; font-size: 28px; }
      .subtitle { color: #666; margin-bottom: 24px; }
      .print-section { margin-bottom: 24px; page-break-inside: avoid; }
      .print-section h2 { font-size: 18px; margin: 0 0 10px; padding-bottom: 8px; border-bottom: 2px solid #C8102E; }
      table { width: 100%; border-collapse: collapse; margin-top: 8px; }
      th, td { border: 1px solid #ddd; padding: 8px 10px; text-align: left; font-size: 13px; }
      th { background: #f8f0f1; }
      .metric-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
      .metric-card { border: 1px solid #ddd; border-radius: 12px; padding: 12px; }
      .metric-card strong { display: block; font-size: 22px; color: #C8102E; margin-bottom: 4px; }
      ul { margin: 8px 0 0 18px; padding: 0; }
    </style>
  </head>
  <body>
    <h1>${escapeHtml(title)}</h1>
    <div class="subtitle">${escapeHtml(subtitle)}</div>
    ${renderedSections}
    <script>
      window.onload = function() {
        window.print();
      };
    <\/script>
  </body>
  </html>`);
  printWindow.document.close();
}

function renderChartFallback(canvasId, message = 'Graphique indisponible pour le moment.') {
  const canvas = document.getElementById(canvasId);
  if (!canvas || !canvas.parentElement) {
    return;
  }

  canvas.parentElement.innerHTML = `<div class="chart-fallback">${message}</div>`;
}

document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', function(e) {
    if (e.target === this) {
      this.classList.remove('open');
    }
  });
});

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
  }
});

function syncNotificationBadges() {
  document.querySelectorAll('.notif-btn').forEach(button => {
    const count = Number(button.dataset.count || 0);
    const badge = button.querySelector('.notif-badge');

    if (!badge) {
      return;
    }

    badge.textContent = String(count);
    badge.style.display = count > 0 ? '' : 'none';
  });
}

function toggleNotifs(button = null) {
  const source = button || document.querySelector('.notif-btn');
  const count = Number(source?.dataset.count || 0);

  if (count > 0) {
    showToast(`${count} notification${count > 1 ? 's' : ''} non lue${count > 1 ? 's' : ''}.`, 'default');
    return;
  }

  showToast('Aucune nouvelle notification.', 'default');
}

function openSmartChatPanel(panelId) {
  const root = document.getElementById(panelId);
  if (!root) {
    showToast('Messagerie indisponible pour le moment.', 'warning');
    return false;
  }

  root.scrollIntoView({ behavior: 'smooth', block: 'start' });

  if (typeof root.openSmartChat === 'function') {
    root.openSmartChat();
    return true;
  }

  const launcher = root.querySelector('[data-chat-open]');
  if (launcher) {
    launcher.click();
    return true;
  }

  showToast('Messagerie indisponible pour le moment.', 'warning');
  return false;
}

function submitLogout(event) {
  if (event && typeof event.preventDefault === 'function') {
    event.preventDefault();
  }

  if (!atlasCsrfToken) {
    showToast('Session invalide. Rechargez la page avant de vous deconnecter.', 'error');
    return false;
  }

  const form = document.createElement('form');
  form.method = 'POST';
  form.action = `${window.location.origin}${<?= json_encode(APP_PATH) ?>}/pages/auth/logout.php`;

  const tokenInput = document.createElement('input');
  tokenInput.type = 'hidden';
  tokenInput.name = '_token';
  tokenInput.value = atlasCsrfToken;
  form.appendChild(tokenInput);

  document.body.appendChild(form);
  form.submit();
  return false;
}

function setActiveSidebarItem(activeItem) {
  document.querySelectorAll('.sidebar-item').forEach(item => item.classList.remove('active'));
  activeItem?.classList.add('active');
}

function bindSidebarActions() {
  document.querySelectorAll('.sidebar-item').forEach(item => {
    if (item.dataset.sidebarBound === 'true') {
      return;
    }

    item.dataset.sidebarBound = 'true';
    item.setAttribute('tabindex', '0');
    item.setAttribute('role', 'button');

    const runAction = () => {
      if (item.dataset.href && item.dataset.href.endsWith('/pages/auth/logout.php')) {
        submitLogout();
        return;
      }

      if (item.dataset.href) {
        window.location.href = item.dataset.href;
        return;
      }

      if (item.dataset.modal) {
        openModal(item.dataset.modal);
        setActiveSidebarItem(item);
        return;
      }

      if (item.dataset.target) {
        const dashboardTargetHandler = window.handleDashboardTarget;
        if (typeof dashboardTargetHandler === 'function' && dashboardTargetHandler(item.dataset.target, item) === true) {
          setActiveSidebarItem(item);
          return;
        }

        const target = document.getElementById(item.dataset.target);
        if (target) {
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
          setActiveSidebarItem(item);
        }
        return;
      }

      if (item.dataset.action) {
        const action = window[item.dataset.action];
        if (typeof action === 'function') {
          action(item);
          setActiveSidebarItem(item);
        }
        return;
      }

      if (item.dataset.toast) {
        showToast(item.dataset.toast, item.dataset.toastType || 'default');
        setActiveSidebarItem(item);
      }
    };

    item.addEventListener('click', runAction);
    item.addEventListener('keydown', event => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        runAction();
      }
    });
  });
}

document.querySelectorAll('.upload-drop').forEach(zone => {
  ['dragenter', 'dragover'].forEach(ev => {
    zone.addEventListener(ev, e => {
      e.preventDefault();
      zone.classList.add('dragover');
    });
  });

  ['dragleave', 'drop'].forEach(ev => {
    zone.addEventListener(ev, e => {
      e.preventDefault();
      zone.classList.remove('dragover');
    });
  });

  zone.addEventListener('drop', e => {
    const files = e.dataTransfer.files;
    if (!files.length) {
      return;
    }

    const input = zone.querySelector('input[type="file"]') || document.getElementById('videoInput');
    const assigned = setFilesOnInput(input, files);

    if (assigned && typeof window.handleFileSelect === 'function') {
      window.handleFileSelect(input);
    }

    showToast('Video recue : ' + files[0].name, 'success');
  });
});

if (typeof Chart !== 'undefined') {
  Chart.defaults.font.family = "'DM Sans', sans-serif";
  Chart.defaults.font.size = 12;
  Chart.defaults.color = '#6B6B80';
  Chart.defaults.plugins.legend.display = false;
  Chart.defaults.plugins.tooltip.backgroundColor = '#0A0A0F';
  Chart.defaults.plugins.tooltip.padding = 10;
  Chart.defaults.plugins.tooltip.cornerRadius = 8;
  Chart.defaults.scale.grid.color = '#F0F0F8';
  Chart.defaults.scale.ticks.padding = 6;
}

document.querySelectorAll('.sidebar-item[data-page]').forEach(item => {
  if (window.location.pathname.includes(item.dataset.page)) {
    item.classList.add('active');
  }
});

document.querySelectorAll('a[href$="/pages/auth/logout.php"]').forEach(link => {
  link.addEventListener('click', event => {
    submitLogout(event);
  });
});

bindSidebarActions();
syncNotificationBadges();

/* ── Mobile sidebar toggle ──────────────────────────────────────────── */
function toggleSidebar() {
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  if (!sidebar) return;
  const isOpen = sidebar.classList.toggle('open');
  if (overlay) overlay.classList.toggle('open', isOpen);
  document.body.style.overflow = isOpen ? 'hidden' : '';
}

function closeSidebar() {
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  if (!sidebar) return;
  sidebar.classList.remove('open');
  if (overlay) overlay.classList.remove('open');
  document.body.style.overflow = '';
}

/* Close sidebar when a nav item is clicked on mobile */
document.querySelectorAll('.sidebar-item').forEach(item => {
  item.addEventListener('click', () => {
    if (window.innerWidth <= 1024) closeSidebar();
  });
});

const urlParams = new URLSearchParams(window.location.search);
const urlMsg = urlParams.get('msg');
const urlErr = urlParams.get('error');

if (urlMsg) {
  showToast(decodeURIComponent(urlMsg), 'success');
}

if (urlErr) {
  showToast(decodeURIComponent(urlErr), 'error');
}
</script>
