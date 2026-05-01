<?php
/**
 * NERV SYSTEM - CATALOGUE CENTRAL
 * Version : 2.0 — Filtres avancés & UI Système
 */

if (!defined('ABSPATH')) exit;

// ============================================================
// SUPPRESSION TOTALE DU FOND ET DES ÉLÉMENTS MADARA
// ============================================================
remove_theme_support('custom-background');
remove_action('wp_head', '_custom_background_cb');

add_action('wp_head', function() {
    echo '<style id="nerv-bg-override">
        html, body, body.custom-background, #page, .wrap, .body-wrap,
        .site-content, #wrapper_bg, .c-page-content, .c-page,
        .site-content > .container {
            background-color: #000000 !important;
            background-image: none !important;
        }
        .item-title, .item-title.h2, h1.item-title, h2.item-title, .page-title,
        .post-title, .entry-title, h1.entry-title, h2.entry-title, .entry-header,
        .entry-header .entry-title, .page-header, .page-header .entry-title,
        .c-blog-post__content > .item-title, .c-page-content .item-title,
        .c-page-content .entry-title, .entry-title + hr, .entry-header + hr,
        .entry-header::after { display: none !important; }
        .post-on, .posted-on, .post-meta, .entry-meta, .c-blog-post__meta,
        .c-page-content .post-on, .c-page-content .posted-on, .c-entry-meta,
        [class*="post-on"], [class*="posted-on"] { display: none !important; }
        .c-page-content > hr, .c-page-content > .post-on + hr,
        .c-page-content > .item-title + hr { display: none !important; }
        .c-page-content .c-blog-post__top, .c-blog-post__header,
        .post-header { display: none !important; }
        .c-page__content { display: none !important; }
        .c-page-content > hr, .c-page-content hr, .site-content hr,
        .c-blog-post hr, .c-page hr, .entry-content > hr, body > #page hr:not(.nerv-own),
        .c-blog-post__content hr, .c-page-content::before, .c-page-content::after,
        .c-blog-post__content::before, .c-blog-post__content::after { display: none !important; border: none !important; }
        .c-page-content, .c-blog-post, .c-blog-post__content {
            border-top: none !important; border-bottom: none !important;
            padding-top: 0 !important; margin-top: 0 !important;
        }
    </style>';
}, 1);

get_header();

// ==========================================
// RÉCUPÉRATION DES PARAMÈTRES DE RECHERCHE
// ==========================================
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
$search_query  = isset($_GET['s_keyword']) ? sanitize_text_field($_GET['s_keyword']) : '';
$search_genres = isset($_GET['s_genres']) && is_array($_GET['s_genres']) ? array_map('sanitize_text_field', $_GET['s_genres']) : [];
$search_status = isset($_GET['s_status']) ? sanitize_text_field($_GET['s_status']) : '';
$search_order  = isset($_GET['s_order']) ? sanitize_text_field($_GET['s_order']) : 'latest';

// ==========================================
// CONSTRUCTION DE LA REQUÊTE WP_QUERY
// ==========================================
$args = [
    'post_type'      => 'wp-manga',
    'post_status'    => 'publish',
    'posts_per_page' => 24,
    'paged'          => $paged,
    'post__not_in'   => [1477],
];

// Recherche par texte
if (!empty($search_query)) {
    $args['s'] = $search_query;
}

// Tri (Ordre)
if ($search_order === 'az') {
    $args['orderby'] = 'title';
    $args['order']   = 'ASC';
} elseif ($search_order === 'za') {
    $args['orderby'] = 'title';
    $args['order']   = 'DESC';
} else {
    // Par défaut : Dernières mises à jour
    $args['orderby']  = 'meta_value_num';
    $args['meta_key'] = '_latest_update';
    $args['order']    = 'DESC';
}

// Filtre par statut (En cours / Terminé)
if (!empty($search_status)) {
    $args['meta_query'] = [
        [
            'key'     => '_wp_manga_status',
            'value'   => $search_status,
            'compare' => 'LIKE'
        ]
    ];
}

