<?php
// ==========================================
// 🎯 NERV UI FRONT-END AJAX (SANS CACHE / HACK DIRECT)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nerv_front_action'])) {
    ob_clean();
    header('Content-Type: application/json');
    
    if (!is_user_logged_in()) {
        echo wp_json_encode(['success' => false, 'data' => 'Vous devez être connecté.']);
        exit;
    }
    
    $current_user = wp_get_current_user();
    $uid = $current_user->ID;
    $roles = (array) $current_user->roles;
    $action = $_POST['nerv_front_action'];

    // 1. HACK: USURPATION POUR SAUVEGARDER LA COVER DIRECTEMENT DEPUIS LA PAGE
    if ($action === 'set_chapter_cover') {
        // Sécurité Staff
        if (!current_user_can('manage_options') && !in_array('magi', $roles) && !in_array('marduk', $roles)) {
            echo wp_json_encode(['success' => false, 'data' => 'Interdit.']); exit;
        }

        $m_id = isset($_POST['manga_id']) ? (int)$_POST['manga_id'] : 0;
        $c_id = isset($_POST['chapter_id']) ? sanitize_text_field($_POST['chapter_id']) : 0;
        $url  = isset($_POST['image_url']) ? esc_url_raw($_POST['image_url']) : '';
        $pos  = isset($_POST['position']) ? sanitize_text_field($_POST['position']) : 'center 50%';

        if ($m_id && $c_id && $url) {
            // Sauvegarde directement les meta de l'aperçu pour manga-single.php
            update_post_meta($m_id, '_chapter_image_' . $c_id, $url);
            update_post_meta($m_id, '_chapter_image_pos_' . $c_id, $pos);
            echo wp_json_encode(['success' => true]); exit;
        }
        echo wp_json_encode(['success' => false, 'data' => 'Données manquantes']); exit;
    }

    // 2. GESTION DES COMMENTAIRES
    if ($action === 'add_comment') {
        $content = isset($_POST['content']) ? trim(sanitize_textarea_field(wp_unslash($_POST['content']))) : '';
        $pid = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
        $chap = !empty($_POST['chapter']) ? sanitize_text_field($_POST['chapter']) : 'chapitre-en-cours';

        if ($content === '') { echo wp_json_encode(['success' => false, 'data' => 'Le message est vide.']); exit; }

        if ($pid > 0) {
            $comment_id = wp_insert_comment([
                'comment_post_ID'      => $pid,
                'comment_content'      => $content,
                'user_id'              => $uid,
                'comment_author'       => $current_user->display_name ?: $current_user->user_login,
                'comment_author_email' => $current_user->user_email,
                'comment_approved'     => 1,
            ]);
            
            if ($comment_id && !is_wp_error($comment_id)) {
                update_comment_meta($comment_id, 'manga_chapter', $chap);
                echo wp_json_encode(['success' => true]); exit;
            } else {
                $err = is_wp_error($comment_id) ? $comment_id->get_error_message() : 'Erreur base de données.';
                echo wp_json_encode(['success' => false, 'data' => $err]); exit;
            }
        }
        echo wp_json_encode(['success' => false, 'data' => 'ID introuvable.']); exit;
    }

    if ($action === 'delete_comment') {
        $cid = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;
        $comment = get_comment($cid);
        if ($comment && ($uid === (int)$comment->user_id || current_user_can('administrator') || current_user_can('magi'))) {
            wp_delete_comment($cid, true);
            echo wp_json_encode(['success' => true]); exit;
        }
        echo wp_json_encode(['success' => false, 'data' => 'Interdit.']); exit;
    }

    if ($action === 'edit_comment') {
        $cid = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;
        $content = isset($_POST['content']) ? sanitize_textarea_field(wp_unslash($_POST['content'])) : '';
        $comment = get_comment($cid);
        if ($comment && $content !== '' && ($uid === (int)$comment->user_id || current_user_can('administrator') || current_user_can('magi'))) {
            wp_update_comment(['comment_ID' => $cid, 'comment_content' => $content]);
            echo wp_json_encode(['success' => true, 'content' => wpautop(esc_html($content))]); exit;
        }
        echo wp_json_encode(['success' => false, 'data' => 'Interdit.']); exit;
    }
}
?>
<?php
/**
 * NERV READING TEMPLATE – V43 "Usurpation Cover Directe"
 */

global $wp_manga, $wp_manga_functions, $wp_manga_chapter;

$reading_chapter = false;
if (function_exists('madara_permalink_reading_chapter')) {
    $reading_chapter = madara_permalink_reading_chapter();
}

if (!$reading_chapter) {
    get_header();
    echo '<div style="padding:100px;color:red;background:#000;text-align:center;">ERREUR CHARGEMENT CHAPITRE</div>';
    get_footer();
    exit;
}

$manga_id = $reading_chapter['manga_id'] ?? get_the_ID();
$cur_chap  = !empty($reading_chapter['chapter_slug']) ? (string)$reading_chapter['chapter_slug'] : 'chapitre-en-cours';

$manga_title = get_the_title($manga_id);
$manga_permalink = get_permalink($manga_id);
$manga_cover_url = get_the_post_thumbnail_url($manga_id, 'large') ?: 'https://placehold.co/800x1100/111111/F4C95D?text=NERV';

$can_download = true;

$display_chap_name = str_replace(['-', '_'], ' ', $cur_chap);
$display_chap_name = ucwords(str_replace('chapitre chapitre', 'Chapitre', strtolower($display_chap_name)));

$all_chapters = [];
if (class_exists('WP_Manga') && isset($wp_manga_functions)) {
    $all_chapters = $wp_manga_functions->get_latest_chapters($manga_id, null, 2000, 0);
}
if (!empty($all_chapters) && is_array($all_chapters)) {
    usort($all_chapters, function ($a, $b) {
        return strnatcasecmp($b['chapter_slug'] ?? '', $a['chapter_slug'] ?? '');
    });
}

// Vérification accès utilisateur
$user_has_access = function_exists('nerv_user_is_premium') ? nerv_user_is_premium() : false;
$curr_user  = wp_get_current_user();
$curr_roles = is_user_logged_in() ? (array) $curr_user->roles : [];
$is_staff = current_user_can('manage_options') || in_array('nervmodo', $curr_roles, true) || in_array('magi', $curr_roles, true) || in_array('marduk', $curr_roles, true) || in_array('nerv_recruteur', $curr_roles, true);
if ($is_staff) { $user_has_access = true; }

$nerv_prev_url = ''; $nerv_next_url = '';
$nerv_prev_premium = false; $nerv_next_premium = false;
$nerv_prev_locked = false; $nerv_next_locked = false;
$cur_cid = ''; 

if (!empty($all_chapters)) {
    $chapter_links = [];
    foreach ($all_chapters as $chap) {
        $slug = !empty($chap['chapter_slug']) ? $chap['chapter_slug'] : 'chapitre-en-cours';
        $chapter_id = $chap['chapter_id'] ?? 0;
        
        $is_premium = false;
        $is_locked = false;
        $custom_unlock = get_post_meta($manga_id, '_chapter_unlock_' . $chapter_id, true);
        if (!empty($custom_unlock)) {
            $ts = strtotime($custom_unlock);
            if ($ts && current_time('timestamp') < $ts) {
                $is_premium = true;
                if (!$user_has_access) {
                    $is_locked = true;
                }
            }
        }

        $chapter_links[] = [
            'slug' => $slug, 
            'url' => $wp_manga_functions->build_chapter_url($manga_id, $slug),
            'is_premium' => $is_premium,
            'is_locked' => $is_locked
        ];
        
        if ($slug === $cur_chap) {
            if ($chapter_id) { $cur_cid = $chapter_id; }
        }
    }
    
    $current_index = null;
    foreach ($chapter_links as $i => $item) {
        if ($item['slug'] === $cur_chap) { $current_index = $i; break; }
    }
    
    if ($current_index !== null) {
        $nerv_next_url     = $chapter_links[$current_index - 1]['url'] ?? '';
        $nerv_next_premium = $chapter_links[$current_index - 1]['is_premium'] ?? false;
        $nerv_next_locked  = $chapter_links[$current_index - 1]['is_locked'] ?? false;
        
        $nerv_prev_url     = $chapter_links[$current_index + 1]['url'] ?? '';
        $nerv_prev_premium = $chapter_links[$current_index + 1]['is_premium'] ?? false;
        $nerv_prev_locked  = $chapter_links[$current_index + 1]['is_locked'] ?? false;
    }
}

