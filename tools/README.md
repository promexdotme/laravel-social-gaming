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

The paid Promex runtime is built from the private, Git-ignored `localscripts/wasm-build` workspace:

```sh
npm --prefix localscripts/wasm-build ci --ignore-scripts --no-audit --no-fund
npm --prefix localscripts/wasm-build test
npm --prefix localscripts/wasm-build run build
node casino/tests/Licensing/bridge-regression.cjs
```

Validate a completed customer release ZIP against the current checkout:

```sh
php tools/packaging/verify_prepack.php /path/to/release.zip
```

The verifier rejects backups, private runtime files and stale security files. Build tooling, test fixtures, dependencies for compiling WASM, and authority private keys must not be shipped in customer packages. The local database-export/repack workflow remains private and is not part of these checks.

The paid ABI 2 WASM runtime is functionally required for protocol parsing, packet templates and short-lived request proofs. PHP independently validates domain-bound signed certificates and every runtime proof. Customer-controlled browser and PHP code can still be patched; this is copying resistance rather than an unbreakable trust boundary.

New first-party HTML games load `js/promex-html-game.js`, which establishes the iframe runtime in the required order and exposes the small `PromexHtmlGame.request()` API. Vendor slot packages keep their existing historical socket-script references.
