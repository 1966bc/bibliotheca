## First things first

Before following the journey of a request, you need to know the
directory structure where the application files live — and
most importantly **why** they are arranged this way.

The fundamental rule: everything inside `public/` is reachable
by the browser (and therefore by anyone). Everything outside it
(`src/`, `sql/`) is invisible to the browser — only PHP can
get there.

```
bibliotheca/
    src/                    ← PRIVATE — only PHP can reach it
        DBMS.php
        Publisher.php
        Category.php
        Author.php
        Book.php
    sql/                    ← PRIVATE — the database
        bibliotheca.db
    public/                 ← PUBLIC — the browser only sees here
        .htaccess
        index.php
        css/
        js/
        api/
        pages/
```

This separation is a **security** choice: the code that talks
to the database and the business logic must not be exposed to the
world. The browser can only see the front door (`public/`), never
the vault (`src/`, `sql/`).

**But who decides that `public/` is public?** Apache's configuration.
In the virtual host file there is a `DocumentRoot` directive that
tells Apache "this is the folder the browser can reach." Everything
inside that folder is accessible via URL. Everything outside it
does not exist for the browser.

In our case `DocumentRoot` points to `public/`. `src/` and `sql/`
sit one level above — outside the door. Apache does not serve them,
the browser cannot reach them. But PHP, which runs **on the server**,
can get there with `require_once __DIR__ . '/../../src/...'` because
for PHP they are files on disk, not URLs. It is not magic, it is one
line of configuration.

Inside `public/` there is also `.htaccess` — the doorman who manages
the rewriting rules with the two `RewriteCond` directives. It is
needed because in our URLs we write `/publishers` (without `.php`) —
"clean" URLs. If we wrote `/publishers.php` and the file existed,
Apache would find it on its own and `.htaccess` would not step in.
This technique is called **URL rewriting** — used by virtually every
modern website. Clean URLs are more readable, easier to remember, and
hide the underlying technology: the user does not know whether the
server uses PHP, Python, or something else. But that is another story;
we will see it in the journey of a request.

## The journey of a request

1. Clicking "Publishers" in the menu is the same as typing
   `http://localhost/bibliotheca/public/publishers` in the browser's
   address bar — the click does it for us, but we could type the string
   ourselves.

2. The browser sends the request `GET /bibliotheca/public/publishers`
   to the server.