// Filtre par genres (Multi-sélection)
if (!empty($search_genres)) {
    $args['tax_query'] = [
        [
            'taxonomy' => 'wp-manga-genre',
            'field'    => 'slug',
            'terms'    => $search_genres,
            'operator' => 'IN'
        ]
    ];
}

$manga_query = new WP_Query($args);

if (!function_exists('nerv_get_terms_list')) {
    function nerv_get_terms_list($post_id, $taxonomy, $limit = 3) {
        $terms = get_the_terms($post_id, $taxonomy);
        if (!$terms || is_wp_error($terms)) return [];
        $names = [];
        foreach ($terms as $term) {
            $names[] = $term->name;
            if (count($names) >= $limit) break;
        }
        return $names;
    }
}
?>

<div id="nerv-kill-cover" style="display:block; position:relative; background:#000; z-index:999; margin-top:-600px; padding-top:600px; pointer-events:none;"></div>

<script>
(function killMadara() {
    var bg = [document.documentElement, document.body];
    ['#page','#wrapper_bg','.wrap','.body-wrap','.site-content'].forEach(function(s){
        var el = document.querySelector(s); if(el) bg.push(el);
    });
    bg.forEach(function(el){
        if(!el) return;
        el.style.setProperty('background-color','#000000','important');
        el.style.setProperty('background-image','none','important');
    });
    function hideJunk() {
        var selectors = ['.item-title', 'h1.item-title', 'h2.item-title', '.post-on', '.posted-on', '.post-meta', '.entry-meta', '.c-blog-post__meta', '.c-blog-post__header', '.post-header', '.c-blog-post__top', '.c-entry-meta'];
        selectors.forEach(function(s) {
            document.querySelectorAll(s).forEach(function(el) { el.style.setProperty('display', 'none', 'important'); });
        });
    }
    hideJunk(); document.addEventListener('DOMContentLoaded', hideJunk);
    new MutationObserver(function() {
        bg.forEach(function(el){ if(el && el.style.backgroundImage && el.style.backgroundImage !== 'none') el.style.setProperty('background-image','none','important'); });
        hideJunk();
    }).observe(document.documentElement, { childList:true, subtree:true, attributes:true, attributeFilter:['style','class'] });
})();

// Toggle pour le panneau des filtres avancés
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('nerv-toggle-filters');
    const panel = document.getElementById('nerv-filters-panel');
    if(btn && panel) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            panel.classList.toggle('active');
            btn.classList.toggle('active');
        });
    }
});
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,700;0,900;1,700;1,900&family=Inter:wght@400;500;700;900&display=swap');

:root {
    --cd-bg: #000000;
    --cd-panel: #0a0a0a;
    --cd-panel-light: #151515;
    --cd-stroke: rgba(255,255,255,0.15);
    --cd-yellow: #F4C95D;
    --cd-yellow-dim: rgba(244,201,93,0.15);
    --cd-muted: #888888;
}

.nerv-cat-wrap {
    max-width: 1300px; margin: 0 auto 100px; padding: 40px 20px;
    background-color: var(--cd-bg);
    background-image: radial-gradient(rgba(244, 201, 93, 0.04) 1px, transparent 1px);
    background-size: 30px 30px; position: relative; z-index: 10;
    font-family: 'Inter', sans-serif; color: #fff; min-height: 100vh;
}

.nerv-cat-header {
    margin-bottom: 40px; border-bottom: 1px solid var(--cd-stroke); padding-bottom: 20px;
}
.nerv-cat-title {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 48px; font-weight: 900; font-style: italic;
    text-transform: uppercase; color: #fff; margin: 0 0 10px; letter-spacing: -0.02em;
}
.nerv-cat-title span { color: var(--cd-yellow); }

/* --- RECHERCHE & FILTRES SYSTEM --- */
.nerv-search-form {
    background: var(--cd-panel); border: 1px solid var(--cd-stroke);
    border-radius: 8px; margin-bottom: 40px; overflow: hidden;
}

.nerv-search-main {
    display: flex; padding: 15px; gap: 15px; align-items: center;
}
.nerv-input-group { flex-grow: 1; position: relative; }
.nerv-search-input {
    width: 100%; padding: 14px 20px; background: #000;
    border: 1px solid var(--cd-stroke); color: #fff;
    border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 15px;
    box-sizing: border-box; transition: 0.3s;
}
.nerv-search-input:focus { outline: none; border-color: var(--cd-yellow); box-shadow: 0 0 10px var(--cd-yellow-dim); }

