#!/usr/bin/env python3
"""Post-process pandoc HTML: inject Thai fonts and back-navigation."""
from __future__ import annotations

import sys
from pathlib import Path

FONT_LINK = (
    '<link rel="preconnect" href="https://fonts.googleapis.com">'
    '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
    '<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;600;700&family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">'
)
NAV = (
    '<div class="wrap"><div class="topbar">'
    '<a href="../index.html">← กลับพอร์ทัล Mali</a>'
    '<span>Controlled Document</span></div>'
)


def process(path: Path) -> None:
    html = path.read_text(encoding="utf-8")
    if FONT_LINK not in html:
        html = html.replace("</head>", FONT_LINK + "\n</head>", 1)
    if 'class="topbar"' not in html:
        html = html.replace("<body>", "<body>" + NAV, 1)
        html = html.replace("</body>", "</div></body>", 1)
    path.write_text(html, encoding="utf-8")


if __name__ == "__main__":
    for arg in sys.argv[1:]:
        process(Path(arg))
        print(f"[OK] {arg}")
