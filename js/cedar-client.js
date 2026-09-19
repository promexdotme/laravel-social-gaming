/* Cedar v2 transport and independent outcome verifier. No server secrets before settlement. */
(() => {
    'use strict';
    const nativeFetch = window.fetch.bind(window);
    const states = new Map();
    const encoder = new TextEncoder();
    let lastProof = null;
    const hex = bytes => Array.from(new Uint8Array(bytes), b => b.toString(16).padStart(2, '0')).join('');
    const sha = async value => hex(await crypto.subtle.digest('SHA-256', encoder.encode(value)));
    const hmac = async (secret, message) => {
        const key = await crypto.subtle.importKey('raw', encoder.encode(secret), {name: 'HMAC', hash: 'SHA-256'}, false, ['sign']);
        return hex(await crypto.subtle.sign('HMAC', key, encoder.encode(message)));
    };
    const sample = async (p, size, cursor = 0) => {
        const limit = Math.floor(4294967296 / size) * size;
        for (let attempt = 0; ; attempt++) {
            const hash = await hmac(p.server_seed, `${p.client_seed}:${p.nonce}:${cursor}:${attempt}`);
            const value = parseInt(hash.slice(0, 8), 16);
            if (value < limit) return value % size;
        }
    };
    async function verify(p) {
        if (!crypto.subtle) throw new Error('Open this site over HTTPS to verify rounds in your browser.');
        if (p.math_version !== 'cedar-v2') throw new Error('Unsupported math version.');
        if (await sha(p.server_seed) !== p.server_seed_hash) throw new Error('Seed hash mismatch.');
        const o = p.outcome;
        let valid = false;
        if (p.game === 'CedarDice') valid = await sample(p, 10000) === Math.round(Number(o.roll) * 100);
        if (p.game === 'CedarWheel') valid = await sample(p, p.parameters.segments) === o.winning_index;
        if (p.game === 'CedarPlinko') {
            const directions = [];
            for (let i = 0; i < p.parameters.rows; i++) directions.push(await sample(p, 2, i));
            valid = JSON.stringify(directions) === JSON.stringify(o.directions) && directions.reduce((a, b) => a + b, 0) === o.slot_index;
        }
        if (p.game === 'CedarMines') {
            const tiles = Array.from({length: 25}, (_, i) => i);
            for (let i = 24; i > 0; i--) {
                const j = await sample(p, i + 1, 24 - i);
                [tiles[i], tiles[j]] = [tiles[j], tiles[i]];
            }
            valid = JSON.stringify(tiles.slice(0, p.parameters.mines).sort((a, b) => a - b)) === JSON.stringify(o.mine_positions);
        }
        if (p.game === 'RoyalSteps') {
            const draw = await sample(p, 10000);
            const failed = p.parameters.ladder.findIndex(mult => draw >= Math.floor((100 - p.rules.house_edge) * 100 / mult));
            valid = (failed === -1 ? 11 : failed + 1) === o.trap_step;
        }
        if (p.game === 'CedarCrash') {
            const hash = await hmac(p.server_seed, `${p.client_seed}:${p.nonce}`);
            const u = parseInt(hash.slice(0, 13), 16) / 4503599627370496;
            const point = Math.max(1, Math.min(p.rules.max_multiplier, Math.floor((1 - p.rules.house_edge / 100) / (1 - u) * 100) / 100));
            valid = point === Number(o.crash_multiplier);
        }
        if (p.game === 'CedarLimbo') valid = await sample(p, 10000) === Number(o.draw);
        if (p.game === 'CedarCoinFlip') valid = (await sample(p, 2) ? 'tails' : 'heads') === o.outcome;
        const cardAt = async index => ({rank: await sample(p, 13, index * 2), suit: await sample(p, 4, index * 2 + 1)});
        if (p.game === 'CedarHiLo') {
            valid = JSON.stringify(await cardAt(0)) === JSON.stringify(o.up_card)
                && JSON.stringify(await cardAt(1)) === JSON.stringify(o.draw_card);
        }
        if (p.game === 'CedarBlackjack') {
            const deck = [];
            for (let i = 0; i < p.parameters.deck_size; i++) deck.push(await cardAt(i));
            valid = JSON.stringify(deck) === JSON.stringify(o.deck);
        }
        if (p.game === 'CedarKeno') {
            const pool = Array.from({length: 40}, (_, i) => i + 1), drawn = [];
            for (let i = 0; i < 10; i++) { const j = await sample(p, 40 - i, i); drawn.push(pool[j]); pool.splice(j, 1); }
            drawn.sort((a,b)=>a-b); valid = JSON.stringify(drawn) === JSON.stringify(o.drawn);
        }
        if (['CedarTower','CedarGoal','CedarTreasure'].includes(p.game)) {
            const hazards=[]; for(let i=0;i<p.parameters.levels;i++) hazards.push(await sample(p,p.parameters.choices,i));
            valid = JSON.stringify(hazards) === JSON.stringify(o.hazards);
        }
        if (!valid) throw new Error('Outcome does not match the seed.');
        return 'Seed and outcome verified locally. Payout rules are included in the round record.';
    }
    window.CedarFairness = {verify, last: () => lastProof};

    window.fetch = async function (input, options = {}) {
        const url = typeof input === 'string' ? new URL(input, location.href) : null;
        const match = url && url.origin === location.origin && url.pathname.match(/^\/game\/(CedarDice|CedarWheel|CedarPlinko|CedarMines|CedarCrash|RoyalSteps|CedarLimbo|CedarTower|CedarKeno|CedarCoinFlip|CedarGoal|CedarTreasure|CedarHiLo|CedarBlackjack)\/server$/);
        if (!match || typeof options.body !== 'string') return nativeFetch(input, options);
        const game = match[1];
        let body;
        try { body = JSON.parse(options.body); } catch (_) { return nativeFetch(input, options); }
        const state = states.get(game) || {commitment: null, betId: null, pending: null, commitments: {}};
        states.set(game, state);
        const isBet = ['bet', 'roll', 'spin', 'drop', 'play', 'flip'].includes(body.action);
        if (isBet) {
            if (state.pending) throw new Error('Previous wager unresolved. Reload to recover its result.');
            body.request_id = typeof crypto.randomUUID === 'function' ? crypto.randomUUID() : hex(crypto.getRandomValues(new Uint8Array(16)));
            body.server_seed_hash = state.commitment;
            state.pending = body.request_id;
        } else if (['cashout', 'step', 'reveal', 'status', 'crash', 'verify', 'hit', 'stand'].includes(body.action)) {
            body.bet_id = body.bet_id || state.betId;
        }
        const opts = {...options, body: JSON.stringify(body)};
        let response;
        try { response = await nativeFetch(input, opts); }
        catch (_) { response = await nativeFetch(input, opts); } // same request ID, never a second wager
        const data = await response.clone().json();
        if (isBet && response.ok) state.pending = null;
        if (data.status === 'error') {
            if (body.action === 'init') alert(data.message || 'Unable to load this game.');
            return response;
        }
        if (isBet && data.bet_id) state.commitments[data.bet_id] = body.server_seed_hash;
        if (body.action === 'init') {
            state.commitment = data.server_seed_hash;
            state.betId = data.active_game?.bet_id || null;
            state.pending = null;
            if (data.last_proof) lastProof = data.last_proof;
            const cap = document.getElementById('cedar-payout-cap');
            if (cap) cap.dataset.amount = data.max_payout;
            if (cap) cap.textContent = `Max payout: ${Number(data.max_payout).toLocaleString()} CEDARS`;
        } else {
            if (data.bet_id) state.betId = data.bet_id;
            if (data.next_server_seed_hash) state.commitment = data.next_server_seed_hash;
        }
        if (data.proof || data.active_game?.proof) {
            lastProof = data.proof || data.active_game.proof;
            lastProof.observed_commitment = state.commitments[lastProof.bet_id] || lastProof.observed_commitment;
            // A proof is only returned for a settled round. Do not let later
            // actions inherit its ID or make a finished game look active.
            state.betId = null;
            try { sessionStorage.setItem(`cedar-proof-${game}`, JSON.stringify(lastProof)); } catch (_) {}
        }
        window.dispatchEvent(new CustomEvent('cedar:response', {detail: {game, data}}));
        return response;
    };

    document.addEventListener('DOMContentLoaded', () => {
        const game = location.pathname.match(/(?:Cedar(?:Dice|Wheel|Plinko|Mines|Crash|Limbo|Tower|Keno|CoinFlip|Goal|Treasure|HiLo|Blackjack)|RoyalSteps)/)?.[0];
        if (!game) return;
        try { lastProof = JSON.parse(sessionStorage.getItem(`cedar-proof-${game}`)) || lastProof; } catch (_) {}
        const panel = document.createElement('details');
        panel.style.cssText = 'margin:20px;padding:12px;background:#121622;color:#eee;border:1px solid #334155;border-radius:12px;font:13px sans-serif;position:relative;z-index:10';
        panel.innerHTML = '<summary>Round verification · <span id="cedar-payout-cap">Loading payout limit…</span></summary><p>Verify the last completed round in this browser. Save the record to verify it independently.</p><button type="button">Verify last round</button> <button type="button">Download round record</button><pre style="white-space:pre-wrap"></pre>';
        document.body.appendChild(panel);
        const output = panel.querySelector('pre');
        panel.querySelectorAll('button')[0].onclick = async () => {
            try {
                if (!lastProof) throw new Error('Complete a round first.');
                const message = await verify(lastProof);
                if (lastProof.observed_commitment && lastProof.observed_commitment !== lastProof.server_seed_hash) throw new Error('Prior commitment mismatch.');
                output.textContent = message + (lastProof.observed_commitment ? ' Prior commitment matches.' : ' Prior commitment was not retained in this browser.') + '\nRound: ' + lastProof.bet_id;
            } catch (e) { output.textContent = e.message; }
        };
        panel.querySelectorAll('button')[1].onclick = () => {
            if (!lastProof) { output.textContent = 'Complete a round first.'; return; }
            const url = URL.createObjectURL(new Blob([JSON.stringify(lastProof, null, 2)], {type: 'application/json'}));
            const link = document.createElement('a'); link.href = url; link.download = `cedar-${lastProof.bet_id}.json`; link.click();
            URL.revokeObjectURL(url);
        };
    });
})();
