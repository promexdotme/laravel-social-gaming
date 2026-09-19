const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { webcrypto, createHmac } = require('node:crypto');
const runtimeKey = Buffer.alloc(32, 7);
const runtimeExpires = Math.floor(Date.now() / 1000) + 1200;
const root = path.resolve(__dirname, '../../..');
const bytes = fs.readFileSync(path.join(root, 'js/ws-bridge.wasm'));
const helper = fs.readFileSync(path.join(root, 'js/game-session.js'), 'utf8');
const bridge = fs.readFileSync(path.join(root, 'js/ws-bridge.js'), 'utf8');
const pause = () => new Promise(resolve => setTimeout(resolve, 80));

async function context(missing = false, iframe = false) {
    const requests = [], xhrs = [];
    class XHR {
        open(method, url) { this.method = method; this.url = url; this.headers = {}; }
        setRequestHeader(k, v) { this.headers[k] = v; }
        send(body) { this.body = body; xhrs.push(this); }
    }
    const c = { console: { log() {}, error() {} }, XMLHttpRequest: XHR, crypto: webcrypto,
        URL, Request, Headers, ArrayBuffer, Uint8Array, Int8Array, DataView, TextEncoder, TextDecoder, Promise, Math, Date, JSON, setTimeout,
        atob: value => Buffer.from(value, 'base64').toString('binary'), clearTimeout,
        document: { currentScript: { getAttribute: name => ({
            'data-csrf': 'test-session-csrf',
            'data-runtime-key': runtimeKey.toString('base64'),
            'data-runtime-expires': String(runtimeExpires),
            'data-runtime-game': 'AuditGame'
        })[name] || null } },
        sessionStorage: { removeItem() {}, getItem() { return null; } },
        location: { hostname: 'audit.invalid', host: 'audit.invalid', origin: 'https://audit.invalid',
            pathname: '/game/AuditGame', href: 'https://audit.invalid/game/AuditGame' }
    };
    c.window = c;
    c.parent = c;
    const baseFetch = async (input, options) => {
        const url = input instanceof Request ? input.url : String(input);
        if (url.startsWith('/js/ws-bridge.wasm')) return { ok: !missing, status: missing ? 404 : 200, arrayBuffer: async () => bytes };
        requests.push({ input, url, options });
        return { ok: true, status: 200, text: async () => '{}', json: async () => ({ responseHex: '0102ff' }) };
    };
    c.fetch = baseFetch;
    if (iframe) {
        const parent = Object.assign({}, c, { window: null, parent: null, fetch: baseFetch });
        parent.window = parent;
        parent.parent = parent;
        vm.createContext(parent);
        vm.runInContext(helper, parent);
        c.parent = parent;
    }
    vm.createContext(c);
    if (!iframe) vm.runInContext(helper, c);
    vm.runInContext(bridge, c);
    const socket = new c.WebSocket('wss://audit.invalid/slots-socket');
    await pause();
    return { c, requests, xhrs, socket };
}
async function directIframeContext() {
    const parentState = await context();
    const child = Object.assign({}, parentState.c, {
        window: null,
        parent: parentState.c,
        fetch: parentState.c.fetch,
        location: { hostname: 'audit.invalid', host: 'audit.invalid', origin: 'https://audit.invalid',
            pathname: '/games/AuditGame/index.html', href: 'https://audit.invalid/games/AuditGame/index.html' },
        document: {
            currentScript: { getAttribute() { return null; } },
            querySelector() { return null; },
            cookie: ''
        }
    });
    child.window = child;
    vm.createContext(child);
    vm.runInContext(helper, child);
    vm.runInContext(bridge, child);
    await pause();
    return { child, requests: parentState.requests };
}
(async () => {
    const { instance: { exports: codec } } = await WebAssembly.instantiate(bytes, { env: { abort() { throw new Error('WASM abort'); } } });
    assert.equal(codec.abi_version(), 2);
    const mem = new Uint8Array(codec.memory.buffer);
    const input = codec.input_ptr(), output = codec.output_ptr();
    mem.set(runtimeKey, input);
    assert.equal(codec.init(input, 32, runtimeExpires, Math.floor(Date.now() / 1000)), 1);
    mem.set(Buffer.from('01aBff'), input);
    assert.equal(codec.hex_decode(input, 6, output), 3);
    assert.deepEqual(Array.from(mem.slice(output, output + 3)), [1, 171, 255]);
    assert.equal(codec.hex_decode(input, 3, output), -1);
    mem.set(Buffer.from('xz'), input);
    assert.equal(codec.hex_decode(input, 2, output), -1);

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
    assert.equal(first.options.headers.get('X-Promex-Protocol'), '2');
    assert.match(first.options.headers.get('X-Promex-Proof'), /^[a-f0-9]{64}$/);
    assert.equal(first.options.headers.get('X-Promex-Expires'), String(runtimeExpires));
    assert.equal(JSON.parse(first.options.body).label, 'é 🎲');
    const canonical = ['POST', '/game/AuditGame/server', first.options.headers.get('X-Promex-Request'),
        first.options.headers.get('X-Promex-Time'), first.options.headers.get('X-Promex-Sequence'), first.options.body].join('\n');
    assert.equal(first.options.headers.get('X-Promex-Proof'), createHmac('sha256', runtimeKey).update(canonical).digest('hex'));

    const framed = await context(false, true);
    framed.socket.send('{"gameName":"AuditGame","command":"login","frame":"parent"}');
    await pause();
    assert.equal(framed.requests.length, 1);
    assert.match(framed.requests[0].options.headers.get('X-Promex-Proof'), /^[a-f0-9]{64}$/, 'CDN iframe uses parent launch session');

    const direct = await directIframeContext();
    await direct.child.fetch('/game/AuditGame/server', { method: 'POST', body: '{"action":"init"}' });
    assert.match(direct.requests.at(-1).options.headers.get('X-Promex-Proof'), /^[a-f0-9]{64}$/,
        'static iframe helper inherits parent context and signs direct fetch');

    // Native HTTP clients use the same authentication without depending on WASM or packet layout.
    const body = '{"msgId":1,"bet":10}';
    await c.fetch('/game/AuditGame/server', { method: 'POST', body });
    assert.equal(requests.at(-1).options.body, body);
    assert.notEqual(first.options.headers.get('X-Promex-Request'), requests.at(-1).options.headers.get('X-Promex-Request'));
    await c.fetch(new Request('https://audit.invalid/game/AuditGame/server', { method: 'POST', body }));
    assert.equal(requests.at(-1).options.body, body);
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
    assert.match(xhrs[0].headers['X-Promex-Proof'], /^[a-f0-9]{64}$/);

    // Binary EGT-style handshake and subsequent request exercise the compiled response codec.
    const binary = new c.WebSocket('wss://audit.invalid/slots-socket'); await pause();
    const messages = []; binary.onmessage = event => messages.push(event.data);
    binary.send(new TextEncoder().encode(':::{"gameName":"AuditGame","sessionId":"test"}'));
    await pause();
    binary.send(new Uint8Array([1, 2, 3, 4, 5, 6, 37])); await pause();
    assert.deepEqual(Array.from(new Uint8Array(messages.at(-1))), [1, 2, 255]);
    assert.equal(requests.at(-1).options.headers.get('X-CSRF-TOKEN'), 'test-session-csrf');
    console.log('PASS: ABI 2 runtime gate, closed failure, protocol parsing, signed fetch/XHR, binary bridge and credential isolation');
})().catch(error => { console.error(error); process.exitCode = 1; });
