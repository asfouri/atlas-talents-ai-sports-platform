<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

Auth::check(ROLE_COACH);

$user = Auth::user();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'add_coach_student') {
    $redirectUrl = APP_URL . '/pages/coach/dashboard.php';

    if (!Auth::verifyCsrf($_POST['_token'] ?? '')) {
        header('Location: ' . $redirectUrl . '?error=' . urlencode('Jeton CSRF invalide.') . '#coachAthletes');
        exit;
    }

    $result = atlasAssignStudentToCoach((int) $user['id'], (int) ($_POST['student_id'] ?? 0));
    $flashKey = !empty($result['success']) ? 'msg' : 'error';

    header('Location: ' . $redirectUrl . '?' . $flashKey . '=' . urlencode((string) ($result['message'] ?? 'Operation terminee.')) . '#coachAthletes');
    exit;
}

$students = atlasGetCoachStudents((int) $user['id']);
usort($students, fn(array $a, array $b): int => ($b['score'] <=> $a['score']));
$assignedStudentIds = array_values(array_map(static fn(array $student): int => (int) ($student['id'] ?? 0), $students));
$availableAthletes = array_values(array_filter(atlasGetAllStudents(), static function (array $student) use ($assignedStudentIds): bool {
    return !in_array((int) ($student['id'] ?? 0), $assignedStudentIds, true);
}));
usort($availableAthletes, static fn(array $a, array $b): int => strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));
$featuredAthlete = $students[0] ?? null;
$progress = atlasGetCoachProgress($featuredAthlete['id'] ?? null);
$clubName = userSubtitle($user);
$avgScore = atlasAverageScore($students);
$sessionCount = array_sum(array_column($students, 'video_count'));
$featuredTrend = ($featuredAthlete['score'] ?? 0) >= 80 ? 'En progression' : 'A consolider';
$featuredStrength = $featuredAthlete['strengths_list'][0] ?? 'Evaluation video disponible';
$featuredImprovement = $featuredAthlete['improvements_list'][0] ?? 'Completer avec une nouvelle seance mesuree';
$featuredRecommendation = $featuredAthlete['recommendations_list'][0] ?? 'Maintenir un suivi regulier avec une nouvelle seance analysee.';
$featuredAthletePayload = $featuredAthlete ? htmlspecialchars(json_encode($featuredAthlete, JSON_UNESCAPED_UNICODE), ENT_QUOTES) : '';
$progressScores = $progress['global'] ?? [];
$progressDelta = count($progressScores) >= 2 ? ((int) end($progressScores) - (int) reset($progressScores)) : 0;
$nextSessionFocus = $featuredAthlete['perf_type'] ?? 'Travail specifique';
$progressSummary = $progressDelta > 0
    ? '+' . $progressDelta . ' points sur la periode observee'
    : ($progressDelta < 0 ? $progressDelta . ' points sur la periode observee' : 'Performance stable sur la periode observee');
$featuredMetricScores = [
    'Vitesse' => (int) ($featuredAthlete['vitesse'] ?? 0),
    'Coordination' => (int) ($featuredAthlete['coordination'] ?? 0),
    'Endurance' => (int) ($featuredAthlete['endurance'] ?? 0),
    'Force' => (int) ($featuredAthlete['force'] ?? 0),
    'Souplesse' => (int) ($featuredAthlete['souplesse'] ?? 0),
];
$strongestMetricScores = $featuredMetricScores;
arsort($strongestMetricScores);
$strongestMetricKeys = array_keys($strongestMetricScores);
$strongestMetric = (string) ($strongestMetricKeys[0] ?? 'Vitesse');
$strongestMetricScore = (int) reset($strongestMetricScores);
$weakestMetricScores = $featuredMetricScores;
asort($weakestMetricScores);
$weakestMetricKeys = array_keys($weakestMetricScores);
$weakestMetric = (string) ($weakestMetricKeys[0] ?? 'Force');
$weakestMetricScore = (int) reset($weakestMetricScores);
$nextSessionDate = new DateTimeImmutable('+7 days');
$nextSessionDay = $nextSessionDate->format('d');
$monthMap = ['JAN', 'FEV', 'MAR', 'AVR', 'MAI', 'JUN', 'JUL', 'AOU', 'SEP', 'OCT', 'NOV', 'DEC'];
$nextSessionMonth = $monthMap[(int) $nextSessionDate->format('n') - 1] ?? strtoupper($nextSessionDate->format('M'));

$pageTitle = 'Dashboard Coach';
$navType = 'coach';
$bodyClass = 'dash-body';
include __DIR__ . '/../../components/head.php';
include __DIR__ . '/../../components/navbar.php';
?>

