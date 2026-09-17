/* Same-origin Laravel session transport. No license secrets or WASM authority in the browser. */
(function () {
    'use strict';
    if (window.PromexGameSession) return;
    const script = document.currentScript;
    let csrf = script && script.getAttribute('data-csrf');
    if (!csrf) {
        csrf = (document.querySelector && document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : null)
            || (() => {
                try { return window.parent && window.parent !== window && window.parent.document.querySelector ? window.parent.document.querySelector('meta[name="csrf-token"]')?.content : null; } catch (_) { return null; }
            })()
            || (typeof document !== 'undefined' && document.cookie && document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/) ? decodeURIComponent(document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/)[1]) : null);
    }
    if (!csrf) throw new Error('Missing game session context');

    function protectedUrl(value, method) {
        const url = new URL(value, window.location.href);
        return String(method || 'GET').toUpperCase() === 'POST'
            && url.origin === window.location.origin
            && /^\/game\/[A-Za-z0-9_]+\/server\/?$/.test(url.pathname);
    }
    function headers() {
        const bytes = window.crypto.getRandomValues(new Uint8Array(16));
        return {
            'X-CSRF-TOKEN': csrf,
            'X-Promex-Request': Array.from(bytes, b => b.toString(16).padStart(2, '0')).join(''),
            'X-Promex-Time': String(Math.floor(Date.now() / 1000))
        };
    }
    const originalFetch = window.fetch.bind(window);
    window.fetch = function (input, init) {
        const isRequest = typeof Request !== 'undefined' && input instanceof Request;
        const method = (init && init.method) || (isRequest ? input.method : 'GET');
        const url = isRequest ? input.url : String(input);
        if (!protectedUrl(url, method)) return originalFetch(input, init);
        const options = Object.assign({}, init);
        const merged = new Headers(options.headers || (isRequest ? input.headers : undefined));
        Object.entries(headers()).forEach(([key, value]) => merged.set(key, value));
        options.headers = merged;
        options.credentials = 'same-origin';
        // Keep the original body unchanged: JSON, FormData, binary and Request streams all work.
        return originalFetch(input, options);
    };
    const open = XMLHttpRequest.prototype.open;
    const send = XMLHttpRequest.prototype.send;
    const setHeader = XMLHttpRequest.prototype.setRequestHeader;
    XMLHttpRequest.prototype.open = function (method, url, ...args) {
        this._promexProtected = protectedUrl(String(url), method);
        return open.call(this, method, url, ...args);
    };
    XMLHttpRequest.prototype.setRequestHeader = function (name, value) {
        if (this._promexProtected && /^(x-csrf-token|x-promex-request|x-promex-time)$/i.test(name)) return;
        return setHeader.call(this, name, value);
    };
    XMLHttpRequest.prototype.send = function (body) {
        if (this._promexProtected) {
            Object.entries(headers()).forEach(([key, value]) => setHeader.call(this, key, value));
        }
        return send.call(this, body);
    };
    window.PromexGameSession = Object.freeze({ version: 1 });
})();
