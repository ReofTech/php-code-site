<?php
/**
 * Template Name: Shop Premium NERV
 * Version V2: Tactical UI & Adjusted Pricing (2.99€)
 */

if (!defined('ABSPATH')) exit;
get_header();

$current_user   = wp_get_current_user();
$is_logged_in   = is_user_logged_in();
$is_premium     = function_exists('nerv_user_is_premium') ? nerv_user_is_premium() : false;
$premium_exp    = $is_premium ? (int) get_user_meta($current_user->ID, 'nerv_premium_end_date', true) : 0;

// Les nouveaux forfaits (Prix ajustés, psychologie "Prix d'un café")
$plans = [
    ['days' => 30,  'price' => '2,99 €',  'label' => '1 Mois',   'desc' => 'Soutien basique',       'best' => false],
    ['days' => 90,  'price' => '7,99 €',  'label' => '3 Mois',   'desc' => 'Pour les réguliers',    'best' => false],
    ['days' => 180, 'price' => '14,99 €', 'label' => '6 Mois',   'desc' => 'Le choix des Pilotes',  'best' => true],
    ['days' => 365, 'price' => '24,99 €', 'label' => '1 An',     'desc' => 'Tranquillité absolue',  'best' => false],
];
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Accès Premium – NERV</title>
<?php wp_head(); ?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,600;0,700;0,800;1,700;1,800;1,900&family=Inter:wght@400;500;600;700;800;900&display=swap');

:root {
  --ns-bg: #000000;
  --ns-panel: #070707;
  --ns-panel-light: #111111;
  --ns-stroke: rgba(255,255,255,0.08);
  --ns-yellow: #F4C95D;
  --ns-red: #ef4444;
  --ns-green: #22c55e;
  --ns-text: #ffffff;
  --ns-muted: #888888;
  --ns-radius: 16px;
  --ns-font: 'Inter', sans-serif;
}

* { box-sizing: border-box; margin: 0; padding: 0; }
body { background: var(--ns-bg); color: var(--ns-text); font-family: var(--ns-font); min-height: 100vh; overflow-x: hidden; }

/* MASQUER HEADER/FOOTER DU THEME SI BESOIN */
.c-page-header, .c-breadcrumb { display: none !important; }

/* BACKGROUND GLOW */
.shop-bg-glow { position: fixed; top: -20%; left: 50%; transform: translateX(-50%); width: 80vw; height: 600px; background: radial-gradient(ellipse at center, rgba(244,201,93,0.08) 0%, transparent 60%); z-index: -1; pointer-events: none; }

.shop-wrap { max-width: 1100px; margin: 0 auto; padding: 60px 20px 100px; position: relative; z-index: 1; }

/* HERO SECTION */
.shop-hero { text-align: center; margin-bottom: 60px; }
.shop-hero .eyebrow { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 99px; background: rgba(244,201,93,.05); color: var(--ns-yellow); font-size: 11px; font-weight: 900; letter-spacing: 0.15em; text-transform: uppercase; margin-bottom: 24px; border: 1px solid rgba(244,201,93,.2); box-shadow: 0 0 20px rgba(244,201,93,0.1); }
.shop-hero h1 { font-family: 'Barlow Condensed', sans-serif; font-size: clamp(40px, 6vw, 64px); font-style: italic; font-weight: 900; letter-spacing: -0.02em; line-height: 1.05; margin-bottom: 16px; text-transform: uppercase; text-shadow: 0 10px 30px rgba(0,0,0,0.8); }
.shop-hero h1 span { color: var(--ns-yellow); }
.shop-hero p { color: var(--ns-muted); font-size: 16px; max-width: 540px; margin: 0 auto; line-height: 1.6; font-weight: 500; }

