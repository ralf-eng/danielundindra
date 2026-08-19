<?php
/**
 * Countdown bis zur freien Trauung.
 *
 * Site-spezifisches Feature, deshalb im Child-Theme (das Parent bleibt
 * allgemein). Aufbau wie inc/gpx/ im Parent: Shortcode + eigenes Markup,
 * Vanilla JS, keine Fremdbibliothek.
 *
 *   [dui_countdown]
 *   [dui_countdown datum="2026-09-12 15:00" titel="Bis wir Ja sagen"]
 *
 * | Attribut  | Standard           | Bedeutung                                  |
 * |-----------|--------------------|--------------------------------------------|
 * | datum     | 2026-09-12 15:00   | Zeitpunkt, auf den gezaehlt wird           |
 * | titel     | ''                 | Zeile ueber den Kacheln, leer = keine      |
 * | sekunden  | true               | Sekundenkachel mitzeigen                   |
 */

declare(strict_types=1);

// Gezaehlt wird auf die freie Trauung um 15:00 – nicht auf das
// Zusammentreffen um 14:45, das ist nur das Vorglühen davor.
const DUI_COUNTDOWN_STANDARD = '2026-09-12 15:00';

/**
 * Wie lange noch? Gibt die Restspanne in Einzelteilen zurueck –
 * oder null, wenn der Zeitpunkt schon vorbei ist.
 */
function dui_countdown_rest(DateTimeImmutable $ziel, ?DateTimeImmutable $jetzt = null): ?array
{
    $jetzt ??= new DateTimeImmutable('now', wp_timezone());
    if ($ziel <= $jetzt) {
        return null;
    }
    $d = $jetzt->diff($ziel);
    return [
        'tage'     => (int) $d->days,
        'stunden'  => (int) $d->h,
        'minuten'  => (int) $d->i,
        'sekunden' => (int) $d->s,
    ];
}

/** Satz fuer Vorlesewerkzeuge und fuer den Fall, dass kein JS laeuft. */
function dui_countdown_satz(?array $rest): string
{
    if ($rest === null) {
        return __('Der große Tag ist da.', 'danielundindra');
    }
    if ($rest['tage'] === 0) {
        return __('Heute ist es so weit.', 'danielundindra');
    }
    return sprintf(
        /* translators: %s: Anzahl Tage */
        _n('Noch %s Tag.', 'Noch %s Tage.', $rest['tage'], 'danielundindra'),
        number_format_i18n($rest['tage'])
    );
}

function dui_countdown_shortcode(array|string $atts = []): string
{
    $a = shortcode_atts([
        'datum'    => DUI_COUNTDOWN_STANDARD,
        'titel'    => '',
        'sekunden' => 'true',
    ], $atts, 'dui_countdown');

    $tz = wp_timezone();
    try {
        $ziel = new DateTimeImmutable((string) $a['datum'], $tz);
    } catch (Exception) {
        return '';
    }

    $rest     = dui_countdown_rest($ziel);
    $sekunden = filter_var($a['sekunden'], FILTER_VALIDATE_BOOLEAN);

    $felder = [
        'tage'     => _x('Tage', 'Countdown', 'danielundindra'),
        'stunden'  => _x('Stunden', 'Countdown', 'danielundindra'),
        'minuten'  => _x('Minuten', 'Countdown', 'danielundindra'),
    ];
    if ($sekunden) {
        $felder['sekunden'] = _x('Sekunden', 'Countdown', 'danielundindra');
    }

    $html  = '<div class="dui-countdown" data-ziel="' . esc_attr($ziel->format(DATE_ATOM)) . '">';

    if ($a['titel'] !== '') {
        $html .= '<p class="dui-countdown__titel">' . esc_html($a['titel']) . '</p>';
    }

    // Sichtbar nur ohne JS bzw. fuer Vorlesewerkzeuge – die Kacheln daneben
    // ticken und waeren als Live-Bereich eine Dauerbeschallung.
    $html .= '<p class="dui-countdown__satz">'
        . '<time datetime="' . esc_attr($ziel->format('Y-m-d')) . '">'
        . esc_html(dui_countdown_satz($rest)) . '</time></p>';

    $html .= '<div class="dui-countdown__reihe" aria-hidden="true">';
    foreach ($felder as $teil => $wort) {
        $wert = $rest === null ? 0 : $rest[$teil];
        $html .= '<div class="dui-countdown__feld">'
            . '<span class="dui-countdown__zahl" data-teil="' . esc_attr($teil) . '">'
            . esc_html(str_pad((string) $wert, 2, '0', STR_PAD_LEFT)) . '</span>'
            . '<span class="dui-countdown__wort">' . esc_html($wort) . '</span>'
            . '</div>';
    }
    $html .= '</div></div>';

    wp_enqueue_script('dui-countdown');

    return $html;
}
add_shortcode('dui_countdown', 'dui_countdown_shortcode');

/**
 * Das Skript wird nur registriert, nicht eingebunden – erst der Shortcode
 * haengt es ein. Seiten ohne Countdown bleiben davon unberuehrt.
 */
add_action('wp_enqueue_scripts', static function (): void {
    $rel  = '/assets/js/countdown.js';
    $datei = get_stylesheet_directory() . $rel;
    wp_register_script(
        'dui-countdown',
        get_stylesheet_directory_uri() . $rel,
        [],
        file_exists($datei) ? (string) filemtime($datei) : '1.0.0',
        ['strategy' => 'defer', 'in_footer' => true]
    );
});
