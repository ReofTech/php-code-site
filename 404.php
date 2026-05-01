<?php
/**
 * The template for displaying 404 pages (not found).
 * NERV Stealth UI Version
 */

get_header();
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,700;0,800;0,900;1,700;1,800;1,900&family=Inter:wght@400;500;600;700;800;900&display=swap');

:root {
    --ns-bg: #000000;
    --ns-panel: #050505;
    --ns-stroke: rgba(255,255,255,0.06);
    --ns-yellow: #F4C95D;
    --ns-txt: #ffffff;
    --ns-gray: #888888;
}

/* RESET DU THÈME PARENT */
html, body {
    background: var(--ns-bg) !important;
    margin: 0; padding: 0;
    color: var(--ns-txt);
    font-family: 'Inter', sans-serif;
}

#page, .site, #content, .site-content, .body-wrap, main {
    background: transparent !important; border: none !important; box-shadow: none !important; margin: 0 !important; padding: 0 !important;
}

.c-page-header, .c-breadcrumb, .entry-header, .page-title, #wpadminbar { 
    display: none !important; 
}

/* DESIGN DE LA PAGE 404 */
.ns-404-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 85vh;
    text-align: center;
    padding: 40px 20px;
    position: relative;
    overflow: hidden;
}

/* Texte fantôme en arrière-plan */
.ns-404-bg-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 45vw;
    font-weight: 900;
    color: rgba(255,255,255,0.02);
    z-index: 0;
    pointer-events: none;
    user-select: none;
    line-height: 1;
}

.ns-404-content {
    position: relative;
    z-index: 1;
    background: var(--ns-panel);
    border: 1px solid var(--ns-stroke);
    padding: 60px 40px;
    border-radius: 24px;
    box-shadow: 0 30px 60px rgba(0,0,0,0.8);
    max-width: 600px;
    width: 100%;
    backdrop-filter: blur(10px);
}

.ns-system-alert {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 6px;
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.2);
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: 24px;
}

.ns-404-code {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 120px;
    font-weight: 900;
    font-style: italic;
    color: var(--ns-yellow);
    line-height: 1;
    margin: 0 0 10px;
    text-shadow: 0 0 40px rgba(244, 201, 93, 0.3);
}

.ns-404-title {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 36px;
    font-weight: 900;
    text-transform: uppercase;
    color: #fff;
    margin: 0 0 20px;
    letter-spacing: 0.02em;
}

.ns-404-desc {
    color: var(--ns-gray);
    font-size: 15px;
    line-height: 1.6;
    margin: 0 auto 40px;
    max-width: 450px;
}

.ns-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 16px 36px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    cursor: pointer;
    transition: 0.3s;
    text-decoration: none;
    border: none;
}

.ns-btn.gold {
    background: var(--ns-yellow);
    color: #000;
    box-shadow: 0 5px 20px rgba(244,201,93,0.3);
}

.ns-btn.gold:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 30px rgba(244,201,93,0.5);
    filter: brightness(1.1);
}

@media (max-width: 768px) {
    .ns-404-content { padding: 40px 20px; }
    .ns-404-code { font-size: 90px; }
    .ns-404-title { font-size: 28px; }
    .ns-404-bg-text { font-size: 60vw; }
}
</style>

<div class="ns-404-wrap">
    <div class="ns-404-bg-text">404</div>
    
    <div class="ns-404-content">
        <div class="ns-system-alert">Alerte Système MAGI</div>
        
        <div class="ns-404-code">404</div>
        <h1 class="ns-404-title">Dossier Introuvable</h1>
        
        <p class="ns-404-desc">
            Le signal a été perdu. Le fichier que vous recherchez n'existe plus, a été déplacé, ou vous n'avez pas l'accréditation nécessaire pour y accéder.
        </p>
        
        <a href="<?= esc_url(home_url('/')); ?>" class="ns-btn gold">Retour à la base</a>
    </div>
</div>

<?php get_footer(); ?>