<?php
/**
 * Template Name: NERV Single Manga
 * Version V17 : Layout Perfect & No Update Stat
 */

if (!defined('ABSPATH')) exit;
get_header();

global $wp_manga_functions;
$post_id = get_the_ID();

if (!$post_id) { get_footer(); return; }

$curr_user  = wp_get_current_user();
$curr_roles = is_user_logged_in() ? (array) $curr_user->roles : [];

$is_staff = current_user_can('manage_options') || in_array('nervmodo', $curr_roles, true) || in_array('magi', $curr_roles, true) || in_array('marduk', $curr_roles, true) || in_array('nerv_recruteur', $curr_roles, true);
$user_has_access = function_exists('nerv_user_is_premium') ? nerv_user_is_premium() : false;
if ($is_staff) { $user_has_access = true; }

if ($post_id == 1477) {
    if (!in_array('dummy', $curr_roles, true) && !$is_staff) {
        echo '<div style="margin-left:84px; padding:100px; text-align:center; color:#ff4d4d; font-family:monospace; background:#000; height:100vh;"><h1>ACCÈS REFUSÉ</h1></div>';
        get_footer(); return;
    }
    $chapters_data = class_exists('WP_Manga') ? $wp_manga_functions->get_latest_chapters($post_id, null, 100, 0) : [];
    ?>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;600;700&display=swap');
    body { background: #030000 !important; }
    .dummy-terminal { margin-left: 84px; min-height: 100vh; background: #030000; color: #ff4d4d; font-family: 'Fira Code', monospace; padding: 60px 20px; background-image: repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(255, 77, 77, 0.03) 2px, rgba(255, 77, 77, 0.03) 4px); }
    .dummy-wrap { max-width: 800px; margin: 0 auto; border: 1px solid rgba(255,77,77,0.3); padding: 40px; box-shadow: inset 0 0 50px rgba(255,0,0,0.1); }
    .dummy-header { border-bottom: 2px dashed rgba(255,77,77,0.5); padding-bottom: 20px; margin-bottom: 30px; text-align: center; }
    .dummy-header h1 { font-size: 32px; font-weight: 700; margin: 0 0 10px 0; letter-spacing: 2px; text-transform: uppercase; }
    .dummy-warning { background: rgba(255,0,0,0.1); padding: 10px; font-size: 12px; font-weight: 600; border-left: 4px solid #ff4d4d; }
    .dummy-list { list-style: none; padding: 0; margin: 0; }
    .dummy-item { display: flex; justify-content: space-between; padding: 15px; border-bottom: 1px solid rgba(255,77,77,0.2); transition: 0.2s; text-decoration: none; color: #ff4d4d; }
    .dummy-item:hover { background: rgba(255,77,77,0.1); padding-left: 20px; }
    .dummy-btn { background: #ff4d4d; color: #000; padding: 4px 12px; font-size: 11px; font-weight: 700; border-radius: 2px; text-transform: uppercase; }
    @media(max-width: 1024px) { .dummy-terminal { margin-left: 0; } }
    </style>
    <div class="dummy-terminal"><div class="dummy-wrap"><div class="dummy-header"><h1>PROTOCOLE DUMMY // 1477</h1><div class="dummy-warning">ATTENTION : Cible de test verrouillée.</div></div><ul class="dummy-list">
        <?php if (!empty($chapters_data)): foreach ($chapters_data as $chap): ?>
            <a href="<?php echo esc_url($wp_manga_functions->build_chapter_url($post_id, $chap['chapter_slug'])); ?>" class="dummy-item"><span>[ EXECUTE ] <?php echo esc_html($chap['chapter_name']); ?></span><span class="dummy-btn">Démarrer</span></a>
        <?php endforeach; else: ?>
            <li style="text-align:center; padding:20px;">[ SYSTÈME VIDE ]</li>
        <?php endif; ?>
    </ul></div></div>
    <?php get_footer(); return;
}

if (!function_exists('nerv_single_tax_list')) { function nerv_single_tax_list($terms) { if (!$terms || is_wp_error($terms)) return '—'; $names = []; foreach ($terms as $term) { $names[] = $term->name; } return implode(', ', $names); } }
if (!function_exists('nerv_single_cover_url')) { function nerv_single_cover_url($post_id) { if (has_post_thumbnail($post_id)) { $url = get_the_post_thumbnail_url($post_id, 'large'); if ($url) return $url; } $ext = get_post_meta($post_id, '_thumbnail_ext_url', true); if (!empty($ext)) return $ext; $thumb = get_post_meta($post_id, '_wp_manga_thumbnail', true); if (!empty($thumb)) return $thumb; return 'https://placehold.co/800x1100/111111/F4C95D?text=NERV'; } }
if (!function_exists('nerv_single_related_status_label')) { function nerv_single_related_status_label($pid) { $raw = strtolower(trim((string) get_post_meta($pid, '_wp_manga_status', true))); $map = ['on-going'=>'En cours', 'ongoing'=>'En cours', 'end'=>'Terminé', 'completed'=>'Terminé', 'canceled'=>'Annulé']; return $map[$raw] ?? 'En cours'; } }
if (!function_exists('nerv_single_chapter_preview')) { function nerv_single_chapter_preview($post_id, $chapter_id, $fallback_url) { $img = trim((string) get_post_meta($post_id, '_chapter_image_' . $chapter_id, true)); $pos = trim((string) get_post_meta($post_id, '_chapter_image_pos_' . $chapter_id, true)) ?: 'center 25%'; return ['url' => $img ?: $fallback_url, 'pos' => $pos]; } }

$title        = get_the_title($post_id);
$cover_url    = nerv_single_cover_url($post_id);
$content_html = apply_filters('the_content', get_post_field('post_content', $post_id));
$content_html = trim((string) $content_html) ? $content_html : '<p>Aucune description disponible pour le moment.</p>';

$status_label  = nerv_single_related_status_label($post_id);
$manga_authors = get_the_terms($post_id, 'wp-manga-author');
$manga_artists = get_the_terms($post_id, 'wp-manga-artist'); 
$manga_genres  = get_the_terms($post_id, 'wp-manga-genre');
$manga_type    = get_post_meta($post_id, '_wp_manga_type', true) ?: 'Manga';

$alt_title     = get_post_meta($post_id, '_wp_manga_alternative', true) ?: get_post_meta($post_id, '_wp_manga_alternative_manga', true);
$banner_url    = get_post_meta($post_id, 'manga_banner', true) ?: get_post_meta($post_id, '_manga_banner', true);
if (empty($banner_url)) {
    $banner_url = $cover_url;
}

$fav_nonce     = wp_create_nonce('nerv_fav_' . $post_id);
$nonce_lang    = wp_create_nonce('chapter_actions_nonce');

$is_fav = false;
if (is_user_logged_in() && function_exists('get_user_meta')) {
    $bookmarks = get_user_meta(get_current_user_id(), '_wp_manga_bookmark', true);
    if (!empty($bookmarks) && is_array($bookmarks)) {
        foreach ($bookmarks as $bm) {
            if (isset($bm['id']) && $bm['id'] == $post_id) { $is_fav = true; break; }
        }
    }
}

$first_chapter_url  = '#';
$latest_chapter_url = '#';
$last_num           = '';
$chapters_data      = [];

if (class_exists('WP_Manga') && isset($wp_manga_functions)) {
    $chapters_data = $wp_manga_functions->get_latest_chapters($post_id, null, 2000, 0);
    if ($chapters_data && is_array($chapters_data) && count($chapters_data) > 0) {
        $latest_chap = reset($chapters_data);
        $first_chap  = end($chapters_data);
        if (!empty($latest_chap['chapter_slug'])) {
            $latest_chapter_url = $wp_manga_functions->build_chapter_url($post_id, $latest_chap['chapter_slug']);
            $last_num = trim(preg_replace('/^(chapter|chapitre|ch)[_\-]*0*/i', '', $latest_chap['chapter_slug']));
        }
        if (!empty($first_chap['chapter_slug'])) {
            $first_chapter_url = $wp_manga_functions->build_chapter_url($post_id, $first_chap['chapter_slug']);
        }
    }
}
$total_chapters = is_array($chapters_data) ? count($chapters_data) : 0;
$top3_data = array_slice($chapters_data, 0, 3, true);
$lang_flags_map = ['VF'=>'🇫🇷', 'VA'=>'🇬🇧', 'ES'=>'🇪🇸', 'RAW'=>'🇯🇵', 'VO'=>'🇯🇵'];
$lang_choices = ['VF', 'VA', 'ES', 'RAW', 'VO'];
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,600;0,700;0,800;1,700;1,800&family=Inter:wght@400;500;600;700;800&display=swap');
:root { --ns-bg: #000000; --ns-bg2: #050505; --ns-panel: #0a0a0a; --ns-panel-2: #111111; --ns-line: rgba(255,255,255,0.06); --ns-line-soft: rgba(255,255,255,0.035); --ns-yellow: #F4C95D; --ns-text: #ffffff; --ns-muted: #aaaaaa; --ns-soft: #666666; --ns-wrap: min(1300px, calc(100vw - 40px)); }
@media (max-width:1100px) { :root { --ns-wrap: min(1400px, calc(100vw - 24px)); } }
html, body { background: #000000 !important; }
#page, .site, #content, .site-content, .c-page, .c-page-content, .body-wrap, .main-container, .content-area, main { background: transparent !important; border: none !important; box-shadow: none !important; padding: 0 !important; margin: 0 !important; }
.c-page-header, .c-breadcrumb, .entry-header, .posted-on, .post-meta, .entry-meta, .entry-title, .c-sub-header-nav, .page-title { display: none !important; }
.nerv-single { background: #000000; color: var(--ns-text); padding: 30px 0 80px; font-family: 'Inter', sans-serif; min-height: 100vh; }
.nerv-single * { box-sizing: border-box; }
.nerv-single__wrap { width: var(--ns-wrap); max-width: var(--ns-wrap); margin: 0 auto; }

/* HERO: Alignement et Hauteur Fixe */
.ns-hero { position: relative; border-radius: 20px; overflow: hidden; background: #050505; margin-bottom: 20px; border: 1px solid var(--ns-line); isolation: isolate; }
.ns-hero__bg { position: absolute; inset: -30px; background-size: cover; background-position: center 25%; filter: blur(15px) brightness(0.35); z-index: 1; transform: scale(1.05); }
.ns-hero__gradient { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.4) 60%, rgba(0,0,0,0.1) 100%); z-index: 2; }

/* ALIGNEMENT PARFAIT ICI */
.ns-hero__inner { position: relative; z-index: 3; display: flex; gap: 40px; padding: 50px 40px; align-items: flex-start; }

.ns-poster { width: 220px; height: 308px; flex-shrink: 0; border-radius: 12px; overflow: hidden; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 30px 60px rgba(0,0,0,0.8); background: #000; }
.ns-poster img { width: 100%; height: 100%; object-fit: cover; display: block; } 

.ns-main { flex: 1; min-width: 0; min-height: 308px; display: flex; flex-direction: column; justify-content: space-between; }

.ns-tags { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
.ns-tag { display: inline-flex; align-items: center; padding: 4px 12px; border-radius: 6px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: var(--ns-text); font-size: 10px; font-weight: 800; letter-spacing: 0.1em; text-transform: uppercase; }
.ns-tag--status { background: var(--ns-yellow); color: #000; border-color: var(--ns-yellow); }

.ns-title { margin: 0; color: #fff; font-family: 'Barlow Condensed', sans-serif; font-style: italic; font-size: clamp(42px, 5vw, 68px); line-height: 0.95; text-transform: uppercase; letter-spacing: -0.02em; text-shadow: 0 4px 20px rgba(0,0,0,0.8); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.ns-alt-title { font-size: 16px; color: rgba(255,255,255,0.6); margin-top: 10px; font-weight: 500; font-style: italic; }

.ns-genre-tags { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 16px; }
.ns-genre-tag { display: inline-flex; align-items: center; padding: 5px 14px; border-radius: 6px; background: rgba(244, 201, 93, 0.1); border: 1px solid rgba(244, 201, 93, 0.3); color: var(--ns-yellow); font-size: 11px; font-weight: 800; text-transform: uppercase; text-decoration: none; transition: 0.2s; }
.ns-genre-tag:hover { background: var(--ns-yellow); color: #000; }

/* BOUTONS FORCÉS ET VISIBLES */
.ns-action-buttons { display: flex !important; visibility: visible !important; flex-wrap: wrap; gap: 14px; margin-top: auto; }
.ns-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 44px; padding: 0 24px; border-radius: 8px; text-decoration: none; font-size: 12px; font-weight: 900; letter-spacing: 0.05em; text-transform: uppercase; transition: all 0.2s ease; border: 1px solid transparent; cursor: pointer; }
.ns-btn--gold { color: #000; background: var(--ns-yellow); box-shadow: 0 8px 20px rgba(244, 201, 93, 0.25); }
.ns-btn--gold:hover { transform: translateY(-2px); filter: brightness(1.1); box-shadow: 0 12px 25px rgba(244, 201, 93, 0.4); }
.ns-btn--ghost { background: rgba(255,255,255,0.05); color: #ffffff; border-color: rgba(255,255,255,0.1); }
.ns-btn--ghost:hover, .ns-btn.is-active { border-color: var(--ns-yellow); background: rgba(244, 201, 93, 0.1); color: var(--ns-yellow); }

/* Modification de la Grid HUD (4 colonnes) */
.ns-hud-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: var(--ns-line-soft); border: 1px solid var(--ns-line); border-radius: 16px; margin-bottom: 24px; overflow: hidden; }
.ns-hud-item { background: var(--ns-panel); padding: 20px 24px; display: flex; flex-direction: column; justify-content: center; transition: background 0.3s; }
.ns-hud-item:hover { background: #111111; }
.ns-hud-lbl { font-size: 10px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: var(--ns-soft); margin-bottom: 6px; }
.ns-hud-val { font-size: 14px; font-weight: 600; color: #fff; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
.ns-hud-val.highlight { font-family: 'Barlow Condensed', sans-serif; font-size: 26px; font-style: italic; font-weight: 800; color: var(--ns-yellow); text-transform: uppercase; }

.ns-panel { border-radius: 16px; background: var(--ns-panel); border: 1px solid var(--ns-line); margin-bottom: 24px; }
.ns-panel__head { padding: 20px 24px; border-bottom: 1px solid var(--ns-line-soft); display: flex; justify-content: space-between; align-items: center; }
.ns-panel__title { margin: 0; font-family: 'Barlow Condensed', sans-serif; font-size: 28px; font-style: italic; line-height: 1; text-transform: uppercase; color: #fff; }
.ns-panel__body { padding: 24px; }
.ns-richtext { color: rgba(255,255,255,0.7); font-size: 14px; line-height: 1.8; }
.ns-preview-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
.ns-preview-card { position: relative; aspect-ratio: 16/9; border-radius: 12px; overflow: hidden; display: block; text-decoration: none; color: inherit; background: #050505; border: 1px solid var(--ns-line); box-shadow: 0 10px 20px rgba(0,0,0,0.5); transition: 0.3s ease; }
.ns-preview-bg { position: absolute; inset: 0; background-size: cover; background-repeat: no-repeat; background-position: center 30%; transition: transform 0.4s ease; }
.ns-preview-card:hover .ns-preview-bg { transform: scale(1.06); }
.ns-preview-card:hover { border-color: rgba(244, 201, 93, 0.4); }
.ns-preview-bg::after { content: ""; position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.4) 60%, transparent 100%); }
.ns-preview-content { position: absolute; bottom: 0; left: 0; right: 0; padding: 16px; z-index: 2; transition: 0.3s; }
.ns-preview-ch { font-size: 11px; color: var(--ns-yellow); font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; transition: 0.3s; }
.ns-preview-title { margin-top: 4px; font-family: 'Barlow Condensed', sans-serif; font-size: 20px; line-height: 1.1; font-style: italic; color: #fff; text-transform: uppercase; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; transition: 0.3s; }
.ns-preview-line { margin-top: 8px; width: 40px; height: 3px; border-radius: 999px; background: var(--ns-yellow); }
.ns-preview-flag { position: absolute; top: 12px; right: 12px; z-index: 3; display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 999px; background: rgba(0,0,0,0.6); border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(8px); font-size: 14px; }

/* STYLE PREMIUM POUR LES CARTES D'APERÇU */
.ns-preview-card.is-premium-card { border-color: var(--ns-yellow); box-shadow: 0 10px 30px rgba(244, 201, 93, 0.15); }
.ns-preview-card.is-premium-card .ns-preview-title,
.ns-preview-card.is-premium-card .ns-preview-ch { color: var(--ns-yellow); text-shadow: 0 0 10px rgba(244, 201, 93, 0.5); }
.ns-preview-card.is-premium-card .ns-preview-bg::after { background: linear-gradient(to top, rgba(20, 15, 0, 0.95) 0%, rgba(0,0,0,0.4) 60%, transparent 100%); }
.ns-preview-lock-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(3px); z-index: 4; display: flex; align-items: center; justify-content: center; transition: 0.3s; }
.ns-preview-lock-icon { width: 44px; height: 44px; background: rgba(0, 0, 0, 0.8); border: 2px solid var(--ns-yellow); color: var(--ns-yellow); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; box-shadow: 0 0 20px rgba(244, 201, 93, 0.4); transition: 0.3s; }
.ns-preview-card:hover .ns-preview-lock-overlay { background: rgba(0,0,0,0.6); }
.ns-preview-card:hover .ns-preview-lock-icon { transform: scale(1.1); box-shadow: 0 0 30px rgba(244, 201, 93, 0.6); }

.ns-ch-tools { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.ns-ch-search-wrap { position: relative; margin-bottom: 20px; }
.ns-ch-search { width: 100%; background: #050505; border: 1px solid var(--ns-line); color: #fff; padding: 14px 16px; border-radius: 10px; font-size: 13px; font-family: 'Inter', sans-serif; outline: none; transition: 0.2s; box-sizing: border-box; }
.ns-ch-search:focus { border-color: var(--ns-yellow); box-shadow: 0 0 10px rgba(244, 201, 93, 0.1); }
.search-hidden { display: none !important; }
.ns-tool-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 38px; padding: 0 14px; border-radius: 8px; border: 1px solid var(--ns-line); background: var(--ns-panel-2); color: #fff; font-size: 11px; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase; cursor: pointer; transition: all 0.2s ease; }
.ns-tool-btn:hover { border-color: rgba(255,255,255,0.2); background: rgba(255,255,255,0.08); }
.ns-tool-btn--gold { background: rgba(244, 201, 93, 0.1); border-color: rgba(244, 201, 93, 0.3); color: var(--ns-yellow); }
.ns-tool-btn--gold:hover { background: var(--ns-yellow); color: #000; }

.ns-chapters { display: grid; gap: 12px; }
.ns-ch-row-wrap { border-radius: 10px; background: #0a0a0a; border: 1px solid var(--ns-line-soft); overflow: hidden; transition: all 0.2s ease; }
.ns-ch-row-wrap:hover { border-color: rgba(244, 201, 93, 0.3); background: rgba(255,255,255,0.03); }
.ns-ch-row-wrap.is-hidden-init { display: none; }
.ns-ch-row { display: grid; grid-template-columns: 120px minmax(0, 1fr) auto; gap: 16px; align-items: center; padding: 12px; text-decoration: none; color: inherit; transition: opacity 0.2s; }
.ns-ch-row.is-read { opacity: 0.5; }
.ns-ch-row.is-read .ns-read { background: transparent; color: var(--ns-soft); border-color: rgba(255,255,255,0.1); }
.ns-ch-thumb { width: 120px; height: 76px; border-radius: 8px; overflow: hidden; border: 1px solid rgba(255,255,255,0.06); background: #050505; flex-shrink: 0; }
.ns-ch-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.ns-ch-main { min-width: 0; }
.ns-ch-title { margin: 0; color: #fff; font-size: 14px; font-weight: 700; line-height: 1.4; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: 0.2s; }
.ns-ch-meta { margin-top: 6px; display: flex; flex-wrap: wrap; gap: 10px; color: var(--ns-soft); font-size: 11px; font-weight: 600; }
.ns-ch-right { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; justify-content: flex-end; }
.ns-lang-badge { display: inline-flex; align-items: center; gap: 6px; min-height: 28px; padding: 0 10px; border-radius: 999px; background: rgba(244, 201, 93, 0.1); border: 1px solid rgba(244, 201, 93, 0.2); color: var(--ns-yellow); font-size: 11px; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase; cursor: default; }
.ns-lang-badge.staff { cursor: pointer; }
.ns-read { color: #fff; font-size: 11px; font-weight: 900; letter-spacing: 0.1em; text-transform: uppercase; background: rgba(255,255,255,0.05); padding: 6px 12px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); transition: 0.2s; display: flex; align-items: center; }
.ns-ch-row:hover .ns-read { background: var(--ns-yellow); color: #000; border-color: var(--ns-yellow); }
.ns-new { display: inline-flex; align-items: center; min-height: 22px; padding: 0 8px; border-radius: 4px; background: var(--ns-yellow); color: #000; font-size: 10px; font-weight: 900; }

/* STYLE PREMIUM (LISTE) - TOUJOURS JAUNE QUAND PREMIUM */
.ns-ch-row-wrap.is-premium { border-left: 4px solid var(--ns-yellow); background: linear-gradient(to right, rgba(244, 201, 93, 0.08), transparent); border-color: rgba(244, 201, 93, 0.25); }
.ns-ch-row-wrap.is-premium:hover { background: linear-gradient(to right, rgba(244, 201, 93, 0.15), rgba(255,255,255,0.03)); border-color: var(--ns-yellow); }
.ns-ch-row-wrap.is-premium .ns-ch-title { color: var(--ns-yellow); text-shadow: 0 0 10px rgba(244, 201, 93, 0.2); }

/* EDITION UI */
.ns-editbar { display: none; align-items: center; gap: 15px; flex-wrap: wrap; margin-bottom: 20px; padding: 15px 20px; border-radius: 12px; background: #080808; border: 1px solid rgba(244, 201, 93, 0.25); }
.ns-editbar.show { display: flex; }
.ns-edit-select { min-width: 140px; background: #000; border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 8px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; outline: none; cursor: pointer; transition: 0.2s; }
.ns-edit-select:focus { border-color: var(--ns-yellow); }
.ns-edit-select option { background: #080808; color: #fff; padding: 10px; }
.ns-ch-edit { display: none; padding: 15px 20px; background: rgba(244, 201, 93, 0.02); border-top: 1px solid rgba(255, 255, 255, 0.05); align-items: center; justify-content: space-between; gap: 20px; }
.ns-ch-row-wrap.editing .ns-ch-edit { display: flex; }
.ns-edit-label { font-size: 10px; font-weight: 900; text-transform: uppercase; color: var(--ns-muted); letter-spacing: 1px; }
.ns-row-check { width: 18px; height: 18px; cursor: pointer; border: 1px solid rgba(255,255,255,0.2); accent-color: var(--ns-yellow); background: transparent; }

@media (max-width:820px){ .ns-ch-edit { flex-direction: column; align-items: flex-start; gap: 12px; } }
@media (max-width:1180px){ .ns-hero__inner { gap: 30px; padding: 40px 30px; } .ns-preview-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width:820px){ 
    .nerv-single { padding-top: 16px; } 
    .ns-hero__inner { flex-direction: column; align-items: center; padding: 30px 20px; } 
    .ns-poster { width: 180px; height: 252px; margin-bottom: 10px; } 
    .ns-main { min-height: auto; text-align: center; justify-content: center; } 
    .ns-title { font-size: 42px; text-align: center; } 
    .ns-alt-title { text-align: center; } 
    .ns-tags, .ns-genre-tags, .ns-action-buttons { justify-content: center; margin-top: 16px; } 
    .ns-hud-stats { grid-template-columns: 1fr 1fr; } 
    .ns-preview-grid { grid-template-columns: 1fr; } 
    .ns-ch-row { grid-template-columns: 90px minmax(0, 1fr); } 
    .ns-ch-thumb { width: 90px; height: 60px; } 
    .ns-ch-right { grid-column: 1 / -1; justify-content: space-between; } 
}
</style>

<div class="nerv-single" data-post-id="<?php echo esc_attr($post_id); ?>">
    <div class="nerv-single__wrap">

        <section class="ns-hero">
            <div class="ns-hero__bg" style="background-image:url('<?php echo esc_url($banner_url); ?>');"></div>
            <div class="ns-hero__gradient"></div>

            <div class="ns-hero__inner">
                <div class="ns-poster">
                    <img src="<?php echo esc_url($cover_url); ?>" alt="<?php echo esc_attr($title); ?>">
                </div>

                <div class="ns-main">
                    <div class="ns-main__top">
                        <div class="ns-tags">
                            <span class="ns-tag ns-tag--status"><?php echo esc_html($status_label); ?></span>
                            <span class="ns-tag"><?php echo esc_html($manga_type); ?></span>
                            <?php if ($last_num !== '') : ?>
                                <span class="ns-tag">Dernier : Ch. <?php echo esc_html($last_num); ?></span>
                            <?php endif; ?>
                        </div>

                        <h1 class="ns-title"><?php echo esc_html($title); ?></h1>

                        <?php if (!empty($alt_title)) : ?>
                            <div class="ns-alt-title"><?php echo esc_html($alt_title); ?></div>
                        <?php endif; ?>

                        <?php if ($manga_genres && !is_wp_error($manga_genres)) : ?>
                            <div class="ns-genre-tags">
                                <?php foreach ($manga_genres as $genre) : ?>
                                    <a href="<?php echo esc_url(get_term_link($genre)); ?>" class="ns-genre-tag"><?php echo esc_html($genre->name); ?></a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="ns-action-buttons">
                        <a class="ns-btn ns-btn--gold" href="<?php echo esc_url($first_chapter_url !== '#' ? $first_chapter_url : '#'); ?>">Commencer la lecture</a>
                        
                        <a class="ns-btn ns-btn--ghost" href="<?php echo esc_url($latest_chapter_url !== '#' ? $latest_chapter_url : '#'); ?>">Dernier chapitre</a>
                        
                        <?php if (is_user_logged_in()) : ?>
                            <button type="button" class="ns-btn ns-btn--ghost js-nerv-fav <?php echo $is_fav ? 'is-active' : ''; ?>" data-id="<?php echo esc_attr($post_id); ?>" data-nonce="<?php echo esc_attr($fav_nonce); ?>">
                                <?php echo $is_fav ? 'Retirer des favoris' : 'Ajouter aux favoris'; ?>
                            </button>
                        <?php else : ?>
                            <button type="button" class="ns-btn ns-btn--ghost" onclick="alert('Connectez-vous pour utiliser cette fonction.');">Ajouter aux favoris</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="ns-hud-stats">
            <div class="ns-hud-item">
                <span class="ns-hud-lbl">Statut</span>
                <span class="ns-hud-val highlight"><?php echo esc_html($status_label); ?></span>
            </div>
            <div class="ns-hud-item">
                <span class="ns-hud-lbl">Chapitres</span>
                <span class="ns-hud-val highlight"><?php echo esc_html($total_chapters); ?></span>
            </div>
            <div class="ns-hud-item">
                <span class="ns-hud-lbl">Auteur</span>
                <span class="ns-hud-val"><?php echo esc_html(nerv_single_tax_list($manga_authors)); ?></span>
            </div>
            <div class="ns-hud-item">
                <span class="ns-hud-lbl">Artiste</span>
                <span class="ns-hud-val"><?php echo esc_html(nerv_single_tax_list($manga_artists)); ?></span>
            </div>
        </section>

        <section class="ns-panel">
            <div class="ns-panel__head">
                <h2 class="ns-panel__title">Synopsis</h2>
            </div>
            <div class="ns-panel__body">
                <div class="ns-richtext"><?php echo wp_kses_post($content_html); ?></div>
            </div>
        </section>

        <?php if (!empty($top3_data)) : ?>
            <section class="ns-panel">
                <div class="ns-panel__head">
                    <h2 class="ns-panel__title">Aperçu</h2>
                </div>
                <div class="ns-panel__body">
                    <div class="ns-preview-grid">
                        <?php
                        foreach ($top3_data as $chap) :
                            $chapter_id   = $chap['chapter_id'] ?? 0;
                            $chapter_slug = $chap['chapter_slug'] ?? '';
                            $chapter_name = $chap['chapter_name'] ?? '';
                            $chapter_url  = $chapter_slug ? $wp_manga_functions->build_chapter_url($post_id, $chapter_slug) : get_permalink($post_id);
                            $lang_code    = strtoupper(trim((string) get_post_meta($post_id, '_chapter_lang_' . $chapter_id, true))) ?: 'VF';
                            $lang_flag    = $lang_flags_map[$lang_code] ?? '🌐';

                            if (!$chapter_name) {
                                $chapter_name = $chapter_slug ? ucwords(str_replace(['-', '_'], ' ', $chapter_slug)) : 'Chapitre';
                            }

                            // === MOTEUR EARLY ACCESS ===
                            $is_premium = false;
                            $is_locked = false;
                            $custom_unlock = get_post_meta($post_id, '_chapter_unlock_' . $chapter_id, true);
                            if (!empty($custom_unlock)) {
                                $ts = strtotime($custom_unlock);
                                if ($ts && current_time('timestamp') < $ts) {
                                    $is_premium = true;
                                    if (!$user_has_access) {
                                        $is_locked = true;
                                    }
                                }
                            }

                            $preview_data = nerv_single_chapter_preview($post_id, $chapter_id, $cover_url);
                        ?>
                            <a class="ns-preview-card ns-ch-lang-target <?php echo $is_premium ? 'is-premium-card' : ''; ?>" href="<?php echo esc_url($chapter_url); ?>" data-cid="<?php echo esc_attr($chapter_id); ?>" data-lang="<?php echo esc_attr($lang_code); ?>">
                                <div class="ns-preview-bg" style="background-image:url('<?php echo esc_url($preview_data['url']); ?>'); background-position: <?php echo esc_attr($preview_data['pos']); ?>;"></div>
                                
                                <?php if ($is_premium) : ?>
                                    <div class="ns-preview-lock-overlay" style="<?php echo !$is_locked ? 'background:rgba(0,0,0,0.2); backdrop-filter:none;' : ''; ?>">
                                        <div class="ns-preview-lock-icon">
                                            <?php if ($is_locked) : ?>
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                            <?php else : ?>
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 9.9-1"></path></svg>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <span class="ns-preview-flag <?php echo $is_staff ? 'staff badge-lang ns-admin-badge' : ''; ?>"
                                      data-cid="<?php echo esc_attr($chapter_id); ?>"
                                      data-mid="<?php echo esc_attr($post_id); ?>"
                                      data-nonce="<?php echo esc_attr($nonce_lang); ?>"
                                      data-curr="<?php echo esc_attr($lang_code); ?>"
                                      <?php if ($is_staff) : ?>onclick="event.preventDefault()"<?php endif; ?>>
                                    <span class="ns-flag-text"><?php echo esc_html($lang_flag); ?></span>
                                </span>
                                <div class="ns-preview-content">
                                    <span class="ns-preview-ch"><?php echo esc_html($chapter_name); ?></span>
                                    <span class="ns-preview-title"><?php echo esc_html($title); ?></span>
                                    <span class="ns-preview-line"></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <section class="ns-panel">
            <div class="ns-panel__head" style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                <h2 class="ns-panel__title">Chapitres (<?php echo esc_html($total_chapters); ?>)</h2>
                <div class="ns-ch-tools">
                    <button class="ns-tool-btn" id="nsSortBtn" type="button">↓ Récent</button>
                    <?php if ($is_staff) : ?>
                        <button class="ns-tool-btn ns-tool-btn--gold" id="nsToggleEdit" type="button">Mode édition</button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="ns-panel__body">

                <?php if ($total_chapters > 5) : ?>
                <div class="ns-ch-search-wrap">
                    <input type="text" id="nsChapSearch" class="ns-ch-search" placeholder="Rechercher un chapitre (ex: 42)...">
                </div>
                <?php endif; ?>

                <?php if ($is_staff && !empty($chapters_data)) : ?>
                    <div class="ns-editbar" id="nsEditBar" data-mid="<?php echo esc_attr($post_id); ?>" data-nonce="<?php echo esc_attr($nonce_lang); ?>">
                        <button type="button" class="ns-tool-btn" id="nsSelectVisible">Sél. visibles</button>
                        <button type="button" class="ns-tool-btn" id="nsClearSelection">Vider sélection</button>
                        <div style="width:1px; height:20px; background:rgba(255,255,255,0.1); margin:0 5px;"></div>
                        <select class="ns-edit-select" id="nsBulkLang">
                            <?php foreach ($lang_choices as $opt) : ?>
                                <option value="<?php echo esc_attr($opt); ?>"><?php echo esc_html($opt); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="ns-tool-btn ns-tool-btn--gold" id="nsApplySelected">Appliquer Sélection</button>
                        <button type="button" class="ns-tool-btn" id="nsApplyAll" style="border-color:var(--ns-yellow); color:var(--ns-yellow);">Appliquer à tous</button>
                        <span class="ns-edit-label" id="nsEditStat">0 sélectionné</span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($chapters_data)) : ?>
                    <div class="ns-chapters" id="nsChapterList">
                        <?php
                        $chapter_index = 0;
                        foreach ($chapters_data as $chap) :
                            $chapter_id   = $chap['chapter_id'] ?? 0;
                            $chapter_slug = $chap['chapter_slug'] ?? '';
                            $chapter_name = $chap['chapter_name'] ?? '';
                            $chapter_date = $chap['date'] ?? '';
                            $chapter_url  = $chapter_slug ? $wp_manga_functions->build_chapter_url($post_id, $chapter_slug) : get_permalink($post_id);

                            $lang_code = strtoupper(trim((string) get_post_meta($post_id, '_chapter_lang_' . $chapter_id, true))) ?: 'VF';
                            $lang_flag = $lang_flags_map[$lang_code] ?? '🌐';

                            if (!$chapter_name) $chapter_name = $chapter_slug ? ucwords(str_replace(['-', '_'], ' ', $chapter_slug)) : 'Chapitre';

                            $preview_data = nerv_single_chapter_preview($post_id, $chapter_id, $cover_url);
                            $diff   = $chapter_date ? human_time_diff(strtotime($chapter_date), time()) : '';
                            $is_new = $chapter_date && (time() - strtotime($chapter_date)) < 43200; // 12h
                            $hidden = $chapter_index >= 22 ? ' is-hidden-init' : '';

                            // === MOTEUR EARLY ACCESS MANUEL ===
                            $is_premium = false;
                            $is_locked = false;
                            $custom_unlock = get_post_meta($post_id, '_chapter_unlock_' . $chapter_id, true);
                            
                            if (!empty($custom_unlock)) {
                                $ts = strtotime($custom_unlock);
                                if ($ts && current_time('timestamp') < $ts) {
                                    $is_premium = true;
                                    if (!$user_has_access) {
                                        $is_locked = true;
                                    }
                                }
                            }
                        ?>
                            <div class="ns-ch-row-wrap ns-ch-lang-target <?php echo $is_premium ? 'is-premium' : ''; ?> <?php echo esc_attr($hidden); ?>" data-cid="<?php echo esc_attr($chapter_id); ?>" data-slug="<?php echo esc_attr($chapter_slug); ?>" data-lang="<?php echo esc_attr($lang_code); ?>" data-title="<?php echo esc_attr(strtolower($chapter_name)); ?>" <?php if ($is_locked) echo 'title="Déblocage Premium le ' . date_i18n('d/m à H:i', $ts) . '"'; ?>>
                                <a class="ns-ch-row" href="<?php echo esc_url($chapter_url); ?>">
                                    <div class="ns-ch-thumb">
                                        <img src="<?php echo esc_url($preview_data['url']); ?>" style="object-position: <?php echo esc_attr($preview_data['pos']); ?>" alt="<?php echo esc_attr($chapter_name); ?>" loading="lazy">
                                    </div>

                                    <div class="ns-ch-main">
                                        <h3 class="ns-ch-title"><?php echo esc_html($chapter_name); ?></h3>
                                        <div class="ns-ch-meta">
                                            <?php if ($diff) : ?><span>Ajouté il y a <?php echo esc_html($diff); ?></span><?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="ns-ch-right">
                                        <span class="ns-lang-badge <?php echo $is_staff ? 'staff badge-lang ns-admin-badge' : ''; ?>"
                                              data-cid="<?php echo esc_attr($chapter_id); ?>"
                                              data-mid="<?php echo esc_attr($post_id); ?>"
                                              data-nonce="<?php echo esc_attr($nonce_lang); ?>"
                                              data-curr="<?php echo esc_attr($lang_code); ?>"
                                              <?php if ($is_staff) : ?>onclick="event.preventDefault()"<?php endif; ?>>
                                            <span class="ns-flag-text"><?php echo esc_html($lang_flag); ?></span>
                                        </span>
                                        
                                        <?php if ($is_new) : ?>
                                            <span class="ns-new">NEW</span>
                                        <?php endif; ?>

                                        <?php if ($is_premium) : ?>
                                            <span class="ns-read" style="color:var(--ns-yellow); border-color:rgba(244,201,93,0.3); background:rgba(244,201,93,0.1);">
                                                <?php if ($is_locked) : ?>
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:-2px; margin-right:4px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> VIP
                                                <?php else : ?>
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:-2px; margin-right:4px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 9.9-1"></path></svg> VIP
                                                <?php endif; ?>
                                            </span>
                                        <?php else : ?>
                                            <span class="ns-read">Lire</span>
                                        <?php endif; ?>
                                    </div>
                                </a>

                                <?php if ($is_staff) : ?>
                                    <div class="ns-ch-edit">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <input type="checkbox" class="ns-row-check" value="<?php echo esc_attr($chapter_id); ?>">
                                            <span class="ns-edit-label">Sélectionner</span>
                                        </div>
                                        
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <span class="ns-edit-label">Langue :</span>
                                            <select class="ns-edit-select ns-row-select" data-cid="<?php echo esc_attr($chapter_id); ?>" data-mid="<?php echo esc_attr($post_id); ?>" data-nonce="<?php echo esc_attr($nonce_lang); ?>">
                                                <?php foreach ($lang_choices as $opt) : ?>
                                                    <option value="<?php echo esc_attr($opt); ?>" <?php selected($lang_code, $opt); ?>><?php echo esc_html($opt); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="button" class="ns-btn ns-btn--gold ns-save-btn" style="min-height: 32px; padding: 0 15px;">Mettre à jour</button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php $chapter_index++; endforeach; ?>
                    </div>

                    <?php if ($total_chapters > 22) : ?>
                        <div class="ns-more" id="nsMoreWrap" style="text-align: center; margin-top: 20px;">
                            <button class="ns-tool-btn" id="nsMoreBtn" type="button">Voir tous les chapitres (<?php echo esc_html($total_chapters - 22); ?> restants)</button>
                        </div>
                    <?php endif; ?>

                <?php else : ?>
                    <div class="ns-empty">Aucun chapitre disponible pour le moment.</div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
    const flagMap = { VF: '🇫🇷', VA: '🇬🇧', ES: '🇪🇸', RAW: '🇯🇵', VO: '🇯🇵' };
    const cycleMap = { VF: 'VA', VA: 'ES', ES: 'RAW', RAW: 'VO', VO: 'VF' };
    const mangaId = '<?php echo esc_js($post_id); ?>';

    // === 1. TRACKER DE LECTURE LOCAL & GLOBAL ===
    const readChapters = JSON.parse(localStorage.getItem('nerv_read_' + mangaId) || '[]');
    document.querySelectorAll('.ns-ch-row').forEach(row => {
        const wrap = row.closest('.ns-ch-row-wrap');
        if(!wrap) return;
        const cid = wrap.dataset.cid;
        const cslug = wrap.dataset.slug; 

        if (readChapters.includes(cid)) {
            row.classList.add('is-read');
            const readBtn = row.querySelector('.ns-read');
            if(readBtn) readBtn.textContent = 'Relire';
        }

        row.addEventListener('click', () => {
            if (!readChapters.includes(cid)) {
                readChapters.push(cid);
                localStorage.setItem('nerv_read_' + mangaId, JSON.stringify(readChapters));
            }
            let globalHist = JSON.parse(localStorage.getItem('nerv_global_history') || '[]');
            globalHist = globalHist.filter(h => h.id !== mangaId);
            globalHist.unshift({ id: mangaId, slug: cslug });
            if (globalHist.length > 15) globalHist.pop();
            localStorage.setItem('nerv_global_history', JSON.stringify(globalHist));
        });
    });

    // === RECHERCHE RAPIDE ===
    const searchInput = document.getElementById('nsChapSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const val = e.target.value.toLowerCase();
            document.querySelectorAll('.ns-ch-row-wrap').forEach(wrap => {
                if(val.length > 0) wrap.classList.remove('is-hidden-init');
                const title = wrap.dataset.title || '';
                if(title.includes(val)) { wrap.classList.remove('search-hidden'); } 
                else { wrap.classList.add('search-hidden'); }
            });
        });
    }

    // === FAVORIS ===
    const favBtn = document.querySelector('.js-nerv-fav');
    if (favBtn) {
        favBtn.addEventListener('click', function () {
            const button = this;
            const formData = new FormData();
            formData.append('action', 'nerv_toggle_favorite');
            formData.append('manga_id', button.getAttribute('data-id'));
            formData.append('nonce', button.getAttribute('data-nonce'));
            button.disabled = true;

            fetch(ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
            .then(res => res.json())
            .then(data => {
                if (!data || !data.success) return;
                button.classList.toggle('is-active', data.data.is_fav);
                button.textContent = data.data.is_fav ? 'Retirer des favoris' : 'Ajouter aux favoris';
            })
            .finally(() => { button.disabled = false; });
        });
    }

    const list = document.getElementById('nsChapterList');
    const sortBtn = document.getElementById('nsSortBtn');
    const moreBtn = document.getElementById('nsMoreBtn');
    const moreWrap = document.getElementById('nsMoreWrap');

    function getRows() { return Array.from(document.querySelectorAll('#nsChapterList .ns-ch-row-wrap')); }
    function getVisibleRows() { return getRows().filter(row => row.style.display !== 'none' && !row.classList.contains('search-hidden')); }
    function getCheckedRows() { return Array.from(document.querySelectorAll('#nsChapterList .ns-row-check:checked')); }

    if (moreBtn) {
        moreBtn.addEventListener('click', function () {
            getRows().forEach(row => row.classList.remove('is-hidden-init'));
            if (moreWrap) moreWrap.remove();
        });
    }

    if (sortBtn && list) {
        sortBtn.addEventListener('click', function () {
            const asc = sortBtn.textContent.includes('Ancien');
            sortBtn.textContent = asc ? '↓ Récent' : '↑ Ancien';
            getRows().reverse().forEach(row => list.appendChild(row));
        });
    }

    function updateSelectionCount() {
        const editStat = document.getElementById('nsEditStat');
        if (!editStat) return;
        editStat.textContent = `${getCheckedRows().length} sélectionné(s)`;
    }

    function updateChapterUI(chapterId, newLang) {
        document.querySelectorAll('[data-cid="' + chapterId + '"]').forEach(el => {
            if (el.classList.contains('badge-lang') || el.classList.contains('ns-preview-flag') || el.classList.contains('ns-lang-badge')) {
                el.dataset.curr = newLang;
                el.querySelector('.ns-flag-text') ? el.querySelector('.ns-flag-text').textContent = flagMap[newLang] : el.textContent = flagMap[newLang];
            }
            if (el.classList.contains('ns-row-select')) el.value = newLang;
            if (el.classList.contains('ns-ch-lang-target')) el.dataset.lang = newLang;
        });
    }

    async function changeChapterLang(chapterId, mangaId, nonce, newLang) {
        const body = new URLSearchParams();
        body.append('action', 'change_chapter_lang');
        body.append('chapter_id', chapterId);
        body.append('manga_id', mangaId);
        body.append('nonce', nonce);
        body.append('new_lang', newLang);

        const res = await fetch(ajaxUrl, { method: 'POST', body });
        const data = await res.json();
        return !!(data && data.success);
    }

    document.querySelectorAll('.ns-save-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            const row = btn.closest('.ns-ch-edit');
            const select = row ? row.querySelector('.ns-row-select') : null;
            if (!select) return;
            const oldText = btn.textContent;
            btn.disabled = true; btn.textContent = '...';

            try {
                const ok = await changeChapterLang(select.dataset.cid, select.dataset.mid, select.dataset.nonce, select.value);
                if (ok) { updateChapterUI(select.dataset.cid, select.value); btn.textContent = 'OK'; } 
                else { btn.textContent = 'Erreur'; }
            } catch (e) { btn.textContent = 'Erreur'; }

            setTimeout(() => { btn.textContent = oldText; btn.disabled = false; }, 1000);
        });
    });

    document.querySelectorAll('#nsChapterList .ns-row-check').forEach(chk => {
        chk.addEventListener('change', updateSelectionCount);
    });

    const toggleEditBtn = document.getElementById('nsToggleEdit');
    const editBar = document.getElementById('nsEditBar');
    const selectVisibleBtn = document.getElementById('nsSelectVisible');
    const clearSelectionBtn = document.getElementById('nsClearSelection');
    const applySelectedBtn = document.getElementById('nsApplySelected');
    const applyAllBtn = document.getElementById('nsApplyAll');
    const bulkLangSelect = document.getElementById('nsBulkLang');

    if (toggleEditBtn) {
        toggleEditBtn.addEventListener('click', function () {
            const rows = document.querySelectorAll('#nsChapterList .ns-ch-row-wrap');
            const active = rows[0] && rows[0].classList.contains('editing');
            rows.forEach(row => row.classList.toggle('editing', !active));
            if (editBar) editBar.classList.toggle('show', !active);
            toggleEditBtn.textContent = active ? 'Mode édition' : 'Terminer';
            updateSelectionCount();
        });
    }

    if (selectVisibleBtn) {
        selectVisibleBtn.addEventListener('click', function () {
            getVisibleRows().forEach(row => {
                const checkbox = row.querySelector('.ns-row-check');
                if (checkbox) checkbox.checked = true;
            });
            updateSelectionCount();
        });
    }

    if (clearSelectionBtn) {
        clearSelectionBtn.addEventListener('click', function () {
            document.querySelectorAll('#nsChapterList .ns-row-check').forEach(chk => chk.checked = false);
            updateSelectionCount();
        });
    }

    if (applySelectedBtn && editBar && bulkLangSelect) {
        applySelectedBtn.addEventListener('click', function () {
            const ids = getCheckedRows().map(chk => chk.value).filter(Boolean);
            if (!ids.length) return;
            const oldText = this.textContent;
            this.textContent = '...'; this.disabled = true;
            ids.forEach(id => {
                changeChapterLang(id, editBar.dataset.mid, editBar.dataset.nonce, bulkLangSelect.value)
                .then(ok => { if(ok) updateChapterUI(id, bulkLangSelect.value); });
            });
            setTimeout(() => { this.textContent = oldText; this.disabled = false; }, 1000);
        });
    }

    if (applyAllBtn && editBar && bulkLangSelect) {
        applyAllBtn.addEventListener('click', function () {
            const ids = getRows().map(row => row.dataset.cid).filter(Boolean);
            if (!ids.length) return;
            const oldText = this.textContent;
            this.textContent = '...'; this.disabled = true;
            ids.forEach(id => {
                changeChapterLang(id, editBar.dataset.mid, editBar.dataset.nonce, bulkLangSelect.value)
                .then(ok => { if(ok) updateChapterUI(id, bulkLangSelect.value); });
            });
            setTimeout(() => { this.textContent = oldText; this.disabled = false; }, 1000);
        });
    }

    document.querySelectorAll('.badge-lang.ns-admin-badge').forEach(badge => {
        badge.addEventListener('click', async function (e) {
            e.preventDefault(); e.stopPropagation();
            const current = badge.dataset.curr || 'VF';
            const newLang = cycleMap[current] || 'VF';
            badge.style.opacity = '0.45';
            try {
                const ok = await changeChapterLang(badge.dataset.cid, badge.dataset.mid, badge.dataset.nonce, newLang);
                if (ok) updateChapterUI(badge.dataset.cid, newLang);
            } catch (e2) {} finally { badge.style.opacity = '1'; }
        });
    });

    updateSelectionCount();
});
</script>

<?php get_footer(); ?>