# FTPS deployment

Requires macOS Keychain, Python 3, Git, and `lftp`.

In your own Terminal, from this repository, run:

```sh
./deploy.sh --setup-keychain
```

Enter the FTP password at the hidden prompt. The final bare `security -w`
prompts without including the password in shell history or process arguments.
This creates/updates the generic Keychain item `sacmaca.com-ftps-deploy` for
`efecan2@sacmaca.com`. The password is never stored in the repository.

Then validate connectivity without writing to production:

```sh
./deploy.sh --check
```

After committing and pushing application changes, specify only affected files:

```sh
./deploy.sh --dry-run mp3-player/script.js mp3-player/style.css
./deploy.sh --upload mp3-player/script.js mp3-player/style.css
```

Uploads overwrite the selected paths. There is no mirror or deletion operation.
The script rejects untracked/uncommitted files, symlinks, dotfiles, deployment
tooling, uploads, common backup/secret files, and server configuration dotfiles.
Review the explicit list: path filtering cannot identify secrets embedded in
otherwise ordinary source files. Build artifacts must be tracked and committed
before this script will upload them.

Explicit TLS on port 21 and certificate verification are mandatory. Passwords
are passed to lftp through an in-memory stdin pipe. Raw client output is suppressed
to prevent credential leakage; fixed diagnostic messages identify common errors.
FTP 530 means the server rejected authentication. Re-enter the dedicated FTP
password using `--setup-keychain`; if rejection persists, verify the account and
password with the host. A `--check` failure never uploads or changes remote files.
A failed upload can leave a partial file; correct
the cause and retry the selected file. No application files need uploading when
only these local deployment instructions/tools change.