<div class="dash-layout">
  <aside class="sidebar">
    <div class="sidebar-user">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
        <div class="avatar avatar-md avatar-green"><?= htmlspecialchars($user['avatar']) ?></div>
        <div>
          <div class="sidebar-user-name"><?= htmlspecialchars($user['name']) ?></div>
          <div class="sidebar-user-role"><?= htmlspecialchars($clubName) ?></div>
        </div>
      </div>
    </div>
    <nav class="sidebar-nav">
      <div class="sidebar-item active" data-target="coachOverview"><span class="icon">🏠</span> Tableau de bord</div>
      <div class="sidebar-item" data-target="coachAthletes"><span class="icon">👟</span> Mes athlètes</div>
      <div class="sidebar-item" data-target="coachSession"><span class="icon">📅</span> Planning sessions</div>
      <div class="sidebar-item" data-target="coachProgress"><span class="icon">📊</span> Statistiques</div>
      <div class="sidebar-item" data-target="coachMessages"><span class="icon">✉</span> Messages</div>
      <div class="sidebar-label" style="margin-top:16px;">Suivi</div>
      <div class="sidebar-item" data-target="coachProgress"><span class="icon">📈</span> Progression</div>
      <div class="sidebar-item" data-target="coachRecommendations"><span class="icon">🎯</span> Objectifs</div>
      <div class="sidebar-item" data-action="exportCoachPdf"><span class="icon">📝</span> Rapports <span class="sidebar-badge">3</span></div>
      <div class="sidebar-label" style="margin-top:16px;">Compte</div>
      <div class="sidebar-item" data-modal="settingsModal"><span class="icon">⚙️</span> Paramètres</div>
      <div class="sidebar-item" data-href="<?= APP_URL ?>/pages/auth/logout.php"><span class="icon">🚪</span> Déconnexion</div>
    </nav>
  </aside>

  <main class="dash-main">
    <div class="dash-header" id="coachOverview">
      <div class="dash-header-top">
        <div>
          <h1 class="heading-xl">Suivi de progression</h1>
          <p class="text-sm text-muted" style="margin-top:4px;"><?= htmlspecialchars($clubName) ?> · <?= htmlspecialchars(formatLongDate()) ?></p>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
          <select class="form-control" style="width:auto;padding:10px 14px;" id="periodSelect" onchange="updateCharts(this.value)">
            <option value="6">6 derniers mois</option>
            <option value="3">3 derniers mois</option>
            <option value="12">1 an</option>
          </select>
          <button class="btn btn-primary" type="button" onclick="exportCoachPdf()">⬇ Exporter rapport</button>
        </div>
      </div>
    </div>

    <div class="athlete-header mb-24" style="background:linear-gradient(135deg,var(--green) 0%,#1B5E20 100%);">
      <div class="avatar avatar-xl avatar-green" style="border:3px solid rgba(255,255,255,.3);"><?= htmlspecialchars(initials($featuredAthlete['name'] ?? 'Coach')) ?></div>
      <div style="flex:1;">
        <h2 style="font-family:var(--font-display);font-size:28px;font-weight:700;color:white;letter-spacing:-1px;margin-bottom:5px;"><?= htmlspecialchars($featuredAthlete['name'] ?? 'Aucun athlète') ?></h2>
        <p style="color:rgba(255,255,255,.75);font-size:14px;"><?= htmlspecialchars((string) ($featuredAthlete['age'] ?? '-')) ?> ans · <?= htmlspecialchars($featuredAthlete['sport'] ?? 'Sport') ?> - <?= htmlspecialchars($featuredAthlete['perf_type'] ?? 'Performance') ?> · 📍 <?= htmlspecialchars($featuredAthlete['ville'] ?? 'Maroc') ?></p>
        <div style="display:flex;gap:8px;margin-top:12px;">
          <span class="badge" style="background:rgba(255,255,255,.15);color:white;">🏅 <?= htmlspecialchars($featuredTrend) ?></span>
          <span class="badge" style="background:rgba(255,255,255,.15);color:white;">🏆 <?= (int) ($featuredAthlete['video_count'] ?? 0) ?> analyses</span>
        </div>
      </div>
      <div class="score-ring">
        <div class="score-ring-num"><?= (int) ($featuredAthlete['score'] ?? 0) ?></div>
        <div class="score-ring-label">Score actuel</div>
        <div class="score-ring-trend"><?= htmlspecialchars(($featuredAthlete['analysis_provider'] ?? 'demo') === 'openai' ? '↗ Analyse OpenAI' : '↗ Analyse demo') ?></div>
      </div>
    </div>

    <div class="grid-4 mb-24">
      <div class="stat-card"><div class="stat-icon" style="background:var(--green-light);">📈</div><div><div class="stat-value"><?= (int) $avgScore ?></div><div class="stat-label">Score moyen</div><div class="stat-trend trend-up">↗ Données athlètes suivis</div></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:rgba(255,143,0,.12);">⚡</div><div><div class="stat-value"><?= (int) ($featuredAthlete['vitesse'] ?? 0) ?></div><div class="stat-label">Vitesse</div><div class="stat-trend trend-up">↗ Point fort actuel</div></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:var(--blue-light);">💪</div><div><div class="stat-value"><?= (int) ($featuredAthlete['force'] ?? 0) ?></div><div class="stat-label">Force</div><div class="stat-trend trend-down">↘ Axe à consolider</div></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:var(--purple-light);">📅</div><div><div class="stat-value"><?= (int) $sessionCount ?></div><div class="stat-label">Sessions</div><div class="stat-trend trend-flat">Videos analysees</div></div></div>
    </div>

    <div class="grid-2-1 mb-24">
      <div class="card" id="coachProgress">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
          <div>
            <div class="heading-md">Évolution des performances</div>
            <div class="text-xs text-muted" style="margin-top:3px;">Suivi détaillé sur la période sélectionnée</div>
          </div>
          <div style="display:flex;gap:8px;">
            <select class="form-control" id="metricSelect" style="width:auto;padding:8px 12px;font-size:12px;" onchange="updateMetricView(this.value)">
              <option value="all">Toutes les metriques</option>
              <option value="speed">Vitesse seulement</option>
              <option value="strength">Force seulement</option>
            </select>
            <button class="btn btn-sm" type="button" style="border:1px solid var(--border);background:white;gap:6px;" onclick="exportCoachPdf()">⬇ Exporter</button>
          </div>
        </div>

        <div style="display:flex;flex-wrap:wrap;gap:14px;margin-bottom:14px;">
          <?php foreach ([['#FF8F00', 'Vitesse'], ['#1565C0', 'Coordination'], ['#2E7D32', 'Endurance'], ['#9C27B0', 'Force'], ['#00ACC1', 'Souplesse']] as [$color, $label]): ?>
          <span style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--muted);">
            <span style="width:10px;height:10px;border-radius:2px;background:<?= htmlspecialchars($color) ?>;display:inline-block;"></span>
            <?= htmlspecialchars($label) ?>
          </span>
          <?php endforeach; ?>
        </div>

        <div style="position:relative;height:260px;"><canvas id="progressChart"></canvas></div>
        <div style="margin-top:20px;padding-top:18px;border-top:1px solid var(--border-soft);">
          <div class="text-sm" style="font-weight:700;margin-bottom:12px;">Tendances de performance</div>
          <div style="position:relative;height:90px;"><canvas id="trendChart"></canvas></div>
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:18px;">
        <div class="card">
          <div class="card-header-row mb-16">
            <div>
              <div class="heading-md">Derniere video analysee</div>
              <div class="text-xs text-muted" style="margin-top:4px;"><?= htmlspecialchars($featuredAthlete['name'] ?? 'Aucun athlete') ?></div>
            </div>
            <?php if ($featuredAthlete): ?>
            <button class="btn btn-sm btn-outline" type="button" data-student='<?= $featuredAthletePayload ?>' onclick="openCoachProfile(this)">Ouvrir le profil</button>
            <?php endif; ?>
          </div>
          <?php if (!empty($featuredAthlete['video_url'])): ?>
          <div class="coach-video-card">
            <video class="coach-video-player" src="<?= htmlspecialchars($featuredAthlete['video_url']) ?>" controls preload="metadata" playsinline></video>
          </div>
          <?php else: ?>
          <div class="empty-state">Aucune video analysee disponible pour cet athlete.</div>
          <?php endif; ?>
          <div class="coach-video-summary">
            <strong><?= htmlspecialchars($featuredStrength) ?></strong>
            <span><?= htmlspecialchars($featuredAthlete['ai_summary'] ?? 'Le resume de l analyse apparaitra ici quand une video sera disponible.') ?></span>
          </div>
        </div>

        <div class="card" id="coachRecommendations">
          <div class="heading-md mb-16">Points clés IA</div>
          <div style="display:flex;flex-direction:column;gap:10px;">
            <div style="background:var(--green-light);border-radius:var(--radius-sm);padding:12px;display:flex;gap:10px;align-items:flex-start;">
              <span style="font-size:18px;">↗</span>
              <div><div style="font-size:13px;font-weight:700;color:var(--green);"><?= htmlspecialchars($progressDelta >= 0 ? 'Progression sur la periode' : 'Variation recente a suivre') ?></div><div class="text-xs" style="color:var(--green);margin-top:2px;"><?= htmlspecialchars($progressSummary) ?></div></div>
            </div>
            <div style="background:var(--orange-light);border-radius:var(--radius-sm);padding:12px;display:flex;gap:10px;align-items:flex-start;">
              <span style="font-size:18px;">🏅</span>
              <div><div style="font-size:13px;font-weight:700;color:var(--orange);">Point fort identifié</div><div class="text-xs" style="color:var(--orange);margin-top:2px;"><?= htmlspecialchars($featuredStrength . ' - ' . $strongestMetric . ' ' . $strongestMetricScore . '/100') ?></div></div>
            </div>
            <div style="background:var(--red-light);border-radius:var(--radius-sm);padding:12px;display:flex;gap:10px;align-items:flex-start;">
              <span style="font-size:18px;">↘</span>
              <div><div style="font-size:13px;font-weight:700;color:var(--red);">Zone à améliorer</div><div class="text-xs" style="color:var(--red);margin-top:2px;"><?= htmlspecialchars($featuredImprovement . ' - ' . $weakestMetric . ' ' . $weakestMetricScore . '/100') ?></div></div>
            </div>
          </div>
        </div>

        <div class="card" id="coachSession">
          <div class="heading-md mb-14">Recommandations IA</div>
          <div style="display:flex;flex-direction:column;gap:9px;">
            <div style="display:flex;gap:8px;font-size:13px;"><span style="color:var(--green);font-weight:700;flex-shrink:0;">✓</span><span><?= htmlspecialchars($featuredRecommendation) ?></span></div>
            <div style="display:flex;gap:8px;font-size:13px;"><span style="color:var(--orange);font-weight:700;flex-shrink:0;">⚠</span><span><?= htmlspecialchars('Priorite du cycle: ' . $weakestMetric . ' a renforcer.') ?></span></div>
            <div style="display:flex;gap:8px;font-size:13px;"><span style="color:var(--blue);font-weight:700;flex-shrink:0;">ℹ</span><span><?= htmlspecialchars('Capitaliser sur ' . $strongestMetric . ' pour la prochaine evaluation video.') ?></span></div>
            <div style="display:flex;gap:8px;font-size:13px;"><span style="color:var(--green);font-weight:700;flex-shrink:0;">✓</span><span><?= htmlspecialchars('Confirmer les acquis sur ' . (int) ($featuredAthlete['video_count'] ?? 0) . ' videos deja analysees.') ?></span></div>
          </div>
        </div>

        <div class="card">
          <div class="heading-md mb-14">Prochaine session recommandee</div>
          <div style="display:flex;gap:14px;align-items:center;">
            <div style="background:var(--green);color:white;border-radius:var(--radius-sm);min-width:52px;text-align:center;padding:8px 10px;">
              <div style="font-family:var(--font-display);font-size:24px;font-weight:700;line-height:1;"><?= htmlspecialchars($nextSessionDay) ?></div>
              <div style="font-size:10px;font-weight:700;margin-top:1px;"><?= htmlspecialchars($nextSessionMonth) ?></div>
            </div>
            <div>
              <div style="font-size:14px;font-weight:700;"><?= htmlspecialchars($nextSessionFocus) ?></div>
              <div class="text-xs text-muted" style="margin-top:3px;">Nouvelle seance video a programmer sous 7 jours</div>
              <div class="text-xs text-muted" style="margin-top:2px;"><?= htmlspecialchars('Focus : consolider ' . strtolower($weakestMetric) . ' tout en conservant ' . strtolower($strongestMetric)) ?></div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="heading-md mb-14">Profil de performance</div>
          <div style="position:relative;height:180px;"><canvas id="radarChart"></canvas></div>
        </div>
      </div>
    </div>

    <div class="card" id="coachAthletes">
      <div class="card-header-row mb-16">
        <div class="heading-md">Mes athlètes (<?= count($students) ?>)</div>
        <button class="btn btn-sm btn-primary" type="button" data-add-athlete="true">+ Ajouter athlète</button>
      </div>
      <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
          <thead>
            <tr style="border-bottom:2px solid var(--border);">
              <th style="text-align:left;padding:10px 12px;font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;">Athlète</th>
              <th style="text-align:center;padding:10px 12px;font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;">Score</th>
              <th style="text-align:center;padding:10px 12px;font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;">Vitesse</th>
              <th style="text-align:center;padding:10px 12px;font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;">Coord.</th>
              <th style="text-align:center;padding:10px 12px;font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;">Force</th>
              <th style="text-align:center;padding:10px 12px;font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;">Tendance</th>
              <th style="text-align:center;padding:10px 12px;font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $student): ?>
            <?php $studentPayload = htmlspecialchars(json_encode($student, JSON_UNESCAPED_UNICODE), ENT_QUOTES); ?>
            <tr style="border-bottom:1px solid var(--border-soft);" onmouseenter="this.style.background='var(--surface)'" onmouseleave="this.style.background=''">
              <td style="padding:14px 12px;">
                <div style="display:flex;align-items:center;gap:10px;">
                  <div class="avatar avatar-sm avatar-green"><?= htmlspecialchars(initials($student['name'])) ?></div>
                  <div><div style="font-size:14px;font-weight:600;"><?= htmlspecialchars($student['name']) ?></div><div class="text-xs text-muted"><?= htmlspecialchars($student['sport']) ?> · <?= htmlspecialchars($student['ville']) ?></div></div>
                </div>
              </td>
              <td style="text-align:center;padding:14px 12px;"><span style="font-family:var(--font-heading);font-size:17px;font-weight:800;color:<?= htmlspecialchars(scoreColor($student['score'])) ?>;"><?= (int) $student['score'] ?></span></td>
              <td style="text-align:center;padding:14px 12px;"><span style="font-weight:700;color:#FF8F00;"><?= (int) $student['vitesse'] ?></span></td>
              <td style="text-align:center;padding:14px 12px;"><span style="font-weight:700;color:var(--blue);"><?= (int) $student['coordination'] ?></span></td>
              <td style="text-align:center;padding:14px 12px;"><span style="font-weight:700;color:var(--purple);"><?= (int) $student['force'] ?></span></td>
              <td style="text-align:center;padding:14px 12px;"><span class="<?= $student['score'] >= 80 ? 'trend-up' : 'trend-down' ?>" style="font-size:16px;"><?= $student['score'] >= 80 ? '↗' : '↘' ?></span></td>
              <td style="text-align:center;padding:14px 12px;"><button class="btn btn-sm" type="button" style="background:var(--green-light);color:var(--green);border:none;font-size:12px;" data-student='<?= $studentPayload ?>' onclick="openCoachProfile(this)">Voir profil</button></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div id="coachMessages" style="margin-top:24px;">
      <?php
      $chatPanelId = 'coachMessagesPanel';
      $chatPanelTitle = 'Messagerie de suivi';
      $chatPanelSubtitle = 'Echanges avec professeurs, talents et cellule recrutement autour des athletes que vous accompagnez.';
      include __DIR__ . '/../../components/chat_panel.php';
      ?>
    </div>
  </main>
