<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

Auth::check(ROLE_RECRUITER);

$user = Auth::user();
$students = atlasGetRecruiterStudents();
$clubName = userSubtitle($user);
$avgScore = atlasAverageScore($students);
$highPotential = count(atlasTopTalents($students));
$favoriteStudents = atlasGetFavoriteStudentsForRecruiter((int) $user['id']);
$favoriteStudentIds = array_values(array_map(static fn(array $student): int => (int) ($student['id'] ?? 0), $favoriteStudents));
$favoriteCount = count($favoriteStudents);

$cityCounts = [];
foreach ($students as $student) {
    $cityCounts[$student['ville']] = ($cityCounts[$student['ville']] ?? 0) + 1;
}
arsort($cityCounts);

$pageTitle = 'Dashboard Recruteur';
$navType = 'recruiter';
$bodyClass = 'dash-body';
include __DIR__ . '/../../components/head.php';
include __DIR__ . '/../../components/navbar.php';
?>

<div class="dash-layout">
  <aside class="sidebar">
    <div class="sidebar-user">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
        <div class="avatar avatar-md avatar-blue"><?= htmlspecialchars($user['avatar']) ?></div>
        <div>
          <div class="sidebar-user-name"><?= htmlspecialchars($user['name']) ?></div>
          <div class="sidebar-user-role"><?= htmlspecialchars($clubName) ?></div>
        </div>
      </div>
    </div>
    <nav class="sidebar-nav">
      <div class="sidebar-item active" data-target="recruiterOverview"><span class="icon">⌂</span> Tableau de bord</div>
      <div class="sidebar-item" data-target="recruiterTalents"><span class="icon">⌕</span> Explorer talents</div>
      <div class="sidebar-item" data-target="recruiterFavorites"><span class="icon">★</span> Mes favoris <span class="sidebar-badge"><?= (int) $favoriteCount ?></span></div>
      <div class="sidebar-item" data-target="recruiterMessages"><span class="icon">✉</span> Messages</div>
      <div class="sidebar-label" style="margin-top:16px;">Outils</div>
      <div class="sidebar-item" data-action="exportRecruiterPdf"><span class="icon">◌</span> Rapports</div>
      <div class="sidebar-item" data-target="recruiterMap"><span class="icon">◍</span> Carte Maroc</div>
      <div class="sidebar-label" style="margin-top:16px;">Compte</div>
      <div class="sidebar-item" data-modal="settingsModal"><span class="icon">⚙</span> Paramètres</div>
      <div class="sidebar-item" data-href="<?= APP_URL ?>/pages/auth/logout.php"><span class="icon">↪</span> Déconnexion</div>
    </nav>
  </aside>

  <main class="dash-main">
    <div class="dash-header" id="recruiterOverview">
      <div class="dash-header-top">
        <div>
          <h1 class="heading-xl">Talents détectés</h1>
          <p class="text-sm text-muted" style="margin-top:4px;"><?= htmlspecialchars($clubName) ?> · <?= htmlspecialchars(formatLongDate()) ?></p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
          <select class="form-control" id="recruiterSortSelect" style="width:auto;padding:10px 14px;" onchange="sortTalentCards(this.value)">
            <option>Plus récents</option>
            <option>Meilleur score</option>
            <option>Par ville</option>
          </select>
          <button class="btn btn-primary" type="button" onclick="exportRecruiterPdf()">⬇ Exporter</button>
        </div>
      </div>
    </div>

    <div class="grid-4 mb-24">
      <div class="stat-card"><div class="stat-icon" style="background:var(--green-light);">★</div><div><div class="stat-value"><?= (int) $highPotential ?></div><div class="stat-label">Talents prioritaires</div><div class="stat-trend trend-up">↗ Profils prêts au scouting</div></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:var(--blue-light);">◌</div><div><div class="stat-value"><?= (int) $avgScore ?>%</div><div class="stat-label">Score moyen</div><div class="stat-trend trend-flat">Qualité observée</div></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:var(--gold-light);">★</div><div><div class="stat-value" id="favoriteCounter"><?= (int) $favoriteCount ?></div><div class="stat-label">Favoris actifs</div><div class="stat-trend trend-flat">Sélection en cours</div></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:var(--red-light);">⌖</div><div><div class="stat-value"><?= count($cityCounts) ?></div><div class="stat-label">Villes couvertes</div><div class="stat-trend trend-flat">Tout le Maroc</div></div></div>
    </div>

    <div class="grid-filter">
      <div class="card recruiter-filter-card" style="align-self:start;position:sticky;top:88px;">
        <div class="heading-md mb-16" style="display:flex;align-items:center;gap:8px;">🔧 Filtres</div>

        <div style="margin-bottom:20px;">
          <div class="filter-group-label">Sport</div>
          <div style="display:flex;flex-direction:column;gap:6px;margin-top:8px;">
            <?php foreach (['Tous les sports', 'Athlétisme', 'Gymnastique', 'Football', 'Handball', 'Natation'] as $index => $sport): ?>
            <label class="filter-radio <?= $index === 0 ? 'active' : '' ?>" onclick="selectFilter(this, 'sport')">
              <input type="radio" name="sport" value="<?= htmlspecialchars($sport, ENT_QUOTES) ?>" <?= $index === 0 ? 'checked' : '' ?>>
              <span><?= htmlspecialchars($sport) ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div style="margin-bottom:20px;">
          <div class="filter-group-label">Ville</div>
          <div style="display:flex;flex-direction:column;gap:6px;margin-top:8px;">
            <?php foreach (['Toutes les villes', 'Casablanca', 'Rabat', 'Marrakech', 'Fès', 'Agadir'] as $index => $city): ?>
            <label class="filter-radio <?= $index === 0 ? 'active' : '' ?>" onclick="selectFilter(this, 'ville')">
              <input type="radio" name="ville" value="<?= htmlspecialchars($city, ENT_QUOTES) ?>" <?= $index === 0 ? 'checked' : '' ?>>
              <span><?= htmlspecialchars($city) ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div>
          <div class="filter-group-label">Score minimum</div>
          <input type="range" min="0" max="100" value="70" id="scoreMinInput" style="width:100%;accent-color:var(--red);margin-top:8px;" oninput="updateScoreFilter(this.value)">
          <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-top:4px;">
            <span>0</span>
            <span id="scoreMinVal" style="color:var(--red);font-weight:700;">70</span>
            <span>100</span>
          </div>
        </div>

        <button class="btn btn-outline" type="button" style="width:100%;justify-content:center;margin-top:20px;" onclick="applyTalentFilters(true)">Appliquer les filtres</button>
      </div>

      <div>
        <div id="recruiterTalents">
          <div class="mb-16" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <h3 class="heading-md">Talents détectés (<span id="talentCount"><?= count($students) ?></span>)</h3>
            <div class="search-bar-wrap" style="width:260px;">
              <span class="search-icon">🔍</span>
              <input type="text" class="search-bar" id="talentSearch" placeholder="Rechercher..." oninput="applyTalentFilters()">
            </div>
          </div>

          <div class="talent-grid">
            <?php
            $gradients = [
                'linear-gradient(135deg,#880e0e,#c62828)',
                'linear-gradient(135deg,#0d47a1,#1565c0)',
                'linear-gradient(135deg,#1b5e20,#2e7d32)',
                'linear-gradient(135deg,#4a148c,#6a1b9a)',
                'linear-gradient(135deg,#e65100,#bf360c)',
                'linear-gradient(135deg,#006064,#00838f)',
            ];
            $sportBadges = [
                'Athlétisme' => 'badge-blue',
                'Gymnastique' => 'badge-purple',
                'Football' => 'badge-success',
                'Natation' => 'badge-blue',
            ];
            foreach ($students as $index => $student):
                $gradient = $gradients[$index % count($gradients)];
                $badgeClass = $sportBadges[$student['sport']] ?? 'badge-blue';
                $studentPayload = htmlspecialchars(json_encode($student, JSON_UNESCAPED_UNICODE), ENT_QUOTES);
            ?>
            <div
              class="talent-card"
              data-name="<?= htmlspecialchars($student['name'], ENT_QUOTES) ?>"
              data-sport="<?= htmlspecialchars($student['sport'], ENT_QUOTES) ?>"
              data-ville="<?= htmlspecialchars($student['ville'], ENT_QUOTES) ?>"
              data-score="<?= (int) $student['score'] ?>"
              data-student='<?= $studentPayload ?>'
            >
              <div class="talent-thumb" style="background:<?= htmlspecialchars($gradient) ?>;">
                <?php if (!empty($student['video_url'])): ?>
                <video class="talent-video" src="<?= htmlspecialchars($student['video_url']) ?>" muted loop playsinline preload="metadata"></video>
                <?php endif; ?>
                <div class="talent-thumb-overlay"></div>
                <div class="talent-score-pin">🏅 <span><?= (int) $student['score'] ?></span></div>
                <span class="badge badge-new" style="position:absolute;top:12px;right:12px;">Nouveau</span>
                <div class="play-btn"><?= !empty($student['video_url']) ? '▶' : 'AI' ?></div>
              </div>
              <div class="talent-body">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:4px;">
                  <div class="talent-name"><?= htmlspecialchars($student['name']) ?></div>
                  <button class="fav-btn<?= in_array((int) ($student['id'] ?? 0), $favoriteStudentIds, true) ? ' active' : '' ?>" type="button" onclick="toggleFav(event, this)" title="Ajouter aux favoris" style="background:none;border:none;font-size:22px;cursor:pointer;color:var(--border);padding:0;line-height:1;"><?= in_array((int) ($student['id'] ?? 0), $favoriteStudentIds, true) ? '&#9733;' : '&#9734;' ?></button>
                </div>
                <div class="talent-location">📍 <?= (int) $student['age'] ?> ans · <?= htmlspecialchars($student['ville']) ?></div>
                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:10px;">
                  <span class="badge <?= htmlspecialchars($badgeClass) ?>"><?= htmlspecialchars($student['sport']) ?></span>
                </div>
                <div class="criteria-grid">
                  <div class="crit-box"><div class="crit-label">Vit.</div><div class="crit-val" style="color:#FF8F00;"><?= (int) $student['vitesse'] ?></div></div>
                  <div class="crit-box"><div class="crit-label">Coor.</div><div class="crit-val" style="color:var(--blue);"><?= (int) $student['coordination'] ?></div></div>
                  <div class="crit-box"><div class="crit-label">End.</div><div class="crit-val" style="color:var(--green);"><?= (int) $student['endurance'] ?></div></div>
                  <div class="crit-box"><div class="crit-label">For.</div><div class="crit-val" style="color:var(--purple);"><?= (int) $student['force'] ?></div></div>
                  <div class="crit-box"><div class="crit-label">Sou.</div><div class="crit-val" style="color:var(--cyan);"><?= (int) $student['souplesse'] ?></div></div>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:14px;padding-top:12px;border-top:1px solid var(--border-soft);gap:12px;flex-wrap:wrap;">
                  <span class="text-xs text-muted">Détecté <?= htmlspecialchars(formatDate($student['updated'])) ?></span>
                  <button class="btn btn-sm" type="button" style="color:var(--red);font-weight:700;background:none;border:none;padding:0;font-size:13px;width:auto;min-height:auto;" onclick="openTalentProfile(event, this)">Voir le profil →</button>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="grid-3-1" style="margin-top:24px;">
          <div style="display:flex;flex-direction:column;gap:20px;">
            <div class="card" id="recruiterFavorites">
              <div class="card-header-row mb-16">
                <div>
                  <div class="heading-md">Mes favoris</div>
                  <div class="text-xs text-muted" style="margin-top:4px;">Ajoutez des profils à votre shortlist.</div>
                </div>
                <span class="badge badge-gold" id="favoriteBadge"><?= (int) $favoriteCount ?> sélection<?= $favoriteCount > 1 ? 's' : '' ?></span>
              </div>
              <div id="favoritesList" class="state-list">
                <?php if ($favoriteStudents === []): ?>
                <div class="empty-state">Aucun favori pour le moment.</div>
                <?php else: ?>
                  <?php foreach ($favoriteStudents as $favoriteStudent): ?>
                  <div class="favorite-item">
                    <div class="avatar avatar-sm avatar-blue"><?= htmlspecialchars(initials((string) ($favoriteStudent['name'] ?? ''))) ?></div>
                    <div style="flex:1;">
                      <div class="text-sm" style="font-weight:700;"><?= htmlspecialchars((string) ($favoriteStudent['name'] ?? '')) ?></div>
                      <div class="text-xs text-muted"><?= htmlspecialchars((string) ($favoriteStudent['sport'] ?? '')) ?> · <?= htmlspecialchars((string) ($favoriteStudent['ville'] ?? '')) ?> · score <?= (int) ($favoriteStudent['score'] ?? 0) ?>%</div>
                    </div>
                  </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>

            <div id="recruiterMessages">
              <?php
              $chatPanelId = 'recruiterMessagesPanel';
              $chatPanelTitle = 'Messagerie scouting';
              $chatPanelSubtitle = 'Echanges avec professeurs, coachs, managers et talents relies a vos profils visibles.';
              include __DIR__ . '/../../components/chat_panel.php';
              ?>
            </div>
          </div>

          <div class="card" id="recruiterMap">
            <div class="heading-md mb-16">Carte Maroc</div>
            <div class="text-xs text-muted" style="margin-bottom:14px;">Répartition des talents détectés par ville.</div>
            <div class="city-stack">
              <?php foreach ($cityCounts as $city => $count): ?>
              <div>
                <div style="display:flex;justify-content:space-between;gap:8px;margin-bottom:6px;">
                  <span class="text-sm" style="font-weight:700;"><?= htmlspecialchars($city) ?></span>
                  <span class="text-xs text-muted"><?= (int) $count ?> profil(s)</span>
                </div>
                <div class="progress"><div class="progress-fill" style="width:<?= (int) round(($count / max(count($students), 1)) * 100) ?>%;background:linear-gradient(90deg,var(--red),var(--red-mid));"></div></div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<div class="modal-overlay" id="talentProfileModal">
  <div class="modal-box recruiter-profile-modal">
    <button class="modal-close" type="button" onclick="closeModal('talentProfileModal')">✕</button>
    <div id="talentProfileContent"></div>
  </div>
