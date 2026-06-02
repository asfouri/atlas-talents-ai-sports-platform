<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
Auth::init();

if (Auth::isLoggedIn()) {
    header('Location: ' . atlasDashboardUrlForRole(Auth::user()['role']));
    exit;
}

$pageTitle = 'Détection de Talents Sportifs par IA';
$navType   = 'public';
$bodyClass = 'landing-body';
include __DIR__ . '/components/head.php';
?>

<?php include __DIR__ . '/components/navbar.php'; ?>

<main class="landing-main">

<!-- ═══ HERO ═══ -->
<section class="hero" id="hero">
  <div class="hero__bg">
    <div class="hero__orb hero__orb--1"></div>
    <div class="hero__orb hero__orb--2"></div>
    <div class="hero__grid"></div>
  </div>

  <div class="hero__container">
    <div class="hero__content">
      <div class="hero__badge anim-fade-up">
        <span class="badge-dot"></span>
        <span>Innovation Sportive au Maroc 🇲🇦</span>
      </div>

      <h1 class="hero__title anim-fade-up delay-1">
        Détection de<br>
        <span class="hero__title-accent">Talents Sportifs</span><br>
        par Intelligence Artificielle
      </h1>

      <p class="hero__desc anim-fade-up delay-2">
        La première plateforme marocaine qui connecte professeurs d'EPS, recruteurs
        et clubs grâce à l'IA pour identifier et développer les champions de demain.
      </p>

      <div class="hero__actions anim-fade-up delay-3">
        <a href="<?= APP_URL ?>/pages/auth/login.php" class="btn btn-primary btn-lg">
          Commencer maintenant
          <span>→</span>
        </a>
        <a href="#solution" class="btn btn-outline btn-lg">
          Voir la démo
        </a>
      </div>

      <div class="hero__stats anim-fade-up delay-4">
        <div class="hero__stat">
          <span class="hero__stat-num">500+</span>
          <span class="hero__stat-label">Talents détectés</span>
        </div>
        <div class="hero__stat-divider"></div>
        <div class="hero__stat">
          <span class="hero__stat-num">120+</span>
          <span class="hero__stat-label">Écoles partenaires</span>
        </div>
        <div class="hero__stat-divider"></div>
        <div class="hero__stat">
          <span class="hero__stat-num">40+</span>
          <span class="hero__stat-label">Clubs sportifs</span>
        </div>
        <div class="hero__stat-divider"></div>
        <div class="hero__stat">
          <span class="hero__stat-num">94%</span>
          <span class="hero__stat-label">Précision IA</span>
        </div>
      </div>
    </div>

    <div class="hero__visual anim-scale-in delay-2">
      <div class="hero__card hero__card--main">
        <div class="hero__card-header">
          <div class="hero__ai-badge">🤖 Analyse IA en cours...</div>
          <div class="hero__ai-pulse"></div>
        </div>
        <div class="hero__athlete">
          <div class="avatar avatar-lg avatar-red">YA</div>
          <div>
            <div class="hero__athlete-name">Youssef El Amrani</div>
            <div class="hero__athlete-sub">Athlétisme · Sprint · Casablanca</div>
            <span class="badge badge-new">✓ Talent Détecté</span>
          </div>
        </div>
        <div class="hero__score-big">
          <span class="hero__score-num">87</span>
          <span class="hero__score-label">Score Global IA</span>
        </div>
        <div class="hero__criteria">
          <?php
          $criteria = [
            ['Vitesse','92','#FF8F00'],['Coordination','88','#1565C0'],
            ['Endurance','85','#2E7D32'],['Force','79','#9C27B0'],['Souplesse','82','#00ACC1']
          ];
          foreach ($criteria as [$label,$val,$color]):
          ?>
          <div class="hero__crit-row">
            <span class="hero__crit-label"><?= $label ?></span>
            <div class="hero__crit-track">
              <div class="hero__crit-fill" style="width:<?= $val ?>%;background:<?= $color ?>;"></div>
            </div>
            <span class="hero__crit-val" style="color:<?= $color ?>;"><?= $val ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="hero__card hero__card--mini hero__card--mini-1">
        <div class="hero__mini-icon" style="background:#E8F5ED;color:#2E7D32;">📈</div>
        <div>
          <div class="hero__mini-num">+27pts</div>
          <div class="hero__mini-label">Progression 6 mois</div>
        </div>
      </div>
      <div class="hero__card hero__card--mini hero__card--mini-2">
        <div class="hero__mini-icon" style="background:#FFF0F1;color:#C8102E;">⚡</div>
        <div>
          <div class="hero__mini-num">2 min</div>
          <div class="hero__mini-label">Analyse IA rapide</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Scroll indicator -->
  <div class="hero__scroll">
    <div class="hero__scroll-line"></div>
    <span>Découvrir</span>
  </div>
</section>

<!-- ═══ TRUSTED BY ═══ -->
<section class="trusted">
  <div class="trusted__inner">
    <p class="trusted__label">Clubs et fédérations partenaires</p>
    <div class="trusted__marquee-wrap">
      <?php $clubs = ['Raja CA','Wydad AC','AS FAR','FUS Rabat','Kawkab Marrakech','MAS Fès','RS Berkane','Ittihad Tanger','OCK','RCA Rabat']; ?>
      <div class="trusted__marquee-track">
        <?php foreach ($clubs as $club): ?>
        <div class="trusted__logo"><?= $club ?></div>
        <?php endforeach; ?>
        <?php foreach ($clubs as $club): ?>
        <div class="trusted__logo"><?= $club ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- ═══ FEATURES ═══ -->