</div>

<div class="modal-overlay" id="coachProfileModal">
  <div class="modal-box" style="width:640px;max-width:94vw;">
    <button class="modal-close" type="button" onclick="closeModal('coachProfileModal')">✕</button>
    <div id="coachProfileContent"></div>
  </div>
</div>

<div class="modal-overlay" id="coachAddAthleteModal">
  <div class="modal-box" style="width:560px;max-width:94vw;">
    <button class="modal-close" type="button" onclick="closeModal('coachAddAthleteModal')">✕</button>
    <h2 class="heading-xl" style="margin-bottom:6px;">Ajouter un athlete</h2>
    <p class="text-sm text-muted" style="margin-bottom:18px;">Associez un athlete existant a votre espace de suivi coach.</p>
    <?php if ($availableAthletes === []): ?>
    <div class="empty-state">Tous les athletes disponibles sont deja rattaches a votre suivi.</div>
    <?php else: ?>
    <form method="post" class="coach-add-athlete-form">
      <input type="hidden" name="action" value="add_coach_student">
      <input type="hidden" name="_token" value="<?= htmlspecialchars(Auth::csrfToken()) ?>">
      <div class="form-group">
        <label class="form-label" for="coachAddStudentSelect">Athlete</label>
        <select class="form-control" id="coachAddStudentSelect" name="student_id" required>
          <option value="">Selectionnez un athlete</option>
          <?php foreach ($availableAthletes as $athlete): ?>
          <option value="<?= (int) $athlete['id'] ?>"><?= htmlspecialchars($athlete['name']) ?> · <?= htmlspecialchars($athlete['sport'] ?? 'Sport') ?> · <?= htmlspecialchars($athlete['ville'] ?? 'Maroc') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="coach-add-athlete-list">
        <?php foreach (array_slice($availableAthletes, 0, 5) as $athlete): ?>
        <div class="coach-add-athlete-item">
          <div class="avatar avatar-sm avatar-green"><?= htmlspecialchars(initials($athlete['name'])) ?></div>
          <div style="min-width:0;">
            <div style="font-size:14px;font-weight:700;"><?= htmlspecialchars($athlete['name']) ?></div>
            <div class="text-xs text-muted"><?= htmlspecialchars($athlete['sport'] ?? 'Sport') ?> · <?= htmlspecialchars($athlete['ville'] ?? 'Maroc') ?></div>
          </div>
          <div class="coach-add-athlete-score"><?= (int) ($athlete['score'] ?? 0) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:18px;">
        <button class="btn btn-outline btn-sm" type="button" onclick="closeModal('coachAddAthleteModal')">Annuler</button>
        <button class="btn btn-primary btn-sm" type="submit">Ajouter au suivi</button>
      </div>
    </form>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../../components/settings_modal.php'; ?>

