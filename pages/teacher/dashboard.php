<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

Auth::check(ROLE_TEACHER);

$user = Auth::user();
$students = atlasGetTeacherStudents((int) $user['id']);
$teacherStats = atlasGetTeacherStats((int) $user['id']);
$recentVideos = atlasGetRecentVideosForTeacher((int) $user['id']);
$latestVideo = $recentVideos[0] ?? null;
$teacherContext = userSubtitle($user);
$displayName = preg_replace('/^Prof\.\s*/u', '', userDisplayName($user));
$displayNameParts = preg_split('/\s+/', trim($displayName));
$displayName = $displayNameParts[0] ?? userDisplayName($user);
$topTalents = atlasTopTalents($students);
$teacherStudentDirectory = array_map(static function (array $student): array {
    $updatedAt = (string) ($student['updated'] ?? 'now');

    return [
        'id' => (int) ($student['id'] ?? 0),
        'initials' => initials((string) ($student['name'] ?? '')),
        'name' => (string) ($student['name'] ?? 'Eleve'),
        'age' => (int) ($student['age'] ?? 0),
        'ville' => (string) ($student['ville'] ?? ''),
        'sport' => (string) ($student['sport'] ?? ''),
        'perf_type' => (string) ($student['perf_type'] ?? 'Performance'),
        'score' => (int) ($student['score'] ?? 0),
        'score_color' => scoreColor((int) ($student['score'] ?? 0)),
        'vitesse' => (int) ($student['vitesse'] ?? 0),
        'coordination' => (int) ($student['coordination'] ?? 0),
        'endurance' => (int) ($student['endurance'] ?? 0),
        'force' => (int) ($student['force'] ?? 0),
        'souplesse' => (int) ($student['souplesse'] ?? 0),
        'video_count' => (int) ($student['video_count'] ?? 0),
        'summary' => (string) ($student['ai_summary'] ?? ''),
        'strengths' => array_values($student['strengths_list'] ?? []),
        'improvements' => array_values($student['improvements_list'] ?? []),
        'recommendations' => array_values($student['recommendations_list'] ?? []),
        'analysis_provider' => strtoupper((string) ($student['analysis_provider'] ?? 'demo')),
        'updated_label' => formatDate($updatedAt),
        'updated_ago' => timeAgo($updatedAt),
        'video_url' => $student['video_url'] ?? null,
    ];
}, $students);
$canUpload = Database::isAvailable();
$aiEnabled = atlasAiEnabled();

$pageTitle = 'Dashboard Professeur';
$navType = 'teacher';
$bodyClass = 'dash-body';
include __DIR__ . '/../../components/head.php';
include __DIR__ . '/../../components/navbar.php';
?>