.nerv-btn {
    padding: 0 24px; height: 50px; font-family: 'Inter', sans-serif;
    font-weight: 700; text-transform: uppercase; border-radius: 6px;
    cursor: pointer; transition: all 0.3s ease; display: flex;
    align-items: center; justify-content: center; font-size: 14px;
}
.nerv-btn-primary {
    background: var(--cd-yellow); color: #000; border: none;
}
.nerv-btn-primary:hover { background: #fff; box-shadow: 0 0 15px rgba(244,201,93,0.4); }

.nerv-btn-secondary {
    background: transparent; color: #fff; border: 1px solid var(--cd-stroke);
}
.nerv-btn-secondary:hover, .nerv-btn-secondary.active {
    background: var(--cd-panel-light); border-color: var(--cd-yellow); color: var(--cd-yellow);
}

/* Panneau déroulant */
.nerv-filters-panel {
    display: none; padding: 0 20px 20px; border-top: 1px dashed var(--cd-stroke);
    background: var(--cd-panel);
}
.nerv-filters-panel.active { display: block; animation: slideDown 0.3s ease-out; }
@keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

.filter-group { margin-top: 20px; }
.filter-title {
    font-family: 'Barlow Condensed', sans-serif; font-size: 16px; font-weight: 700;
    color: var(--cd-muted); text-transform: uppercase; margin-bottom: 12px; letter-spacing: 1px;
}

/* Radio buttons (Statut / Ordre) */
.nerv-radio-group { display: flex; gap: 10px; flex-wrap: wrap; }
.nerv-radio-pill { position: relative; }
.nerv-radio-pill input { display: none; }
.nerv-radio-pill label {
    display: block; padding: 8px 16px; background: #000; border: 1px solid var(--cd-stroke);
    border-radius: 4px; color: #aaa; font-size: 13px; font-weight: 500; cursor: pointer; transition: 0.2s;
}
.nerv-radio-pill input:checked + label {
    border-color: var(--cd-yellow); color: var(--cd-yellow); background: var(--cd-yellow-dim);
}

/* Checkboxes (Genres) */
.nerv-checkbox-group { display: flex; gap: 8px; flex-wrap: wrap; }
.nerv-checkbox-pill { position: relative; }
.nerv-checkbox-pill input { display: none; }
.nerv-checkbox-pill label {
    display: block; padding: 6px 12px; background: #000; border: 1px dashed var(--cd-stroke);
    border-radius: 4px; color: #888; font-size: 12px; font-weight: 500; cursor: pointer; transition: 0.2s;
}
.nerv-checkbox-pill label:hover { border-color: #fff; color: #fff; }
.nerv-checkbox-pill input:checked + label {
    border-style: solid; border-color: var(--cd-yellow); color: #000; background: var(--cd-yellow); font-weight: 700;
}

@media (max-width: 767px) {
    .nerv-search-main { flex-direction: column; align-items: stretch; }
    .nerv-btn { width: 100%; }
}

/* --- GRILLE --- */
.nerv-cat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }

/* Cartes (Restées fidèles à ta DA) */
.manga-card {
    background: var(--cd-panel); border: 1px solid var(--cd-stroke); border-radius: 8px;
    overflow: hidden; transition: all 0.3s ease; display: flex; text-decoration: none; color: #fff; height: 140px;
}
.manga-card:hover { transform: translateY(-4px); border-color: rgba(244,201,93,0.5); box-shadow: 0 8px 25px rgba(0,0,0,0.6); }
.mc-cover {
    width: 95px; min-width: 95px; height: 100%; background-size: cover; background-position: center;
    position: relative; border-right: 1px solid var(--cd-stroke);
}
.mc-cover::before { content: ''; position: absolute; inset: 0; background: linear-gradient(180deg, transparent 60%, rgba(0,0,0,0.8) 100%); }
.mc-cover-badge {
    position: absolute; bottom: 5px; left: 5px; right: 5px; text-align: center; background: rgba(0,0,0,0.8);
    backdrop-filter: blur(2px); border: 1px solid rgba(255,255,255,0.1); color: #fff; font-size: 9px; font-weight: 800; padding: 2px 0; border-radius: 4px;
}
.mc-info { padding: 12px 15px; display: flex; flex-direction: column; flex-grow: 1; overflow: hidden; }
.mc-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 8px; }
.mc-title {
    font-size: 15px; font-weight: 800; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2;
    -webkit-box-orient: vertical; overflow: hidden; color: #fff; margin: 0;
}
.mc-status {
    background: var(--cd-yellow); color: #000; font-size: 9px; font-weight: 900;
    text-transform: uppercase; padding: 2px 6px; border-radius: 4px; white-space: nowrap;
}
.mc-status.end { background: #1a1a1a; color: #888; border: 1px solid #333; }
.mc-subinfo { font-size: 11px; color: var(--cd-muted); margin-bottom: 10px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mc-subinfo span { color: #aaa; }
.mc-divider { height: 1px; background: linear-gradient(90deg, var(--cd-stroke) 0%, transparent 100%); margin: auto 0 8px 0; }
.mc-genres { display: flex; gap: 6px; overflow: hidden; white-space: nowrap; }
.genre-tag { background: transparent; border: 1px solid rgba(255,255,255,0.1); color: #888; font-size: 9px; font-weight: 700; text-transform: uppercase; padding: 3px 6px; border-radius: 3px; flex-shrink: 0; }

/* Pagination */
.nerv-pagination { margin-top: 50px; display: flex; justify-content: center; gap: 10px; }
.page-numbers {
    display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 8px;
    background: var(--cd-panel); border: 1px solid var(--cd-stroke); color: #fff; text-decoration: none; font-weight: 700; transition: 0.2s;
}
.page-numbers.current { background: var(--cd-yellow); color: #000; border-color: var(--cd-yellow); }
.page-numbers:hover:not(.current) { background: rgba(255,255,255,0.05); }
</style>

<div class="nerv-cat-wrap">
    <div class="nerv-cat-header">
        <h1 class="nerv-cat-title">CATALOGUE <span>D'ARCHIVES</span></h1>
        <p style="color:var(--cd-muted); font-size:14px;">Accès à l'ensemble des dossiers classifiés.</p>
    </div>

    <form method="GET" action="" class="nerv-search-form">
        <div class="nerv-search-main">
            <div class="nerv-input-group">
                <input type="text" name="s_keyword" class="nerv-search-input" placeholder="Titre, mot-clé..." value="<?php echo esc_attr($search_query); ?>">
            </div>
            <button type="button" id="nerv-toggle-filters" class="nerv-btn nerv-btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:8px;"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                Filtres
            </button>
            <button type="submit" class="nerv-btn nerv-btn-primary">Exécuter</button>
        </div>

        <div id="nerv-filters-panel" class="nerv-filters-panel <?php echo (!empty($search_genres) || !empty($search_status) || $search_order !== 'latest') ? 'active' : ''; ?>">
            
            <div style="display:flex; flex-wrap:wrap; gap:30px;">
                <div class="filter-group">
                    <div class="filter-title">Statut de parution</div>
                    <div class="nerv-radio-group">
                        <div class="nerv-radio-pill">
                            <input type="radio" id="st_all" name="s_status" value="" <?php checked($search_status, ''); ?>>
                            <label for="st_all">Tous</label>
                        </div>
                        <div class="nerv-radio-pill">
                            <input type="radio" id="st_ongoing" name="s_status" value="on-going" <?php checked(strpos($search_status, 'on-going') !== false); ?>>
                            <label for="st_ongoing">En cours</label>
                        </div>
                        <div class="nerv-radio-pill">
                            <input type="radio" id="st_completed" name="s_status" value="completed" <?php checked(strpos($search_status, 'completed') !== false); ?>>
                            <label for="st_completed">Terminé</label>
                        </div>
                    </div>
                </div>

                <div class="filter-group">
                    <div class="filter-title">Classification</div>
                    <div class="nerv-radio-group">
                        <div class="nerv-radio-pill">
                            <input type="radio" id="ord_latest" name="s_order" value="latest" <?php checked($search_order, 'latest'); ?>>
                            <label for="ord_latest">Dernières M.A.J</label>
                        </div>
                        <div class="nerv-radio-pill">
                            <input type="radio" id="ord_az" name="s_order" value="az" <?php checked($search_order, 'az'); ?>>
                            <label for="ord_az">A - Z</label>
                        </div>
                        <div class="nerv-radio-pill">
                            <input type="radio" id="ord_za" name="s_order" value="za" <?php checked($search_order, 'za'); ?>>
                            <label for="ord_za">Z - A</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="filter-group">
                <div class="filter-title">Catégories (Genres)</div>
                <div class="nerv-checkbox-group">
                    <?php
                    $all_genres = get_terms(['taxonomy' => 'wp-manga-genre', 'hide_empty' => true]);
                    if (!is_wp_error($all_genres) && !empty($all_genres)) {
                        foreach ($all_genres as $g) {
                            $is_checked = in_array($g->slug, $search_genres) ? 'checked' : '';
                            echo '<div class="nerv-checkbox-pill">';
                            echo '<input type="checkbox" id="genre_' . esc_attr($g->slug) . '" name="s_genres[]" value="' . esc_attr($g->slug) . '" ' . $is_checked . '>';
                            echo '<label for="genre_' . esc_attr($g->slug) . '">' . esc_html($g->name) . '</label>';
                            echo '</div>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </form>

    <?php if ($manga_query->have_posts()) : ?>
        <div class="nerv-cat-grid">
            <?php while ($manga_query->have_posts()) : $manga_query->the_post();
                $manga_id     = get_the_ID();
                $title        = get_the_title();
                $link         = get_permalink();
                $cover        = get_the_post_thumbnail_url($manga_id, 'medium') ?: get_post_meta($manga_id, '_thumbnail_ext_url', true) ?: 'https://placehold.co/400x600/101010/F4C95D?text=NERV';
                $status_raw   = get_post_meta($manga_id, '_wp_manga_status', true);
                $status_clean = strtolower($status_raw);
                $is_end       = ($status_clean == 'completed' || $status_clean == 'end' || strpos($status_clean, 'termin') !== false);
                $status_text  = $is_end ? 'Terminé' : 'En cours';
                $status_class = $is_end ? 'end' : '';
                $genres       = nerv_get_terms_list($manga_id, 'wp-manga-genre', 3);
                $authors      = nerv_get_terms_list($manga_id, 'wp-manga-author', 1);
                $releases     = nerv_get_terms_list($manga_id, 'wp-manga-release', 1);
                $year         = !empty($releases) ? $releases[0] : '';
                $author_name  = !empty($authors) ? $authors[0] : 'Inconnu';
            ?>
                <a href="<?php echo esc_url($link); ?>" class="manga-card">
                    <div class="mc-cover" style="background-image: url('<?php echo esc_url($cover); ?>');">
                        <?php if ($year) : ?><div class="mc-cover-badge"><?php echo esc_html($year); ?></div><?php endif; ?>
                    </div>
                    <div class="mc-info">
                        <div class="mc-header">
                            <h3 class="mc-title"><?php echo esc_html($title); ?></h3>
                            <span class="mc-status <?php echo $status_class; ?>"><?php echo esc_html($status_text); ?></span>
                        </div>
                        <div class="mc-subinfo">Par <span><?php echo esc_html($author_name); ?></span></div>
                        <div class="mc-divider"></div>
                        <div class="mc-genres">
                            <?php foreach ($genres as $genre) : ?>
                                <span class="genre-tag"><?php echo esc_html($genre); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>

        <div class="nerv-pagination">
            <?php echo paginate_links(['total' => $manga_query->max_num_pages, 'current' => $paged, 'prev_text' => '←', 'next_text' => '→']); ?>
        </div>
        <?php wp_reset_postdata(); ?>
    <?php else : ?>
        <div style="padding: 100px 20px; text-align: center; border: 1px dashed var(--cd-stroke); border-radius: 12px; color: var(--cd-muted);">
            [ SYSTÈME ] Aucun dossier classifié ne correspond à vos filtres.
        </div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>