<style>
.card-header-row { display:flex;align-items:center;justify-content:space-between; }
</style>

<style>
.card-header-row {
  gap: 12px;
  flex-wrap: wrap;
}

#coachAthletes table {
  min-width: 760px;
}

#coachAthletes tbody tr {
  transition: background-color .2s ease, transform .2s ease;
}

#coachAthletes tbody tr:hover {
  transform: translateX(2px);
}

.coach-video-card {
  border-radius: 22px;
  overflow: hidden;
  background: #000;
  box-shadow: var(--shadow-sm);
}

.coach-video-player {
  width: 100%;
  max-height: 260px;
  display: block;
  background: #000;
}

.coach-video-summary {
  margin-top: 14px;
  padding: 14px 16px;
  border-radius: 18px;
  background: rgba(255,255,255,.72);
  border: 1px solid rgba(10,10,15,.06);
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.coach-video-summary strong {
  font-size: 13px;
  color: var(--green);
}

.coach-video-summary span {
  font-size: 13px;
  color: var(--ink-80);
  line-height: 1.6;
}

.empty-state {
  padding: 16px;
  border-radius: 18px;
  background: rgba(255,255,255,.62);
  border: 1px dashed rgba(10,10,15,.08);
  text-align: center;
  color: var(--muted);
  font-size: 13px;
}

.coach-add-athlete-form {
  display: flex;
  flex-direction: column;
}

.coach-add-athlete-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.coach-add-athlete-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border-radius: 18px;
  background: rgba(255,255,255,.76);
  border: 1px solid rgba(10,10,15,.06);
}