> **Who does this?** How do we get from the address in the browser bar
> to the actual string sent to the server (Apache in our case)?
>
> It is the browser's **network stack** — not a separate program, but
> a set of components (libraries, modules) compiled into Chrome,
> Firefox, etc. You do not see it, you do not install it separately:
> it is inside the browser the way an engine is inside a car. It takes
> care of resolving the domain (DNS), opening a TCP connection, and
> sending the HTTP request.
>
> **Resolving the domain (DNS — Domain Name System):** the browser sees
> `localhost` in the URL, but to open a network connection it needs an
> **IP address** (like `127.0.0.1`). DNS is the mechanism that
> translates the name into the number — like a phone book: you look up
> the name, you find the number.
> But who does it ask? It depends on where you are:
>
> | Case | Asks whom | IP it gets |
> |------|-----------|------------|
> | `localhost` | Nobody, it already knows (`/etc/hosts`) | `127.0.0.1` |
> | Local network (company, school, hospital) | Internal DNS on the network | Private IP (e.g. `192.168.x.x`) |
> | Public site (`google.com`) | External DNS (e.g. from your ISP) | Public IP (e.g. `142.250.181.174`) |
>
> An application running on a local network does not need a public
> address: the internal DNS translates names only for those connected
> to that network. From outside, that name does not exist.
>
> **The HTTP response:** when Apache receives the request, what happens?
> Let us follow the path inside the server:
>
> 1. Apache receives `GET /bibliotheca/public/publishers`
> 2. Looks for the file `publishers` inside `public/` — does not exist
> 3. Looks for the directory `publishers` inside `public/` — does not exist
> 4. Before giving up with a 404, Apache checks whether the
>    directory contains an `.htaccess` with instructions — the **plan B**.
>    It finds it, reads it, and the two `RewriteCond` directives say:
>    "if it is not a file (`!-f`) and not a directory (`!-d`), then
>    apply the rule." The `RewriteRule` says: "take what was requested
>    and pass it to `index.php?route=...`."
>    Result: the request becomes `index.php?route=publishers`.
>    (If instead you request `style.css` or `publishers.js` which exist
>    as files, `.htaccess` does not step in — Apache serves them
>    directly)
>
>    `.htaccess` is like the **doorman of a building**: "Looking for
>    Mr. Publishers? I cannot find him, maybe the address is not quite
>    right... but try apartment `index.php`, unit `route=publishers`."
>    If instead you ask for someone who actually lives there
>    (`style.css`), he lets you through without a fuss.
>
>    **Is this built-in behavior?** No. Just as Apache looks for
>    `index.php` or `index.html` when you access a directory
>    (`DirectoryIndex` directive), consulting `.htaccess` is also a
>    **configurable convention** (`AllowOverride` directive).
>    If an administrator sets `AllowOverride None`, Apache ignores
>    all `.htaccess` files even if they exist. If they set
>    `DirectoryIndex home.php`, Apache looks for `home.php` instead of
>    `index.php`. Apache ships from the factory with certain defaults
>    that everyone uses, and by using them so much they seem
>    built-in — but you can change them.
>
>    Here is the proof from our Debian 12 machine at the time of writing.
>    Apache's main file lives at `/etc/apache2/apache2.conf`.
>    Opening it, the first lines explain how the configuration is
>    organized — a dance of files:
>
>    ```
>    /etc/apache2/
>    |-- apache2.conf        (the main file)
>    |   `-- ports.conf      (ports: 80, 443...)
>    |-- mods-enabled/       (active modules)
>    |-- conf-enabled/       (extra configurations)
>    `-- sites-enabled/      (the sites being served)
>    ```
>
>    Inside `apache2.conf` we find the directives that matter to us:
>
>    ```apache
>    # Line 193 — the "plan B" filename.
>    AccessFileName .htaccess
>
>    # Lines 165-168 — AllowOverride All for /var/www/html/
>    # Our .htaccess works thanks to this All.
>    <Directory /var/www/html/>
>        AllowOverride All
>        Require all granted
>    </Directory>
>    ```
>
>    And in `/etc/apache2/mods-enabled/dir.conf`:
>
>    ```apache
>    # The files searched for when you access a directory.
>    DirectoryIndex index.html index.cgi index.pl
>                   index.php index.xhtml index.htm
>    ```
>
> 5. Apache sees that `index.php` is a `.php` file → passes it to
>    the **PHP engine** (mod_php)
> 6. PHP executes `index.php`. Note: PHP does not even know that the
>    original URL was `/publishers`. It only sees
>    `index.php?route=publishers` — it is a **relay race**: `.htaccess`
>    rewrote the address and passed the baton, PHP picks up the baton
>    and knows what to do.
>
>    **But what does "executes" mean?** PHP is an **interpreter** that
>    reads the file from beginning to end, line by line, alternating
>    between two modes:
>    - sees `<?php ... ?>` → **executes** the code
>    - sees everything else → **copies** it to the output as-is
>
>    Let us follow our `index.php`:
>
>    ```php
>    <?php                            // PHP executes
>    $route = $_GET['route'] ?? 'home';
>    $route = trim($route, '/');
>    // ... checks $allowed, prepares $page
>    ?>                                // PHP finishes
>    <!DOCTYPE html>                   // HTML: output
>    <html lang="en">
>    <head>
>        ...
>    </head>
>    <body>
>        <header>...</header>
>        <main>
>            <?php require $page; ?>  // PHP steps back in
>        </main>                      // HTML again
>        <footer>...</footer>
>    </body>
>    </html>
>    ```
>
>    PHP alternates: execute, copy, execute, copy. The final result is
>    that single HTML string we saw in the server's actual response.
>
>    **Try it yourself:** PHP does not live only inside Apache. Like any
>    other interpreted language, we can run scripts from the command
>    line by prepending the interpreter to the file:
>
>    ```bash
>    php script.php          # same as you would do with:
>    python script.py
>    tclsh script.tcl
>    bash script.sh
>    ```
>
>    So we can do exactly what Apache + mod_php does:
>
>    ```bash
>    cd /var/www/html/bibliotheca/public
>    php -r "\$_GET['route']='publishers'; require 'index.php';"
>    ```
>
>    Out comes the exact same HTML. No magic: Apache does exactly
>    this — passes the route to PHP, PHP executes `index.php`, HTML
>    comes out.
>
> 7. PHP returns all the HTML to Apache as a **single string**
> 8. Apache adds the headers (`Content-Type`, `Content-Length`,
>    etc.) and sends the response to the browser
>
> So: Apache acts as the postman, `.htaccess` rewrites the address,
> PHP builds the HTML by assembling `index.php` + `pages/publishers.php`,
> and Apache packages the result with headers and ships it.
>
> The response is a text protocol with a fixed, mandatory order:
>
> Here is the **actual** response from our server. If you type on the
> command line `curl -s -D - http://localhost/bibliotheca/public/publishers`
> you will get this:
>
> ```
> HTTP/1.1 200 OK                        ← status line
> Date: Wed, 25 Mar 2026 08:05:38 GMT    ← header
> Server: Apache/2.4.66 (Debian)         ← header
> Content-Length: 1495                    ← header
> Content-Type: text/html; charset=UTF-8 ← header
>                                         ← empty line
> <!DOCTYPE html>                         ← body
> <html lang="en">
> <head>
>     <meta charset="UTF-8">
>     <title>Bibliotheca</title>
>     <link rel="stylesheet"
>           href=".../css/style.css">
> </head>
> <body>
>     <header>
>         <a href=".../" class="logo">
>             Bibliotheca</a>
>         <nav>
>             <a href=".../">Home</a>
>             <a href=".../publishers">
>                 Publishers</a>
>             ...
>         </nav>
>     </header>
>     <main>
>         <section>
>         <h2>Publishers</h2>
>         <table id="publisher-table">
>             <thead>...</thead>
>             <tbody></tbody>
>         </table>
>         </section>
>         <script src=".../publishers.js">
>         </script>
>     </main>
>     <footer>...</footer>
> </body>
> </html>
> ```
>
> (URLs are abbreviated with `...` for readability.
> The `curl` command will show you the full version.)
>
> If the empty line is missing or the headers come after the body, the
> response is malformed. The parts in detail:
> - the **status line** — the very first line: protocol, code, message
> - the **headers** — key-value pairs that tell the browser *how
>   to handle* what it is receiving. The ones in our response:
>   - `Date` — when the server generated the response
>   - `Server` — who the server is (software and version)
>   - `Vary` — tells caches that the response may vary
>   - `Content-Length` — the body is 1495 bytes long, so the browser
>     knows when it has finished receiving
>   - `Content-Type: text/html; charset=UTF-8` — the most important:
>     tells the browser the body is HTML encoded in UTF-8. Without this
>     the browser would not know what to do: is it HTML? Render it. Is
>     it JSON? Show it as text. Is it a PDF? Open the viewer
> - the **empty line** — separates headers and body, it is the delimiter
> - the **body** — the full HTML of the page, everything that
>   `index.php` produces. Note the empty `<tbody></tbody>` and the
>   `<script>` tag at the bottom: the table is empty because the data
>   will arrive *later*, when JavaScript makes a new request to the API
>
> You can see all of this (besides from the CLI as shown above, if you
> are a masochist) in the browser's **DevTools**: in Firefox press
> **F12**, go to the **Network** tab, reload the page, and click the
> first request. You will find the response broken down into its
> parts — the **Headers** tab for the status line and headers, the
> **Response** tab for the HTML body.
> "View page source" (right-click on the page) shows only the body
> and hides the rest.
>
> The `<script src="...">` tags inside that HTML will then trigger
> *new* requests to download the JavaScript files.
>
> Only at that point does the **rendering engine** (Blink in Chrome,
> Gecko in Firefox) come into play: it takes the HTML and transforms
> it into what you see on screen. When it encounters a `<script>` tag,
> it hands control to the **JavaScript engine** (V8 in Chrome) to
> execute the code.
>
> So the order is: network stack → rendering engine → JS engine.
> V8 arrives last.
>
> **Keep in mind:** many actors read your files, each one understands
> only its own part:
>
> | Who reads | What it reads | When |
> |-----------|--------------|------|
> | Apache | `.htaccess` | when it cannot find the requested file |
> | PHP | `index.php`, `pages/*.php`, `src/*.php` | when Apache passes it a `.php` |
> | Rendering engine | the HTML produced by PHP | when the response reaches the browser |
> | V8 (JavaScript) | `js/*.js` | when the rendering encounters `<script>` |
>
> Knowing who reads what and when is the foundation of **debugging**:
> if the page does not load, the problem is in Apache or `.htaccess`.
> If the HTML is wrong, the problem is in PHP. If the table stays
> empty, the problem is in JavaScript or the API. Each actor has its
> moment — and its own errors.

