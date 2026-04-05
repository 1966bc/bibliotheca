# Bibliotheca

### *Liber Rerum Cognitarum*

## *Praeludium*

Questo quaderno racconta come funziona Bibliotheca dall'interno —
non *cosa* fa (quello lo vedi usandolo), ma *come* lo fa e
*perché* è fatto così. Lo scopo è capire la programmazione web
attraverso un progetto reale: il nostro.

Bibliotheca è un'applicazione *pure-stack*: PHP, JavaScript,
HTML, CSS, SQLite. Niente framework, niente Composer, niente
sessioni, niente login. Ogni riga di codice è scritta da noi —
e ogni riga è qui per un motivo.

Ogni concetto viene spiegato nel punto in cui serve, non prima.
Non è un manuale da leggere a pezzi — è un viaggio, e ogni tappa
costruisce sulla precedente.

---

## *Fundamenta* — La struttura del progetto

Prima di seguire il viaggio di una richiesta, devi conoscere la
struttura della directory dove stanno i file dell'applicazione — e
soprattutto **perché** sono disposti così.

La regola fondamentale: tutto quello che sta dentro `public/` è
raggiungibile dal browser (e quindi da chiunque). Tutto quello che
sta fuori (`src/`, `sql/`) è invisibile al browser — solo PHP
può arrivarci.

```
bibliotheca/
    src/                    ← PRIVATO — solo PHP ci arriva
        DBMS.php
        Publisher.php
        Category.php
        Author.php
        Book.php
    sql/                    ← PRIVATO — il database
        bibliotheca.db
    public/                 ← PUBBLICO — il browser vede solo qui
        .htaccess
        index.php
        css/
        js/
        api/
        pages/
```

Questa separazione è una scelta di **sicurezza**: il codice che
parla con il database e la logica di business non devono essere
esposti al mondo. Il browser può vedere solo la porta d'ingresso
(`public/`), mai la cassaforte (`src/`, `sql/`).

**Ma chi decide che `public/` è pubblico?** La configurazione di
Apache. Nel file del virtual host c'è una direttiva `DocumentRoot`
che dice ad Apache "questa è la cartella che il browser può
raggiungere". Tutto ciò che sta dentro quella cartella è accessibile
via URL. Tutto ciò che sta fuori non esiste per il browser.

Nel nostro caso il `DocumentRoot` punta a `public/`. `src/` e `sql/`
stanno un livello sopra — fuori dalla porta. Apache non li serve,
il browser non li raggiunge. Ma PHP, che gira **sul server**, può
arrivarci con `require_once __DIR__ . '/../../src/...'` perché per
lui sono file sul disco, non URL. Non è magia, è una riga di
configurazione.

Dentro `public/` c'è poi `.htaccess` — il portiere che gestisce le
regole di rewriting con le due `RewriteCond`. Serve perché negli URL
scriviamo `/publishers` (senza `.php`) — URL "puliti". Se scrivessimo
`/publishers.php` e il file esistesse, Apache lo troverebbe da solo
e `.htaccess` non interverrebbe. Questa tecnica si chiama **URL
rewriting** (riscrittura degli URL) — usata da praticamente tutti i
siti moderni. Gli URL puliti sono più leggibili, più facili da
ricordare, e nascondono la tecnologia dietro: l'utente non sa se il
server usa PHP, Python o altro. Ma quella è un'altra storia, la
vedremo nel viaggio di una richiesta.

## *Lingua Universalis* — HTTP, il protocollo del web

Quando digiti un URL nel browser e premi Invio, succede qualcosa
di sorprendentemente semplice: il browser invia un **messaggio di
testo** al server, e il server risponde con un altro **messaggio
di testo**. Questi messaggi seguono un formato preciso chiamato
**HTTP** (HyperText Transfer Protocol).

Con `curl` possiamo parlare direttamente con il server, senza
browser — e vedere esattamente cosa viaggia sulla rete:

```bash
# Il dialogo completo: richiesta E risposta (senza il body HTML)
curl -v -o /dev/null http://localhost/bibliotheca/public/publishers 2>&1
```

L'output ha tre sezioni, marcate con simboli diversi:

```
* Trying 127.0.0.1:80...                      ← * = informazioni di curl
* Connected to localhost (127.0.0.1) port 80

> GET /bibliotheca/public/publishers HTTP/1.1  ← > = quello che INVII
> Host: localhost                                    (la richiesta)
> User-Agent: curl/7.88.1
> Accept: */*

< HTTP/1.1 200 OK                             ← < = quello che RICEVI
< Content-Type: text/html; charset=UTF-8             (la risposta)
< Content-Length: 1495
```

Tre simboli, tre attori: `*` è curl che ti racconta cosa sta
facendo, `>` è quello che parte da te verso il server, `<` è
quello che torna dal server verso di te. Il body HTML non si
vede perché `-o /dev/null` lo butta via — per ora ci
interessano solo gli header.

Una richiesta HTTP è fatta così:

```
GET /bibliotheca/public/publishers HTTP/1.1
Host: localhost
Accept: text/html
```

La prima riga ha tre parti:
- **Il metodo** (`GET`) — cosa vuoi fare. GET = "dammi qualcosa"
- **Il percorso** (`/bibliotheca/public/publishers`) — cosa vuoi
- **La versione** (`HTTP/1.1`) — quale dialetto parliamo

Poi vengono gli **header** — metadati sulla richiesta:
- `Host` — a quale sito sto parlando (un server può ospitarne molti)
- `Accept` — che tipo di risposta preferisco (HTML, JSON, ecc.)

Una riga vuota separa gli header dal **body** — il corpo del
messaggio. In un GET il body è vuoto (stai chiedendo, non mandando).
In un POST il body contiene i dati che invii.

La risposta del server ha la stessa struttura:

```
HTTP/1.1 200 OK
Content-Type: text/html; charset=UTF-8

<!DOCTYPE html>
<html>...l'intera pagina HTML...</html>
```

E puoi simulare un POST — quello che JavaScript fa con `fetch`
quando invii un form:

```bash
# -X POST = metodo POST
# -H = header aggiuntivo
# -d = body (il payload JSON)
curl -v -X POST http://localhost/bibliotheca/public/api/publishers.php \
     -H "Content-Type: application/json" \
     -d '{"name": "Feltrinelli"}'
```

Se provi un metodo non supportato, il server risponde con
`405 Method Not Allowed`. Provalo:

```bash
# PATCH su un endpoint che non lo supporta → 405
curl -s -o /dev/null -w "%{http_code}\n" \
     -X PATCH http://localhost/bibliotheca/public/api/publishers.php
# Stampa: 405
```

Questo è REST in azione: lo stesso URL si comporta diversamente
a seconda del metodo HTTP. L'API decide cosa accettare.

### I quattro metodi che usiamo