// ==========================================
// MOTEUR EARLY ACCESS (LE CHAPITRE ACTUEL)
// ==========================================
$is_premium_current = false;
$is_locked_current = false;
$unlock_timestamp = 0;

$custom_unlock_current = get_post_meta($manga_id, '_chapter_unlock_' . $cur_cid, true);
if (!empty($custom_unlock_current)) {
    $unlock_timestamp = strtotime($custom_unlock_current);
    if ($unlock_timestamp && current_time('timestamp') < $unlock_timestamp) {
        $is_premium_current = true;
        if (!$user_has_access) {
            $is_locked_current = true;
        }
    }
}

global $wpdb;
$post_id_safe = (int)$manga_id;
$query = "SELECT c.* FROM {$wpdb->comments} c 
          INNER JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id 
          WHERE c.comment_post_ID = %d 
          AND cm.meta_key = 'manga_chapter' AND cm.meta_value = %s
          AND c.comment_approved IN ('0', '1') 
          ORDER BY c.comment_date_gmt ASC";
$comments_list = $wpdb->get_results($wpdb->prepare($query, $post_id_safe, $cur_chap));

get_header();

// SVGs des Cadenas
$svg_lock_closed = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin: 0 4px; margin-bottom:-2px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>';
$svg_lock_open = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin: 0 4px; margin-bottom:-2px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 9.9-1"></path></svg>';
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,700;0,800;0,900;1,700;1,800;1,900&family=Inter:wght@400;500;600;700;800;900&display=swap');

:root {
    --ns-bg: #000000;
    --ns-panel: #050505;
    --ns-stroke: rgba(255,255,255,0.06);
    --ns-yellow: #F4C95D;
    --ns-txt: #ffffff;
    --ns-muted: #666666;
    --ns-gray: #888888;
}

html, body { background: var(--ns-bg) !important; margin: 0 !important; padding: 0 !important; color: var(--ns-txt) !important; font-family: 'Inter', sans-serif !important; overflow-x: hidden; }
#page, .site, #content, .site-content, .body-wrap, main { background: transparent !important; border: none !important; box-shadow: none !important; margin: 0 !important; padding: 0 !important; }

/* MASQUER LES ÉLÉMENTS MADARA PARASITES */
.site-header, footer, .site-footer, .c-breadcrumb, #chapter-heading, .entry-header, .posted-on, .page-title, #wpadminbar,
.wp-manga-nav, .c-blog-post, .alert-warning, .chapter-warning, .manga-reading-nav { display: none !important; }

.ns-container { width: 100%; max-width: 1040px; margin: 0 auto; padding: 0 16px; box-sizing: border-box; }

.ns-reading-hud {
    background: #050505;
    border: 1px solid var(--ns-stroke);
    border-radius: 16px;
    margin: 30px auto 16px;
    padding: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 24px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.5);
}

