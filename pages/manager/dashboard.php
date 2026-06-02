<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

Auth::check(ROLE_MANAGER);

$user = Auth::user();
$clubName = userSubtitle($user);
$students = atlasGetRecruiterStudents();
usort($students, static function (array $left, array $right): int {
    $scoreOrder = (int) ($right['score'] ?? 0) <=> (int) ($left['score'] ?? 0);

    if ($scoreOrder !== 0) {
        return $scoreOrder;
    }

    return strcmp((string) ($right['updated'] ?? ''), (string) ($left['updated'] ?? ''));
});

$avgScore = atlasAverageScore($students);
$highPotential = count(atlasTopTalents($students));
$priorityTalents = array_slice($students, 0, 6);
$cityCounts = [];
$partnerIds = [];
$coachIdsByStudent = [];

foreach (atlasGetCoachAssignments() as $assignment) {
    $studentId = (int) ($assignment['student_id'] ?? 0);
    $coachId = (int) ($assignment['coach_id'] ?? 0);

    if ($studentId > 0 && $coachId > 0) {
        $coachIdsByStudent[$studentId][] = $coachId;
    }
}

foreach ($students as $student) {
    $city = $student['ville'] ?? 'Non renseignee';
    $cityCounts[$city] = ($cityCounts[$city] ?? 0) + 1;
    $partnerIds[(int) ($student['teacher_id'] ?? 0)] = true;

    foreach ($coachIdsByStudent[(int) ($student['id'] ?? 0)] ?? [] as $coachId) {
        $partnerIds[(int) $coachId] = true;
    }
}

arsort($cityCounts);

$partnerUsers = [];
foreach (atlasGetPlatformUsers() as $platformUser) {
    $platformUserId = (int) ($platformUser['id'] ?? 0);

    if ($platformUserId > 0 && isset($partnerIds[$platformUserId])) {
        $partnerUsers[] = $platformUser;
    }
}

usort($partnerUsers, static function (array $left, array $right): int {
    return strcmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
});

$teacherPartners = array_values(array_filter($partnerUsers, static fn(array $entry): bool => ($entry['role'] ?? '') === ROLE_TEACHER));
$coachPartners = array_values(array_filter($partnerUsers, static fn(array $entry): bool => ($entry['role'] ?? '') === ROLE_COACH));

$pageTitle = 'Dashboard Manager recrutement';
$navType = 'manager';
$bodyClass = 'dash-body';
include __DIR__ . '/../../components/head.php';
include __DIR__ . '/../../components/navbar.php';
?>