3. The browser receives the response — that block of text from
   `HTTP/1.1 200 OK` down to `</html>`. It splits it apart: headers
   on one side, body on the other (separated by the empty line).

   The **rendering engine** comes into play. What is it? It is the
   browser component that transforms HTML text into pixels on screen.
   It takes the HTML, builds a tree of objects in memory (the DOM —
   Document Object Model), applies CSS to calculate positions, colors
   and sizes, and finally draws everything on screen.

   Each browser has its own:

   | Browser | Rendering engine | JavaScript engine |
   |---------|-----------------|-------------------|
   | Firefox | Gecko | SpiderMonkey |
   | Chrome | Blink | V8 |
   | Chromium | Blink | V8 |
   | Safari | WebKit | JavaScriptCore (Nitro) |
   | Edge | Blink | V8 |

   Blink is a fork of WebKit, which in turn is a fork of KHTML
   (from the KDE project). Chrome, Chromium, Edge, and Opera all use
   Blink — Firefox and Safari are the only ones left with their own
   engine.

   The rendering engine reads the HTML top to bottom and builds the
   page piece by piece:

   - `<head>` → encounters `<link rel="stylesheet" href="...style.css">` →
     fires a **new request** to the server to download the CSS
   - `<body>` → builds the structure: `<header>`, `<nav>`, `<main>`,
     the `<table>` with an empty `<tbody></tbody>`...
   - at the bottom, just before `</body>`, encounters
     `<script src=".../js/publishers.js">` →
     fires a **new request** to download the JS
   - the rendering engine stops, hands control to V8
   - V8 executes `publishers.js`

   **Why is the `<script>` at the bottom and not in `<head>`?** If it
   were at the top, the rendering engine would stop right away to
   download and execute the JavaScript — **before** having built the
   page. The JS would look for the table `#publisher-table` in the
   DOM, but would not find it because the HTML below has not been
   read yet. By putting it at the bottom: the HTML is all read, the
   DOM is complete, the JS runs and finds every element it needs. And
   the user sees the page structure right away (even if empty) instead
   of a blank screen.

   **What does `publishers.js` do?** Here is where **object-oriented
   programming** (OOP) comes in, which is a programming style.
   To understand it you need to read from the end of the file.
   The last line of the file says:

   ```javascript
   const publishersView = new PublishersView();
   ```

   `PublishersView` is a **class** — a blueprint, a cookie cutter.
   `new` creates a copy of the PublishersView object called an instance.
   Objects have properties (variables) and methods (functions).
   When the object is born, JavaScript always runs first, if it exists,
   the special method `constructor()`:

   ```javascript
   constructor() {
       this.API = '/bibliotheca/public/api/publishers.php';
       this.table = document.querySelector('#publisher-table tbody');
       this.load();
   }
   ```
   `this` is the representation of the object itself, equivalent to `self` in Python.

   The constructor does three things:
   - saves the API address in `this.API` (a **property** of the
     object — a piece of data that belongs to it, a variable)
   - looks up the `<tbody>` of the table in the DOM and saves it
     in `this.table` (another property)
   - calls `this.load()` — a **method** of the object (a function
     that belongs to it)

   `this` is the object itself — "me". `this.API` is "my API
   address", `this.load()` is "my load method".

   **The DOM and references:** when the rendering engine reads the
   HTML, it builds a tree structure of objects in memory — the
   **DOM** (Document Object Model). Every HTML tag becomes an object
   in memory: `<table>` is an object, `<tbody>` is an object,
   `<tr>` is an object, and so on. They are all connected to each
   other in a parent-child hierarchy, just like the HTML they come
   from.

   When JavaScript does:

   ```javascript
   this.table = document.querySelector('#publisher-table tbody');
   ```

   it works because in `pages/publishers.php` **we** wrote:

   ```html
   <table id="publisher-table">
       ...
       <tbody></tbody>
   </table>
   ```

   That `id="publisher-table"` is a **contract** between the HTML
   and JavaScript: if the id were missing, or had a different name,
   JavaScript would find nothing. The two files must speak the same
   language.

   **But what is `id`?** It is a different thing for each actor:

   | Who sees it | What it is | How they use it |
   |-------------|-----------|----------------|
   | HTML | a tag **attribute** | identifies the element uniquely on the page |
   | CSS | a **selector** (`#publisher-table`) | selects the element to apply styles |
   | JavaScript | a **search key** | `querySelector('#publisher-table')` uses it to find the object in the DOM |
   | PHP | just **text** | writes it into the HTML but does not know what it means |

   `id` in HTML must be **unique** within a single page — there
   cannot be two elements with the same `id`. It is like a license
   plate: one plate, one car. One id, one element. Unlike a license
   plate though, it is not a number — it is a **name** you choose:
   `publisher-table`, `form-title`, `btn-delete` are all valid ids.
   What matters is that it is unique on the page. As good practice
   it is wise to have ids unique across the whole project too — we do
   this by using the entity prefix: `publisher-table`,
   `book-table`, `category-table`. Nobody steps on anyone's toes.

   For things that repeat the same way across multiple pages we use
   **classes** (`class`): a `.btn` button, a `.row-disabled` disabled
   row, a `.section-header` structure. The `id` identifies the unique
   thing, the `class` identifies the type.

   Different pages, however,
   can have the same ids without problems — each page has its own
   separate DOM.

   **Only `id`?** No. `querySelector` uses the same syntax as CSS
   selectors and can search for anything:

   | Selector | What it looks for | Example |
   |----------|------------------|---------|
   | `#name` | by `id` | `querySelector('#publisher-table')` |
   | `.name` | by `class` | `querySelector('.btn')` |
   | `tag` | by HTML tag | `querySelector('tbody')` |
   | `[attr]` | by any attribute | `querySelector('[data-id="3"]')` |
   | combinations | descendants | `querySelector('#publisher-table tbody')` |

   The key difference: an `id` is unique, a `class` is not. If you
   want one specific element you use `id`, if you want a group of
   similar elements you use `class`.

   **Naming conventions:** in the project we use **kebab-case**
   (words separated by hyphens) for both `id` and `class`:
   `publisher-table`, `section-header`, `row-disabled`.
   Not `publisherTable` (camelCase) nor `publisher_table` (snake_case).
   Kebab-case is the standard convention in HTML and CSS.

   In short: selectors are the **point of contact** between HTML and
   JavaScript. HTML defines elements with attributes (`id`, `class`,
   etc.), JavaScript finds them via selectors. Without that contract,
   JavaScript would have no way of knowing where to put its hands in
   the DOM. It is the **injection point**: JavaScript knows *what* to
   put (the data from the API) and the selector tells it *where* to
   put it.

   `querySelector` does not copy the `<tbody>` — it gets a
   **reference** to that object in the DOM. It is like a pointer in C:
   `this.table` points directly at the object in memory. If JavaScript
   adds a child to `this.table`, it is modifying the DOM — and the
   rendering engine updates the screen accordingly.

   And that reference gives access to **the entire subtree** — all
   children, grandchildren, etc. The DOM is a tree: if you point to
   a node, you can navigate anywhere below (and above too):

   ```javascript
   this.table = document.querySelector('#publisher-table tbody');

   this.table.appendChild(row)        // add a child
   this.table.querySelector('tr')     // search inside
   this.table.children                // read all children
   this.table.textContent = ''        // clear all content
   this.table.parentElement           // go up to the parent (<table>)
   ```

   You navigate in both directions, like in a tree structure.

   And not just data — JavaScript can also **format**, that is,
   modify the appearance of elements. In our `render()` we already
   do it:

   ```javascript
   if (publisher.status === 0) {
       row.className = 'row-disabled';
   }
   ```

   **But how does it work?** JavaScript does not read `style.css`.
   It does not even know what `row-disabled` looks like visually.
   What it does is add the **class name** to the element in the DOM.
   Then another contract kicks in — three things in three different
   places:

   1. In **`style.css`** — the rule `.row-disabled { ... }` that
      defines the appearance (gray, faded, etc.)
   2. In **`publishers.js`** — `row.className = 'row-disabled'` that
      assigns the class at runtime
   3. In **your head** — the connection between the two

   JavaScript says: "this element has the class `row-disabled`."
   CSS says: "elements with class `row-disabled` are gray."
   The rendering engine puts them together and draws.

   Note: that `row-disabled` class does not exist in the original
   HTML sent by the server — JavaScript adds it at runtime. If you
   rename the class in style.css to something like `rows-disableding`
   and forget to rename it in JS (or vice versa), nobody warns you —
   it simply does not work. It is another one of those unwritten
   contracts, like the `id` between HTML and JS.

   We can also act directly on styles without going through classes:

   ```javascript
   element.style.color = 'red';           // change color
   element.style.display = 'none';        // hide
   element.classList.add('active');        // add a class
   element.classList.remove('active');     // remove it
   ```

   JavaScript has full access to the DOM: structure, content, and
   appearance.

   **Fundamental mindset shift.** When we study, they teach us HTML
   for structure and CSS for appearance — as if they were fixed,
   separate roles. But in a web application it is not like that:

   - **Without JavaScript**: the page is a static document. The HTML
     that arrives from the server is what you see. To change anything
     you must reload the entire page from the server.
   - **With JavaScript**: the page is **alive**. After it arrives,
     JavaScript can modify everything — create HTML (`createElement`),
     change CSS (`classList`, `style`), load data (`fetch`), react to
     clicks — without ever reloading the page.

   HTML and CSS define the **initial state** — the skeleton and the
   outfit. JavaScript is what changes them **afterwards**, at runtime,
   based on what happens: a click, data arriving, an error. It is the
   transition from a document to an **application**.

   In our case it is obvious: the HTML from `publishers.php` arrives
   with an empty table. Without JavaScript you would see only the
   headers and nothing else. It is JavaScript that transforms it into
   something useful, and it does so by manipulating the same DOM that
   HTML and CSS built.

   That is why writing `this.table.appendChild(row)` in the
   `render()` method makes the row appear on the page: we are not
   manipulating HTML as text, we are manipulating **live objects in
   memory** that the browser keeps synchronized with the screen —
   which has the undeniable advantage of not reloading the entire
   page but only what is needed within it, in the DOM.

   Who keeps them synchronized? The **rendering engine**, as always.
   When something changes in the DOM (an `appendChild`, a text change,
   a CSS class added), the engine recalculates the layout
   (**reflow**) and redraws the pixels on screen (**repaint**).
   JavaScript touches the DOM → the rendering engine notices →
   redraws. All automatic.

   **The chain of methods.** The three methods in the class call
   each other in cascade, in an order we decided:

   ```
   new PublishersView()
       └── constructor()          ← the object is born
               ├── saves this.API
               ├── saves this.table
               └── calls this.load()
                       ├── fetch() → request to the server
                       ├── waits for the response (await)
                       └── calls this.render(publishers)
                               └── for each publisher creates <tr> in the DOM
   ```

   Nothing automatic: it is us who write `this.load()` in the
   constructor, and inside `load()` we write `this.render()`.
   JavaScript executes what we tell it, in the order we tell it.

   **What does `load()` do?** (`load`, like `render`, is a name
   we made up — we could have called it `carica`, `fetchData`,
   anything.)

   Here we go back to the server for the **second time**. The first
   trip brought us the HTML (the page structure). This second trip
   will bring us the **data**.

   ```javascript
   async load() {
       const response = await fetch(this.API);
       const publishers = await response.json();
       this.render(publishers);
   }
   ```

   **`async` — why?** Calling a server takes **time**: the request
   must travel, the server must process, the response must come back.
   `async` before the method tells JavaScript: "this function does
   things that are not instantaneous." Without `async` you could not
   use `await` inside. And without `await`, JavaScript would not wait
   for the response — it would move on and `publishers` would be
   empty.

   Meanwhile the browser does not freeze — the user can keep
   interacting with the page. When the response arrives, JavaScript
   picks up where it left off.

   **What does the user see?** The page appears with an empty table
   (just the "Name" headers), and after a moment the rows with data
   appear. On localhost it is so fast you barely notice. But on a slow
   connection or a remote server you would see an instant of
   "emptiness" before the data arrive — that is the time of the
   second trip: JavaScript asked the server for data and is waiting.

   Without `async/await` it would be worse: the browser would
   **freeze** — no clicks, no scrolling, nothing at all — until the
   server responds. The page would seem "frozen." With `async/await`
   instead, the page stays alive and responsive: the data arrive when
   they arrive, and in the meantime the user can do other things.

   If you want to see the delay with your own eyes, open DevTools
   (F12), **Network** tab, and reload the page: you will see the
   request to `publishers.php` with its response time in milliseconds.

   Line by line:
   - `this.API` is the property we saved in the constructor that
     tells JavaScript which file to use and where it is:

    constructor() {
        this.API = '/bibliotheca/public/api/publishers.php';
        ...}


   - `fetch()` is a **native** JavaScript function (not invented by
     us) that takes that address and generates an HTTP request — the
     same mechanism as when the browser goes to a URL you type in the
     bar, but done by JavaScript in the background, without reloading
     the page. `fetch` is the **bridge** between JavaScript and PHP:
     the two do not know each other, do not speak the same language,
     run in different places (browser vs server). The only way they
     can communicate is HTTP — like two people writing letters to
     each other
   - `await` says "wait for the response to arrive before continuing"
   - `response.json()` reads the response body and transforms it
     from JSON text into a usable JavaScript object
   - `this.render(publishers)` passes the data to the method that
     will fill the table

   So `fetch(this.API)` generates a request
   `GET .../api/publishers.php`.

   **Why GET?** Because `fetch()` uses GET by default — the HTTP
   method for "give me something." If you want a different method
   you must say so explicitly:

   ```javascript
   fetch(url)                          // GET (default) — read
   fetch(url, { method: 'POST' })      // POST — create
   fetch(url, { method: 'PUT' })       // PUT — update
   fetch(url, { method: 'DELETE' })    // DELETE — delete
   ```

   We are just asking for the list of publishers, so GET.

   **Why does the doorman not step in this time?** Because
   `api/publishers.php` is a **real** file inside `public/`.
   Remember the two `RewriteCond`? They say "step in only if it is
   NOT a file (`!-f`) and NOT a directory (`!-d`)." Apache finds the
   file and serves it directly.

   The first trip asked for `/publishers` (without `.php`) — it does
   not exist as a file, so the rewrite was needed. This second trip
   asks for `/api/publishers.php` — it exists, so it goes straight
   through.

   **The second trip — inside the API.** Opening the file
   `api/publishers.php`, the first thing we find in the comment is:

   ```
   Publisher REST API endpoint.
   ```

   Three words that deserve an explanation:

   - **API** (Application Programming Interface) — an interface that
     lets two programs talk to each other. In our case JavaScript
     (in the browser) talks to PHP (on the server). It is not a page
     for the user — it is a "service" for code.
   - **REST** (Representational State Transfer) — a way of organizing
     APIs using HTTP methods: GET to read, POST to create, PUT to
     update, DELETE to delete. A single address
     (`/api/publishers.php`), four different operations depending on
     the method.
   - **Endpoint** — the arrival point, the address to knock on.
     `/api/publishers.php` is an endpoint, `/api/books.php` is
     another endpoint. Each entity in the project has its own.

   Note: API endpoints **are not pages**. The user never visits them
   in the browser — they have no HTML, no graphics. They are files
   that respond only with data (JSON) and talk only to JavaScript.
   If you tried opening
   `http://localhost/bibliotheca/public/api/publishers.php` in the
   browser you would see only raw JSON text, not a page.

   What happens when Apache receives `GET /api/publishers.php`? PHP
   executes it top to bottom, as it did with `index.php`. Let us
   follow it:

   ```php
   // 1. Loads the necessary code (outside the public folder!)
   require_once __DIR__ . '/../../src/DBMS.php';
   require_once __DIR__ . '/../../src/Publisher.php';

   // 2. Tells the browser: "what I am about to send you is JSON"
   header('Content-Type: application/json; charset=utf-8');

   // 3. Opens the connection to the database
   $db = new DBMS(__DIR__ . '/../../sql/bibliotheca.db');

   // 4. Creates a Publisher object (PHP uses OOP too!)
   $publisher = new Publisher($db);

   // 5. Reads the HTTP method of the request
   $method = $_SERVER['REQUEST_METHOD'];  // → 'GET'

   // 6. If it is GET with no parameters, returns all publishers
   if ($method === 'GET') {
       echo json_encode($publisher->getAll());
   }
   ```

   Note the `require_once __DIR__ . '/../../src/...'` — the API goes
   up two directories to reach `src/`, which sits outside `public/`.
   The browser cannot get there, but PHP can.

   **What does `$publisher->getAll()` do?** We are in the model
   `Publisher.php` — another OOP class, this time in PHP:

   ```php
   public function getAll(): array
   {
       return $this->db->query(
           "SELECT publisher_id, name, status
            FROM publisher
            ORDER BY name"
       );
   }
   ```

   The model asks the database (via `DBMS`) to execute the SQL
   query. SQLite returns the rows, PHP transforms them into JSON
   with `json_encode()` and sends them back.

   Here is the **actual** response from our server
   (try it with `curl` on the API URL):

   ```json
   [
       {"publisher_id":3,"name":"Addison-Wesley","status":1},
       {"publisher_id":1,"name":"Adelphi","status":1},
       {"publisher_id":4,"name":"Mondadori","status":1},
       {"publisher_id":2,"name":"Penguin Books","status":1}
   ]
   ```

   This time the response is not HTML — it is **JSON** (JavaScript
   Object Notation). But what is it concretely?

   JSON is a **text format** for representing data. It uses few
   rules:
   - `{}` for an **object** (a set of key-value pairs)
   - `[]` for an **array** (an ordered list of values)
   - keys are always strings in double quotes
   - values can be strings, numbers, true, false, null, objects,
     or arrays

   Let us break down our response:

   ```json
   [                                          ← array (list of publishers)
       {                                      ← object (one publisher)
           "publisher_id": 3,                 ← key: value (number)
           "name": "Addison-Wesley",          ← key: value (string)
           "status": 1                        ← key: value (number)
       },
       {                                      ← another publisher
           "publisher_id": 1,
           "name": "Adelphi",
           "status": 1
       },
       ...
   ]
   ```

   Why JSON and not HTML? Because these are **pure data**, with no
   visual structure — no `<table>`, no `<tr>`, no formatting. It is
   JavaScript that will decide how to present them. JSON is the
   universal format for data exchange on the web: lightweight,
   readable by humans and machines, and JavaScript can read it
   natively with `response.json()`.

   JavaScript receives the JSON and calls `this.render(publishers)` —
   another method of the same class.

   **`render()` — we made the name up ourselves.** It is not a
   reserved word in JavaScript. We could have called it `draw`,
   `show`, `build`. "Render" is a common convention in the
   programming world (it means transforming data into something
   visible), but the choice is ours.

   What does it do concretely? Remember that the HTML had already
   prepared the table's **skeleton** — the header with "Name" and
   an empty `<tbody>`:

   ```html
   <table id="publisher-table">
       <thead>
           <tr>
               <th>Name</th>
               <th></th>
           </tr>
       </thead>
       <tbody></tbody>     ← empty, waiting for data
   </table>
   ```

   `render()` takes the JSON data and for each publisher builds the
   HTML elements (`<tr>`, `<td>`, `<button>`) with JavaScript and
   injects them into the `<tbody>` at runtime. It does not rewrite
   the page — it fills the skeleton that HTML had already prepared.

   To sum up, the `PublishersView` class has:
   - **properties**: `this.API`, `this.table` (the data)
   - **methods**: `constructor()`, `load()`, `render()` (the behaviors)

   This is the heart of OOP: grouping **data and behaviors** that
   belong to the same thing into a single object.

   Now the table has data. The page is complete.

