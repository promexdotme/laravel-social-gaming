/* Same-origin Laravel transport. Protected requests require the compiled runtime signer. */
(function () {
    'use strict';
    if (window.PromexGameSession) return;
    const script = document.currentScript;
    let parentSession = null;
    try {
        if (window.parent && window.parent !== window && window.parent.PromexGameSession) {
            parentSession = window.parent.PromexGameSession;
        }
    } catch (_) {}
    let csrf = script && script.getAttribute('data-csrf');
    if (!csrf) {
        csrf = (document.querySelector && document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : null)
            || (parentSession && parentSession.csrfToken)
            || (() => {
                try { return window.parent && window.parent !== window && window.parent.document.querySelector ? window.parent.document.querySelector('meta[name="csrf-token"]')?.content : null; } catch (_) { return null; }
            })()
            || (typeof document !== 'undefined' && document.cookie && document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/) ? decodeURIComponent(document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/)[1]) : null);
    }
    let runtimeKey = (script && script.getAttribute('data-runtime-key')) || (parentSession && parentSession.runtimeKey);
    let runtimeExpires = Number((script && script.getAttribute('data-runtime-expires')) || (parentSession && parentSession.runtimeExpires));
    const runtimeGame = (script && script.getAttribute('data-runtime-game')) || (parentSession && parentSession.runtimeGame);
    if (!csrf || !runtimeKey || !runtimeGame || !Number.isInteger(runtimeExpires)) throw new Error('Missing game runtime context');

    let runtimeSigner = null;
    let resolveSigner;
    let rejectSigner;
    const signerReady = new Promise((resolve, reject) => {
        resolveSigner = resolve;
        rejectSigner = reject;
    });
    signerReady.catch(() => {});
    let signerTimeout = setTimeout(() => rejectSigner(new Error('Compiled Promex runtime did not initialize')), 10000);
    if (signerTimeout && typeof signerTimeout.unref === 'function') signerTimeout.unref();
    function protectedUrl(value, method) {
        const url = new URL(value, window.location.href);
        return String(method || 'GET').toUpperCase() === 'POST'
            && url.origin === window.location.origin
            && /^\/game\/[A-Za-z0-9_]+\/server\/?$/.test(url.pathname);
    }
    function identity() {
        const bytes = window.crypto.getRandomValues(new Uint8Array(16));
        return {
            id: Array.from(bytes, b => b.toString(16).padStart(2, '0')).join(''),
            time: String(Math.floor(Date.now() / 1000))
        };
    }
    function signedHeaders(method, url, body) {
        if (!runtimeSigner) throw new Error('Compiled Promex runtime is required');
        const request = identity();
        const pathname = new URL(url, window.location.href).pathname.replace(/\/+$/, '') || '/';
        const signed = runtimeSigner(String(method).toUpperCase(), pathname, request.id, request.time, body);
        if (!signed || !signed.proof || !signed.sequence) throw new Error('Compiled runtime refused the game request');
        return {
            'X-CSRF-TOKEN': csrf,
            'X-Promex-Request': request.id,
            'X-Promex-Time': request.time,
            'X-Promex-Protocol': '2',
            'X-Promex-Expires': String(runtimeExpires),
            'X-Promex-Sequence': String(signed.sequence),
            'X-Promex-Proof': signed.proof
        };
    }

    const originalFetch = window.fetch.bind(window);
    window.fetch = async function (input, init) {
        const isRequest = typeof Request !== 'undefined' && input instanceof Request;
        const method = (init && init.method) || (isRequest ? input.method : 'GET');
        const url = isRequest ? input.url : String(input);
        if (!protectedUrl(url, method)) return originalFetch(input, init);
        const options = Object.assign({}, init);
        let body = options.body;
        if (body === undefined && isRequest) body = await input.clone().text();
        if (body === undefined || body === null) body = '';
        if (typeof body !== 'string') throw new Error('Protected game requests require a textual body');
        if (!runtimeSigner) await signerReady;
        const merged = new Headers(options.headers || (isRequest ? input.headers : undefined));
        Object.entries(signedHeaders(method, url, body)).forEach(([key, value]) => merged.set(key, value));
        options.headers = merged;
        options.body = body;
        options.method = method;
        options.credentials = 'same-origin';
        return originalFetch(isRequest ? input.url : input, options);
    };

    const open = XMLHttpRequest.prototype.open;
    const send = XMLHttpRequest.prototype.send;
    const setHeader = XMLHttpRequest.prototype.setRequestHeader;
    XMLHttpRequest.prototype.open = function (method, url, ...args) {
        this._promexMethod = String(method).toUpperCase();
        this._promexUrl = String(url);
        this._promexProtected = protectedUrl(this._promexUrl, this._promexMethod);
        return open.call(this, method, url, ...args);
    };
    XMLHttpRequest.prototype.setRequestHeader = function (name, value) {
        if (this._promexProtected && /^(x-csrf-token|x-promex-request|x-promex-time|x-promex-protocol|x-promex-expires|x-promex-sequence|x-promex-proof)$/i.test(name)) return;
        return setHeader.call(this, name, value);
    };
    XMLHttpRequest.prototype.send = function (body) {
        if (this._promexProtected) {
            if (body !== undefined && body !== null && typeof body !== 'string') throw new Error('Protected game requests require a textual body');
            Object.entries(signedHeaders(this._promexMethod, this._promexUrl, body || '')).forEach(([key, value]) => setHeader.call(this, key, value));
        }
        return send.call(this, body);
    };

    window.PromexGameSession = Object.freeze({
        version: 2,
        get csrfToken() { return csrf; },
        get runtimeKey() { return runtimeKey; },
        get runtimeExpires() { return runtimeExpires; },
        runtimeGame,
        installSigner(signer) {
            if (typeof signer !== 'function') throw new Error('Invalid runtime signer installation');
            runtimeSigner = signer;
            clearTimeout(signerTimeout);
            signerTimeout = null;
            resolveSigner();
        },
        async refresh() {
            const response = await originalFetch('/game/' + encodeURIComponent(runtimeGame) + '/runtime-session', {
                method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) throw new Error('Runtime session refresh rejected: ' + response.status);
            const next = await response.json();
            if (next.version !== 2 || typeof next.key !== 'string' || !Number.isInteger(next.expires)) {
                throw new Error('Invalid runtime session response');
            }
            runtimeKey = next.key;
            runtimeExpires = next.expires;
            return { key: runtimeKey, expires: runtimeExpires };
        }
    });

    // Static game iframes load this helper without runtime attributes. Once the
    // same-origin parent session is inherited, load the compiled signer locally
    // so direct fetch clients and legacy socket clients share the same gate.
    if (!window.PromexSocketBridge && document.createElement) {
        const bridge = document.createElement('script');
        bridge.src = '/js/ws-bridge.js?v=4';
        bridge.async = true;
        bridge.onerror = () => rejectSigner(new Error('Compiled Promex runtime is unavailable'));
        (document.head || document.documentElement).appendChild(bridge);
    }
})();
