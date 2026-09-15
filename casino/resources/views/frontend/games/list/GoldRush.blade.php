<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $game->title }} - Social Casino</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { background: #0c0517; font-family: 'Syne', sans-serif; color: white; overflow-x: hidden; }
        .slot-frame { background: linear-gradient(180deg, rgba(26,11,46,0.95), rgba(12,5,23,0.98)); border: 2px solid rgba(255,215,0,0.5); box-shadow: 0 0 40px rgba(255,215,0,0.2); }
        .reel-window { background: #06020a; border: 1px solid rgba(255,215,0,0.3); border-radius: 16px; overflow: hidden; position: relative; }
        .symbol-tile { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 110px; border-radius: 12px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); margin: 3px; transition: all 0.2s ease; }
        .symbol-tile:hover { transform: scale(1.02); border-color: rgba(255,215,0,0.6); }
        .neon-btn { background: linear-gradient(90deg, #ffd700, #ff8c00); color: #000; font-weight: 800; box-shadow: 0 0 25px rgba(255,215,0,0.5); }
        .neon-btn:hover { filter: brightness(1.2); }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between p-4 md:p-8">

    <!-- Top Navigation Bar -->
    <div class="flex items-center justify-between glass-card p-4 rounded-2xl border border-yellow-500/30 mb-4 bg-purple-950/40 backdrop-blur-md">
        <div class="flex items-center gap-3">
            <a href="/" class="bg-white/10 hover:bg-white/20 p-2.5 rounded-xl text-xs font-bold font-mono text-white transition-all">&larr; BACK TO LOBBY</a>
            <h1 class="text-xl font-extrabold text-yellow-400 tracking-wider uppercase flex items-center gap-2">
                <span>🪙</span> {{ $game->title }}
            </h1>
        </div>
        <div class="flex items-center gap-3 font-mono">
            <span class="text-xs text-gray-400 uppercase">Cedar Coins Balance:</span>
            <span id="player-balance" class="text-lg font-bold text-yellow-400 bg-black/60 px-4 py-1.5 rounded-xl border border-yellow-400/40">
                {{ Auth::check() ? number_format(Auth::user()->balance, 2, '.', '') : '50,000.00' }} CEDARS
            </span>
        </div>
    </div>

    <!-- Main Slot Frame Container -->
    <div class="max-w-4xl w-full mx-auto slot-frame p-6 md:p-8 rounded-3xl space-y-6">

        <!-- Banner Title & Status Message -->
        <div class="text-center space-y-1">
            <span id="status-msg" class="text-xs font-mono text-yellow-400 uppercase tracking-widest block font-bold">READY TO SPIN &bull; GOLD RUSH SLOTS</span>
            <div id="win-banner" class="text-3xl font-black text-yellow-300 hidden animate-bounce drop-shadow-[0_0_15px_rgba(255,215,0,0.8)]">
                🎉 BIG WIN! <span id="win-amount">0.00</span> CEDARS
            </div>
        </div>

        <!-- 5 Reels Grid Display (5 Columns x 3 Rows) -->
        <div class="reel-window grid grid-cols-5 gap-1 p-3">
            @for ($c = 0; $c < 5; $c++)
                <div class="bg-black/80 rounded-xl border border-yellow-500/20 overflow-hidden p-1">
                    <div id="reel-col-{{ $c }}" class="reel-column flex flex-col space-y-1">
                        <div class="symbol-tile">
                            <span class="text-3xl">🍋</span>
                            <span class="text-[10px] font-mono text-yellow-400 uppercase font-bold mt-1">Lemon</span>
                        </div>
                        <div class="symbol-tile">
                            <span class="text-3xl">7️⃣</span>
                            <span class="text-[10px] font-mono text-red-400 uppercase font-bold mt-1">Seven</span>
                        </div>
                        <div class="symbol-tile">
                            <span class="text-3xl">🔔</span>
                            <span class="text-[10px] font-mono text-yellow-300 uppercase font-bold mt-1">Bell</span>
                        </div>
                    </div>
                </div>
            @endfor
        </div>

        <!-- Slot Controls Footer -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center pt-2">
            <!-- Bet Control -->
            <div class="bg-black/50 p-3 rounded-2xl border border-yellow-500/30 font-mono text-center">
                <span class="text-[10px] text-gray-400 uppercase block">BET PER LINE (20 LINES)</span>
                <div class="flex items-center justify-between mt-1">
                    <button type="button" id="btn-bet-down" class="bg-white/10 hover:bg-white/20 w-8 h-8 rounded-lg text-sm font-bold text-yellow-400">-</button>
                    <span id="bet-amount-text" class="text-sm font-bold text-white">1.00</span>
                    <button type="button" id="btn-bet-up" class="bg-white/10 hover:bg-white/20 w-8 h-8 rounded-lg text-sm font-bold text-yellow-400">+</button>
                </div>
            </div>

            <!-- Total Bet Display -->
            <div class="bg-black/50 p-3 rounded-2xl border border-yellow-500/30 font-mono text-center">
                <span class="text-[10px] text-gray-400 uppercase block">TOTAL WAGER</span>
                <span id="total-wager-text" class="text-lg font-bold text-yellow-400 block mt-0.5">20.00 CEDARS</span>
            </div>

            <!-- Spin Button -->
            <div class="md:col-span-2">
                <button type="button" id="btn-spin" class="w-full neon-btn py-4 rounded-2xl font-extrabold text-lg tracking-widest uppercase transition-all flex items-center justify-center gap-2">
                    <span>🎰</span> SPIN NOW
                </button>
            </div>
        </div>
    </div>

    <!-- WebAudio & Sound Effects Engine -->
    <script>
        const sounds = {
            spin: new Audio('/audio/slots/spin.wav'),
            win: new Audio('/audio/slots/win.wav'),
            stop: new Audio('/audio/slots/stop.wav'),
            lose: new Audio('/audio/slots/lose.wav'),
            start: new Audio('/audio/slots/start.wav')
        };

        function playSound(key) {
            try {
                if (sounds[key]) {
                    sounds[key].currentTime = 0;
                    sounds[key].play().catch(() => {});
                }
            } catch(e) {}
        }

        // Gold Rush Theme Symbol Config
        const themeSymbols = [
            { icon: '🍎', name: 'Apple', color: 'text-red-400' },
            { icon: '🍒', name: 'Cherry', color: 'text-pink-500' },
            { icon: '🍋', name: 'Lemon', color: 'text-yellow-300' },
            { icon: '🍊', name: 'Orange', color: 'text-orange-400' },
            { icon: '🍇', name: 'Grape', color: 'text-purple-400' },
            { icon: '🍉', name: 'Watermelon', color: 'text-green-400' },
            { icon: '🔔', name: 'Gold Bell', color: 'text-yellow-400' },
            { icon: '⭐', name: 'Gold Star', color: 'text-amber-300' },
            { icon: '💎', name: 'WILD BAR', color: 'text-cyan-300 font-bold' },
            { icon: '7️⃣', name: 'LUCKY SEVEN', color: 'text-red-500 font-bold' }
        ];

        let betPerLine = 1.0;
        const totalLines = 20;
        let isSpinning = false;
        let currentBalance = {{ Auth::check() ? Auth::user()->balance : 50000 }};

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('player-balance').innerText = currentBalance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' CEDARS';
            
            document.getElementById('btn-bet-up').addEventListener('click', () => adjustBet(1));
            document.getElementById('btn-bet-down').addEventListener('click', () => adjustBet(-1));
            document.getElementById('btn-spin').addEventListener('click', executeSpin);
        });

        function adjustBet(delta) {
            if (isSpinning) return;
            betPerLine = Math.max(1, betPerLine + delta);
            document.getElementById('bet-amount-text').innerText = betPerLine.toFixed(2);
            document.getElementById('total-wager-text').innerText = (betPerLine * totalLines).toFixed(2) + ' CEDARS';
        }

        function executeSpin() {
            if (isSpinning) return;
            const totalWager = betPerLine * totalLines;

            if (currentBalance < totalWager) {
                alert('Insufficient Cedar Coins Balance!');
                return;
            }

            isSpinning = true;
            document.getElementById('btn-spin').disabled = true;
            document.getElementById('status-msg').innerText = 'SPINNING REELS...';
            document.getElementById('win-banner').classList.add('hidden');
            playSound('spin');

            for (let c = 0; c < 5; c++) {
                const col = document.getElementById(`reel-col-${c}`);
                col.style.filter = 'blur(4px)';
            }

            fetch('/game/{{ $game->name }}/server', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    action: 'spin',
                    bet: betPerLine,
                    sessionId: 'custom_session_' + Date.now()
                })
            })
            .then(res => res.json())
            .then(data => {
                setTimeout(() => {
                    isSpinning = false;
                    document.getElementById('btn-spin').disabled = false;

                    if (data.status === 'success') {
                        renderGrid(data.grid);
                        currentBalance = parseFloat(data.new_balance.replace(/,/g, ''));
                        document.getElementById('player-balance').innerText = currentBalance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' CEDARS';

                        const winVal = parseFloat(data.total_win);
                        if (winVal > 0) {
                            document.getElementById('status-msg').innerText = 'CONGRATULATIONS!';
                            document.getElementById('win-amount').innerText = winVal.toFixed(2);
                            document.getElementById('win-banner').classList.remove('hidden');
                            playSound('win');
                        } else {
                            document.getElementById('status-msg').innerText = 'READY TO SPIN';
                            playSound('stop');
                        }
                    } else {
                        alert(data.message || 'Spin failed!');
                        document.getElementById('status-msg').innerText = 'READY TO SPIN';
                    }
                }, 600);
            })
            .catch(err => {
                isSpinning = false;
                document.getElementById('btn-spin').disabled = false;
                alert('Error communicating with game server');
            });
        }

        function renderGrid(gridData) {
            for (let c = 0; c < 5; c++) {
                const col = document.getElementById(`reel-col-${c}`);
                col.style.filter = 'none';
                let html = '';
                for (let r = 0; r < 3; r++) {
                    const symId = gridData[r][c];
                    const sym = themeSymbols[symId] || themeSymbols[0];
                    html += `
                        <div class="symbol-tile">
                            <span class="text-3xl">${sym.icon}</span>
                            <span class="text-[10px] font-mono ${sym.color} uppercase font-bold mt-1">${sym.name}</span>
                        </div>
                    `;
                }
                col.innerHTML = html;
            }
        }
    </script>
</body>
</html>