## CRUD — Create, Read, Update, Delete

So far we have followed the journey of a request that **reads**
data: you click Publishers, the HTML arrives, JavaScript calls GET
on the API, the JSON data arrive, the table fills up. This read
operation is called **Read** — the R in CRUD.

But an application must also create, modify, and delete data.
These four operations are called **CRUD** and they are the heart of
any application that manages data.

The framework is the same: fetch → API → PHP → SQLite → response.
What changes is the HTTP method and the **direction of data**:

| Operation | Method | Data goes... |
|-----------|--------|-------------|
| **Read** (list) | GET | server → browser |
| **Create** (new) | POST | browser → server |
| **Update** (edit) | PUT | browser → server |
| **Delete** (remove) | DELETE | browser → server |

**A tale of forgotten methods.** GET, POST, PUT, and DELETE have
existed since the origins of HTTP (the 90s). But for years the web
used almost only GET and POST. The reason? HTML forms support only
those two — if you write `<form method="PUT">` it does not work.
So "old-school" applications did everything with POST and
distinguished the operation with a hidden field like
`<input name="action" value="delete">`. One method for everything,
like using a single knife to cut, spread, and screw.

REST gave PUT and DELETE their dignity back. REST is not a
technology, not a piece of software, you do not install it — it is a
**set of architectural principles**, a way of thinking about APIs
proposed by Roy Fielding in his doctoral dissertation in 2000. It
simply says: use HTTP methods for what they mean, identify resources
with URLs, and communicate using standard formats (like JSON). Each
HTTP method does what it was designed for. But this is only possible
thanks to JavaScript and `fetch`, which can use **any** HTTP method.
HTML forms alone cannot — this is one of the reasons JavaScript
became indispensable in modern web applications.

