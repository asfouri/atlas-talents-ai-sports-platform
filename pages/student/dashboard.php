<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

Auth::check(ROLE_STUDENT);

$user = Auth::user();
$student = atlasGetLinkedStudentForUser($user);
$progress = atlasGetCoachProgress($student['id'] ?? null);
$recentVideos = atlasGetRecentVideosForStudent((int) ($student['id'] ?? 0), 4);
$strengths = $student['strengths_list'] ?? [];
$improvements = $student['improvements_list'] ?? [];
$recommendations = $student['recommendations_list'] ?? [];
$topStrength = $strengths[0] ?? 'Profil en cours de consolidation';
$nextFocus = $improvements[0] ?? 'Ajouter une nouvelle video pour affiner le suivi';

$pageTitle = 'Dashboard Eleve';
$navType = 'student';
$bodyClass = 'dash-body';
include __DIR__ . '/../../components/head.php';
include __DIR__ . '/../../components/navbar.php';
?>

<div class="dash-layout">
  <aside class="sidebar">
    <div class="sidebar-user">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
        <div class="avatar avatar-md avatar-gold"><?= htmlspecialchars($user['avatar']) ?></div>
        <div>
          <div class="sidebar-user-name"><?= htmlspecialchars($user['name']) ?></div>
          <div class="sidebar-user-role"><?= htmlspecialchars($student['school'] ?? 'Espace eleve') ?></div>
        </div>
      </div>
      <div style="background:var(--gold-light);border-radius:var(--radius-sm);padding:8px 12px;display:flex;align-items:center;gap:6px;">
        <span style="font-size:11px;color:var(--gold);">●</span>
        <span style="font-size:12px;color:#8a6a00;font-weight:700;">Suivi personnel</span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="sidebar-item active" data-target="studentOverview"><span class="icon">⌂</span> Mon tableau de bord</div>
      <div class="sidebar-item" data-target="studentProfile"><span class="icon">◉</span> Mon profil</div>
      <div class="sidebar-item" data-target="studentProgress"><span class="icon">↗</span> Ma progression</div>
      <div class="sidebar-item" data-target="studentPlan"><span class="icon">◎</span> Mes objectifs</div>
      <div class="sidebar-item" data-target="studentActivity"><span class="icon">▣</span> Mes analyses</div>
      <div class="sidebar-item" data-target="studentMessages"><span class="icon">✉</span> Messages</div>
      <div class="sidebar-label" style="margin-top:16px;">Compte</div>
      <div class="sidebar-item" data-modal="settingsModal"><span class="icon">⚙</span> Parametres</div>
      <div class="sidebar-item" data-href="<?= APP_URL ?>/pages/auth/logout.php"><span class="icon">↪</span> Deconnexion</div>
    </nav>
  </aside>

  <main class="dash-main">
    <?php if (!$student): ?>
    <div class="card">
      <h1 class="heading-xl" style="margin-bottom:12px;">Profil non relie</h1>
      <p class="text-sm text-muted">Ce compte eleve n est pas encore relie a une fiche talent. Demandez a votre professeur ou manager de finaliser le rattachement.</p>
    </div>
    <?php else: ?>
    <div class="dash-header" id="studentOverview">
      <div class="dash-header-top">
        <div>
          <h1 class="heading-xl">Bonjour, <?= htmlspecialchars(explode(' ', $student['name'])[0] ?? $student['name']) ?></h1>
          <p class="text-sm text-muted" style="margin-top:4px;"><?= htmlspecialchars(($student['sport'] ?? 'Sport') . ' · ' . ($student['ville'] ?? 'Maroc')) ?> · derniere mise a jour <?= htmlspecialchars(formatLongDate((string) ($student['updated'] ?? 'now'))) ?></p>
        </div>
        <span class="badge <?= htmlspecialchars(scoreBadgeClass((int) ($student['score'] ?? 0))) ?>">Score global <?= (int) ($student['score'] ?? 0) ?>%</span>
      </div>
    </div>

    <div class="grid-4 mb-24">
      <div class="stat-card"><div class="stat-icon" style="background:var(--gold-light);color:#8a6a00;">★</div><div><div class="stat-value"><?= (int) ($student['score'] ?? 0) ?>%</div><div class="stat-label">Score global</div><div class="stat-trend trend-up"><?= htmlspecialchars($student['perf_type'] ?? 'Performance') ?></div></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:var(--blue-light);color:var(--blue);">IA</div><div><div class="stat-value"><?= (int) ($student['ai_confidence'] ?? 0) ?>%</div><div class="stat-label">Confiance IA</div><div class="stat-trend trend-flat"><?= htmlspecialchars(strtoupper((string) ($student['analysis_provider'] ?? 'demo'))) ?></div></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:var(--green-light);color:var(--green);">✓</div><div><div class="stat-value"><?= (int) ($student['video_count'] ?? 0) ?></div><div class="stat-label">Analyses</div><div class="stat-trend trend-up">Historique personnel</div></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:rgba(255,143,0,.12);color:#FF8F00;">↗</div><div><div class="stat-value" style="font-size:20px;"><?= htmlspecialchars($topStrength) ?></div><div class="stat-label">Point fort du moment</div><div class="stat-trend trend-up">A conserver</div></div></div>
    </div>

    <div class="grid-2-1 mb-24" style="align-items:start;">
      <div style="display:flex;flex-direction:column;gap:20px;">
        <div class="card" id="studentProfile">
          <div class="student-hero">
            <div class="avatar avatar-xl avatar-gold"><?= htmlspecialchars(initials($student['name'])) ?></div>
            <div>
              <h2 class="heading-lg" style="margin-bottom:8px;"><?= htmlspecialchars($student['name']) ?></h2>
              <div class="text-sm text-muted"><?= (int) ($student['age'] ?? 0) ?> ans · <?= htmlspecialchars($student['sport'] ?? 'Sport') ?> · <?= htmlspecialchars($student['ville'] ?? 'Maroc') ?></div>
              <div class="text-sm text-muted" style="margin-top:4px;"><?= htmlspecialchars($student['school'] ?? 'Etablissement non renseigne') ?></div>
            </div>
          </div>

          <div class="student-metrics">
            <?php foreach (CRITERIA as $criterion): ?>
            <div class="student-metric">
              <span><?= htmlspecialchars(CRITERIA_LABELS[$criterion]) ?></span>
              <strong style="color:<?= htmlspecialchars(CRITERIA_COLORS[$criterion]) ?>;"><?= (int) ($student[$criterion] ?? 0) ?></strong>
            </div>
            <?php endforeach; ?>
          </div>

          <div class="student-summary">
            <div class="heading-md" style="margin-bottom:8px;">Resume IA</div>
            <p class="text-sm text-muted"><?= htmlspecialchars($student['ai_summary'] ?? 'Ajoutez des videos supplementaires pour enrichir votre lecture IA.') ?></p>
          </div>
        </div>

        <div class="card" id="studentProgress">
          <div class="card-header-row mb-16">
            <div>
              <div class="heading-md">Evolution de mes performances</div>
              <div class="text-xs text-muted" style="margin-top:4px;">Lecture de tendance a partir des analyses disponibles</div>
            </div>
          </div>
          <div style="position:relative;height:280px;"><canvas id="studentProgressChart"></canvas></div>
        </div>

        <div class="card" id="studentActivity">
          <div class="heading-md mb-16">Mes analyses recentes</div>
          <div class="student-activity-list">
            <?php foreach ($recentVideos as $video): ?>
            <div class="student-activity-item">
              <div>
                <div class="text-sm" style="font-weight:700;"><?= htmlspecialchars($video['perf_type'] ?? 'Performance') ?></div>
                <div class="text-xs text-muted"><?= htmlspecialchars(formatLongDate((string) ($video['analyzed_at'] ?? $video['created_at'] ?? 'now'))) ?></div>
              </div>
              <div style="text-align:right;">
                <div style="font-weight:800;color:<?= htmlspecialchars(scoreColor((int) ($video['score_global'] ?? 0))) ?>;"><?= (int) ($video['score_global'] ?? 0) ?>%</div>
                <div class="text-xs text-muted"><?= htmlspecialchars(strtoupper((string) ($video['analysis_provider'] ?? 'demo'))) ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:20px;">
        <div class="card">
          <div class="heading-md mb-16">Mes points forts</div>
          <ul class="student-list">
            <?php foreach ($strengths as $item): ?>
            <li><?= htmlspecialchars($item) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div class="card" id="studentPlan">
          <div class="heading-md mb-16">Mes axes de progression</div>
          <ul class="student-list">
            <?php foreach ($improvements as $item): ?>
            <li><?= htmlspecialchars($item) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div class="card">
          <div class="heading-md mb-16">Plan d action recommande</div>
          <div class="student-focus-card">
            <strong>Priorite immediate</strong>
            <span><?= htmlspecialchars($nextFocus) ?></span>
          </div>
          <ul class="student-list" style="margin-top:14px;">
            <?php foreach ($recommendations as $item): ?>
            <li><?= htmlspecialchars($item) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>

    <div id="studentMessages" style="margin-top:24px;">
      <?php
      $chatPanelId = 'studentMessagesPanel';
      $chatPanelTitle = 'Messagerie accompagnement';
      $chatPanelSubtitle = 'Discutez avec votre professeur, votre coach et la cellule recrutement quand votre profil avance.';
      include __DIR__ . '/../../components/chat_panel.php';
      ?>
    </div>
    <?php endif; ?>
  </main>
