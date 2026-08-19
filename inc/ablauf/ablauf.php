<?php
/**
 * Ablauf des Tages – die Stationen vom Zusammentreffen bis zum Essen.
 *
 * Site-spezifisches Feature, deshalb im Child-Theme (das Parent bleibt
 * allgemein). Aufbau wie inc/countdown/ nebenan: Shortcode + eigenes Markup,
 * kein Fremdcode, kein Block-Plugin.
 *
 * Die Zeiten stehen so auch auf der gedruckten Einladung. Sie liegen deshalb
 * als Standard hier im Code – wer nur die Uhrzeit einer Station ändert, muss
 * dafür nicht den Seiteninhalt anfassen. Über das Attribut punkte lässt sich
 * die Liste trotzdem im Beitrag überschreiben.
 *
 *   [dui_ablauf]
 *   [dui_ablauf punkte="15:00|Freie Trauung|Unter freiem Himmel;;18:30|Essen"]
 *
 * | Attribut | Standard          | Bedeutung                                  |
 * |----------|-------------------|--------------------------------------------|
 * | punkte   | DUI_ABLAUF_TAG    | Stationen, ";;" trennt sie, "|" die Felder |
 *
 * Felder je Station: Zeit | Titel | Beschreibung (die Beschreibung darf fehlen).
 */

declare(strict_types=1);

/** Der Ablauf des 12. September 2026, wie er auf der Einladung steht. */
const DUI_ABLAUF_TAG = [
    ['14:45', 'Zusammentreffen', 'Wir sammeln uns auf Hof Reismann.'],
    ['15:00', 'Freie Trauung',   'Unsere Traurednerin führt uns durch den Nachmittag.'],
    ['16:00', 'Sektempfang, Kaffee & Fotos', 'Zeit zum Anstoßen, für Kuchen und für Bilder.'],
    ['18:30', 'Essen & Feiern',  'Und dann bleibt der Abend uns allen.'],
];

/**
 * Liest die Stationen aus dem Attribut – oder gibt den Standard zurück.
 *
 * @return array<int, array{0:string,1:string,2:string}>
 */
function dui_ablauf_punkte(string $roh): array
{
    $roh = trim($roh);
    if ($roh === '') {
        return array_map(
            static fn(array $p): array => [$p[0], $p[1], $p[2] ?? ''],
            DUI_ABLAUF_TAG
        );
    }

    $punkte = [];
    foreach (explode(';;', $roh) as $zeile) {
        $teile = array_map('trim', explode('|', $zeile));
        if (($teile[0] ?? '') === '' && ($teile[1] ?? '') === '') {
            continue;
        }
        $punkte[] = [$teile[0] ?? '', $teile[1] ?? '', $teile[2] ?? ''];
    }

    return $punkte;
}

function dui_ablauf_shortcode(array|string $atts = []): string
{
    $a = shortcode_atts(['punkte' => ''], $atts, 'dui_ablauf');

    $punkte = dui_ablauf_punkte((string) $a['punkte']);
    if ($punkte === []) {
        return '';
    }

    // <ol>, weil die Reihenfolge die Aussage trägt: erst treffen, dann trauen.
    // Die Zeit steht als <time>, damit Vorlesewerkzeuge sie als Uhrzeit lesen.
    $html = '<ol class="dui-ablauf">';

    foreach ($punkte as [$zeit, $titel, $text]) {
        $html .= '<li class="dui-ablauf__punkt">';

        if ($zeit !== '') {
            $uhrzeit = preg_match('/^\d{1,2}:\d{2}$/', $zeit) === 1
                ? ' datetime="' . esc_attr($zeit) . '"'
                : '';
            $html .= '<time class="dui-ablauf__zeit"' . $uhrzeit . '>'
                . esc_html($zeit) . '</time>';
        }

        $html .= '<span class="dui-ablauf__text">';
        $html .= '<span class="dui-ablauf__titel">' . esc_html($titel) . '</span>';

        if ($text !== '') {
            $html .= '<span class="dui-ablauf__zusatz">' . esc_html($text) . '</span>';
        }

        $html .= '</span></li>';
    }

    return $html . '</ol>';
}
add_shortcode('dui_ablauf', 'dui_ablauf_shortcode');