.ns-rh-left { display: flex; align-items: center; gap: 20px; flex: 1; min-width: 0; }
.ns-rh-cover { width: 90px; height: 130px; object-fit: cover; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 10px 20px rgba(0,0,0,0.5); flex-shrink: 0; }
.ns-rh-info { display: flex; flex-direction: column; min-width: 0; }
.ns-rh-manga { font-size: 14px; font-weight: 800; color: var(--ns-gray); text-transform: uppercase; text-decoration: none; margin-bottom: 4px; letter-spacing: 0.05em; transition: 0.2s; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ns-rh-manga:hover { color: #fff; }
.ns-rh-chap { font-family: 'Barlow Condensed', sans-serif; font-size: 38px; font-weight: 900; font-style: italic; color: #fff; line-height: 1; margin: 0; text-transform: uppercase; }

.ns-rh-actions { display: flex; flex-direction: column; gap: 8px; min-width: 250px; flex-shrink: 0; }
.ns-rh-row { display: flex; gap: 8px; width: 100%; }
.ns-btn { flex: 1; padding: 10px 8px; border-radius: 8px; font-size: 11px; font-weight: 900; text-transform: uppercase; text-align: center; cursor: pointer; transition: 0.2s; border: none; color: #fff; background: #111; border: 1px solid var(--ns-stroke); text-decoration: none; }
.ns-btn:hover { background: rgba(255,255,255,0.1); }
.ns-btn.gold { background: rgba(244,201,93,0.1); color: var(--ns-yellow); border-color: rgba(244,201,93,0.3); }
.ns-btn.gold:hover, .ns-btn.gold.active { background: var(--ns-yellow); color: #000; box-shadow: 0 5px 15px rgba(244,201,93,0.2); }
.ns-btn.danger { color: #ef4444; border-color: rgba(239, 68, 68, 0.2); background: rgba(239, 68, 68, 0.05); }
.ns-btn.danger:hover { background: rgba(239, 68, 68, 0.15); }

/* BARRE DE NAV HAUT */
.ns-top-nav { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 30px; }
.ns-top-btn { display: flex; align-items: center; justify-content: center; padding: 14px 20px; background: #050505; border: 1px solid var(--ns-stroke); border-radius: 12px; color: var(--ns-gray); font-size: 12px; font-weight: 800; text-transform: uppercase; text-decoration: none; transition: 0.2s; letter-spacing: 0.05em; }
.ns-top-btn:hover:not(.disabled) { background: #111; color: #fff; border-color: rgba(255,255,255,0.15); transform: translateY(-2px); }
.ns-top-btn.disabled { opacity: 0.3; pointer-events: none; }

/* STYLE PREMIUM POUR LES BOUTONS NAVIGATION */
.ns-top-btn.is-premium-btn { border-color: rgba(244, 201, 93, 0.4); color: var(--ns-yellow); background: rgba(244, 201, 93, 0.05); }
.ns-top-btn.is-premium-btn:hover:not(.disabled) { background: rgba(244, 201, 93, 0.1); border-color: var(--ns-yellow); box-shadow: 0 5px 15px rgba(244, 201, 93, 0.15); }

/* ECRAN DE VERROUILLAGE PREMIUM */
.ns-premium-lock-screen { background: #050505; border: 1px solid var(--ns-yellow); border-radius: 16px; padding: 60px 20px; text-align: center; max-width: 500px; margin: 40px auto 80px; box-shadow: 0 20px 50px rgba(244, 201, 93, 0.08); }
.ns-pls-icon { margin-bottom: 16px; line-height: 1; color: var(--ns-yellow); filter: drop-shadow(0 0 10px rgba(244,201,93,0.4)); display: flex; justify-content: center; }
.ns-pls-icon svg { width: 48px; height: 48px; }
.ns-pls-title { font-family: 'Barlow Condensed', sans-serif; font-size: 32px; font-weight: 900; font-style: italic; color: #fff; text-transform: uppercase; margin: 0 0 8px 0; }
.ns-pls-desc { color: var(--ns-gray); font-size: 14px; margin-bottom: 24px; line-height: 1.5; }
.ns-pls-timer { background: rgba(244, 201, 93, 0.05); color: var(--ns-yellow); display: inline-block; padding: 12px 24px; border-radius: 8px; font-weight: 800; font-size: 13px; margin-bottom: 30px; border: 1px dashed rgba(244, 201, 93, 0.3); letter-spacing: 0.05em; }

/* LECTEUR CENTRAL (800px) */
.reading-content-wrap { padding: 0 0 40px; background: #000; width: 100%; overflow-x: hidden; }
.reading-content { max-width: 800px; margin: 0 auto; padding: 0; box-sizing: border-box; display: block; text-align: center; line-height: 0; font-size: 0; position: relative; }
.reading-content img { width: 100%; max-width: 100%; height: auto; display: block !important; margin: 0 auto !important; padding: 0; border-radius: 0px; position: relative; z-index: 1; transition: transform 0.3s ease; }
.reading-content > * { margin-top: 0 !important; margin-bottom: 0 !important; }
.reading-content br { display: none !important; }

@media (max-width: 820px) {
    .ns-reading-hud { flex-direction: column; text-align: center; gap: 20px; }
    .ns-rh-left { flex-direction: column; gap: 16px; width: 100%; }
    .ns-rh-manga { white-space: normal; }
    .ns-rh-actions { width: 100%; }
    .reading-content { max-width: 100% !important; width: 100% !important; padding: 0; margin: 0 auto; }
    #nerv-reading-root img { width: 100% !important; max-width: 100% !important; margin: 0 auto !important; padding: 0 !important; object-fit: contain; touch-action: pan-x pan-y pinch-zoom !important; }
}

.reading-mode-page .reading-content { max-width: 100%; width: 100%; height: 95vh; display: flex; justify-content: center; align-items: center; position: relative; overflow: hidden; background: #000; }
.reading-mode-page .reading-content img { display: none !important; max-width: 100%; max-height: 95vh; width: auto; height: auto; object-fit: contain; margin: 0 auto !important; }
.reading-mode-page .reading-content img.active-page { display: block !important; }
.page-click-zone { position: absolute; top:0; bottom:0; width:50%; z-index:50; cursor: pointer; display:none; }
.page-click-left { left: 0; }
.page-click-right { right: 0; }
.reading-mode-page .page-click-zone { display: block; }

/* === STYLES DU MODE QC === */
body.qc-mode-active .page-click-zone { display: none !important; } 
body.qc-mode-active #nerv-reading-root img { border: 2px dashed var(--ns-yellow) !important; cursor: crosshair !important; opacity: 0.7; transition: 0.2s; }
body.qc-mode-active #nerv-reading-root img:hover { opacity: 1; border-style: solid !important; box-shadow: 0 0 30px rgba(244, 201, 93, 0.8); z-index: 10; transform: scale(1.02); }

.ns-end { text-align: center; padding: 80px 20px; }
.ns-end h2 { font-family: 'Barlow Condensed', sans-serif; font-size: 42px; font-weight: 900; font-style: italic; text-transform: uppercase; color: #fff; margin: 0 0 40px; letter-spacing: -0.02em; }
.ns-end-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-width: 800px; margin: 0 auto; }
.ns-big-btn { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 30px 20px; border-radius: 12px; text-decoration: none; transition: 0.3s; border: 1px solid transparent; }
.ns-big-btn span { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; opacity: 0.7; }
.ns-big-btn strong { font-family: 'Barlow Condensed', sans-serif; font-size: 32px; font-weight: 900; font-style: italic; text-transform: uppercase; letter-spacing: -0.02em; display: flex; align-items: center; gap: 8px; }

/* BOUTONS GROS PREMIUM */
.ns-big-btn.prev { background: #050505; border-color: var(--ns-stroke); color: #fff; }
.ns-big-btn.prev:hover:not(.disabled) { background: #111; border-color: rgba(255,255,255,0.15); transform: translateY(-2px); }
.ns-big-btn.prev.is-premium-btn { border-color: rgba(244, 201, 93, 0.3); color: var(--ns-yellow); }

.ns-big-btn.next { background: var(--ns-yellow); color: #000; box-shadow: 0 0 40px rgba(244, 201, 93, 0.2); }
.ns-big-btn.next:hover:not(.disabled) { transform: translateY(-4px); box-shadow: 0 0 60px rgba(244, 201, 93, 0.4); filter: brightness(1.05); }
.ns-big-btn.next.is-premium-btn { background: linear-gradient(135deg, #F4C95D, #D1A848); border: 2px solid #fff; box-shadow: 0 10px 40px rgba(244, 201, 93, 0.6); }

.ns-big-btn.disabled { opacity: 0.3; pointer-events: none; }
.ns-end-links { margin-top: 30px; display: flex; justify-content: center; gap: 20px; }
.ns-end-links a { font-size: 12px; font-weight: 800; color: var(--ns-gray); text-transform: uppercase; text-decoration: none; transition: 0.2s; }
.ns-end-links a:hover { color: #fff; }
.ns-end-links span { color: var(--ns-stroke); }

.ns-comments { max-width: 800px; margin: 0 auto 100px; padding: 0 20px; }
.ns-c-title { font-family: 'Barlow Condensed', sans-serif; font-size: 28px; font-weight: 900; font-style: italic; text-transform: uppercase; color: var(--ns-muted); margin: 0 0 20px; padding-bottom: 15px; border-bottom: 1px solid var(--ns-stroke); }
.ns-c-empty { border: 1px dashed rgba(255,255,255,0.1); border-radius: 8px; padding: 40px 20px; text-align: center; color: var(--ns-gray); font-size: 13px; margin-bottom: 30px; }

.comment-card { position: relative; background: #050505; border: 1px solid var(--ns-stroke); border-radius: 12px; overflow: hidden; margin-bottom: 16px; padding: 20px; display: flex; gap: 16px; }
.c-banner { position: absolute; inset: 0; width: 100%; height: 100%; background-size: cover; background-position: center; opacity: 0.15; z-index: 0; mask-image: linear-gradient(to right, black 20%, transparent 100%); -webkit-mask-image: linear-gradient(to right, black 20%, transparent 100%); }
.c-avatar { width: 48px; height: 48px; border-radius: 8px; border: 1px solid var(--ns-stroke); object-fit: cover; position: relative; z-index: 1; }
.c-content { flex: 1; position: relative; z-index: 1;}
.c-author-row { display: flex; align-items: center; gap: 10px; margin-bottom: 4px; }
.c-author { font-weight: 800; color: #fff; font-size: 14px; }
.c-rank { font-size: 9px; font-weight: 900; color: var(--ns-yellow); background: rgba(244,201,93,0.1); padding: 3px 8px; border-radius: 4px; text-transform: uppercase; }
.c-date { font-size: 11px; color: var(--ns-gray); margin-bottom: 10px; }
.c-text { color: rgba(255,255,255,0.85); font-size: 14px; line-height: 1.6; }
.c-actions { display: flex; gap: 10px; margin-top: 12px; }
.c-btn { background: #111; border: 1px solid var(--ns-stroke); color: #fff; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 10px; font-weight: 800; transition: .2s; text-transform: uppercase; }
.c-btn:hover { background: rgba(255,255,255,0.1); }
.c-btn.delete:hover { background: rgba(244,201,93,0.1); color: var(--ns-yellow); border-color: rgba(244,201,93,0.3); }

.ns-c-form-box { background: #050505; border: 1px solid var(--ns-stroke); border-radius: 12px; padding: 24px; }
.ns-c-form-box h3 { font-size: 13px; font-weight: 800; text-transform: uppercase; color: var(--ns-gray); margin: 0 0 16px; }
.ns-c-form-box textarea { width: 100%; background: #000; border: 1px solid var(--ns-stroke); border-radius: 8px; padding: 16px; color: #fff; font-family: inherit; font-size: 13px; min-height: 100px; outline: none; margin-bottom: 16px; resize:vertical; }
.ns-c-form-box textarea:focus { border-color: rgba(244,201,93,0.5); }
.ns-c-btn-submit { background: var(--ns-yellow); color: #000; font-weight: 900; font-size: 12px; text-transform: uppercase; border: none; padding: 14px 28px; border-radius: 6px; cursor: pointer; transition: 0.2s; }
.ns-c-btn-submit:hover { filter: brightness(1.1); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(244, 201, 93, 0.3); }
.ns-c-btn-submit:disabled { opacity: 0.5; pointer-events: none; }

.ns-progress-wrap { position: fixed; top: 0; left: 0; width: 100%; height: 3px; background: transparent; z-index: 999999; }
.ns-progress-bar { height: 100%; width: 0%; background: var(--ns-yellow); box-shadow: 0 0 8px rgba(244,201,93,0.5); transition: width 0.1s linear; }

.ns-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); z-index: 99999; display: none; align-items: center; justify-content: center; }
.ns-modal-overlay.active { display: flex; }
.ns-modal { background: #050505; border: 1px solid var(--ns-stroke); border-radius: 16px; width: 90%; max-width: 420px; max-height: 80vh; display: flex; flex-direction: column; box-shadow: 0 20px 60px rgba(0,0,0,0.9); }
.ns-modal-head { padding: 20px; border-bottom: 1px solid var(--ns-stroke); display: flex; justify-content: space-between; align-items: center; }
.ns-modal-head h3 { margin:0; font-size: 16px; color:#fff; font-weight:900; text-transform:uppercase;}
.ns-modal-close { background:transparent; border:none; color:var(--ns-gray); cursor:pointer; font-size:24px; line-height:1;}
.ns-modal-close:hover { color:#fff; }
.ns-modal-body { padding: 15px; overflow-y: auto; flex:1; }
.ns-ch-item { display: flex; align-items: center; padding: 12px 16px; border-radius: 8px; color: var(--ns-gray); text-decoration: none; font-weight: 600; font-size: 13px; transition: 0.2s; margin-bottom: 4px; border: 1px solid transparent;}
.ns-ch-item:hover { background: rgba(255,255,255,0.05); color: #fff; }
.ns-ch-item.current { background: rgba(244,201,93,0.1); border-color: rgba(244,201,93,0.3); color: var(--ns-yellow); font-weight: 800;}

/* LIGNE PREMIUM DANS LA MODALE CHAPITRES */
.ns-ch-item.is-premium-item { border-left: 2px solid var(--ns-yellow); color: var(--ns-yellow); background: linear-gradient(90deg, rgba(244,201,93,0.05), transparent); }
.ns-ch-item.is-premium-item:hover { background: linear-gradient(90deg, rgba(244,201,93,0.15), rgba(255,255,255,0.02)); }

input[type="range"].nerv-slider { -webkit-appearance: none; width: 100%; height: 6px; background: #222; border-radius: 4px; outline: none; margin: 15px 0; }
input[type="range"].nerv-slider::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 18px; height: 18px; border-radius: 50%; background: var(--ns-yellow); cursor: pointer; box-shadow: 0 0 10px rgba(244, 201, 93, 0.6); transition: 0.2s; }
input[type="range"].nerv-slider::-webkit-slider-thumb:hover { transform: scale(1.2); }

#nerv-control-bar { 
    position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); 
    display: flex; align-items: center; padding: 12px 24px; gap: 16px; 
    background: #1e1e1e;
    border-radius: 999px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); 
    z-index: 1000; transition: transform 0.3s ease, opacity 0.3s ease; 
}
#nerv-control-bar.is-hidden { transform: translateX(-50%) translateY(120px); opacity: 0; pointer-events: none; }
.nc-ico { 
    display: flex; align-items: center; justify-content: center; 
    background: transparent; color: #888; cursor: pointer; 
    text-decoration: none; transition: 0.2s; border: none; outline: none; padding: 4px;
}
.nc-ico:hover { color: #fff; }
.nc-ico.is-premium-ico { color: var(--ns-yellow); }
.nc-ico.is-premium-ico:hover { text-shadow: 0 0 15px rgba(244,201,93,0.6); transform: scale(1.1); }
.nc-ico svg { width: 20px; height: 20px; }
.nc-sep { width: 1px; height: 20px; background: rgba(255,255,255,0.1); }
.nc-ico.disabled { opacity: 0.3; pointer-events: none; }

.ncm-preview-box { width: 100%; aspect-ratio: 16/9; max-height: 40vh; border-radius: 8px; overflow: hidden; border: 2px solid var(--ns-yellow); margin: 15px 0; background:#000; }
.ncm-preview-box img { width: 100%; height: 100%; object-fit: cover; }
.img-wrapper { position: relative; width: 100%; }
.nerv-admin-cover-ctrl { position: absolute; top: 15px; right: 15px; display:flex; gap:10px; opacity: 0; transition: 0.3s; z-index: 10; }
.img-wrapper:hover .nerv-admin-cover-ctrl { opacity: 1; }

@media (max-width: 820px) {
    .nerv-admin-cover-ctrl { opacity: 1 !important; top: 5px; right: 5px; } 
    .ns-end-grid { grid-template-columns: 1fr; }
}
</style>

<div class="ns-progress-wrap">
    <div class="ns-progress-bar" id="ns-progress-bar"></div>
</div>

<div class="ns-container">
    <div class="ns-reading-hud">
        <div class="ns-rh-left">
            <img src="<?php echo esc_url($manga_cover_url); ?>" alt="Cover" class="ns-rh-cover">
            <div class="ns-rh-info">
                <a href="<?php echo esc_url($manga_permalink); ?>" class="ns-rh-manga"><?php echo esc_html($manga_title); ?></a>
                <h1 class="ns-rh-chap"><?php echo esc_html($display_chap_name); ?></h1>
            </div>
        </div>
        
        <div class="ns-rh-actions">
            <div class="ns-rh-row">
                <button class="ns-btn" id="open-chapters-modal">Chapitres</button>
                <a href="<?php echo esc_url($manga_permalink); ?>" class="ns-btn">Fiche</a>
            </div>
            
            <div class="ns-rh-row">
                <?php if ($can_download): ?>
                    <button class="ns-btn" id="nerv-download-btn-top">Extraire (ZIP)</button>
                <?php endif; ?>
                <button class="ns-btn danger" id="open-report-modal">Signaler</button>
            </div>

            <?php if ($is_staff) : ?>
                <button class="ns-btn gold" id="btn-open-gallery" style="width: 100%;">🎯 Radar de Cadrage (Staff)</button>
            <?php endif; ?>

            <?php if (!$is_locked_current) : ?>
            <div class="ns-rh-row" style="background:#111; border:1px solid var(--ns-stroke); border-radius:8px; overflow:hidden; margin-top:4px;">
                <button class="ns-btn ns-mode-btn" data-mode="page" style="border:none; background:transparent;">Mode Page</button>
                <button class="ns-btn ns-mode-btn gold active" data-mode="vertical" style="border:none;">Mode Vertical</button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="ns-top-nav">
        <a href="<?php echo $nerv_prev_url ? esc_url($nerv_prev_url) : '#'; ?>" class="ns-top-btn prev <?php echo $nerv_prev_url ? '' : 'disabled'; ?> <?php echo $nerv_prev_premium ? 'is-premium-btn' : ''; ?>">
            ← Précédent
            <?php if ($nerv_prev_premium) echo $nerv_prev_locked ? $svg_lock_closed : $svg_lock_open; ?>
        </a>
        
        <a href="<?php echo $nerv_next_url ? esc_url($nerv_next_url) : '#'; ?>" class="ns-top-btn next <?php echo $nerv_next_url ? '' : 'disabled'; ?> <?php echo $nerv_next_premium ? 'is-premium-btn' : ''; ?>">
            <?php if ($nerv_next_premium) echo $nerv_next_locked ? $svg_lock_closed : $svg_lock_open; ?>
            Suivant →
        </a>
    </div>
</div>

<div class="reading-content-wrap reading-mode-vertical" id="nerv-reading-top">
  <div class="reading-content" id="nerv-reading-root">
    
    <?php if ($is_locked_current) : ?>
        <div class="ns-premium-lock-screen">
            <div class="ns-pls-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            </div>
            <h2 class="ns-pls-title">Accès Anticipé</h2>
            <p class="ns-pls-desc">Ce chapitre est une exclusivité Premium.<br>Il sera rendu public gratuitement dans quelques jours.</p>
            <div class="ns-pls-timer">Déverrouillage : <strong><?php echo date_i18n('d/m/Y à H:i', $unlock_timestamp); ?></strong></div>
            <br>
            <a href="<?php echo esc_url(home_url('/shop/')); ?>" class="ns-btn gold" style="padding: 16px 32px; font-size: 13px;">Découvrir le Pass Premium</a>
        </div>
    <?php else : ?>
        <?php if ($is_premium_current) : ?>
            <div style="background:rgba(244,201,93,0.1); border:1px dashed var(--ns-yellow); color:var(--ns-yellow); font-size:12px; font-weight:800; text-transform:uppercase; padding:10px; margin-bottom:20px; border-radius:8px; display:inline-block;">
                <?php echo $svg_lock_open; ?> Vous lisez un Chapitre Premium
            </div><br>
        <?php endif; ?>

        <div class="page-click-zone page-click-left" id="click-prev" title="Image précédente"></div>
        <div class="page-click-zone page-click-right" id="click-next" title="Image suivante"></div>
        <?php
          do_action('wp_manga_before_chapter_content', $cur_chap, $manga_id);
          do_action('wp_manga_chapter_content',        $cur_chap, $manga_id);
        ?>
    <?php endif; ?>

  </div>
</div>

<div class="ns-end" id="nerv-end-screen">
    <h2>Chapitre Terminé</h2>
    <div class="ns-end-grid">
        <a href="<?php echo $nerv_prev_url ? esc_url($nerv_prev_url) : '#'; ?>" class="ns-big-btn prev <?php echo $nerv_prev_url ? '' : 'disabled'; ?> <?php echo $nerv_prev_premium ? 'is-premium-btn' : ''; ?>">
            <span>Chapitre Précédent</span>
            <strong>← Précédent <?php if ($nerv_prev_premium) echo $nerv_prev_locked ? $svg_lock_closed : $svg_lock_open; ?></strong>
        </a>
        
        <a href="<?php echo $nerv_next_url ? esc_url($nerv_next_url) : '#'; ?>" class="ns-big-btn next <?php echo $nerv_next_url ? '' : 'disabled'; ?> <?php echo $nerv_next_premium ? 'is-premium-btn' : ''; ?>">
            <span><?php echo $nerv_next_premium ? 'Chapitre Premium' : 'Chapitre Suivant'; ?></span>
            <strong><?php if ($nerv_next_premium) echo $nerv_next_locked ? $svg_lock_closed : $svg_lock_open; ?>Continuer →</strong>
        </a>
    </div>
    <div class="ns-end-links">
        <a href="<?php echo esc_url($manga_permalink); ?>">Fiche de l'œuvre</a>
        <span>|</span>
        <a href="<?php echo esc_url(home_url('/')); ?>">Accueil</a>
    </div>
</div>

<section class="ns-comments" id="comments-section">
    <h2 class="ns-c-title">Discussions (<?php echo count($comments_list); ?>)</h2>

    <div class="comments-list">
        <?php if (!empty($comments_list)): ?>
            <?php foreach ($comments_list as $comment) : 
                $uid = (int)$comment->user_id;
                $custom_avatar = ($uid > 0) ? get_user_meta($uid, 'nerv_avatar_url', true) : '';
                $avatar_url = !empty($custom_avatar) ? $custom_avatar : get_avatar_url($comment->comment_author_email);
                $user_banner = ($uid > 0) ? get_user_meta($uid, 'nerv_banner_url', true) : 'https://i.imgur.com/r6O0w0s.jpg';
                $user_info = $uid > 0 ? get_userdata($uid) : false;
                $rank_label = ($user_info && function_exists('nerv_get_rank_label')) ? nerv_get_rank_label($user_info) : 'Membre';
            ?>
            <div class="comment-card" id="comment-<?php echo esc_attr($comment->comment_ID); ?>">
                <div class="c-banner" style="background-image:url('<?php echo esc_url($user_banner); ?>')"></div>
                <img class="c-avatar" src="<?php echo esc_url($avatar_url); ?>" alt="Avatar">
                <div class="c-content">
                    <div class="c-author-row">
                        <span class="c-author"><?php echo esc_html($comment->comment_author); ?></span>
                        <span class="c-rank"><?php echo esc_html($rank_label); ?></span>
                    </div>
                    <div class="c-date"><?php echo esc_html(date_i18n('d M Y à H:i', strtotime($comment->comment_date))); ?></div>
                    <div class="c-text"><?php echo wpautop(esc_html($comment->comment_content)); ?></div>
                    <?php $current_user_id = get_current_user_id(); ?>
                    <?php if ($current_user_id > 0 && ($current_user_id == $uid || current_user_can('administrator'))) : ?>
                    <div class="c-actions">
                        <?php if ($current_user_id == $uid): ?>
                            <button class="c-btn" onclick="editComment(<?php echo esc_attr($comment->comment_ID); ?>, this)">Éditer</button>
                        <?php endif; ?>
                        <button class="c-btn delete" onclick="deleteComment(<?php echo esc_attr($comment->comment_ID); ?>, this)">Supprimer</button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="ns-c-empty">Aucun rapport de mission sur ce chapitre pour le moment.</div>
        <?php endif; ?>
    </div>

    <div class="ns-c-form-box">
        <?php if (is_user_logged_in() || get_option('comment_registration') == 0): ?>
            <h3>Ajouter un commentaire</h3>
            <form id="nerv-ajax-comment-form" onsubmit="event.preventDefault();">
                <textarea name="comment" required placeholder="Votre avis sur ce chapitre..."></textarea>
                <input type="hidden" name="comment_post_ID" value="<?php echo esc_attr($manga_id); ?>" />
                <input type="hidden" name="chapter_id" value="<?php echo esc_attr($cur_chap); ?>" />
                <button type="submit" class="ns-c-btn-submit">Publier</button>
            </form>
        <?php else: ?>
            <p style="text-align:center; color:var(--ns-gray); font-size:13px; margin:0;">
                Vous devez être <a href="<?php echo esc_url(home_url('/nerv-gate/')); ?>" style="color:var(--ns-yellow);">connecté</a> pour participer.
            </p>
        <?php endif; ?>
    </div>
</section>

<div class="ns-modal-overlay" id="modal-chapters">
  <div class="ns-modal">
    <div class="ns-modal-head">
      <h3>Chapitres</h3>
      <button class="ns-modal-close" id="close-chapters">&times;</button>
    </div>
    <div class="ns-modal-body">
      <?php
      if (!empty($all_chapters)) {
          foreach ($all_chapters as $chap) {
              $slug = $chap['chapter_slug'] ?? '';
              if (!$slug) continue;
              $url = $wp_manga_functions->build_chapter_url($manga_id, $slug);
              $name = $chap['chapter_name'] ?? $slug;
              $clean_name = ucwords(str_replace(['-', '_', 'chapitre chapitre'], [' ', ' ', 'Chapitre'], strtolower($name)));
              $is_current = ($slug === $cur_chap) ? 'current' : '';
              
              $chapter_id = $chap['chapter_id'] ?? 0;
              $custom_unlock = get_post_meta($manga_id, '_chapter_unlock_' . $chapter_id, true);
              $is_premium_m = false;
              $is_locked_m = false;
              if (!empty($custom_unlock)) {
                  $ts = strtotime($custom_unlock);
                  if ($ts && current_time('timestamp') < $ts) {
                      $is_premium_m = true;
                      if (!$user_has_access) { $is_locked_m = true; }
                  }
              }
              $prem_class = $is_premium_m ? ' is-premium-item' : '';
              $prem_icon = '';
              if ($is_premium_m) {
                  $prem_icon = $is_locked_m ? $svg_lock_closed : $svg_lock_open;
              }

              echo '<a href="' . esc_url($url) . '" class="ns-ch-item ' . esc_attr($is_current . $prem_class) . '"><span style="flex:1;">' . esc_html($clean_name) . '</span>' . $prem_icon . '</a>';
          }
      }
      ?>
    </div>
  </div>
</div>

<div class="ns-modal-overlay" id="modal-report">
    <div class="ns-modal">
        <div class="ns-modal-head">
            <h3>Signaler le chapitre</h3>
            <button class="ns-modal-close" id="close-report">&times;</button>
        </div>
        <div class="ns-modal-body">
            <form id="form-report">
                <label style="color:var(--ns-gray); font-size:10px; font-weight:800; text-transform:uppercase; margin-bottom:6px; display:block;">Raison</label>
                <select name="reason" style="width:100%; padding:12px; border-radius:8px; background:#000; border:1px solid var(--ns-stroke); color:#fff; outline:none; margin-bottom:16px;">
                    <option>Pages manquantes / Désordre</option>
                    <option>Mauvais chapitre / Œuvre</option>
                    <option>Problème qualité</option>
                    <option>Images qui ne chargent pas</option>
                    <option>Autre</option>
                </select>
                <label style="color:var(--ns-gray); font-size:10px; font-weight:800; text-transform:uppercase; margin-bottom:6px; display:block;">Détails</label>
                <textarea name="desc" required style="width:100%; padding:12px; border-radius:8px; background:#000; border:1px solid var(--ns-stroke); color:#fff; outline:none; resize:vertical; min-height:80px; margin-bottom:16px;"></textarea>
                <button type="submit" style="width:100%; padding:14px; background:var(--ns-yellow); color:#000; font-weight:900; font-size:12px; border:none; border-radius:8px; cursor:pointer; text-transform:uppercase;">Envoyer</button>
            </form>
        </div>
    </div>
</div>

<?php if ($is_staff) : ?>
<div class="ns-modal-overlay" id="modal-qc">
    <div class="ns-modal" style="max-width:600px; width:95%;">
        <div class="ns-modal-head" style="border-bottom-color: rgba(244,201,93,0.3);">
            <h3 style="color:var(--ns-yellow);">SIGNALEMENT QC 🚨</h3>
            <button class="ns-modal-close" id="close-qc" style="color:var(--ns-yellow);">&times;</button>
        </div>
        <div class="ns-modal-body">
            <div style="width: 100%; height: 65vh; border-radius: 8px; overflow: hidden; border: 2px solid var(--ns-yellow); margin-bottom: 10px; background:#000;">
                <img id="qc-preview-img" src="" style="width: 100%; height: 100%; object-fit: cover; object-position: center 50%;">
            </div>
            
            <label style="color:var(--ns-yellow); font-size:10px; font-weight:800; text-transform:uppercase; margin-bottom:6px; display:block; text-align:center;">Ajuster la zone visible pour le correcteur</label>
            <input type="range" id="qc-slider" class="nerv-slider" min="0" max="100" value="50" step="0.1">
            
            <textarea id="qc-note" placeholder="Décrivez l'erreur (Ex: Bulle du haut, remplacer 'Salut' par 'Bonjour')..." style="width:100%; padding:12px; border-radius:8px; background:#000; border:1px solid rgba(244,201,93,0.3); color:#fff; outline:none; resize:vertical; min-height:80px; margin-bottom:16px; font-family:inherit;"></textarea>
            <button id="qc-submit-btn" style="width:100%; padding:14px; background:var(--ns-yellow); color:#000; font-weight:900; font-size:12px; border:none; border-radius:8px; cursor:pointer; text-transform:uppercase; transition:0.2s;">Envoyer au Central Dogma</button>
        </div>
    </div>
</div>

<div class="ns-modal-overlay" id="nervCropModal">
    <div class="ns-modal" style="text-align:center; max-width:550px;">
        <div class="ns-modal-head"><h3>Cadrage Aperçu (Réseaux / Accueil)</h3></div>
        <div class="ns-modal-body">
            <p style="font-size:11px; color:#888; margin-bottom:10px;">Ce format 16/9 sera utilisé pour l'aperçu du chapitre sur la page de l'œuvre.</p>
            <div class="ncm-preview-box"><img id="ncmPreviewImg" src="" style="object-position: center 50%;"></div>
            <input type="range" id="ncmSlider" class="nerv-slider" min="0" max="100" value="50" step="0.1">
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button class="ns-btn" onclick="document.getElementById('nervCropModal').classList.remove('active'); document.getElementById('nervGalleryModal').classList.add('active');" style="flex:1">Retour</button>
                <button class="ns-btn gold" id="ncmSave" style="flex:1;">Sauvegarder la Cover</button>
            </div>
        </div>
    </div>
</div>

<div class="ns-modal-overlay" id="nervGalleryModal">
    <div class="ns-modal" style="max-width:800px; width:95%;">
        <div class="ns-modal-head" style="border-bottom: 1px solid var(--ns-yellow);">
            <h3 style="color:var(--ns-yellow);">SÉLECTION DE LA COVER</h3>
            <button class="ns-modal-close" id="close-gallery" style="color:var(--ns-yellow);">&times;</button>
        </div>
        <div class="ns-modal-body" style="background:#000;">
            <p style="font-size:11px; color:#888; margin-bottom:15px; text-transform:uppercase;">Cliquez sur l'image à utiliser pour l'aperçu du chapitre.</p>
            <div id="nerv-gallery-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; max-height: 50vh; overflow-y: auto; padding-right:5px;">
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<nav id="nerv-control-bar">
  <a href="<?php echo $nerv_prev_url ? esc_url($nerv_prev_url) : '#'; ?>" class="nc-ico <?php echo $nerv_prev_url ? '' : 'disabled'; ?> <?php echo $nerv_prev_premium ? 'is-premium-ico' : ''; ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
  </a>
  <div class="nc-sep"></div>
  <button id="nerv-chapters-bottom" class="nc-ico">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line></svg>
  </button>
  <div class="nc-sep"></div>
  <a href="<?php echo esc_url(home_url('/')); ?>" class="nc-ico">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
  </a>
  <div class="nc-sep"></div>
  <a href="<?php echo $nerv_next_url ? esc_url($nerv_next_url) : '#'; ?>" class="nc-ico <?php echo $nerv_next_url ? '' : 'disabled'; ?> <?php echo $nerv_next_premium ? 'is-premium-ico' : ''; ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
  </a>
</nav>

<script>
document.addEventListener("DOMContentLoaded", function () {
    
    const metaViewport = document.querySelector('meta[name="viewport"]');
    if (metaViewport) {
        metaViewport.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes');
    }

    const mIdTrack = '<?php echo esc_js($manga_id); ?>';
    const cSlugTrack = '<?php echo esc_js($cur_chap); ?>';
    const cIdTrack = '<?php echo esc_js($cur_cid); ?>';
    const isLogged = <?php echo is_user_logged_in() ? 'true' : 'false'; ?>;

    if (mIdTrack && cSlugTrack) {
        let gHist = JSON.parse(localStorage.getItem('nerv_global_history') || '[]');
        gHist = gHist.filter(h => h.id !== mIdTrack);
        gHist.unshift({ id: mIdTrack, slug: cSlugTrack });
        if (gHist.length > 15) gHist.pop();
        localStorage.setItem('nerv_global_history', JSON.stringify(gHist));

        if (isLogged) {
            const fdSync = new FormData();
            fdSync.append('action', 'nerv_sync_history');
            fdSync.append('manga_id', mIdTrack);
            fdSync.append('chapter_slug', cSlugTrack);
            fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', { method: 'POST', body: fdSync });
        }

        if (cIdTrack) {
            let rHist = JSON.parse(localStorage.getItem('nerv_read_' + mIdTrack) || '[]');
            if (!rHist.includes(cIdTrack)) {
                rHist.push(cIdTrack);
                localStorage.setItem('nerv_read_' + mIdTrack, JSON.stringify(rHist));
            }
        }
    }

    const mChap = document.getElementById('modal-chapters');
    const mRep  = document.getElementById('modal-report');
    
    document.getElementById('open-chapters-modal')?.addEventListener('click', () => mChap.classList.add('active'));
    document.getElementById('nerv-chapters-bottom')?.addEventListener('click', () => mChap.classList.add('active'));
    document.getElementById('close-chapters')?.addEventListener('click', () => mChap.classList.remove('active'));

    document.getElementById('open-report-modal')?.addEventListener('click', () => mRep.classList.add('active'));
    document.getElementById('close-report')?.addEventListener('click', () => mRep.classList.remove('active'));

    document.querySelectorAll('.ns-modal-overlay').forEach(mw => {
        mw.addEventListener('click', (e) => { if (e.target === mw && mw.id !== 'modal-qc') mw.classList.remove('active'); });
    });

    setTimeout(() => {
        document.querySelectorAll('#nerv-reading-root img:not(.page-click-zone)').forEach(img => {
            img.style.width = '100%'; img.style.height = 'auto'; img.style.display = 'block'; img.style.margin = '0 auto';
        });
    }, 1000);

    const modeBtns = document.querySelectorAll('.ns-mode-btn');
    const readingWrap = document.getElementById('nerv-reading-top');
    let currentMode = 'vertical';
    let currentPage = 0;

    function updatePageDisplay() {
        const imgs = document.querySelectorAll('#nerv-reading-root img:not(.page-click-zone)');
        if (currentMode !== 'page' || imgs.length === 0) return;
        
        if (currentPage >= imgs.length) {
            imgs.forEach(img => img.classList.remove('active-page'));
            window.scrollTo({ top: document.getElementById('nerv-end-screen').offsetTop - 50, behavior: 'smooth' });
        } else {
            imgs.forEach((img, index) => {
                if (index === currentPage) {
                    img.classList.add('active-page');
                    const lazySrc = img.getAttribute('data-src') || img.getAttribute('data-lazy-src') || img.getAttribute('src');
                    if (lazySrc && img.getAttribute('src') !== lazySrc) img.src = lazySrc;
                } else {
                    img.classList.remove('active-page');
                }
            });
            window.scrollTo({ top: readingWrap.offsetTop, behavior: 'smooth' });
        }
    }

    document.getElementById('click-next')?.addEventListener('click', () => { const imgs = document.querySelectorAll('#nerv-reading-root img:not(.page-click-zone)'); if(currentPage <= imgs.length - 1){ currentPage++; updatePageDisplay(); }});
    document.getElementById('click-prev')?.addEventListener('click', () => { if(currentPage > 0){ currentPage--; updatePageDisplay(); }});

    modeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            modeBtns.forEach(b => { b.classList.remove('active'); });
            this.classList.add('active');
            currentMode = this.getAttribute('data-mode');

            if (currentMode === 'page') {
                readingWrap.classList.remove('reading-mode-vertical'); readingWrap.classList.add('reading-mode-page');
                currentPage = 0; updatePageDisplay();
            } else {
                readingWrap.classList.remove('reading-mode-page'); readingWrap.classList.add('reading-mode-vertical');
                document.querySelectorAll('#nerv-reading-root img').forEach(img => img.classList.remove('active-page'));
            }
        });
    });

    const topProgress = document.getElementById('ns-progress-bar');
    const bar = document.getElementById('nerv-control-bar');
    let lastScrollY = window.scrollY;

    window.addEventListener('scroll', () => {
        if (currentMode === 'page') return;
        const scrollTop = window.scrollY;
        const maxScroll = (document.documentElement.scrollHeight - window.innerHeight) || 1;
        
        if (topProgress) topProgress.style.width = Math.max(0, Math.min(100, (scrollTop / maxScroll) * 100)) + '%';
        
        if (bar) {
            const goingDown = scrollTop > lastScrollY && scrollTop > 150;
            const isBottom = (window.innerHeight + scrollTop) >= document.body.offsetHeight - 200;
            if (goingDown && !isBottom) bar.classList.add('is-hidden'); else bar.classList.remove('is-hidden');
        }
        lastScrollY = scrollTop;
    }, { passive: true });

    <?php if ($is_staff) : ?>
    const modalCrop = document.getElementById('nervCropModal');
    const previewImg = document.getElementById('ncmPreviewImg');
    const cropSlider = document.getElementById('ncmSlider');
    
    const qcBtn = document.getElementById('nerv-qc-toggle');
    const modalQc = document.getElementById('modal-qc');
    const qcPreview = document.getElementById('qc-preview-img');
    const qcSlider = document.getElementById('qc-slider');
    const qcNote = document.getElementById('qc-note');
    const qcSubmit = document.getElementById('qc-submit-btn');

    const btnOpenGallery = document.getElementById('btn-open-gallery');
    const modalGallery = document.getElementById('nervGalleryModal');
    const galleryGrid = document.getElementById('nerv-gallery-grid');

    let currentCropSrc = '';
    let qcTargetImg = '';
    let qcActive = false;

    cropSlider.addEventListener('input', (e) => { previewImg.style.objectPosition = `center ${e.target.value}%`; });
    qcSlider.addEventListener('input', (e) => { qcPreview.style.objectPosition = `center ${e.target.value}%`; });

    document.getElementById('close-gallery')?.addEventListener('click', () => modalGallery.classList.remove('active'));

    if (btnOpenGallery) {
        btnOpenGallery.addEventListener('click', () => {
            galleryGrid.innerHTML = ''; 
            
            document.querySelectorAll('#nerv-reading-root img:not(.page-click-zone)').forEach((img) => {
                const src = img.src || img.dataset.src;
                if(!src) return;

                const thumb = document.createElement('img');
                thumb.src = src;
                thumb.style.width = '100%';
                thumb.style.aspectRatio = '1/1.5';
                thumb.style.objectFit = 'cover';
                thumb.style.cursor = 'crosshair';
                thumb.style.border = '1px solid var(--ns-stroke)';
                thumb.style.borderRadius = '4px';
                thumb.style.transition = '0.2s';
                
                thumb.onmouseover = () => { thumb.style.borderColor = 'var(--ns-yellow)'; thumb.style.transform = 'scale(1.05)'; };
                thumb.onmouseout = () => { thumb.style.borderColor = 'var(--ns-stroke)'; thumb.style.transform = 'scale(1)'; };

                thumb.addEventListener('click', () => {
                    modalGallery.classList.remove('active');
                    currentCropSrc = src; 
                    previewImg.src = currentCropSrc; 
                    cropSlider.value = 50;
                    previewImg.style.objectPosition = `center 50%`;
                    modalCrop.classList.add('active'); 
                });

                galleryGrid.appendChild(thumb);
            });
            modalGallery.classList.add('active');
        });
    }

    document.querySelectorAll('#nerv-reading-root img:not(.page-click-zone)').forEach(img => {
        img.addEventListener('click', (e) => {
            if (!qcActive) return;
            e.preventDefault(); 
            e.stopPropagation();
            qcTargetImg = img.getAttribute('src') || img.dataset.src;
            qcPreview.src = qcTargetImg;
            qcSlider.value = 50; 
            qcPreview.style.objectPosition = `center 50%`;
            qcNote.value = '';
            modalQc.classList.add('active');
        });
    });

    if (qcBtn) {
        qcBtn.addEventListener('click', () => {
            qcActive = !qcActive;
            document.body.classList.toggle('qc-mode-active', qcActive);
            qcBtn.style.color = qcActive ? '#fff' : '#888';
            qcBtn.style.background = qcActive ? 'var(--ns-yellow)' : 'transparent';
            if(qcActive) qcBtn.style.color = '#000';
        });
    }

    // LE FAMEUX HACK D'USURPATION (ENVOIE À LUI-MÊME)
    document.getElementById('ncmSave').onclick = async function() {
        this.innerText = 'Envoi...';
        this.disabled = true;
        
        const fd = new FormData();
        fd.append('nerv_front_action', 'set_chapter_cover');
        fd.append('manga_id', '<?php echo esc_js($manga_id); ?>');
        fd.append('chapter_id', '<?php echo esc_js($cur_cid); ?>'); // Injecte l'ID validé par le PHP
        fd.append('image_url', currentCropSrc);
        fd.append('position', `center ${cropSlider.value}%`);
        
        try {
            const res = await fetch('', { method: 'POST', body: fd });
            const json = await res.json();
            if (json.success) {
                location.reload();
            } else {
                alert('Erreur: ' + json.data);
                this.innerText = 'Sauvegarder la Cover';
                this.disabled = false;
            }
        } catch (e) {
            alert('Erreur réseau.');
            this.innerText = 'Sauvegarder la Cover';
            this.disabled = false;
        }
    };

    document.getElementById('close-qc').addEventListener('click', () => modalQc.classList.remove('active'));
    
    qcSubmit.addEventListener('click', async () => {
        const note = qcNote.value.trim();
        if (!note) { alert('Pilote, vous devez indiquer une correction à faire !'); return; }
        
        qcSubmit.disabled = true; 
        qcSubmit.innerText = 'ENVOI EN COURS...';
        
        const fd = new FormData();
        fd.append('action', 'nerv_submit_qc');
        fd.append('manga_id', mIdTrack);
        fd.append('chapter', cSlugTrack);
        fd.append('image_url', qcTargetImg);
        fd.append('position', `center ${qcSlider.value}%`);
        fd.append('note', note);

        try {
            const res = await fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', { method: 'POST', body: fd });
            const json = await res.json();
            if (json.success) {
                qcSubmit.innerText = 'TRANSMIS AU CENTRAL DOGMA !';
                qcSubmit.style.background = '#22c55e';
                setTimeout(() => { 
                    modalQc.classList.remove('active'); 
                    qcSubmit.disabled = false; 
                    qcSubmit.innerText = 'Envoyer au Central Dogma'; 
                    qcSubmit.style.background = 'var(--ns-yellow)'; 

                    qcActive = false; 
                    document.body.classList.remove('qc-mode-active'); 
                    qcBtn.style.color = '#888'; 
                    qcBtn.style.background = 'transparent'; 
                }, 1500);
            } else { 
                alert('Erreur: ' + json.data); 
                qcSubmit.disabled = false; 
                qcSubmit.innerText = 'Envoyer au Central Dogma'; 
            }
        } catch (err) { 
            alert('Erreur réseau.'); 
            qcSubmit.disabled = false; 
            qcSubmit.innerText = 'Envoyer au Central Dogma'; 
        }
    });
    <?php endif; ?>

    const commentForm = document.getElementById('nerv-ajax-comment-form');
    if (commentForm) {
        commentForm.addEventListener('submit', async (e) => {
            e.preventDefault(); 
            const btn = commentForm.querySelector('button[type="submit"]');
            const textarea = commentForm.querySelector('textarea[name="comment"]');
            const postId = commentForm.querySelector('input[name="comment_post_ID"]').value;
            const chapterId = commentForm.querySelector('input[name="chapter_id"]').value;
            const content = textarea.value.trim();
            if (!content) return;
            btn.disabled = true; btn.innerText = 'ENVOI EN COURS...';
            try {
                const fd = new FormData();
                fd.append('nerv_front_action', 'add_comment');
                fd.append('post_id', postId);
                fd.append('chapter', chapterId);
                fd.append('content', content);
                const res = await fetch('', { method: 'POST', body: fd });
                const json = await res.json();
                if (json.success) {
                    textarea.value = ''; btn.innerText = 'SUCCÈS !'; btn.style.background = '#22c55e';
                    setTimeout(() => location.reload(), 500); 
                } else {
                    alert('Erreur : ' + (json.data || 'Impossible d\'envoyer le message.'));
                    btn.disabled = false; btn.innerText = 'PUBLIER';
                }
            } catch (err) {
                alert('Erreur de connexion.'); btn.disabled = false; btn.innerText = 'PUBLIER';
            }
        });
    }
});

async function deleteComment(cid, btn) {
    if(!confirm('Supprimer ce commentaire ?')) return;
    const card = btn.closest('.comment-card'); btn.innerText = '...';
    try {
        const fd = new FormData(); fd.append('nerv_front_action', 'delete_comment'); fd.append('comment_id', cid);
        const res = await fetch('', { method: 'POST', body: fd });
        const json = await res.json();
        if(json.success) { card.style.opacity = '0'; setTimeout(() => card.remove(), 300); } 
        else { alert('Erreur.'); btn.innerText = 'Supprimer'; }
    } catch(err) { alert('Erreur réseau.'); btn.innerText = 'Supprimer'; }
}

async function editComment(cid, btn) {
    const card = btn.closest('.comment-card');
    const textNode = card.querySelector('.c-text');
    if(card.querySelector('.c-edit-area')) return;
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = textNode.innerHTML.replace(/<br\s*\/?>/gi, '\n');
    const oldText = tempDiv.textContent.trim();
    textNode.style.display = 'none'; btn.style.display = 'none';
    const editWrapper = document.createElement('div'); editWrapper.className = 'c-edit-area'; editWrapper.style.marginTop = '10px';
    const textarea = document.createElement('textarea'); textarea.value = oldText; textarea.style.width = '100%'; textarea.style.background = '#000'; textarea.style.color = '#fff'; textarea.style.border = '1px solid rgba(255,255,255,0.1)'; textarea.style.padding = '12px'; textarea.style.borderRadius = '8px'; textarea.style.minHeight = '80px'; textarea.style.marginBottom = '10px'; textarea.style.outline = 'none'; textarea.style.fontFamily = 'inherit';
    const actions = document.createElement('div'); actions.style.display = 'flex'; actions.style.gap = '10px';
    const saveBtn = document.createElement('button'); saveBtn.innerText = 'Enregistrer'; saveBtn.className = 'ns-btn gold';
    const cancelBtn = document.createElement('button'); cancelBtn.innerText = 'Annuler'; cancelBtn.className = 'ns-btn ghost';
    actions.appendChild(saveBtn); actions.appendChild(cancelBtn);
    editWrapper.appendChild(textarea); editWrapper.appendChild(actions);
    textNode.parentNode.insertBefore(editWrapper, textNode.nextSibling);
    cancelBtn.addEventListener('click', () => { editWrapper.remove(); textNode.style.display = 'block'; btn.style.display = 'inline-flex'; });
    saveBtn.addEventListener('click', async () => {
        const newText = textarea.value.trim(); if(!newText) return;
        saveBtn.innerText = '...'; saveBtn.disabled = true;
        try {
            const fd = new FormData(); fd.append('nerv_front_action', 'edit_comment'); fd.append('comment_id', cid); fd.append('content', newText);
            const res = await fetch('', { method: 'POST', body: fd });
            const json = await res.json();
            if(json.success) {
                textNode.innerHTML = json.content || newText.replace(/\n/g, '<br>');
                editWrapper.remove(); textNode.style.display = 'block'; btn.style.display = 'inline-flex';
            } else { alert('Erreur.'); saveBtn.innerText = 'Enregistrer'; saveBtn.disabled = false; }
        } catch(err) { alert('Erreur réseau.'); saveBtn.innerText = 'Enregistrer'; saveBtn.disabled = false; }
    });
}
</script>

<?php get_footer(); ?>