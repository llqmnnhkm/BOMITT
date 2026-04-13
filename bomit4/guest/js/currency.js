// guest/js/currency.js
// Global Currency Switcher for BoMIT
// IMPORTANT: This file is loaded at bottom of <body> so DOM is ready when it runs.
// Base currency: MYR. DB prices are always MYR. Converts to USD/EUR for display.

window.BOMIT_CURRENCY = {
    current: localStorage.getItem('bomit_currency') || 'MYR',
    rates:   { MYR: 1.0, USD: 0.2128, EUR: 0.1963 },
    symbols: { MYR: 'RM', USD: '$', EUR: '€' },
    labels:  { MYR: 'Malaysian Ringgit', USD: 'US Dollar', EUR: 'Euro' },
    loaded:  false,
};

// ── Load rates from server ────────────────────────────────────────────────
async function bomitLoadRates() {
    try {
        const resp = await fetch('get_exchange_rates.php');
        const data = await resp.json();
        if (data.rates)   window.BOMIT_CURRENCY.rates   = data.rates;
        if (data.symbols) window.BOMIT_CURRENCY.symbols = data.symbols;
        if (data.labels)  window.BOMIT_CURRENCY.labels  = data.labels;
    } catch (e) {
        console.warn('Currency rates fetch failed, using defaults');
    }
    window.BOMIT_CURRENCY.loaded = true;
    bomitUpdateDropdown();
}

// ── Convert MYR amount → current currency ─────────────────────────────────
function bomitConvert(myrAmount) {
    const rate = window.BOMIT_CURRENCY.rates[window.BOMIT_CURRENCY.current] || 1;
    return (parseFloat(myrAmount) || 0) * rate;
}

