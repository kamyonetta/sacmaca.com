#!/bin/bash
# Never enable shell tracing: credentials must stay out of logs.
set +x
set -eu
cd -- "$(dirname -- "$0")"
if [ "${1:-}" = "--setup-keychain" ]; then
    [ "$#" -eq 1 ] || exit 2
    # A final bare -w prompts securely; no password in argv or shell history.
    exec /usr/bin/security add-generic-password -U \
        -a 'efecan2@sacmaca.com' -s 'sacmaca.com-ftps-deploy' \
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
Account: efecan2@sacmaca.com
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
    '-a', 'efecan2@sacmaca.com', '-s', 'sacmaca.com-ftps-deploy', '-w'],
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
    'open -u ' + quote('efecan2@sacmaca.com,' + password) + ' ftp://ams201.greengeeks.net:21',
    'cd /', 'cls -d index.php wp-admin wp-content wp-includes',
]
for name in files:
    parent = str(pathlib.PurePosixPath(name).parent)
    if parent != '.':
        # This server returns 550 for mkdir -p on an existing directory.
        # Check it first; conditional failure must not abort the upload batch.
        commands.append('cd ' + quote('/' + parent) + ' || mkdir -p ' + quote('/' + parent))
        commands.append('cd /')
    commands.append('put ' + quote(str(root / name)) + ' -o ' + quote('/' + name))
commands.append('bye')
# Pipe commands in memory. Never use a credential-bearing command argument/file.
# Suppress raw client output because authentication failures can echo input.
def failure_reason(output):
    # Only return fixed descriptions, never server/client text or credentials.
    output = output.lower()
    if '530' in output or 'login failed' in output:
        return ('Server rejected authentication (FTP 530). Re-enter the dedicated FTP '
                'account password using ./deploy.sh --setup-keychain. If it still fails, '
                'the FTP account/password or account access needs verification with the host.')
    if 'certificate' in output:
        return 'TLS certificate validation failed; verify server certificate/trust without disabling verification.'
    if any(x in output for x in ('name or service', 'nodename', 'name resolution', 'host name lookup')):
        return 'FTPS hostname lookup failed; check DNS and network access.'
    if '550' in output:
        return 'Server denied access to an expected path (FTP 550); check account root and permissions.'
    if 'connection refused' in output:
        return 'FTPS connection refused; check server availability and port 21 access.'
    if 'timed out' in output or 'timeout' in output:
        return 'FTPS connection timed out; check network and passive FTP access.'
    return 'FTPS client failed. Further terminal diagnosis is needed; raw output suppressed to protect credentials.'

failure_context = ('Read-only check failed; no files were uploaded. ' if mode == '--check'
                   else 'Upload failed; selected files may be partial. ')
try:
    result = subprocess.run([lftp, '--norc'], input='\n'.join(commands) + '\n',
        text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=300)
except subprocess.TimeoutExpired:
    fail(failure_context + 'FTPS timed out.')
if result.returncode:
    fail(failure_context + failure_reason(result.stderr + result.stdout))
print('FTPS root check passed.' if mode == '--check' else 'Uploaded:\n' + '\n'.join(files))
PY
