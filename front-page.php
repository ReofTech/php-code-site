<?php
/**
 * Front Page — NERV Scans
 * Version V_SUPREME_FINAL : Slider Actif + Lang System + New Badge
 */

if (!defined('ABSPATH')) exit;
get_header();

$current_user   = wp_get_current_user();
$is_logged_in   = is_user_logged_in();
$current_roles  = $is_logged_in ? (array) $current_user->roles : [];

$is_admin   = current_user_can('manage_options') || in_array('nervmodo', $current_roles) || in_array('magi', $current_roles);
$user_has_access = function_exists('nerv_user_is_premium') ? nerv_user_is_premium() : false;
if ($is_admin) { $user_has_access = true; }

$hidden_test_id = 1477;

/** FONCTIONS DE RÉCUPÉRATION (IMAGES & DATA) */
if (!function_exists('nerv_fp_cover')) {
    function nerv_fp_cover($post_id) {
        if (has_post_thumbnail($post_id)) {
            $img = get_the_post_thumbnail_url($post_id, 'full'); 
            if ($img) return $img;
        }
        $thumb = get_post_meta($post_id, '_thumbnail_ext_url', true);
        if (!empty($thumb)) return $thumb;
        return 'https://placehold.co/800x1200/101010/F4C95D?text=NERV+COVER';
    }
}

if (!function_exists('nerv_fp_banner')) {
    function nerv_fp_banner($post_id) {
        $banner_keys = ['manga_banner', '_manga_banner', 'manga_title_bg', '_manga_title_bg'];
        foreach ($banner_keys as $key) {
            $banner = get_post_meta($post_id, $key, true);
            if (!empty($banner)) {
                if (is_numeric($banner)) {
                    $banner_url = wp_get_attachment_url($banner);
                    if ($banner_url) return $banner_url;
                } else {
                    return $banner;
                }
            }
        }
        return nerv_fp_cover($post_id);
    }
}

if (!function_exists('nerv_fp_excerpt')) {
    function nerv_fp_excerpt($post_id, $words = 20) {
        $excerpt = get_post_field('post_excerpt', $post_id);
        if (!empty($excerpt)) return wp_trim_words(wp_strip_all_tags($excerpt), $words, '...');
        return wp_trim_words(wp_strip_all_tags(get_post_field('post_content', $post_id)), $words, '...');
    }
}

if (!function_exists('nerv_fp_latest_chapters')) {
    function nerv_fp_latest_chapters($post_id, $limit = 3, $has_access = false) {
        global $wp_manga_functions;
        if (!class_exists('WP_Manga') || !isset($wp_manga_functions)) return [];
        $chapters = $wp_manga_functions->get_latest_chapters($post_id, null, $limit, 0);
        if (!$chapters || !is_array($chapters)) return [];

        $out = [];
        foreach ($chapters as $chapter) {
            $slug = $chapter['chapter_slug'] ?? '';
            $name = $chapter['chapter_name'] ?? $slug;
            $raw_date = $chapter['date'] ?? '';
            $chapter_id = $chapter['chapter_id'] ?? 0;
            if (!$slug) continue;

            $clean_date = $raw_date;
            $is_premium = false; $is_locked = false; $is_new = false;
            
            if ($raw_date) {
                $ts = strtotime($raw_date);
                if ($ts) {
                    $diff = human_time_diff($ts, current_time('timestamp'));
                    $clean_date = str_replace(['mins', 'hours', 'days', 'months', 'years'], ['m', 'h', 'j', 'mo', 'a'], $diff);
                    if ((current_time('timestamp') - $ts) < 86400) { $is_new = true; }
                }
            }

            $custom_unlock = get_post_meta($post_id, '_chapter_unlock_' . $chapter_id, true);
            if (!empty($custom_unlock)) {
                $unlock_time = strtotime($custom_unlock);
                if ($unlock_time && current_time('timestamp') < $unlock_time) {
                    $is_premium = true;
                    if (!$has_access) $is_locked = true;
                }
            }

            // LE FIX DU SYSTÈME DE LANGUE EST ICI
            $lang_code = strtoupper(trim((string) get_post_meta($post_id, '_chapter_lang_' . $chapter_id, true))) ?: 'VF';

            $out[] = [
                'url'         => $wp_manga_functions->build_chapter_url($post_id, $slug),
                'name'        => str_replace(['Chapter ', 'Chapitre '], ['CH.', 'CH.'], $name ? $name : $slug),
                'date'        => $clean_date,
                'is_premium'  => $is_premium,
                'is_locked'   => $is_locked,
                'is_new'      => $is_new,
                'lang'        => $lang_code,
            ];
        }
        return $out;
    }
}

