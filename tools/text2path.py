"""Schriftzug in einen SVG-Pfad wandeln – mit echter Textformung (HarfBuzz),
damit die Verbindungsstriche der Kalligrafie sitzen."""
import sys
import uharfbuzz as hb
from fontTools.ttLib import TTFont
from fontTools.pens.svgPathPen import SVGPathPen
from fontTools.pens.transformPen import TransformPen
from fontTools.misc.transform import Transform


def shape(font_path, text, size=100.0):
    with open(font_path, "rb") as fh:
        data = fh.read()
    face = hb.Face(data)
    hb_font = hb.Font(face)
    upem = face.upem
    hb_font.scale = (upem, upem)
    hb.ot_font_set_funcs(hb_font)

    buf = hb.Buffer()
    buf.add_str(text)
    buf.guess_segment_properties()
    hb.shape(hb_font, buf, {"kern": True, "liga": True, "calt": True})

    tt = TTFont(font_path)
    glyph_order = tt.getGlyphOrder()
    glyph_set = tt.getGlyphSet()

    scale = size / upem
    pen_out = SVGPathPen(glyph_set, ntos=lambda v: f"{v:.2f}")

    x = y = 0.0
    for info, pos in zip(buf.glyph_infos, buf.glyph_positions):
        name = glyph_order[info.codepoint]
        # y invertieren: SVG zaehlt nach unten
        t = Transform(scale, 0, 0, -scale, (x + pos.x_offset) * scale, -(y + pos.y_offset) * scale)
        glyph_set[name].draw(TransformPen(pen_out, t))
        x += pos.x_advance
        y += pos.y_advance

    return pen_out.getCommands(), x * scale


if __name__ == "__main__":
    font_path, text, size = sys.argv[1], sys.argv[2], float(sys.argv[3])
    d, advance = shape(font_path, text, size)
    sys.stderr.write(f"advance width: {advance:.2f}\n")
    print(d)
