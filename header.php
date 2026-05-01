<?php
/**
 * NERV — Premium Stealth Sidebar Header
 * Version : Glassmorphism & Centrage Parfait (App-like)
 */

if (!defined('ABSPATH')) exit;

$is_logged_in = is_user_logged_in();
$current_user = wp_get_current_user();
$current_roles = $is_logged_in ? (array) $current_user->roles : [];

$is_premium = function_exists('nerv_user_is_premium') ? nerv_user_is_premium() : false;
$is_admin   = current_user_can('manage_options');

$is_staff   = $is_admin
    || in_array('nervmodo', $current_roles, true)
    || in_array('nerv_modo', $current_roles, true)
    || in_array('magi', $current_roles, true)
    || in_array('marduk', $current_roles, true)
    || in_array('nerv_recruteur', $current_roles, true);

$cat_page       = get_page_by_path('catalogue');
$shop_page      = get_page_by_path('shop') ?: get_page_by_path('premium');
$user_page_obj  = get_page_by_path('profil') ?: get_page_by_path('user') ?: get_page_by_path('compte') ?: get_page_by_path('nerv-data');
$dogma_page     = get_page_by_path('central-dogma');

$catalogue_url  = $cat_page ? get_permalink($cat_page->ID) : home_url('/catalogue/');
$shop_url       = $shop_page ? get_permalink($shop_page->ID) : home_url('/shop/');
$user_page_url  = $user_page_obj ? get_permalink($user_page_obj->ID) : home_url('/nerv-data/');
$dogma_url      = $dogma_page ? get_permalink($dogma_page->ID) : home_url('/central-dogma/');
$logout_url     = wp_logout_url(home_url('/'));
$login_url      = function_exists('wp_login_url') ? home_url('/nerv-gate/') : wp_login_url(get_permalink());

$avatar_url = '';
$display_name = '';
$rank_label = 'Civil';

