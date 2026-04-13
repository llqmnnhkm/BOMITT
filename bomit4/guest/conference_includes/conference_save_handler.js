// guest/conference_includes/conference_save_handler.js
// Mirrors network_save_handler.js — auto-load, save, reset, unsaved-changes guard

let confHasUnsavedChanges = false;

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Conference Save Handler Initialized');

    // Auto-load saved config
    confAutoLoad();

    // Track changes
    const container = document.querySelector('#container-conference');
    if (container) {
        container.addEventListener('change', e => {
            if (e.target.matches('input, select, textarea')) {
                confHasUnsavedChanges = true;
                confShowUnsaved();
            }
        });
        container.addEventListener('input', e => {
            if (e.target.matches('input[type="number"]')) {
                confHasUnsavedChanges = true;
                confShowUnsaved();
            }
        });
    }
});

// ── Auto-load ─────────────────────────────────────────────────
async function confAutoLoad() {
    const projectName = document.querySelector('input[name="project_name"]')?.value;
    if (!projectName) return;

    try {
        const res = await fetch(`conference_includes/load_conference_config.php?project_name=${encodeURIComponent(projectName)}`);
        const data = await res.json();

        if (data.success) {
            confPopulateForm(data.configuration);
            confShowInfo(`Last saved: ${data.last_saved}`);
        }
    } catch (e) {
        console.log('ℹ️ Conference auto-load skipped:', e.message);
    }
}

// ── Populate form from saved config ──────────────────────────
function confPopulateForm(config) {
    if (!config) return;
    console.log('📥 Populating conference form');

    // Room info
    if (config.room_info) {
        const ri = config.room_info;
        if (ri.conference_size) {
            const radio = document.querySelector(`input[name="conference_size"][value="${ri.conference_size}"]`);
            if (radio) { radio.checked = true; onRoomSizeChange(); }
        }
        if (ri.conference_meeting_type) {
            const radio = document.querySelector(`input[name="conference_meeting_type"][value="${ri.conference_meeting_type}"]`);
            if (radio) {
                radio.checked = true;
                const card = radio.nextElementSibling;
                if (card) {
                    card.style.borderColor = '#80c7a0';
                    card.style.background  = 'rgba(128,199,160,0.1)';
                }
            }
        }
        if (ri.conference_setup_type) {
            const radio = document.querySelector(`input[name="conference_setup_type"][value="${ri.conference_setup_type}"]`);
            if (radio) {
                radio.checked = true;
                const card = radio.nextElementSibling;
                if (card) {
                    card.style.borderColor = '#80c7a0';
                    card.style.background  = 'rgba(128,199,160,0.1)';
                }
            }
        }
    }

    // AV & Connectivity
    if (config.av_connectivity) {
        const av = config.av_connectivity;
        const setSelect = (name, val) => {
            const el = document.querySelector(`select[name="${name}"]`);
            if (el && val) el.value = val;
        };
        const setCheck = (name, val) => {
            const el = document.querySelector(`input[name="${name}"]`);
            if (el) el.checked = !!val;
        };
        setSelect('av_display_type',   av.display_type);
        setCheck('av_display_required', av.display_required);
        setSelect('av_vc_platform',    av.vc_platform);
        setCheck('av_vc_required',     av.vc_required);
        setSelect('av_wireless_type',  av.wireless_type);
        setCheck('av_wireless_required', av.wireless_required);
        setSelect('av_wired_drops',    av.wired_drops);
        setCheck('av_wired_required',  av.wired_required);
        setSelect('av_control_system', av.control_system);
        setCheck('av_control_required',av.control_required);
        setSelect('av_network_type',   av.network_type);
        setCheck('av_network_required',av.network_required);
    }

    // Equipment quantities
    if (config.equipment && Array.isArray(config.equipment)) {
        config.equipment.forEach(item => {
            const input = document.querySelector(`input[name="${item.name}"]`);
            if (input) input.value = item.quantity;
        });
        // Recalculate totals for visible section
        const size = document.querySelector('input[name="conference_size"]:checked')?.value;
        if (size && typeof calculateConferenceTotals === 'function') {
            calculateConferenceTotals(size);
        }
    }

    // Notes
    if (config.notes) {
        const ta = document.querySelector('textarea[name="conference_notes"]');
        if (ta) ta.value = config.notes;
    }

    confHasUnsavedChanges = false;
}

// ── Save ──────────────────────────────────────────────────────
async function saveConferenceConfiguration() {
    const btn = event?.target;
    const origText = btn?.innerHTML;
    if (btn) { btn.disabled = true; btn.innerHTML = '⏳ Saving...'; }

    try {
        const formData = confCollectFormData();

        const res = await fetch('conference_includes/save_conference_config.php', {
            method: 'POST',
            body: formData
        });
        const text = await res.text();
        let result;
        try { result = JSON.parse(text); }
        catch (e) { throw new Error('Invalid server response'); }

        if (result.success) {
            confShowSaved(result.message);
            if (btn) { btn.innerHTML = '✅ Saved!'; setTimeout(() => { btn.innerHTML = origText; btn.disabled = false; }, 2000); }
            confHasUnsavedChanges = false;
        } else {
            throw new Error(result.message || 'Save failed');
        }
    } catch (err) {
        confShowError('Error: ' + err.message);
        if (btn) { btn.innerHTML = origText; btn.disabled = false; }
    }
}