<div class="dash-layout">
  <aside class="sidebar">
    <div class="sidebar-user">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
        <div class="avatar avatar-md avatar-purple"><?= htmlspecialchars($user['avatar']) ?></div>
        <div>
          <div class="sidebar-user-name"><?= htmlspecialchars($user['name']) ?></div>
          <div class="sidebar-user-role"><?= htmlspecialchars($clubName) ?></div>
        </div>
      </div>
      <div style="background:var(--purple-light);border-radius:var(--radius-sm);padding:8px 12px;display:flex;align-items:center;gap:6px;">
        <span style="font-size:11px;color:var(--purple);">●</span>
        <span style="font-size:12px;color:var(--purple);font-weight:700;">Recrutement et coordination</span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="sidebar-item active" data-target="managerOverview"><span class="icon">⌂</span> Tableau de bord</div>
      <div class="sidebar-item" data-target="managerPipeline"><span class="icon">★</span> Shortlist</div>
      <div class="sidebar-item" data-target="managerPartners"><span class="icon">◌</span> Partenaires</div>
      <div class="sidebar-item" data-target="managerCoverage"><span class="icon">◍</span> Couverture</div>
      <div class="sidebar-item" data-target="managerMessages"><span class="icon">✉</span> Messages</div>
      <div class="sidebar-label" style="margin-top:16px;">Actions</div>
      <div class="sidebar-item" data-action="exportManagerPdf"><span class="icon">⇩</span> Export recrutement</div>
      <div class="sidebar-label" style="margin-top:16px;">Compte</div>
      <div class="sidebar-item" data-modal="settingsModal"><span class="icon">⚙</span> Parametres</div>
      <div class="sidebar-item" data-href="<?= APP_URL ?>/pages/auth/logout.php"><span class="icon">↪</span> Deconnexion</div>
    </nav>
  </aside>

  <main class="dash-main">
    <div class="dash-header" id="managerOverview">
      <div class="dash-header-top">
        <div>
          <h1 class="heading-xl">Pilotage recrutement</h1>
          <p class="text-sm text-muted" style="margin-top:4px;"><?= htmlspecialchars($clubName) ?> · <?= htmlspecialchars(formatLongDate('now', true)) ?></p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
          <button class="btn btn-outline" type="button" onclick="openSmartChatPanel('managerMessagesPanel')">Ouvrir la messagerie</button>
          <button class="btn btn-primary" type="button" onclick="exportManagerPdf()">Exporter</button>
        </div>
      </div>
    </div>

    <div class="grid-4 mb-24">
      <div class="stat-card"><div class="stat-icon" style="background:var(--purple-light);color:var(--purple);">★</div><div><div class="stat-value"><?= count($students) ?></div><div class="stat-label">Talents visibles</div><div class="stat-trend trend-up">Pipeline actif</div></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:var(--green-light);color:var(--green);">✓</div><div><div class="stat-value"><?= (int) $highPotential ?></div><div class="stat-label">Priorites recrutement</div><div class="stat-trend trend-up">Score >= 85</div></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:var(--blue-light);color:var(--blue);">IA</div><div><div class="stat-value"><?= (int) $avgScore ?>%</div><div class="stat-label">Score moyen</div><div class="stat-trend trend-flat">Base prospecte</div></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:var(--gold-light);color:#8a6a00;">◌</div><div><div class="stat-value"><?= count($partnerUsers) ?></div><div class="stat-label">Interlocuteurs terrain</div><div class="stat-trend trend-flat"><?= count($teacherPartners) ?> profs · <?= count($coachPartners) ?> coachs</div></div></div>
    </div>

    <div class="grid-2-1 mb-24" style="align-items:start;">
      <div class="card" id="managerPipeline">
        <div class="card-header-row mb-16">
          <div>
            <div class="heading-md">Shortlist manager</div>
            <div class="text-xs text-muted" style="margin-top:4px;">Profils a traiter rapidement avec le staff terrain.</div>
          </div>
          <span class="badge badge-purple"><?= count($priorityTalents) ?> profils</span>
        </div>

        <div class="manager-pipeline">
          <?php foreach ($priorityTalents as $index => $student): ?>
          <?php
          $priorityBadge = (int) ($student['score'] ?? 0) >= 90 ? 'Priorite haute' : ((int) ($student['score'] ?? 0) >= 85 ? 'Priorite active' : 'A suivre');
          $priorityClass = (int) ($student['score'] ?? 0) >= 90 ? 'badge-success' : 'badge-warning';
          ?>
          <div class="manager-pipeline__item">
            <div class="manager-pipeline__rank"><?= $index + 1 ?></div>
            <div style="flex:1;min-width:0;">
              <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
                <div>
                  <div class="text-sm" style="font-weight:800;"><?= htmlspecialchars($student['name']) ?></div>
                  <div class="text-xs text-muted"><?= htmlspecialchars(($student['sport'] ?? 'Sport') . ' · ' . ($student['ville'] ?? 'Maroc') . ' · ' . ((int) ($student['age'] ?? 0)) . ' ans') ?></div>
                </div>
                <span class="badge <?= htmlspecialchars($priorityClass) ?>"><?= htmlspecialchars($priorityBadge) ?></span>
              </div>
              <div class="manager-pipeline__meta">
                <span>Score <?= (int) ($student['score'] ?? 0) ?>%</span>
                <span><?= htmlspecialchars($student['perf_type'] ?? 'Performance') ?></span>
                <span>Mise a jour <?= htmlspecialchars(formatDate((string) ($student['updated'] ?? 'now'))) ?></span>
              </div>
              <div class="progress" style="margin-top:12px;">
                <div class="progress-fill" style="width:<?= (int) ($student['score'] ?? 0) ?>%;background:linear-gradient(90deg,var(--purple),#7E57C2);"></div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:20px;">
        <div class="card" id="managerCoverage">
          <div class="heading-md mb-16">Couverture geographique</div>
          <div class="manager-city-stack">
            <?php foreach ($cityCounts as $city => $count): ?>
            <div>
              <div style="display:flex;justify-content:space-between;gap:12px;margin-bottom:6px;">
                <span class="text-sm" style="font-weight:700;"><?= htmlspecialchars($city) ?></span>
                <span class="text-sm text-muted"><?= (int) $count ?> profil(s)</span>
              </div>
              <div class="progress"><div class="progress-fill" style="width:<?= (int) round(($count / max(count($students), 1)) * 100) ?>%;background:linear-gradient(90deg,var(--purple),#7E57C2);"></div></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="card">
          <div class="heading-md mb-16">Lecture rapide</div>
          <div style="display:flex;flex-direction:column;gap:10px;">
            <div class="manager-note manager-note--good">
              <strong>Talents mobilisables</strong>
              <span><?= (int) $highPotential ?> profils peuvent deja entrer dans un circuit de prise de contact.</span>
            </div>
            <div class="manager-note manager-note--neutral">
              <strong>Zone la plus dense</strong>
              <span><?= htmlspecialchars((string) (array_key_first($cityCounts) ?? 'Maroc')) ?> concentre actuellement le plus de profils visibles.</span>
            </div>
            <div class="manager-note manager-note--alert">
              <strong>Action conseillee</strong>
              <span>Utilisez la messagerie pour synchroniser professeurs, coachs et talents avant toute evaluation terrain.</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-24" id="managerPartners">
      <div class="card-header-row mb-16">
        <div>
          <div class="heading-md">Reseau terrain</div>
          <div class="text-xs text-muted" style="margin-top:4px;">Professeurs et coachs relies aux profils que vous suivez.</div>
        </div>
        <span class="badge badge-blue"><?= count($partnerUsers) ?> contacts</span>
      </div>

      <div class="manager-partners">
        <?php foreach ($partnerUsers as $partner): ?>
        <div class="manager-partner">
          <div class="avatar avatar-md <?= htmlspecialchars(atlasRoleAvatarClass((string) ($partner['role'] ?? ''))) ?>"><?= htmlspecialchars(initials($partner['name'])) ?></div>
          <div style="min-width:0;flex:1;">
            <div class="text-sm" style="font-weight:800;"><?= htmlspecialchars($partner['name']) ?></div>
            <div class="text-xs text-muted"><?= htmlspecialchars(atlasRoleLabel((string) ($partner['role'] ?? ''))) ?> · <?= htmlspecialchars(userSubtitle($partner)) ?></div>
          </div>
          <div class="text-xs text-muted"><?= htmlspecialchars($partner['ville'] ?: 'Maroc') ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div id="managerMessages">
      <?php
      $chatPanelId = 'managerMessagesPanel';
      $chatPanelTitle = 'Messagerie recrutement';
      $chatPanelSubtitle = 'Discutez avec les professeurs, coachs, recruteurs et talents relies a votre pipeline.';
      include __DIR__ . '/../../components/chat_panel.php';
      ?>
    </div>
  </main>