$hero_query = new WP_Query([
    'post_type' => 'wp-manga', 'posts_per_page' => 5, 'post_status' => 'publish',
    'orderby' => 'modified', 'order' => 'DESC', 'post__not_in' => [$hidden_test_id],
]);

$latest_release_query = new WP_Query([
    'post_type' => 'wp-manga', 'posts_per_page' => 12, 'post_status' => 'publish',
    'orderby' => 'modified', 'order' => 'DESC', 'post__not_in' => [$hidden_test_id],
]);
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,600;0,700;0,800;1,700;1,800;1,900&family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;700&display=swap');

:root{ 
    --ns-bg: #000000; 
    --ns-panel: #050505; 
    --ns-border: rgba(255,255,255,0.08); 
    --ns-y: #F4C95D; 
    --ns-txt: #ffffff; 
    --ns-muted: #888888; 
}

body { background-color: var(--ns-bg); color: var(--ns-txt); font-family: 'Inter', sans-serif; overflow-x: hidden; }
.supreme-wrapper { background-image: radial-gradient(rgba(244, 201, 93, 0.05) 1px, transparent 1px); background-size: 40px 40px; background-position: center top; padding-bottom: 20px; min-height: 100vh; position: relative; }
.supreme-wrapper::before { content: " "; display: block; position: absolute; top: 0; left: 0; bottom: 0; right: 0; background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.25) 50%), linear-gradient(90deg, rgba(255, 0, 0, 0.06), rgba(0, 255, 0, 0.02), rgba(0, 0, 255, 0.06)); z-index: 999; background-size: 100% 2px, 3px 100%; pointer-events: none; opacity: 0.3; }
.supreme-container { max-width: 1440px; margin: 0 auto; padding: 0 24px; position: relative; z-index: 10; }