.coach-add-athlete-score {
  margin-left: auto;
  min-width: 42px;
  text-align: center;
  font-family: var(--font-heading);
  font-size: 18px;
  font-weight: 800;
  color: var(--green);
}
</style>

<?php include __DIR__ . '/../../components/scripts.php'; ?>
<script>
const chartSeries = {
  labels: <?= json_encode($progress['labels'], JSON_UNESCAPED_UNICODE) ?>,
  vitesse: <?= json_encode($progress['vitesse']) ?>,
  coordination: <?= json_encode($progress['coordination']) ?>,
  endurance: <?= json_encode($progress['endurance']) ?>,
  force: <?= json_encode($progress['force']) ?>,
  souplesse: <?= json_encode($progress['souplesse']) ?>,
  global: <?= json_encode($progress['global']) ?>,
};

const featuredMetrics = <?= json_encode([
    (int) ($featuredAthlete['vitesse'] ?? 0),
    (int) ($featuredAthlete['coordination'] ?? 0),
    (int) ($featuredAthlete['endurance'] ?? 0),
    (int) ($featuredAthlete['force'] ?? 0),
    (int) ($featuredAthlete['souplesse'] ?? 0),
]) ?>;

let progressChart = null;
let trendChart = null;
let radarChart = null;
const hasCoachProgressData = Array.isArray(chartSeries.labels) && chartSeries.labels.length > 0;
const hasFeaturedAthlete = <?= $featuredAthlete ? 'true' : 'false' ?>;