if ($is_logged_in) {
    $avatar_url   = get_user_meta($current_user->ID, 'nerv_avatar_url', true) ?: get_avatar_url($current_user->ID, ['size' => 96]);
    $display_name = $current_user->display_name ?: $current_user->user_login;

    if ($is_admin) {
        $rank_label = 'Commandant';
    } elseif (in_array('magi', $current_roles, true)) {
        $rank_label = 'Magi (Staff)';
    } elseif (in_array('marduk', $current_roles, true)) {
        $rank_label = 'Marduk (Staff)';
    } elseif (in_array('pilote', $current_roles, true)) {
        $rank_label = 'Pilote (Staff)';
    } elseif ($is_premium) {
        $rank_label = 'Premium VIP';
    }
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#000000">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,700;0,800;0,900;1,800&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <?php wp_head(); ?>

    <style>
        :root {
            --ns-bg: #000000;
            --ns-panel: rgba(8, 8, 8, 0.75);
            --ns-border: rgba(255,255,255,0.08);
            --ns-border-glow: rgba(244,201,93,0.3);
            --ns-y: #F4C95D;
            --ns-txt: #ffffff;
            --ns-muted: #888888;
            --ns-w: 88px;
            --ns-w-open: 280px;
            --ns-ease: cubic-bezier(0.16, 1, 0.3, 1);
        }

        body {
            background: var(--ns-bg);
            color: var(--ns-txt);
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
            margin: 0;
            -webkit-tap-highlight-color: transparent;
        }

        /* ----------------------------------------------------
           LA BARRE LATÉRALE (GLASSMORPHISM)
        ----------------------------------------------------- */
        .ns-sidebar {
            position: fixed;
            top: 0; left: 0; bottom: 0;
            height: 100%;
            width: var(--ns-w);
            background: var(--ns-panel);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-right: 1px solid var(--ns-border);
            z-index: 10020;
            display: flex;
            flex-direction: column;
            transition: width 0.5s var(--ns-ease), box-shadow 0.5s var(--ns-ease), border-color 0.5s var(--ns-ease);
            overflow: hidden;
            white-space: nowrap;
        }

        .ns-sidebar:hover {
            width: var(--ns-w-open);
            box-shadow: 20px 0 50px rgba(0,0,0,0.9);
            border-right-color: var(--ns-border-glow);
        }

        .ns-sys-deco {
            position: absolute; top: 0; right: 4px;
            font-family: monospace; font-size: 8px; color: var(--ns-muted);
            writing-mode: vertical-rl; transform: rotate(180deg);
            letter-spacing: 2px; opacity: 0.3; padding-top: 20px;
            transition: 0.3s;
        }
        .ns-sidebar:hover .ns-sys-deco { opacity: 0; }

        .ns-inner {
            display: flex;
            flex-direction: column;
            height: 100%;
            padding: 30px 16px;
            box-sizing: border-box;
            overflow-y: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .ns-inner::-webkit-scrollbar { display: none; }

        /* ----------------------------------------------------
           LOGO & MARQUE (EN HAUT)
        ----------------------------------------------------- */
        .ns-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            text-decoration: none;
            color: #fff;
            transition: all 0.4s var(--ns-ease);
            padding: 0 4px;
        }
        .ns-sidebar:hover .ns-brand { justify-content: flex-start; padding-left: 10px; }

        .ns-logo {
            width: 48px; height: 48px;
            flex-shrink: 0;
            background: linear-gradient(135deg, #111, #000);
            border: 1px solid rgba(244,201,93,0.4);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: inset 0 0 15px rgba(244,201,93,0.1), 0 5px 15px rgba(0,0,0,0.5);
            position: relative;
            overflow: hidden;
        }
        .ns-logo::after {
            content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
            background: linear-gradient(to bottom, transparent, rgba(244,201,93,0.1), transparent);
            transform: translateY(-100%); transition: 0.5s;
        }
        .ns-sidebar:hover .ns-logo::after { transform: translateY(100%); transition: 1.5s linear infinite; }
        .ns-logo img { width: 30px; height: 30px; object-fit: contain; z-index: 2; position: relative; }

        .ns-brand-text { display: none; opacity: 0; transform: translateX(-10px); transition: all 0.4s var(--ns-ease); }
        .ns-sidebar:hover .ns-brand-text { display: block; opacity: 1; transform: translateX(0); }
        .ns-brand-text strong { 
            display: block; 
            font-family: 'Barlow Condensed', sans-serif; 
            font-size: 28px; 
            font-style: italic; 
            text-transform: uppercase; 
            letter-spacing: 0.02em; 
            line-height: 1; 
            color: var(--ns-y);
            text-shadow: 0 0 15px rgba(244,201,93,0.4);
        }
        .ns-brand-text span { font-family: monospace; font-size: 9px; color: var(--ns-muted); letter-spacing: 0.1em; }

        /* ----------------------------------------------------
           NAVIGATION TACTIQUE (CENTRAGE MAGIQUE ICI)
        ----------------------------------------------------- */
        .ns-nav { 
            display: flex; 
            flex-direction: column; 
            gap: 12px; 
            margin: auto 0; /* MAGIE FLEXBOX : Pousse le menu au centre exact de l'écran */
        }
        
        .ns-link {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: var(--ns-muted);
            padding: 12px 0;
            height: 52px;
            border-radius: 12px;
            transition: all 0.3s var(--ns-ease);
            box-sizing: border-box;
            gap: 0;
            overflow: hidden;
        }
        
        .ns-link::before {
            content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%);
            width: 3px; height: 0%; background: var(--ns-y);
            transition: 0.3s var(--ns-ease); border-radius: 0 4px 4px 0;
            box-shadow: 0 0 10px var(--ns-y); opacity: 0;
        }

        .ns-sidebar:hover .ns-link { justify-content: flex-start; padding-left: 18px; gap: 16px; }
        
        .ns-link:hover { color: #fff; background: rgba(255,255,255,0.03); }
        .ns-link:hover::before { height: 40%; opacity: 0.5; }
        
        .ns-link.active { 
            color: #fff; 
            background: linear-gradient(90deg, rgba(244,201,93,0.1) 0%, transparent 100%); 
        }
        .ns-link.active::before { height: 60%; opacity: 1; }
        .ns-link.active .ns-icon { color: var(--ns-y); filter: drop-shadow(0 0 8px rgba(244,201,93,0.6)); }
        
        .ns-icon { width: 26px; height: 26px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; transition: 0.3s; }
        .ns-icon svg { width: 20px; height: 20px; stroke-width: 2; transition: 0.3s; }
        .ns-link:hover .ns-icon { transform: scale(1.1); }
        
        .ns-label {
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            opacity: 0;
            width: 0;
            transform: translateX(-10px);
            transition: all 0.3s var(--ns-ease);
        }
        .ns-sidebar:hover .ns-label { opacity: 1; width: auto; transform: translateX(0); }

        /* ----------------------------------------------------
           CARTE D'IDENTITÉ PILOTE (EN BAS)
        ----------------------------------------------------- */
        .ns-user { 
            padding: 24px 16px; 
            background: rgba(0,0,0,0.4); 
            border: 1px solid var(--ns-border);
            border-radius: 16px;
            text-align: center; 
            transition: 0.4s var(--ns-ease);
            position: relative;
            overflow: hidden;
            /* margin-top a été retiré ici pour que la nav prenne le centre */
        }
        .ns-sidebar:hover .ns-user { background: rgba(0,0,0,0.6); padding: 24px 20px; border-color: rgba(255,255,255,0.1); box-shadow: inset 0 0 20px rgba(0,0,0,0.5); }
        
        .ns-profile { display: flex; align-items: center; justify-content: center; gap: 16px; transition: 0.3s; }
        .ns-sidebar:hover .ns-profile { justify-content: flex-start; }
        
        .ns-avatar { 
            width: 48px; height: 48px; flex-shrink: 0; 
            border-radius: 12px; background: #000; 
            border: 2px solid <?php echo $is_premium ? 'var(--ns-y)' : 'var(--ns-border)'; ?>; 
            padding: 2px;
            box-shadow: <?php echo $is_premium ? '0 0 15px rgba(244,201,93,0.3)' : 'none'; ?>;
            transition: 0.3s;
        }
        .ns-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; }
        
        .ns-user-info { display: none; opacity: 0; text-align: left; transform: translateX(-10px); transition: all 0.4s var(--ns-ease); }
        .ns-sidebar:hover .ns-user-info { display: block; opacity: 1; transform: translateX(0); }
        .ns-user-name { color: #fff; font-weight: 800; font-size: 15px; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px; }
        
        .ns-user-rank { 
            display: inline-block; 
            background: <?php echo $is_premium ? 'rgba(244,201,93,0.15)' : 'rgba(255,255,255,0.05)'; ?>; 
            color: <?php echo $is_premium ? 'var(--ns-y)' : 'var(--ns-muted)'; ?>; 
            padding: 3px 8px; 
            border-radius: 6px; 
            font-size: 9px; 
            font-weight: 900; 
            text-transform: uppercase; 
            letter-spacing: 0.1em;
            border: 1px solid <?php echo $is_premium ? 'rgba(244,201,93,0.3)' : 'rgba(255,255,255,0.1)'; ?>;
        }

        .ns-actions { display: none; flex-direction: column; gap: 8px; margin-top: 20px; opacity: 0; transform: translateY(10px); transition: all 0.4s var(--ns-ease); }
        .ns-sidebar:hover .ns-actions { display: flex; opacity: 1; transform: translateY(0); }
        
        .ns-btn { display: flex; align-items: center; justify-content: center; height: 42px; border-radius: 10px; text-decoration: none; font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; transition: 0.2s; border: 1px solid transparent; }
        .ns-btn.y { background: var(--ns-y); color: #000; box-shadow: 0 5px 15px rgba(244,201,93,0.2); }
        .ns-btn.y:hover { filter: brightness(1.1); transform: translateY(-2px); box-shadow: 0 8px 20px rgba(244,201,93,0.4); }
        .ns-btn.ghost { background: rgba(255,255,255,0.03); color: #fff; border-color: rgba(255,255,255,0.1); }
        .ns-btn.ghost:hover { background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.3); color: var(--ns-red); }

        /* ----------------------------------------------------
           MOBILE & RESPONSIVE
        ----------------------------------------------------- */
        .ns-mobile-fab { 
            display: none; position: fixed; bottom: 24px; right: 24px; 
            width: 64px; height: 64px; border-radius: 50%; 
            background: rgba(10, 10, 10, 0.85); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(244,201,93,0.5); color: var(--ns-y); 
            z-index: 10010; align-items: center; justify-content: center; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.6), 0 0 20px rgba(244,201,93,0.2);
            cursor: pointer; transition: 0.3s;
        }
        .ns-mobile-fab:hover { transform: scale(1.05); background: var(--ns-y); color: #000; }
        
        .ns-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px); z-index: 10015; opacity: 0; pointer-events: none; transition: 0.4s; }
        .ns-overlay.active { opacity: 1; pointer-events: auto; }

        #page { margin-left: var(--ns-w); transition: margin-left 0.5s var(--ns-ease); min-height: 100vh; }
        /* Effet "Push" : Décale le site quand le menu est survolé (Sur PC uniquement) */
@media (min-width: 1025px) {
    body:has(.ns-sidebar:hover) #page {
        margin-left: var(--ns-w-open) !important;
    }
}

        @media (max-width: 1024px) {
            .ns-mobile-fab { display: flex; }
            .ns-sidebar { transform: translateX(-100%); width: var(--ns-w-open); background: rgba(5,5,5,0.95); }
            .ns-sidebar.active { transform: translateX(0); box-shadow: 20px 0 50px rgba(0,0,0,0.9); border-right-color: var(--ns-y); }
            .ns-brand-text, .ns-label, .ns-user-info, .ns-actions { display: block; opacity: 1; width: auto; transform: none; }
            .ns-brand, .ns-link, .ns-profile { justify-content: flex-start; padding-left: 14px; }
            .ns-sys-deco { display: none; }
            #page { margin-left: 0 !important; }
        }

        @media (min-width: 1025px) {
            body.admin-bar .ns-sidebar { top: 32px; height: calc(100% - 32px); }
        }
    </style>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<button class="ns-mobile-fab" id="nsMobileBtn" aria-label="Ouvrir le terminal">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="28" height="28" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="3"></circle>
        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
    </svg>