If for Read we used the **plural** files: `publishers.php` (page) +
`publishers.js` (list), for Create, Update, and Delete we use the
**singular** files: `publisher.php` (page with the form) +
`publisher.js` (form logic). One form for three operations.
This is **our convention** — plural for lists, singular for
detail/form. No language or framework imposes it; we chose it
because it is intuitive and consistent.

### Create — 'Add new publisher'

1. The user clicks "Add new publisher" in the list. It is a simple
   link: `<a href="/bibliotheca/public/publisher">`. No
   JavaScript — it is pure HTML, a first trip just like the one we
   already know.

2. The browser loads `/publisher` — it is the exact same first trip
   as before: `.htaccess` rewrites →
   `index.php?route=publisher` → PHP executes `index.php` which loads
   `pages/publisher.php` into the layout → the form HTML arrives:

   ```html
   <h2 id="form-title">Add Publisher</h2>
   <form id="publisher-form" novalidate>
       <input type="hidden" id="publisher-id" value="">
       <div>
           <label for="publisher-name">Name</label>
           <input type="text" id="publisher-name" required autofocus>
           <span class="error" id="publisher-name-error"></span>
       </div>
       <div id="status-group" class="checkbox-group" hidden>
           ...checkbox Active...
       </div>
       <div class="form-actions">
           <button type="submit">Save</button>
           <button type="button"
                   id="btn-delete"
                   class="btn-delete"
                   hidden>Delete</button>
           <a href=".../publishers">Cancel</a>
       </div>
   </form>
   <script src=".../js/publisher.js"></script>
   ```

   **But does the `<form>` only support GET and POST?** Yes — and
   in fact we **never submit that form with HTML**. JavaScript
   intercepts the submit with `e.preventDefault()` (we will see it
   shortly) and takes control: it reads the values from the fields
   and uses `fetch` with whatever method it wants — POST, PUT,
   DELETE. The `<form>` HTML tag only serves as a **container** to
   group the fields and for the submit button. The actual submission
   is done by JavaScript.

   And `novalidate`? It tells the browser "do not do field validation
   yourself" — we will do it with JavaScript, so we control the error
   messages and where to show them.

   Note that these two things live in **different files**:
   - `novalidate` → in `pages/publisher.php` (HTML)
   - `e.preventDefault()` → in `js/publisher.js`, which the browser
     downloads thanks to the `<script>` tag in the HTML

   The browser receives everything together — the form, the fields,
   and at the bottom the `<script>`. It is a single block of HTML
   (the first trip). When the rendering engine reaches the `<script>`,
   it downloads `publisher.js` and executes it. At that point
   JavaScript sees the DOM that the rendering engine built from the
   HTML — and in the DOM is the form with all its fields. Then it
   is us with `querySelector` who tell it where to look and what to
   do (`addEventListener`). Same dynamic as the list — only the
   loaded JS file changes.

   Selectors in the DOM serve JavaScript both for **injecting** data
   and for **grabbing** them:

   ```javascript
   // GRAB — reads what the user typed in the field
   const name = this.inputName.value;

   // INJECT — writes into the field (in edit mode)
   this.inputName.value = publisher.name;
   ```

   Same DOM reference, two directions. In the list it was only
   server → DOM. In the form it is **bidirectional**.

   **The concrete link:** in the constructor JavaScript writes:

   ```javascript
   this.inputName = document.querySelector('#publisher-name');
   ```

   And in the HTML that selector points to:

   ```html
   <input type="text" id="publisher-name" maxlength="100" required autofocus>
   ```

   Each attribute of that `<input>` adds a behavior:
   - `type="text"` — it is a text field
   - `id="publisher-name"` — the selector JavaScript uses to find it
   - `maxlength="100"` — the browser will not let you type more than
     100 characters
   - `required` — the field is mandatory (but we disabled that with
     `novalidate` on the form and validate from JavaScript instead)
   - `autofocus` — when the page loads, the cursor is already inside,
     ready to type

   **But who decides which HTTP method to use?** No automation — it
   is us in the JavaScript code. The logic is in the `save()` method
   of `publisher.js` and it is based on that hidden field
   `publisher-id`:

   - hidden field **empty** → we are creating → `method: 'POST'`
   - hidden field **has a value** (an id) → we are editing →
     `method: 'PUT'`
   - the user clicks the Delete button → calls a different method
     (`remove()`) that uses `method: 'DELETE'`

   All decided by us, written by us. JavaScript does not know what a
   publisher is — it only knows how to read a field and choose an
   HTTP method based on what it finds there.

   Note three things:
   - `<input type="hidden" id="publisher-id" value="">` — a
     **hidden** field with an empty value. It will be the way to tell
     whether we are creating (empty) or editing (with the id inside).
   - `id="status-group"` with `hidden` — the Active checkbox is
     hidden in creation mode; we will only see it in edit mode.
   - `id="btn-delete"` with `hidden` — the Delete button is hidden;
     it will appear only in edit mode.

