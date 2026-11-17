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

    const render = (hand) => hand.forEach((c, i) => {
        const r = c[0] === 'T' ? '10' : c[0];
        cards[i].className = 'card' + ('HD'.includes(c[1]) ? ' red' : '');
        cards[i].innerHTML = `<span class="held">HELD</span><span>${r}</span><span class="suit">${SUIT[c[1]]}</span>`;
    });

    const highlight = (name) => document.querySelectorAll('.paytable tr[data-hand]')
        .forEach(tr => tr.classList.toggle('win', tr.dataset.hand === name));

    const updateUi = () => {
        $('bet').textContent = bet;
        $('credits').textContent = credits;
        document.querySelectorAll('[data-bet-col]').forEach(el => el.classList.toggle('active-col', +el.dataset.betCol === bet));
        const locked = state === 'dealt' || busy;
        ['betUp', 'betDown', 'betMax'].forEach(id => $(id).disabled = locked);
        $('action').disabled = busy;
        $('action').textContent = state === 'dealt' ? 'Draw' : (credits <= 0 ? 'Refill' : 'Deal');
    };

    cards.forEach(card => card.addEventListener('click', () => {
        if (state === 'dealt') card.classList.toggle('hold');
    }));

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
                $('message').textContent = d.result ? `${d.result}! You win ${d.payout}` : 'No win. Deal again.';
            } else if (credits <= 0) {
                const d = await post(cfg.routes.reset);
                credits = d.credits;
                $('message').textContent = 'Credits refilled. Good luck!';
            } else {
                if (bet > credits) bet = credits;
                const d = await post(cfg.routes.deal, { bet });
                render(d.hand); credits = d.credits; state = 'dealt';
                highlight(d.preview);
                $('message').textContent = 'Tap cards to hold, then Draw.';
            }
        } catch (e) {
            $('message').textContent = e.message;
        } finally {
            busy = false; updateUi();
        }
    };

    updateUi();
})();
