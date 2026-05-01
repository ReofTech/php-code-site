<?php
/**
 * Template Name: NERV Central Dogma (Unified Hub)
 * Version V42 : Upload Médias Natif WP + Purge Cache Madara + Stats
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$roles = (array) $current_user->roles;
$is_staff = current_user_can('manage_options') || in_array('nervmodo', $roles) || in_array('magi', $roles) || in_array('marduk', $roles);
$is_supreme_admin = current_user_can('manage_options');

if (!$is_staff) {
    get_header();
    echo '<div style="padding:100px; text-align:center; color:#ff4d4d; font-family:monospace; background:#000; height:100vh;"><h1>ACCÈS REFUSÉ - PROTOCOLE DE SÉCURITÉ ACTIVÉ</h1></div>';
    get_footer(); return;
}

// Permet de charger la modale d'upload de médias WordPress sur le front-end
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_media();
});

// =========================================================================
// TRAITEMENT DES FORMULAIRES (PHP)
// =========================================================================
$generated_pionniers = [];
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Purge du cache Madara (Nouveau)
    if (isset($_POST['nerv_purge_madara'])) {
        check_admin_referer('nerv_purge_madara_action');
        if ($is_supreme_admin) {
            global $wpdb;
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_wp_manga_chapters_%' OR option_name LIKE '_transient_wp_manga_chapters_%'");
            $success_msg = "Cache de lecture Madara purgé. Synchronisation des chapitres forcée.";
        }
    }

    // 2. Suppression d'un utilisateur
    if (isset($_POST['nerv_delete_user_id'])) {
        check_admin_referer('nerv_delete_user_action');
        if ($is_supreme_admin) {
            $uid_to_delete = intval($_POST['nerv_delete_user_id']);
            if ($uid_to_delete === get_current_user_id()) {
                $error_msg = "Sécurité activée : Vous ne pouvez pas purger votre propre compte de commandement.";
            } else {
                require_once(ABSPATH . 'wp-admin/includes/user.php');
                $deleted_user = get_userdata($uid_to_delete);
                if ($deleted_user) {
                    wp_delete_user($uid_to_delete);
                    $success_msg = "Le compte '{$deleted_user->user_login}' a été définitivement purgé du système.";
                }
            }
        }
    }

    // 3. Modification d'un utilisateur (Identifiants, PP, Bannière)
    if (isset($_POST['nerv_edit_user_id'])) {
        check_admin_referer('nerv_edit_user_action');
        if ($is_supreme_admin) {
            $uid_to_edit = intval($_POST['nerv_edit_user_id']);
            $new_login   = sanitize_user($_POST['nerv_edit_login']);
            $new_email   = sanitize_email($_POST['nerv_edit_email']);
            $new_pass    = $_POST['nerv_edit_pass'];
            $new_avatar  = esc_url_raw($_POST['nerv_edit_avatar']);
            $new_banner  = esc_url_raw($_POST['nerv_edit_banner']);

            $user_data = array(
                'ID' => $uid_to_edit,
                'user_email' => $new_email
            );

            if (!empty($new_pass)) {
                $user_data['user_pass'] = $new_pass;
            }

            // Gérer le changement de pseudo
            $old_user_data = get_userdata($uid_to_edit);
            if ($old_user_data && $old_user_data->user_login !== $new_login) {
                global $wpdb;
                $wpdb->update(
                    $wpdb->users,
                    array('user_login' => $new_login),
                    array('ID' => $uid_to_edit)
                );
            }

            $user_id = wp_update_user($user_data);

            if (is_wp_error($user_id)) {
                $error_msg = "Erreur lors de la modification : " . $user_id->get_error_message();
            } else {
                update_user_meta($uid_to_edit, 'nerv_avatar_url', $new_avatar);
                update_user_meta($uid_to_edit, 'nerv_banner_url', $new_banner);
                $success_msg = "Le profil de l'utilisateur a été mis à jour avec succès.";
            }
        }
    }

    // 4. Création manuelle d'un compte
    if (isset($_POST['nerv_create_user'])) {
        check_admin_referer('nerv_create_user_action');
        if ($is_supreme_admin) {
            $new_login = sanitize_user($_POST['new_user_login']);
            $new_email = sanitize_email($_POST['new_user_email']);
            $new_pass  = $_POST['new_user_pass'];
            $new_role  = sanitize_text_field($_POST['new_user_role']);

            if (username_exists($new_login) || email_exists($new_email)) {
                $error_msg = "Erreur : Ce pseudo ou cet email est déjà utilisé dans le système.";
            } else {
                $user_id = wp_create_user($new_login, $new_pass, $new_email);
                if (is_wp_error($user_id)) {
                    $error_msg = "Erreur de création : " . $user_id->get_error_message();
                } else {
                    $u = new WP_User($user_id);
                    $u->set_role(strtolower($new_role));
                    $success_msg = "Le compte '{$new_login}' a été forgé avec le rôle '{$new_role}'.";
                }
            }
        }
    }

    // 5. Suppression d'un code d'accès
    if (isset($_POST['delete_nerv_code'])) {
        $codes = get_option('nerv_access_codes', []);
        if (!is_array($codes)) $codes = [];
        $k = sanitize_text_field($_POST['delete_code_key'] ?? '');
        if ($k && isset($codes[$k])) {
            unset($codes[$k]);
            update_option('nerv_access_codes', $codes);
            $success_msg = "Code $k purgé du système.";
        }
    }

    // 6. Génération Code Standard
    if (isset($_POST['generate_nerv_code'])) {
        check_admin_referer('nerv_gen_action');
        $codes = get_option('nerv_access_codes', []);
        if (!is_array($codes)) $codes = [];
        $new_code = 'ACCESS-' . strtoupper(wp_generate_password(8, false));
        $role = sanitize_text_field($_POST['nerv_code_role'] ?? 'DISCORD');
        $codes[$new_code] = ['status' => 'active', 'role' => $role, 'user' => null, 'date' => current_time('mysql')];
        update_option('nerv_access_codes', $codes);
        $success_msg = "Code généré : $new_code ($role)";
    }

    // 7. Génération Pionniers
    if (isset($_POST['generate_tester'])) {
        check_admin_referer('nerv_gen_tester');
        $num = min(10, max(1, intval($_POST['nerv_tester_num'] ?? 1)));
        $duration = intval($_POST['nerv_tester_duration'] ?? 604800);
        $expiration_time = current_time('timestamp') + $duration;
        for ($i = 0; $i < $num; $i++) {
            $username = 'pionnier_' . strtolower(wp_generate_password(6, false));
            $password = wp_generate_password(12);
            $user_id = wp_create_user($username, $password, $username . '@test.local');
            if (!is_wp_error($user_id)) {
                $u = new WP_User($user_id); $u->set_role('dummy');
                update_user_meta($user_id, 'nerv_test_expiration', $expiration_time);
                $generated_pionniers[] = ['username' => $username, 'password' => $password];
            }
        }
        if(!empty($generated_pionniers)) $success_msg = "Pionniers injectés dans le système.";
    }
}

$access_codes = get_option('nerv_access_codes', []) ?: [];
$all_users = get_users(['orderby' => 'registered', 'order' => 'DESC']);
$total_mangas = wp_count_posts('wp-manga')->publish ?? 0;

get_header();
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,700;0,800;0,900;1,700;1,800;1,900&family=Inter:wght@400;500;600;700;800;900&display=swap');
:root { 
    --ns-bg: #000000; --ns-panel: #070707; --ns-stroke: rgba(255,255,255,0.08); 
    --ns-yellow: #F4C95D; --ns-red: #ef4444; --ns-green: #22c55e; --ns-muted: #888888; 
    --ns-wrap: min(1000px, calc(100vw - 40px)); 
}
html, body { background: var(--ns-bg) !important; color: #fff; font-family: 'Inter', sans-serif; overflow-x: hidden; }
#page, .site, #content, .site-content, main { background: transparent !important; border: none !important; box-shadow: none !important; margin: 0 !important; padding: 0 !important; }
.c-page-header, .c-breadcrumb { display: none !important; }

.cd-wrap { width: var(--ns-wrap); margin: 60px auto 100px; }
.cd-header { text-align: center; margin-bottom: 60px; padding-bottom: 24px; border-bottom: 1px dashed var(--ns-stroke); }
.cd-title { font-family: 'Barlow Condensed', sans-serif; font-size: 52px; font-weight: 900; font-style: italic; text-transform: uppercase; margin: 0; }
.cd-title span { color: var(--ns-yellow); text-shadow: 0 0 20px rgba(244, 201, 93, 0.3); }

/* Stats HUD */
.sys-hud-stats { display: flex; gap: 16px; margin-bottom: 32px; flex-wrap: wrap; }
.sys-hud-box { flex: 1; background: var(--ns-panel); border: 1px solid var(--ns-stroke); border-radius: 12px; padding: 16px 20px; text-align: center; border-bottom: 2px solid var(--ns-yellow); }
.sys-hud-val { font-family: 'Barlow Condensed', sans-serif; font-size: 32px; font-weight: 900; font-style: italic; color: #fff; line-height: 1; }
.sys-hud-lbl { font-size: 10px; font-weight: 800; color: var(--ns-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-top: 4px; }

/* Sections & Tabs */
.terminal-section { background: var(--ns-panel); border: 1px solid var(--ns-stroke); border-radius: 20px; padding: 40px; margin-bottom: 32px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); position: relative; }
.inner-nav { display: flex; gap: 8px; margin-bottom: 32px; border-bottom: 1px solid var(--ns-stroke); padding-bottom: 16px; flex-wrap: wrap; }
.i-tab { background: #000; border: 1px solid var(--ns-stroke); color: var(--ns-muted); padding: 12px 20px; font-weight: 800; font-size: 11px; text-transform: uppercase; border-radius: 8px; cursor: pointer; transition: 0.2s; }
.i-tab.active { background: rgba(244,201,93,0.1); color: var(--ns-yellow); border-color: var(--ns-yellow); }
.tab-content { display: none; animation: fadeIn 0.3s ease; }
.tab-content.active { display: block; }

/* UI Elements */
.input-label { font-size: 11px; font-weight: 900; color: var(--ns-muted); text-transform: uppercase; display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
.input-dogma { width: 100%; padding: 0 16px; background: #000; border: 1px solid var(--ns-stroke); color: #fff; border-radius: 12px; outline: none; height: 52px; font-size: 14px; box-sizing: border-box; transition: 0.2s; }
.input-dogma:focus { border-color: var(--ns-yellow); box-shadow: 0 0 10px rgba(244, 201, 93, 0.1); }
.btn-submit { height: 52px; padding: 0 32px; font-weight: 950; font-size: 12px; text-transform: uppercase; border: none; border-radius: 12px; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
.btn-action { background: var(--ns-yellow); color: #000; }
.btn-action:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(244, 201, 93, 0.3); }
.btn-danger { background: rgba(239, 68, 68, 0.1); color: var(--ns-red); border: 1px solid rgba(239, 68, 68, 0.3); }
.btn-danger:hover { background: var(--ns-red); color: #fff; transform: translateY(-3px); }
.btn-edit { background: rgba(255, 255, 255, 0.05); color: #fff; border: 1px solid rgba(255, 255, 255, 0.2); }
.btn-edit:hover { background: #fff; color: #000; transform: translateY(-3px); }
.flex-row { display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap; }

/* Custom Select */
.custom-select-wrapper { position: relative; flex: 1; min-width: 150px; }
.custom-select-trigger { display: flex; justify-content: space-between; align-items: center; cursor: pointer; user-select: none; }
.custom-select-options { display: none; position: absolute; top: calc(100% + 8px); left: 0; right: 0; background: #070707; border: 1px solid var(--ns-yellow); border-radius: 12px; z-index: 999; overflow: hidden; box-shadow: 0 15px 30px rgba(0,0,0,0.8); }
.custom-option { padding: 16px 20px; color: var(--ns-muted); font-size: 13px; font-weight: 700; cursor: pointer; border-bottom: 1px solid rgba(255,255,255,0.03); }
.custom-option:hover { background: rgba(244, 93, 93, 0.1); color: var(--ns-yellow); padding-left: 28px; }

/* Listes Accréditations */
.acc-list { background: #000; border: 1px solid var(--ns-stroke); border-radius: 12px; padding: 10px; max-height: 400px; overflow-y: auto; }
.acc-item { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; background: rgba(255,255,255,0.01); border-radius: 8px; margin-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.05); }

/* Système de Filtres */
.filter-bar { display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap; }
.btn-filter { background: #000; border: 1px solid var(--ns-stroke); color: var(--ns-muted); padding: 8px 16px; font-weight: 800; font-size: 11px; text-transform: uppercase; border-radius: 8px; cursor: pointer; transition: 0.2s; }
.btn-filter.active, .btn-filter:hover { background: rgba(244,201,93,0.1); color: var(--ns-yellow); border-color: var(--ns-yellow); }

/* ID CARDS COMPACTES NERV */
.users-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; max-height: 700px; overflow-y: auto; padding-right: 8px; align-items: start; }
.users-grid::-webkit-scrollbar { width: 4px; }
.users-grid::-webkit-scrollbar-thumb { background: var(--ns-stroke); border-radius: 4px; }

.card-theme-magi { --role-color: #a855f7; }
.card-theme-marduk { --role-color: #f97316; }
.card-theme-pilote { --role-color: #0ea5e9; }
.card-theme-lilim { --role-color: #F4C95D; }
.card-theme-membre { --role-color: #4b5563; }

.id-card { 
    background: #000; 
    border: 1px solid var(--ns-stroke); 
    border-left: 4px solid var(--role-color, var(--ns-muted));
    border-radius: 16px; 
    padding: 20px; 
    display: flex; 
    flex-direction: column;
    gap: 16px;
    position: relative; 
    overflow: hidden;
    transition: 0.3s; 
    isolation: isolate;
}
.id-card:hover { 
    border-color: var(--role-color); 
    box-shadow: 0 8px 30px rgba(0,0,0,0.6), inset 0 0 20px rgba(255,255,255,0.02); 
    transform: translateY(-4px); 
}

/* Background Bannière */
.id-card-bg {
    position: absolute; inset: 0; z-index: -1;
    background-size: cover; background-position: center;
    opacity: 0.15; filter: grayscale(50%) blur(2px);
    transition: 0.3s;
}
.id-card:hover .id-card-bg { opacity: 0.3; filter: grayscale(0%) blur(0px); }
.id-card-gradient {
    position: absolute; inset: 0; z-index: -1;
    background: linear-gradient(to right, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.7) 100%);
}

.id-card-top { display: flex; gap: 16px; align-items: center; z-index: 2; }
.id-avatar { width: 64px; height: 64px; border-radius: 12px; object-fit: cover; border: 2px solid rgba(255,255,255,0.1); background: #111; flex-shrink: 0; box-shadow: 0 5px 15px rgba(0,0,0,0.5); }
.id-info { flex: 1; min-width: 0; }
.id-pseudo { font-family: 'Barlow Condensed', sans-serif; font-size: 22px; font-weight: 800; color: #fff; text-transform: uppercase; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.1; margin-bottom: 4px;}
.id-pseudo.is-vip { color: var(--ns-yellow); text-shadow: 0 0 10px rgba(244, 201, 93, 0.4); }
.id-email { font-size: 11px; font-weight: 500; color: rgba(255,255,255,0.6); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 10px;}

.id-badges { display: flex; gap: 6px; flex-wrap: wrap; }
.badge-role { font-size: 9px; font-weight: 900; padding: 4px 8px; border-radius: 6px; text-transform: uppercase; background: rgba(0,0,0,0.4); color: var(--role-color, #fff); border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(4px); }
.badge-timer { font-size: 9px; font-weight: 900; padding: 4px 8px; border-radius: 6px; text-transform: uppercase; background: rgba(244, 201, 93, 0.1); color: var(--ns-yellow); border: 1px solid rgba(244, 201, 93, 0.3); display: flex; align-items: center; gap: 4px; backdrop-filter: blur(4px); }

.id-card-hover { 
    max-height: 0; opacity: 0; overflow: hidden; 
    transition: max-height 0.3s ease, opacity 0.3s ease, margin-top 0.3s ease;
    display: flex; gap: 8px; z-index: 2;
}
.id-card:hover .id-card-hover { max-height: 60px; opacity: 1; margin-top: 8px; }

/* MODAL D'ÉDITION */
.nerv-modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px);
    z-index: 99999; display: none; align-items: center; justify-content: center;
}
.nerv-modal-overlay.active { display: flex; animation: fadeIn 0.3s ease; }
.nerv-modal {
    background: var(--ns-panel); border: 1px solid var(--ns-yellow); border-radius: 20px;
    padding: 40px; width: 100%; max-width: 600px; box-shadow: 0 30px 60px rgba(0,0,0,0.8);
    position: relative; max-height: 90vh; overflow-y: auto;
}
.nerv-modal-close {
    position: absolute; top: 20px; right: 20px; background: transparent; border: none;
    color: var(--ns-muted); font-size: 24px; cursor: pointer; transition: 0.2s;
}
.nerv-modal-close:hover { color: #fff; }

@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="cd-wrap">
    <div class="cd-header"><h1 class="cd-title">CENTRAL <span>DOGMA</span></h1></div>

    <div class="sys-hud-stats">
        <div class="sys-hud-box">
            <div class="sys-hud-val"><?php echo count($all_users); ?></div>
            <div class="sys-hud-lbl">Effectif Total</div>
        </div>
        <div class="sys-hud-box">
            <div class="sys-hud-val"><?php echo esc_html($total_mangas); ?></div>
            <div class="sys-hud-lbl">Œuvres Madara</div>
        </div>
        <div class="sys-hud-box">
            <div class="sys-hud-val" style="color:var(--ns-green);">ON</div>
            <div class="sys-hud-lbl">Statut Serveur</div>
        </div>
    </div>

    <?php if (!empty($success_msg)) : ?>
        <div style="background: rgba(34, 197, 94, 0.1); border: 1px solid var(--ns-green); border-radius: 12px; padding: 20px; margin-bottom: 30px; color: var(--ns-green); font-weight: 800;">
            ✓ <?php echo esc_html($success_msg); ?>
            <?php if ($generated_pionniers) : ?>
                <ul style="margin-top:10px; color:#fff; font-size:12px;"><?php foreach($generated_pionniers as $p) echo "<li>".$p['username']." / <code>".$p['password']."</code></li>"; ?></ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($error_msg)) : ?>
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--ns-red); border-radius: 12px; padding: 20px; margin-bottom: 30px; color: var(--ns-red); font-weight: 800;">
            ✗ <?php echo esc_html($error_msg); ?>
        </div>
    <?php endif; ?>

    <?php if ($is_supreme_admin) : ?>
    <div class="terminal-section" style="background: rgba(239, 68, 68, 0.03); border-color: rgba(239, 68, 68, 0.2); z-index:0; display:flex; flex-wrap:wrap; gap:20px; justify-content:space-between; align-items:center;">
        <div style="flex:1; min-width:300px;">
            <h3 style="color:#fff; font-size:24px; text-transform:uppercase; font-family:'Barlow Condensed', sans-serif; margin:0 0 8px 0;">MAINTENANCE SYSTÈME</h3>
            <p style="color:var(--ns-muted); margin:0; font-size:12px;">Gestion des verrous et de la synchronisation Madara.</p>
        </div>
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <button class="btn-submit btn-danger" style="min-width: 200px;" onclick="fetch('<?php echo admin_url('admin-ajax.php'); ?>', {method:'POST',body:new URLSearchParams({action:'nerv_toggle_maintenance'})}).then(()=>location.reload())">
                VERROUILLAGE SITE
            </button>
            <form method="post" style="margin:0;">
                <?php wp_nonce_field('nerv_purge_madara_action'); ?>
                <input type="hidden" name="nerv_purge_madara" value="1">
                <button type="submit" class="btn-submit" style="min-width: 200px; background: rgba(244, 201, 93, 0.1); border: 1px solid var(--ns-yellow); color: var(--ns-yellow);">
                    PURGER CACHE MADARA
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="terminal-section" style="z-index: 10;">
        <div class="input-label" style="color:var(--ns-yellow); font-size:14px; margin-bottom:24px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            GESTION DES ACCÈS VIP (PILOTES)
        </div>
        <form id="formVip" class="flex-row">
            <div style="flex:2; min-width:200px;">
                <label class="input-label">Pseudo ou Email</label>
                <input type="text" name="user_target" class="input-dogma" placeholder="Ex: Shinji" required>
            </div>
            <div style="flex:1; min-width:160px;">
                <label class="input-label">Durée</label>
                <input type="hidden" name="vip_days" id="realVipInput" value="30">
                <div class="custom-select-wrapper" data-type="vip">
                    <div class="input-dogma custom-select-trigger"><span>1 Mois</span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
                    <div class="custom-select-options">
                        <div class="custom-option selected" data-value="30">1 Mois</div>
                        <div class="custom-option" data-value="90">3 Mois</div>
                        <div class="custom-option" data-value="180">6 Mois</div>
                        <div class="custom-option" data-value="365">1 An</div>
                        <div class="custom-option" data-value="9999">Illimité</div>
                    </div>
                </div>
            </div>
            <div style="display:flex; gap:10px; flex:1.5;">
                <button type="submit" id="btnGrant" class="btn-submit btn-action" style="flex:1;">VALIDER</button>
                <button type="button" id="btnRemove" class="btn-submit btn-danger" style="flex:1;">RÉVOQUER</button>
            </div>
        </form>
    </div>

    <?php if ($is_supreme_admin) : ?>
    <div class="terminal-section" style="z-index: 5;">
        <div class="input-label" style="color:var(--ns-yellow); font-size:14px; margin-bottom:24px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            GÉNÉRATEUR D'ACCRÉDITATIONS & CRÉATION NERV
        </div>

        <div class="inner-nav">
            <button class="i-tab active" data-target="tab-create">Création Manuelle</button>
            <button class="i-tab" data-target="tab-std">Codes d'Accès</button>
            <button class="i-tab" data-target="tab-pio">Pionniers</button>
            <button class="i-tab" data-target="tab-reg">Registre</button>
        </div>

        <div id="tab-create" class="tab-content active">
            <form method="post">
                <?php wp_nonce_field('nerv_create_user_action'); ?>
                <input type="hidden" name="nerv_create_user" value="1">
                <div class="flex-row" style="margin-bottom: 16px;">
                    <div style="flex:1; min-width:200px;">
                        <label class="input-label">Pseudo</label>
                        <input type="text" name="new_user_login" class="input-dogma" placeholder="Ex: Asuka" required>
                    </div>
                    <div style="flex:1; min-width:200px;">
                        <label class="input-label">Email</label>
                        <input type="email" name="new_user_email" class="input-dogma" placeholder="asuka@nerv.com" required>
                    </div>
                </div>
                <div class="flex-row">
                    <div style="flex:1; min-width:200px;">
                        <label class="input-label">Mot de passe</label>
                        <input type="text" name="new_user_pass" class="input-dogma" value="<?php echo wp_generate_password(12, false); ?>" required>
                    </div>
                    <div style="flex:1; min-width:150px;">
                        <label class="input-label">Rôle</label>
                        <input type="hidden" name="new_user_role" id="realRoleCreateInput" value="subscriber">
                        <div class="custom-select-wrapper" data-type="create_role">
                            <div class="input-dogma custom-select-trigger"><span>Membre (Base)</span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
                            <div class="custom-select-options">
                                <div class="custom-option selected" data-value="subscriber">Membre (Base)</div>
                                <div class="custom-option" data-value="lilim">Lilim (VIP)</div>
                                <div class="custom-option" data-value="pilote">Pilote (Staff)</div>
                                <div class="custom-option" data-value="marduk">Marduk (Admin)</div>
                                <div class="custom-option" data-value="magi">Magi (Super Admin)</div>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit btn-action" style="flex:1; min-width:200px;">FORGER COMPTE</button>
                </div>
            </form>
        </div>

        <div id="tab-std" class="tab-content">
            <form method="post" class="flex-row">
                <?php wp_nonce_field('nerv_gen_action'); ?>
                <input type="hidden" name="generate_nerv_code" value="1">
                <input type="hidden" name="nerv_code_role" id="realRoleInput" value="DISCORD">
                <div class="custom-select-wrapper" data-type="role" style="flex:2;">
                    <div class="input-dogma custom-select-trigger"><span>Abonné (Base)</span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
                    <div class="custom-select-options">
                        <div class="custom-option selected" data-value="DISCORD">Abonné (Base)</div>
                        <div class="custom-option" data-value="LILIM">Lilim (Discord VIP)</div>
                        <div class="custom-option" data-value="PILOTE">Pilote (Staff)</div>
                        <div class="custom-option" data-value="MARDUK">Marduk (RH)</div>
                        <div class="custom-option" data-value="MAGI">Magi (Admin)</div>
                    </div>
                </div>
                <button type="submit" class="btn-submit btn-action" style="flex:1;">GÉNÉRER CODE</button>
            </form>
        </div>

        <div id="tab-pio" class="tab-content">
            <form method="post" class="flex-row">
                <?php wp_nonce_field('nerv_gen_tester'); ?>
                <input type="hidden" name="generate_tester" value="1">
                <div style="flex:1;"><label class="input-label">Nombre</label><input type="number" name="nerv_tester_num" class="input-dogma" value="1" min="1" max="15"></div>
                <div style="flex:2;">
                    <label class="input-label">Durée</label>
                    <input type="hidden" name="nerv_tester_duration" id="realDur" value="604800">
                    <div class="custom-select-wrapper" data-type="dur">
                        <div class="input-dogma custom-select-trigger"><span>7 Jours (Max)</span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
                        <div class="custom-select-options">
                            <div class="custom-option" data-value="86400">1 Jour</div>
                            <div class="custom-option selected" data-value="604800">7 Jours</div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn-submit btn-action" style="flex:1;">INJECTER PIONNIERS</button>
            </form>
        </div>

        <div id="tab-reg" class="tab-content">
            <div class="acc-list">
                <?php foreach (array_reverse($access_codes, true) as $k => $d) : 
                    $role = is_array($d) ? ($d['role'] ?? 'DISCORD') : $d;
                    $is_used = is_array($d) && ($d['status'] ?? '') === 'used';
                ?>
                <div class="acc-item">
                    <div>
                        <div style="font-family:monospace; color:var(--ns-yellow); font-weight:800; font-size:16px;"><?php echo $k; ?></div>
                        <div style="font-size:11px; opacity:0.5; margin-top:4px;"><?php echo $role; ?> • <?php echo $is_used ? "UTILISÉ" : "ACTIF"; ?></div>
                    </div>
                    <form method="post" onsubmit="return confirm('Purger ce code définitivement ?');" style="margin:0;">
                        <input type="hidden" name="delete_code_key" value="<?php echo $k; ?>">
                        <button type="submit" name="delete_nerv_code" class="btn-submit btn-danger" style="height:36px; padding:0 16px; font-size:11px;">PURGER</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="terminal-section" style="z-index: 4;">
        <div class="input-label" style="color:var(--ns-yellow); font-size:14px; margin-bottom:24px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            BASE DE DONNÉES DU PERSONNEL (PILOTES & STAFF)
        </div>
        
        <div style="margin-bottom: 16px;">
            <input type="text" id="userSearchInput" class="input-dogma" placeholder="Rechercher par pseudo ou email..." style="background: rgba(255,255,255,0.02);">
        </div>

        <div class="filter-bar" id="roleFilters">
            <button class="btn-filter active" data-filter="all">TOUS</button>
            <button class="btn-filter" data-filter="magi">MAGI</button>
            <button class="btn-filter" data-filter="marduk">MARDUK</button>
            <button class="btn-filter" data-filter="pilote">PILOTES</button>
            <button class="btn-filter" data-filter="lilim">LILIM (VIP)</button>
            <button class="btn-filter" data-filter="membre">MEMBRES</button>
        </div>

        <div class="users-grid">
            <?php foreach ($all_users as $u) : 
                $u_roles = (array) $u->roles;
                $primary_role = !empty($u_roles) ? strtolower($u_roles[0]) : 'membre';
                
                $theme_class = 'card-theme-membre';
                if ($primary_role === 'magi' || $primary_role === 'administrator') $theme_class = 'card-theme-magi';
                elseif ($primary_role === 'marduk') $theme_class = 'card-theme-marduk';
                elseif ($primary_role === 'pilote') $theme_class = 'card-theme-pilote';
                
                $vip_end = get_user_meta($u->ID, 'nerv_premium_end_date', true);
                $is_vip = false;
                $vip_display = '';
                
                if ($vip_end && current_time('timestamp') < $vip_end) {
                    $is_vip = true;
                    $primary_role .= ' lilim';
                    if ($theme_class === 'card-theme-membre') $theme_class = 'card-theme-lilim';
                    
                    $time_left = $vip_end - current_time('timestamp');
                    $days_left = ceil($time_left / DAY_IN_SECONDS);
                    
                    if ($days_left > 1800) { 
                        $vip_display = 'ILLIMITÉ';
                    } else {
                        $vip_display = $days_left . ' J. RESTANTS';
                    }
                }
                
                $display_role = ucfirst(!empty($u_roles) ? $u_roles[0] : 'Membre');
                $avatar = get_user_meta($u->ID, 'nerv_avatar_url', true) ?: get_avatar_url($u->ID, ['size' => 150]);
                $banner = get_user_meta($u->ID, 'nerv_banner_url', true);
                $search_index = strtolower($u->user_login . ' ' . $u->user_email);
            ?>
            <div class="id-card <?php echo esc_attr($theme_class); ?>" data-role="<?php echo esc_attr($primary_role); ?>" data-search="<?php echo esc_attr($search_index); ?>">
                
                <!-- Background Bannière -->
                <?php if ($banner) : ?>
                    <div class="id-card-bg" style="background-image: url('<?php echo esc_url($banner); ?>');"></div>
                <?php endif; ?>
                <div class="id-card-gradient"></div>

                <div class="id-card-top">
                    <img src="<?php echo esc_url($avatar); ?>" class="id-avatar" loading="lazy" onerror="this.src='https://placehold.co/150x150/111/F4C95D?text=N'">
                    <div class="id-info">
                        <div class="id-pseudo <?php if($is_vip) echo 'is-vip'; ?>" title="<?php echo esc_attr($u->user_login); ?>">
                            <?php echo esc_html($u->user_login); ?>
                        </div>
                        <div class="id-email" title="<?php echo esc_attr($u->user_email); ?>"><?php echo esc_html($u->user_email); ?></div>
                        <div class="id-badges">
                            <span class="badge-role"><?php echo esc_html($display_role); ?></span>
                            <?php if ($is_vip) : ?>
                                <span class="badge-timer">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                    <?php echo $vip_display; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="id-card-hover">
                    <button type="button" class="btn-submit btn-edit edit-user-btn" style="flex:1; height:36px; font-size:10px; border-radius:6px; letter-spacing: 1px;" 
                        data-uid="<?php echo esc_attr($u->ID); ?>" 
                        data-login="<?php echo esc_attr($u->user_login); ?>" 
                        data-email="<?php echo esc_attr($u->user_email); ?>"
                        data-avatar="<?php echo esc_url($avatar); ?>"
                        data-banner="<?php echo esc_url($banner); ?>">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                        ÉDITER
                    </button>

                    <form method="post" onsubmit="return confirm('⚠️ DANGER : Êtes-vous sûr de vouloir PURGER DÉFINITIVEMENT cet utilisateur ?');" style="margin:0; flex:1;">
                        <?php wp_nonce_field('nerv_delete_user_action'); ?>
                        <input type="hidden" name="nerv_delete_user_id" value="<?php echo esc_attr($u->ID); ?>">
                        <?php if ($u->ID === get_current_user_id()) : ?>
                            <button type="button" class="btn-submit" style="width:100%; background:rgba(255,255,255,0.05); color:#555; cursor:not-allowed; height:36px; font-size:10px; border-radius:6px;" disabled>ACTUEL</button>
                        <?php else : ?>
                            <button type="submit" class="btn-submit btn-danger" style="width:100%; height:36px; font-size:10px; border-radius:6px; letter-spacing: 1px;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px;"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path></svg>
                                PURGER
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- MODAL D'ÉDITION UTILISATEUR AVEC BOUTONS D'UPLOAD WP MEDIA -->
<div class="nerv-modal-overlay" id="editUserModal">
    <div class="nerv-modal">
        <button class="nerv-modal-close" id="closeEditModal">&times;</button>
        <div class="input-label" style="color:var(--ns-yellow); font-size:14px; margin-bottom:24px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
            MODIFIER L'UTILISATEUR
        </div>

        <form method="post">
            <?php wp_nonce_field('nerv_edit_user_action'); ?>
            <input type="hidden" name="nerv_edit_user_id" id="editUserId" value="">
            
            <div class="flex-row" style="margin-bottom: 20px;">
                <div style="flex:1;">
                    <label class="input-label">Nouveau Pseudo</label>
                    <input type="text" name="nerv_edit_login" id="editUserLogin" class="input-dogma" required>
                </div>
                <div style="flex:1;">
                    <label class="input-label">Nouvel Email</label>
                    <input type="email" name="nerv_edit_email" id="editUserEmail" class="input-dogma" required>
                </div>
            </div>

            <!-- Ajout des boutons pour ouvrir la bibliothèque WordPress -->
            <div style="margin-bottom: 20px;">
                <label class="input-label">Avatar (PP)</label>
                <div style="display:flex; gap:10px;">
                    <input type="url" name="nerv_edit_avatar" id="editUserAvatar" class="input-dogma" style="flex:1;" placeholder="URL de l'image ou cliquer sur Parcourir">
                    <button type="button" class="btn-submit btn-edit js-media-upload" data-target="editUserAvatar" style="padding:0 20px;">PARCOURIR</button>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label class="input-label">Bannière de Profil</label>
                <div style="display:flex; gap:10px;">
                    <input type="url" name="nerv_edit_banner" id="editUserBanner" class="input-dogma" style="flex:1;" placeholder="URL de l'image ou cliquer sur Parcourir">
                    <button type="button" class="btn-submit btn-edit js-media-upload" data-target="editUserBanner" style="padding:0 20px;">PARCOURIR</button>
                </div>
            </div>

            <div style="margin-bottom: 30px;">
                <label class="input-label">Nouveau Mot de passe <span style="color:var(--ns-muted); text-transform:none; font-weight:500;">(Laisser vide pour ne pas modifier)</span></label>
                <input type="password" name="nerv_edit_pass" class="input-dogma" placeholder="••••••••">
            </div>

            <button type="submit" class="btn-submit btn-action" style="width: 100%;">SAUVEGARDER LES MODIFICATIONS</button>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

    // --- NOUVEAU : SYSTÈME D'UPLOAD WP MEDIA ---
    let mediaUploader;
    document.querySelectorAll('.js-media-upload').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const targetInputId = this.getAttribute('data-target');
            const targetInput = document.getElementById(targetInputId);
            
            if (mediaUploader) {
                mediaUploader.open();
                mediaUploader.targetInput = targetInput;
                return;
            }
            
            // Configuration de la fenêtre native WordPress
            mediaUploader = wp.media.frames.file_frame = wp.media({
                title: 'Sélectionner une image NERV',
                button: { text: 'Utiliser cette image' },
                multiple: false
            });
            
            // Lorsqu'une image est choisie
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                mediaUploader.targetInput.value = attachment.url;
            });
            
            mediaUploader.targetInput = targetInput;
            mediaUploader.open();
        });
    });

    // 1. Navigation des onglets
    document.querySelectorAll('.i-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.i-tab').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById(btn.dataset.target).classList.add('active');
        });
    });

    // 2. Sélecteurs Custom
    document.querySelectorAll('.custom-select-wrapper').forEach(w => {
        const t = w.querySelector('.custom-select-trigger');
        const o = w.querySelector('.custom-select-options');
        
        let targetInputId = 'realDur';
        if (w.dataset.type === 'vip') targetInputId = 'realVipInput';
        if (w.dataset.type === 'role') targetInputId = 'realRoleInput';
        if (w.dataset.type === 'create_role') targetInputId = 'realRoleCreateInput';
        
        const input = document.getElementById(targetInputId);
        
        t.addEventListener('click', (e) => { 
            e.stopPropagation(); 
            document.querySelectorAll('.custom-select-options').forEach(p => { if(p !== o) p.style.display = 'none'; });
            o.style.display = o.style.display === 'block' ? 'none' : 'block'; 
        });
        
        o.querySelectorAll('.custom-option').forEach(opt => {
            opt.addEventListener('click', () => {
                o.querySelectorAll('.custom-option').forEach(x => x.classList.remove('selected'));
                opt.classList.add('selected');
                t.querySelector('span').textContent = opt.textContent;
                input.value = opt.dataset.value;
                o.style.display = 'none';
            });
        });
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('.custom-select-options').forEach(p => p.style.display = 'none');
    });

    // 3. AJAX VIP
    const handleVip = async (action) => {
        const form = document.getElementById('formVip');
        const fd = new FormData(form);
        fd.append('action', action);
        const res = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: fd });
        const json = await res.json();
        alert(json.success ? "OPÉRATION TERMINÉE" : "ERREUR : " + json.data);
        if(json.success) location.reload();
    };

    const btnGrant = document.getElementById('btnGrant');
    const btnRemove = document.getElementById('btnRemove');
    if(btnGrant) btnGrant.addEventListener('click', (e) => { e.preventDefault(); handleVip('nerv_grant_premium_manual'); });
    if(btnRemove) btnRemove.addEventListener('click', (e) => { e.preventDefault(); if(confirm('Révoquer cet accès VIP ?')) handleVip('nerv_remove_premium_manual'); });

    // 4. Filtres et Recherche en temps réel
    const searchInput = document.getElementById('userSearchInput');
    const filterBtns = document.querySelectorAll('.btn-filter');
    const userCards = document.querySelectorAll('.id-card');
    let currentFilter = 'all';
    let currentSearch = '';

    const filterCards = () => {
        userCards.forEach(card => {
            const matchesFilter = currentFilter === 'all' || card.dataset.role.includes(currentFilter);
            const matchesSearch = card.dataset.search.includes(currentSearch);
            
            if (matchesFilter && matchesSearch) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    };

    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            currentSearch = e.target.value.toLowerCase();
            filterCards();
        });
    }

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentFilter = btn.dataset.filter;
            filterCards();
        });
    });

    // 5. Modal d'Édition Utilisateur
    const editModal = document.getElementById('editUserModal');
    const closeEditModalBtn = document.getElementById('closeEditModal');
    
    const editUserIdInput = document.getElementById('editUserId');
    const editUserLoginInput = document.getElementById('editUserLogin');
    const editUserEmailInput = document.getElementById('editUserEmail');
    const editUserAvatarInput = document.getElementById('editUserAvatar');
    const editUserBannerInput = document.getElementById('editUserBanner');

    document.querySelectorAll('.edit-user-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            editUserIdInput.value = this.dataset.uid;
            editUserLoginInput.value = this.dataset.login;
            editUserEmailInput.value = this.dataset.email;
            editUserAvatarInput.value = this.dataset.avatar !== 'false' ? this.dataset.avatar : '';
            editUserBannerInput.value = this.dataset.banner !== 'false' ? this.dataset.banner : '';
            
            editModal.classList.add('active');
        });
    });

    closeEditModalBtn.addEventListener('click', () => {
        editModal.classList.remove('active');
    });

    editModal.addEventListener('click', (e) => {
        if (e.target === editModal) editModal.classList.remove('active');
    });
});
</script>

<?php get_footer(); ?>