</div>

<?php include __DIR__ . '/../../components/settings_modal.php'; ?>

<style>
.card-header-row { display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap; }
.student-hero { display:flex;align-items:center;gap:16px;margin-bottom:22px; }
.student-metrics { display:grid;grid-template-columns:repeat(5, minmax(0, 1fr));gap:12px; }
.student-metric { padding:14px;border-radius:18px;background:rgba(255,255,255,.72);border:1px solid rgba(10,10,15,.06);display:flex;flex-direction:column;gap:6px; }
.student-metric span { font-size:12px;color:var(--muted); }
.student-metric strong { font-size:24px;font-weight:800; }
.student-summary { margin-top:18px;padding:18px;border-radius:22px;background:linear-gradient(180deg, rgba(255,248,225,.76), rgba(255,255,255,.9));border:1px solid rgba(212,175,55,.18); }
.student-list { list-style:none;display:flex;flex-direction:column;gap:10px; }
.student-list li { padding:12px 14px;border-radius:16px;background:rgba(255,255,255,.66);border:1px solid rgba(10,10,15,.06);font-size:13px;color:var(--ink-80); }
.student-activity-list { display:flex;flex-direction:column;gap:12px; }
.student-activity-item { display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;border-radius:18px;background:rgba(255,255,255,.68);border:1px solid rgba(10,10,15,.06); }
.student-focus-card { display:flex;flex-direction:column;gap:6px;padding:16px 18px;border-radius:20px;background:var(--gold-light);border:1px solid rgba(212,175,55,.22); }
.student-focus-card strong { font-size:14px;color:#8a6a00; }
.student-focus-card span { font-size:13px;color:var(--ink-80); }
@media (max-width: 980px) {
  .student-metrics { grid-template-columns:repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 640px) {
  .student-hero { flex-direction:column;align-items:flex-start; }
  .student-metrics { grid-template-columns:1fr; }
}
</style>

<?php include __DIR__ . '/../../components/scripts.php'; ?>
<?php if ($student): ?>
<script>
const studentProgressData = <?= json_encode($progress, JSON_UNESCAPED_UNICODE) ?>;
const hasStudentProgressData = Array.isArray(studentProgressData.labels) && studentProgressData.labels.length > 0;

if (!hasStudentProgressData) {
  renderChartFallback('studentProgressChart', 'Pas encore assez d’analyses pour afficher une progression fiable.');
} else if (typeof Chart === 'undefined') {
  renderChartFallback('studentProgressChart', 'Le graphique de progression est indisponible hors ligne.');
} else {
  new Chart(document.getElementById('studentProgressChart'), {
    type: 'line',
    data: {
      labels: studentProgressData.labels,
      datasets: [
        { label: 'Score global', data: studentProgressData.global, borderColor: '#D4AF37', backgroundColor: 'rgba(212,175,55,.12)', tension: .32, fill: true },
        { label: 'Vitesse', data: studentProgressData.vitesse, borderColor: '#FF8F00', tension: .32 },
        { label: 'Coordination', data: studentProgressData.coordination, borderColor: '#1565C0', tension: .32 },
        { label: 'Endurance', data: studentProgressData.endurance, borderColor: '#2E7D32', tension: .32 }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: true, position: 'bottom' } },
      scales: { y: { suggestedMin: 50, suggestedMax: 100 } }
    }
  });
}
</script>
<?php endif; ?>
