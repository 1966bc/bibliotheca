#!/usr/bin/env python3
"""Fix pandoc-generated LaTeX table widths for older TeX Live."""

import re
import sys

with open(sys.argv[1], 'r') as f:
    tex = f.read()

# Replace \real{X} with X (pandoc 2.17+ uses this)
tex = re.sub(r'\\real\{([0-9.]+)\}', r'\1', tex)

# Wrap proportional column widths in \dimexpr...\relax
# Pattern: p{(\columnwidth - N\tabcolsep) * 0.XXXX}
tex = re.sub(
    r'p\{(\(\\columnwidth\s*-\s*\d+\\tabcolsep\)\s*\*\s*[0-9.]+)\}',
    r'p{\\dimexpr \1 \\relax}',
    tex
)

# Force page breaks before specific sections that fall awkwardly
# at the bottom of pages
for section in ['The Model', 'The implementation']:
    tex = tex.replace(
        f'\\subsection{{{section}}}',
        f'\\clearpage\n\\subsection{{{section}}}'
    )
    tex = tex.replace(
        f'\\section{{{section}}}',
        f'\\clearpage\n\\section{{{section}}}'
    )

with open(sys.argv[1], 'w') as f:
    f.write(tex)
