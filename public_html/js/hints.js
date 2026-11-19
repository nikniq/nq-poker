// Simplified Jacks or Better strategy: returns { hold: [indices], reason }.
window.pokerHint = (hand) => {
    const RANK = '23456789TJQKA';
    const NAME = { 11: 'Jacks', 12: 'Queens', 13: 'Kings', 14: 'Aces' };
    const cards = hand.map((c, i) => ({ i, v: RANK.indexOf(c[0]) + 2, s: c[1] }));
    const all = cards.map(c => c.i);
    const byRank = {}, bySuit = {};
    cards.forEach(c => { (byRank[c.v] ||= []).push(c.i); (bySuit[c.s] ||= []).push(c); });
    const groups = Object.entries(byRank).map(([v, ix]) => ({ v: +v, ix })).sort((a, b) => b.ix.length - a.ix.length || b.v - a.v);
    const suits = Object.values(bySuit);
    const flush = suits.some(g => g.length === 5);
    const isRun = (vs) => {
        const u = [...new Set(vs)].sort((a, b) => a - b);
        if (u.length !== vs.length) return false;
        const span = (arr) => arr[arr.length - 1] - arr[0];
        const low = u.includes(14) ? [1, ...u.filter(v => v !== 14)] : null;
        return span(u) <= 4 || (low && span(low) <= 4);
    };
    const straight = isRun(cards.map(c => c.v));
    const fours = [0, 1, 2, 3, 4].map(skip => cards.filter(c => c.i !== skip));
    const ix = (cs) => cs.map(c => c.i);
    const royalIn = (n) => { for (const g of suits) { const r = g.filter(c => c.v >= 10); if (r.length >= n) return ix(r.slice(0, n)); } };
    const pairs = groups.filter(g => g.ix.length === 2);
    const top = groups[0];

    if (flush && straight) return { hold: all, reason: 'Straight flush or better: hold everything!' };
    if (top.ix.length === 4) return { hold: top.ix, reason: 'Four of a kind: hold all four.' };
    if (top.ix.length === 3 && pairs.length) return { hold: all, reason: 'Full house: hold everything.' };
    let h = royalIn(4);
    if (h) return { hold: h, reason: 'Four to a Royal Flush: worth breaking up anything smaller to chase 4000.' };
    if (flush) return { hold: all, reason: 'Flush: hold everything.' };
    if (straight) return { hold: all, reason: 'Straight: hold everything.' };
    if (top.ix.length === 3) return { hold: top.ix, reason: 'Three of a kind: hold the three, draw two.' };
    const sf4 = fours.find(f => f.every(c => c.s === f[0].s) && isRun(f.map(c => c.v)));
    if (sf4) return { hold: ix(sf4), reason: 'Four to a straight flush: draw one.' };
    if (pairs.length === 2) return { hold: [...pairs[0].ix, ...pairs[1].ix], reason: 'Two pair: hold both pairs, draw one.' };
    const high = pairs.find(p => p.v >= 11);
    if (high) return { hold: high.ix, reason: `High pair (${NAME[high.v]}): already a winner, hold it and draw three.` };
    h = royalIn(3);
    if (h) return { hold: h, reason: 'Three to a Royal Flush: hold them.' };
    const f4 = suits.find(g => g.length === 4);
    if (f4) return { hold: ix(f4), reason: 'Four to a flush: draw one.' };
    if (pairs.length) return { hold: pairs[0].ix, reason: 'Low pair: hold it. It can become two pair, trips or better.' };
    const open = fours.find(f => { const v = f.map(c => c.v).sort((a, b) => a - b); return isRun(v) && v[3] - v[0] === 3 && v[3] < 14; });
    if (open) return { hold: ix(open), reason: 'Open-ended straight draw: hold the four.' };
    const hi = cards.filter(c => c.v >= 11);
    for (const g of suits) { const s = g.filter(c => c.v >= 11); if (s.length >= 2) return { hold: ix(s.slice(0, 2)), reason: 'Two suited high cards: hold them.' }; }
    if (hi.length) {
        const keep = hi.sort((a, b) => a.v - b.v).slice(0, 2);
        return { hold: ix(keep), reason: keep.length > 1 ? 'Hold your two lowest high cards (J or better).' : 'Hold your high card (J or better).' };
    }
    return { hold: [], reason: 'Nothing worth keeping: draw five new cards.' };
};
