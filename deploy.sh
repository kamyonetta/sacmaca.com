#!/bin/bash
# Never enable shell tracing: credentials must stay out of logs.
set +x
set -eu
cd -- "$(dirname -- "$0")"
if [ "${1:-}" = "--setup-keychain" ]; then
    [ "$#" -eq 1 ] || exit 2
    # A final bare -w prompts securely; no password in argv or shell history.
    exec /usr/bin/security add-generic-password -U \
        -a 'deploy@sacmaca.com' -s 'sacmaca.com-ftps-deploy' \
        -l 'sacmaca.com FTPS deployment' -T /usr/bin/security -w
fi
exec python3 - "$@" <<'PY'
import pathlib
import re
import shutil
import subprocess
import sys

def fail(message):
    sys.exit(message)

args = sys.argv[1:]
if not args or args == ['--help']:
    print('''Usage:
  ./deploy.sh --setup-keychain       Store/update password via hidden local prompt
  ./deploy.sh --check                Read-only FTPS connection/root check
  ./deploy.sh --dry-run FILE [...]   Validate and list explicit tracked files
  ./deploy.sh --upload FILE [...]    Upload only those files, never mirror/delete

Keychain service: sacmaca.com-ftps-deploy
Account: deploy@sacmaca.com
Run --check before uploading. Paths are relative to this repository.
Commit changes before uploading. No default full-site deployment.''')
    sys.exit(0)
mode, *files = args
if mode not in ('--check', '--dry-run', '--upload') or (mode == '--check' and files) or (mode != '--check' and not files):
    fail('Invalid arguments; use --help.')
root = pathlib.Path.cwd()
tracked = set(subprocess.check_output(['git', 'ls-files', '-z']).decode().split('\0'))
for name in files:
    p = pathlib.PurePosixPath(name)
    parts = p.parts
    blocked = {'wp-config.php', 'wp-config-sample.php', 'deploy.sh', 'AGENTS.md',
               'DEPLOYMENT.md', 'node_modules', 'uploads', 'cache', 'upgrade',
               'backups', 'backup', 'cgi-bin', 'error_log'}
    if (not re.fullmatch(r'[A-Za-z0-9_./-]+', name) or p.is_absolute()
            or any(x.startswith('.') or x in blocked for x in parts)
            or name not in tracked or name.endswith(('.key', '.pem', '.sql', '.log', '.bak', '.bk'))
            or any((root.joinpath(*parts[:i])).is_symlink() for i in range(1, len(parts) + 1))
            or not (root / name).is_file()):
        fail(f'Refusing protected, untracked, or invalid path: {name}')
    if subprocess.check_output(['git', 'status', '--porcelain', '--', name]):
        fail(f'Commit this file before deploying: {name}')
if mode == '--dry-run':
    print('\n'.join('Would upload /' + name for name in files))
    sys.exit(0)
lftp = shutil.which('lftp')
if not lftp:
    fail('lftp is required.')
credential = subprocess.run(['/usr/bin/security', 'find-generic-password',
    '-a', 'deploy@sacmaca.com', '-s', 'sacmaca.com-ftps-deploy', '-w'],
    stdout=subprocess.PIPE, stderr=subprocess.DEVNULL)
if credential.returncode:
    fail('Keychain credential unavailable. Run ./deploy.sh --setup-keychain in your local Terminal.')
password = credential.stdout.decode().removesuffix('\n')
if not password or any(c in password for c in '\r\n\0'):
    fail('Credential is empty or contains unsupported control characters.')
def quote(value):
    return '"' + re.sub(r'([\\"$`])', r'\\\1', value) + '"'
commands = [
    'set cmd:fail-exit yes', 'set net:timeout 20', 'set net:max-retries 1',
    'set ftp:ssl-force yes', 'set ftp:ssl-auth TLS',
    'set ftp:ssl-protect-data yes', 'set ssl:verify-certificate yes',
    'set ftp:passive-mode yes', 'set xfer:clobber yes',
    'open ftp://ams201.greengeeks.net:21',
    'user "deploy@sacmaca.com" ' + quote(password),
    'cd /', 'cls -d index.php wp-admin wp-content wp-includes',
]
for name in files:
    parent = str(pathlib.PurePosixPath(name).parent)
    if parent != '.':
        commands.append('mkdir -p ' + quote('/' + parent))
    commands.append('put ' + quote(str(root / name)) + ' -o ' + quote('/' + name))
commands.append('bye')
# Pipe commands in memory. Never use a credential-bearing command argument/file.
# Suppress raw client output because authentication failures can echo input.
try:
    result = subprocess.run([lftp, '--norc'], input='\n'.join(commands) + '\n',
        text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=300)
except subprocess.TimeoutExpired:
    fail('FTPS timed out; uploads may be partial. Raw output suppressed to protect credentials.')
if result.returncode:
    fail('FTPS failed; uploads may be partial. Check Keychain access, connectivity, certificate trust, and remote paths. Raw output suppressed to protect credentials.')
print('FTPS root check passed.' if mode == '--check' else 'Uploaded:\n' + '\n'.join(files))
PY