| Metodo | Significato | Ha un body? | Esempio |
|--------|-------------|------------|---------|
| `GET` | Leggi | No | Apri la lista editori |
| `POST` | Crea | Sì (i dati nuovi) | Crea un editore |
| `PUT` | Aggiorna | Sì (i dati aggiornati) | Modifica un editore |
| `DELETE` | Cancella | Sì (l'id) | Cancella un editore |

Questo schema — un metodo per ogni operazione, lo stesso URL — si
chiama **REST** (Representational State Transfer). Non è una
tecnologia, è una convenzione: ci mettiamo d'accordo che `GET`
legge, `POST` crea, `PUT` aggiorna, `DELETE` cancella.

HTTP è **stateless** — senza stato. Ogni richiesta è indipendente:
il server non "ricorda" chi sei tra una richiesta e l'altra.
Bibliotheca non ha sessioni e non ha login — è stateless fino
in fondo. Ogni richiesta è davvero indipendente dalle altre.

Tutto il web — pagine, API, immagini, video — viaggia su HTTP. Non
c'è nient'altro. Quando capiremo come Bibliotheca costruisce
richieste e risposte HTTP, avremo capito come funziona.

### Codici di stato HTTP

La prima riga della risposta ha il **codice di stato** — un
numero che dice com'è andata:

| Codice | Significato | Analogia |
|--------|-------------|----------|
| `200 OK` | Tutto bene, ecco quello che hai chiesto | "Certo, eccolo" |
| `201 Created` | Ho creato quello che mi hai chiesto | "Fatto, ecco il nuovo" |
| `400 Bad Request` | Non capisco la tua richiesta | "Non ha senso quello che dici" |
| `404 Not Found` | Non esiste | "Non so di cosa parli" |
| `405 Not Allowed` | Metodo sbagliato | "Non puoi bussare così" |
| `409 Conflict` | Conflitto (es. nome duplicato, ha figli) | "Non posso, c'è un problema" |
| `500 Server Error` | Qualcosa si è rotto dentro | "Scusa, ho avuto un problema" |

### *Instrumenta* — La cassetta degli attrezzi

Ogni comando che abbiamo visto finora è un attrezzo. Sono
tutti gratuiti, tutti già installati, e tra dieci anni
funzioneranno uguale. Eccoli in un colpo d'occhio:

```bash
# ─── Parlare con il server (HTTP) ───────────────────────

curl -v URL                    # request + response grezza
curl -s URL | head -20         # solo il body, prime 20 righe
curl -s -D - -o /dev/null URL  # solo gli header della response
curl -s -o /dev/null -w "%{http_code}" URL  # solo il codice (200, 404...)
curl -s -o /dev/null -w "%{time_total}s" URL  # tempo di risposta
curl -X POST -H "Content-Type: application/json" -d '{}' URL  # simulare un POST

# ─── Parlare con il database (SQL) ──────────────────────

sqlite3 sql/bibliotheca.db                    # sessione interattiva
sqlite3 sql/bibliotheca.db ".tables"          # elenco delle tabelle
sqlite3 sql/bibliotheca.db ".schema book"     # struttura di una tabella
sqlite3 sql/bibliotheca.db "SELECT COUNT(*) FROM book"  # query singola
sqlite3 sql/bibliotheca.db < file.sql         # eseguire uno script SQL

# ─── Leggere i log ──────────────────────────────────────

tail -f /var/log/apache2/error.log        # errori Apache in tempo reale

# ─── Ispezionare file e codice ───────────────────────────

cat -n src/Publisher.php          # leggere un file con numeri di riga
head -50 src/Publisher.php        # prime 50 righe
grep -r "fetchAll" src/           # cercare una stringa in tutti i file
find public/js -name "*.js"       # trovare file per nome
wc -l src/*.php                   # contare le righe di codice
diff file1 file2                  # confrontare due file

# ─── Controllare la rete ─────────────────────────────────

ss -tlnp | grep :80              # chi sta ascoltando sulla porta 80?
ping localhost                   # il server è vivo?
```

`curl` è lo strumento più utile per il debugging delle API:
se qualcosa non funziona, togli di mezzo il browser e JavaScript,
e parla direttamente con il server. Se `curl` funziona ma il
browser no, il problema è nel JavaScript. Se `curl` non funziona,
il problema è nel server.

Un bravo programmatore usa il terminale per tutto — è il posto
dove vedi le cose per quello che sono, senza interfacce che
nascondono, senza rendering che abbellisce. Testo grezzo, dati
grezzi, la verità.

---

## Il viaggio di una richiesta

1. Cliccare sulla voce "Publishers" nel menu equivale a scrivere nella barra degli indirizzi del browser `http://localhost/bibliotheca/public/publishers`, cioè l'azione del click lo fa al nostro posto, potremmo scrive la stringa direttamente noi.

2. Il browser invia al server la richiesta `GET /bibliotheca/public/publishers`

> **Chi fa questa cosa?** Come si passa dall'indirizzo che vedo nella barra
> del browser alla stringa effettiva inviata al server (Apache nel nostro caso)?
>
> È il **network stack** del browser — non un programma separato, ma un
> insieme di componenti (librerie, moduli) compilati dentro Chrome, Firefox,
> ecc. Non lo vedi, non lo installi a parte: è dentro il browser come il
> motore è dentro la macchina. Si occupa di risolvere il dominio (DNS),
> aprire una connessione TCP e inviare la richiesta HTTP.
>
> **Risolvere il dominio (DNS — Domain Name System):** il browser vede `localhost` nell'URL,
> ma per aprire una connessione di rete serve un **indirizzo IP** (tipo
> `127.0.0.1`). Il DNS è il meccanismo che traduce il nome nel numero —
> come una rubrica telefonica: cerchi il nome, trovi il numero.
> Ma a chi lo chiede? Dipende da dove ti trovi:
>
> | Caso | A chi chiede | IP che ottiene |
> |------|-------------|----------------|
> | `localhost` | A nessuno, lo sa già (`/etc/hosts`) | `127.0.0.1` |
> | Rete locale (azienda, scuola, ospedale) | DNS interno della rete | IP privato (es. `192.168.x.x`) |
> | Sito pubblico (`google.com`) | DNS esterno (es. quello del provider) | IP pubblico (es. `142.250.181.174`) |
>
> Un'applicazione che gira su una rete locale non ha bisogno di un
> indirizzo pubblico: il DNS interno traduce i nomi solo per chi è
> collegato a quella rete. Da fuori, quel nome non esiste.
>
> **La risposta HTTP:** quando Apache riceve la richiesta, che succede?
> Seguiamo il percorso dentro il server:
>
> 1. Apache riceve `GET /bibliotheca/public/publishers`
> 2. Cerca il file `publishers` dentro `public/` — non esiste
> 3. Cerca la directory `publishers` dentro `public/` — non esiste
> 4. Prima di arrendersi con un 404, Apache controlla se nella
>    directory c'è un `.htaccess` con istruzioni — è il **piano B**.
>    Lo trova, lo legge, e le due `RewriteCond` gli dicono: "se non
>    è un file (`!-f`) e non è una directory (`!-d`), allora applica
>    la regola". La `RewriteRule` dice: "prendi quello che è stato
>    chiesto e passalo a `index.php?route=...`".
>    Risultato: la richiesta diventa `index.php?route=publishers`.
>    (Se invece chiedi `style.css` o `publishers.js` che esistono
>    come file, `.htaccess` non interviene — Apache li serve
>    direttamente)
>
>    `.htaccess` è come il **portiere di uno stabile**: "Cerchi il signor
>    Publishers? Non riesco a trovarlo, forse l'indirizzo non è proprio
>    corretto... ma prova all'interno `index.php`, appartamento
>    `route=publishers`." Se invece chiedi di qualcuno che abita lì
>    davvero (`style.css`), ti lascia passare senza fare storie.
>
>    **Ma è un comportamento innato?** No. Come il fatto che Apache
>    cerca `index.php` o `index.html` quando accedi a una directory
>    (direttiva `DirectoryIndex`), anche la consultazione di `.htaccess`
>    è una **convenzione configurabile** (direttiva `AllowOverride`).
>    Se un amministratore mette `AllowOverride None`, Apache ignora
>    tutti i `.htaccess` anche se esistono. Se mette
>    `DirectoryIndex home.php`, Apache cerca `home.php` invece di
>    `index.php`. Apache esce dalla fabbrica con certi valori
>    predefiniti che tutti usano, e a forza di usarli sembrano
>    innati — ma puoi cambiarli.
>
>    Ecco le prove dalla nostra macchina Debian 12 alla data di scrittura.
>    Il file principale di Apache sta in `/etc/apache2/apache2.conf`.
>    Aprendolo, le prime righe spiegano come è organizzata la
>    configurazione — una danza di file:
>
>    ```
>    /etc/apache2/
>    |-- apache2.conf        (il file principale)
>    |   `-- ports.conf      (porte: 80, 443...)
>    |-- mods-enabled/       (moduli attivi)
>    |-- conf-enabled/       (configurazioni extra)
>    `-- sites-enabled/      (i siti serviti)
>    ```
>
>    Dentro `apache2.conf` troviamo le direttive che ci riguardano:
>
>    ```apache
>    # Riga 193 — il nome del file "piano B".
>    AccessFileName .htaccess
>
>    # Riga 165-168 — AllowOverride All per /var/www/html/
>    # Il nostro .htaccess funziona grazie a questo All.
>    <Directory /var/www/html/>
>        AllowOverride All
>        Require all granted
>    </Directory>
>    ```
>
>    E in `/etc/apache2/mods-enabled/dir.conf`:
>
>    ```apache
>    # I file cercati quando accedi a una directory.
>    DirectoryIndex index.html index.cgi index.pl
>                   index.php index.xhtml index.htm
>    ```
>
> 5. Apache vede che `index.php` è un file `.php` → lo passa al
>    **motore PHP** (mod_php)
> 6. PHP esegue `index.php`. Attenzione: PHP non sa nemmeno che l'URL
>    originale era `/publishers`. Lui vede solo
>    `index.php?route=publishers` — è una **staffetta**: `.htaccess`
>    ha riscritto l'indirizzo e passato il testimone, PHP raccoglie
>    il testimone e sa cosa fare.
>
>    **Ma cosa significa "esegue"?** PHP è un **interprete** che legge
>    il file dall'inizio alla fine, riga per riga, alternando due
>    modalità:
>    - vede `<?php ... ?>` → **esegue** il codice
>    - vede tutto il resto → **lo copia** nell'output così com'è
>
>    Seguiamo il nostro `index.php`:
>
>    ```php
>    <?php                            // PHP esegue
>    $route = $_GET['route'] ?? 'home';
>    $route = trim($route, '/');
>    // ... controlla $allowed, prepara $page
>    ?>                                // PHP finisce
>    <!DOCTYPE html>                   // HTML: output
>    <html lang="en">
>    <head>
>        ...
>    </head>
>    <body>
>        <header>...</header>
>        <main>
>            <?php require $page; ?>  // PHP rientra
>        </main>                      // di nuovo HTML
>        <footer>...</footer>
>    </body>
>    </html>
>    ```
>
>    PHP alterna: esegue, copia, esegue, copia. Il risultato finale è
>    quella stringa unica di HTML che abbiamo visto nella risposta vera
>    del server.
>
>    **Prova tu stesso:** PHP non vive solo dentro Apache. Come qualsiasi
>    altro linguaggio interpretato, possiamo eseguire script da riga di
>    comando anteponendo l'interprete al file:
>
>    ```bash
>    php script.php          # come faresti con:
>    python script.py
>    tclsh script.tcl
>    bash script.sh
>    ```
>
>    Quindi possiamo fare esattamente quello che fa Apache + mod_php:
>
>    ```bash
>    cd /var/www/html/bibliotheca/public
>    php -r "\$_GET['route']='publishers'; require 'index.php';"
>    ```
>
>    Esce lo stesso identico HTML. Nessuna magia: Apache fa esattamente
>    questo — passa la route a PHP, PHP esegue `index.php`, esce l'HTML.
>
> 7. PHP restituisce tutto l'HTML ad Apache come una **stringa unica**
> 8. Apache ci aggiunge gli header (`Content-Type`, `Content-Length`,
>    ecc.) e manda la risposta al browser
>
> Quindi: Apache fa da postino, `.htaccess` riscrive l'indirizzo, PHP
> costruisce l'HTML assemblando `index.php` + `pages/publishers.php`,
> e Apache impacchetta il risultato con gli header e lo spedisce.
>
> La risposta è un protocollo testuale con un ordine fisso e obbligatorio:
>
> Ecco la risposta **vera** del nostro server. Se scrivi da riga di
> comando `curl -s -D - http://localhost/bibliotheca/public/publishers`
> otterrai questo:
>
> ```
> HTTP/1.1 200 OK                        ← status line
> Date: Wed, 25 Mar 2026 08:05:38 GMT    ← header
> Server: Apache/2.4.66 (Debian)         ← header
> Content-Length: 1495                    ← header
> Content-Type: text/html; charset=UTF-8 ← header
>                                         ← riga vuota
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
> (Gli URL sono abbreviati con `...` per leggibilità.
> Il comando `curl` ti mostra la versione completa.)
>
> Se la riga vuota manca o gli header vengono dopo il body, la risposta
> è malformata. Le parti nel dettaglio:
> - la **status line** — prima riga assoluta: protocollo, codice, messaggio
> - gli **header** — coppie chiave-valore che dicono al browser *come
>   trattare* quello che sta ricevendo. Quelli della nostra risposta:
>   - `Date` — quando il server ha generato la risposta
>   - `Server` — chi è il server (software e versione)
>   - `Vary` — dice alle cache che la risposta può variare
>   - `Content-Length` — il body è lungo 1495 byte, così il browser sa
>     quando ha finito di ricevere
>   - `Content-Type: text/html; charset=UTF-8` — il più importante: dice
>     al browser che il body è HTML codificato in UTF-8. Senza questo il
>     browser non saprebbe cosa fare: è HTML? Lo renderizza. È JSON? Lo
>     mostra come testo. È un PDF? Apre il viewer
> - la **riga vuota** — separa header e body, è il delimitatore
> - il **body** — l'HTML completo della pagina, tutto quello che
>   `index.php` produce. Nota il `<tbody></tbody>` vuoto e il tag
>   `<script>` in fondo: la tabella è vuota perché i dati arriveranno
>   *dopo*, quando JavaScript farà una nuova richiesta all'API
>
> Puoi vedere tutto questo (oltre che da cli come mostrato sopra, se sei masochista)
> nei **DevTools** del browser: su Firefox
> premi **F12**, vai sulla tab **Rete** (Network), ricarica la pagina
> e clicca sulla prima richiesta. Trovi la risposta scomposta nelle
> sue parti — tab **Intestazioni** (Headers) per la status line e gli
> header, tab **Risposta** (Response) per il body HTML.
> "View page source" (tasto destro sulla pagina) mostra solo il body
> e nasconde il resto.
>
> I tag `<script src="...">` dentro quell'HTML faranno poi partire
> *nuove* richieste per scaricare i file JavaScript.
>
> Solo a quel punto entra in gioco il **motore di rendering** (Blink in
> Chrome, Gecko in Firefox): prende l'HTML e lo trasforma in quello che
> vedi sullo schermo. Quando incontra un tag `<script>`, passa il
> controllo al **motore JavaScript** (V8 in Chrome) per eseguire il codice.
>
> Quindi l'ordine è: network stack → motore di rendering → motore JS.
> V8 arriva per ultimo.
>
> **Tieni a mente:** molti attori leggono i tuoi file, ognuno capisce
> solo la sua parte:
>
> | Chi legge | Cosa legge | Quando |
> |-----------|-----------|--------|
> | Apache | `.htaccess` | quando non trova il file richiesto |
> | PHP | `index.php`, `pages/*.php`, `src/*.php` | quando Apache gli passa un `.php` |
> | Motore di rendering | l'HTML prodotto da PHP | quando la risposta arriva al browser |
> | V8 (JavaScript) | `js/*.js` | quando il rendering incontra `<script>` |
>
> Sapere chi legge cosa e quando è la base del **debugging**: se la
> pagina non si carica, il problema è in Apache o in `.htaccess`. Se
> l'HTML è sbagliato, il problema è in PHP. Se la tabella resta vuota,
> il problema è in JavaScript o nell'API. Ogni attore ha il suo
> momento — e i suoi errori.

3. Il browser riceve la risposta — quel blocco di testo che va da
   `HTTP/1.1 200 OK` fino a `</html>`. La spacchetta: header da una
   parte, body dall'altra (separati dalla riga vuota).

   Il **motore di rendering** entra in gioco. Cos'è? È il componente
   del browser che trasforma il testo HTML in pixel sullo schermo.
   Prende l'HTML, costruisce un albero di oggetti in memoria (il DOM —
   Document Object Model), applica il CSS per calcolare posizioni,
   colori e dimensioni, e infine disegna tutto sullo schermo.

   Ogni browser ha il suo:

   | Browser | Motore di rendering | Motore JavaScript |
   |---------|-------------------|-------------------|
   | Firefox | Gecko | SpiderMonkey |
   | Chrome | Blink | V8 |
   | Chromium | Blink | V8 |
   | Safari | WebKit | JavaScriptCore (Nitro) |
   | Edge | Blink | V8 |

   Blink è un fork di WebKit, che a sua volta è un fork di KHTML
   (dal progetto KDE). Chrome, Chromium, Edge e Opera usano tutti
   Blink — Firefox e Safari sono gli unici rimasti con un motore
   proprio.

   Il motore di rendering legge l'HTML dall'alto verso il basso e
   costruisce la pagina pezzo per pezzo:

   - `<head>` → incontra `<link rel="stylesheet" href="...style.css">` →
     parte una **nuova richiesta** al server per scaricare il CSS
   - `<body>` → costruisce la struttura: `<header>`, `<nav>`, `<main>`,
     la `<table>` con `<tbody></tbody>` vuoto...
   - in fondo, appena prima di `</body>`, incontra
     `<script src=".../js/publishers.js">` →
     parte una **nuova richiesta** per scaricare il JS
   - il motore di rendering si ferma, passa il controllo a V8
   - V8 esegue `publishers.js`

   **Perché lo `<script>` è in fondo e non in `<head>`?** Se fosse
   in cima, il motore di rendering si fermerebbe subito per scaricare
   ed eseguire il JavaScript — **prima** di aver costruito la pagina.
   Il JS cercherebbe la tabella `#publisher-table` nel DOM, ma non la
   troverebbe perché l'HTML sotto non è stato ancora letto.
   Mettendolo in fondo: l'HTML è già tutto letto, il DOM è completo,
   il JS parte e trova tutti gli elementi che cerca. E l'utente vede
   subito la struttura della pagina (anche se vuota) invece di uno
   schermo bianco.

   **Cosa fa `publishers.js`?** Qui entra in gioco la **programmazione
   a oggetti** (OOP) che è uno stile di programmazione. 
   Per capire devi leggere dalla fine del file.
   L'ultima riga del file riporta:

   ```javascript
   const publishersView = new PublishersView();
   ```

   `PublishersView` è una **classe** — un progetto, uno stampino.
   `new` crea una copia dell' oggetto PublishersView che si chiama istanza.
   Gli oggetti hanno proprietà (le variabili) e metodi (le funzioni)
   Quando l'oggetto nasce, JavaScript esegue sempre per primo, se esiste,il
   metodo speciale `constructor()`:

   ```javascript
   constructor() {
       this.API = '/bibliotheca/public/api/publishers.php';
       this.table = document.querySelector('#publisher-table tbody');
       this.load();
   }
   ```
   this è la rappresentazione dell'oggetto stesso, e uguale a self di python.

   Il costruttore fa tre cose:
   - salva l'indirizzo dell'API in `this.API` (una **proprietà**
     dell'oggetto — un dato che gli appartiene, una variabile)
   - cerca nel DOM il `<tbody>` della tabella e lo salva in
     `this.table` (un'altra proprietà)
   - chiama `this.load()` — un **metodo** dell'oggetto (una funzione
     che gli appartiene)

   `this` è l'oggetto stesso — "io". `this.API` è "il mio indirizzo
   API", `this.load()` è "il mio metodo load".

   **Il DOM e i riferimenti:** quando il motore di rendering legge
   l'HTML, costruisce in memoria una struttura ad albero di oggetti —
   il **DOM** (Document Object Model). Ogni tag HTML diventa un
   oggetto in memoria: `<table>` è un oggetto, `<tbody>` è un oggetto,
   `<tr>` è un oggetto, e così via. Sono tutti collegati tra loro
   in una gerarchia padre-figlio, come l'HTML da cui derivano.

   Quando JavaScript fa:

   ```javascript
   this.table = document.querySelector('#publisher-table tbody');
   ```

   funziona perché in `pages/publishers.php` **noi** abbiamo scritto:

   ```html
   <table id="publisher-table">
       ...
       <tbody></tbody>
   </table>
   ```

   Quell'`id="publisher-table"` è un **contratto** tra l'HTML e il
   JavaScript: se l'id non ci fosse, o si chiamasse diversamente,
   JavaScript non troverebbe niente. I due file devono parlare la
   stessa lingua.

   **Ma `id` cos'è?** È una cosa diversa per ogni attore:

   | Chi lo vede | Cos'è | Come lo usa |
   |-------------|-------|-------------|
   | HTML | un **attributo** del tag | identifica l'elemento in modo unico nella pagina |
   | CSS | un **selettore** (`#publisher-table`) | seleziona l'elemento per applicargli stili |
   | JavaScript | una **chiave di ricerca** | `querySelector('#publisher-table')` lo usa per trovare l'oggetto nel DOM |
   | PHP | solo **testo** | lo scrive nell'HTML ma non sa cosa significa |

   `id` in HTML deve essere **unico** dentro una singola pagina — non
   possono esserci due elementi con lo stesso `id`. È come un codice
   targa: una targa, un'auto. Un id, un elemento. A differenza della
   targa però non è un numero — è un **nome** che scegli tu:
   `publisher-table`, `form-title`, `btn-delete` sono tutti id validi.
   L'importante è che sia unico nella pagina. Per buona pratica
   conviene avere id univoci anche a livello di progetto — noi lo
   facciamo usando il prefisso dell'entità: `publisher-table`,
   `book-table`, `category-table`. Nessuno si pesta i piedi.

   Per quello che si ripete uguale in più pagine si usano le
   **classi** (`class`): un bottone `.btn`, una riga disabilitata
   `.row-disabled`, la struttura `.section-header`. L'`id` identifica
   l'unico, la `class` identifica il tipo.

   Pagine diverse però
   possono avere id uguali senza problemi — ogni pagina ha il suo
   DOM separato.

   **Solo `id`?** No. `querySelector` usa la stessa sintassi dei
   selettori CSS e può cercare per qualsiasi cosa:

   | Selettore | Cosa cerca | Esempio |
   |-----------|-----------|---------|
   | `#nome` | per `id` | `querySelector('#publisher-table')` |
   | `.nome` | per `class` | `querySelector('.btn')` |
   | `tag` | per tag HTML | `querySelector('tbody')` |
   | `[attr]` | per qualsiasi attributo | `querySelector('[data-id="3"]')` |
   | combinazioni | discendenti | `querySelector('#publisher-table tbody')` |

   La differenza chiave: un `id` è unico, una `class` no. Se vuoi
   un elemento preciso usi `id`, se vuoi un gruppo di elementi simili
   usi `class`.

   **Convenzioni di nomenclatura:** nel progetto usiamo il
   **kebab-case** (parole separate da trattini) sia per gli `id` che
   per le `class`: `publisher-table`, `section-header`, `row-disabled`.
   Non `publisherTable` (camelCase) né `publisher_table` (snake_case).
   Il kebab-case è la convenzione standard in HTML e CSS.

   In sintesi: i selettori sono il **punto di contatto** tra HTML e
   JavaScript. HTML definisce gli elementi con attributi (`id`, `class`,
   ecc.), JavaScript li trova tramite i selettori. Senza quel contratto,
   JavaScript non avrebbe modo di sapere dove mettere le mani nel DOM.
   È il **punto di iniezione**: JavaScript sa *cosa* mettere (i dati
   dall'API) e il selettore gli dice *dove* metterli.

   `querySelector` non copia il `<tbody>` — ottiene un **riferimento**
   a quell'oggetto nel DOM. È come un puntatore in C: `this.table`
   punta direttamente all'oggetto in memoria. Se JavaScript aggiunge
   un figlio a `this.table`, sta modificando il DOM — e il motore di
   rendering aggiorna lo schermo di conseguenza.

   E quel riferimento dà accesso a **tutto il sottoalbero** — tutti
   i figli, i figli dei figli, ecc. Il DOM è un albero: se punti a
   un nodo, puoi navigare ovunque sotto (e anche sopra):

   ```javascript
   this.table = document.querySelector('#publisher-table tbody');
   
   this.table.appendChild(row)        // aggiungere un figlio
   this.table.querySelector('tr')     // cercare dentro
   this.table.children                // leggere tutti i figli
   this.table.textContent = ''        // svuotare tutto il contenuto
   this.table.parentElement           // risalire al padre (<table>)
   ```

   Si naviga in entrambe le direzioni, come in una struttura ad
   albero.

   E non solo dati — JavaScript può anche **formattare**, cioè
   modificare l'aspetto degli elementi. Nel nostro `render()` lo
   facciamo già:

   ```javascript
   if (publisher.status === 0) {
       row.className = 'row-disabled';
   }
   ```

   **Ma come funziona?** JavaScript non legge `style.css`. Non sa
   nemmeno cosa significhi `row-disabled` visivamente. Quello che fa
   è aggiungere il **nome della classe** all'elemento nel DOM. Poi
   entra in gioco un altro contratto — tre cose in tre posti diversi:

   1. In **`style.css`** — la regola `.row-disabled { ... }` che
      definisce l'aspetto (grigio, opaco, ecc.)
   2. In **`publishers.js`** — `row.className = 'row-disabled'` che
      assegna la classe a runtime
   3. Nella **tua testa** — il collegamento tra i due

   JavaScript dice: "questo elemento ha la classe `row-disabled`".
   CSS dice: "gli elementi con classe `row-disabled` sono grigi".
   Il motore di rendering li mette insieme e disegna.

   Nota: quella classe `row-disabled` non esiste nell'HTML originale
   mandato dal server — la aggiunge JavaScript a runtime. Se rinomini
   la classe nel file style.css ad esempio in `rows-disableding` e ti dimentichi di rinominarla in JS 
   (o viceversa), nessuno ti avvisa — semplicemente non funziona. 
   È un altro di quei contratti non scritti, come l'`id` tra HTML e JS.

   Possiamo anche agire direttamente sugli stili senza passare per
   le classi:

   ```javascript
   element.style.color = 'red';           // cambiare colore
   element.style.display = 'none';        // nascondere
   element.classList.add('active');        // aggiungere una classe
   element.classList.remove('active');     // rimuoverla
   ```

   JavaScript ha accesso completo al DOM: struttura, contenuto e
   aspetto.

   **Cambio mentale fondamentale.** Quando si studia, ci insegnano
   HTML per la struttura e CSS per l'aspetto — come se fossero ruoli
   fissi e separati. Ma in un'applicazione web non è così:

   - **Senza JavaScript**: la pagina è un documento statico. L'HTML
     che arriva dal server è quello che vedi. Per cambiare qualcosa
     devi ricaricare l'intera pagina dal server.
   - **Con JavaScript**: la pagina è **viva**. Dopo che è arrivata,
     JavaScript può modificare tutto — creare HTML (`createElement`),
     cambiare CSS (`classList`, `style`), caricare dati (`fetch`),
     reagire ai click — senza mai ricaricare la pagina.

   HTML e CSS definiscono lo **stato iniziale** — lo scheletro e il
   vestito. JavaScript è quello che li cambia **dopo**, a runtime,
   in base a quello che succede: un click, dei dati che arrivano, un
   errore. È il passaggio da un documento a un'**applicazione**.

   Nel nostro caso è evidente: l'HTML di `publishers.php` arriva con
   una tabella vuota. Senza JavaScript vedresti solo le intestazioni
   e nient'altro. È JavaScript che la trasforma in qualcosa di utile,
   e lo fa manipolando lo stesso DOM che HTML e CSS hanno costruito.

   Ecco perché basta scrivere `this.table.appendChild(row)` nel
   metodo `render()` e la riga appare nella pagina: non stiamo
   manipolando HTML come testo, stiamo manipolando **oggetti vivi
   in memoria** che il browser tiene sincronizzati con lo schermo
   cosa che ha l'indubbio vantaggio di non ricaricare l'intera
   pagina ma solo quello che serve all'interno della pagina, nel DOM.

   Chi li tiene sincronizzati? Sempre il **motore di rendering**.
   Quando qualcosa cambia nel DOM (un `appendChild`, un cambio di
   testo, una classe CSS aggiunta), il motore ricalcola il layout
   (**reflow**) e ridisegna i pixel sullo schermo (**repaint**).
   JavaScript tocca il DOM → il motore di rendering se ne accorge →
   ridisegna. Tutto automatico.

   **La catena dei metodi.** I tre metodi della classe si chiamano
   a cascata, in un ordine deciso da noi:

   ```
   new PublishersView()
       └── constructor()          ← nasce l'oggetto
               ├── salva this.API
               ├── salva this.table
               └── chiama this.load()
                       ├── fetch() → richiesta al server
                       ├── aspetta la risposta (await)
                       └── chiama this.render(publishers)
                               └── per ogni editore crea <tr> nel DOM
   ```

   Niente di automatico: siamo noi che nel costruttore scriviamo
   `this.load()`, e dentro `load()` scriviamo `this.render()`.
   JavaScript esegue quello che gli diciamo, nell'ordine in cui
   glielo diciamo.

   **Cosa fa `load()`?** (Anche `load`, come `render`, è un nome
   che ci siamo inventati noi — potevamo chiamarlo `carica`,
   `fetchData`, qualsiasi cosa.)

   Qui si torna al server per la **seconda volta**. Il primo viaggio
   ci ha riportato l'HTML (la struttura della pagina). Questo secondo
   viaggio ci porterà i **dati**.

   ```javascript
   async load() {
       const response = await fetch(this.API);
       const publishers = await response.json();
       this.render(publishers);
   }
   ```

   **`async` — perché?** Chiamare un server richiede **tempo**: la
   richiesta deve viaggiare, il server deve elaborare, la risposta
   deve tornare. `async` davanti al metodo dice a JavaScript: "questa
   funzione fa cose non istantanee". Senza `async` non potresti usare
   `await` dentro. E senza `await`, JavaScript non aspetterebbe la
   risposta — andrebbe avanti e `publishers` sarebbe vuoto.

   Nel frattempo il browser non si blocca — l'utente può continuare
   a interagire con la pagina. Quando la risposta arriva, JavaScript
   riprende da dove si era fermato.

   **Cosa vede l'utente?** La pagina appare con la tabella vuota
   (solo le intestazioni "Name"), e dopo un istante le righe con i
   dati compaiono. Su localhost è talmente veloce che quasi non si
   nota. Ma su una connessione lenta o un server remoto vedresti un
   attimo di "vuoto" prima che i dati arrivino — è il tempo del
   secondo viaggio: JavaScript ha chiesto i dati al server e sta
   aspettando.

   Senza `async/await` sarebbe peggio: il browser si
   **bloccherebbe** — niente click, niente scroll, niente di niente —
   finché il server non risponde. La pagina sembrerebbe "congelata".
   Con `async/await` invece la pagina resta viva e reattiva: i dati
   arrivano quando arrivano, e nel frattempo l'utente può fare
   altro.

   Se vuoi vedere il ritardo ad occhio nudo, apri i DevTools (F12),
   tab **Rete**, e ricarica la pagina: vedrai la richiesta a
   `publishers.php` con il suo tempo di risposta in millisecondi.

   Riga per riga:
   - `this.API` è la proprietà che abbiamo salvato nel costruttore
     che dice a javascript quale file usare e dove sta:
    
    constructor() {
        this.API = '/bibliotheca/public/api/publishers.php';
        ...˚}

     
   - `fetch()` è una funzione **nativa** di JavaScript (non inventata
     da noi) che prende quell'indirizzo e genera una richiesta HTTP —
     lo stesso meccanismo del browser quando scrivi un URL nella barra,
     ma fatto da JavaScript in background, senza ricaricare la pagina.
     `fetch` è il **ponte** tra JavaScript e PHP: i due non si
     conoscono, non parlano la stessa lingua, girano in posti diversi
     (browser vs server). L'unico modo che hanno per comunicare è
     HTTP — come due persone che si scrivono lettere
   - `await` dice "aspetta che la risposta arrivi prima di continuare"
   - `response.json()` legge il body della risposta e lo trasforma
     da testo JSON in un oggetto JavaScript utilizzabile
   - `this.render(publishers)` passa i dati al metodo che riempirà
     la tabella

   Quindi `fetch(this.API)` genera una richiesta
   `GET .../api/publishers.php`.

   **Perché GET?** Perché `fetch()` di default usa sempre GET — il
   metodo HTTP per "dammi qualcosa". Se vuoi un metodo diverso devi
   dirlo esplicitamente:

   ```javascript
   fetch(url)                          // GET (default) — leggi
   fetch(url, { method: 'POST' })      // POST — crea
   fetch(url, { method: 'PUT' })       // PUT — aggiorna
   fetch(url, { method: 'DELETE' })    // DELETE — cancella
   ```

   Noi stiamo solo chiedendo la lista degli editori, quindi GET.

   **Perché stavolta il portiere non interviene?** Perché
   `api/publishers.php` è un file **reale** dentro `public/`.
   Ricordi le due `RewriteCond`? Dicono "intervieni solo se NON è
   un file (`!-f`) e NON è una directory (`!-d`)". Apache trova il
   file e lo serve direttamente.

   Il primo viaggio chiedeva `/publishers` (senza `.php`) — non
   esiste come file, quindi serviva il rewrite. Questo secondo
   viaggio chiede `/api/publishers.php` — esiste, quindi passa
   diritto.

   **Il secondo viaggio — dentro l'API.** Aprendo il file
   `api/publishers.php` la prima cosa che troviamo nel commento è:

   ```
   Publisher REST API endpoint.
   ```

   Tre parole che meritano una spiegazione:

   - **API** (Application Programming Interface) — un'interfaccia
     che permette a due programmi di parlare tra loro. Nel nostro
     caso JavaScript (nel browser) parla con PHP (sul server). Non è
     una pagina per l'utente — è un "servizio" per il codice.
   - **REST** (Representational State Transfer) — un modo di
     organizzare le API usando i metodi HTTP: GET per leggere, POST
     per creare, PUT per aggiornare, DELETE per cancellare. Un unico
     indirizzo (`/api/publishers.php`), quattro operazioni diverse a
     seconda del metodo.
   - **Endpoint** — il punto di arrivo, l'indirizzo a cui bussare.
     `/api/publishers.php` è un endpoint, `/api/books.php` è un
     altro endpoint. Ogni entità del progetto ha il suo.

   Attenzione: gli endpoint API **non sono pagine**. L'utente non li
   visita mai nel browser — non hanno HTML, non hanno grafica. Sono
   file che rispondono solo con dati (JSON) e parlano solo con
   JavaScript. Se provassi ad aprire
   `http://localhost/bibliotheca/public/api/publishers.php` nel
   browser vedresti solo testo JSON grezzo, non una pagina.

   Cosa succede quando Apache riceve `GET /api/publishers.php`? PHP
   lo esegue dall'alto verso il basso, come ha fatto con `index.php`.
   Seguiamolo:

   ```php
   // 1. Carica il codice necessario (fuori dalla cartella public!)
   require_once __DIR__ . '/../../src/DBMS.php';
   require_once __DIR__ . '/../../src/Publisher.php';

   // 2. Dice al browser: "quello che sto per mandarti è JSON"
   header('Content-Type: application/json; charset=utf-8');

   // 3. Apre la connessione al database
   $db = new DBMS(__DIR__ . '/../../sql/bibliotheca.db');

   // 4. Crea un oggetto Publisher (anche PHP usa l'OOP!)
   $publisher = new Publisher($db);

   // 5. Legge il metodo HTTP della richiesta
   $method = $_SERVER['REQUEST_METHOD'];  // → 'GET'

   // 6. Se è GET senza parametri, restituisce tutti gli editori
   if ($method === 'GET') {
       echo json_encode($publisher->getAll());
   }
   ```

   Nota il `require_once __DIR__ . '/../../src/...'` — l'API sale di
   due directory per raggiungere `src/`, che sta fuori da `public/`.
   Il browser non può arrivarci, ma PHP sì.

   **Cosa fa `$publisher->getAll()`?** Siamo nel modello
   `Publisher.php` — un'altra classe OOP, stavolta in PHP:

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

   Il modello chiede al database (tramite `DBMS`) di eseguire la
   query SQL. SQLite restituisce le righe, PHP le trasforma in JSON
   con `json_encode()` e le manda indietro.

   Ecco la risposta **vera** del nostro server
   (provalo con `curl` sull'URL dell'API):

   ```json
   [
       {"publisher_id":3,"name":"Addison-Wesley","status":1},
       {"publisher_id":1,"name":"Adelphi","status":1},
       {"publisher_id":4,"name":"Mondadori","status":1},
       {"publisher_id":2,"name":"Penguin Books","status":1}
   ]
   ```

   Stavolta la risposta non è HTML — è **JSON** (JavaScript Object
   Notation). Ma cos'è concretamente?

   JSON è un **formato di testo** per rappresentare dati. Usa poche
   regole:
   - `{}` per un **oggetto** (un insieme di coppie chiave-valore)
   - `[]` per un **array** (una lista ordinata di valori)
   - le chiavi sono sempre stringhe tra virgolette
   - i valori possono essere stringhe, numeri, true, false, null,
     oggetti o array

   Scomponiamo la nostra risposta:

   ```json
   [                                          ← array (lista di editori)
       {                                      ← oggetto (un editore)
           "publisher_id": 3,                 ← chiave: valore (numero)
           "name": "Addison-Wesley",          ← chiave: valore (stringa)
           "status": 1                        ← chiave: valore (numero)
       },
       {                                      ← un altro editore
           "publisher_id": 1,
           "name": "Adelphi",
           "status": 1
       },
       ...
   ]
   ```

   Perché JSON e non HTML? Perché sono **dati puri**, senza
   struttura visiva — niente `<table>`, niente `<tr>`, niente
   formattazione. È JavaScript che deciderà come presentarli.
   JSON è il formato universale per lo scambio di dati sul web:
   leggero, leggibile da umani e da macchine, e JavaScript sa
   leggerlo nativamente con `response.json()`.

   JavaScript riceve il JSON e chiama `this.render(publishers)` —
   un altro metodo della stessa classe.

   **`render()` — il nome ce lo siamo inventati noi.** Non è una
   parola riservata di JavaScript. Potevamo chiamarlo `disegna`,
   `mostra`, `build`. "Render" è una convenzione comune nel mondo
   della programmazione (significa trasformare dati in qualcosa di
   visibile), ma la scelta è nostra.

   Cosa fa concretamente? Ricordiamo che l'HTML aveva già preparato
   lo **scheletro** della tabella — l'intestazione con "Name" e un
   `<tbody>` vuoto:

   ```html
   <table id="publisher-table">
       <thead>
           <tr>
               <th>Name</th>
               <th></th>
           </tr>
       </thead>
       <tbody></tbody>     ← vuoto, in attesa dei dati
   </table>
   ```

   `render()` prende i dati JSON e per ogni editore costruisce con
   JavaScript gli elementi HTML (`<tr>`, `<td>`, `<button>`) e li
   inietta nel `<tbody>` a runtime. Non riscrive la pagina — riempie
   lo scheletro che HTML aveva già preparato.

   Ricapitolando, la classe `PublishersView` ha:
   - **proprietà**: `this.API`, `this.table` (i dati)
   - **metodi**: `constructor()`, `load()`, `render()` (i comportamenti)

   Questo è il cuore dell'OOP: raggruppare **dati e comportamenti**
   che appartengono alla stessa cosa in un unico oggetto.

   Adesso la tabella ha i dati. La pagina è completa.

## CRUD — Create, Read, Update, Delete

Finora abbiamo seguito il viaggio di una richiesta che **legge**
dati: clicchi Publishers, arriva l'HTML, JavaScript chiama GET
sull'API, arrivano i dati JSON, la tabella si riempie. Questa
operazione di lettura si chiama **Read** — la R di CRUD.

Ma un'applicazione deve anche creare, modificare e cancellare dati.
Queste quattro operazioni si chiamano **CRUD** e sono il cuore di
qualsiasi applicazione che gestisce dati.

Il telaio è lo stesso: fetch → API → PHP → SQLite → risposta.
Cambia il metodo HTTP e la **direzione dei dati**:

| Operazione | Metodo | Dati vanno... |
|-----------|--------|--------------|
| **Read** (lista) | GET | server → browser |
| **Create** (nuovo) | POST | browser → server |
| **Update** (modifica) | PUT | browser → server |
| **Delete** (cancella) | DELETE | browser → server |

**Una storia di metodi dimenticati.** GET, POST, PUT e DELETE
esistono fin dalle origini di HTTP (anni '90). Ma per anni il web
ha usato quasi solo GET e POST. Il motivo? I form HTML supportano
solo quei due — se scrivi `<form method="PUT">` non funziona.
Quindi le applicazioni "vecchio stile" facevano tutto con POST e
distinguevano l'operazione con un campo nascosto tipo
`<input name="action" value="delete">`. Un unico metodo per tutto,
come usare un solo coltello per tagliare, spalmare e avvitare.

REST ha ridato dignità a PUT e DELETE. REST non è una tecnologia,
non è un software, non si installa — è un **insieme di principi
architetturali**, un modo di pensare le API proposto da Roy Fielding
nella sua tesi di dottorato nel 2000. Dice semplicemente: usa i
metodi HTTP per quello che significano, identifica le risorse con
gli URL, e comunica con formati standard (come JSON). Ogni metodo
HTTP fa quello per cui è stato progettato. Ma questo è possibile solo grazie a
JavaScript e `fetch`, che possono usare **qualsiasi** metodo HTTP.
I form HTML da soli non possono — è uno dei motivi per cui
JavaScript è diventato indispensabile nelle applicazioni web
moderne.

Se per Read abbiamo usato i file **plurali**: `publishers.php` (pagina)
+ `publishers.js` (lista) per Create, Update e Delete useremo i
file **singolari**: `publisher.php` (pagina con il form) +
`publisher.js` (logica del form). Un solo form per tre operazioni.
Questa è una **nostra convenzione** — plurale per le liste, singolare
per il dettaglio/form. Non la impone nessun linguaggio o framework,
l'abbiamo scelta noi perché è intuitiva e coerente.

### Create — "Add new publisher"

1. L'utente clicca "Add new publisher" nella lista. È un semplice
   link: `<a href="/bibliotheca/public/publisher">`. Niente
   JavaScript — è HTML puro, un primo viaggio come quello che
   conosciamo.

2. Il browser carica `/publisher` — è lo stesso identico primo
   viaggio di prima: `.htaccess` riscrive →
   `index.php?route=publisher` → PHP esegue `index.php` che carica
   `pages/publisher.php` dentro il layout → arriva l'HTML del form:

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

   **Ma il `<form>` non supporta solo GET e POST?** Sì — e infatti
   noi quel form **non lo inviamo mai con HTML**. JavaScript
   intercetta il submit con `e.preventDefault()` (lo vedremo tra
   poco) e prende il controllo: legge i valori dai campi e usa
   `fetch` con il metodo che vuole — POST, PUT, DELETE. Il `<form>`
   HTML serve solo come **contenitore** per raggruppare i campi
   e per il bottone submit. L'invio vero lo fa JavaScript.

   E `novalidate`? Dice al browser "non fare tu la validazione dei
   campi" — la faremo noi con JavaScript, così controlliamo noi i
   messaggi di errore e dove mostrarli.

   Nota che queste due cose stanno in **file diversi**:
   - `novalidate` → in `pages/publisher.php` (HTML)
   - `e.preventDefault()` → in `js/publisher.js`,
     che il browser scarica grazie al tag
     `<script>` nell'HTML

   Il browser riceve tutto assieme — il form, i campi, e in fondo
   il `<script>`. È un unico blocco HTML (il primo viaggio). Quando
   il motore di rendering arriva al `<script>`, scarica `publisher.js`
   e lo esegue. A quel punto JavaScript vede il DOM che il motore di
   rendering ha costruito dall'HTML — e nel DOM c'è il form con tutti
   i suoi campi. Poi siamo noi con i `querySelector` che gli diciamo
   dove guardare e cosa fare (`addEventListener`). Stessa dinamica
   della lista — cambia solo il file JS caricato.

   I selettori nel DOM servono a JavaScript sia per **iniettare**
   dati che per **prenderli**:

   ```javascript
   // PRENDERE — legge quello che l'utente ha scritto nel campo
   const name = this.inputName.value;

   // INIETTARE — scrive nel campo (in modalità modifica)
   this.inputName.value = publisher.name;
   ```

   Stesso riferimento al DOM, due direzioni. Nella lista era solo
   server → DOM. Nel form è **bidirezionale**.

   **Il collegamento concreto:** nel costruttore JavaScript scrive:

   ```javascript
   this.inputName = document.querySelector('#publisher-name');
   ```

   E nell'HTML quel selettore punta a:

   ```html
   <input type="text" id="publisher-name" maxlength="100" required autofocus>
   ```

   Ogni attributo di quell'`<input>` aggiunge un comportamento:
   - `type="text"` — è un campo di testo
   - `id="publisher-name"` — il selettore che JavaScript usa per
     trovarlo
   - `maxlength="100"` — il browser non fa scrivere più di 100
     caratteri
   - `required` — il campo è obbligatorio (ma noi l'abbiamo
     disattivato con `novalidate` sul form e validiamo da
     JavaScript)
   - `autofocus` — quando la pagina si carica, il cursore è già
     lì dentro, pronto per scrivere

   **Ma chi decide quale metodo HTTP usare?** Nessun automatismo —
   siamo noi nel codice JavaScript. La logica è nel metodo `save()`
   di `publisher.js` e si basa su quel campo nascosto `publisher-id`:

   - campo nascosto **vuoto** → stiamo creando → `method: 'POST'`
   - campo nascosto **ha un valore** (un id) → stiamo modificando →
     `method: 'PUT'`
   - l'utente clicca il bottone Delete → chiama un metodo diverso
     (`remove()`) che usa `method: 'DELETE'`

   Tutto deciso da noi, scritto da noi. JavaScript non sa cosa sia
   un editore — sa solo leggere un campo e scegliere un metodo HTTP
   in base a quello che ci trova.

   Nota tre cose:
   - `<input type="hidden" id="publisher-id" value="">` — un campo
     **nascosto** con valore vuoto. Sarà il modo per capire se stiamo
     creando (vuoto) o modificando (con l'id dentro).
   - `id="status-group"` con `hidden` — il checkbox Active è nascosto
     in modalità creazione, lo vedremo solo in modifica.
   - `id="btn-delete"` con `hidden` — il bottone Delete è nascosto,
     apparirà solo in modifica.

3. In fondo c'è lo `<script>` — parte `publisher.js`. Anche qui
   leggiamo dall'ultima riga:

   ```javascript
   const publisherForm = new PublisherForm();
   ```

   La classe `PublisherForm` ha un costruttore più ricco di
   `PublishersView` — deve gestire il form, gli eventi e due
   modalità (creazione e modifica):

   ```javascript
   constructor() {
       // COSA FARE — l'indirizzo dell'API
       this.API = '/bibliotheca/public/api/publishers.php';

       // CON COSA lavorare — i riferimenti ai campi del form
       this.form = document.querySelector('#publisher-form');
       this.inputId = document.querySelector('#publisher-id');
       this.inputName = document.querySelector('#publisher-name');
       this.inputStatus = document.querySelector('#publisher-status');
       this.statusGroup = document.querySelector('#status-group');
       this.btnDelete = document.querySelector('#btn-delete');
       this.title = document.querySelector('#form-title');
       //QUALI EVENTI ASCOLTARE
       //SALVARE
       this.form.addEventListener('submit', (e) => {
           e.preventDefault();
           this.save();
       });
       //ELIMINARE
       this.btnDelete.addEventListener('click', () => {
           this.remove();
       });

       // CHE MODALITÀ SIAMO — creazione o modifica?
       this.checkEdit();
   }
   ```

   Stessa logica: salva i riferimenti al DOM (tutti quei
   `querySelector`) e poi fa tre cose nuove:

   - **`addEventListener('submit', ...)`** — quando l'utente clicca
     "Save", invece di lasciare che il browser invii il form
     normalmente (che ricaricherebbe la pagina), intercetta l'evento
     con `e.preventDefault()` e chiama `this.save()`. Il controllo
     resta a JavaScript.
   - **`addEventListener('click', ...)`** — quando l'utente clicca
     "Delete", chiama `this.remove()`.
   - **`this.checkEdit()`** — controlla se nell'URL c'è `?id=`.

     ```javascript
     checkEdit() {
         const params = new URLSearchParams(window.location.search);
         const id = params.get('id');
         if (id) {
             this.load(parseInt(id));
         }
     }
     ```

     `window.location.search` è la parte dell'URL dopo il `?`.
     Se l'URL è `/publisher?id=3`, `search` è `?id=3`.
     `URLSearchParams` è una classe **nativa** di JavaScript che sa
     leggere quei parametri — `params.get('id')` restituisce `'3'`.

     Nota: `checkEdit()` non restituisce niente — non calcola un
     valore. **Agisce**: è `params.get('id')` che restituisce o la
     stringa `'3'` o `null`, e `checkEdit()` usa quel risultato per
     decidere cosa fare, fail fast fail save.

     Se l'id c'è → siamo in modifica, chiama `load()` che
     fa un viaggio al server per prendere i dati dell'editore e
     riempire il form. Se non c'è → non fa niente, il form resta
     com'è arrivato dall'HTML: titolo "Add Publisher", campo nome
     vuoto, campo nascosto vuoto, checkbox e bottone Delete
     nascosti. Siamo in modalità **creazione per default** — non
     serve dirlo esplicitamente, basta non fare niente.

     **E se qualcuno bara?** Se modifichi l'URL a mano e scrivi
     `/publisher?id=999` (un id che non esiste), `checkEdit()`
     trova l'id e chiama `load(999)`. Parte il viaggio al
     server: l'API chiama `$publisher->getById(999)`, che ritorna
     `null`. PHP risponde con `404 Not Found`:

     ```php
     http_response_code(404);
     echo json_encode(['error' => 'Not found']);
     ```

     In JavaScript `response.ok` è `false` (perché 404 non è ok),
     si entra nel `catch` e l'utente vede un alert: "Unable to load
     record." Il form resta vuoto — non è crashato, non è elegante,
     ma è **sicuro**: il server non ha dato dati, JavaScript non ha
     scritto niente nel DOM.

4. L'utente scrive "Feltrinelli" nel campo Name e clicca Save.
   JavaScript chiama `this.save()` — un metodo che abbiamo scritto
   noi (come `load` e `render`), dichiarato `async` perché dovrà
   fare una `fetch` al server e aspettare la risposta.

   Prima cosa che fa `save()`: **validazione**.

   ```javascript
   if (!this.validate()) {
       return;
   }
   ```

   `validate()` controlla che il campo non sia vuoto. Se lo è,
   mostra l'errore in rosso sotto il campo (ricordi quel `<span
   class="error">`?) e si ferma. Niente viaggio al server per
   dati sbagliati — lo blocchiamo subito lato browser.

5. Se la validazione passa, `save()` guarda il campo nascosto
   `publisher-id`. È vuoto → stiamo creando. Parte il terzo
   viaggio al server:

   ```javascript
   const payload = {name: name};

   response = await fetch(this.API, {
       method: 'POST',
       headers: {'Content-Type': 'application/json'},
       body: JSON.stringify(payload),
   });
   ```

   Stavolta `fetch` è diverso da prima:
   - `method: 'POST'` — non è più GET (leggi), è POST (crea)
   - `headers: {'Content-Type': 'application/json'}` — dice al
     server "ti sto mandando JSON"
   - `body: JSON.stringify({name: 'Feltrinelli'})` — il corpo
     della richiesta, con i dati. Nel GET i dati non c'erano —
     ora vanno dal browser al server.
     `{name: 'Feltrinelli'}` è un oggetto JavaScript con una coppia
     chiave-valore — come un dizionario Python. Per il publisher ne
     basta una, ma per salvare un book ne servono parecchie quindi usiamo
     una variabile `payload` per contenerle:

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

     Sei coppie, incluso un array di id autori. Ma perché nel
     publisher scriviamo `JSON.stringify({name: name})` direttamente,
     e nel book prima creiamo `const payload = {...}` e poi facciamo
     `JSON.stringify(payload)`? Nessuna differenza funzionale — è
     solo organizzazione. È come scrivere una lettera: puoi scriverla
     direttamente sulla busta (se è corta) o scriverla su un foglio
     e poi infilarla nella busta (se è lunga). La lettera è la stessa.

     `JSON.stringify()` trasforma quell'oggetto in una stringa JSON
     che viaggia al server. PHP dall'altra parte farà l'operazione
     inversa con `json_decode()` per riottenere le coppie chiave-valore

     **Vuoi vedere cosa parte?** Aggiungi temporaneamente un
     `console.log(payload)` prima del `fetch` e apri la **Console**
     nei DevTools (F12):

     ```javascript
     console.log(payload);   // ← aggiungi questa riga
     response = await fetch(this.API, { ... });
     ```

     Vedrai l'oggetto che sta per partire verso il server —
     esplorabile, cliccabile. È uno strumento di studio e debug,
     non codice da lasciare in produzione.

     **Attenzione:** dopo il salvataggio `save()` fa un redirect
     (`window.location.href = ...`) che ricarica la pagina e pulisce
     la Console — perdi il log. Due soluzioni:
     - nei DevTools di Firefox, nella Console, spunta **"Persist
       Logs"** (Mantieni log) — i log sopravvivono alla navigazione
     - oppure aggiungi un `return` temporaneo dopo il `console.log`
       per bloccare il flusso:

     ```javascript
     console.log(payload);
     return;  // ← blocca qui, il fetch non parte
     ```

     Così vedi il payload senza che la pagina cambi. Poi togli il
     `return` quando hai finito.

     Nella Console vedrai l'oggetto espandibile. La parte in alto
     sono i **tuoi** dati (`title`, `pages`, `author_ids`, ecc.).
     In fondo trovi `<prototype>: Object` con metodi come
     `toString()`, `hasOwnProperty()` — sono "di fabbrica", li hanno
     tutti gli oggetti JavaScript. Non li abbiamo scritti noi, puoi
     ignorarli.

6. L'API riceve la richiesta — lo stesso file che
   abbiamo indicato nel costruttore di `publisher.js`
   con `this.API = '.../api/publishers.php'`.
   Vediamo cosa succede dentro:

   ```php
   $method = $_SERVER['REQUEST_METHOD'];  // → 'POST' in questo caso
   ```

   **Attenzione:** non è JavaScript che manda `$_SERVER`. La catena
   è questa: JavaScript specifica `method: 'POST'` nel `fetch` →
   la richiesta HTTP viaggia col metodo POST → **Apache** la riceve,
   ne estrae le informazioni, e le passa a PHP tramite le
   **variabili superglobali**.

   `$_SERVER` è un **array superglobale** di PHP — una variabile
   che PHP crea automaticamente all'inizio di ogni richiesta. Non
   le creiamo noi, ci sono sempre — è così perché chi ha creato PHP
   ha deciso che funzionasse così. Ognuna si riempie da una **fonte
   diversa**:

   | Superglobale | Da dove prende i dati |
   |-------------|----------------------|
   | `$_SERVER` | dalla richiesta HTTP (metodo, header, IP, ecc.) |
   | `$_GET` | dai parametri nell'URL (`?route=publishers`) |
   | `$_POST` | dal body di un form HTML con `method="POST"` |
   | `$_FILES` | dai file caricati tramite form |
   | `$_COOKIE` | dai cookie del browser |

   Forse ricordi `$_POST` dai form HTML classici:
   `<form method="POST">` → PHP riempie `$_POST`.
   `<form method="GET">` → PHP riempie `$_GET`.
   Ma noi in Bibliotheca **non usiamo `$_POST`** — non inviamo i
   form con HTML (ricordi `e.preventDefault()`?). Mandiamo JSON con
   `fetch`, e lo leggiamo con `file_get_contents('php://input')`,
   che legge il body grezzo della richiesta.

   Chi fa cosa in questa catena:

   | Chi | Cosa fa | Esempio |
   |-----|---------|---------|
   | **JavaScript** | decide il metodo e prepara i dati | `fetch(url, {method: 'POST', body: ...})` |
   | **HTTP** | trasporta la richiesta col metodo e il body | `POST /api/publishers.php` + payload JSON |
   | **Apache** | riceve la richiesta, estrae le informazioni | legge il metodo, gli header, il body |
   | **PHP** | legge le informazioni dalle superglobali | `$_SERVER['REQUEST_METHOD']` → `'POST'` |

   **Attenzione a non confondersi:** `fetch` non crea `$_SERVER`.
   `fetch` non sa nemmeno che PHP esiste — manda una richiesta HTTP
   e basta. Il cerchio completo:

   1. **Noi** scriviamo `method: 'POST'` nel `fetch`
   2. **Il browser** costruisce la richiesta HTTP con `POST` come
      prima parola della prima riga
   3. **La richiesta** viaggia come testo sulla rete
   4. **Apache** legge la prima riga e vede `POST` — come leggere
      "URGENTE" sulla busta di una lettera
   5. **PHP** lo trova in `$_SERVER['REQUEST_METHOD']`

   Ogni attore fa solo il suo pezzo — nessuno vede gli altri.

   Ma come arriva? Come un **blocco unico di testo** sulla
   connessione TCP — lo stesso formato della risposta che abbiamo
   smontato all'inizio del quaderno, ma nella direzione opposta
   (dal browser al server):

   ```
   POST /bibliotheca/public/api/publishers.php HTTP/1.1  ← metodo + percorso
   Host: localhost                                        ← header
   Content-Type: application/json                         ← header
                                                          ← riga vuota
   {"name":"Feltrinelli"}                                 ← body (il payload)
   ```

   Apache riceve questo testo, lo spacchetta e lo distribuisce a
   PHP nelle superglobali:
   - `POST` → `$_SERVER['REQUEST_METHOD']`
   - `application/json` → `$_SERVER['CONTENT_TYPE']`
   - `{"name":"Feltrinelli"}` → leggibile con
     `file_get_contents('php://input')`

   Puoi vedere tutto questo nei DevTools (F12), tab **Rete**: clicca
   sulla richiesta POST e trovi:
   - tab **Intestazioni** → il metodo, l'URL, gli header
   - tab **Richiesta** → il body (il payload JSON che hai mandato)
   - tab **Risposta** → quello che il server ha risposto

   Richiesta e risposta, andata e ritorno — tutto visibile.
   Hanno la stessa struttura. Esempio: **creare** un editore.

   La richiesta (browser → server):

   ```
   POST /api/publishers.php HTTP/1.1
   Host: localhost
   Content-Type: application/json

   {"name":"Feltrinelli"}
   ```

   La risposta (server → browser):

   ```
   HTTP/1.1 200 OK
   Content-Type: application/json

   {"publisher_id":5,"name":"Feltrinelli"}
   ```

   Esempio: **leggere** la lista.

   La richiesta (nessun body — chiede solo "dammi tutto"):

   ```
   GET /api/publishers.php HTTP/1.1
   Host: localhost
   ```

   La risposta (il body ha i dati):

   ```
   HTTP/1.1 200 OK
   Content-Type: application/json

   [{"publisher_id":1,...},
    {"publisher_id":2,...}]
   ```

   Stessa struttura: prima riga, header, riga vuota, body.
   Solo la direzione e il contenuto cambiano.

   Quindi `$_SERVER['REQUEST_METHOD']` contiene `'POST'` non perché
   JavaScript lo ha scritto lì dentro, ma perché Apache ha letto
   il metodo dalla richiesta HTTP e lo ha passato a PHP.

   Lo stesso file gestisce tutte le operazioni con una catena
   `if/elseif`:

   ```php
   if ($method === 'GET') { ... }         // Read
   elseif ($method === 'POST') { ... }    // Create
   elseif ($method === 'PUT') { ... }     // Update
   elseif ($method === 'DELETE') { ... }  // Delete
   ```

   Un unico file, quattro comportamenti diversi — decisi dal metodo
   HTTP che arriva. Il metodo arriva sempre **da fuori** — dal
   client. PHP non decide niente, legge quello che gli è stato
   mandato e reagisce. È un cameriere: non sceglie il piatto, lo
   porta. Nel nostro caso è POST, quindi entra qui:

   **Come PHP spacchetta il payload.** Il body della richiesta
   arriva come testo puro — PHP non sa che è JSON, per lui è solo
   una stringa. Lo spacchettamento avviene in due passi:

   ```php
   // 1. Legge il body grezzo della richiesta HTTP — cioè il payload
   //    che JavaScript ha mandato con JSON.stringify().
   //    php://input non è un file sul disco — è uno "stream"
   //    (flusso di dati) virtuale che PHP usa per dare accesso
   //    al body della richiesta HTTP. Come un rubinetto: lo apri,
   //    esce il testo che è arrivato, e finisce lì. Si legge una
   //    volta sola — per questo lo salviamo subito in $data.
   //    Se la connessione si interrompe e il body arriva incompleto,
   //    json_decode() fallisce e il try/catch gestisce l'errore.
   file_get_contents('php://input')  // → '{"name":"Feltrinelli"}'

   // 2. Trasforma la stringa JSON in un array associativo PHP
   $data = json_decode(..., true);   // → ['name' => 'Feltrinelli']

   $data['name']  // → 'Feltrinelli'
   ```

   È l'operazione inversa di `JSON.stringify()` in JavaScript:

   | Direzione | Chi | Cosa fa |
   |-----------|-----|---------|
   | browser → server | JS | `JSON.stringify({name:'Feltrinelli'})` → stringa |
   | server | PHP | `json_decode('{"name":"Feltrinelli"}')` → array |

   Con un book il payload è più ricco, ma il meccanismo è identico:

   ```php
   $data = json_decode(file_get_contents('php://input'), true);
   // $data è ora:
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

   Dopo lo spacchettamento, PHP **pulisce** i dati. Per il publisher:

   ```php
   $name = mb_substr(ucwords(strtolower(trim(strip_tags(
       $data['name'] ?? ''
   )))), 0, 100);
   ```

   Toglie tag HTML (`strip_tags`), spazi (`trim`), converte in
   minuscolo poi maiuscola iniziale (`ucwords(strtolower())`),
   taglia a 100 caratteri (`mb_substr`).
   "feltrinelli" diventa "Feltrinelli".

   Poi fa i controlli:
   - il nome è vuoto? → risponde `400 Bad Request`
   - esiste già un editore con lo stesso nome? → risponde
     `409 Conflict`
   - tutto ok? → `$publisher->insert($name)` e risponde con l'id

7. JavaScript riceve la risposta. Se è ok, fa:

   ```javascript
   window.location.href = '/bibliotheca/public/publishers';
   ```

   `window.location.href` è il modo in cui JavaScript dice al
   browser "vai a questa pagina" — come se l'utente scrivesse
   quell'URL nella barra degli indirizzi e premesse Invio. Parte
   un nuovo primo viaggio, la pagina si ricarica completamente.

   Lo usiamo dopo **tutte e tre** le operazioni che modificano dati:
   - **Create** (POST) → torna alla lista, vedi il nuovo editore
   - **Update** (PUT) → torna alla lista, vedi la modifica
   - **Delete** (DELETE) → torna alla lista, l'editore non c'è più

   Queste tre operazioni vivono su **due lati** — stesso lavoro,
   due file:

   | Operazione | JavaScript | PHP |
   |-----------|-----------|-----|
   | Create | `save()` con `POST` | `$method === 'POST'` |
   | Update | `save()` con `PUT` | `$method === 'PUT'` |
   | Delete | `remove()` con `DELETE` | `$method === 'DELETE'` |

   JavaScript decide e manda. PHP riceve, fa il lavoro, e
   **risponde** — se no JavaScript non saprebbe cosa fare.
   Le risposte di PHP:

   - **Create** → `{"publisher_id":5,"name":"Feltrinelli"}`
   - **Update** → `{"publisher_id":5,"name":"Feltrinelli","status":1}`
   - **Delete** → `{"deleted":true}`
   - **Errore** → `{"error":"Publisher already exists"}` + codice 409

   Se la risposta è ok → redirect alla lista. Se c'è un errore →
   lo mostra all'utente.

   Se invece il server ha risposto con un errore (409 — nome
   duplicato), JavaScript **non** fa il redirect — mostra il
   messaggio di errore sotto il campo:

   ```javascript
   const result = await response.json();
   this.showError('publisher-name', result.error);
   ```

   L'utente resta sul form e può correggere.

### Update — "Edit"

1. Nella lista degli editori, ogni riga ha un bottone "Edit".
   Cliccandolo:

   ```javascript
   window.location.href = '/bibliotheca/public/publisher?id=' +
       publisher.publisher_id;
   ```

   Stessa pagina del Create, ma con `?id=3` nell'URL.

2. Il form si carica uguale — stesso HTML, stesso `publisher.js`.
   Ma `checkEdit()` trova l'`id` nell'URL:

   ```javascript
   checkEdit() {
       const params = new URLSearchParams(window.location.search);
       const id = params.get('id');
       if (id) {
           this.load(parseInt(id));
       }
   }
   ```

3. `load()` fa un viaggio al server per caricare i dati
   dell'editore:

   ```javascript
   const response = await fetch(this.API + '?id=' + id);
   const publisher = await response.json();
   ```

   L'API risponde con un singolo oggetto JSON:
   `{"publisher_id":3,"name":"Addison-Wesley","status":1}`

4. JavaScript riempie il form con i dati esistenti:

   ```javascript
   // campo nascosto: ora ha l'id!
   this.inputId.value = publisher.publisher_id;
   this.inputName.value = publisher.name;
   this.inputStatus.checked =
       publisher.status === 1;
   // mostra il checkbox Active
   this.statusGroup.hidden = false;
   // mostra il bottone Delete
   this.btnDelete.hidden = false;
   // cambia il titolo
   this.title.textContent = 'Edit Publisher';
   ```

   Stesso form, stesso HTML — ma JavaScript lo ha trasformato
   in modalità modifica mostrando gli elementi nascosti.

5. L'utente modifica il nome e clicca Save. `save()` guarda il
   campo nascosto — stavolta **ha un valore** → stiamo modificando.
   Parte un PUT:

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

   Stessa struttura del POST, ma con `PUT` e più dati: l'id, il
   nome e lo stato.

### Delete — "Cancella"

1. Nel form in modalità modifica, l'utente clicca il bottone Delete.
   JavaScript chiama `this.remove()`:

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

   `confirm()` è una funzione nativa del browser — apre una finestra
   di dialogo con OK e Annulla. Se l'utente annulla, `remove()` si
   ferma. Se conferma, parte il DELETE.

2. L'API controlla: l'editore ha libri associati? Se sì → `409
   Conflict` ("Cannot delete: publisher has associated books").
   Se no → lo cancella e risponde `{"deleted": true}`.

### Un unico form, tre operazioni

Il segreto è quel campo nascosto `<input type="hidden"
id="publisher-id" value="">`:

| Valore | Modalità | Save invia | Delete |
|--------|---------|-----------|---------|
| vuoto | Creazione | POST | nascosto |
| un numero | Modifica | PUT | visibile |

Stesso file HTML, stesso file JavaScript. Il campo nascosto è la
chiave che decide il comportamento — e JavaScript mostra o nasconde
gli elementi di conseguenza.

## *Custos Secreti* — CSRF, il biglietto di controllo

C'è un pericolo nascosto che riguarda tutte le applicazioni web —
anche quelle senza login come Bibliotheca. Si chiama **CSRF**
(Cross-Site Request Forgery — falsificazione di richiesta tra siti).

### Il problema

Immagina di avere Bibliotheca aperta nel browser. In un'altra
scheda visiti un sito maligno. Quel sito potrebbe contenere:

```html
<script>
fetch('http://localhost/bibliotheca/public/api/publishers.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: '{"name": "HACKED"}'
});
</script>
```

Il browser invierebbe il POST — e il server lo eseguirebbe.
Il sito maligno ha creato un editore nel nostro database senza
che noi facessimo niente.

### La soluzione — un token segreto

La difesa è un **token CSRF**: un codice segreto che solo le
*nostre* pagine conoscono. Il sito maligno non può leggerlo.

Quando PHP genera la pagina, mette il token nell'HTML:

```html
<meta name="csrf-token" content="a7f3b9c2e1d4...">
```

Il token viene da `$_SESSION['csrf_token']` — generato con
`bin2hex(random_bytes(32))`, 64 caratteri esadecimali casuali.
Sta nella sessione (sul server) E nell'HTML (nel browser).
Solo le nostre pagine lo hanno.

Quando JavaScript invia un POST/PUT/DELETE, include il token:

```javascript
headers: {
    'Content-Type': 'application/json',
    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
}
```

L'API lo verifica:

```php
if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    Csrf::start();
    Csrf::verify();  // 403 se il token è sbagliato o mancante
}
```

`Csrf::verify()` confronta il token ricevuto nell'header con
quello in `$_SESSION`. Se coincidono, la richiesta è legittima —
viene dalla nostra pagina. Se non coincidono (o manca), qualcuno
sta provando a fare una richiesta da fuori — 403 Forbidden.

### Perché il sito maligno non può leggere il token?

Il browser lo vieta. La **Same-Origin Policy** (politica della
stessa origine) impedisce a un sito di leggere il contenuto di un
altro sito. Il sito maligno può *inviare* una richiesta verso il
nostro server, ma non può *leggere* le nostre pagine per estrarre
il token dal `<meta>`. Senza token, il server rifiuta.

### Ma Bibliotheca non ha login!

Vero — e il CSRF classico sfrutta sessioni autenticate. Ma il
principio vale anche senza login: proteggere le operazioni di
scrittura da richieste non volute. In un progetto didattico è
fondamentale: insegna il concetto *prima* che servano le sessioni,
così quando arriveranno (come in VirtUaLab) il meccanismo sarà
già familiare.

### Provalo tu stesso

```bash
# Senza token → 403 Forbidden
curl -s -o /dev/null -w "%{http_code}" -X POST \
     http://localhost/bibliotheca/public/api/publishers.php \
     -H "Content-Type: application/json" \
     -d '{"name": "Test"}'
# Stampa: 403

# Con token → 200 OK (prendi il token dalla pagina)
TOKEN=$(curl -s -c /tmp/c.txt \
    http://localhost/bibliotheca/public/ \
    | grep csrf-token | sed 's/.*content="\([^"]*\)".*/\1/')
curl -s -o /dev/null -w "%{http_code}" -b /tmp/c.txt -X POST \
     http://localhost/bibliotheca/public/api/publishers.php \
     -H "Content-Type: application/json" \
     -H "X-CSRF-Token: $TOKEN" \
     -d '{"name": "Test"}'
# Stampa: 200
```

Nota che il secondo curl usa `-c` (salva i cookie) e `-b`
(invia i cookie) — il token nella sessione PHP è legato al
cookie `PHPSESSID`. Senza il cookie, il server non riconosce
la sessione e il token non corrisponde.

---

## *Tabula Itineris* — La mappa completa

```
Browser                          Server (Apache + PHP)
───────                          ────────────────────
1. Click "Publishers"
   GET /bibliotheca/public/publishers ──→
                                    2. Apache → .htaccess → index.php
                                    3. index.php matcha "publishers"
                                       nella lista $allowed
                                    4. Carica pages/publishers.php
                                       - layout HTML (header, nav, footer)
                                       - <table> con <tbody> vuoto
                                       - <script src="js/publishers.js">
                                    5. PHP → HTML → Apache
                              ←── risposta HTML completa

6. Browser renderizza HTML
   (header, tabella vuota)
7. Esegue publishers.js
   new PublishersView()
   → constructor()
   → load()
   GET /bibliotheca/public/api/publishers.php ──→
                                    8. Apache serve api/publishers.php
                                       (file reale, niente rewrite)
                                       - require_once DBMS.php, Publisher.php
                                       - $db = new DBMS(...)
                                       - $publisher = new Publisher($db)
                                    9. $publisher->getAll()
                                       - DBMS → SQLite → query SQL
                                       - righe → array PHP
                                   10. json_encode() → risposta JSON
                              ←── risposta JSON

11. response.json() → oggetto JS
12. this.render()
    → createElement per ogni riga
    → appendChild nel <tbody>
13. Motore di rendering ridisegna
    → la tabella appare con i dati
```

Due viaggi al server: il primo per lo scheletro HTML, il secondo
per i dati JSON. Il primo produce la struttura, il secondo la
riempie. L'utente vede la pagina apparire (header, tabella vuota)
e poi le righe che si popolano — su localhost è talmente veloce
che sembra istantaneo.

## *Tria Officia* — Model, View, Controller

Quello che abbiamo seguito è il pattern **Model-View-Controller**:

| Componente | File | Responsabilità |
|------------|------|----------------|
| **Model** | `src/Publisher.php` | Parla con il database. Query SQL, nient'altro. Non sa niente di HTTP o HTML |
| **Controller** | `public/api/publishers.php` | Riceve la richiesta HTTP, chiama il Model, restituisce JSON. Non sa niente di HTML o SQL |
| **View** | `public/pages/publishers.php` + `public/js/publishers.js` | HTML per lo scheletro, JavaScript per riempirlo. Non sa niente di SQL |

Ogni pezzo fa una cosa sola. Se la query SQL è sbagliata, il
problema è nel Model. Se la risposta JSON è malformata, il
problema è nel Controller. Se la tabella non si riempie, il
problema è nella View. Ogni attore ha il suo momento — e i suoi
errori.

## *Quis Legit Quid* — Chi legge cosa, e quando

| Chi legge | Cosa legge | Quando |
|-----------|-----------|--------|
| Apache | `.htaccess` | quando non trova il file richiesto |
| PHP | `index.php` | ad ogni richiesta di pagina — smista la rotta |
| PHP | `pages/*.php`, layout in `index.php` | quando la rotta è valida |
| PHP | `api/*.php`, `src/*.php` | quando JavaScript chiede dati |
| SQLite | query SQL | quando DBMS le esegue |
| Motore di rendering | l'HTML prodotto da PHP | quando la risposta arriva al browser |
| V8 (JavaScript) | `js/*.js` | quando il rendering incontra `<script>` |

Sapere chi legge cosa e quando è la base del **debugging**: se la
pagina non si carica, il problema è in Apache o in `.htaccess`. Se
l'HTML è sbagliato, il problema è in PHP. Se la tabella resta vuota,
il problema è in JavaScript o nell'API. Se la query non restituisce
dati, il problema è nel Model o nel database.

## *Ubi Stat Res* — Dove vive lo stato

Un'applicazione web ha un problema che un programma desktop non ha:
lo **stato** — le informazioni che servono per funzionare — è
sparpagliato in posti diversi. Capire dove sta ogni pezzo è la
chiave per non perdersi.

### I tre contenitori

Bibliotheca non ha sessioni e non ha login — è più semplice di
un'applicazione enterprise. Lo stato vive in tre posti:

| Dove | Cosa ci sta | Durata | Chi ci accede |
|------|-------------|--------|---------------|
| **Database** (SQLite) | Dati permanenti: editori, categorie, autori, libri | Per sempre (finché non li cancelli) | Solo PHP (via DBMS) |
| **DOM** (il documento HTML) | Cosa vedi: tabelle, form, bottoni | Finché la pagina è aperta | JavaScript (e il motore di rendering) |
| **Variabili JavaScript** | Dati temporanei: `this.API`, `this.table` | Finché la pagina è aperta | Solo JavaScript |

Questi tre contenitori **non si parlano direttamente**. Il
database non sa cosa c'è nel DOM. JavaScript non sa cosa c'è nel
database. Per spostare informazioni da un contenitore all'altro
servono passaggi espliciti:

```
Database ←──SQL──→ PHP ←──JSON/HTTP──→ JavaScript ←──DOM API──→ Schermo

$db->query()              fetch()              createElement()
$db->insert()             JSON.stringify()     textContent
                          response.json()      appendChild()
```

### L'esempio concreto

Quando carichi la lista editori, i dati fanno questo viaggio:

1. **Database → PHP**: `$publisher->getAll()` esegue una SELECT e
   ottiene un array PHP
2. **PHP → HTTP**: `json_encode()` trasforma l'array in una
   stringa JSON che viaggia nella risposta
3. **HTTP → JavaScript**: `response.json()` trasforma la stringa
   in un array di oggetti JavaScript
4. **JavaScript → DOM**: `createElement()` + `textContent` +
   `appendChild()` trasformano i dati in elementi visibili

Ogni passaggio è una **trasformazione di formato**: riga SQL →
array PHP → stringa JSON → oggetto JS → elemento DOM. Il dato
è sempre lo stesso, il vestito cambia.

### Lo stato che si perde

C'è un'asimmetria cruciale: il database è **permanente**, tutto
il resto è **temporaneo**.

Se l'utente chiude il browser: DOM sparisce, variabili JavaScript
spariscono. Il database resta per sempre.

Se l'utente ricarica la pagina (F5): DOM ricostruito da zero,
JavaScript ripartito da zero, database intatto. Per questo
`this.load()` è nel costruttore — ogni volta che la pagina si
carica, JavaScript deve riandarsi a prendere i dati.

Non c'è "memoria" nel browser. Ogni pagina riparte da zero e
ricostruisce il suo stato dal server. L'unica cosa che sopravvive
tra una pagina e l'altra è il database su disco.

## *Ars Venandi* — A caccia di errori

La programmazione web è debugging. Non perché il codice sia fragile,
ma perché ci sono tanti pezzi che devono cooperare — e quando uno
si rompe, gli effetti si vedono altrove. Il segreto è sapere
**dove guardare**.

### Gli strumenti

| Strumento | Dove sta | Cosa ti dice |
|-----------|----------|--------------|
| **Console del browser** | F12 → Console | Errori JavaScript, `console.log()` |
| **Tab Network** | F12 → Rete (Network) | Richieste HTTP, risposte, tempi |
| **Tab Elements** | F12 → Elementi (Elements) | Il DOM attuale (dopo le modifiche JS) |
| **Log di Apache** | `/var/log/apache2/error.log` | Errori del server (404, 500, permessi) |
| **SQLite** | `sqlite3 sql/bibliotheca.db` | Query dirette, verifica dati |

### La mappa mentale — cosa non funziona?

```
La pagina non si carica affatto?
  → Log di Apache + .htaccess
  → Il server è su? La rotta è in $allowed?

La pagina si carica ma è vuota/rotta?
  → View source (Ctrl+U): PHP ha prodotto HTML?
  → Log di Apache: c'è un Fatal Error?

La pagina si carica ma la tabella è vuota?
  → Tab Network: la chiamata API parte?
  → Se sì: cosa risponde? (200 con dati? 404? 500?)
  → Se 500: log di Apache
  → Se 200 ma dati vuoti: query sbagliata
    → testa in sqlite3: sqlite3 sql/bibliotheca.db
      "SELECT * FROM publisher"

La pagina funziona ma il salvataggio fallisce?
  → Tab Network: il POST parte? Cosa risponde?
  → Console: errori JavaScript?
  → Se 400: validazione fallita → leggi il messaggio
  → Se 409: dato duplicato
  → Se 500: log di Apache → errore nel Model

Il CSS non si applica?
  → Cache del browser → Ctrl+Shift+R (hard reload)
```

### La regola d'oro

**Segui il dato.** Quando qualcosa non funziona, parti dalla
sorgente (il database o l'input utente) e segui il dato attraverso
ogni passaggio fino a dove si perde. Non tirare a indovinare —
controlla ogni snodo. Il bug è sempre nel passaggio tra due strati
che non si parlano come dovrebbero.