<section class="section features" id="features">
  <div class="section__container">
    <div class="section__header">
      <span class="section__eyebrow">Fonctionnalités</span>
      <h2 class="display-md">Une Solution Complète</h2>
      <p class="text-lg text-muted">De la détection à la formation, accompagnez chaque talent dans son parcours sportif</p>
    </div>

    <div class="features__grid">
      <?php
      $features = [
        ['🎥','Analyse Vidéo IA','red','Uploadez les performances de vos élèves. Notre IA analyse 5 critères physiques clés en 2 minutes avec une précision de 94%.',['Vitesse','Coordination','Endurance','Force','Souplesse']],
        ['👥','Réseau Social Sportif','blue','Connectez professeurs d\'EPS, recruteurs et clubs sur une plateforme collaborative dédiée au développement des jeunes talents marocains.',['Prof EPS','Recruteurs','Clubs','Fédérations']],
        ['📊','Dashboard Coach IA','green','Suivez l\'évolution de chaque talent avec des graphiques détaillés, recommandations personnalisées et analyses de progression.',['Suivi','Graphiques','Rapports','Objectifs']],
        ['🔍','Détection Intelligente','purple','Algorithme IA entraîné sur des milliers d\'athlètes marocains pour des résultats pertinents et adaptés au contexte local.',['ML','Vision','Scoring','Alertes']],
        ['🏆','Profil Talent Complet','orange','Chaque talent dispose d\'un profil digital complet accessible aux recruteurs : vidéos, scores, historique, contact.',['Portfolio','CV Sportif','Contact','Stats']],
        ['📱','Application Mobile','cyan','Interface optimisée mobile pour les professeurs d\'EPS sur le terrain : capture vidéo, upload et résultats en temps réel.',['iOS','Android','Offline','Caméra']],
      ];
      foreach ($features as $fi => [$icon,$title,$color,$desc,$tags]):
      ?>
      <div class="feature-card card card-hover feature-card--<?= $color ?> section-anim stagger-<?= ($fi % 6) + 1 ?>">
        <div class="feature-icon feature-icon--<?= $color ?>"><?= $icon ?></div>
        <h3 class="heading-md" style="margin:16px 0 10px;"><?= $title ?></h3>
        <p class="text-md text-muted"><?= $desc ?></p>
        <div class="feature-tags" style="margin-top:16px;display:flex;flex-wrap:wrap;gap:6px;">
          <?php foreach ($tags as $tag): ?>
          <span class="badge badge-<?= $color === 'red' ? 'red' : ($color === 'blue' ? 'blue' : 'success') ?>"><?= $tag ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══ HOW IT WORKS ═══ -->
<section class="section how" id="solution" style="background:var(--surface);">
  <div class="section__container">
    <div class="section__header">
      <span class="section__eyebrow">Processus</span>
      <h2 class="display-md">Comment ça marche ?</h2>
      <p class="text-lg text-muted">Un processus simple et efficace en 4 étapes</p>
    </div>
    <div class="how__grid">
      <div class="how__steps">
        <?php
        $steps = [
          ['s-red',  '01','Les professeurs uploadent les vidéos','Enregistrez facilement les performances sportives de vos élèves depuis votre smartphone ou caméra depuis n\'importe quelle école.'],
          ['s-blue', '02','L\'IA analyse les performances','Notre intelligence artificielle évalue automatiquement 5 critères physiques clés en quelques minutes avec une précision de 94%.'],
          ['s-green','03','Les recruteurs découvrent les talents','Les clubs et recruteurs accèdent aux profils des meilleurs talents détectés selon leur sport, région et critères personnalisés.'],
          ['s-gold', '04','Suivi et progression continue','Les coachs suivent l\'évolution de chaque talent avec des dashboards détaillés, recommandations IA et planning d\'entraînement.'],
        ];
        foreach ($steps as $i => [$cls,$num,$title,$desc]):
        ?>
        <div class="how__step how__step--<?= $cls ?>" onclick="selectStep(<?= $i ?>)" data-step="<?= $i ?>">
          <div class="how__step-indicator"></div>
          <div class="how__step-num"><?= $num ?></div>
          <div class="how__step-body">
            <h4 class="heading-md how__step-title"><?= $title ?></h4>
            <p class="text-sm text-muted how__step-desc"><?= $desc ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="how__visual">
        <div class="how__visual-card">
          <div id="howVisual">
            <!-- Step 0 default -->
            <div class="how__vis-content">
              <div style="text-align:center;padding:20px 0;">
                <div style="font-size:52px;margin-bottom:14px;">📱</div>
                <div class="heading-lg" style="margin-bottom:8px;">Upload vidéo</div>
                <p class="text-sm text-muted" style="margin-bottom:24px;">Depuis votre smartphone</p>
                <div style="background:var(--surface);border-radius:var(--radius);padding:20px;text-align:left;">
                  <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
                    <div style="width:48px;height:48px;background:var(--red-light);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;">🎥</div>
                    <div><div class="text-md" style="font-weight:600;">sprint_youssef_27mars.mp4</div><div class="text-xs text-muted">24.5 MB · Athlétisme · Sprint</div></div>
                  </div>
                  <div style="background:var(--red);height:4px;border-radius:2px;width:75%;"></div>
                  <div class="text-xs text-muted" style="margin-top:6px;">Upload en cours... 75%</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══ ROLES ═══ -->
