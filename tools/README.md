# Logo neu erzeugen

Die Logos in `assets/logo/` sind erzeugt, nicht von Hand gezeichnet. Der
Schriftzug steckt als Pfad in der Datei – dadurch braucht das Logo nirgends eine
Schrift nachzuladen und sieht überall gleich aus.

```bash
python3 -m venv .venv
.venv/bin/pip install fonttools brotli uharfbuzz

# Great Vibes aus dem Schriftverzeichnis der Seite holen und entpacken
curl -o great-vibes.woff2 \
  "https://www.hochzeit-daniel-indra.de/wp-content/uploads/fonts/wprbn/great-vibes/great-vibes-400-normal-latin.woff2"
.venv/bin/python -c "from fontTools.ttLib import TTFont; f=TTFont('great-vibes.woff2'); f.flavor=None; f.save('great-vibes.ttf')"

.venv/bin/python logo.py
```

Heraus fallen vier Dateien:

| Datei | Verwendung |
|-------|-----------|
| `logo-quer.svg` | Kopfleiste, eingefahrener Zustand (dunkle Schrift auf Creme) |
| `logo-quer-hell.svg` | Kopfleiste über dem Hero-Foto (cremefarben) |
| `logo-stapel.svg` | grosse Auftritte, Drucksachen |
| `logo-stapel-hell.svg` | grosse Auftritte auf dunklem Grund |

HarfBuzz setzt den Schriftzug mit echten Unterschneidungen – ohne das sitzen die
Verbindungsstriche der Kalligrafie nicht. Wichtig: HarfBuzz liest **kein** woff2,
deshalb der Zwischenschritt nach TTF.