function sliceTail(values, months) {
  return months >= values.length ? values : values.slice(values.length - months);
}

if (typeof Chart === 'undefined') {
  renderChartFallback('progressChart', 'Le graphique principal est indisponible hors ligne.');
  renderChartFallback('trendChart', 'Les tendances sont indisponibles hors ligne.');
  renderChartFallback('radarChart', 'Le radar de performance est indisponible hors ligne.');
} else {
  if (hasCoachProgressData) {
    progressChart = new Chart(document.getElementById('progressChart'), {
      type: 'line',
      data: {
        labels: chartSeries.labels,
        datasets: [
          { label: 'Vitesse', data: chartSeries.vitesse, borderColor: '#FF8F00', backgroundColor: 'transparent', tension: .4, pointRadius: 5, pointBackgroundColor: '#FF8F00', borderWidth: 2.5 },
          { label: 'Coordination', data: chartSeries.coordination, borderColor: '#1565C0', backgroundColor: 'transparent', tension: .4, pointRadius: 5, pointBackgroundColor: '#1565C0', borderWidth: 2.5 },
          { label: 'Endurance', data: chartSeries.endurance, borderColor: '#2E7D32', backgroundColor: 'transparent', tension: .4, pointRadius: 5, pointBackgroundColor: '#2E7D32', borderWidth: 2.5 },
          { label: 'Force', data: chartSeries.force, borderColor: '#9C27B0', backgroundColor: 'transparent', tension: .4, pointRadius: 5, pointBackgroundColor: '#9C27B0', borderWidth: 2.5, borderDash: [5, 3] },
          { label: 'Souplesse', data: chartSeries.souplesse, borderColor: '#00ACC1', backgroundColor: 'transparent', tension: .4, pointRadius: 5, pointBackgroundColor: '#00ACC1', borderWidth: 2.5 },
        ]
      },
      options: { responsive: true, maintainAspectRatio: false, scales: { y: { min: 50, max: 100 } } }
    });

    trendChart = new Chart(document.getElementById('trendChart'), {
      type: 'line',
      data: { labels: chartSeries.labels, datasets: [{ data: chartSeries.global, borderColor: '#2E7D32', backgroundColor: 'rgba(46,125,50,.12)', fill: true, tension: .4, pointRadius: 0, borderWidth: 2.5 }] },
      options: { responsive: true, maintainAspectRatio: false, scales: { y: { min: 50, max: 100, ticks: { font: { size: 10 } } }, x: { grid: { display: false }, ticks: { font: { size: 10 } } } } }
    });
  } else {
    renderChartFallback('progressChart', 'Aucune progression fiable Ã  afficher tant qu une analyse n a pas ete enregistree.');
    renderChartFallback('trendChart', 'La tendance apparaitra apres la premiere analyse disponible.');
  }

  if (hasFeaturedAthlete) {
    radarChart = new Chart(document.getElementById('radarChart'), {
      type: 'radar',
      data: {
        labels: ['Vitesse', 'Coordination', 'Endurance', 'Force', 'Souplesse'],
        datasets: [{ data: featuredMetrics, borderColor: '#C8102E', backgroundColor: 'rgba(200,16,46,.12)', pointBackgroundColor: '#C8102E', borderWidth: 2 }]
      },
      options: { responsive: true, maintainAspectRatio: false, scales: { r: { min: 0, max: 100, ticks: { stepSize: 25, font: { size: 9 } }, pointLabels: { font: { size: 11 } } } } }
    });
  } else {
    renderChartFallback('radarChart', 'Aucun athlÃ¨te affectÃ© pour le moment.');
  }
}