3. At the bottom is the `<script>` — `publisher.js` starts. Here
   too we read from the last line:

   ```javascript
   const publisherForm = new PublisherForm();
   ```

   The `PublisherForm` class has a richer constructor than
   `PublishersView` — it must handle the form, the events, and two
   modes (creation and editing):

   ```javascript
   constructor() {
       // WHAT TO DO — the API address
       this.API = '/bibliotheca/public/api/publishers.php';

       // WHAT TO WORK WITH — references to the form fields
       this.form = document.querySelector('#publisher-form');
       this.inputId = document.querySelector('#publisher-id');
       this.inputName = document.querySelector('#publisher-name');
       this.inputStatus = document.querySelector('#publisher-status');
       this.statusGroup = document.querySelector('#status-group');
       this.btnDelete = document.querySelector('#btn-delete');
       this.title = document.querySelector('#form-title');
       // WHICH EVENTS TO LISTEN FOR
       // SAVE
       this.form.addEventListener('submit', (e) => {
           e.preventDefault();
           this.save();
       });
       // DELETE
       this.btnDelete.addEventListener('click', () => {
           this.remove();
       });

       // WHICH MODE ARE WE IN — creation or editing?
       this.checkEdit();
   }
   ```

   Same logic: save the DOM references (all those `querySelector`
   calls) and then do three new things:

   - **`addEventListener('submit', ...)`** — when the user clicks
     "Save", instead of letting the browser submit the form normally
     (which would reload the page), it intercepts the event with
     `e.preventDefault()` and calls `this.save()`. Control stays
     with JavaScript.
   - **`addEventListener('click', ...)`** — when the user clicks
     "Delete", it calls `this.remove()`.
   - **`this.checkEdit()`** — checks whether the URL has `?id=`.

     ```javascript
     checkEdit() {
         const params = new URLSearchParams(window.location.search);
         const id = params.get('id');
         if (id) {
             this.loadRecord(parseInt(id));
         }
     }
     ```

     `window.location.search` is the part of the URL after the `?`.
     If the URL is `/publisher?id=3`, `search` is `?id=3`.
     `URLSearchParams` is a **native** JavaScript class that knows
     how to read those parameters — `params.get('id')` returns `'3'`.

     Note: `checkEdit()` does not return anything — it does not
     compute a value. It **acts**: it is `params.get('id')` that
     returns either the string `'3'` or `null`, and `checkEdit()`
     uses that result to decide what to do, fail fast fail safe.

     If the id is there → we are in edit mode, it calls
     `loadRecord()` which makes a trip to the server to fetch the
     publisher's data and fill the form. If it is not there → it
     does nothing, the form stays as it arrived from HTML: title
     "Add Publisher", name field empty, hidden field empty, checkbox
     and Delete button hidden. We are in **creation mode by
     default** — no need to say it explicitly, just do nothing.

     **What if someone cheats?** If you manually edit the URL and
     type `/publisher?id=999` (an id that does not exist),
     `checkEdit()` finds the id and calls `loadRecord(999)`. The
     trip to the server begins: the API calls
     `$publisher->getById(999)`, which returns `null`. PHP responds
     with `404 Not Found`:

     ```php
     http_response_code(404);
     echo json_encode(['error' => 'Not found']);
     ```

     In JavaScript `response.ok` is `false` (because 404 is not ok),
     we enter the `catch` and the user sees an alert: "Unable to load
     record." The form stays empty — it did not crash, it is not
     elegant, but it is **safe**: the server gave no data, JavaScript
     wrote nothing into the DOM.

4. The user types "Feltrinelli" in the Name field and clicks Save.
   JavaScript calls `this.save()` — a method we wrote (like `load`
   and `render`), declared `async` because it will need to `fetch`
   the server and wait for the response.

   First thing `save()` does: **validation**.

   ```javascript
   if (!this.validate()) {
       return;
   }
   ```

   `validate()` checks that the field is not empty. If it is, it
   shows the error in red below the field (remember that `<span
   class="error">`?) and stops. No trip to the server for bad
   data — we block it right away on the browser side.

