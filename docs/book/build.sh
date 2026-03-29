#!/bin/bash
#
# Build the Bibliotheca book as PDF.
#
# Usage: bash docs/book/build.sh
# Output: docs/book/ten_brief_lessons.pdf

set -e

BOOK_DIR="docs/book"
DOCS_DIR="docs"
OUTPUT="$BOOK_DIR/ten_brief_lessons.pdf"
COMBINED="$BOOK_DIR/combined.md"

echo "Assembling book..."

# Start with empty file
> "$COMBINED"

# --- WHO IS THIS FOR ---
cat >> "$COMBINED" << 'FRONTMATTER'
# Who Is This Book For {.unnumbered}

For the young programmer — or the curious one at any age — who wants
to understand how a web application actually works, in anno Domini 2026.
From the database to the browser, with nothing in between but your own code.

You do not need experience with web development. But you should be
comfortable with basic programming: variables, functions, loops,
conditionals. You should know what a class is and how objects work —
we use Object-Oriented Programming throughout, and we will not stop
to explain inheritance or encapsulation.

If you have never written a line of code, start there first.
Python is a good choice. Then come back.

## What you will need {.unnumbered}

- A **Linux** machine (we use Debian, but any distribution works)
- **Apache**, **PHP** and **SQLite** installed
- A **text editor** (we recommend Sublime Text)
- A **terminal** and a **web browser**
- **Curiosity**

## What you will learn {.unnumbered}

- How HTTP works — requests, responses, status codes
- How to design a relational database with SQLite
- How to structure an application with Model-View-Controller
- How to build a REST API with pure PHP
- How to build a dynamic frontend with pure JavaScript
- How to implement CRUD operations from click to database row
- How to validate, sanitize, and normalize user input
- How to handle permissions, soft deletes, and dependencies
- How to debug when things go wrong
- How to secure your application: authentication, CSRF, headers, HTTPS

## What this book is not {.unnumbered}

This is not a reference manual. It is a journey. Each chapter builds
on the previous one. Read in order. Type the code yourself. Break it.
Fix it. That is how you learn.

We use no frameworks, no libraries, no build tools. Not because they
are bad — but because you need to understand what they replace before
you use them.

\newpage

FRONTMATTER

# --- CHAPTERS ---
# Preprocess: strip "Chapter XX — " from headings, let LaTeX number them
for file in \
    "$DOCS_DIR/00_prelude.md" \
    "$DOCS_DIR/01_introduction.md" \
    "$DOCS_DIR/02_database.md" \
    "$DOCS_DIR/03_structure.md" \
    "$DOCS_DIR/04_backend.md" \
    "$DOCS_DIR/05_frontend.md" \
    "$DOCS_DIR/06_crud.md" \
    "$DOCS_DIR/07_validation.md" \
    "$DOCS_DIR/08_permissions.md" \
    "$DOCS_DIR/09_debugging.md" \
    "$DOCS_DIR/10_security.md"
do
    echo "  Adding $file..."
    echo "" >> "$COMBINED"
    # Remove "## Next" sections, strip "Chapter XX — " from headings,
    # and remove standalone markdown links to other chapters
    sed '/^## Next$/,$ d' "$file" \
        | sed 's/^# Chapter [0-9]* — /# /' \
        | sed 's/^# Chapter 10 — /# /' \
        | sed '/^\[Chapter [0-9]/d' \
        >> "$COMBINED"
    echo "" >> "$COMBINED"
    echo "\\newpage" >> "$COMBINED"
    echo "" >> "$COMBINED"
done

# --- APPENDICES ---
cat >> "$COMBINED" << 'EOF'

\appendix

EOF

for file in \
    "$DOCS_DIR/how_it_works.md" \
    "$DOCS_DIR/glossary.md" \
    "$DOCS_DIR/apocrypha.md"
do
    echo "  Adding appendix $file..."
    echo "" >> "$COMBINED"
    sed '/^## Next$/,$ d' "$file" >> "$COMBINED"
    echo "" >> "$COMBINED"
    echo "\\newpage" >> "$COMBINED"
    echo "" >> "$COMBINED"
done

echo "Generating PDF..."

# Generate .tex first, then fix compatibility issues, then compile
TEX_FILE="$BOOK_DIR/ten_brief_lessons.tex"

pandoc "$COMBINED" \
    --template="$BOOK_DIR/template.tex" \
    --pdf-engine=xelatex \
    --highlight-style=kate \
    --top-level-division=chapter \
    --columns=65 \
    -V documentclass=book \
    -o "$TEX_FILE"

# Fix: proportional column widths for older TeX Live
python3 "$BOOK_DIR/fix_tables.py" "$TEX_FILE"

# Compile with xelatex (twice for TOC)
cd "$BOOK_DIR"
xelatex -interaction=nonstopmode "ten_brief_lessons.tex" > /dev/null 2>&1
xelatex -interaction=nonstopmode "ten_brief_lessons.tex" > /dev/null 2>&1
cd - > /dev/null

echo "Done: $OUTPUT"
