/* Shared bootstrap for new same-origin HTML games. */
(function () {
    'use strict';
    if (window.PromexHtmlGame) return;

    const script = document.currentScript;
    const game = script && script.getAttribute('data-game');
    if (!game || !/^[A-Za-z0-9_]+$/.test(game)) {
        throw new Error('Promex HTML game requires a valid data-game value');
    }

    function loadScript(src, ready) {
        if (ready()) return Promise.resolve();
        return new Promise((resolve, reject) => {
            const element = document.createElement('script');
            element.src = src;
            element.async = false;
            element.onload = () => ready() ? resolve() : reject(new Error(src + ' did not initialize'));
            element.onerror = () => reject(new Error('Unable to load ' + src));
            (document.head || document.documentElement).appendChild(element);
        });
    }

    const ready = (async () => {
        await loadScript('/js/game-session.js?v=3', () => Boolean(window.PromexGameSession));
        if (window.PromexGameSession.runtimeGame !== game) {
            throw new Error('Game runtime context does not match ' + game);
        }
        await loadScript('/js/cedar-client.js?v=4', () => Boolean(window.CedarFairness));
        window.dispatchEvent(new CustomEvent('promex:game-ready', { detail: { game } }));
        return true;
    })();

    ready.catch(error => {
        window.dispatchEvent(new CustomEvent('promex:game-error', { detail: { game, error } }));
    });

    async function request(action, payload) {
        await ready;
        const response = await window.fetch('/game/' + encodeURIComponent(game) + '/server', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(Object.assign({}, payload || {}, { action }))
        });
        let data;
        try {
            data = await response.json();
        } catch (_) {
            throw new Error('The game server returned an unreadable response.');
        }
        if (!response.ok || data.status === 'error' || data.error) {
            const fallback = response.status === 403
                ? 'The licensed game session was rejected. Return to the lobby and reopen the game.'
                : 'The game request failed (HTTP ' + response.status + ').';
            throw new Error(data.message || data.error || fallback);
        }
        window.dispatchEvent(new CustomEvent('promex:game-response', { detail: { game, action, data } }));
        return data;
    }

    window.PromexHtmlGame = Object.freeze({
        version: 1,
        game,
        ready,
        request,
        verify(proof) {
            if (!window.CedarFairness) throw new Error('Round verifier is unavailable.');
            return window.CedarFairness.verify(proof);
        }
    });
})();