/* STATUS MEMBRE */
.premium-status { display: flex; align-items: center; gap: 20px; background: rgba(10,10,10,0.6); backdrop-filter: blur(10px); border: 1px solid var(--ns-stroke); border-radius: var(--ns-radius); padding: 24px 32px; margin-bottom: 60px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); }
.premium-status .ps-icon { display: flex; align-items: center; justify-content: center; width: 54px; height: 54px; border-radius: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; }
.premium-status .ps-icon.is-vip { background: rgba(244,201,93,0.1); border-color: rgba(244,201,93,0.3); color: var(--ns-yellow); box-shadow: inset 0 0 20px rgba(244,201,93,0.2); }
.premium-status h3 { font-size: 16px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #fff; margin-bottom: 4px; }
.premium-status.is-vip h3 { color: var(--ns-yellow); }
.premium-status p { font-size: 13px; color: var(--ns-muted); font-weight: 500; }

/* GRILLE DES PLANS */
.plans-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 80px; }
.plan-card { background: var(--ns-panel); border: 1px solid var(--ns-stroke); border-radius: var(--ns-radius); padding: 32px 24px; text-align: center; position: relative; transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s; box-shadow: 0 10px 30px rgba(0,0,0,0.5); display: flex; flex-direction: column; }
.plan-card:hover { transform: translateY(-8px); border-color: rgba(244,201,93,.3); box-shadow: 0 20px 40px rgba(0,0,0,0.8); }