<div class="dash-layout">
  <aside class="sidebar">
    <div class="sidebar-user">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
        <div class="avatar avatar-md avatar-red"><?= htmlspecialchars($user['avatar']) ?></div>
        <div>
          <div class="sidebar-user-name"><?= htmlspecialchars($user['name']) ?></div>
          <div class="sidebar-user-role">Professeur d'EPS</div>
        </div>
      </div>
      <div style="background:var(--green-light);border-radius:var(--radius-sm);padding:8px 12px;display:flex;align-items:center;gap:6px;">
        <span style="font-size:11px;color:var(--green);">●</span>
        <span style="font-size:12px;color:var(--green);font-weight:600;"><?= htmlspecialchars($teacherContext) ?></span>
      </div>
    </div>
    <nav class="sidebar-nav">
      <div class="sidebar-item active" data-target="teacherOverview"><span class="icon">🏠</span> Tableau de bord</div>
      <div class="sidebar-item" data-modal="uploadModal"><span class="icon">🎥</span> Uploader vidéo</div>
      <div class="sidebar-item" data-target="teacherStudents"><span class="icon">👥</span> Mes élèves</div>
      <div class="sidebar-item" data-target="teacherActivity"><span class="icon">📹</span> Vidéos analysées</div>
      <div class="sidebar-item" data-target="teacherMessagesView"><span class="icon">✉</span> Messages</div>
      <div class="sidebar-label" style="margin-top:16px;">Rapports</div>
      <div class="sidebar-item" data-target="teacherScores"><span class="icon">📊</span> Statistiques</div>
      <div class="sidebar-item" data-action="exportTeacherPdf"><span class="icon">📝</span> Rapports PDF <span class="sidebar-badge">2</span></div>
      <div class="sidebar-label" style="margin-top:16px;">Compte</div>
      <div class="sidebar-item" data-modal="settingsModal"><span class="icon">⚙️</span> Paramètres</div>
      <div class="sidebar-item" data-href="<?= APP_URL ?>/pages/auth/logout.php"><span class="icon">🚪</span> Déconnexion</div>
    </nav>
  </aside>

  <main class="dash-main">
    <div class="teacher-dashboard-view is-active" id="teacherHomeView">
    <div class="dash-header" id="teacherOverview">
      <div class="dash-header-top">
        <div>
          <h1 class="heading-xl">Bonjour, <?= htmlspecialchars($displayName) ?> 👋</h1>
          <p class="text-sm text-muted" style="margin-top:4px;"><?= htmlspecialchars($teacherContext) ?> · <?= htmlspecialchars(formatLongDate('now', true)) ?></p>
        </div>
        <button class="btn btn-primary" onclick="openUploadModal()" <?= !$canUpload ? 'disabled' : '' ?>>🎥 Uploader une vidéo</button>
      </div>
    </div>

    <div class="grid-4 mb-24">
      <div class="stat-card">
        <div class="stat-icon" style="background:var(--blue-light);">👥</div>
        <div>
          <div class="stat-value"><?= (int) $teacherStats['students'] ?></div>
          <div class="stat-label">Élèves</div>
          <div class="stat-trend trend-up">↗ Groupe suivi</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:var(--green-light);">🎥</div>
        <div>
          <div class="stat-value"><?= (int) $teacherStats['videos'] ?></div>
          <div class="stat-label">Vidéos analysées</div>
          <div class="stat-trend trend-up"><?= $aiEnabled ? '↗ Analyse OpenAI active' : '↗ Mode demo intelligent' ?></div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:var(--orange-light);">📈</div>
        <div>
          <div class="stat-value"><?= (int) $teacherStats['avg_score'] ?>%</div>
          <div class="stat-label">Score moyen classe</div>
          <div class="stat-trend trend-up">↗ Mis à jour après chaque upload</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:var(--red-light);">⭐</div>
        <div>
          <div class="stat-value"><?= (int) $teacherStats['talents'] ?></div>
          <div class="stat-label">Talents détectés</div>
          <div class="stat-trend trend-up">↗ Visibles aux recruteurs</div>
        </div>
      </div>
    </div>

    <div class="grid-2-1 mb-24" style="align-items:start;">
      <div class="card" id="teacherStudents">
        <div class="card-header-row mb-16">
          <span class="heading-md">Mes élèves</span>
          <button class="btn btn-sm btn-outline" type="button" data-open-students-directory="true">Voir tout</button>
        </div>
        <div class="search-bar-wrap mb-16">
          <span class="search-icon">🔍</span>
          <input type="text" class="search-bar" placeholder="Rechercher un élève..." id="studentSearch" oninput="filterStudents(this.value)">
        </div>
        <div id="studentsList">
          <?php foreach ($students as $student): ?>
          <div class="student-item" data-name="<?= htmlspecialchars($student['name'], ENT_QUOTES) ?>" data-student-id="<?= (int) $student['id'] ?>">
            <div class="avatar avatar-md avatar-red"><?= htmlspecialchars(initials($student['name'])) ?></div>
            <div style="flex:1;">
              <div class="student-name"><?= htmlspecialchars($student['name']) ?></div>
              <div class="student-meta"><?= (int) $student['age'] ?> ans · <?= htmlspecialchars($student['ville']) ?> · <?= htmlspecialchars($student['sport']) ?></div>
              <div class="progress mt-6">
                <div class="progress-fill" style="width:<?= (int) $student['score'] ?>%;background:<?= htmlspecialchars(scoreColor($student['score'])) ?>;"></div>
              </div>
            </div>
            <div style="text-align:right;flex-shrink:0;">
              <div style="font-family:var(--font-heading);font-size:18px;font-weight:800;color:<?= htmlspecialchars(scoreColor($student['score'])) ?>;"><?= (int) $student['score'] ?>%</div>
              <div style="display:flex;gap:6px;margin-top:7px;">
                <button class="btn btn-sm btn-primary btn-icon" type="button" title="Uploader vidéo" data-student-upload="<?= (int) $student['id'] ?>" style="width:34px;height:34px;padding:0;" <?= !$canUpload ? 'disabled' : '' ?>>🎥</button>
                <button class="btn btn-sm btn-icon" type="button" title="Voir stats" data-student-profile="<?= (int) $student['id'] ?>" style="width:34px;height:34px;padding:0;border:1px solid var(--border);">📊</button>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:20px;">
        <div class="card" id="teacherScores">
          <div class="heading-md mb-16">Scores de la classe</div>
          <div style="position:relative;height:200px;"><canvas id="classChart"></canvas></div>
        </div>
        <div class="card" id="teacherActivity">
          <div class="heading-md mb-16">Activité récente</div>
          <?php if ($latestVideo): ?>
          <div class="teacher-latest-analysis">
            <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
              <div>
                <div class="text-xs text-muted">Dernière analyse IA</div>
                <div class="text-sm" style="font-weight:700;"><?= htmlspecialchars($latestVideo['student_name'] ?? '') ?> · <?= htmlspecialchars($latestVideo['perf_type'] ?? 'Performance') ?></div>
              </div>
              <span class="badge <?= htmlspecialchars(scoreBadgeClass((int) ($latestVideo['score_global'] ?? 0))) ?>"><?= (int) ($latestVideo['score_global'] ?? 0) ?>%</span>
            </div>
            <p class="text-xs text-muted" style="margin-top:10px;line-height:1.6;"><?= htmlspecialchars($latestVideo['ai_summary'] ?? 'Analyse prête.') ?></p>
            <div class="teacher-meta-row">
              <span><?= htmlspecialchars(strtoupper($latestVideo['analysis_provider'] ?? 'demo')) ?></span>
              <span><?= htmlspecialchars(timeAgo($latestVideo['analyzed_at'] ?? $latestVideo['created_at'] ?? 'now')) ?></span>
            </div>
          </div>
          <?php endif; ?>
          <div style="display:flex;flex-direction:column;gap:14px;">
            <?php if ($recentVideos): ?>
              <?php foreach ($recentVideos as $video): ?>
              <div class="activity-item">
                <div class="activity-icon" style="background:<?= ($video['ai_status'] ?? '') === 'done' ? 'var(--green-light)' : 'var(--orange-light)' ?>;"><?= ($video['ai_status'] ?? '') === 'done' ? '✅' : '⏳' ?></div>
                <div>
                  <div class="text-sm" style="font-weight:600;"><?= htmlspecialchars(($video['ai_status'] ?? '') === 'done' ? 'Vidéo analysée' : 'Analyse en attente') ?></div>
                  <div class="text-xs text-muted"><?= htmlspecialchars($video['student_name'] ?? '') ?> · <?= htmlspecialchars($video['perf_type'] ?? 'Performance') ?> · <?= htmlspecialchars(timeAgo($video['analyzed_at'] ?? $video['created_at'] ?? 'now')) ?></div>
                </div>
              </div>
              <?php endforeach; ?>
            <?php else: ?>
            <div class="empty-state">Aucune vidéo analysée pour le moment. Lancez un premier upload pour générer un score IA.</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="talents-banner">
      <div class="talents-banner__left">
        <span class="badge badge-gold">⭐ Top Talents</span>
        <h3 class="heading-md" style="margin:8px 0 4px;"><?= count($topTalents) ?> élèves ont le potentiel d'être recrutés</h3>
        <p class="text-sm text-muted">Score ≥ 85, considérés comme prioritaires côté recrutement</p>
      </div>
      <div class="talents-banner__avatars">
        <?php foreach ($topTalents as $student): ?>
        <div class="avatar avatar-md avatar-red" title="<?= htmlspecialchars($student['name']) ?>"><?= htmlspecialchars(initials($student['name'])) ?></div>
        <?php endforeach; ?>
      </div>
      <button class="btn btn-primary" type="button" onclick="openUploadModal()" <?= !$canUpload ? 'disabled' : '' ?>>Uploader plus de vidéos</button>
    </div>
    </div>

    <div class="teacher-dashboard-view teacher-dashboard-view--messages" id="teacherMessagesView">
      <div id="teacherMessages" class="teacher-messages-screen">
      <?php
      $chatPanelId = 'teacherMessagesPanel';
      $chatPanelTitle = 'Messagerie terrain';
      $chatPanelSubtitle = 'Coordonnez les prises de contact avec managers, recruteurs, coachs et eleves relies a vos profils.';
      include __DIR__ . '/../../components/chat_panel.php';
      ?>
      </div>
    </div>
  </main>
</div>

