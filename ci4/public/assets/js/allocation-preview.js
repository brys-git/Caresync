/**
 * Shows where a payment will go for a plan that has a package instalment.
 *   csAllocationPreview({ url, plan: () => planId, amount: inputEl, date: inputEl, kind: () => 'regular'|'down_payment', out: el, onMode: (isAmountMode) => {} })
 * Asks the server (PaymentPreview::allocation); the server recomputes the same split on save.
 */
function csAllocationPreview(cfg) {
    let timer = null;
    let seq = 0;

    function show(text, isError) {
        cfg.out.textContent = text || '';
        cfg.out.classList.toggle('text-danger', !!isError);
        cfg.out.classList.toggle('d-none', !text);
    }

    function run() {
        const planId = cfg.plan();
        if (!planId) { show(''); if (cfg.onMode) { cfg.onMode(false); } return; }
        const my = ++seq;
        const q = new URLSearchParams({
            plan_id: planId,
            amount: cfg.amount ? (cfg.amount.value || '0') : '0',
            date: cfg.date ? cfg.date.value : '',
            kind: cfg.kind ? cfg.kind() : 'regular',
        });
        fetch(cfg.url + '?' + q.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (my !== seq) { return; }
                if (cfg.onMode) { cfg.onMode(!!d.amount_mode, d); }
                if (!d.amount_mode) { show(''); return; }
                show(d.ok ? d.text : (d.error || ''), !d.ok);
            })
            .catch(function () { if (my === seq) { show(''); } });
    }

    function later() { clearTimeout(timer); timer = setTimeout(run, 250); }

    if (cfg.amount) { cfg.amount.addEventListener('input', later); }
    if (cfg.date) { cfg.date.addEventListener('change', later); }
    return { refresh: later };
}
