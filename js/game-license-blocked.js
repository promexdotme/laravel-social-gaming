(function () {
    'use strict';

    const root = document.getElementById('game-license-blocked');
    if (!root) return;

    const isAdmin = root.dataset.admin === '1';
    const game = root.dataset.game || 'This game';

    const style = document.createElement('style');
    style.textContent = [
        '*{box-sizing:border-box}',
        'html,body{width:100%;height:100%;margin:0}',
        'body{display:grid;place-items:center;padding:24px;background:radial-gradient(circle at 50% 0,#1d2944 0,#0b0f18 52%,#07090e 100%);color:#f5f7fb;font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}',
        '.license-card{width:min(560px,100%);padding:34px;border:1px solid rgba(255,255,255,.12);border-radius:22px;background:rgba(14,19,31,.92);box-shadow:0 24px 70px rgba(0,0,0,.45);text-align:center}',
        '.license-mark{display:grid;place-items:center;width:58px;height:58px;margin:0 auto 18px;border-radius:18px;background:rgba(236,19,128,.14);color:#ff5bab;font-size:28px}',
        'h1{margin:0 0 10px;font-size:clamp(26px,5vw,38px)}',
        'p{margin:0 auto;color:#aeb8ca;line-height:1.65;max-width:460px}',
        '.license-actions{display:flex;flex-wrap:wrap;justify-content:center;gap:10px;margin-top:26px}',
        '.license-action{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:0 18px;border:1px solid rgba(255,255,255,.16);border-radius:12px;color:#fff;text-decoration:none;font-weight:750;background:#171e2e;transition:.18s ease}',
        '.license-action:hover{transform:translateY(-1px);border-color:#17d89a}',
        '.license-action-primary{border-color:#17d89a;background:#17d89a;color:#07110e}',
        '.license-note{margin-top:20px;font-size:12px;color:#758198}'
    ].join('');
    document.head.appendChild(style);

    const card = document.createElement('section');
    card.className = 'license-card';
    card.setAttribute('role', 'alert');

    const mark = document.createElement('div');
    mark.className = 'license-mark';
    mark.setAttribute('aria-hidden', 'true');
    mark.textContent = '!';

    const heading = document.createElement('h1');
    heading.textContent = 'Game temporarily unavailable';

    const message = document.createElement('p');
    message.textContent = isAdmin
        ? game + ' could not start because this installation does not currently have an active game entitlement.'
        : game + ' is temporarily unavailable. Please return to the lobby or contact the platform operator.';

    const actions = document.createElement('div');
    actions.className = 'license-actions';

    function addAction(label, href, primary, external) {
        if (!href) return;
        const link = document.createElement('a');
        link.className = 'license-action' + (primary ? ' license-action-primary' : '');
        link.textContent = label;
        link.href = href;
        if (external) {
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
        }
        actions.appendChild(link);
    }

    addAction('Return to lobby', root.dataset.lobbyUrl, !isAdmin, false);
    if (isAdmin) {
        addAction('Manage license', root.dataset.manageUrl, true, false);
        addAction('Official Promex Gaming Suite', root.dataset.officialUrl, false, true);
    }

    const note = document.createElement('p');
    note.className = 'license-note';
    note.textContent = isAdmin
        ? 'No provider game code was loaded. Reactivate the license, then reopen the game.'
        : 'Your balance and game history were not affected.';

    card.append(mark, heading, message, actions, note);
    root.replaceWith(card);
})();