<div class="modal-overlay" id="uploadModal">
  <div class="modal-box upload-modal-box">
    <button class="modal-close" type="button" onclick="closeModal('uploadModal')">✕</button>
    <h2 class="heading-xl" style="margin-bottom:6px;">Uploader une vidéo</h2>
    <p class="text-sm text-muted" style="margin-bottom:18px;">Sélectionnez l'élève et uploadez sa performance pour l'analyse IA.</p>
    <div class="upload-agent-note" style="margin-bottom:20px;">
      <strong><?= $aiEnabled ? 'Agent OpenAI actif' : 'Mode démonstration actif' ?></strong>
      <span><?= $aiEnabled ? 'Le navigateur extrait maintenant les images les plus utiles de la video avec leurs reperes temporels avant envoi au modele pour une note plus fiable.' : 'Sans OPENAI_API_KEY, Atlas Talents genere une analyse locale provisoire pour preserver le flux produit.' ?></span>
    </div>
    <?php if (!$canUpload): ?>
    <div class="auth-alert auth-alert--error" style="margin-bottom:20px;">Configurez MySQL pour enregistrer les uploads et les résultats d’analyse.</div>
    <?php endif; ?>

    <form id="uploadForm" class="upload-form">
      <input type="hidden" name="action" value="upload_video">
      <input type="hidden" name="_token" value="<?= htmlspecialchars(Auth::csrfToken()) ?>">
      <div class="grid-2" style="gap:14px;">
        <div class="form-group">
          <label class="form-label">Élève</label>
          <select class="form-control" name="student_id" id="uploadStudentSelect">
            <?php foreach ($students as $student): ?>
            <option value="<?= (int) $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Type de performance</label>
          <select class="form-control" name="perf_type">
            <option>Sprint / Vitesse</option>
            <option>Saut en hauteur</option>
            <option>Lancer du poids</option>
            <option>Endurance / Cross</option>
            <option>Gym artistique</option>
            <option>Dribble / Football</option>
            <option>Natation</option>
          </select>
        </div>
      </div>
      <div class="upload-drop" id="uploadDrop" onclick="document.getElementById('videoInput').click()">
        <div style="font-size:44px;margin-bottom:12px;">🎥</div>
        <div class="text-md" style="font-weight:600;margin-bottom:6px;">Glissez votre vidéo ici</div>
        <div class="text-sm text-muted">ou <span style="color:var(--red);font-weight:700;">cliquez pour parcourir</span></div>
        <div class="text-xs text-muted" style="margin-top:8px;">MP4, MOV, AVI · Maximum 500 MB · <?= AI_FRAME_LIMIT ?> images clés extraites côté navigateur</div>
        <input type="file" id="videoInput" name="video" accept="video/*" style="display:none;" onchange="handleFileSelect(this)">
      </div>
      <div id="uploadProgress" style="display:none;margin-top:14px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
          <span class="text-sm" id="uploadFileName">fichier.mp4</span>
          <span class="text-sm text-muted" id="uploadPct">0%</span>
        </div>
        <div class="progress"><div class="progress-fill" id="uploadBar" style="background:var(--red);width:0%;transition:width .3s;"></div></div>
      </div>
      <div id="uploadStatusNote" class="text-xs text-muted" style="margin-top:10px;min-height:18px;"></div>
      <div id="uploadResultPanel" class="upload-result-card" hidden>
        <div class="upload-result-card__header">
          <div>
            <div class="text-xs text-muted">Conclusion IA</div>
            <div class="heading-md" id="uploadResultTitle">Analyse terminee</div>
          </div>
          <div class="upload-result-score" id="uploadResultScore">--</div>
        </div>
        <div class="upload-result-summary">
          <div class="upload-result-summary__item">
            <span class="upload-result-icon">🧠</span>
            <div>
              <strong>Resume</strong>
              <p id="uploadResultSummary">Le resume de l analyse apparaitra ici.</p>
            </div>
          </div>
          <div class="upload-result-summary__item">
            <span class="upload-result-icon">📍</span>
            <div>
              <strong>Point fort</strong>
              <p id="uploadResultStrength">En attente de l analyse.</p>
            </div>
          </div>
          <div class="upload-result-summary__item">
            <span class="upload-result-icon">🎯</span>
            <div>
              <strong>Axe de progression</strong>
              <p id="uploadResultImprovement">En attente de l analyse.</p>
            </div>
          </div>
          <div class="upload-result-summary__item">
            <span class="upload-result-icon">🛠️</span>
            <div>
              <strong>Action recommandee</strong>
              <p id="uploadResultRecommendation">En attente de l analyse.</p>
            </div>
          </div>
        </div>
        <div class="upload-result-meta">
          <span class="badge badge-blue" id="uploadResultMode">Demo</span>
          <span class="badge badge-success" id="uploadResultConfidence">Confiance --</span>
          <span class="badge badge-purple" id="uploadResultEvidence">Evidence --</span>
        </div>
        <div class="upload-result-actions">
          <button type="button" class="btn btn-outline" onclick="closeModal('uploadModal')">Fermer</button>
          <button type="button" class="btn btn-primary" onclick="refreshTeacherDashboard()">Actualiser le tableau</button>
        </div>
      </div>
      <div class="upload-submit-row">
        <button type="button" class="btn btn-primary<?= !$canUpload ? ' btn-disabled' : '' ?>" id="uploadSubmitBtn" style="width:100%;justify-content:center;" onclick="submitVideoAnalysis()" aria-disabled="<?= !$canUpload ? 'true' : 'false' ?>" title="<?= $canUpload ? 'Lancer l’analyse' : 'MySQL doit être configuré avant l’upload' ?>">Lancer l'analyse IA</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="teacherStudentModal">
  <div class="modal-box teacher-student-modal">
    <button class="modal-close" type="button" onclick="closeModal('teacherStudentModal')">✕</button>
    <div id="teacherStudentModalContent"></div>
  </div>
</div>

<div class="modal-overlay" id="teacherStudentsDirectoryModal">
  <div class="modal-box teacher-students-directory-modal">
    <button class="modal-close" type="button" onclick="closeModal('teacherStudentsDirectoryModal')">✕</button>
    <div class="teacher-directory-header">
      <div>
        <div class="heading-md">Tous les élèves</div>
        <div class="text-sm text-muted">Consultez rapidement les profils et relancez un upload ciblé.</div>
      </div>
      <div class="search-bar-wrap teacher-directory-search">
        <span class="search-icon">🔍</span>
        <input type="text" class="search-bar" id="teacherDirectorySearch" placeholder="Rechercher un élève...">
      </div>
    </div>
    <div id="teacherDirectoryList" class="teacher-directory-list"></div>
  </div>
</div>

<?php include __DIR__ . '/../../components/settings_modal.php'; ?>