<section class="section roles">
  <div class="section__container">
    <div class="section__header">
      <span class="section__eyebrow">Pour qui ?</span>
      <h2 class="display-md">Une plateforme, trois rôles</h2>
    </div>
    <div class="roles__grid">
      <?php
      $roles = [
        ['👟','Eleve','gold','Consultez vos scores, votre progression et les recommandations issues de vos analyses video.','student',['Mes scores','Progression','Points forts','Objectifs']],
        ['🧭','Manager recrutement','purple','Reperez les talents a recruter, coordonnez les prises de contact et pilotez votre shortlist club.','manager',['Shortlist','Messages terrain','Coordination club','Suivi prospects']],
        ['🎓','Professeur EPS','red','Détectez les talents de vos classes. Uploadez des vidéos, obtenez les scores IA, suivez vos élèves.','teacher',['Upload vidéos','Scores IA instantanés','Suivi élèves','Rapports PDF']],
        ['🏢','Recruteur / Club','blue','Découvrez les meilleurs talents de tout le Maroc filtrés par sport, ville et score minimum.','recruiter',['Recherche talents','Filtres avancés','Favoris','Profils complets']],
        ['🏆','Coach Sportif','green','Suivez la progression détaillée de vos athlètes avec graphiques, recommandations IA et planning.','coach',['Dashboards avancés','Graphiques évolution','Recommandations IA','Planning sessions']],
      ];
      foreach ($roles as $ri => [$icon,$title,$color,$desc,$role,$features]):
      ?>
      <div class="role-card card card-hover role-card--<?= $color ?> section-anim stagger-<?= ($ri % 5) + 1 ?>">
        <div class="role-card__header">
          <div class="role-icon role-icon--<?= $color ?>"><?= $icon ?></div>
          <h3 class="heading-xl"><?= $title ?></h3>
          <p class="text-md text-muted"><?= $desc ?></p>
        </div>
        <ul class="role-features">
          <?php foreach ($features as $f): ?>
          <li><span class="role-check">✓</span><?= $f ?></li>
          <?php endforeach; ?>
        </ul>
        <a href="<?= APP_URL ?>/pages/auth/login.php?role=<?= $role ?>" class="btn btn-<?= $color === 'red' ? 'primary' : 'outline' ?> role-btn" style="width:100%;justify-content:center;margin-top:24px;">
          Accéder en tant que <?= $title ?>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══ TESTIMONIALS ═══ -->
<section class="section testimonials" style="background:var(--ink);" id="testimonials">
  <div class="section__container">
    <div class="section__header" style="--eyebrow-color:rgba(255,255,255,.4)">
      <span class="section__eyebrow" style="color:rgba(255,255,255,.4);">Témoignages</span>
      <h2 class="display-md" style="color:white;">Ce que disent nos utilisateurs</h2>
      <p class="text-lg" style="color:rgba(255,255,255,.5);">Des professeurs, recruteurs et coachs à travers tout le Maroc</p>
    </div>
    <div class="grid-3" style="gap:24px;">
      <?php
      $testimonials = [
        ['HA','Hassan Alami','Professeur EPS · Casablanca','avatar-red','Atlas Talents a révolutionné ma façon de détecter les talents. En 3 mois, 4 de mes élèves ont été contactés par des clubs régionaux. Un outil indispensable pour tout prof d\'EPS.'],
        ['KR','Karim Recruteur','Raja Club Athletic · Casablanca','avatar-blue','En tant que recruteur, Atlas Talents me permet de découvrir des talents de tout le Maroc sans me déplacer. Les critères détaillés de l\'IA m\'aident à cibler les bons profils rapidement.'],
        ['CA','Coach Ahmed','AS FAR Rabat','avatar-green','Les dashboards de suivi sont exceptionnels. Les graphiques de progression et les recommandations IA m\'aident à adapter mes programmes d\'entraînement de façon très précise.'],
      ];
      foreach ($testimonials as [$init,$name,$role,$avatarClass,$text]):
      ?>
      <div class="testimonial-card">
        <div class="testimonial-stars">★★★★★</div>
        <p class="testimonial-text">"<?= $text ?>"</p>
        <div class="testimonial-author">
          <div class="avatar avatar-md <?= $avatarClass ?>"><?= $init ?></div>
          <div>
            <div class="testimonial-name"><?= $name ?></div>
            <div class="testimonial-role"><?= $role ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══ PRICING ═══ -->
<section class="section pricing" id="tarifs">
  <div class="section__container">
    <div class="section__header">
      <span class="section__eyebrow">Tarifs</span>
      <h2 class="display-md">Simple et transparent</h2>
      <p class="text-lg text-muted">Commencez gratuitement, évoluez selon vos besoins</p>
    </div>
    <div class="pricing__grid">
      <?php
      $plans = [
        ['Starter','Gratuit','Pour démarrer',['5 élèves','10 vidéos/mois','Analyse IA basique','Support email'],'false','btn-outline'],
        ['Pro','299 MAD/mois','Pour les professionnels',['Élèves illimités','Vidéos illimitées','Analyse IA avancée','Dashboard coach','Export PDF','Support prioritaire'],'true','btn-primary'],
        ['Club','799 MAD/mois','Pour les clubs',['Tout Pro inclus','Multi-utilisateurs','API accès','Manager de talents','Rapports personnalisés','Account manager dédié'],'false','btn-outline'],
      ];
      foreach ($plans as [$name,$price,$desc,$features,$popular,$btnClass]):
      ?>
      <div class="pricing-card card <?= $popular==='true'?'pricing-card--popular':'' ?>">
        <?php if ($popular === 'true'): ?><div class="pricing-badge">⭐ Plus populaire</div><?php endif; ?>
        <div class="pricing-name"><?= $name ?></div>
        <div class="pricing-price"><?= $price ?></div>
        <div class="pricing-desc"><?= $desc ?></div>
        <ul class="pricing-features">
          <?php foreach ($features as $f): ?>
          <li><span>✓</span><?= $f ?></li>
          <?php endforeach; ?>
        </ul>
        <a href="<?= APP_URL ?>/pages/auth/login.php?tab=register" class="btn <?= $btnClass ?>" style="width:100%;justify-content:center;margin-top:24px;">Choisir ce plan</a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══ CTA ═══ -->
<section class="cta-section" id="contact">
  <div class="cta-inner">
    <div class="cta-badge">🚀 Rejoignez la révolution sportive</div>
    <h2 class="display-md" style="color:white;margin:20px 0 16px;">Prêt à découvrir les champions<br>de demain ?</h2>
    <p class="text-lg" style="color:rgba(255,255,255,.75);margin-bottom:40px;">Rejoignez 500+ professeurs, recruteurs et coachs qui font confiance à Atlas Talents</p>
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
      <a href="<?= APP_URL ?>/pages/auth/login.php?tab=register" class="btn btn-white btn-lg">Créer un compte gratuit</a>
      <a href="#features" class="btn btn-ghost btn-lg">En savoir plus</a>
    </div>
    <div class="cta-trust">
      <span>✓ Sans carte bancaire</span>
      <span>✓ Annulation à tout moment</span>
      <span>✓ Support inclus</span>
    </div>
  </div>