</button>

<div class="ns-overlay" id="nsOverlay"></div>

<aside class="ns-sidebar" id="nsSidebar">
    <div class="ns-sys-deco">SYS.ONLINE // MAGI_OS</div>
    
    <div class="ns-inner">
        
        <a href="<?php echo esc_url(home_url('/')); ?>" class="ns-brand">
            <div class="ns-logo">
                <img src="http://nervshiroe.free.nf/wp-content/uploads/2026/04/f12.png" alt="NERV">
            </div>
            <div class="ns-brand-text">
                <strong>NERVSCANS</strong>
                <span>BASE DE DONNÉES</span>
            </div>
        </a>

        <nav class="ns-nav">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="ns-link <?php echo is_front_page() ? 'active' : ''; ?>">
                <div class="ns-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                </div>
                <span class="ns-label">Accueil</span>
            </a>

            <a href="<?php echo esc_url($catalogue_url); ?>" class="ns-link <?php echo is_page('catalogue') ? 'active' : ''; ?>">
                <div class="ns-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
                </div>
                <span class="ns-label">Catalogue</span>
            </a>

            <a href="<?php echo esc_url($shop_url); ?>" class="ns-link <?php echo is_page('shop') || is_page('premium') ? 'active' : ''; ?>">
                <div class="ns-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                </div>
                <span class="ns-label">Premium</span>
            </a>

            <?php if ($is_logged_in): ?>
            <a href="<?php echo esc_url($user_page_url); ?>" class="ns-link <?php echo is_page('profil') || is_page('user') || is_page('compte') || is_page('nerv-data') ? 'active' : ''; ?>">
                <div class="ns-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </div>
                <span class="ns-label">Profil</span>
            </a>
            <?php endif; ?>

            <?php if ($is_staff): ?>
            <a href="<?php echo esc_url($dogma_url); ?>" class="ns-link <?php echo is_page('central-dogma') ? 'active' : ''; ?>">
                <div class="ns-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" style="color:var(--ns-y);"><polyline points="4 17 10 11 4 5"></polyline><line x1="12" y1="19" x2="20" y2="19"></line></svg>
                </div>
                <span class="ns-label" style="color:var(--ns-y);">Staff</span>
            </a>
            <?php endif; ?>
        </nav>

        <div class="ns-user">
            <?php if ($is_logged_in): ?>
                <div class="ns-profile">
                    <div class="ns-avatar">
                        <img src="<?php echo esc_url($avatar_url); ?>" alt="Avatar">
                    </div>
                    <div class="ns-user-info">
                        <div class="ns-user-name"><?php echo esc_html($display_name); ?></div>
                        <div class="ns-user-rank"><?php echo esc_html($rank_label); ?></div>
                    </div>
                </div>
                <div class="ns-actions">
                    <?php if ($is_admin || $is_staff): ?>
                        <a href="<?php echo esc_url(admin_url()); ?>" class="ns-btn y">Administration WP</a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url($logout_url); ?>" class="ns-btn ghost">Déconnexion</a>
                </div>
            <?php else: ?>
                <a href="<?php echo esc_url($login_url); ?>" class="ns-btn y" style="width:100%; height:48px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="16" height="16" stroke-width="2" style="margin-right:8px;"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                    Connexion
                </a>
            <?php endif; ?>
        </div>

    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const sidebar = document.getElementById('nsSidebar');
    const btn = document.getElementById('nsMobileBtn');
    const overlay = document.getElementById('nsOverlay');

    if(!sidebar || !btn || !overlay) return;

    function toggleMenu() {
        const isActive = sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        document.body.style.overflow = isActive ? 'hidden' : '';
        
        if(isActive) {
            btn.style.transform = 'rotate(90deg)';
            btn.style.borderColor = 'var(--ns-red)';
            btn.style.color = 'var(--ns-red)';
        } else {
            btn.style.transform = 'rotate(0deg)';
            btn.style.borderColor = 'var(--ns-y)';
            btn.style.color = 'var(--ns-y)';
        }
    }

    btn.addEventListener('click', toggleMenu);
    overlay.addEventListener('click', toggleMenu);

    document.addEventListener('keydown', function(e){
        if(e.key === 'Escape' && sidebar.classList.contains('active')) toggleMenu();
    });
});
</script>