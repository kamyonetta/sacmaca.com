# Project workflow

- Inspect the relevant implementation before editing. This repository contains WordPress and custom components; do not assume all functionality uses WordPress. Preserve unrelated functionality and reuse existing code.
- Work locally, build/test the affected application, and review the Git diff for unrelated changes.
- Never read out, commit, or deploy credentials, `.env` files, `wp-config.php`, user uploads, or production-only data. Preserve server configuration.
- Do not use browser automation or cPanel File Manager for deployment or troubleshooting.
- Commit completed source changes descriptively and push to the existing `kamyonetta/sacmaca.com` remote before deployment.
- Use `./deploy.sh` for command-line FTPS. The account `efecan2@sacmaca.com` on `ams201.greengeeks.net` is rooted directly at production `public_html`; never append another `public_html` directory.
- Retrieve credentials from macOS Keychain. Never request passwords in chat or put them in source, command arguments, logs, or Git. The user runs `./deploy.sh --setup-keychain` locally to enter the password securely.
- Run `./deploy.sh --check`, review `--dry-run FILE ...`, then use `--upload FILE ...` for explicit affected production files. Never mirror/delete production or upload the full local backup by default. Deployment tooling and instructions stay local/Git only.
- Diagnose issues using terminal tooling. If credentials or permissions block progress, report the exact remaining local action honestly.
- Report changes, validation, pushed commit, and exact deployed files. Do not claim deployment when no upload occurred.