</div>

<?php include __DIR__ . '/../../components/settings_modal.php'; ?>

<style>
.card-header-row { display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap; }
.manager-pipeline { display:flex;flex-direction:column;gap:14px; }
.manager-pipeline__item {
  display:flex;
  gap:14px;
  align-items:flex-start;
  padding:16px 18px;
  border-radius:22px;
  background:rgba(255,255,255,.68);
  border:1px solid rgba(10,10,15,.06);
}
.manager-pipeline__rank {
  width:42px;
  height:42px;
  border-radius:14px;
  display:flex;
  align-items:center;
  justify-content:center;
  font-weight:800;
  color:var(--purple);
  background:var(--purple-light);
  flex-shrink:0;
}
.manager-pipeline__meta {
  display:flex;
  flex-wrap:wrap;
  gap:10px;
  margin-top:10px;
  font-size:11px;
  color:var(--muted);
  text-transform:uppercase;
  letter-spacing:.05em;
}
.manager-city-stack,
.manager-partners {
  display:flex;
  flex-direction:column;
  gap:14px;
}
.manager-partner {
  display:flex;
  align-items:center;
  gap:12px;
  padding:14px 16px;
  border-radius:18px;
  background:rgba(255,255,255,.62);
  border:1px solid rgba(10,10,15,.06);
}
.manager-note {
  display:flex;
  flex-direction:column;
  gap:4px;
  padding:14px 16px;
  border-radius:18px;
  border:1px solid rgba(10,10,15,.06);
  background:rgba(255,255,255,.62);
}
.manager-note strong { font-size:13px; }
.manager-note span { font-size:12px; color:var(--ink-80); line-height:1.55; }
.manager-note--good { background:rgba(27,110,58,.08); border-color:rgba(27,110,58,.14); }
.manager-note--neutral { background:rgba(12,74,143,.08); border-color:rgba(12,74,143,.12); }
.manager-note--alert { background:rgba(255,143,0,.1); border-color:rgba(255,143,0,.12); }
</style>

