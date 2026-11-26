(() => {
    const cfg = window.POKER;
    const token = document.querySelector('meta[name=csrf-token]').content;
    const $ = (id) => document.getElementById(id);
    const cards = [...document.querySelectorAll('#hand .card')];
    const SUIT = { S: '♠', H: '♥', D: '♦', C: '♣' };
    let state = 'idle'; // idle | dealt
    let bet = 1;
    let credits = cfg.credits;
    let busy = false;

    const post = async (url, body = {}) => {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify(body),
            credentials: 'same-origin',
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || data.message || 'Request failed');
        return data;
    };

    let current = [];

    // --- Sound (WebAudio blips, no assets) ---
    const pref = (k, d) => { try { return localStorage.getItem(k) ?? d; } catch (e) { return d; } };
    const save = (k, v) => { try { localStorage.setItem(k, v); } catch (e) {} };
    let sound = pref('pokerSound', 'on') === 'on';
    let ac;
    const tone = (freq, dur = .08, when = 0, type = 'triangle', vol = .08) => {
        if (!sound) return;
        try {
            ac ||= new (window.AudioContext || window.webkitAudioContext)();
            const t = ac.currentTime + when, o = ac.createOscillator(), g = ac.createGain();
            o.type = type; o.frequency.value = freq;
            g.gain.setValueAtTime(vol, t); g.gain.exponentialRampToValueAtTime(.0001, t + dur);
            o.connect(g).connect(ac.destination); o.start(t); o.stop(t + dur);
        } catch (e) {}
    };
    const sfx = {
        deal: (n) => { for (let i = 0; i < n; i++) tone(520 + i * 40, .05, i * .09, 'square', .03); },
        click: () => tone(880, .04, 0, 'square', .03),
        win: (big) => [523, 659, 784, 1047].concat(big ? [1319, 1568] : []).forEach((f, i) => tone(f, .18, i * .1)),
        lose: () => { tone(220, .2, 0, 'sawtooth', .03); tone(165, .3, .15, 'sawtooth', .03); },
    };

    // --- Animated credit meter + session stats ---
    let shown = credits;
    const countTo = (target) => {
        const from = shown, start = performance.now(), dur = Math.min(1500, Math.abs(target - from) * 25 + 200);
        const step = (now) => {
            const k = Math.min(1, (now - start) / dur);
            shown = Math.round(from + (target - from) * k);
            $('credits').textContent = shown;
            if (k < 1) requestAnimationFrame(step); else if (target > from) tone(1200, .05, 0, 'square', .02);
        };
        requestAnimationFrame(step);
    };
    const stats = { hands: 0, wins: 0, best: 0 };
    const renderStats = () => {
        $('statHands').textContent = stats.hands;
        $('statRate').textContent = stats.hands ? Math.round(100 * stats.wins / stats.hands) + '%' : '–';
        $('statBest').textContent = stats.best;
    };
    let hints = true;
    try { hints = localStorage.getItem('pokerHints') !== 'off'; } catch (e) {}
    let lastHint = null;
    const showHint = () => {
        cards.forEach(c => c.classList.remove('hint'));
        $('hint').hidden = !(hints && state === 'dealt' && lastHint);
        if ($('hint').hidden) return;
        lastHint.hold.forEach(i => cards[i].classList.add('hint'));
        $('hint').textContent = '💡 ' + lastHint.reason;
    };
    const setHints = (on) => {
        hints = on; $('hintToggle').checked = on;
        try { localStorage.setItem('pokerHints', on ? 'on' : 'off'); } catch (e) {}
        showHint();
    };
    const FACE = { J: 'Jack', Q: 'Queen', K: 'King', A: 'Ace' };

    // Re-render only cards that changed, with a staggered deal animation.
    // Classic pip positions [x%, y%] for number cards; pips below the middle are flipped.
    const PIPS = {
        2: [[50, 20], [50, 80]],
        3: [[50, 20], [50, 50], [50, 80]],
        4: [[15, 20], [85, 20], [15, 80], [85, 80]],
        5: [[15, 20], [85, 20], [50, 50], [15, 80], [85, 80]],
        6: [[15, 20], [85, 20], [15, 50], [85, 50], [15, 80], [85, 80]],
        7: [[15, 20], [85, 20], [50, 35], [15, 50], [85, 50], [15, 80], [85, 80]],
        8: [[15, 20], [85, 20], [50, 35], [15, 50], [85, 50], [50, 65], [15, 80], [85, 80]],
        9: [[15, 20], [85, 20], [15, 40], [85, 40], [50, 50], [15, 60], [85, 60], [15, 80], [85, 80]],
        10: [[15, 20], [85, 20], [50, 30], [15, 40], [85, 40], [15, 60], [85, 60], [50, 70], [15, 80], [85, 80]],
    };
    const COURT = { J: '♞', Q: '♛', K: '♚' };
    const cardCenter = (rank, s) => {
        if (COURT[rank]) {
            return `<span class="court"><span class="court-glyph">${COURT[rank]}</span><span class="court-suit">${s}</span>`
                + `<span class="court-glyph flip">${COURT[rank]}</span></span>`;
        }
        if (rank === 'A') return `<span class="pip ace">${s}</span>`;
        const n = rank === 'T' ? 10 : +rank;
        return `<span class="pips">${PIPS[n].map(([x, y]) =>
            `<i style="left:${x}%;top:${y}%"${y > 50 ? ' class="flip"' : ''}>${s}</i>`).join('')}</span>`;
    };

    const render = (hand) => {
        let n = 0;
        hand.forEach((c, i) => {
            if (current[i] === c && !cards[i].classList.contains('back')) return;
            const r = c[0] === 'T' ? '10' : c[0];
            const s = SUIT[c[1]];
            const center = cardCenter(c[0], s);
            const el = cards[i];
            el.className = 'card' + ('HD'.includes(c[1]) ? ' red' : '');
            el.setAttribute('aria-label', `${FACE[c[0]] || r} of ${{S:'spades',H:'hearts',D:'diamonds',C:'clubs'}[c[1]]}`);
            el.innerHTML = `<span class="held">HELD</span>`
                + `<span class="corner tl">${r}<small>${s}</small></span>${center}`
                + `<span class="corner br">${r}<small>${s}</small></span>`;
            el.style.animationDelay = `${n++ * 90}ms`;
            void el.offsetWidth; // restart animation
            el.classList.add('deal');
        });
        if (n) sfx.deal(n);
        current = [...hand];
    };

    const highlight = (name) => document.querySelectorAll('.paytable tr[data-hand]')
        .forEach(tr => tr.classList.toggle('win', tr.dataset.hand === name));

    const updateUi = () => {
        $('bet').textContent = bet;
        document.querySelectorAll('[data-bet-col]').forEach(el => el.classList.toggle('active-col', +el.dataset.betCol === bet));
        const locked = state === 'dealt' || busy;
        ['betUp', 'betDown', 'betMax'].forEach(id => $(id).disabled = locked);
        $('action').disabled = busy;
        $('action').textContent = state === 'dealt' ? 'Draw' : (credits <= 0 ? 'Refill' : 'Deal');
    };

    // --- Confetti for big wins (canvas, auto-removed) ---
    const confetti = (count = 120) => {
        if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        const host = document.querySelector('.table');
        const cv = document.createElement('canvas');
        cv.className = 'confetti';
        host.appendChild(cv);
        const w = cv.width = host.clientWidth, h = cv.height = host.clientHeight, ctx = cv.getContext('2d');
        const colors = ['#f5c542', '#ffffff', '#d32f2f', '#4fc3f7', '#81c784'];
        const parts = Array.from({ length: count }, () => ({
            x: w / 2 + (Math.random() - .5) * w * .3, y: h * .45,
            vx: (Math.random() - .5) * 12, vy: -Math.random() * 12 - 4,
            r: Math.random() * 6 + 3, a: Math.random() * 6, va: (Math.random() - .5) * .3,
            c: colors[Math.floor(Math.random() * colors.length)],
        }));
        const t0 = performance.now();
        const frame = (now) => {
            ctx.clearRect(0, 0, w, h);
            parts.forEach(p => {
                p.vy += .35; p.vx *= .99; p.x += p.vx; p.y += p.vy; p.a += p.va;
                ctx.save(); ctx.translate(p.x, p.y); ctx.rotate(p.a); ctx.fillStyle = p.c;
                ctx.fillRect(-p.r / 2, -p.r / 4, p.r, p.r / 2); ctx.restore();
            });
            if (now - t0 < 2600) requestAnimationFrame(frame); else cv.remove();
        };
        requestAnimationFrame(frame);
    };

    // --- Full screen (Fullscreen API, CSS fallback for iPhone Safari) ---
    const tableEl = document.querySelector('.table');
    const nativeFs = !!(tableEl.requestFullscreen || tableEl.webkitRequestFullscreen);
    const isFull = () => !!(document.fullscreenElement || document.webkitFullscreenElement) || tableEl.classList.contains('pseudo-full');
    const syncFull = () => {
        const on = isFull();
        tableEl.classList.toggle('is-full', on);
        document.body.classList.toggle('game-full', on);
        $('fullscreen').innerHTML = on ? '<span aria-hidden="true">⤡</span> Exit full screen' : '<span aria-hidden="true">⛶</span> Full screen';
        $('fullscreen').setAttribute('aria-pressed', on);
    };
    const toggleFull = async () => {
        try {
            if (isFull()) {
                if (tableEl.classList.contains('pseudo-full')) tableEl.classList.remove('pseudo-full');
                else await (document.exitFullscreen || document.webkitExitFullscreen).call(document);
            } else if (nativeFs) {
                await (tableEl.requestFullscreen || tableEl.webkitRequestFullscreen).call(tableEl);
            } else {
                tableEl.classList.add('pseudo-full');
            }
        } catch (e) {
            tableEl.classList.toggle('pseudo-full');
        }
        syncFull();
    };
    $('fullscreen').onclick = toggleFull;
    document.addEventListener('fullscreenchange', syncFull);
    document.addEventListener('webkitfullscreenchange', syncFull);

    // --- Double Up ---
    let doubling = false;
    const SUITS = { S: '♠', H: '♥', D: '♦', C: '♣' };
    const showDouble = (amount) => {
        doubling = amount > 0;
        $('double').hidden = !doubling;
        if (!doubling) return;
        $('doubleAmount').textContent = amount;
        $('doubleWin').textContent = amount * 2;
        $('double').querySelectorAll('button').forEach(b => b.disabled = false);
    };
    const revealDouble = (c) => {
        const el = $('doubleCard'), r = c[0] === 'T' ? '10' : c[0];
        el.className = 'double-card card deal' + ('HD'.includes(c[1]) ? ' red' : '');
        el.innerHTML = `<span class="corner tl">${r}<small>${SUITS[c[1]]}</small></span><span class="pip">${SUITS[c[1]]}</span>`;
    };
    const pick = async (choice) => {
        if (!doubling || busy) return;
        busy = true; updateUi();
        $('double').querySelectorAll('button').forEach(b => b.disabled = true);
        try {
            const d = await post(cfg.routes.double, { pick: choice });
            revealDouble(d.card); credits = d.credits; countTo(credits);
            if (d.won) {
                stats.best = Math.max(stats.best, d.amount); renderStats();
                sfx.win(false);
                $('message').textContent = `Correct! Your win is now ${d.amount}.`;
                $('message').classList.add('win');
            } else {
                sfx.lose();
                $('message').textContent = 'Wrong colour. Win lost. Deal again.';
                $('message').classList.remove('win');
            }
            await new Promise(r => setTimeout(r, 900));
            const back = $('doubleCard'); back.className = 'double-card card back'; back.innerHTML = '';
            showDouble(d.can_double ? d.amount : 0);
            if (d.won && !d.can_double) $('message').textContent = `Maximum doubles reached. You collect ${d.amount}!`;
        } catch (e) {
            $('message').textContent = e.message; showDouble(0);
        } finally { busy = false; updateUi(); }
    };
    $('double').querySelectorAll('[data-pick]').forEach(b => b.onclick = () => pick(b.dataset.pick));
    $('collect').onclick = async () => {
        if (!doubling || busy) return;
        showDouble(0); sfx.click();
        try { await post(cfg.routes.collect); } catch (e) {}
        $('message').textContent = 'Win collected. Deal again.';
    };

    const toggle = (card) => { if (state === 'dealt' && !busy) { card.classList.toggle('hold'); sfx.click(); } };
    const setSound = (on) => { sound = on; $('soundToggle').checked = on; save('pokerSound', on ? 'on' : 'off'); if (on) sfx.click(); };
    $('soundToggle').onchange = (e) => setSound(e.target.checked);
    cards.forEach(card => card.addEventListener('click', () => toggle(card)));

    $('hintToggle').onchange = (e) => setHints(e.target.checked);

    document.addEventListener('keydown', (e) => {
        if (e.target.closest('input, textarea') || e.metaKey || e.ctrlKey) return;
        if (e.key === 'h' || e.key === 'H') return setHints(!hints);
        if (e.key === 'm' || e.key === 'M') return setSound(!sound);
        if (e.key === 'f' || e.key === 'F') return toggleFull();
        if (e.key === 'Escape' && tableEl.classList.contains('pseudo-full')) { tableEl.classList.remove('pseudo-full'); return syncFull(); }
        if (doubling && /^[rbc]$/i.test(e.key)) return e.key.toLowerCase() === 'c' ? $('collect').click() : pick(e.key.toLowerCase() === 'r' ? 'red' : 'black');
        if (e.key >= '1' && e.key <= '5') toggle(cards[+e.key - 1]);
        else if (e.key === ' ' || e.key === 'Enter') { e.preventDefault(); if (!busy) $('action').click(); }
        else if (state !== 'dealt' && !busy && (e.key === '+' || e.key === '=')) $('betUp').click();
        else if (state !== 'dealt' && !busy && e.key === '-') $('betDown').click();
    });

    $('betUp').onclick = () => { bet = Math.min(5, bet + 1); updateUi(); };
    $('betDown').onclick = () => { bet = Math.max(1, bet - 1); updateUi(); };
    $('betMax').onclick = () => { bet = 5; updateUi(); };

    $('action').onclick = async () => {
        busy = true; updateUi();
        try {
            if (state === 'dealt') {
                const hold = cards.map((c, i) => c.classList.contains('hold') ? i : -1).filter(i => i >= 0);
                const d = await post(cfg.routes.draw, { hold });
                render(d.hand); credits = d.credits; state = 'idle';
                highlight(d.result);
                stats.hands++;
                if (d.result) { stats.wins++; stats.best = Math.max(stats.best, d.payout); }
                renderStats();
                setTimeout(() => (d.result ? sfx.win(d.payout >= 25 * bet) : sfx.lose()), 450);
                if (d.result && d.payout >= 9 * bet) setTimeout(() => confetti(d.payout >= 50 * bet ? 220 : 120), 400);
                countTo(credits);
                showDouble(d.can_double ? d.payout : 0);
                $('message').textContent = d.result ? `${d.result}! You win ${d.payout}` : 'No win. Deal again.';
                $('message').classList.toggle('win', !!d.result);
                if (d.result) cards.forEach(c => c.classList.add('winner'));
            } else if (credits <= 0) {
                const d = await post(cfg.routes.reset);
                credits = d.credits; countTo(credits);
                $('message').textContent = 'Credits refilled. Good luck!';
            } else {
                if (bet > credits) bet = credits;
                const d = await post(cfg.routes.deal, { bet });
                showDouble(0);
                $('message').classList.remove('win');
                cards.forEach(c => c.classList.add('back'));
                render(d.hand); credits = d.credits; state = 'dealt'; countTo(credits);
                highlight(d.preview);
                lastHint = window.pokerHint(d.hand);
                $('message').textContent = 'Tap cards to hold, then Draw.';
            }
        } catch (e) {
            $('message').textContent = e.message;
        } finally {
            busy = false; updateUi(); showHint();
        }
    };

    $('hintToggle').checked = hints;
    $('soundToggle').checked = sound;
    syncFull();
    updateUi();
})();