@keyframes magiScan { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
.ns-skeleton { background: linear-gradient(90deg, #0a0a0a 25%, rgba(244, 201, 93, 0.1) 50%, #0a0a0a 75%); background-size: 200% 100%; animation: magiScan 2.5s infinite linear; }

.sys-ticker { display: flex; align-items: center; gap: 16px; padding: 24px 0; border-bottom: 1px solid var(--ns-border); margin-bottom: 40px; }
.sys-ticker-badge { background: rgba(255,255,255,0.05); color: #fff; padding: 6px 12px; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 11px; font-weight: 800; border: 1px solid rgba(255,255,255,0.1); text-transform: uppercase; letter-spacing: 0.1em; }
.sys-ticker-text { font-family: 'JetBrains Mono', monospace; font-size: 11px; color: var(--ns-y); text-transform: uppercase; letter-spacing: 0.1em; border-right: 2px solid var(--ns-y); white-space: nowrap; overflow: hidden; animation: blink 1s step-end infinite; }
@keyframes blink { 50% { border-color: transparent; } }

.hero-slider-wrap { position: relative; width: 100%; min-height: 70vh; border-radius: 32px; background: #000; box-shadow: 0 40px 100px rgba(0,0,0,0.8), inset 0 0 0 1px var(--ns-border); margin-bottom: 80px; overflow: hidden; }
.hero-slide { position: absolute; inset: 0; width: 100%; height: 100%; display: flex; align-items: flex-end; opacity: 0; visibility: hidden; transition: opacity 0.8s ease-in-out, visibility 0.8s; }
.hero-slide.is-active { opacity: 1; visibility: visible; z-index: 2; }
.hero-bg { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: center 25%; transform: scale(1.05); transition: transform 12s ease-out; }
.hero-slide.is-active .hero-bg { transform: scale(1); }
.hero-gradient { position: absolute; inset: 0; background: linear-gradient(to right, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.3) 60%, transparent 100%), linear-gradient(to top, rgba(0,0,0,0.8) 0%, transparent 40%); pointer-events: none; }
.hero-glass-panel { position: relative; z-index: 10; margin: 40px; padding: 48px; max-width: 650px; background: rgba(10, 10, 10, 0.4); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.1); border-radius: 32px 32px 32px 8px; border-left: 4px solid var(--ns-y); box-shadow: 0 30px 60px rgba(0,0,0,0.6); transition: transform 0.6s ease-out, opacity 0.6s ease; }
.hero-slide:not(.is-active) .hero-glass-panel { opacity: 0; transform: translateY(20px); }
.hero-slide.is-active .hero-glass-panel { opacity: 1; transform: translateY(0); }

.hero-tag { font-family: 'JetBrains Mono', monospace; color: var(--ns-y); font-size: 12px; margin-bottom: 16px; display: block; letter-spacing: 0.1em; }
.hero-title { font-family: 'Barlow Condensed', sans-serif; font-size: clamp(48px, 6vw, 80px); font-weight: 900; font-style: italic; text-transform: uppercase; line-height: 0.9; margin: 0 0 20px; color: #fff; text-shadow: 0 10px 30px rgba(0,0,0,0.5); letter-spacing: -0.02em; }
.hero-desc { font-family: 'Inter', sans-serif; font-size: 16px; color: rgba(255,255,255,0.8); line-height: 1.6; margin-bottom: 32px; font-weight: 500; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
.hero-btn { display: inline-flex; align-items: center; justify-content: center; gap: 12px; padding: 18px 36px; background: var(--ns-y); color: #000; font-size: 14px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; border-radius: 14px; text-decoration: none; transition: 0.3s; box-shadow: 0 10px 30px rgba(244,201,93,0.2); }
.hero-btn:hover { background: #fff; transform: translateY(-4px); box-shadow: 0 20px 50px rgba(244,201,93,0.5); }

.hero-controls { position: absolute; bottom: 40px; right: 40px; z-index: 20; display: flex; gap: 12px; }
.hero-dot { width: 12px; height: 12px; border-radius: 6px; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.1); cursor: pointer; transition: 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
.hero-dot.is-active { width: 32px; background: var(--ns-y); border-color: var(--ns-y); box-shadow: 0 0 15px rgba(244,201,93,0.5); }

.section-header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 50px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 16px; }
.section-title { font-family: 'Barlow Condensed', sans-serif; font-size: 42px; font-weight: 900; font-style: italic; text-transform: uppercase; margin: 0; line-height: 1; }
.title-muted { color: rgba(255,255,255,0.3); } .title-highlight { color: var(--ns-y); }
.section-subtitle { font-family: 'JetBrains Mono', monospace; font-size: 11px; color: var(--ns-y); text-transform: uppercase; letter-spacing: 0.1em; font-weight: 700; }

.supreme-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 40px 24px; }

.breakout-card { position: relative; background: linear-gradient(145deg, #0a0a0a, #030303); border: 1px solid var(--ns-border); border-radius: 24px; padding: 24px 24px 24px 130px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); display: flex; flex-direction: column; justify-content: center; min-height: 180px; transition: transform 0.3s ease-out, border-color 0.4s, box-shadow 0.4s; }
.breakout-card:hover { transform: translateY(-8px); border-color: rgba(244,201,93,0.4); box-shadow: 0 30px 60px rgba(0,0,0,0.9), 0 0 30px rgba(244,201,93,0.05); }

.bc-cover-wrap { position: absolute; top: -20px; left: 16px; width: 100px; height: 150px; border-radius: 12px; overflow: hidden; box-shadow: 10px 10px 30px rgba(0,0,0,0.9); border: 1px solid rgba(255,255,255,0.1); transition: 0.4s; z-index: 2; }
.bc-cover-wrap img { width: 100%; height: 100%; object-fit: cover; object-position: top center; transition: 0.5s; display: block; }
.breakout-card:hover .bc-cover-wrap { transform: translateY(-5px) scale(1.05); border-color: rgba(244,201,93,0.5); }
.breakout-card:hover .bc-cover-wrap img { filter: brightness(1.1); }

.bc-meta { margin-bottom: 12px; }
.bc-id { font-family: 'JetBrains Mono', monospace; font-size: 9px; color: var(--ns-muted); display: block; margin-bottom: 4px; }
.bc-title { font-family: 'Barlow Condensed', sans-serif; font-size: 22px; font-weight: 800; color: #fff; text-transform: uppercase; text-decoration: none; line-height: 1.1; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; transition: 0.2s; }
.breakout-card:hover .bc-title { color: var(--ns-y); }

.bc-logs { display: flex; flex-direction: column; gap: 8px; }
.log-pill { display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.04); padding: 8px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; color: #fff; text-decoration: none; transition: 0.2s; }
.log-pill:hover { background: rgba(255,255,255,0.06); transform: translateX(6px); border-color: rgba(255,255,255,0.1); }
.log-pill.is-premium { background: rgba(244,201,93,0.05); border-color: rgba(244,201,93,0.2); color: var(--ns-y); }
.log-pill.is-premium:hover { background: rgba(244,201,93,0.1); border-color: var(--ns-y); }
.log-pill.is-locked { opacity: 0.5; border-color: rgba(239, 68, 68, 0.2); color: var(--ns-muted); background: rgba(239, 68, 68, 0.02); }
.log-pill.is-locked:hover { opacity: 1; transform: none; cursor: not-allowed; }

.log-name { display: flex; align-items: center; gap: 6px; }
.log-date { font-family: 'JetBrains Mono', monospace; font-size: 9px; opacity: 0.6; display: flex; align-items: center; }

@keyframes pulseNew { 0% { box-shadow: 0 0 0 0 rgba(244, 201, 93, 0.4); } 70% { box-shadow: 0 0 0 4px rgba(244, 201, 93, 0); } 100% { box-shadow: 0 0 0 0 rgba(244, 201, 93, 0); } }
.log-new-badge { background: var(--ns-y); color: #000; font-family: 'JetBrains Mono', monospace; font-size: 8px; font-weight: 900; padding: 2px 4px; border-radius: 4px; margin-left: 6px; animation: pulseNew 2s infinite; }

.ns-signature-wrapper { margin-top: 80px; padding: 40px 20px; border-top: 1px solid var(--ns-border); text-align: center; position: relative; overflow: hidden; }
.ns-signature-content { display: inline-flex; flex-direction: column; align-items: center; gap: 8px; font-family: 'JetBrains Mono', monospace; font-size: 10px; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 0.2em; position: relative; z-index: 2; }
.ns-signature-content span { color: var(--ns-y); font-weight: 700; filter: drop-shadow(0 0 5px rgba(244,201,93,0.4)); }
.ns-signature-logo { width: 32px; height: 32px; opacity: 0.2; margin-bottom: 8px; transition: 0.3s; }
.ns-signature-wrapper:hover .ns-signature-logo { opacity: 0.8; transform: rotate(180deg); }
.ns-signature-wrapper:hover .ns-signature-content { color: rgba(255,255,255,0.6); }

@media (max-width: 768px) {
    .supreme-grid { grid-template-columns: 1fr; gap: 40px 0; }
    .hero-slider-wrap { min-height: 50vh; align-items: flex-end; border-radius: 20px; }
    .hero-glass-panel { margin: 20px; padding: 30px 20px; border-radius: 20px; }
    .hero-title { font-size: 36px; }
    .hero-controls { bottom: 20px; right: 20px; }
    .section-header { flex-direction: column; align-items: flex-start; gap: 10px; }
}
</style>

<div class="supreme-wrapper">
    <div class="supreme-container">

        <div class="sys-ticker">
            <div class="sys-ticker-badge">ARCHIVES NERV</div>
            <div class="sys-ticker-text" id="typewriterText"></div>
        </div>

        <?php if ($hero_query->have_posts()) : ?>
            <div class="hero-slider-wrap ns-skeleton" id="nervHeroSlider">
                <?php $slide_index = 0; while ($hero_query->have_posts()) : $hero_query->the_post(); $hid = get_the_ID(); ?>
                    <div class="hero-slide <?php echo $slide_index === 0 ? 'is-active' : ''; ?>">
                        <img src="<?php echo esc_url(nerv_fp_banner($hid)); ?>" alt="Banner" class="hero-bg" loading="lazy">
                        <div class="hero-gradient"></div>
                        <div class="hero-glass-panel">
                            <span class="hero-tag">DOSSIER CLASSIFIÉ PRIORITAIRE</span>
                            <h1 class="hero-title"><?php echo esc_html(get_the_title()); ?></h1>
                            <p class="hero-desc"><?php echo esc_html(nerv_fp_excerpt($hid, 25)); ?></p>
                            <a href="<?php the_permalink(); ?>" class="hero-btn">
                                DÉCRYPTER L'ARCHIVE
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                            </a>
                        </div>
                    </div>
                <?php $slide_index++; endwhile; wp_reset_postdata(); ?>

                <div class="hero-controls">
                    <?php for ($i = 0; $i < $slide_index; $i++) : ?>
                        <div class="hero-dot <?php echo $i === 0 ? 'is-active' : ''; ?>" data-slide="<?php echo $i; ?>"></div>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="section-header">
            <h2 class="section-title"><span class="title-muted">FLUX DES</span> <span class="title-highlight">SORTIES</span></h2>
            <div class="section-subtitle">MISE À JOUR EN TEMPS RÉEL</div>
        </div>

        <?php 
        $lang_flags_map = ['VF'=>'🇫🇷', 'VA'=>'🇬🇧', 'ES'=>'🇪🇸', 'RAW'=>'🇯🇵', 'VO'=>'🇯🇵'];
        
        if ($latest_release_query->have_posts()) : ?>
            <div class="supreme-grid">
                <?php while ($latest_release_query->have_posts()) : $latest_release_query->the_post(); 
                    $pid = get_the_ID(); 
                    $chaps = nerv_fp_latest_chapters($pid, 3, $user_has_access); ?>
                    
                    <div class="breakout-card">
                        <a href="<?php the_permalink(); ?>" class="bc-cover-wrap ns-skeleton">
                            <img src="<?php echo esc_url(nerv_fp_cover($pid)); ?>" loading="lazy" alt="Cover">
                        </a>

                        <div class="bc-meta">
                            <span class="bc-id">ID.<?php echo str_pad($pid, 6, "0", STR_PAD_LEFT); ?></span>
                            <a href="<?php the_permalink(); ?>" class="bc-title"><?php the_title(); ?></a>
                        </div>

                        <div class="bc-logs">
                            <?php foreach($chaps as $c): 
                                $pill_class = '';
                                $icon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>';
                                
                                if ($c['is_locked']) {
                                    $pill_class = 'is-locked';
                                    $icon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>';
                                } elseif ($c['is_premium']) {
                                    $pill_class = 'is-premium';
                                    $icon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>';
                                }
                                
                                // On récupère le bon drapeau
                                $flag = $lang_flags_map[$c['lang']] ?? '🌐';
                            ?>
                                <a class="log-pill <?php echo $pill_class; ?>" href="<?php echo esc_url($c['url']); ?>">
                                    <span class="log-name">
                                        <?php echo $icon . esc_html($c['name']); ?>
                                        <?php if ($c['is_new']): ?>
                                            <span class="log-new-badge">NEW</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="log-date">
                                        <span style="font-size:12px; margin-right:4px;"><?php echo $flag; ?></span>
                                        -<?php echo esc_html($c['date']); ?>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        <?php endif; ?>

        <div class="ns-signature-wrapper">
            <div class="ns-signature-content">
                <svg class="ns-signature-logo" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                <div>DESIGN EXCLUSIF À LA <span>NERVSCANS</span></div>
                <div>ENGINEERED BY <span>SHIROE</span> // MAGI.SYS.CORE</div>
            </div>
        </div>

    </div>
</div>

<script>
const textToType = "Sync: <?php echo date('H:i:s'); ?> // Flux de données actif... Surveillance des cibles...";
const typeContainer = document.getElementById('typewriterText');
let charIndex = 0;

function typeWriter() {
    if (charIndex < textToType.length) {
        typeContainer.innerHTML += textToType.charAt(charIndex);
        charIndex++;
        setTimeout(typeWriter, Math.random() * 50 + 20);
    }
}
setTimeout(typeWriter, 500);

document.addEventListener('DOMContentLoaded', function() {
    const sliderWrap = document.getElementById('nervHeroSlider');
    if (!sliderWrap) return;

    const slides = sliderWrap.querySelectorAll('.hero-slide');
    const dots = sliderWrap.querySelectorAll('.hero-dot');
    let currentIndex = 0;
    let timer;

    function goToSlide(index) {
        slides.forEach(s => s.classList.remove('is-active'));
        dots.forEach(d => d.classList.remove('is-active'));
        slides[index].classList.add('is-active');
        dots[index].classList.add('is-active');
        currentIndex = index;
    }

    function nextSlide() {
        let nextIndex = (currentIndex + 1) % slides.length;
        goToSlide(nextIndex);
    }

    function startTimer() { timer = setInterval(nextSlide, 6000); }
    function resetTimer() { clearInterval(timer); startTimer(); }

    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => { goToSlide(index); resetTimer(); });
    });

    startTimer();
});
</script>

<?php get_footer(); ?>