<style>
.card-header-row { display:flex;align-items:center;justify-content:space-between; }
.mt-6 { margin-top:6px; }
.activity-item { display:flex;gap:12px;align-items:flex-start; }
.activity-icon { width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0; }
.talents-banner { background:linear-gradient(135deg,rgba(200,16,46,.05),rgba(200,16,46,.1));border:1px solid rgba(200,16,46,.15);border-radius:var(--radius-xl);padding:24px 28px;display:flex;align-items:center;gap:24px;flex-wrap:wrap; }
.talents-banner__avatars { display:flex;gap:-8px;margin-left:auto; }
.talents-banner__avatars .avatar { margin-left:-8px;border:2px solid white; }
.teacher-latest-analysis { padding:14px 16px;border-radius:20px;background:rgba(255,255,255,.62);border:1px solid rgba(10,10,15,.06);margin-bottom:16px; }
.teacher-meta-row { display:flex;justify-content:space-between;gap:12px;margin-top:10px;font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.06em; }
.upload-agent-note { display:flex;flex-direction:column;gap:6px;padding:14px 16px;border-radius:18px;background:rgba(255,255,255,.68);border:1px solid rgba(10,10,15,.06);font-size:12px;color:var(--ink-60); }
.upload-agent-note strong { color:var(--ink);font-size:13px; }
.empty-state { padding:16px;border-radius:18px;background:rgba(255,255,255,.62);border:1px dashed rgba(10,10,15,.08);text-align:center;color:var(--muted);font-size:13px; }
.teacher-dashboard-view { display:none; }
.teacher-dashboard-view.is-active { display:block; }
.teacher-dashboard-view--messages { min-height:calc(100vh - var(--nav-height) - 68px); }
.teacher-messages-screen { height:calc(100vh - var(--nav-height) - 68px); min-height:760px; }
.student-item.is-active { padding-left:12px;padding-right:12px;margin:0 -12px;border-radius:22px;background:linear-gradient(135deg,rgba(200,16,46,.08),rgba(255,255,255,.9));border-bottom-color:transparent;box-shadow:var(--shadow-xs); }
#uploadModal { align-items:flex-start; overflow-y:auto; padding:18px 12px; }
.upload-modal-box { width:640px; max-width:min(94vw, 640px); margin:0 auto; max-height:none; }
.upload-form { display:flex; flex-direction:column; gap:0; }
.upload-form .upload-drop { padding:30px 20px; min-height:220px; display:flex; flex-direction:column; justify-content:center; }
.upload-result-card { margin-top:16px; padding:18px; border-radius:24px; background:linear-gradient(180deg, rgba(255,255,255,.84), rgba(247,243,239,.92)); border:1px solid rgba(10,10,15,.06); box-shadow:var(--shadow-xs); }
.upload-result-card__header { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:14px; }
.upload-result-score { min-width:78px; padding:14px 16px; border-radius:20px; background:var(--red-light); color:var(--red); font-family:var(--font-display); font-size:28px; font-weight:700; text-align:center; line-height:1; }
.upload-result-summary { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:12px; }
.upload-result-summary__item { display:flex; gap:10px; padding:14px; border-radius:18px; background:rgba(255,255,255,.78); border:1px solid rgba(10,10,15,.05); }
.upload-result-summary__item strong { display:block; font-size:12px; margin-bottom:4px; }
.upload-result-summary__item p { margin:0; font-size:13px; color:var(--ink-60); line-height:1.55; }
.upload-result-icon { width:34px; height:34px; border-radius:12px; display:flex; align-items:center; justify-content:center; background:var(--surface); flex-shrink:0; font-size:16px; }
.upload-result-meta { display:flex; gap:8px; flex-wrap:wrap; margin-top:14px; }
.upload-result-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:16px; flex-wrap:wrap; }
.upload-submit-row { position:sticky; bottom:-1px; margin-top:16px; padding-top:14px; background:linear-gradient(180deg, rgba(252,248,245,0), rgba(252,248,245,.88) 18%, rgba(252,248,245,.98) 100%); }
.btn-disabled { opacity:.68; filter:saturate(.78); }
.teacher-student-modal { width:760px; max-width:min(94vw, 760px); }
.teacher-students-directory-modal { width:760px; max-width:min(94vw, 760px); }
.teacher-profile-shell { display:flex; flex-direction:column; gap:16px; }
.teacher-profile-hero { display:flex; gap:18px; align-items:center; margin-bottom:4px; }
.teacher-profile-summary { padding:16px 18px; border-radius:20px; background:rgba(255,255,255,.72); border:1px solid rgba(10,10,15,.06); }
.teacher-profile-kpis { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:12px; }
.teacher-profile-kpi { padding:14px; border-radius:18px; background:rgba(255,255,255,.72); border:1px solid rgba(10,10,15,.06); }
.teacher-profile-kpi strong { display:block; font-size:24px; margin-bottom:4px; }
.teacher-profile-kpi span { font-size:12px; color:var(--muted); }
.teacher-profile-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:12px; }
.teacher-profile-panel { padding:16px 18px; border-radius:20px; background:rgba(255,255,255,.72); border:1px solid rgba(10,10,15,.06); }
.teacher-profile-panel ul { margin:10px 0 0; padding-left:18px; color:var(--ink-60); }
.teacher-profile-panel li + li { margin-top:7px; }
.teacher-profile-video { width:100%; border-radius:20px; overflow:hidden; background:#000; box-shadow:var(--shadow-xs); }
.teacher-profile-video video { width:100%; max-height:280px; display:block; }
.teacher-directory-header { display:flex; align-items:flex-end; justify-content:space-between; gap:16px; margin-bottom:18px; flex-wrap:wrap; }
.teacher-directory-search { width:min(100%, 320px); }
.teacher-directory-list { display:flex; flex-direction:column; gap:10px; max-height:min(70vh, 620px); overflow:auto; padding-right:4px; }
.teacher-directory-item { display:flex; align-items:center; gap:14px; padding:14px 16px; border-radius:22px; background:rgba(255,255,255,.76); border:1px solid rgba(10,10,15,.06); }
.teacher-directory-meta { display:flex; flex-wrap:wrap; gap:8px; margin-top:4px; font-size:12px; color:var(--muted); }
.teacher-directory-actions { display:flex; gap:8px; flex-wrap:wrap; }
</style>

<style>
.card-header-row {
  gap: 12px;
  flex-wrap: wrap;
}

.activity-item {
  padding: 10px 0;
}

.activity-icon {
  width: 38px;
  height: 38px;
  border-radius: 14px;
  box-shadow: var(--shadow-xs);
}

.talents-banner {
  background: linear-gradient(135deg, rgba(200,16,46,.12), rgba(255,255,255,.82));
  border: 1px solid rgba(255,255,255,.86);
  box-shadow: var(--shadow-md);
}

.talents-banner__left {
  max-width: 420px;
}

#studentsList .student-item {
  transition: transform .2s ease, opacity .2s ease;
}

#studentsList .student-item:hover {
  transform: translateX(4px);
}

@media (max-width: 720px) {
  .upload-result-summary { grid-template-columns:1fr; }
  .upload-result-card__header { flex-direction:column; }
  .teacher-profile-kpis,
  .teacher-profile-grid { grid-template-columns:1fr; }
  .teacher-messages-screen { min-height:640px; height:auto; }
}
</style>