<?php include __DIR__ . '/../../components/scripts.php'; ?>
<script>
function exportManagerPdf() {
  const shortlistRows = <?= json_encode(array_map(static function (array $student): array {
      return [
          'name' => (string) ($student['name'] ?? ''),
          'sport' => (string) ($student['sport'] ?? ''),
          'ville' => (string) ($student['ville'] ?? ''),
          'score' => (int) ($student['score'] ?? 0),
      ];
  }, $priorityTalents), JSON_UNESCAPED_UNICODE) ?>;

  const partnerRows = <?= json_encode(array_map(static function (array $partner): array {
      return [
          'name' => (string) ($partner['name'] ?? ''),
          'role' => atlasRoleLabel((string) ($partner['role'] ?? '')),
          'ville' => (string) ($partner['ville'] ?? ''),
      ];
  }, $partnerUsers), JSON_UNESCAPED_UNICODE) ?>;

  const shortlistTable = shortlistRows.map(row => `
    <tr>
      <td>${escapeHtml(row.name)}</td>
      <td>${escapeHtml(row.sport)}</td>
      <td>${escapeHtml(row.ville)}</td>
      <td>${escapeHtml(row.score)}%</td>
    </tr>
  `).join('');

  const partnerTable = partnerRows.map(row => `
    <tr>
      <td>${escapeHtml(row.name)}</td>
      <td>${escapeHtml(row.role)}</td>
      <td>${escapeHtml(row.ville)}</td>
    </tr>
  `).join('');

  exportDashboardPdf(
    'Rapport Manager recrutement',
    <?= json_encode($clubName . ' · ' . formatLongDate('now', true), JSON_UNESCAPED_UNICODE) ?>,
    [
      {
        heading: 'Synthese',
        content: `
          <div class="metric-grid">
            <div class="metric-card"><strong><?= count($students) ?></strong><span>Talents visibles</span></div>
            <div class="metric-card"><strong><?= (int) $highPotential ?></strong><span>Priorites recrutement</span></div>
            <div class="metric-card"><strong><?= (int) $avgScore ?>%</strong><span>Score moyen</span></div>
            <div class="metric-card"><strong><?= count($partnerUsers) ?></strong><span>Interlocuteurs terrain</span></div>
          </div>
        `
      },
      {
        heading: 'Shortlist',
        content: `<table><thead><tr><th>Nom</th><th>Sport</th><th>Ville</th><th>Score</th></tr></thead><tbody>${shortlistTable}</tbody></table>`
      },
      {
        heading: 'Partenaires terrain',
        content: `<table><thead><tr><th>Nom</th><th>Role</th><th>Ville</th></tr></thead><tbody>${partnerTable}</tbody></table>`
      }
    ]
  );
}
</script>
