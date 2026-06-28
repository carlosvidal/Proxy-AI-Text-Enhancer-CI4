# dev-scripts

Ad-hoc, one-off scripts used during development and debugging — **not part of the
application** and not run in production. They were previously scattered in the
project root; moved here to keep the root clean.

Run them from the **project root** (several use a relative `require 'vendor/autoload.php'`):

```bash
php dev-scripts/<script>.php
```

Categories:
- `debug_*`, `debug-test.html` — throwaway debugging probes for button/config issues.
- `check_*` — ad-hoc inspections of the buttons table / API keys (the maintained
  equivalents are the proper CLI commands in `app/Commands/`: `CheckApiKeys`,
  `CheckButtonsTable`).
- `setup_*`, `create_*` — local demo/data bootstrap helpers.
- `fix_*` — historical one-time data fixes.
- `dump_schema.php`, `schema_dump.sql`, `fix_buttons_table.sql` — schema dumps/scratch SQL
  (canonical schema docs live in `docs/`).

Safe to delete any you no longer need — they're kept only for reference and are
preserved in git history regardless.