</section>

</main>

<?php include __DIR__ . '/components/footer.php'; ?>
<?php include __DIR__ . '/components/scripts.php'; ?>

<style>
.landing-main { overflow: hidden; }
.hero { min-height: 100vh; display: flex; align-items: center; position: relative; overflow: hidden; padding: calc(var(--nav-height) + 54px) 0 68px; }
.hero__bg { position: absolute; inset: 0; pointer-events: none; }
.hero__orb { position: absolute; border-radius: 50%; filter: blur(8px); }
.hero__orb--1 { width: 760px; height: 760px; top: -240px; right: -120px; background: radial-gradient(circle, rgba(200,16,46,.14) 0%, rgba(200,16,46,.04) 42%, transparent 72%); }
.hero__orb--2 { width: 420px; height: 420px; bottom: -120px; left: -80px; background: radial-gradient(circle, rgba(212,175,55,.16) 0%, rgba(212,175,55,.04) 44%, transparent 74%); }
.hero__grid { position: absolute; inset: 0; background-image: linear-gradient(rgba(200,16,46,.04) 1px, transparent 1px), linear-gradient(90deg, rgba(200,16,46,.04) 1px, transparent 1px); background-size: 68px 68px; mask-image: linear-gradient(180deg, rgba(0,0,0,.35), transparent 92%); }
.hero__container { max-width: 1300px; margin: 0 auto; padding: 0 40px; display: grid; grid-template-columns: minmax(0, 1.02fr) minmax(420px, .98fr); gap: 72px; align-items: center; width: 100%; position: relative; z-index: 2; }
.hero__badge { display: inline-flex; align-items: center; gap: 10px; background: rgba(255,255,255,.84); color: var(--red-dark); padding: 10px 18px; border-radius: 999px; font-size: 12px; font-weight: 800; margin-bottom: 28px; border: 1px solid rgba(200,16,46,.16); box-shadow: var(--shadow-xs); letter-spacing: .08em; text-transform: uppercase; }
.badge-dot { width: 8px; height: 8px; background: var(--red); border-radius: 50%; animation: pulse 1.5s infinite; box-shadow: 0 0 0 6px rgba(200,16,46,.1); }
.hero__title { font-family: var(--font-display); font-size: clamp(48px, 5vw, 76px); font-weight: 700; line-height: .94; letter-spacing: -2.8px; color: var(--ink); margin-bottom: 22px; max-width: 720px; }
.hero__title-accent { color: var(--red); position: relative; display: inline-block; }
.hero__title-accent::after { content: ''; position: absolute; left: 0; right: 0; bottom: 6px; height: 16px; background: linear-gradient(180deg, rgba(200,16,46,0), rgba(200,16,46,.18)); z-index: -1; border-radius: 999px; }
.hero__desc { font-size: 17px; color: var(--muted); line-height: 1.75; max-width: 540px; margin-bottom: 34px; }
.hero__actions { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 42px; }
.hero__stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; padding: 18px; background: rgba(255,255,255,.62); border: 1px solid rgba(255,255,255,.82); border-radius: 28px; box-shadow: var(--shadow-sm); max-width: 760px; backdrop-filter: blur(16px); }
.hero__stat { padding: 6px 8px; }
.hero__stat-num { font-family: var(--font-display); font-size: 30px; font-weight: 700; color: var(--red); display: block; line-height: 1; }
.hero__stat-label { font-size: 11px; color: var(--muted); font-weight: 700; display: block; margin-top: 8px; text-transform: uppercase; letter-spacing: .08em; }
.hero__stat-divider { display: none; }
.hero__visual { position: relative; padding: 28px 0; }
.hero__card { background: linear-gradient(180deg, rgba(255,255,255,.96), rgba(255,248,245,.88)); border: 1px solid rgba(255,255,255,.86); border-radius: 30px; padding: 28px; box-shadow: var(--shadow-xl); backdrop-filter: blur(18px); }
.hero__card--main { position: relative; z-index: 2; transform: rotate(-2deg); }
.hero__card--main::before { content: ''; position: absolute; inset: 16px; border-radius: 22px; border: 1px solid rgba(200,16,46,.08); pointer-events: none; }
.hero__card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.hero__ai-badge { background: rgba(200,16,46,.08); color: var(--red); font-size: 12px; font-weight: 800; padding: 6px 14px; border-radius: 999px; letter-spacing: .04em; }
.hero__ai-pulse { width: 11px; height: 11px; background: var(--green); border-radius: 50%; animation: pulse 1.2s infinite; box-shadow: 0 0 0 6px rgba(27,110,58,.14); }
.hero__athlete { display: flex; align-items: center; gap: 14px; margin-bottom: 20px; }
.hero__athlete-name { font-family: var(--font-heading); font-size: 17px; font-weight: 800; }
.hero__athlete-sub { font-size: 12px; color: var(--muted); margin: 3px 0 7px; }
.hero__score-big { display: flex; align-items: baseline; gap: 12px; margin-bottom: 18px; }
.hero__score-num { font-family: var(--font-display); font-size: 64px; font-weight: 700; color: var(--red); line-height: 1; }
.hero__score-label { font-size: 13px; color: var(--muted); font-weight: 600; }
.hero__criteria { display: flex; flex-direction: column; gap: 10px; }
.hero__crit-row { display: flex; align-items: center; gap: 10px; }
.hero__crit-label { font-size: 12px; color: var(--muted); width: 92px; flex-shrink: 0; font-weight: 600; }
.hero__crit-track { flex: 1; height: 8px; background: rgba(10,10,15,.08); border-radius: 999px; overflow: hidden; }
.hero__crit-fill { height: 100%; border-radius: inherit; transition: width 1.5s var(--ease); }
.hero__crit-val { font-size: 12px; font-weight: 800; width: 24px; text-align: right; }
.hero__card--mini { position: absolute; display: flex; align-items: center; gap: 12px; padding: 16px 18px; min-width: 210px; box-shadow: var(--shadow-md); border-radius: 24px; }
.hero__card--mini-1 { bottom: 0; right: -18px; z-index: 3; }
.hero__card--mini-2 { top: 0; left: -20px; z-index: 3; }
.hero__mini-icon { width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
.hero__mini-num { font-family: var(--font-heading); font-size: 20px; font-weight: 800; line-height: 1; }
.hero__mini-label { font-size: 11px; color: var(--muted); margin-top: 2px; font-weight: 600; }
.hero__scroll { position: absolute; bottom: 24px; left: 50%; display: flex; flex-direction: column; align-items: center; gap: 8px; color: var(--muted); font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
.hero__scroll-line { width: 1px; height: 42px; background: linear-gradient(to bottom, transparent, rgba(10,10,15,.18)); }

.trusted { padding: 0 0 12px; }
.trusted__inner { max-width: 1300px; margin: 0 auto; padding: 0 40px; display: grid; grid-template-columns: 1fr; align-items: center; gap: 18px; }
.trusted__label { font-size: 12px; color: var(--muted); font-weight: 800; text-transform: uppercase; letter-spacing: .14em; text-align: center; }
.trusted__logo { text-align: center; padding: 15px 20px; font-size: 13px; font-weight: 800; color: var(--ink-60); border-radius: 18px; background: rgba(255,255,255,.56); border: 1px solid rgba(255,255,255,.84); box-shadow: var(--shadow-xs); white-space: nowrap; flex-shrink: 0; transition: background .2s, transform .2s; }
.trusted__logo:hover { background: rgba(255,255,255,.86); transform: translateY(-2px); }

.section { padding: 96px 0; position: relative; }
.section__container { max-width: 1300px; margin: 0 auto; padding: 0 40px; }
.section__header { text-align: center; margin-bottom: 60px; }
.section__eyebrow { font-size: 12px; font-weight: 800; color: var(--red); letter-spacing: .18em; text-transform: uppercase; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 14px; padding: 8px 14px; border-radius: 999px; background: rgba(255,255,255,.74); border: 1px solid rgba(200,16,46,.12); }
.section__header h2 { margin-bottom: 14px; }
.section__header p { max-width: 560px; margin: 0 auto; }

.features::before,
.roles::before,
.pricing::before {
  content: '';
  position: absolute;
  left: 50%;
  width: min(1300px, calc(100% - 32px));
  top: 22px;
  bottom: 22px;
  transform: translateX(-50%);
  border-radius: 36px;
  background: rgba(255,255,255,.46);
  border: 1px solid rgba(255,255,255,.7);
  box-shadow: inset 0 1px 0 rgba(255,255,255,.6);
  pointer-events: none;
}

.features .section__container,
.roles .section__container,
.pricing .section__container {
  position: relative;
  z-index: 1;
}

.features__grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 24px; }
.feature-card { padding: 34px 28px; position: relative; overflow: hidden; min-height: 100%; }
.feature-card::after { content: ''; position: absolute; width: 140px; height: 140px; top: -50px; right: -50px; border-radius: 50%; background: rgba(255,255,255,.55); filter: blur(6px); opacity: .6; }
.feature-card--red { background: linear-gradient(180deg, rgba(255,240,241,.92), rgba(255,255,255,.88)); }
.feature-card--blue { background: linear-gradient(180deg, rgba(232,241,251,.92), rgba(255,255,255,.88)); }
.feature-card--green { background: linear-gradient(180deg, rgba(232,245,237,.92), rgba(255,255,255,.88)); }
.feature-card--purple { background: linear-gradient(180deg, rgba(243,232,255,.92), rgba(255,255,255,.88)); }
.feature-card--orange { background: linear-gradient(180deg, rgba(255,243,224,.92), rgba(255,255,255,.88)); }
.feature-card--cyan { background: linear-gradient(180deg, rgba(224,245,247,.92), rgba(255,255,255,.88)); }
.feature-icon { width: 62px; height: 62px; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 28px; box-shadow: var(--shadow-xs); }
.feature-icon--red { background: rgba(255,255,255,.72); color: var(--red); }
.feature-icon--blue { background: rgba(255,255,255,.72); color: var(--blue); }
.feature-icon--green { background: rgba(255,255,255,.72); color: var(--green); }
.feature-icon--purple { background: rgba(255,255,255,.72); color: var(--purple); }
.feature-icon--orange { background: rgba(255,255,255,.72); color: var(--orange); }
.feature-icon--cyan { background: rgba(255,255,255,.72); color: var(--cyan); }

.how { background: linear-gradient(180deg, rgba(255,255,255,.28), rgba(255,255,255,.12)); }
.how__grid { display: grid; grid-template-columns: minmax(0, .96fr) minmax(0, 1.04fr); gap: 56px; align-items: start; }
.how__steps { display: flex; flex-direction: column; gap: 12px; }
.how__step { display: flex; gap: 18px; padding: 22px; border-radius: 28px; cursor: pointer; transition: all .25s var(--ease); background: rgba(255,255,255,.54); border: 1px solid rgba(255,255,255,.74); box-shadow: var(--shadow-xs); }
.how__step:hover { transform: translateX(4px); box-shadow: var(--shadow-sm); }
.how__step--s-red .how__step-num { background: linear-gradient(135deg, var(--red) 0%, var(--red-mid) 100%); }
.how__step--s-blue .how__step-num { background: linear-gradient(135deg, var(--blue) 0%, #1565C0 100%); }
.how__step--s-green .how__step-num { background: linear-gradient(135deg, var(--green) 0%, #2E7D32 100%); }
.how__step--s-gold .how__step-num { background: linear-gradient(135deg, var(--gold) 0%, #B88816 100%); }
.how__step-num { width: 52px; height: 52px; border-radius: 18px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-family: var(--font-heading); font-size: 14px; font-weight: 800; color: white; box-shadow: var(--shadow-xs); }
.how__step-title { font-size: 16px; margin-bottom: 6px; }
.how__step-desc { font-size: 13px; line-height: 1.6; }
.how__visual-card { background: linear-gradient(180deg, rgba(255,255,255,.94), rgba(255,248,245,.84)); border: 1px solid rgba(255,255,255,.88); border-radius: 34px; padding: 32px; box-shadow: var(--shadow-lg); min-height: 400px; display: flex; align-items: center; justify-content: center; position: sticky; top: calc(var(--nav-height) + 20px); }

.roles__grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 24px; }
.role-card { padding: 34px 28px; position: relative; overflow: hidden; }
.role-card::after { content: ''; position: absolute; inset: auto -50px -60px auto; width: 180px; height: 180px; border-radius: 50%; background: rgba(255,255,255,.55); }
.role-card--red { background: linear-gradient(180deg, rgba(255,240,241,.92), rgba(255,255,255,.88)); }
.role-card--gold { background: linear-gradient(180deg, rgba(255,248,225,.92), rgba(255,255,255,.88)); }
.role-card--purple { background: linear-gradient(180deg, rgba(243,232,255,.92), rgba(255,255,255,.88)); }
.role-card--blue { background: linear-gradient(180deg, rgba(232,241,251,.92), rgba(255,255,255,.88)); }
.role-card--green { background: linear-gradient(180deg, rgba(232,245,237,.92), rgba(255,255,255,.88)); }
.role-icon { width: 68px; height: 68px; border-radius: 22px; display: flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 20px; background: rgba(255,255,255,.78); box-shadow: var(--shadow-xs); }
.role-icon--red { color: var(--red); }
.role-icon--gold { color: var(--gold); }
.role-icon--purple { color: var(--purple); }
.role-icon--blue { color: var(--blue); }
.role-icon--green { color: var(--green); }
.role-card__header h3 { margin-bottom: 10px; }
.role-features { list-style: none; margin-top: 22px; display: flex; flex-direction: column; gap: 12px; }
.role-features li { display: flex; gap: 10px; align-items: flex-start; font-size: 14px; color: var(--ink-60); }
.role-check { color: var(--green); font-weight: 700; flex-shrink: 0; }
.role-card--gold .role-btn.btn-outline { color: var(--gold); }
.role-card--purple .role-btn.btn-outline { color: var(--purple); }
.role-card--blue .role-btn.btn-outline { color: var(--blue); }
.role-card--green .role-btn.btn-outline { color: var(--green); }

.testimonials { position: relative; overflow: hidden; }
.testimonials::before { content: ''; position: absolute; inset: 0; background: radial-gradient(circle at top left, rgba(200,16,46,.22), transparent 32%); pointer-events: none; }
.testimonial-card { background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12); border-radius: 30px; padding: 30px; backdrop-filter: blur(14px); box-shadow: 0 28px 60px rgba(0,0,0,.18); }
.testimonial-stars { color: var(--gold); font-size: 16px; margin-bottom: 16px; letter-spacing: .16em; }
.testimonial-text { font-size: 14px; line-height: 1.8; color: rgba(255,255,255,.8); margin-bottom: 24px; }
.testimonial-author { display: flex; align-items: center; gap: 12px; }
.testimonial-name { font-family: var(--font-heading); font-size: 14px; font-weight: 700; color: white; }
.testimonial-role { font-size: 12px; color: rgba(255,255,255,.46); margin-top: 2px; }

.pricing__grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 24px; max-width: 1080px; margin: 0 auto; align-items: stretch; }
.pricing-card { padding: 36px 28px; position: relative; min-height: 100%; background: linear-gradient(180deg, rgba(255,255,255,.94), rgba(255,248,245,.86)); }
.pricing-card--popular { border: 1px solid rgba(200,16,46,.22); box-shadow: var(--shadow-red); transform: translateY(-10px); }
.pricing-badge { position: absolute; top: -14px; left: 28px; background: linear-gradient(135deg, var(--red), var(--red-mid)); color: white; font-size: 11px; font-weight: 800; padding: 7px 16px; border-radius: 999px; white-space: nowrap; box-shadow: 0 12px 26px rgba(200,16,46,.24); }
.pricing-name { font-family: var(--font-heading); font-size: 20px; font-weight: 800; margin-bottom: 10px; }
.pricing-price { font-family: var(--font-display); font-size: 38px; font-weight: 700; color: var(--red); margin-bottom: 6px; letter-spacing: -1px; }
.pricing-desc { font-size: 13px; color: var(--muted); margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid rgba(10,10,15,.08); }
.pricing-features { list-style: none; display: flex; flex-direction: column; gap: 12px; }
.pricing-features li { display: flex; gap: 10px; font-size: 13px; color: var(--ink-60); }
.pricing-features li span { color: var(--green); font-weight: 700; }

.cta-section { background: linear-gradient(135deg, #6f091a 0%, var(--red) 42%, var(--red-dark) 100%); padding: 112px 40px; text-align: center; position: relative; overflow: hidden; }
.cta-section::before { content: ''; position: absolute; top: -260px; left: 50%; transform: translateX(-50%); width: 1000px; height: 1000px; background: rgba(255,255,255,.05); border-radius: 50%; }
.cta-section::after { content: ''; position: absolute; inset: 0; background-image: linear-gradient(rgba(255,255,255,.08) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.08) 1px, transparent 1px); background-size: 88px 88px; mask-image: linear-gradient(180deg, rgba(0,0,0,.3), transparent 88%); }
.cta-inner { position: relative; z-index: 2; max-width: 760px; margin: 0 auto; }
.cta-badge { display: inline-block; background: rgba(255,255,255,.12); color: rgba(255,255,255,.92); font-size: 12px; font-weight: 800; padding: 9px 18px; border-radius: 999px; border: 1px solid rgba(255,255,255,.2); margin-bottom: 8px; letter-spacing: .08em; text-transform: uppercase; }
.cta-trust { display: flex; gap: 24px; justify-content: center; margin-top: 26px; font-size: 12px; color: rgba(255,255,255,.7); flex-wrap: wrap; letter-spacing: .04em; text-transform: uppercase; font-weight: 700; }

@media (max-width: 1180px) {
  .hero__container { grid-template-columns: 1fr; gap: 42px; }
  .hero__visual { max-width: 620px; margin: 0 auto; }
}

@media (max-width: 1024px) {
  .hero { padding-bottom: 54px; }
  .hero__container { padding: 0 24px; }
  .hero__visual { display: none; }
  .hero__stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .features__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .how__grid { grid-template-columns: 1fr; }
  .how__visual { display: none; }
  .trusted__inner { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
  .hero__title { font-size: 42px; letter-spacing: -1.8px; }
  .hero__actions { flex-direction: column; }
  .features__grid, .pricing__grid, .roles__grid { grid-template-columns: 1fr; }
  .section { padding: 72px 0; }
  .section__container, .trusted__inner { padding: 0 20px; }
  .pricing-card--popular { transform: none; }
  .cta-section { padding: 88px 20px; }
}

@media (max-width: 560px) {
  .hero__stats { grid-template-columns: 1fr; }
}
</style>

<script>
/* ── How-it-works visuals ──────────────────────────────────────────── */
const howVisuals = [
  `<div style="text-align:center;padding:20px 0;">
    <div style="font-size:52px;margin-bottom:14px;">📱</div>
    <div class="heading-lg" style="margin-bottom:8px;">Upload vidéo</div>
    <p class="text-sm text-muted" style="margin-bottom:20px;">Depuis votre smartphone ou caméra</p>
    <div style="background:var(--surface);border-radius:var(--radius);padding:20px;text-align:left;">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
        <div style="width:44px;height:44px;background:var(--red-light);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;">🎥</div>
        <div><div style="font-size:14px;font-weight:600;">sprint_youssef.mp4</div><div class="text-xs text-muted">24.5 MB · Sprint</div></div>
      </div>
      <div style="background:rgba(10,10,15,.08);border-radius:4px;height:6px;overflow:hidden;"><div style="background:var(--red);height:100%;border-radius:4px;width:0;transition:width 1.2s cubic-bezier(.16,1,.3,1);" id="uploadBar"></div></div>
      <div class="text-xs text-muted" style="margin-top:6px;" id="uploadLabel">Upload en cours... 0%</div>
    </div>
  </div>`,
  `<div style="text-align:center;padding:20px 0;">
    <div style="font-size:52px;margin-bottom:14px;">🤖</div>
    <div class="heading-lg" style="margin-bottom:8px;">Analyse IA</div>
    <p class="text-sm text-muted" style="margin-bottom:20px;">5 critères physiques évalués</p>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
      <div style="background:rgba(255,143,0,.1);border-radius:10px;padding:14px;text-align:center;"><div style="font-size:24px;font-weight:800;color:#FF8F00;font-family:var(--font-heading);">92</div><div class="text-xs text-muted" style="margin-top:3px;">Vitesse</div></div>
      <div style="background:var(--blue-light);border-radius:10px;padding:14px;text-align:center;"><div style="font-size:24px;font-weight:800;color:var(--blue);font-family:var(--font-heading);">88</div><div class="text-xs text-muted" style="margin-top:3px;">Coordination</div></div>
      <div style="background:var(--green-light);border-radius:10px;padding:14px;text-align:center;"><div style="font-size:24px;font-weight:800;color:var(--green);font-family:var(--font-heading);">85</div><div class="text-xs text-muted" style="margin-top:3px;">Endurance</div></div>
      <div style="background:var(--purple-light);border-radius:10px;padding:14px;text-align:center;"><div style="font-size:24px;font-weight:800;color:var(--purple);font-family:var(--font-heading);">79</div><div class="text-xs text-muted" style="margin-top:3px;">Force</div></div>
    </div>
    <div style="background:var(--red-light);border-radius:var(--radius);padding:16px;margin-top:12px;"><span style="font-family:var(--font-display);font-size:30px;font-weight:700;color:var(--red);">87</span><span class="text-sm text-muted"> / 100 Score IA</span></div>
  </div>`,
  `<div style="text-align:center;padding:20px 0;">
    <div style="font-size:52px;margin-bottom:14px;">🔍</div>
    <div class="heading-lg" style="margin-bottom:8px;">Découverte talents</div>
    <p class="text-sm text-muted" style="margin-bottom:20px;">Recruteurs accèdent aux profils</p>
    <div style="display:flex;flex-direction:column;gap:10px;">
      <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px;display:flex;align-items:center;gap:12px;"><div class="avatar avatar-sm avatar-red">YA</div><div style="flex:1;text-align:left;"><div style="font-size:13px;font-weight:700;">Youssef El Amrani</div><div class="text-xs text-muted">Athlétisme · Casablanca</div></div><span class="badge badge-success">87</span></div>
      <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px;display:flex;align-items:center;gap:12px;"><div class="avatar avatar-sm avatar-purple">FZ</div><div style="flex:1;text-align:left;"><div style="font-size:13px;font-weight:700;">Fatima Zahra B.</div><div class="text-xs text-muted">Gymnastique · Rabat</div></div><span class="badge badge-success">91</span></div>
      <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px;display:flex;align-items:center;gap:12px;"><div class="avatar avatar-sm avatar-blue">KM</div><div style="flex:1;text-align:left;"><div style="font-size:13px;font-weight:700;">Khalid Mansouri</div><div class="text-xs text-muted">Football · Marrakech</div></div><span class="badge badge-success">84</span></div>
    </div>
  </div>`,
  `<div style="text-align:center;padding:20px 0;">
    <div style="font-size:52px;margin-bottom:14px;">📈</div>
    <div class="heading-lg" style="margin-bottom:8px;">Suivi progression</div>
    <p class="text-sm text-muted" style="margin-bottom:20px;">Dashboard coach en temps réel</p>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
      <div style="background:var(--green-light);border-radius:10px;padding:12px;"><div style="font-size:11px;color:var(--green);font-weight:700;">↗ +6.1%</div><div style="font-size:22px;font-weight:800;font-family:var(--font-heading);color:var(--green);">87</div><div class="text-xs text-muted">Score moyen</div></div>
      <div style="background:rgba(255,143,0,.1);border-radius:10px;padding:12px;"><div style="font-size:11px;color:#FF8F00;font-weight:700;">↗ +8.9%</div><div style="font-size:22px;font-weight:800;font-family:var(--font-heading);color:#FF8F00;">92</div><div class="text-xs text-muted">Vitesse</div></div>
      <div style="background:var(--blue-light);border-radius:10px;padding:12px;"><div style="font-size:11px;color:var(--blue);font-weight:700;">↗ +3.2%</div><div style="font-size:22px;font-weight:800;font-family:var(--font-heading);color:var(--blue);">88</div><div class="text-xs text-muted">Coordination</div></div>
      <div style="background:var(--surface);border-radius:10px;padding:12px;"><div style="font-size:11px;color:var(--muted);font-weight:700;">Sessions</div><div style="font-size:22px;font-weight:800;font-family:var(--font-heading);">24</div><div class="text-xs text-muted">Ce semestre</div></div>
    </div>
  </div>`,
];

let howAutoTimer = null;
let currentHowStep = 0;

function selectStep(index, userInitiated = true) {
  currentHowStep = index;

  document.querySelectorAll('.how__step').forEach((s, i) => {
    s.classList.toggle('active', i === index);
  });

  const vis = document.getElementById('howVisual');
  if (vis) {
    vis.style.opacity = '0';
    setTimeout(() => {
      vis.innerHTML = `<div class="how__vis-content">${howVisuals[index]}</div>`;
      vis.style.opacity = '1';

      /* Animate upload progress bar in step 0 */
      if (index === 0) {
        const bar = document.getElementById('uploadBar');
        const label = document.getElementById('uploadLabel');
        if (bar) {
          bar.style.width = '0';
          let pct = 0;
          const tick = setInterval(() => {
            pct += 3;
            if (pct > 75) { clearInterval(tick); pct = 75; }
            bar.style.width = pct + '%';
            if (label) label.textContent = 'Upload en cours... ' + pct + '%';
          }, 40);
        }
      }
    }, 160);
  }

  if (userInitiated) {
    clearInterval(howAutoTimer);
    howAutoTimer = setInterval(() => selectStep((currentHowStep + 1) % howVisuals.length, false), 5000);
  }
}

document.getElementById('howVisual').style.transition = 'opacity .2s';
selectStep(0, false);
howAutoTimer = setInterval(() => selectStep((currentHowStep + 1) % howVisuals.length, false), 5000);

/* ── Hero stats counter animation ─────────────────────────────────── */
function animateCounter(el, target, suffix, duration) {
  let start = 0;
  const step = target / (duration / 16);
  const timer = setInterval(() => {
    start += step;
    if (start >= target) {
      start = target;
      clearInterval(timer);
    }
    el.textContent = Math.round(start) + suffix;
  }, 16);
}

function runHeroCounters() {
  const counters = [
    { sel: '.hero__stat-num:nth-child(1)', target: 500, suffix: '+' },
  ];
  document.querySelectorAll('.hero__stat').forEach((stat, i) => {
    const numEl = stat.querySelector('.hero__stat-num');
    if (!numEl || numEl.dataset.animated) return;
    numEl.dataset.animated = '1';
    const text = numEl.textContent.trim();
    const match = text.match(/^(\d+)([+%]?)$/);
    if (!match) return;
    const target = parseInt(match[1]);
    const suffix = match[2] || '';
    numEl.textContent = '0' + suffix;
    setTimeout(() => animateCounter(numEl, target, suffix, 1200), i * 120);
  });
}

/* ── Hero criteria bar animate-in ──────────────────────────────────── */
function animateCritBars() {
  document.querySelectorAll('.hero__crit-fill').forEach(fill => {
    if (fill.dataset.animated) return;
    fill.dataset.animated = '1';
    const targetW = fill.style.width;
    fill.style.setProperty('--target-w', targetW);
    fill.style.width = '0';
    setTimeout(() => fill.classList.add('animated'), 100);
  });
}

/* ── Scroll-reveal via IntersectionObserver ────────────────────────── */
(function initScrollReveal() {
  if (!('IntersectionObserver' in window)) {
    document.querySelectorAll('.section-anim').forEach(el => el.classList.add('visible'));
    return;
  }

  const obs = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        obs.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

  document.querySelectorAll('.section-anim').forEach(el => obs.observe(el));
})();

/* ── Hero section observer (counters + bars) ───────────────────────── */
(function initHeroObserver() {
  const heroStats = document.querySelector('.hero__stats');
  if (!heroStats) return;
  if (!('IntersectionObserver' in window)) { runHeroCounters(); animateCritBars(); return; }

  const obs = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        runHeroCounters();
        animateCritBars();
        obs.disconnect();
      }
    });
  }, { threshold: 0.5 });

  obs.observe(heroStats);
})();

/* ── Active nav link on scroll ─────────────────────────────────────── */
(function initNavHighlight() {
  const sections = ['features', 'solution', 'tarifs', 'contact'];
  const links = {};
  sections.forEach(id => {
    links[id] = document.querySelector(`.at-nav__link[href="#${id}"]`);
  });

  const obs = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      const link = links[entry.target.id];
      if (link) link.classList.toggle('active', entry.isIntersecting);
    });
  }, { threshold: 0.3 });

  sections.forEach(id => {
    const el = document.getElementById(id);
    if (el) obs.observe(el);
  });
})();
</script>