</div>

<?php include __DIR__ . '/../../components/settings_modal.php'; ?>

<style>
.filter-group-label { font-size:10px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:1.2px; }
.recruiter-filter-card { overflow:hidden; }
.filter-radio {
  display:flex;align-items:center;gap:8px;padding:10px 12px;border-radius:16px;cursor:pointer;font-size:13px;color:var(--ink-60);transition:all .15s;
  background: rgba(255,255,255,.52);
  border: 1px solid rgba(255,255,255,.72);
  box-shadow: inset 0 1px 0 rgba(255,255,255,.7);
}
.filter-radio:hover { background:var(--surface); }
.filter-radio.active { background: linear-gradient(135deg, rgba(200,16,46,.16), rgba(255,240,241,.92)); color:var(--red-dark);font-weight:700;box-shadow: var(--shadow-xs); }
.filter-radio input { accent-color:var(--red); }
.fav-btn { transition: transform .2s ease, color .2s ease; }
.fav-btn:hover { color: var(--gold) !important; }
.fav-btn.active { color: var(--gold) !important; transform: scale(1.08); }
.talent-grid { display:grid;grid-template-columns:repeat(2, minmax(0,1fr));gap:20px;align-items:start; }
.talent-video { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
.state-list { display:flex;flex-direction:column;gap:12px; }
.favorite-item {
  display:flex;
  gap:12px;
  align-items:center;
  padding:12px 0;
  border-bottom:1px solid rgba(10,10,15,.06);
}
.favorite-item:last-child { border-bottom:none; }
.empty-state {
  padding:18px;
  border-radius:18px;
  background:rgba(255,255,255,.56);
  border:1px dashed rgba(10,10,15,.08);
  text-align:center;
  color:var(--muted);
  font-size:13px;
}
.city-stack { display:flex; flex-direction:column; gap:14px; }
.recruiter-profile-modal { width: 640px; max-width: 94vw; }
.profile-hero { display:flex; gap:18px; align-items:center; margin-bottom:22px; }
.profile-video-wrap { margin-top:20px; border-radius:22px; overflow:hidden; background:var(--ink); box-shadow:var(--shadow-sm); }
.profile-video { width:100%; max-height:320px; display:block; background:#000; }
.profile-summary-card { margin-top:18px; padding:16px 18px; border-radius:20px; background:rgba(255,255,255,.72); border:1px solid rgba(10,10,15,.06); }
.profile-kpis { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:12px; margin-top:20px; }
.profile-kpi { padding:14px; border-radius:18px; background:rgba(255,255,255,.72); border:1px solid rgba(10,10,15,.06); }
.profile-kpi strong { display:block; font-size:22px; margin-bottom:4px; }
.profile-kpi span { font-size:12px; color:var(--muted); }
@media (max-width: 1024px) {
  .talent-grid { grid-template-columns:1fr; }
}
@media (max-width: 768px) {
  .profile-kpis { grid-template-columns:1fr; }
}
</style>

<?php include __DIR__ . '/../../components/scripts.php'; ?>
<script>
const recruiterFilters = {
  sport: document.querySelector('input[name="sport"]:checked')?.value || '',
  ville: document.querySelector('input[name="ville"]:checked')?.value || '',
  scoreMin: 70,
};

const recruiterFavoriteApiUrl = <?= json_encode(APP_URL . '/api/index.php') ?>;
const initialFavoriteStudents = <?= json_encode($favoriteStudents, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const recruiterFavorites = new Map(initialFavoriteStudents.map(student => [String(student.id), student]));

function normalizeValue(value) {
  return (value || '')
    .toString()
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '');
}

function selectFilter(element, group) {
  document.querySelectorAll('.filter-radio').forEach(radio => {
    if (radio.querySelector('input')?.name === group) {
      radio.classList.remove('active');
    }
  });

  element.classList.add('active');
  recruiterFilters[group] = element.querySelector('input')?.value || '';
  applyTalentFilters();
}

function updateScoreFilter(value) {
  recruiterFilters.scoreMin = Number(value);
  document.getElementById('scoreMinVal').textContent = value;
  applyTalentFilters();
}

function applyTalentFilters(showFeedback = false) {
  const search = normalizeValue(document.getElementById('talentSearch')?.value || '');
  const sport = normalizeValue(recruiterFilters.sport.replace('Tous les sports', ''));
  const ville = normalizeValue(recruiterFilters.ville.replace('Toutes les villes', ''));
  let visibleCount = 0;

  document.querySelectorAll('.talent-card').forEach(card => {
    const matchesSearch = search === '' || normalizeValue(card.dataset.name).includes(search);
    const matchesSport = sport === '' || normalizeValue(card.dataset.sport) === sport;
    const matchesVille = ville === '' || normalizeValue(card.dataset.ville) === ville;
    const matchesScore = Number(card.dataset.score || 0) >= recruiterFilters.scoreMin;
    const visible = matchesSearch && matchesSport && matchesVille && matchesScore;

    card.style.display = visible ? '' : 'none';
    if (visible) {
      visibleCount += 1;
    }
  });

  document.getElementById('talentCount').textContent = visibleCount;
  if (showFeedback) {
    showToast('Filtres appliqués', 'success');
  }
}

function updateFavoritesView() {
  const list = document.getElementById('favoritesList');
  const count = recruiterFavorites.size;
  document.getElementById('favoriteCounter').textContent = String(count);
  document.getElementById('favoriteBadge').textContent = `${count} sélection${count > 1 ? 's' : ''}`;

  if (!list) {
    return;
  }

  if (count === 0) {
    list.innerHTML = '<div class="empty-state">Aucun favori pour le moment.</div>';
    return;
  }

  list.innerHTML = Array.from(recruiterFavorites.values()).map(student => `
    <div class="favorite-item">
      <div class="avatar avatar-sm avatar-blue">${escapeHtml((student.name || '').split(' ').map(part => part[0] || '').slice(0, 2).join('').toUpperCase())}</div>
      <div style="flex:1;">
        <div class="text-sm" style="font-weight:700;">${escapeHtml(student.name)}</div>
        <div class="text-xs text-muted">${escapeHtml(student.sport)} · ${escapeHtml(student.ville)} · score ${escapeHtml(student.score)}%</div>
      </div>
    </div>
  `).join('');
}

function syncFavoriteButtons() {
  document.querySelectorAll('.talent-card').forEach(card => {
    const button = card.querySelector('.fav-btn');
    const student = JSON.parse(card.dataset.student || '{}');
    const isActive = recruiterFavorites.has(String(student.id || ''));

    if (!button) {
      return;
    }

    button.classList.toggle('active', isActive);
    button.innerHTML = isActive ? '&#9733;' : '&#9734;';
  });
}

function applyFavoriteState(students) {
  recruiterFavorites.clear();

  (students || []).forEach(student => {
    if (student && student.id) {
      recruiterFavorites.set(String(student.id), student);
    }
  });

  updateFavoritesView();
  syncRecruiterSidebarFavoriteBadge();
  syncFavoriteButtons();
}

function syncRecruiterSidebarFavoriteBadge() {
  const badge = document.querySelector('.sidebar-item[data-target="recruiterFavorites"] .sidebar-badge');
  if (badge) {
    badge.textContent = String(recruiterFavorites.size);
  }
}

async function toggleFav(event, button) {
  event.preventDefault();
  event.stopPropagation();

  const card = button.closest('.talent-card');
  if (!card) {
    return;
  }

  const student = JSON.parse(card.dataset.student || '{}');
  const nextState = !button.classList.contains('active');

  if (!student.id) {
    showToast('Profil indisponible pour ce favori.', 'error');
    return;
  }

  button.disabled = true;

  try {
    const formData = new FormData();
    formData.append('action', 'favorite_toggle');
    formData.append('_token', atlasCsrfToken);
    formData.append('student_id', String(student.id));
    formData.append('favorite', nextState ? '1' : '0');

    const response = await fetch(`${recruiterFavoriteApiUrl}?action=favorite_toggle`, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
    });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok || !payload.success) {
      throw new Error(payload.error || 'Enregistrement du favori impossible.');
    }

    applyFavoriteState(payload.data?.favorites?.students || []);
    showToast(nextState ? 'Ajouté aux favoris' : 'Retiré des favoris', nextState ? 'success' : 'default');
  } catch (error) {
    showToast(error.message || 'Enregistrement du favori impossible.', 'error');
  } finally {
    button.disabled = false;
  }
}