/* Plan "Meilleur Deal" */
.plan-card.best { background: linear-gradient(180deg, rgba(244,201,93,.05), var(--ns-panel)); border-color: rgba(244,201,93,.4); box-shadow: 0 15px 40px rgba(244,201,93,0.15); transform: scale(1.02); z-index: 2; }
.plan-card.best:hover { transform: scale(1.02) translateY(-8px); box-shadow: 0 25px 50px rgba(244,201,93,0.25); }
.best-badge { position: absolute; top: -14px; left: 50%; transform: translateX(-50%); background: var(--ns-yellow); color: #000; font-size: 10px; font-weight: 900; padding: 6px 16px; border-radius: 99px; letter-spacing: 0.1em; text-transform: uppercase; box-shadow: 0 5px 15px rgba(244,201,93,0.4); white-space: nowrap; }

.plan-label { font-size: 16px; font-weight: 800; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.05em; color: #fff; }
.plan-card.best .plan-label { color: var(--ns-yellow); }
.plan-desc { font-size: 12px; color: var(--ns-muted); margin-bottom: 24px; font-weight: 500; height: 34px; }
.plan-price { font-family: 'Barlow Condensed', sans-serif; font-size: 42px; font-weight: 900; font-style: italic; color: #fff; margin-bottom: 30px; line-height: 1; }
.plan-card.best .plan-price { color: var(--ns-yellow); text-shadow: 0 0 20px rgba(244,201,93,0.3); }

/* Boutons d'achat */
.btn-plan { margin-top: auto; display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 14px; border-radius: 10px; background: var(--ns-panel-light); border: 1px solid var(--ns-stroke); color: #fff; font-size: 12px; font-weight: 800; text-decoration: none; cursor: pointer; transition: 0.3s; text-transform: uppercase; letter-spacing: 0.05em; }
.btn-plan svg { width: 16px; height: 16px; }
.btn-plan:hover { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.2); }
.plan-card.best .btn-plan { background: var(--ns-yellow); color: #000; border: none; box-shadow: 0 8px 20px rgba(244,201,93,0.3); }
.plan-card.best .btn-plan:hover { filter: brightness(1.1); transform: translateY(-2px); box-shadow: 0 12px 25px rgba(244,201,93,0.4); }

/* COMPARATIF & AVANTAGES */
.features-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 80px; }
.features-card { background: rgba(10,10,10,0.6); backdrop-filter: blur(10px); border: 1px solid var(--ns-stroke); border-radius: var(--ns-radius); padding: 40px; box-shadow: 0 20px 50px rgba(0,0,0,0.6); }
.features-card h2 { font-family: 'Barlow Condensed', sans-serif; font-style: italic; font-size: 32px; font-weight: 800; margin-bottom: 30px; text-transform: uppercase; letter-spacing: -0.01em; }
.features-card h2 span { color: var(--ns-yellow); }

.feature-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid var(--ns-stroke); font-size: 13px; font-weight: 600; color: #ddd; }
.feature-row:last-child { border-bottom: none; }
.feature-icons { display: flex; gap: 16px; align-items: center; width: 100px; justify-content: flex-end; }
.fi-icon { width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; border-radius: 50%; }
.fi-yes { background: rgba(34,197,94,0.1); color: var(--ns-green); border: 1px solid rgba(34,197,94,0.2); }
.fi-no { background: rgba(255,255,255,0.03); color: #444; border: 1px solid rgba(255,255,255,0.05); }

.perks-list { display: flex; flex-direction: column; gap: 20px; }
.perk-item { display: flex; gap: 16px; align-items: flex-start; }
.perk-icon { display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: rgba(244,201,93,0.1); border: 1px solid rgba(244,201,93,0.2); color: var(--ns-yellow); border-radius: 10px; flex-shrink: 0; }
.perk-text h4 { font-size: 14px; font-weight: 800; color: #fff; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.05em; }
.perk-text p { font-size: 12px; color: var(--ns-muted); line-height: 1.5; font-weight: 500; }

/* FAQ */
.faq-section { max-width: 800px; margin: 0 auto; }
.faq-section h2 { text-align: center; font-family: 'Barlow Condensed', sans-serif; font-style: italic; font-size: 36px; font-weight: 900; margin-bottom: 40px; text-transform: uppercase; }
.faq-item { background: var(--ns-panel); border: 1px solid var(--ns-stroke); border-radius: 12px; padding: 24px 30px; margin-bottom: 16px; transition: 0.2s; }
.faq-item:hover { border-color: rgba(255,255,255,0.15); background: var(--ns-panel-light); }
.faq-item h3 { font-size: 14px; font-weight: 800; margin-bottom: 10px; color: var(--ns-yellow); display: flex; align-items: center; gap: 10px; text-transform: uppercase; letter-spacing: 0.05em; }
.faq-item p { font-size: 13px; color: var(--ns-muted); line-height: 1.6; font-weight: 500; }

/* MODALE WIP (TERMINAL ALERT) */
.ns-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.9); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); z-index: 999999; display: none; align-items: center; justify-content: center; padding: 20px; }
.ns-modal-overlay.is-active { display: flex; animation: nsFadeIn 0.3s ease forwards; }
.ns-modal-card { background: #050505; border: 1px solid var(--ns-red); border-radius: 20px; max-width: 500px; width: 100%; padding: 50px 40px; text-align: center; box-shadow: 0 40px 100px rgba(239, 68, 68, 0.15), inset 0 0 60px rgba(239, 68, 68, 0.05); transform: translateY(20px); animation: nsSlideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; position: relative; overflow: hidden; }
.ns-modal-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: var(--ns-red); box-shadow: 0 0 20px var(--ns-red); }
.ns-modal-icon { display: flex; justify-content: center; color: var(--ns-red); margin-bottom: 24px; filter: drop-shadow(0 0 10px rgba(239,68,68,0.5)); }
.ns-modal-card h2 { font-family: 'Barlow Condensed', sans-serif; font-size: 36px; font-weight: 900; font-style: italic; text-transform: uppercase; color: #fff; margin-bottom: 16px; letter-spacing: -0.02em; }
.ns-modal-card h2 span { color: var(--ns-red); }
.ns-modal-card p { color: var(--ns-muted); font-size: 14px; line-height: 1.6; margin-bottom: 40px; }
.ns-modal-close { background: rgba(239,68,68,0.1); color: var(--ns-red); border: 1px solid rgba(239,68,68,0.3); padding: 16px 36px; border-radius: 10px; font-family: var(--ns-font); font-weight: 900; text-transform: uppercase; font-size: 12px; letter-spacing: 0.1em; cursor: pointer; transition: 0.2s; width: 100%; }
.ns-modal-close:hover { background: var(--ns-red); color: #fff; transform: translateY(-2px); box-shadow: 0 10px 25px rgba(239,68,68,0.4); }

@keyframes nsFadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes nsSlideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

/* RESPONSIVE */
@media (max-width: 1024px) {
  .plans-grid { grid-template-columns: repeat(2, 1fr); gap: 30px; }
  .plan-card.best { transform: none; }
  .plan-card.best:hover { transform: translateY(-8px); }
  .features-grid { grid-template-columns: 1fr; gap: 30px; }
}
@media (max-width: 600px) {
  .plans-grid { grid-template-columns: 1fr; }
  .shop-hero h1 { font-size: 36px; }
  .premium-status { flex-direction: column; text-align: center; padding: 24px 20px; }
  .features-card { padding: 24px 20px; }
  .ns-modal-card { padding: 40px 24px; }
}
</style>
</head>
<body <?php body_class('nerv-shop-page'); ?>>
<?php wp_body_open(); ?>

<div class="shop-bg-glow"></div>

<div class="shop-wrap">

  <div class="shop-hero">
    <div class="eyebrow">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
        Programme VIP
    </div>
    <h1>Soutiens NERV, <span>lis en avance</span></h1>
    <p>Accède instantanément aux chapitres en cours de traduction, supprime les publicités et affiche ton soutien avec le badge exclusif.</p>
  </div>

  <?php if ($is_logged_in): ?>
    <div class="premium-status <?php echo $is_premium ? 'is-vip' : ''; ?>">
      <div class="ps-icon <?php echo $is_premium ? 'is-vip' : ''; ?>">
        <?php if ($is_premium): ?>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 9.9-1"></path></svg>
        <?php else: ?>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
        <?php endif; ?>
      </div>
      <div>
        <?php if ($is_premium): ?>
          <h3>Accès Premium Activé</h3>
          <p>
            Ton pass VIP est valide. 
            <?php echo $premium_exp ? 'Expire le <strong>' . date_i18n('d/m/Y à H:i', $premium_exp) . '</strong>' : 'Accès illimité par le Commandement.'; ?>
          </p>
        <?php else: ?>
          <h3>Accès Restreint</h3>
          <p>Choisis un pass ci-dessous pour déverrouiller instantanément le terminal.</p>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="plans-grid">
    <?php foreach ($plans as $plan): ?>
    <div class="plan-card <?php echo $plan['best'] ? 'best' : ''; ?>">
      <?php if ($plan['best']): ?><span class="best-badge">OPÉRATION RENTABLE</span><?php endif; ?>
      
      <div class="plan-label"><?php echo esc_html($plan['label']); ?></div>
      <div class="plan-desc"><?php echo esc_html($plan['desc']); ?></div>
      <div class="plan-price"><?php echo esc_html($plan['price']); ?></div>
      
      <?php if ($is_logged_in): ?>
        <button class="btn-plan ns-trigger-modal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 9.9-1"></path></svg>
            Obtenir l'accès
        </button>
      <?php else: ?>
        <a href="<?php echo esc_url(home_url('/nerv-gate/')); ?>" class="btn-plan">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
            Se Connecter
        </a>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="features-grid">
    <div class="features-card">
      <h2>Free vs <span>Premium</span></h2>
      <?php
      $features = [
        ['label' => 'Lecture des chapitres publics', 'free' => true],
        ['label' => 'Commentaires & Favoris',        'free' => true],
        ['label' => 'Déblocage des chapitres en avance', 'free' => false],
        ['label' => 'Lecture 100% sans publicité',   'free' => false],
        ['label' => 'Badge VIP sur le profil',       'free' => false],
        ['label' => 'Rôle Discord Privé',            'free' => false],
      ];
      
      $svg_yes = '<div class="fi-icon fi-yes"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></div>';
      $svg_no  = '<div class="fi-icon fi-no"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></div>';
      
      foreach ($features as $f):
      ?>
        <div class="feature-row">
          <span><?php echo esc_html($f['label']); ?></span>
          <div class="feature-icons">
              <?php echo $f['free'] ? $svg_yes : $svg_no; ?>
              <?php echo $svg_yes; /* Premium has everything */ ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="features-card">
      <h2>Vos <span>Avantages</span></h2>
      <div class="perks-list">
          <div class="perk-item">
              <div class="perk-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></div>
              <div class="perk-text">
                  <h4>Aucune attente</h4>
                  <p>Lisez les chapitres dès qu'ils sortent des mains de nos traducteurs, sans attendre les délais de publication publique.</p>
              </div>
          </div>
          <div class="perk-item">
              <div class="perk-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg></div>
              <div class="perk-text">
                  <h4>Immersion totale</h4>
                  <p>Adieu les bannières intrusives. Profitez d'une expérience de lecture propre, rapide et adaptée aux mobiles.</p>
              </div>
          </div>
          <div class="perk-item">
              <div class="perk-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg></div>
              <div class="perk-text">
                  <h4>Soutien direct</h4>
                  <p>100% des fonds servent à maintenir les serveurs en vie et à acheter les chapitres originaux (Raw) au Japon et en Corée.</p>
              </div>
          </div>
      </div>
    </div>
  </div>

  <div class="faq-section">
    <h2>Foire aux <span>Questions</span></h2>
    <?php
    $faqs = [
      ['q' => 'Est-ce un abonnement avec prélèvement automatique ?',
       'a' => 'Absolument pas. Vous achetez un "Pass" pour une durée définie. Une fois le temps écoulé, votre accès s\'arrête. Pas de mauvaise surprise, pas de désabonnement compliqué à chercher.'],
      ['q' => 'Que se passe-t-il si j\'achète un Pass alors que je suis déjà VIP ?',
       'a' => 'Le système est intelligent. Les jours achetés s\'additionnent simplement à votre temps restant. (Ex: S\'il vous reste 5 jours et que vous achetez 30 jours, vous aurez 35 jours).'],
      ['q' => 'L\'activation est-elle immédiate ?',
       'a' => 'Oui. Dès que la transaction sécurisée est validée, le serveur MAGI met à jour votre sablier et tous les cadenas du site s\'ouvrent automatiquement.'],
    ];
    foreach ($faqs as $faq):
    ?>
    <div class="faq-item">
      <h3>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
          <?php echo esc_html($faq['q']); ?>
      </h3>
      <p><?php echo esc_html($faq['a']); ?></p>
    </div>
    <?php endforeach; ?>
  </div>

</div>

<div class="ns-modal-overlay" id="wipModal">
    <div class="ns-modal-card">
        <div class="ns-modal-icon">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
        </div>
        <h2>Connexion <span>Échouée</span></h2>
        <p>
            <strong>ALERTE SYSTÈME :</strong> Le portail de paiement Stripe est actuellement en cours de cryptage par le Commandement.<br><br>
            Gardez vos crédits en sécurité pour le moment. L'intégration de la passerelle sera bientôt finalisée !
        </p>
        <button class="ns-modal-close" id="closeWipModal">FERMER LE TERMINAL</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('wipModal');
    const openBtns = document.querySelectorAll('.ns-trigger-modal');
    const closeBtn = document.getElementById('closeWipModal');

    openBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            modal.classList.add('is-active');
        });
    });

    closeBtn.addEventListener('click', () => { modal.classList.remove('is-active'); });
    modal.addEventListener('click', (e) => { if (e.target === modal) { modal.classList.remove('is-active'); } });
});
</script>

<?php wp_footer(); ?>
</body>
</html>