function exportCoachPdf() {
  const rows = <?= json_encode(array_map(function ($student) {
      return [
          'name' => $student['name'],
          'sport' => $student['sport'],
          'ville' => $student['ville'],
          'score' => (int) $student['score'],
          'vitesse' => (int) $student['vitesse'],
          'coordination' => (int) $student['coordination'],
          'endurance' => (int) $student['endurance'],
          'force' => (int) $student['force'],
          'souplesse' => (int) $student['souplesse'],
      ];
  }, $students), JSON_UNESCAPED_UNICODE) ?>;

  if (!rows.length) {
    showToast('Aucun athlete a exporter pour le moment.', 'warning');
    return;
  }

  const bestAthlete = rows.reduce((best, current) => current.score > best.score ? current : best, rows[0]);
  const tableRows = rows.map(student => `
    <tr>
      <td>${escapeHtml(student.name)}</td>
      <td>${escapeHtml(student.sport)}</td>
      <td>${escapeHtml(student.ville)}</td>
      <td>${escapeHtml(student.score)}%</td>
    </tr>
  `).join('');

  exportDashboardPdf(
    'Rapport Coach',
    <?= json_encode($clubName . ' · ' . formatLongDate('now', true), JSON_UNESCAPED_UNICODE) ?>,
    [
      {
        heading: 'Synthèse',
        content: `
          <div class="metric-grid">
            <div class="metric-card"><strong>${rows.length}</strong><span>Athlètes suivis</span></div>
            <div class="metric-card"><strong><?= (int) $avgScore ?>%</strong><span>Score moyen global</span></div>
            <div class="metric-card"><strong>${escapeHtml(bestAthlete.name)}</strong><span>Meilleur profil du moment</span></div>
            <div class="metric-card"><strong><?= (int) $sessionCount ?></strong><span>Sessions analysees</span></div>
          </div>
        `
      },
      {
        heading: 'Athlètes',
        content: `<table><thead><tr><th>Nom</th><th>Sport</th><th>Ville</th><th>Score</th></tr></thead><tbody>${tableRows}</tbody></table>`
      },
      {
        heading: 'Focus d’entraînement',
        content: `
          <ul>
            <li><?= htmlspecialchars($featuredRecommendation, ENT_QUOTES) ?></li>
            <li><?= htmlspecialchars('Priorite actuelle : ' . $weakestMetric . ' pour le profil principal.', ENT_QUOTES) ?></li>
            <li><?= htmlspecialchars('S appuyer sur ' . $strongestMetric . ' et maintenir un suivi video mensuel.', ENT_QUOTES) ?></li>
          </ul>
        `
      }
    ]
  );
}

