"""Logo fuer die Hochzeitsseite Indra & Daniel.

Zwei ineinandergreifende Goldringe, von einem Eukalyptus-Kranzsegment umfasst
(wie am Ringkissen des Paares), dazu der Schriftzug als Pfad – schriftunabhaengig,
laedt nirgends nach.

Zwei Fassungen:
  gestapelt  – Ringe ueber dem Namen, fuer grosse Auftritte
  waagerecht – Ringe neben dem Namen, fuer die schmale Kopfleiste
"""
import math
from xml.sax.saxutils import escape
from fontTools.ttLib import TTFont
from fontTools.pens.boundsPen import BoundsPen
from fontTools.pens.transformPen import TransformPen
from fontTools.misc.transform import Transform
import uharfbuzz as hb

FONT = "great-vibes.ttf"
GOLD = "#b08d45"
GOLD_LIGHT = "#c9a961"
GREEN = "#5f7361"
GREEN_SOFT = "#7d8f7e"
INK = "#2c332c"


# ── Schriftzug ──────────────────────────────────────────────────────────────

def _shape_buffer(text):
    with open(FONT, "rb") as fh:
        data = fh.read()
    face = hb.Face(data)
    font = hb.Font(face)
    font.scale = (face.upem, face.upem)
    hb.ot_font_set_funcs(font)
    buf = hb.Buffer()
    buf.add_str(text)
    buf.guess_segment_properties()
    hb.shape(font, buf, {"kern": True, "liga": True, "calt": True})
    return buf, face.upem


def name_path(text, size=100.0):
    """SVG-Pfad + Bounds des gesetzten Schriftzugs (Baseline liegt auf y=0)."""
    from fontTools.pens.svgPathPen import SVGPathPen

    buf, upem = _shape_buffer(text)
    tt = TTFont(FONT)
    order, gs = tt.getGlyphOrder(), tt.getGlyphSet()
    scale = size / upem

    pen = SVGPathPen(gs, ntos=lambda v: f"{v:.1f}")
    bounds = BoundsPen(gs)
    x = y = 0.0
    for info, pos in zip(buf.glyph_infos, buf.glyph_positions):
        t = Transform(scale, 0, 0, -scale,
                      (x + pos.x_offset) * scale, -(y + pos.y_offset) * scale)
        glyph = gs[order[info.codepoint]]
        glyph.draw(TransformPen(pen, t))
        glyph.draw(TransformPen(bounds, t))
        x += pos.x_advance
        y += pos.y_advance
    return pen.getCommands(), bounds.bounds


# ── Ringe ───────────────────────────────────────────────────────────────────

def rings(cx, cy, r, uid=""):
    """Zwei ineinandergreifende Ringe – oben liegt der linke vorn."""
    sw = r * 0.10
    d = r * 0.56
    ax, bx = cx - d, cx + d
    iy = math.sqrt(max(r * r - d * d, 0.0))
    return f'''  <g fill="none" stroke-width="{sw:.2f}">
    <circle cx="{ax:.2f}" cy="{cy:.2f}" r="{r:.2f}" stroke="{GOLD}"/>
    <circle cx="{bx:.2f}" cy="{cy:.2f}" r="{r:.2f}" stroke="{GOLD_LIGHT}"/>
    <clipPath id="knot{uid}"><circle cx="{cx:.2f}" cy="{cy - iy:.2f}" r="{r * 0.32:.2f}"/></clipPath>
    <circle cx="{ax:.2f}" cy="{cy:.2f}" r="{r:.2f}" stroke="{GOLD}" clip-path="url(#knot{uid})"/>
  </g>'''


# ── Eukalyptus ──────────────────────────────────────────────────────────────

def _pt(cx, cy, r, deg):
    a = math.radians(deg)
    return cx + r * math.cos(a), cy - r * math.sin(a)


