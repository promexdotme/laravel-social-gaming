"""Test the real .htaccess in a temporary Apache instance on loopback only."""
import argparse
import http.client
from pathlib import Path
import shutil
import socket
import subprocess
import tempfile
import time

parser = argparse.ArgumentParser()
parser.add_argument('apache_root', type=Path)
args = parser.parse_args()
source = Path(__file__).resolve().parents[3]
apache = args.apache_root.resolve()
with socket.socket() as sock:
    sock.bind(('127.0.0.1', 0))
    port = sock.getsockname()[1]
with tempfile.TemporaryDirectory(prefix='promex-apache-test-') as directory:
    root = Path(directory)
    web = root / 'www'
    web.mkdir()
    shutil.copy2(source / '.htaccess', web / '.htaccess')
    forbidden = ['install.sql', 'database_backup.sql', 'release.zip', 'old.php.bak',
                 '.env', 'installed.lock', 'license.cert', 'license.cert.tmp',
                 'casino/storage/framework/license.cert', 'casino/storage/logs/laravel.log',
                 'casino/app/Example.php', 'casino/config/app.php', 'casino/bootstrap/cache/config.php',
                 'casino/resources/views/test.blade.php', 'casino/vendor/autoload.php',
                 'casino/server.php', 'casino/composer.json', 'localscripts/private.txt', '_access/private.txt']
    allowed = ['js/game-session.js', 'js/ws-bridge.wasm', 'public/logo.png', 'frontend/theme.css', 'install.php']
    for relative in forbidden + allowed:
        file = web / relative
        file.parent.mkdir(parents=True, exist_ok=True)
        file.write_text('fixture only', encoding='utf-8')
    lines = [f'ServerRoot "{apache.as_posix()}"', f'Listen 127.0.0.1:{port}', 'ServerName localhost',
             f'PidFile "{(root / "httpd.pid").as_posix()}"', f'ErrorLog "{(root / "error.log").as_posix()}"']
    for name in ['authz_core', 'authz_host', 'mime', 'dir', 'rewrite']:
        lines.append(f'LoadModule {name}_module modules/mod_{name}.so')
    lines += [f'DocumentRoot "{web.as_posix()}"', f'<Directory "{web.as_posix()}">',
              'AllowOverride All', 'Require all granted', '</Directory>', 'DirectoryIndex index.php',
              'TypesConfig conf/mime.types']
    config = root / 'httpd.conf'
    config.write_text('\n'.join(lines), encoding='utf-8')
    executable = apache / 'bin/httpd.exe'
    subprocess.run([str(executable), '-t', '-f', str(config)], check=True, capture_output=True)
    process = subprocess.Popen([str(executable), '-X', '-f', str(config)],
                               stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL,
                               creationflags=getattr(subprocess, 'CREATE_NO_WINDOW', 0))
    def status(path):
        conn = http.client.HTTPConnection('127.0.0.1', port, timeout=3)
        try:
            conn.request('GET', '/' + path)
            res = conn.getresponse()
            res.read()
            return res.status
        finally:
            conn.close()
    try:
        for attempt in range(30):
            try:
                status('public/logo.png')
                break
            except OSError:
                time.sleep(0.1)
        for path in forbidden:
            actual = status(path)
            assert actual == 403, f'{path}: expected 403, got {actual}; { (root / "error.log").read_text() }'
        for path in allowed:
            actual = status(path)
            assert actual == 200, f'{path}: expected 200, got {actual}'
        assert status('install') == 200, 'clean installer URL must work before cleanup'
        print(f'PASS: real Apache denied {len(forbidden)} private paths; assets and installer remained accessible')
    finally:
        process.terminate()
        process.wait(timeout=10)