5. If validation passes, `save()` looks at the hidden field
   `publisher-id`. It is empty → we are creating. The third trip
   to the server begins:

   ```javascript
   const payload = {name: name};

   response = await fetch(this.API, {
       method: 'POST',
       headers: {'Content-Type': 'application/json'},
       body: JSON.stringify(payload),
   });
   ```

   This time `fetch` is different from before:
   - `method: 'POST'` — it is no longer GET (read), it is POST
     (create)
   - `headers: {'Content-Type': 'application/json'}` — tells the
     server "I am sending you JSON"
   - `body: JSON.stringify({name: 'Feltrinelli'})` — the body of
     the request, with the data. In GET there was no data — now
     they go from browser to server.
     `{name: 'Feltrinelli'}` is a JavaScript object with a key-value
     pair — like a Python dictionary. For the publisher one is enough,
     but to save a book you need several, so we use a `payload`
     variable to hold them:

     ```javascript
     const payload = {
         publisher_id: 1,
         category_id: 3,
         title: 'Il Sentiero Dei Nidi Di Ragno',
         pages: 180,
         published: 1947,
         author_ids: [2, 5],
     };
     ```

     Six pairs, including an array of author ids. But why for the
     publisher do we write `JSON.stringify({name: name})` directly,
     while for the book we first create `const payload = {...}` and
     then do `JSON.stringify(payload)`? No functional difference — it
     is just organization. It is like writing a letter: you can write
     it directly on the envelope (if it is short) or write it on a
     sheet of paper and then slip it into the envelope (if it is
     long). The letter is the same.

     `JSON.stringify()` transforms that object into a JSON string
     that travels to the server. PHP on the other side will do the
     inverse operation with `json_decode()` to get back the key-value
     pairs.

     **Want to see what gets sent?** Temporarily add a
     `console.log(payload)` before the `fetch` and open the
     **Console** in DevTools (F12):

     ```javascript
     console.log(payload);   // ← add this line
     response = await fetch(this.API, { ... });
     ```

     You will see the object about to leave for the server —
     expandable, clickable. It is a study and debug tool, not code
     to leave in production.

     **Watch out:** after saving, `save()` does a redirect
     (`window.location.href = ...`) that reloads the page and clears
     the Console — you lose the log. Two solutions:
     - in Firefox DevTools, in the Console, check **"Persist Logs"**
       — logs survive navigation
     - or add a temporary `return` after the `console.log` to block
       the flow:

     ```javascript
     console.log(payload);
     return;  // ← stop here, the fetch does not fire
     ```

     That way you see the payload without the page changing. Then
     remove the `return` when you are done.

     In the Console you will see the expandable object. The top part
     is **your** data (`title`, `pages`, `author_ids`, etc.). At the
     bottom you will find `<prototype>: Object` with methods like
     `toString()`, `hasOwnProperty()` — those are "factory-default,"
     all JavaScript objects have them. We did not write them; you can
     ignore them.

6. The API receives the request — the same file we specified in the
   `publisher.js` constructor with
   `this.API = '.../api/publishers.php'`.
   Let us see what happens inside:

   ```php
   $method = $_SERVER['REQUEST_METHOD'];  // → 'POST' in this case
   ```

   **Watch out:** it is not JavaScript that fills `$_SERVER`. The
   chain is this: JavaScript specifies `method: 'POST'` in `fetch` →
   the HTTP request travels with the POST method → **Apache**
   receives it, extracts the information, and passes it to PHP via
   **superglobal variables**.

   `$_SERVER` is a PHP **superglobal array** — a variable that PHP
   creates automatically at the start of every request. We do not
   create them; they are always there — that is how it works because
   PHP's creators decided it would work that way. Each one fills
   from a **different source**:

   | Superglobal | Where it gets its data |
   |-------------|----------------------|
   | `$_SERVER` | from the HTTP request (method, headers, IP, etc.) |
   | `$_GET` | from URL parameters (`?route=publishers`) |
   | `$_POST` | from the body of an HTML form with `method="POST"` |
   | `$_FILES` | from files uploaded via form |
   | `$_COOKIE` | from browser cookies |

   You might remember `$_POST` from classic HTML forms:
   `<form method="POST">` → PHP fills `$_POST`.
   `<form method="GET">` → PHP fills `$_GET`.
   But in Bibliotheca **we do not use `$_POST`** — we do not submit
   forms with HTML (remember `e.preventDefault()`?). We send JSON
   with `fetch`, and we read it with
   `file_get_contents('php://input')`, which reads the raw request
   body.

   Who does what in this chain:

   | Who | What they do | Example |
   |-----|-------------|---------|
   | **JavaScript** | decides the method and prepares the data | `fetch(url, {method: 'POST', body: ...})` |
   | **HTTP** | transports the request with the method and body | `POST /api/publishers.php` + JSON payload |
   | **Apache** | receives the request, extracts the information | reads the method, headers, body |
   | **PHP** | reads the information from superglobals | `$_SERVER['REQUEST_METHOD']` → `'POST'` |

   **Be careful not to get confused:** `fetch` does not create
   `$_SERVER`. `fetch` does not even know PHP exists — it sends an
   HTTP request and that is it. The full circle:

   1. **We** write `method: 'POST'` in `fetch`
   2. **The browser** builds the HTTP request with `POST` as the
      first word of the first line
   3. **The request** travels as text over the network
   4. **Apache** reads the first line and sees `POST` — like reading
      "URGENT" on the envelope of a letter
   5. **PHP** finds it in `$_SERVER['REQUEST_METHOD']`

   Each actor does only its part — none of them sees the others.

   But how does it arrive? As a **single block of text** over the
   TCP connection — the same format as the response we took apart
   at the beginning of this notebook, but in the opposite direction
   (from browser to server):

   ```
   POST /bibliotheca/public/api/publishers.php HTTP/1.1  ← method + path
   Host: localhost                                        ← header
   Content-Type: application/json                         ← header
                                                          ← empty line
   {"name":"Feltrinelli"}                                 ← body (the payload)
   ```

   Apache receives this text, splits it apart, and distributes it
   to PHP in the superglobals:
   - `POST` → `$_SERVER['REQUEST_METHOD']`
   - `application/json` → `$_SERVER['CONTENT_TYPE']`
   - `{"name":"Feltrinelli"}` → readable with
     `file_get_contents('php://input')`

   You can see all of this in DevTools (F12), **Network** tab: click
   on the POST request and you will find:
   - **Headers** tab → the method, URL, headers
   - **Request** tab → the body (the JSON payload you sent)
   - **Response** tab → what the server answered

   Request and response, round trip — all visible.
   They have the same structure. Example: **creating** a publisher.

   The request (browser → server):

   ```
   POST /api/publishers.php HTTP/1.1
   Host: localhost
   Content-Type: application/json

   {"name":"Feltrinelli"}
   ```

   The response (server → browser):

   ```
   HTTP/1.1 200 OK
   Content-Type: application/json

   {"publisher_id":5,"name":"Feltrinelli"}
   ```

   Example: **reading** the list.

   The request (no body — just asks "give me everything"):

   ```
   GET /api/publishers.php HTTP/1.1
   Host: localhost
   ```

   The response (the body has the data):

   ```
   HTTP/1.1 200 OK
   Content-Type: application/json

   [{"publisher_id":1,...},
    {"publisher_id":2,...}]
   ```

   Same structure: first line, headers, empty line, body.
   Only the direction and content change.

   So `$_SERVER['REQUEST_METHOD']` contains `'POST'` not because
   JavaScript wrote it there, but because Apache read the method
   from the HTTP request and passed it to PHP.

   The same file handles all operations with an `if/elseif` chain:

   ```php
   if ($method === 'GET') { ... }         // Read
   elseif ($method === 'POST') { ... }    // Create
   elseif ($method === 'PUT') { ... }     // Update
   elseif ($method === 'DELETE') { ... }  // Delete
   ```

   One file, four different behaviors — decided by the HTTP method
   that arrives. The method always comes **from outside** — from the
   client. PHP decides nothing; it reads what was sent and reacts.
   It is a waiter: it does not choose the dish, it brings it. In our
   case it is POST, so it enters here:

   **How PHP unpacks the payload.** The request body arrives as
   plain text — PHP does not know it is JSON; to PHP it is just a
   string. The unpacking happens in two steps:

   ```php
   // 1. Reads the raw body of the HTTP request — i.e., the payload
   //    that JavaScript sent with JSON.stringify().
   //    php://input is not a file on disk — it is a virtual "stream"
   //    (data flow) that PHP uses to give access to the HTTP request
   //    body. Like a faucet: you open it, the text that arrived
   //    comes out, and that is it. You read it once — that is why
   //    we save it in $data right away.
   //    If the connection drops and the body arrives incomplete,
   //    json_decode() fails and the try/catch handles the error.
   file_get_contents('php://input')  // → '{"name":"Feltrinelli"}'

   // 2. Transforms the JSON string into a PHP associative array
   $data = json_decode(..., true);   // → ['name' => 'Feltrinelli']

   $data['name']  // → 'Feltrinelli'
   ```

   It is the inverse operation of `JSON.stringify()` in JavaScript:

   | Direction | Who | What it does |
   |-----------|-----|-------------|
   | browser → server | JS | `JSON.stringify({name:'Feltrinelli'})` → string |
   | server | PHP | `json_decode('{"name":"Feltrinelli"}')` → array |

   With a book the payload is richer, but the mechanism is identical:

   ```php
   $data = json_decode(file_get_contents('php://input'), true);
   // $data is now:
   // [
   //     'publisher_id' => 1,
   //     'category_id'  => 3,
   //     'title'        => 'Il Sentiero Dei Nidi Di Ragno',
   //     'pages'        => 180,
   //     'published'    => 1947,
   //     'author_ids'   => [2, 5],
   // ]

   $data['title']       // → 'Il Sentiero Dei Nidi Di Ragno'
   $data['pages']       // → 180
   $data['author_ids']  // → [2, 5]
   ```

   After unpacking, PHP **sanitizes** the data. For the publisher:

   ```php
   $name = mb_substr(ucwords(strtolower(trim(strip_tags(
       $data['name'] ?? ''
   )))), 0, 100);
   ```

   Strips HTML tags (`strip_tags`), whitespace (`trim`), converts to
   lowercase then capitalizes each word (`ucwords(strtolower())`),
   and truncates to 100 characters (`mb_substr`).
   "feltrinelli" becomes "Feltrinelli".

   Then it checks:
   - is the name empty? → responds `400 Bad Request`
   - does a publisher with the same name already exist? → responds
     `409 Conflict`
   - all good? → `$publisher->insert($name)` and responds with the id

