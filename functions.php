<?php
/**
 * Child Theme: Daniel & Indra – Hochzeit
 *
 * Parent: wprbn_theme
 *
 * Das Parent-Theme lädt sein Haupt-Stylesheet über get_stylesheet_uri() – in
 * einem Child Theme zeigt das auf die style.css DIESES Child Themes. Dadurch
 * würden die Farb-Tokens aus der Parent-style.css fehlen. Deshalb wird die
 * Parent-style.css hier explizit VOR der Child-style.css eingebunden.
 */

declare(strict_types=1);

/* ── Eigene Bausteine dieser Seite ───────────────────────────────────────
   Site-spezifische Features gehören ins Child; das Parent bleibt allgemein. */
require_once __DIR__ . '/inc/countdown/countdown.php';

add_action('wp_enqueue_scripts', function (): void {
    // Parent-style.css (Farb-Tokens etc.) laden.
    // get_template_directory_uri() zeigt immer auf das Parent-Theme.
    $parent_style     = get_template_directory() . '/style.css';
    $parent_style_ver = file_exists($parent_style) ? filemtime($parent_style) : false;

    wp_enqueue_style(
        'wprbn-parent-style',
        get_template_directory_uri() . '/style.css',
        [],
        $parent_style_ver ?: (wp_get_theme(get_template())->get('Version') ?: null)
    );
}, 9); // Priorität 9: läuft vor dem Parent-Enqueue (Standard 10),
       // damit die Child-style.css danach ausgegeben wird und überschreiben kann.

/**
 * Fix: Site-Header + Hero + Footer im Child-Theme wiederherstellen.
 *
 * Die Parent-Templates referenzieren die Template-Parts hart mit
 * {"slug":"header","theme":"wprbn_theme"} bzw. "footer". WordPress rendert ein
 * Template-Part nur unter dem AKTIVEN Stylesheet – unter dem Child-Slug
 * schlägt die auf "wprbn_theme" gepinnte Referenz fehl, wodurch Header, Hero
 * und Footer auf allen Seiten verschwinden.
 *
 * Lösung: Das gepinnte Parent-theme-Attribut zur Renderzeit entfernen. Der
 * core/template-part-Block löst dann über get_stylesheet() (= Child) auf und
 * fällt via _get_block_template_file() automatisch auf die Parent-parts/*.html
 * zurück. So bleibt das Child rein erbend – keine Template-Duplikate nötig.
 */
add_filter('render_block_data', static function (array $block): array {
    if (
        ($block['blockName'] ?? '') === 'core/template-part'
        && isset($block['attrs']['theme'])
        && $block['attrs']['theme'] === get_template() // 'wprbn_theme'
    ) {
        unset($block['attrs']['theme']);
    }
    return $block;
});

/**
 * Cache-Buster für die Child-style.css.
 *
 * Der Parent bindet die (Child-)style.css via get_stylesheet_uri() mit der
 * statischen Konstante WPRBN_VERSION ein (Handle 'wprbn-style'). Änderungen an
 * dieser Datei kämen dadurch wegen unveränderter Version aus dem Browser-Cache.
 * Hier wird die Version nachträglich auf filemtime() der Child-style.css
 * gesetzt – analog zur main.css im Parent.
 */
add_action('wp_enqueue_scripts', static function (): void {
    $styles = wp_styles();
    if (!isset($styles->registered['wprbn-style'])) {
        return;
    }
    $child_css = get_stylesheet_directory() . '/style.css';
    if (file_exists($child_css)) {
        $styles->registered['wprbn-style']->ver = (string) filemtime($child_css);
    }
}, 11); // Priorität 11: nach dem Parent-Enqueue (Standard 10).
