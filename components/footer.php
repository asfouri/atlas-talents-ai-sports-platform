<?php // components/footer.php ?>
<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-grid">
      <div class="footer-brand">
        <div class="footer-logo">
          <div class="at-nav__logo" style="width:44px;height:44px;border-radius:12px;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="white" stroke-width="2.5"/><circle cx="12" cy="12" r="4" stroke="white" stroke-width="2.5"/><circle cx="12" cy="12" r="1.5" fill="white"/></svg>
          </div>
          <span style="font-family:var(--font-heading);font-weight:800;font-size:17px;color:white;">ATLAS TALENTS</span>
        </div>
        <p>La première plateforme marocaine de détection de talents sportifs par intelligence artificielle.</p>
        <div class="footer-social">
          <a href="<?= APP_URL ?>/#hero" class="social-link" title="Facebook">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
          </a>
          <a href="<?= APP_URL ?>/#testimonials" class="social-link" title="Instagram">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>
          </a>
          <a href="<?= APP_URL ?>/#features" class="social-link" title="Twitter / X">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
          </a>
          <a href="<?= APP_URL ?>/#contact" class="social-link" title="LinkedIn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
          </a>
        </div>
      </div>
      <div class="footer-col">
        <h5>Plateforme</h5>
        <a href="<?= APP_URL ?>/#features">Fonctionnalités</a>
        <a href="<?= APP_URL ?>/#solution">Analyse vidéo IA</a>
        <a href="<?= APP_URL ?>/#features">Dashboard coach</a>
        <a href="<?= APP_URL ?>/#testimonials">Réseau sportif</a>
      </div>
      <div class="footer-col">
        <h5>Pour vous</h5>
        <a href="<?= APP_URL ?>/#features">Professeurs EPS</a>
        <a href="<?= APP_URL ?>/#features">Recruteurs</a>
        <a href="<?= APP_URL ?>/#features">Clubs sportifs</a>
        <a href="<?= APP_URL ?>/#features">Coachs</a>
      </div>
      <div class="footer-col">
        <h5>Ressources</h5>
        <a href="<?= APP_URL ?>/#features">Documentation</a>
        <a href="<?= APP_URL ?>/#testimonials">Blog</a>
        <a href="<?= APP_URL ?>/#contact">Support</a>
        <a href="<?= APP_URL ?>/#tarifs">Tarifs</a>
      </div>
      <div class="footer-col">
        <h5>Entreprise</h5>
        <a href="<?= APP_URL ?>/#hero">À propos</a>
        <a href="<?= APP_URL ?>/#contact">Carrières</a>
        <a href="<?= APP_URL ?>/#contact">Contact</a>
        <a href="<?= APP_URL ?>/#testimonials">Presse</a>
      </div>
    </div>
    <div class="footer-bottom">
      <div class="footer-bottom-left">
        <span>© <?= date('Y') ?> Atlas Talents. Tous droits réservés.</span>
        <span class="footer-made">Made with ❤️ in Morocco 🇲🇦</span>
      </div>
      <div class="footer-bottom-right">
        <a href="<?= APP_URL ?>/#contact">Confidentialité</a>
        <a href="<?= APP_URL ?>/#contact">CGU</a>
        <a href="<?= APP_URL ?>/#contact">Cookies</a>
      </div>
    </div>
  </div>
</footer>

<style>
.site-footer {
  background:
    radial-gradient(circle at top left, rgba(200,16,46,.18), transparent 26%),
    linear-gradient(180deg, #140608 0%, #0a0a0f 100%);
  color: rgba(255,255,255,.56);
  padding: 84px 0 32px;
  position: relative;
  overflow: hidden;
}
.site-footer::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,.04) 1px, transparent 1px);
  background-size: 84px 84px;
  mask-image: linear-gradient(180deg, rgba(0,0,0,.4), transparent 90%);
  pointer-events: none;
}
.footer-inner { max-width: 1300px; margin: 0 auto; padding: 0 40px; }
.footer-inner { position: relative; z-index: 1; }
.footer-grid { display: grid; grid-template-columns: minmax(280px, 2.4fr) repeat(4, minmax(0, 1fr)); gap: 42px; margin-bottom: 56px; }
.footer-logo { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
.footer-brand p { font-size: 14px; line-height: 1.7; max-width: 320px; }
.footer-social { display: flex; gap: 10px; margin-top: 22px; }
.social-link {
  width: 40px; height: 40px; background: rgba(255,255,255,.08);
  border: 1px solid rgba(255,255,255,.12); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 16px;
  transition: background .2s, transform .2s, border-color .2s;
}
.social-link:hover { background: rgba(255,255,255,.14); transform: translateY(-2px); border-color: rgba(255,255,255,.18); }
.footer-col h5 { font-family: var(--font-heading); font-size: 14px; font-weight: 700; color: white; margin-bottom: 18px; letter-spacing: -.02em; }
.footer-col a { display: block; font-size: 13px; margin-bottom: 12px; transition: color .2s, transform .2s; }
.footer-col a:hover { color: white; }
.footer-bottom { border-top: 1px solid rgba(255,255,255,.08); padding-top: 28px; display: flex; justify-content: space-between; align-items: center; font-size: 13px; gap: 16px; }
.footer-bottom-left { display: flex; gap: 20px; align-items: center; }
.footer-made { color: rgba(255,255,255,.3); }
.footer-bottom-right { display: flex; gap: 20px; }
.footer-bottom-right a { transition: color .2s, transform .2s; }
.footer-col a:hover,
.footer-bottom-right a:hover { transform: translateX(2px); }
.footer-bottom-right a:hover { color: white; }
@media (max-width: 1024px) { .footer-grid { grid-template-columns: 1fr 1fr; gap: 32px; } }
@media (max-width: 640px)  {
  .footer-inner { padding: 0 20px; }
  .footer-grid { grid-template-columns: 1fr; gap: 24px; }
  .footer-bottom { flex-direction: column; gap: 12px; text-align: center; }
  .footer-bottom-left, .footer-bottom-right { flex-wrap: wrap; justify-content: center; }
}
</style>
