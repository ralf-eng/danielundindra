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

/**
 * Ladereihenfolge der Stylesheets geraderücken.
 *
 * Das Parent bindet `wprbn-style` über get_stylesheet_uri() ein und hängt
 * `wprbn-main` (main.css) als abhängig daran. Unter einem Child zeigt
 * get_stylesheet_uri() aber auf die Child-style.css – die läuft damit VOR
 * main.css. Eine Child-Regel verliert dann gegen eine gleich spezifische
 * Parent-Regel, obwohl sie gewinnen soll. Beides steht außerhalb von @layer,
 * also entscheidet allein die Reihenfolge.
 *
 * Deshalb: `wprbn-style` auf die Parent-style.css umbiegen (dort stehen die
 * Farb-Tokens, und main.css darf weiter darauf aufbauen) und die
 * Child-style.css als eigenes Blatt hinter main.css hängen.
 *
 * Nebeneffekt: Der frühere Umweg über die Version von `wprbn-style` entfällt.
 * Das eigene Blatt trägt filemtime() als Cache-Buster und zeigt Änderungen
 * sofort – die Konstante WPRBN_VERSION des Parents steht dafür zu lange fest.
 */
add_action('wp_enqueue_scripts', static function (): void {
    $styles = wp_styles();
    $parent = get_template_directory() . '/style.css';

    if (isset($styles->registered['wprbn-style']) && file_exists($parent)) {
        $styles->registered['wprbn-style']->src = get_template_directory_uri() . '/style.css';
        $styles->registered['wprbn-style']->ver = (string) filemtime($parent);
    }

    $child = get_stylesheet_directory() . '/style.css';
    wp_enqueue_style(
        'danielundindra-style',
        get_stylesheet_uri(),
        ['wprbn-main'],
        file_exists($child) ? (string) filemtime($child) : '1.0.0'
    );
}, 11); // Priorität 11: nach dem Parent-Enqueue (Standard 10).

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