7. JavaScript receives the response. If it is ok, it does:

   ```javascript
   window.location.href = '/bibliotheca/public/publishers';
   ```

   `window.location.href` is how JavaScript tells the browser "go to
   this page" — as if the user typed that URL in the address bar and
   pressed Enter. A new first trip begins, the page reloads
   completely.

   We use it after **all three** data-modifying operations:
   - **Create** (POST) → back to the list, you see the new publisher
   - **Update** (PUT) → back to the list, you see the change
   - **Delete** (DELETE) → back to the list, the publisher is gone

   These three operations live on **two sides** — same job, two
   files:

   | Operation | JavaScript | PHP |
   |-----------|-----------|-----|
   | Create | `save()` with `POST` | `$method === 'POST'` |
   | Update | `save()` with `PUT` | `$method === 'PUT'` |
   | Delete | `remove()` with `DELETE` | `$method === 'DELETE'` |

   JavaScript decides and sends. PHP receives, does the work, and
   **responds** — otherwise JavaScript would not know what to do.
   PHP's responses:

   - **Create** → `{"publisher_id":5,"name":"Feltrinelli"}`
   - **Update** → `{"publisher_id":5,"name":"Feltrinelli","status":1}`
   - **Delete** → `{"deleted":true}`
   - **Error** → `{"error":"Publisher already exists"}` + code 409

   If the response is ok → redirect to the list. If there is an
   error → show it to the user.

   If instead the server responded with an error (409 — duplicate
   name), JavaScript **does not** redirect — it shows the error
   message below the field:

   ```javascript
   const result = await response.json();
   this.showError('publisher-name', result.error);
   ```

   The user stays on the form and can correct the mistake.

### Update — 'Edit'

1. In the publishers list, every row has an "Edit" button.
   Clicking it:

   ```javascript
   window.location.href = '/bibliotheca/public/publisher?id=' +
       publisher.publisher_id;
   ```

   Same page as Create, but with `?id=3` in the URL.

2. The form loads the same — same HTML, same `publisher.js`.
   But `checkEdit()` finds the `id` in the URL:

   ```javascript
   checkEdit() {
       const params = new URLSearchParams(window.location.search);
       const id = params.get('id');
       if (id) {
           this.loadRecord(parseInt(id));
       }
   }
   ```

3. `loadRecord()` makes a trip to the server to load the
   publisher's data:

   ```javascript
   const response = await fetch(this.API + '?id=' + id);
   const publisher = await response.json();
   ```

   The API responds with a single JSON object:
   `{"publisher_id":3,"name":"Addison-Wesley","status":1}`

4. JavaScript fills the form with the existing data:

   ```javascript
   // hidden field: now it has the id!
   this.inputId.value = publisher.publisher_id;
   this.inputName.value = publisher.name;
   this.inputStatus.checked =
       publisher.status === 1;
   // show the Active checkbox
   this.statusGroup.hidden = false;
   // show the Delete button
   this.btnDelete.hidden = false;
   // change the title
   this.title.textContent = 'Edit Publisher';
   ```

   Same form, same HTML — but JavaScript transformed it into edit
   mode by showing the hidden elements.

5. The user edits the name and clicks Save. `save()` looks at the
   hidden field — this time **it has a value** → we are editing.
   A PUT fires:

   ```javascript
   response = await fetch(this.API, {
       method: 'PUT',
       headers: {'Content-Type': 'application/json'},
       body: JSON.stringify({
           publisher_id: parseInt(id),
           name: name,
           status: this.inputStatus.checked ? 1 : 0,
       }),
   });
   ```

   Same structure as POST, but with `PUT` and more data: the id,
   the name, and the status.

### Delete

1. In the form in edit mode, the user clicks the Delete button.
   JavaScript calls `this.remove()`:

   ```javascript
   async remove() {
       if (!confirm('Permanently delete this publisher?')) {
           return;
       }

       const response = await fetch(this.API, {
           method: 'DELETE',
           headers: {'Content-Type': 'application/json'},
           body: JSON.stringify({publisher_id: parseInt(id)}),
       });
   }
   ```

   `confirm()` is a native browser function — it opens a dialog box
   with OK and Cancel. If the user cancels, `remove()` stops. If
   they confirm, the DELETE fires.

2. The API checks: does the publisher have associated books? If yes
   → `409 Conflict` ("Cannot delete: publisher has associated
   books"). If no → deletes it and responds `{"deleted": true}`.

### One form, three operations

The secret is that hidden field `<input type="hidden"
id="publisher-id" value="">`:

| Value | Mode | Save sends | Delete |
|-------|------|-----------|--------|
| empty | Creation | POST | hidden |
| a number | Editing | PUT | visible |

Same HTML file, same JavaScript file. The hidden field is the key
that decides the behavior — and JavaScript shows or hides the
elements accordingly.