def wreath(cx, cy, r, a0, a1, leaves=6, green=GREEN):
    """Kranzsegment: Blaetter entlang eines Bogens, tangential gedreht –
    liegt aussen am Ring an wie die Zweige am Goldreif des Ringkissens."""
    rr = r * 1.12
    x0, y0 = _pt(cx, cy, rr, a0)
    x1, y1 = _pt(cx, cy, rr, a1)
    large, sweep = 0, (1 if a1 < a0 else 0)
    out = [f'    <path d="M{x0:.2f} {y0:.2f} A{rr:.2f} {rr:.2f} 0 {large} {sweep} {x1:.2f} {y1:.2f}" '
           f'fill="none" stroke="{green}" stroke-width="{r * 0.028:.2f}" stroke-linecap="round" opacity="0.75"/>']
    for i in range(leaves):
        t = (i + 0.5) / leaves
        a = a0 + (a1 - a0) * t
        # abwechselnd knapp innen und aussen am Bogen
        side = 1.0 if i % 2 == 0 else -1.0
        lr = r * (0.24 - 0.11 * t)                  # zur Spitze hin kleiner
        px, py = _pt(cx, cy, rr + side * lr * 0.52, a)
        rot = -(a + 90) + side * 24                 # tangential, leicht aufgefaechert
        out.append(
            f'    <ellipse cx="{px:.2f}" cy="{py:.2f}" rx="{lr:.2f}" ry="{lr * 0.70:.2f}" '
            f'fill="{green if i % 3 else GREEN_SOFT}" opacity="{0.95 - 0.30 * t:.2f}" '
            f'transform="rotate({rot:.1f} {px:.2f} {py:.2f})"/>'
        )
    return "\n".join(out)


def ring_greenery(cx, cy, r):
    """Kranzsegmente an beiden Aussenseiten der Ringpaare."""
    d = r * 0.56
    return ('  <g>\n'
            + wreath(cx - d, cy, r, 168, 250) + '\n'
            + wreath(cx + d, cy, r, 12, -70) + '\n'
            '  </g>')


# ── Aufbau ──────────────────────────────────────────────────────────────────

def _svg(w, h, body, text):
    label = escape(text)
    return (f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {w:.2f} {h:.2f}" '
            f'width="{w:.0f}" height="{h:.0f}" role="img" aria-label="{label}">\n'
            f'  <title>{label}</title>\n{body}\n</svg>')


def stacked(text="Indra & Daniel", ink=INK, green=GREEN, pad=10.0):
    S = 100.0
    d, (x0, y0, x1, y1) = name_path(text, S)
    nw, nh = x1 - x0, y1 - y0

    r = nw * 0.108                      # Ringgroesse folgt der Namensbreite
    block_w = 2 * r * 1.56 + 2 * (r * 1.12 + r * 0.30)
    gap = nh * 0.26

    W = max(nw, block_w) + 2 * pad
    H = 2 * r * 1.42 + gap + nh + 2 * pad
    cx = W / 2
    ring_cy = pad + r * 1.42

    body = "\n".join([
        ring_greenery(cx, ring_cy, r),
        rings(cx, ring_cy, r),
        f'  <path transform="translate({cx - nw / 2 - x0:.2f} {pad + 2 * r * 1.42 + gap - y0:.2f})" '
        f'fill="{ink}" d="{d}"/>',
    ])
    return _svg(W, H, body, text)


def horizontal(text="Indra & Daniel", ink=INK, green=GREEN, pad=8.0):
    """Ringe links, Schriftzug rechts – bleibt in einer 60–70px-Kopfleiste lesbar."""
    S = 100.0
    d, (x0, y0, x1, y1) = name_path(text, S)
    nw, nh = x1 - x0, y1 - y0

    r = nh * 0.40
    block_w = 2 * r * 1.56 + 2 * (r * 1.12 + r * 0.30)
    gap = r * 0.55

    W = block_w + gap + nw + 2 * pad
    H = max(2 * r * 1.42, nh) + 2 * pad
    cy = H / 2
    ring_cx = pad + block_w / 2

    body = "\n".join([
        ring_greenery(ring_cx, cy, r),
        rings(ring_cx, cy, r, uid="h"),
        f'  <path transform="translate({pad + block_w + gap - x0:.2f} {cy + nh / 2 - y0 - nh:.2f})" '
        f'fill="{ink}" d="{d}"/>',
    ])
    return _svg(W, H, body, text)


def hell(svg):
    """Helle Fassung fuer dunkle Flaechen und Fotos."""
    return (svg.replace(f'fill="{INK}"', 'fill="#f8f5ef"')
               .replace(f'fill="{GREEN}"', 'fill="#c3d0bf"')
               .replace(f'fill="{GREEN_SOFT}"', 'fill="#dbe3d6"')
               .replace(f'stroke="{GREEN}"', 'stroke="#c3d0bf"')
               .replace(f'stroke="{GOLD}"', 'stroke="#d9bd78"')
               .replace(f'stroke="{GOLD_LIGHT}"', 'stroke="#eed9a6"'))


if __name__ == "__main__":
    files = {
        "logo-stapel.svg": stacked(),
        "logo-stapel-hell.svg": hell(stacked()),
        "logo-quer.svg": horizontal(),
        "logo-quer-hell.svg": hell(horizontal()),
    }
    for name, svg in files.items():
        with open(name, "w") as fh:
            fh.write(svg)
        print(f"{name}: {len(svg)} B")