function openTalentProfile(event, button) {
  event.preventDefault();
  event.stopPropagation();

  const card = button.closest('.talent-card');
  const student = JSON.parse(card?.dataset.student || '{}');
  const content = document.getElementById('talentProfileContent');

  if (!content || !student.name) {
    return;
  }

  content.innerHTML = `
    <div class="profile-hero">
      <div class="avatar avatar-xl avatar-blue">${escapeHtml((student.name || '').split(' ').map(part => part[0] || '').slice(0, 2).join('').toUpperCase())}</div>
      <div>
        <h2 class="heading-xl" style="margin-bottom:6px;">${escapeHtml(student.name)}</h2>
        <p class="text-sm text-muted">${escapeHtml(student.sport)} · ${escapeHtml(student.ville)} · ${escapeHtml(student.age)} ans</p>
        <span class="badge badge-blue" style="margin-top:10px;">Score global ${escapeHtml(student.score)}%</span>
      </div>
    </div>
    ${student.video_url ? `
      <div class="profile-video-wrap">
        <video class="profile-video" src="${escapeHtml(student.video_url)}" controls preload="metadata" playsinline></video>
      </div>
    ` : `
      <div class="profile-summary-card">
        <div class="text-sm text-muted">Aucune video analysee disponible pour ce profil.</div>
      </div>
    `}
    <div class="profile-summary-card">
      <div class="heading-md" style="margin-bottom:8px;">Lecture IA</div>
      <p class="text-sm text-muted">${escapeHtml(student.ai_summary || 'Resume IA indisponible pour le moment.')}</p>
    </div>
    <div class="profile-kpis">
      <div class="profile-kpi"><strong style="color:#FF8F00;">${escapeHtml(student.vitesse)}</strong><span>Vitesse</span></div>
      <div class="profile-kpi"><strong style="color:var(--blue);">${escapeHtml(student.coordination)}</strong><span>Coordination</span></div>
      <div class="profile-kpi"><strong style="color:var(--green);">${escapeHtml(student.endurance)}</strong><span>Endurance</span></div>
      <div class="profile-kpi"><strong style="color:var(--purple);">${escapeHtml(student.force)}</strong><span>Force</span></div>
      <div class="profile-kpi"><strong style="color:var(--cyan);">${escapeHtml(student.souplesse)}</strong><span>Souplesse</span></div>
      <div class="profile-kpi"><strong>${escapeHtml(student.updated || '')}</strong><span>Dernière analyse</span></div>
    </div>
  `;

  openModal('talentProfileModal');
}

