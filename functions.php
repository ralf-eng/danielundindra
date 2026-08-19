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
require_once __DIR__ . '/inc/ablauf/ablauf.php';

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

/**
 * Menü-Knopf auf schmalen Schirmen nach links.
 *
 * Der Kopf ist dort zweizeilig: Zeile 1 trägt die mittige Marke, Zeile 2 den
 * Knopf – ebenfalls mittig, was unter dem zentrierten Logo verloren aussieht.
 * Der Knopf rückt deshalb in Zeile 1 an den linken Rand, das Logo bleibt mittig.
 *
 * Warum hier und nicht in der style.css: `@media` kann keine CSS-Variable
 * auswerten, der Umschaltpunkt ist aber eine Dashboard-Option. Der Block muss
 * also serverseitig entstehen – dieselbe Bauweise wie wprbn_menu_css_mobile()
 * im Parent. Die Kaskade stimmt von selbst, weil wp_head nach den Stylesheets
 * ausgegeben wird.
 *
 * `.header-inner` ist ein Raster, das sich per Stylesheet nicht auf flex
 * umstellen lässt (display:grid !important im Parent). Position also über
 * grid-row und justify-self, nicht über die Anordnungsrichtung.
 */
add_action('wp_head', static function (): void {
    $bp = (int) (get_option('wprbn_options', [])['menu_mobile_breakpoint'] ?? 767);
    $bp = max(320, min(1400, $bp));

    // Gegen eine sehr bestimmte Parent-Regel: Weil das Logo mittig steht,
    // setzt menu.css den Knopf mit
    //   .logo-align--center.site-header .header-inner > .wprbn-menu-slot
    //   { grid-area: 2 / 1 / auto / -1 !important; justify-self: center !important; }
    // absichtlich in Zeile 2 unter die Marke. Ein schlichtes
    // `.header-inner .wprbn-menu-slot` verliert dagegen. Deshalb derselbe
    // Selektor plus vorangestelltes `body` – das hebt die Spezifität um eine
    // Stufe und gewinnt unabhängig von der Reihenfolge.
    $sel = 'body .logo-align--center.site-header .header-inner > .wprbn-menu-slot,'
         . 'body .logo-align--center.site-header .wp-block-group.header-inner > .wprbn-menu-slot';

    printf(
        '<style id="dui-menu-mobil">@media (max-width:%dpx){%s{'
        . 'grid-area:1/1/auto/-1!important;'
        . 'justify-self:start!important;'
        . 'align-self:center!important;'
        . '}}</style>' . "\n",
        $bp,
        $sel
    );
}, 13); // nach den Farb-Ausgaben des Parents (Priorität 12)