function openCoachProfile(button) {
  const student = JSON.parse(button.dataset.student || '{}');

  if (!student.name) {
    showToast('Profil indisponible pour le moment.', 'error');
    return;
  }

  const metrics = [
    ['Vitesse', student.vitesse, '#FF8F00'],
    ['Coordination', student.coordination, '#1565C0'],
    ['Endurance', student.endurance, '#2E7D32'],
    ['Force', student.force, '#9C27B0'],
    ['Souplesse', student.souplesse, '#00ACC1'],
  ];

  document.getElementById('coachProfileContent').innerHTML = `
    <div style="display:flex;gap:18px;align-items:center;flex-wrap:wrap;">
      <div class="avatar avatar-xl avatar-green">${escapeHtml((student.name || '').split(' ').map(part => part[0] || '').slice(0, 2).join(''))}</div>
      <div style="flex:1;min-width:220px;">
        <h2 class="heading-xl" style="margin-bottom:6px;">${escapeHtml(student.name)}</h2>
        <div class="text-sm text-muted">${escapeHtml(student.age)} ans · ${escapeHtml(student.sport)} · ${escapeHtml(student.ville)}</div>
      </div>
      <div style="padding:14px 18px;border-radius:20px;background:var(--green-light);text-align:center;min-width:108px;">
        <div style="font-family:var(--font-display);font-size:34px;font-weight:700;color:var(--green);line-height:1;">${escapeHtml(student.score)}%</div>
        <div class="text-xs text-muted" style="margin-top:6px;">Score global</div>
      </div>
    </div>
    ${student.video_url ? `
      <div class="coach-video-card" style="margin-top:22px;">
        <video class="coach-video-player" src="${escapeHtml(student.video_url)}" controls preload="metadata" playsinline></video>
      </div>
    ` : `
      <div class="empty-state" style="margin-top:22px;">Aucune video analysee disponible pour cet athlete.</div>
    `}
    <div class="coach-video-summary" style="margin-top:18px;">
      <strong>${escapeHtml((student.strengths_list && student.strengths_list[0]) || 'Lecture IA')}</strong>
      <span>${escapeHtml(student.ai_summary || 'Resume IA indisponible pour le moment.')}</span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:24px;">
      ${metrics.map(([label, value, color]) => `
        <div style="padding:14px 16px;border-radius:18px;background:rgba(255,255,255,.72);border:1px solid rgba(10,10,15,.06);">
          <div class="text-xs text-muted" style="margin-bottom:8px;text-transform:uppercase;letter-spacing:.08em;">${escapeHtml(label)}</div>
          <div style="font-size:26px;font-weight:800;color:${color};">${escapeHtml(value)}</div>
        </div>
      `).join('')}
    </div>
  `;

  openModal('coachProfileModal');
}

function updateMetricView(metricLabel) {
  if (!progressChart) {
    return;
  }

  const metricMap = {
    all: ['Vitesse', 'Coordination', 'Endurance', 'Force', 'Souplesse'],
    speed: ['Vitesse'],
    strength: ['Force'],
  };

  const visibleLabels = metricMap[metricLabel] || metricMap.all;
  progressChart.data.datasets.forEach(dataset => {
    dataset.hidden = !visibleLabels.includes(dataset.label);
  });
  progressChart.update();
}

function handleAddAthlete() {
  openModal('coachAddAthleteModal');
  window.setTimeout(() => {
    document.getElementById('coachAddStudentSelect')?.focus();
  }, 40);
}

function updateCharts(months) {
  if (!progressChart || !trendChart) {
    return;
  }

  const selectedMonths = Number(months);
  const labels = sliceTail(chartSeries.labels, selectedMonths);

  progressChart.data.labels = labels;
  progressChart.data.datasets[0].data = sliceTail(chartSeries.vitesse, selectedMonths);
  progressChart.data.datasets[1].data = sliceTail(chartSeries.coordination, selectedMonths);
  progressChart.data.datasets[2].data = sliceTail(chartSeries.endurance, selectedMonths);
  progressChart.data.datasets[3].data = sliceTail(chartSeries.force, selectedMonths);
  progressChart.data.datasets[4].data = sliceTail(chartSeries.souplesse, selectedMonths);
  progressChart.update();

  trendChart.data.labels = labels;
  trendChart.data.datasets[0].data = sliceTail(chartSeries.global, selectedMonths);
  trendChart.update();

  showToast('Période mise à jour : ' + months + ' mois', 'default');
}
document.querySelector('[data-add-athlete="true"]')?.addEventListener('click', handleAddAthlete);
updateMetricView(document.getElementById('metricSelect')?.value || 'all');
</script>