function openTalentProfileFromCard(card) {
  const button = card?.querySelector('button[onclick*="openTalentProfile"]');
  if (!button) {
    return;
  }

  openTalentProfile({ preventDefault() {}, stopPropagation() {} }, button);
}

function handleTalentCardKey(event, card) {
  if (event.key === 'Enter' || event.key === ' ') {
    event.preventDefault();
    openTalentProfileFromCard(card);
  }
}

function exportRecruiterPdf() {
  const visibleCards = Array.from(document.querySelectorAll('.talent-card')).filter(card => card.style.display !== 'none');
  const rows = visibleCards.map(card => JSON.parse(card.dataset.student || '{}'));

  if (!rows.length) {
    showToast('Aucun talent visible a exporter avec les filtres actuels.', 'warning');
    return;
  }

  const tableRows = rows.map(student => `
    <tr>
      <td>${escapeHtml(student.name)}</td>
      <td>${escapeHtml(student.sport)}</td>
      <td>${escapeHtml(student.ville)}</td>
      <td>${escapeHtml(student.score)}%</td>
    </tr>
  `).join('');

  exportDashboardPdf(
    'Rapport Recruteur',
    <?= json_encode($clubName . ' · ' . formatLongDate('now', true), JSON_UNESCAPED_UNICODE) ?>,
    [
      {
        heading: 'Synthèse',
        content: `
          <div class="metric-grid">
            <div class="metric-card"><strong>${rows.length}</strong><span>Profils visibles</span></div>
            <div class="metric-card"><strong>${recruiterFavorites.size}</strong><span>Favoris sélectionnés</span></div>
            <div class="metric-card"><strong><?= count($cityCounts) ?></strong><span>Villes couvertes</span></div>
            <div class="metric-card"><strong><?= (int) $avgScore ?>%</strong><span>Score moyen</span></div>
          </div>
        `
      },
      {
        heading: 'Talents exportés',
        content: `<table><thead><tr><th>Nom</th><th>Sport</th><th>Ville</th><th>Score</th></tr></thead><tbody>${tableRows}</tbody></table>`
      }
    ]
  );
}