<?php include __DIR__ . '/../../components/scripts.php'; ?>
<script>
function normalizeValue(value) {
  return (value || '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '');
}

const teacherStudentsDirectory = <?= json_encode($teacherStudentDirectory, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const teacherStudentsMap = new Map(teacherStudentsDirectory.map(student => [String(student.id), student]));
let classChart = null;

function setTeacherDashboardView(viewId) {
  const nextViewId = viewId === 'teacherMessagesView' ? 'teacherMessagesView' : 'teacherHomeView';

  document.querySelectorAll('.teacher-dashboard-view').forEach(view => {
    view.classList.toggle('is-active', view.id === nextViewId);
  });

  if (nextViewId === 'teacherMessagesView') {
    document.getElementById('teacherMessagesView')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    document.getElementById('teacherMessagesPanel')?.openSmartChat?.();
  }
}

window.handleDashboardTarget = function(targetId) {
  if (targetId === 'teacherMessagesView') {
    setTeacherDashboardView('teacherMessagesView');
    return true;
  }

  const homeTargets = ['teacherOverview', 'teacherStudents', 'teacherActivity', 'teacherScores'];

  if (!homeTargets.includes(targetId)) {
    return false;
  }

  setTeacherDashboardView('teacherHomeView');

  window.requestAnimationFrame(() => {
    document.getElementById(targetId)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });

  return true;
};

if (typeof Chart === 'undefined') {
  renderChartFallback('classChart', 'Le graphique de la classe est indisponible hors ligne.');
} else {
  classChart = new Chart(document.getElementById('classChart'), {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_map(fn($student) => explode(' ', $student['name'])[0], $students), JSON_UNESCAPED_UNICODE) ?>,
      datasets: [{
        data: <?= json_encode(array_column($students, 'score')) ?>,
        backgroundColor: <?= json_encode(array_map(fn($student) => scoreColor($student['score']), $students)) ?>,
        borderRadius: 6,
        borderSkipped: false,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: { y: { min: 0, max: 100 }, x: { grid: { display: false } } }
    }
  });
}

function filterStudents(query) {
  const normalizedQuery = normalizeValue(query);
  document.querySelectorAll('#studentsList .student-item').forEach(item => {
    const matches = normalizeValue(item.dataset.name).includes(normalizedQuery);
    item.style.display = matches ? '' : 'none';
  });
}

function getTeacherStudent(studentId) {
  return teacherStudentsMap.get(String(studentId || '')) || null;
}

function markActiveStudent(studentId) {
  const selectedId = String(studentId || '');
  document.querySelectorAll('#studentsList .student-item').forEach(item => {
    item.classList.toggle('is-active', item.dataset.studentId === selectedId);
  });
}

function bindTeacherStudentActions(scope = document) {
  scope.querySelectorAll('[data-open-students-directory]').forEach(button => {
    if (button.dataset.boundClick === 'true') {
      return;
    }

    button.dataset.boundClick = 'true';
    button.addEventListener('click', showAllStudents);
  });

  scope.querySelectorAll('[data-student-upload]').forEach(button => {
    if (button.dataset.boundClick === 'true') {
      return;
    }

    button.dataset.boundClick = 'true';
    button.addEventListener('click', () => openUploadModal(button.dataset.studentUpload));
  });

  scope.querySelectorAll('[data-student-profile]').forEach(button => {
    if (button.dataset.boundClick === 'true') {
      return;
    }

    button.dataset.boundClick = 'true';
    button.addEventListener('click', () => focusStudentStats(button.dataset.studentProfile));
  });
}

function renderTeacherDirectory(query = '') {
  const container = document.getElementById('teacherDirectoryList');

  if (!container) {
    return;
  }

  const normalizedQuery = normalizeValue(query);
  const filteredStudents = teacherStudentsDirectory.filter(student => {
    if (normalizedQuery === '') {
      return true;
    }

    const haystack = [student.name, student.ville, student.sport, student.perf_type].join(' ');
    return normalizeValue(haystack).includes(normalizedQuery);
  });

  if (filteredStudents.length === 0) {
    container.innerHTML = '<div class="empty-state">Aucun eleve ne correspond a cette recherche.</div>';
    return;
  }

  container.innerHTML = filteredStudents.map(student => `
    <div class="teacher-directory-item">
      <div class="avatar avatar-md avatar-red">${escapeHtml(student.initials)}</div>
      <div style="flex:1;min-width:0;">
        <div class="student-name">${escapeHtml(student.name)}</div>
        <div class="teacher-directory-meta">
          <span>${escapeHtml(student.age)} ans</span>
          <span>${escapeHtml(student.ville)}</span>
          <span>${escapeHtml(student.sport)}</span>
          <span>${escapeHtml(student.video_count)} videos</span>
        </div>
      </div>
      <div style="text-align:right;display:flex;align-items:center;gap:12px;flex-wrap:wrap;justify-content:flex-end;">
        <span class="badge badge-success">${escapeHtml(student.score)}%</span>
        <div class="teacher-directory-actions">
          <button type="button" class="btn btn-sm btn-outline" data-student-profile="${escapeHtml(student.id)}">Voir stats</button>
          <button type="button" class="btn btn-sm btn-primary" data-student-upload="${escapeHtml(student.id)}">Uploader</button>
        </div>
      </div>
    </div>
  `).join('');

  bindTeacherStudentActions(container);
}

function showAllStudents() {
  const input = document.getElementById('studentSearch');
  if (input) {
    input.value = '';
  }

  filterStudents('');
  renderTeacherDirectory('');
  openModal('teacherStudentsDirectoryModal');

  const directorySearch = document.getElementById('teacherDirectorySearch');
  if (directorySearch) {
    directorySearch.value = '';
    window.setTimeout(() => directorySearch.focus(), 40);
  }

  showToast(`${teacherStudentsDirectory.length} eleves disponibles.`, 'default');
}

function focusStudentStats(studentId) {
  const student = getTeacherStudent(studentId);

  if (!student) {
    showToast('Profil eleve introuvable.', 'error');
    return;
  }

  markActiveStudent(student.id);
  document.getElementById('teacherScores')?.scrollIntoView({ behavior: 'smooth', block: 'start' });

  const input = document.getElementById('studentSearch');
  if (input) {
    input.value = student.name || '';
    filterStudents(input.value);
  }

  if (classChart && classChart.setActiveElements) {
    const labels = classChart.data?.labels || [];
    const firstName = String(student.name || '').split(' ')[0];
    const index = labels.findIndex(label => label === firstName);

    if (index >= 0) {
      classChart.setActiveElements([{ datasetIndex: 0, index }]);
      classChart.update();
    }
  }

  const content = document.getElementById('teacherStudentModalContent');

  if (content) {
    const strengths = (student.strengths || []).map(item => `<li>${escapeHtml(item)}</li>`).join('');
    const improvements = (student.improvements || []).map(item => `<li>${escapeHtml(item)}</li>`).join('');
    const recommendations = (student.recommendations || []).map(item => `<li>${escapeHtml(item)}</li>`).join('');

    content.innerHTML = `
      <div class="teacher-profile-shell">
        <div class="teacher-profile-hero">
          <div class="avatar avatar-xl avatar-red">${escapeHtml(student.initials)}</div>
          <div>
            <h2 class="heading-xl" style="margin-bottom:6px;">${escapeHtml(student.name)}</h2>
            <p class="text-sm text-muted">${escapeHtml(student.age)} ans · ${escapeHtml(student.ville)} · ${escapeHtml(student.sport)}</p>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;">
              <span class="badge badge-success">Score global ${escapeHtml(student.score)}%</span>
              <span class="badge badge-blue">${escapeHtml(student.perf_type)}</span>
              <span class="badge badge-purple">${escapeHtml(student.analysis_provider)}</span>
            </div>
          </div>
        </div>
        ${student.video_url ? `
          <div class="teacher-profile-video">
            <video src="${escapeHtml(student.video_url)}" controls preload="metadata" playsinline></video>
          </div>
        ` : ''}
        <div class="teacher-profile-summary">
          <div class="heading-md" style="margin-bottom:8px;">Lecture IA</div>
          <p class="text-sm text-muted">${escapeHtml(student.summary || 'Aucun resume IA disponible pour le moment.')}</p>
        </div>
        <div class="teacher-profile-kpis">
          <div class="teacher-profile-kpi"><strong style="color:${escapeHtml(student.score_color)};">${escapeHtml(student.score)}%</strong><span>Score global</span></div>
          <div class="teacher-profile-kpi"><strong>${escapeHtml(student.video_count)}</strong><span>Videos analysees</span></div>
          <div class="teacher-profile-kpi"><strong>${escapeHtml(student.updated_label)}</strong><span>Derniere analyse (${escapeHtml(student.updated_ago)})</span></div>
          <div class="teacher-profile-kpi"><strong style="color:#FF8F00;">${escapeHtml(student.vitesse)}</strong><span>Vitesse</span></div>
          <div class="teacher-profile-kpi"><strong style="color:var(--blue);">${escapeHtml(student.coordination)}</strong><span>Coordination</span></div>
          <div class="teacher-profile-kpi"><strong style="color:var(--green);">${escapeHtml(student.endurance)}</strong><span>Endurance</span></div>
          <div class="teacher-profile-kpi"><strong style="color:var(--red);">${escapeHtml(student.force)}</strong><span>Force</span></div>
          <div class="teacher-profile-kpi"><strong style="color:var(--cyan);">${escapeHtml(student.souplesse)}</strong><span>Souplesse</span></div>
          <div class="teacher-profile-kpi"><strong>${escapeHtml(student.perf_type)}</strong><span>Performance suivie</span></div>
        </div>
        <div class="teacher-profile-grid">
          <div class="teacher-profile-panel">
            <div class="heading-md">Points forts</div>
            ${strengths ? `<ul>${strengths}</ul>` : '<p class="text-sm text-muted" style="margin-top:10px;">Aucun point fort detaille.</p>'}
          </div>
          <div class="teacher-profile-panel">
            <div class="heading-md">Axes de progression</div>
            ${improvements ? `<ul>${improvements}</ul>` : '<p class="text-sm text-muted" style="margin-top:10px;">Aucun axe detaille.</p>'}
          </div>
          <div class="teacher-profile-panel" style="grid-column:1 / -1;">
            <div class="heading-md">Actions recommandees</div>
            ${recommendations ? `<ul>${recommendations}</ul>` : '<p class="text-sm text-muted" style="margin-top:10px;">Aucune recommandation detaillee.</p>'}
          </div>
        </div>
        <div class="upload-result-actions" style="margin-top:0;">
          <button type="button" class="btn btn-outline" data-profile-focus-list="${escapeHtml(student.id)}">Voir dans la liste</button>
          <button type="button" class="btn btn-primary" data-profile-upload="${escapeHtml(student.id)}">Uploader une video</button>
        </div>
      </div>
    `;

    content.querySelector('[data-profile-focus-list]')?.addEventListener('click', () => {
      closeModal('teacherStudentModal');
      document.getElementById('teacherStudents')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    content.querySelector('[data-profile-upload]')?.addEventListener('click', event => {
      const nextStudentId = event.currentTarget?.dataset.profileUpload || student.id;
      closeModal('teacherStudentModal');
      closeModal('teacherStudentsDirectoryModal');
      openUploadModal(nextStudentId);
    });
  }

  closeModal('teacherStudentsDirectoryModal');
  openModal('teacherStudentModal');
  showToast(`Statistiques de ${student.name} affichees.`, 'default');
}

const uploadConfig = {
  endpoint: <?= json_encode(APP_URL . '/api/index.php') ?>,
  frameLimit: <?= (int) AI_FRAME_LIMIT ?>,
  frameWidth: <?= (int) AI_FRAME_IMAGE_WIDTH ?>,
  candidateMultiplier: 3,
  canUpload: <?= $canUpload ? 'true' : 'false' ?>,
};

function openUploadModal(studentId = null) {
  if (studentId) {
    const select = document.getElementById('uploadStudentSelect');
    if (select) {
      select.value = String(studentId);
    }
  }

  resetUploadResult();
  openModal('uploadModal');
}

function setUploadStatus(message) {
  const note = document.getElementById('uploadStatusNote');
  if (note) {
    note.textContent = message || '';
  }
}

function handleFileSelect(input) {
  if (input.files[0]) {
    document.getElementById('uploadFileName').textContent = input.files[0].name;
    setUploadStatus('Extraction des images cles prete a etre lancee.');
    resetUploadResult();
  }
}

function resetUploadResult() {
  const panel = document.getElementById('uploadResultPanel');
  if (panel) {
    panel.hidden = true;
  }
}

function refreshTeacherDashboard() {
  const nextUrl = new URL(window.location.href);
  nextUrl.searchParams.set('msg', 'Analyse IA terminee');
  window.location.href = nextUrl.toString();
}

function setResultText(id, value, fallback) {
  const node = document.getElementById(id);
  if (!node) {
    return;
  }

  node.textContent = value && String(value).trim() !== '' ? String(value).trim() : fallback;
}

function renderUploadAnalysisSummary(video, mode) {
  const panel = document.getElementById('uploadResultPanel');

  if (!panel || !video) {
    return;
  }

  const strengths = Array.isArray(video.strengths) ? video.strengths : [];
  const improvements = Array.isArray(video.improvements) ? video.improvements : [];
  const recommendations = Array.isArray(video.coach_recommendations) ? video.coach_recommendations : [];
  const confidence = Number.isFinite(Number(video.confidence)) ? `${Number(video.confidence)}%` : 'n/d';
  const evidence = video.evidence_quality ? String(video.evidence_quality) : 'standard';

  setResultText('uploadResultTitle', `${video.student_name || 'Eleve'} · ${video.perf_type || 'Performance'}`, 'Analyse terminee');
  setResultText('uploadResultScore', `${video.score_global ?? '--'}%`, '--');
  setResultText('uploadResultSummary', video.summary, 'Le modele a termine l analyse, mais aucun resume n a ete retourne.');
  setResultText('uploadResultStrength', strengths[0], 'Aucun point fort specifique remonte.');
  setResultText('uploadResultImprovement', improvements[0], 'Aucun axe de progression specifique remonte.');
  setResultText('uploadResultRecommendation', recommendations[0], 'Aucune recommandation immediate remontee.');
  setResultText('uploadResultMode', mode === 'openai' ? 'OpenAI' : 'Demo', 'Mode inconnu');
  setResultText('uploadResultConfidence', `Confiance ${confidence}`, 'Confiance n/d');
  setResultText('uploadResultEvidence', `Evidence ${evidence}`, 'Evidence standard');

  panel.hidden = false;
}

function setUploadProgress(value) {
  const progress = document.getElementById('uploadProgress');
  const bar = document.getElementById('uploadBar');
  const pct = document.getElementById('uploadPct');
  progress.style.display = 'block';
  bar.style.width = `${value}%`;
  pct.textContent = `${Math.round(value)}%`;
}

function createVideoElement(file) {
  return new Promise((resolve, reject) => {
    const url = URL.createObjectURL(file);
    const video = document.createElement('video');
    video.preload = 'metadata';
    video.muted = true;
    video.playsInline = true;
    video.src = url;

    video.onloadedmetadata = () => resolve({ video, url });
    video.onerror = () => reject(new Error('Impossible de lire cette video dans le navigateur.'));
  });
}

function captureVideoFrame(video, time, canvas, context, sampleCanvas, sampleContext, previousSignature) {
  return new Promise((resolve, reject) => {
    const onSeeked = () => {
      try {
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        sampleContext.drawImage(canvas, 0, 0, sampleCanvas.width, sampleCanvas.height);
        const metrics = analyzeFrameSample(sampleContext, sampleCanvas.width, sampleCanvas.height, previousSignature);

        resolve({
          image: canvas.toDataURL('image/jpeg', 0.82),
          signature: metrics.signature,
          sharpness: metrics.sharpness,
          motion: metrics.motion,
          brightness: metrics.brightness,
        });
      } catch (error) {
        reject(error);
      }
    };

    video.addEventListener('seeked', onSeeked, { once: true });
    video.currentTime = Math.min(Math.max(time, 0), Math.max(video.duration - 0.05, 0));
  });
}

function analyzeFrameSample(sampleContext, width, height, previousSignature) {
  const pixels = sampleContext.getImageData(0, 0, width, height).data;
  const signature = new Float32Array(width * height);
  let brightnessTotal = 0;
  let sharpnessTotal = 0;
  let motionTotal = 0;

  for (let y = 0; y < height; y += 1) {
    for (let x = 0; x < width; x += 1) {
      const pixelIndex = ((y * width) + x) * 4;
      const signatureIndex = (y * width) + x;
      const gray = (pixels[pixelIndex] * 0.299) + (pixels[pixelIndex + 1] * 0.587) + (pixels[pixelIndex + 2] * 0.114);

      signature[signatureIndex] = gray;
      brightnessTotal += gray;

      if (x > 0) {
        sharpnessTotal += Math.abs(gray - signature[signatureIndex - 1]);
      }

      if (y > 0) {
        sharpnessTotal += Math.abs(gray - signature[signatureIndex - width]);
      }

      if (previousSignature) {
        motionTotal += Math.abs(gray - previousSignature[signatureIndex]);
      }
    }
  }

  const pixelCount = Math.max(signature.length, 1);
  const edgeCount = Math.max(((width - 1) * height) + ((height - 1) * width), 1);

  return {
    signature,
    brightness: Math.min(1, (brightnessTotal / pixelCount) / 255),
    sharpness: Math.min(1, (sharpnessTotal / edgeCount) / 255),
    motion: previousSignature ? Math.min(1, (motionTotal / pixelCount) / 255) : 0,
  };
}

function buildCandidateTimestamps(duration, candidateCount) {
  const timestamps = [];

  if (!Number.isFinite(duration) || duration <= 0) {
    return new Array(candidateCount).fill(0);
  }

  const safeLead = Math.min(duration * 0.08, 0.8);
  const safeTail = Math.min(duration * 0.08, 0.8);
  const start = Math.min(safeLead, Math.max(duration * 0.2, 0));
  const end = Math.max(duration - safeTail, start);

  for (let index = 0; index < candidateCount; index += 1) {
    const ratio = candidateCount === 1 ? 0.5 : index / (candidateCount - 1);
    timestamps.push(start + ((end - start) * ratio));
  }

  return timestamps;
}

function pickBestFrame(candidates, selectedIndices, { start = 0, end = candidates.length - 1, minGap = 1, prefer = 'importance' } = {}) {
  let winner = null;

  for (let index = start; index <= end; index += 1) {
    if (selectedIndices.has(index)) {
      continue;
    }

    let isFarEnough = true;
    selectedIndices.forEach(selectedIndex => {
      if (Math.abs(selectedIndex - index) < minGap) {
        isFarEnough = false;
      }
    });

    if (!isFarEnough) {
      continue;
    }

    const candidate = candidates[index];

    if (!winner) {
      winner = candidate;
      continue;
    }

    if ((prefer === 'motion' && candidate.motionScore > winner.motionScore)
      || (prefer === 'sharpness' && candidate.sharpnessScore > winner.sharpnessScore)
      || (prefer === 'importance' && candidate.importance > winner.importance)) {
      winner = candidate;
    }
  }

  return winner;
}

function selectSmartFrames(candidates, frameLimit) {
  if (candidates.length <= frameLimit) {
    return candidates.map((candidate, index) => ({
      ...candidate,
      role: index === 0 ? 'opening' : (index === candidates.length - 1 ? 'closing' : 'transition'),
    }));
  }

  const selected = [];
  const selectedIndices = new Set();
  const minGap = Math.max(1, Math.floor(candidates.length / Math.max(frameLimit + 2, 3)));

  const addCandidate = (candidate, role) => {
    if (!candidate || selectedIndices.has(candidate.index)) {
      return;
    }

    selected.push({ ...candidate, role });
    selectedIndices.add(candidate.index);
  };

  addCandidate(pickBestFrame(candidates, selectedIndices, { start: 0, end: Math.floor(candidates.length / 3), minGap, prefer: 'sharpness' }), 'opening');
  addCandidate(pickBestFrame(candidates, selectedIndices, { minGap, prefer: 'motion' }), 'peak_action');
  addCandidate(pickBestFrame(candidates, selectedIndices, { start: Math.floor(candidates.length / 3), end: Math.max(Math.floor((candidates.length * 2) / 3), 0), minGap, prefer: 'importance' }), 'transition');
  addCandidate(pickBestFrame(candidates, selectedIndices, { start: Math.max(candidates.length - Math.floor(candidates.length / 3) - 1, 0), end: candidates.length - 1, minGap, prefer: 'sharpness' }), 'closing');

  const byImportance = [...candidates].sort((left, right) => right.importance - left.importance);

  for (const candidate of byImportance) {
    if (selected.length >= frameLimit) {
      break;
    }

    let farEnough = true;
    selectedIndices.forEach(selectedIndex => {
      if (Math.abs(selectedIndex - candidate.index) < minGap) {
        farEnough = false;
      }
    });

    if (!farEnough || selectedIndices.has(candidate.index)) {
      continue;
    }

    addCandidate(candidate, candidate.motionScore >= candidate.sharpnessScore ? 'dynamic' : 'detail');
  }

  for (const candidate of candidates) {
    if (selected.length >= frameLimit) {
      break;
    }

    addCandidate(candidate, 'transition');
  }

  return selected
    .sort((left, right) => left.timestamp - right.timestamp)
    .map((candidate, index, items) => ({
      ...candidate,
      role: index === 0 ? 'opening' : (index === items.length - 1 ? 'closing' : candidate.role),
    }));
}

async function extractVideoFrames(file) {
  const { video, url } = await createVideoElement(file);

  try {
    const width = video.videoWidth || uploadConfig.frameWidth;
    const height = video.videoHeight || Math.round(uploadConfig.frameWidth * 0.56);
    const scaledWidth = Math.max(1, Math.min(width, uploadConfig.frameWidth));
    const scaledHeight = Math.max(1, Math.round((height / Math.max(width, 1)) * scaledWidth));
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d', { willReadFrequently: true });
    const sampleCanvas = document.createElement('canvas');
    const sampleContext = sampleCanvas.getContext('2d', { willReadFrequently: true });

    canvas.width = scaledWidth;
    canvas.height = scaledHeight;
    sampleCanvas.width = 64;
    sampleCanvas.height = Math.max(36, Math.round((scaledHeight / Math.max(scaledWidth, 1)) * 64));

    const duration = Number(video.duration || 0);
    const candidateCount = Math.max(uploadConfig.frameLimit * uploadConfig.candidateMultiplier, uploadConfig.frameLimit + 4);
    const candidateTimestamps = buildCandidateTimestamps(duration, candidateCount);
    const candidates = [];
    let previousSignature = null;

    for (let index = 0; index < candidateTimestamps.length; index += 1) {
      const timestamp = candidateTimestamps[index];
      const capture = await captureVideoFrame(video, timestamp, canvas, context, sampleCanvas, sampleContext, previousSignature);
      const ratio = duration > 0 ? timestamp / duration : 0;
      const brightnessBias = Math.abs(0.52 - capture.brightness);
      const importance = (capture.sharpness * 0.4) + (capture.motion * 0.45) + ((1 - brightnessBias) * 0.15);

      candidates.push({
        index,
        image: capture.image,
        timestamp,
        ratio,
        motion: Number(capture.motion.toFixed(4)),
        sharpness: Number(capture.sharpness.toFixed(4)),
        brightness: Number(capture.brightness.toFixed(4)),
        motionScore: capture.motion,
        sharpnessScore: capture.sharpness,
        importance,
      });

      previousSignature = capture.signature;
      setUploadProgress(8 + (((index + 1) / candidateTimestamps.length) * 32));
    }

    const selectedFrames = selectSmartFrames(candidates, uploadConfig.frameLimit);
    const averageMotion = candidates.reduce((sum, candidate) => sum + candidate.motionScore, 0) / Math.max(candidates.length, 1);
    const averageSharpness = candidates.reduce((sum, candidate) => sum + candidate.sharpnessScore, 0) / Math.max(candidates.length, 1);

    return {
      frames: selectedFrames.map(frame => ({
        image: frame.image,
        timestamp: Number(frame.timestamp.toFixed(2)),
        ratio: Number(frame.ratio.toFixed(4)),
        motion: frame.motion,
        sharpness: frame.sharpness,
        brightness: frame.brightness,
        role: frame.role,
      })),
      meta: {
        duration,
        width,
        height,
        candidate_count: candidates.length,
        selection_strategy: 'smart-motion-sharpness-diversity',
        average_motion: Number(averageMotion.toFixed(4)),
        average_sharpness: Number(averageSharpness.toFixed(4)),
      },
    };
  } finally {
    URL.revokeObjectURL(url);
  }
}

async function submitVideoAnalysis() {
  if (!uploadConfig.canUpload) {
    showToast('MySQL doit etre configure pour activer les uploads.', 'error');
    return;
  }

  const form = document.getElementById('uploadForm');
  const fileInput = document.getElementById('videoInput');
  const submitButton = document.getElementById('uploadSubmitBtn');

  if (!form || !fileInput || !fileInput.files[0]) {
    showToast('Selectionnez une video avant de lancer l’analyse.', 'warning');
    return;
  }

  submitButton.disabled = true;
  setUploadProgress(5);
  setUploadStatus('Extraction des images cles pour l’agent IA...');

  let extracted;

  try {
    extracted = await extractVideoFrames(fileInput.files[0]);
  } catch (error) {
    submitButton.disabled = false;
    setUploadStatus('');
    showToast(error.message || 'Extraction video impossible.', 'error');
    return;
  }

  setUploadProgress(42);
  setUploadStatus('Upload de la video et des images cles en cours...');

  const formData = new FormData(form);
  formData.append('frames', JSON.stringify(extracted.frames));
  formData.append('video_meta', JSON.stringify(extracted.meta));

  const request = new XMLHttpRequest();
  request.open('POST', uploadConfig.endpoint, true);

  request.upload.addEventListener('progress', event => {
    if (!event.lengthComputable) {
      return;
    }

    const pct = 45 + ((event.loaded / event.total) * 35);
    setUploadProgress(Math.min(pct, 80));
  });

  request.addEventListener('load', () => {
    submitButton.disabled = false;

    let response;

    try {
      response = JSON.parse(request.responseText || '{}');
    } catch (error) {
      showToast('Reponse serveur invalide.', 'error');
      return;
    }

    if (request.status < 200 || request.status >= 300 || !response.success) {
      setUploadStatus('');
      showToast(response.error || 'L’analyse IA a echoue.', 'error');
      return;
    }

    setUploadProgress(100);
    const mode = response.data?.mode === 'openai' ? 'openai' : 'demo';
    const modeLabel = mode === 'openai' ? 'OpenAI' : 'demo';
    const score = response.data?.video?.score_global ?? '';
    const message = score !== '' ? `Analyse ${modeLabel} terminee · score ${score}%` : `Analyse ${modeLabel} terminee`;

    setUploadStatus('Analyse terminee. Resume pret ci-dessous.');
    renderUploadAnalysisSummary(response.data?.video || null, mode);
    showToast(message, 'success');
  });

  request.addEventListener('error', () => {
    submitButton.disabled = false;
    setUploadStatus('');
    showToast('Erreur reseau pendant l’upload.', 'error');
  });

  request.send(formData);
}

function simulateUpload() {
  const progress = document.getElementById('uploadProgress');
  const bar = document.getElementById('uploadBar');
  const pct = document.getElementById('uploadPct');
  progress.style.display = 'block';

  let value = 0;
  const timer = setInterval(() => {
    value += Math.random() * 15;
    if (value >= 100) {
      value = 100;
      clearInterval(timer);
      setTimeout(() => {
        closeModal('uploadModal');
        showToast('Analyse IA terminée. Score généré avec succès.', 'success');
      }, 500);
    }

    bar.style.width = value + '%';
    pct.textContent = Math.round(value) + '%';
  }, 200);
}

function exportTeacherPdf() {
  const rows = <?= json_encode(array_map(function ($student) {
      return [
          'name' => $student['name'],
          'sport' => $student['sport'],
          'ville' => $student['ville'],
          'score' => (int) $student['score'],
      ];
  }, $students), JSON_UNESCAPED_UNICODE) ?>;

  const tableRows = rows.map(row => `
    <tr>
      <td>${escapeHtml(row.name)}</td>
      <td>${escapeHtml(row.sport)}</td>
      <td>${escapeHtml(row.ville)}</td>
      <td>${escapeHtml(row.score)}%</td>
    </tr>
  `).join('');

  exportDashboardPdf(
    'Rapport Professeur',
    <?= json_encode($teacherContext . ' · ' . formatLongDate('now', true), JSON_UNESCAPED_UNICODE) ?>,
    [
      {
        heading: 'Indicateurs clés',
        content: `
          <div class="metric-grid">
            <div class="metric-card"><strong>${rows.length}</strong><span>Élèves suivis</span></div>
            <div class="metric-card"><strong><?= count($topTalents) ?></strong><span>Talents détectés</span></div>
            <div class="metric-card"><strong><?= (int) $teacherStats['videos'] ?></strong><span>Vidéos analysées</span></div>
            <div class="metric-card"><strong><?= (int) $teacherStats['avg_score'] ?>%</strong><span>Score moyen</span></div>
          </div>
        `
      },
      {
        heading: 'Élèves',
        content: `<table><thead><tr><th>Nom</th><th>Sport</th><th>Ville</th><th>Score</th></tr></thead><tbody>${tableRows}</tbody></table>`
      }
    ]
  );
}

bindTeacherStudentActions();

document.getElementById('teacherDirectorySearch')?.addEventListener('input', event => {
  renderTeacherDirectory(event.target.value || '');
});
</script>
