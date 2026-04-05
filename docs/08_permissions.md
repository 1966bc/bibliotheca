# Chapter 08 — Permissions

## The problem

Your code works from the terminal. You open the browser and get a
blank page, or worse, a 500 Internal Server Error. The reason is
almost always permissions.

Apache runs as a user called `www-data`. Your files belong to your
user (e.g., `bc`). If `www-data` cannot read a file, the page is
blank. If it cannot write, the database operations fail silently.

## How Linux permissions work

Every file has three sets of permissions: owner, group, others.

```
-rw-r--r-- 1 youruser youruser 28672 bibliotheca.db
```

- `rw-` — owner (`youruser`) can read and write.
- `r--` — group (`youruser`) can only read.
- `r--` — others can only read.

Apache (`www-data`) is "others" here — it can read but not write.
That is why SELECT works but INSERT fails.

## The fix

Give the group `www-data` write access to the database file and
its directory (SQLite needs the directory for its journal file):

```bash
sudo chgrp www-data /var/www/html/bibliotheca/sql/bibliotheca.db
sudo chmod 664 /var/www/html/bibliotheca/sql/bibliotheca.db
sudo chgrp www-data /var/www/html/bibliotheca/sql/
sudo chmod 775 /var/www/html/bibliotheca/sql/
```

- `664` — owner reads/writes, group reads/writes, others read.
- `775` — same for directories, plus execute (needed to list
  contents).

Check the result with `ls -la`:

```bash
ls -la sql/
```

The terminal is your best friend for diagnosing permission
problems. Always verify with `ls -la` after changing
permissions. Get comfortable with the command line — it should
always be your first choice. A production server has no
graphical interface. There is no file manager, no right-click,
no properties dialog. There is only the terminal. The sooner
you make it your natural environment, the better.

## Every time you recreate the database

When you delete and recreate `bibliotheca.db`, the new file
belongs to your user with default permissions. You must set
them again:

```bash
rm bibliotheca.db
sqlite3 bibliotheca.db ".read ddl/create_table.sql"
sqlite3 bibliotheca.db ".read dml/insert.sql"
sudo chgrp www-data bibliotheca.db
sudo chmod 664 bibliotheca.db
```

This is easy to forget. And when you forget, the application
reads data fine but fails on any write operation with:

```
SQLSTATE[HY000]: General error: 8 attempt to write a readonly database
```

If you see this error, check the permissions first. Nine times
out of ten, that is the problem.

## Why `public/` matters

Notice the project structure: the `src/` folder (models, DBMS)
and the `sql/` folder (database) are outside `public/`. Only
the files inside `public/` are reachable from the browser.

This is a permission boundary too. Even if someone guesses the
path to `sql/bibliotheca.db`, Apache will not serve it — the
`.htaccess` rewrite rule sends everything through `index.php`,
and the router only accepts whitelisted routes.

The file system layout is your first line of defense. Keep
sensitive files outside the web root.

## Next

[Chapter 09 — Debugging](09_debugging.md)