// ── Collect Form Data ─────────────────────────────────────────
function confCollectFormData() {
    const fd = new FormData();
    fd.append('action', 'save_conference_config');
    fd.append('project_name', document.querySelector('input[name="project_name"]')?.value || '');

    // Room info
    fd.append('conference_size',         document.querySelector('input[name="conference_size"]:checked')?.value         || '');
    fd.append('conference_meeting_type', document.querySelector('input[name="conference_meeting_type"]:checked')?.value || '');
    fd.append('conference_setup_type',   document.querySelector('input[name="conference_setup_type"]:checked')?.value   || '');

    // AV
    ['av_display_type','av_vc_platform','av_wireless_type','av_wired_drops','av_control_system','av_network_type'].forEach(name => {
        fd.append(name, document.querySelector(`select[name="${name}"]`)?.value || '');
    });
    ['av_display_required','av_vc_required','av_wireless_required','av_wired_required','av_control_required','av_network_required'].forEach(name => {
        if (document.querySelector(`input[name="${name}"]`)?.checked) fd.append(name, 'yes');
    });

    // Equipment
    const equipData = [];
    const visibleSection = document.querySelector('.conf-size-section:not(.hidden)');
    if (visibleSection) {
        visibleSection.querySelectorAll('input[type="number"]').forEach(input => {
            const qty = parseFloat(input.value) || 0;
            if (qty > 0) {
                const row      = input.closest('tr');
                const itemName = row?.querySelector('td:first-child')?.textContent.trim() || '';
                equipData.push({ name: input.name, item_description: itemName, quantity: qty, price: input.getAttribute('data-price') || '0' });
            }
        });
    }
    fd.append('equipment_data', JSON.stringify(equipData));

    // Notes
    fd.append('conference_notes', document.querySelector('textarea[name="conference_notes"]')?.value || '');

    return fd;
}

// ── Reset ─────────────────────────────────────────────────────
function resetConferenceConfiguration() {
    if (!confirm('🔄 Reset the conference form?\n\nThis will not delete your saved configuration.')) return;

    document.querySelectorAll('input[name="conference_size"]').forEach(r => r.checked = false);
    document.querySelectorAll('input[name="conference_meeting_type"]').forEach(r => r.checked = false);
    document.querySelectorAll('input[name="conference_setup_type"]').forEach(r => r.checked = false);

    // Reset AV selects
    ['av_display_type','av_vc_platform','av_wireless_type','av_wired_drops','av_control_system','av_network_type'].forEach(n => {
        const el = document.querySelector(`select[name="${n}"]`);
        if (el) el.selectedIndex = 0;
    });
    ['av_display_required','av_vc_required','av_wireless_required','av_wired_required','av_control_required','av_network_required'].forEach(n => {
        const el = document.querySelector(`input[name="${n}"]`);
        if (el) el.checked = false;
    });

    // Reset card styles
    document.querySelectorAll('.room-size-card,.meeting-type-card,.setup-type-card').forEach(c => {
        c.style.borderColor = '#e0e0e0';
        c.style.background  = 'white';
        c.style.boxShadow   = 'none';
    });

    // Hide equipment sections
    document.querySelectorAll('.conf-size-section').forEach(s => {
        s.classList.add('hidden');
        s.style.display = 'none';
    });

    const ta = document.querySelector('textarea[name="conference_notes"]');
    if (ta) ta.value = '';

    confHasUnsavedChanges = false;
    confShowInfo('Form reset. Save to update database.');
}

// ── Status indicators ─────────────────────────────────────────
function confShowInfo(msg) {
    _confStatus('#e3f2fd', '4px solid #2196F3', '#1565c0', 'ℹ️ ' + msg, 5000);
}
function confShowUnsaved() {
    _confStatus('#fff3cd', '4px solid #ffc107', '#856404', '⚠️ You have unsaved changes', 0);
}
function confShowSaved(msg) {
    _confStatus('#d4edda', '4px solid #28a745', '#155724', '✅ ' + (msg || 'Saved'), 5000);
}
function confShowError(msg) {
    _confStatus('#f8d7da', '4px solid #dc3545', '#721c24', '❌ ' + msg, 7000);
}
function _confStatus(bg, border, color, text, timeout) {
    const el   = document.getElementById('conference-save-status');
    const span = document.getElementById('conference-save-status-text');
    if (!el || !span) return;
    el.style.display    = 'block';
    el.style.background = bg;
    el.style.borderLeft = border;
    el.style.color      = color;
    span.textContent    = text;
    if (timeout > 0) setTimeout(() => el.style.display = 'none', timeout);
}

// Warn before leaving
window.addEventListener('beforeunload', e => {
    if (confHasUnsavedChanges) {
        e.preventDefault();
        e.returnValue = 'You have unsaved conference changes';
    }
});

console.log('✅ Conference Save Handler Loaded');
