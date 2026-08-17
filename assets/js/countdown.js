/**
 * Countdown bis zur freien Trauung.
 *
 * Zaehlt jede Sekunde herunter. Der Zielzeitpunkt steht als ISO-Zeit mit
 * Zeitzone im data-ziel-Attribut, damit Gaeste in anderen Zeitzonen dieselbe
 * Restzeit sehen. Die Kacheln sind aria-hidden – daneben steht ein ruhiger
 * Satz fuer Vorlesewerkzeuge.
 */
(function () {
    'use strict';

    var TAG = 86400000, STUNDE = 3600000, MINUTE = 60000, SEKUNDE = 1000;

    function zweistellig(n) {
        return n < 10 ? '0' + n : String(n);
    }

    function starte(wurzel) {
        var ziel = Date.parse(wurzel.getAttribute('data-ziel'));
        if (isNaN(ziel)) { return; }

        var felder = {};
        wurzel.querySelectorAll('[data-teil]').forEach(function (el) {
            felder[el.getAttribute('data-teil')] = el;
        });

        var timer = null;

        function zeichne() {
            var rest = ziel - Date.now();

            if (rest <= 0) {
                Object.keys(felder).forEach(function (k) { felder[k].textContent = '00'; });
                wurzel.classList.add('is-vorbei');
                if (timer) { clearInterval(timer); }
                return;
            }

            var tage = Math.floor(rest / TAG);
            var std  = Math.floor((rest % TAG) / STUNDE);
            var min  = Math.floor((rest % STUNDE) / MINUTE);
            var sek  = Math.floor((rest % MINUTE) / SEKUNDE);

            if (felder.tage)     { felder.tage.textContent     = String(tage); }
            if (felder.stunden)  { felder.stunden.textContent  = zweistellig(std); }
            if (felder.minuten)  { felder.minuten.textContent  = zweistellig(min); }
            if (felder.sekunden) { felder.sekunden.textContent = zweistellig(sek); }
        }

        zeichne();
        timer = setInterval(zeichne, SEKUNDE);

        // Kommt der Rechner aus dem Ruhezustand, steht die Anzeige sonst falsch.
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) { zeichne(); }
        });
    }

    function los() {
        document.querySelectorAll('.dui-countdown[data-ziel]').forEach(starte);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', los);
    } else {
        los();
    }
}());