// ── Format MYR amount in current currency ─────────────────────────────────
function bomitFormat(myrAmount) {
    const cur = window.BOMIT_CURRENCY.current;
    const sym = window.BOMIT_CURRENCY.symbols[cur] || 'RM';
    const val = bomitConvert(myrAmount);
    return sym + val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// ── Global overrides — all existing code calls these ─────────────────────
window.formatCurrency = (amount) => bomitFormat(parseFloat(amount) || 0);
window.euFmt          = (amount) => bomitFormat(parseFloat(amount) || 0);

// ── Switch currency ───────────────────────────────────────────────────────
function bomitSetCurrency(code) {
    if (!window.BOMIT_CURRENCY.rates[code]) return;
    window.BOMIT_CURRENCY.current = code;
    localStorage.setItem('bomit_currency', code);
    bomitUpdateDropdown();
    bomitRefreshAll();
    window.dispatchEvent(new CustomEvent('currencyChanged', { detail: { currency: code } }));
}

// ── Tag PHP-rendered unit price cells with data-myr-price ─────────────────
// Finds static price cells next to qty inputs and tags them so they
// can be re-rendered when currency changes.
function bomitTagUnitPriceCells() {
    document.querySelectorAll('input[data-price]').forEach(function(inp) {
        const row   = inp.closest('tr');
        if (!row) return;
        const price = parseFloat(inp.getAttribute('data-price')) || 0;
        if (price <= 0) return;
        row.querySelectorAll('td').forEach(function(cell) {
            if (cell.querySelector('input') || cell.querySelector('button')) return;
            if (cell.hasAttribute('data-myr-price')) return; // already tagged
            const cellVal = parseFloat(cell.textContent.replace(/[^0-9.]/g, ''));
            if (Math.abs(cellVal - price) < 0.02) {
                cell.setAttribute('data-myr-price', price);
            }
        });
    });
}

// ── Refresh all visible price cells ──────────────────────────────────────
function bomitRefreshAll() {
    // Re-render tagged unit price cells
    document.querySelectorAll('[data-myr-price]').forEach(function(el) {
        el.textContent = bomitFormat(parseFloat(el.getAttribute('data-myr-price')) || 0);
    });

    // Re-render row totals (qty × data-price)
    document.querySelectorAll('input[data-price]').forEach(function(inp) {
        const qty   = parseFloat(inp.value) || 0;
        const price = parseFloat(inp.getAttribute('data-price')) || 0;
        const total = qty * price;
        const row   = inp.closest('tr');
        if (!row) return;
        const totalCell = row.querySelector('.row-total, .eu-row-total, .conf-row-total, .cable-row-total, .accessory-row-total');
        if (totalCell) totalCell.textContent = bomitFormat(total);
    });

    // Re-run subtotal/calc functions
    if (typeof updateNetworkConfigPricing     === 'function') updateNetworkConfigPricing();
    if (typeof updateCablesAccessoriesPricing === 'function') updateCablesAccessoriesPricing();

    // Network equipment sections
    document.querySelectorAll('[id^="equipment_"]').forEach(function(sec) {
        if (sec.style.display !== 'none' && !sec.classList.contains('hidden')) {
            var st = sec.id.replace('equipment_', '');
            if (typeof calculateEquipmentTotals === 'function') calculateEquipmentTotals(st);
        }
    });

    // Conference sections
    document.querySelectorAll('.conf-size-section:not(.hidden)').forEach(function(sec) {
        var roomSize = sec.id.replace('conf_equip_', '');
        if (typeof calculateConferenceTotals === 'function') calculateConferenceTotals(roomSize);
    });

    // End user sections
    if (typeof euCalcSectionTotal === 'function') {
        document.querySelectorAll('.eu-usertype-section').forEach(function(sec) {
            if (sec.style.display !== 'none') {
                var utKey = sec.id.replace('eu-section-', '');
                ['workstation','peripherals','mobile','software'].forEach(function(cat) {
                    euCalcSectionTotal(utKey, cat);
                });
            }
        });
    }
}

// ── Build the dropdown UI ─────────────────────────────────────────────────
function bomitBuildDropdown() {
    const container = document.getElementById('bomit-currency-toggle');
    if (!container) return;

    const cur = window.BOMIT_CURRENCY.current;
    const sym = window.BOMIT_CURRENCY.symbols[cur] || 'RM';

    container.innerHTML = `
        <div style="position:relative;display:inline-block;" id="bomit-dropdown-wrap">
            <button type="button" id="bomit-currency-btn"
                onclick="bomitToggleDropdown(event)"
                style="display:flex;align-items:center;gap:7px;
                       background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.35);
                       color:white;padding:6px 12px;border-radius:8px;cursor:pointer;
                       font-family:Montserrat,sans-serif;font-size:0.82rem;font-weight:600;
                       transition:background 0.2s;white-space:nowrap;">
                <span id="bomit-cur-sym">${sym}</span>
                <span id="bomit-cur-code">${cur}</span>
                <svg width="10" height="6" viewBox="0 0 10 6" fill="none" style="margin-left:2px;">
                    <path d="M1 1l4 4 4-4" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            </button>
            <div id="bomit-currency-menu"
                 style="display:none;position:absolute;top:calc(100% + 6px);right:0;
                        background:white;border-radius:10px;
                        box-shadow:0 8px 24px rgba(0,0,0,0.18);
                        overflow:hidden;min-width:190px;z-index:9999;
                        border:1px solid rgba(0,0,0,0.08);">
                ${['MYR','USD','EUR'].map(function(code) {
                    var s = window.BOMIT_CURRENCY.symbols[code] || code;
                    var l = window.BOMIT_CURRENCY.labels[code]  || code;
                    var active = code === cur;
                    return '<button type="button"' +
                        ' onclick="bomitSetCurrency(\'' + code + '\'); bomitCloseDropdown();"' +
                        ' style="display:flex;align-items:center;gap:10px;width:100%;' +
                        'padding:10px 14px;background:' + (active ? '#f0f7ff' : 'transparent') + ';' +
                        'border:none;cursor:pointer;font-family:Montserrat,sans-serif;' +
                        'font-size:0.875rem;color:' + (active ? '#0070ef' : '#333') + ';' +
                        'font-weight:' + (active ? '600' : '400') + ';text-align:left;">' +
                        '<span style="font-size:1rem;min-width:22px;">' + s + '</span>' +
                        '<span>' + code + ' — ' + l + '</span>' +
                        (active ? '<span style="margin-left:auto;color:#0070ef;font-size:.75rem;">✓</span>' : '') +
                        '</button>';
                }).join('')}
            </div>
        </div>`;

    // Close on outside click
    document.addEventListener('click', function(e) {
        var wrap = document.getElementById('bomit-dropdown-wrap');
        if (wrap && !wrap.contains(e.target)) bomitCloseDropdown();
    });
}

function bomitToggleDropdown(e) {
    e.stopPropagation();
    var menu = document.getElementById('bomit-currency-menu');
    if (menu) menu.style.display = (menu.style.display === 'none' ? 'block' : 'none');
}

function bomitCloseDropdown() {
    var menu = document.getElementById('bomit-currency-menu');
    if (menu) menu.style.display = 'none';
}

// ── Update dropdown button to show selected currency ──────────────────────
function bomitUpdateDropdown() {
    var cur = window.BOMIT_CURRENCY.current;
    var sym = window.BOMIT_CURRENCY.symbols[cur] || 'RM';
    var symEl  = document.getElementById('bomit-cur-sym');
    var codeEl = document.getElementById('bomit-cur-code');
    if (symEl)  symEl.textContent  = sym;
    if (codeEl) codeEl.textContent = cur;
    // Rebuild menu to refresh active state
    bomitBuildDropdown();
}

// ── Init — runs immediately because script is at bottom of <body> ─────────
bomitBuildDropdown();
bomitLoadRates();
// Tag and refresh after slight delay to let PHP-rendered tables settle
setTimeout(function() {
    bomitTagUnitPriceCells();
    bomitRefreshAll();
}, 600);