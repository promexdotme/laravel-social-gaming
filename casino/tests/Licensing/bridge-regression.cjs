const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { webcrypto } = require('node:crypto');
const root = path.resolve(__dirname, '../../..');
const bytes = fs.readFileSync(path.join(root, 'js/ws-bridge.wasm'));
const helper = fs.readFileSync(path.join(root, 'js/game-session.js'), 'utf8');
const bridge = fs.readFileSync(path.join(root, 'js/ws-bridge.js'), 'utf8');
const pause = () => new Promise(resolve => setTimeout(resolve, 80));

async function context(missing = false) {
    const requests = [], xhrs = [];
    class XHR {
        open(method, url) { this.method = method; this.url = url; this.headers = {}; }
        setRequestHeader(k, v) { this.headers[k] = v; }
        send(body) { this.body = body; xhrs.push(this); }
    }
    const c = { console: { log() {}, error() {} }, XMLHttpRequest: XHR, crypto: webcrypto,
        URL, Request, Headers, ArrayBuffer, Uint8Array, Int8Array, DataView, TextEncoder, Promise, Math, Date, JSON, setTimeout,
        document: { currentScript: { getAttribute: () => 'test-session-csrf' } },
        sessionStorage: { removeItem() {}, getItem() { return null; } },
        location: { hostname: 'audit.invalid', host: 'audit.invalid', origin: 'https://audit.invalid',
            pathname: '/game/AuditGame', href: 'https://audit.invalid/game/AuditGame' }
    };
    c.window = c;
    c.fetch = async (input, options) => {
        const url = input instanceof Request ? input.url : String(input);
        if (url.startsWith('/js/ws-bridge.wasm')) return { ok: !missing, status: missing ? 404 : 200, arrayBuffer: async () => bytes };
        requests.push({ input, url, options });
        return { ok: true, status: 200, text: async () => '{}', json: async () => ({ responseHex: '0102ff' }) };
    };
    vm.createContext(c);
    vm.runInContext(helper, c);
    vm.runInContext(bridge, c);
    const socket = new c.WebSocket('wss://audit.invalid/slots-socket');
    await pause();
    return { c, requests, xhrs, socket };
}
(async () => {
    const { instance: { exports: codec } } = await WebAssembly.instantiate(bytes, {});
    assert.equal(codec.abi_version(), 1);
    assert.equal(codec.init, undefined);
    assert.equal(codec.sign_frame, undefined);
    const mem = new Uint8Array(codec.memory.buffer);
    mem.set(Buffer.from('01aBff'), 32768);
    assert.equal(codec.hex_decode(32768, 6, 1024), 3);
    assert.deepEqual(Array.from(mem.slice(1024, 1027)), [1, 171, 255]);
    assert.equal(codec.hex_decode(32768, 3, 1024), -1);
    assert.equal(codec.hex_decode(32768, 32770, 1024), -1);
    assert.equal(codec.hex_decode(131071, 6, 1024), -1);
    mem.set(Buffer.from('xz'), 32768);
    assert.equal(codec.hex_decode(32768, 2, 1024), -1);

    const failed = await context(true);
    failed.socket.send('{"gameName":"AuditGame","command":"login"}');
    assert.equal(failed.socket.readyState, 3);
    assert.equal(failed.requests.length, 0);
    const { c, requests, xhrs, socket } = await context();
    assert.equal(socket.readyState, 1);
    socket.send(JSON.stringify({ gameName: 'AuditGame', command: 'login', label: 'é 🎲' }));
    await pause();
    const first = requests[0];
    assert.equal(first.options.headers.get('X-CSRF-TOKEN'), 'test-session-csrf');
    assert.match(first.options.headers.get('X-Promex-Request'), /^[a-f0-9]{32}$/);
    assert.equal(first.options.headers.get('X-Wasm-Sig'), null);
    assert.equal(JSON.parse(first.options.body).label, 'é 🎲');

    // Native HTTP clients use the same authentication without depending on WASM or packet layout.
    const body = '{"msgId":1,"bet":10}';
    await c.fetch('/game/AuditGame/server', { method: 'POST', body });
    assert.equal(requests.at(-1).options.body, body);
    assert.notEqual(first.options.headers.get('X-Promex-Request'), requests.at(-1).options.headers.get('X-Promex-Request'));
    await c.fetch(new Request('https://audit.invalid/game/AuditGame/server', { method: 'POST', body }));
    assert.equal(await requests.at(-1).input.clone().text(), body);
    assert.equal(requests.at(-1).options.headers.get('X-CSRF-TOKEN'), 'test-session-csrf');
    await c.fetch('https://external.invalid/game/AuditGame/server', { method: 'POST', body });
    assert.equal(requests.at(-1).options.headers, undefined, 'never leak session headers cross-origin');
    const xhr = new c.XMLHttpRequest();
    xhr.open('POST', '/game/AuditGame/server');
    xhr.setRequestHeader('X-CSRF-TOKEN', 'old-token');
    xhr.send(body);
    assert.equal(xhrs[0].body, body);
    assert.equal(xhrs[0].headers['X-CSRF-TOKEN'], 'test-session-csrf');
    assert.match(xhrs[0].headers['X-Promex-Request'], /^[a-f0-9]{32}$/);

    // Binary EGT-style handshake and subsequent request exercise the compiled response codec.
    const binary = new c.WebSocket('wss://audit.invalid/slots-socket'); await pause();
    const messages = []; binary.onmessage = event => messages.push(event.data);
    binary.send(new TextEncoder().encode(':::{"gameName":"AuditGame","sessionId":"test"}'));
    await pause();
    binary.send(new Uint8Array([1, 2, 3, 4, 5, 6, 37])); await pause();
    assert.deepEqual(Array.from(new Uint8Array(messages.at(-1))), [1, 2, 255]);
    assert.equal(requests.at(-1).options.headers.get('X-CSRF-TOKEN'), 'test-session-csrf');
    console.log('PASS: WASM codec, closed failure, JSON/Unicode, binary bridge, fetch/Request, XHR and credential isolation');
})().catch(error => { console.error(error); process.exitCode = 1; });