function sortTalentCards(mode) {
  const grid = document.querySelector('.talent-grid');
  if (!grid) {
    return;
  }

  const cards = Array.from(grid.querySelectorAll('.talent-card'));
  const sorted = cards.sort((left, right) => {
    if (mode === 'Meilleur score') {
      return Number(right.dataset.score || 0) - Number(left.dataset.score || 0);
    }

    if (mode === 'Par ville') {
      return normalizeValue(left.dataset.ville).localeCompare(normalizeValue(right.dataset.ville));
    }

    const leftStudent = JSON.parse(left.dataset.student || '{}');
    const rightStudent = JSON.parse(right.dataset.student || '{}');
    return String(rightStudent.updated || '').localeCompare(String(leftStudent.updated || ''));
  });

  sorted.forEach(card => grid.appendChild(card));
}

document.querySelectorAll('.talent-card').forEach(card => {
  const video = card.querySelector('.talent-video');
  if (video) {
    video.muted = true;
    video.play().catch(() => {});
  }
  card.setAttribute('tabindex', '0');
  card.setAttribute('role', 'button');
  card.addEventListener('click', event => {
    if (event.target.closest('button')) {
      return;
    }

    openTalentProfileFromCard(card);
  });
  card.addEventListener('keydown', event => handleTalentCardKey(event, card));
});

applyFavoriteState(initialFavoriteStudents);
sortTalentCards(document.getElementById('recruiterSortSelect')?.value || 'Plus rÃ©cents');
applyTalentFilters();
</script>
