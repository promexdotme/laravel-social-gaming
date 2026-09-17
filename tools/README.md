# Development checks

Run from the repository root with PHP 8.2+, Composer dependencies installed under `casino/vendor`, and Node.js:

```sh
php casino/tests/Installer/cleanup-regression.php
php casino/tests/Licensing/regression.php
node casino/tests/Licensing/bridge-regression.cjs
php casino/tests/Licensing/packager-regression.php
```

On Windows, test the real access rules against an installed Apache distribution in an isolated loopback-only instance:

```sh
python casino/tests/Installer/apache-access-regression.py C:/path/to/apache
```

Rebuild the packet codec reproducibly:

```sh
npm --prefix tools/wasm ci --ignore-scripts --no-audit --no-fund
npm --prefix tools/wasm run build
node casino/tests/Licensing/bridge-regression.cjs
```

Validate a completed customer release ZIP against the current checkout:

```sh
php tools/packaging/verify_prepack.php /path/to/release.zip
```

The verifier rejects backups, private runtime files and stale security files. Build tooling, test fixtures, dependencies for compiling WASM, and authority private keys must not be shipped in customer packages. The local database-export/repack workflow remains private and is not part of these checks.

WASM handles packet conversion; it is not a licensing trust boundary. PHP validates domain-bound signed certificates and game requests. Any customer-controlled PHP can be patched; CDN, feed and download services must enforce purchases independently on infrastructure operated by the publisher.
