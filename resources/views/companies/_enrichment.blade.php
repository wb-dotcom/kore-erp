{{-- Company Enrichment via OpenStreetMap Nominatim (fully free, no API key) --}}
@push('scripts')
<script>
(function () {
    const nameInput = document.getElementById('companyName');
    if (!nameInput) return;

    // ── Build dropdown ─────────────────────────────────────────────────
    const wrapper = nameInput.parentElement;
    wrapper.style.position = 'relative';

    const dropdown = document.createElement('div');
    dropdown.id = 'enrichmentDropdown';
    Object.assign(dropdown.style, {
        position:     'absolute',
        top:          '100%',
        left:         '0',
        width:        '100%',
        marginTop:    '2px',
        background:   '#fff',
        border:       '1px solid #d1d5db',
        borderRadius: '6px',
        boxShadow:    '0 4px 14px rgba(0,0,0,0.10)',
        zIndex:       '1050',
        maxHeight:    '300px',
        overflowY:    'auto',
        display:      'none',
        fontSize:     '0.8rem',
    });
    wrapper.appendChild(dropdown);

    // ── Spinner badge on the input ──────────────────────────────────────
    const badge = document.createElement('span');
    badge.innerHTML = '<i class="bi bi-geo-alt-fill" style="color:#6b7280;"></i>';
    Object.assign(badge.style, {
        position:  'absolute',
        right:     '8px',
        top:       '50%',
        transform: 'translateY(-50%)',
        fontSize:  '0.75rem',
        display:   'none',
    });
    wrapper.appendChild(badge);

    // ── Debounced fetch ─────────────────────────────────────────────────
    let timer = null;
    let activeQuery = '';

    nameInput.addEventListener('input', function () {
        const q = this.value.trim();
        clearTimeout(timer);
        if (q.length < 3) { hide(); return; }
        timer = setTimeout(() => fetchSuggestions(q), 520);
    });

    async function fetchSuggestions(q) {
        if (q === activeQuery) return;
        activeQuery = q;
        badge.style.display = 'block';
        try {
            const res = await fetch(
                '/api/company-enrichment?q=' + encodeURIComponent(q),
                { headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } }
            );
            if (!res.ok) { hide(); return; }
            const data = await res.json();
            render(data);
        } catch (_) {
            hide();
        } finally {
            badge.style.display = 'none';
        }
    }

    // ── Render results ─────────────────────────────────────────────────
    function render(results) {
        dropdown.innerHTML = '';
        if (!results.length) { hide(); return; }

        results.forEach(function (item) {
            const label = item.name || (item.display_name || '').split(',')[0];
            const sub   = item.city
                ? [item.city, item.state, item.country].filter(Boolean).join(', ')
                : (item.display_name || '').split(',').slice(1, 3).join(',').trim();

            const extras = [];
            if (item.phone)   extras.push('<i class="bi bi-telephone-fill me-1"></i>' + esc(item.phone));
            if (item.website) extras.push('<i class="bi bi-globe me-1"></i>' + esc(item.website.replace(/^https?:\/\//, '')));

            const row = document.createElement('div');
            row.className = 'enrichment-item';
            Object.assign(row.style, {
                padding:      '8px 12px',
                cursor:       'pointer',
                borderBottom: '1px solid #f3f4f6',
            });
            row.innerHTML =
                '<div style="font-weight:600;color:#111827;">' + esc(label) + '</div>' +
                '<div style="color:#6b7280;font-size:0.72rem;">' + esc(sub) + '</div>' +
                (extras.length
                    ? '<div style="color:#9ca3af;font-size:0.7rem;margin-top:2px;">' + extras.join(' &nbsp;·&nbsp; ') + '</div>'
                    : '');

            row.addEventListener('mouseenter', function () { this.style.background = '#f9fafb'; });
            row.addEventListener('mouseleave', function () { this.style.background = ''; });
            row.addEventListener('mousedown', function (e) {
                e.preventDefault(); // keep focus on nameInput
                fill(item);
                hide();
            });
            dropdown.appendChild(row);
        });

        // Attribution footer (required by OSM usage policy)
        const footer = document.createElement('div');
        Object.assign(footer.style, {
            padding:    '5px 12px',
            fontSize:   '0.68rem',
            color:      '#9ca3af',
            background: '#f9fafb',
            textAlign:  'center',
        });
        footer.innerHTML = '© <a href="https://www.openstreetmap.org/copyright" target="_blank" style="color:#9ca3af;">OpenStreetMap</a> contributors';
        dropdown.appendChild(footer);

        dropdown.style.display = 'block';
    }

    // ── Fill form fields ───────────────────────────────────────────────
    function fill(item) {
        setVal('address_line1', item.address_line1);
        setVal('city',          item.city);
        setVal('state',         item.state);
        setVal('zip',           item.zip);
        setVal('country',       item.country);

        // Only fill phone/website if currently empty (don't overwrite user data)
        if (item.phone)   setValIfEmpty('phone',   item.phone);
        if (item.website) setValIfEmpty('website', item.website);

        // Update the name input if OSM returned a cleaner name
        if (item.name && item.name.trim()) {
            nameInput.value = item.name.trim();
        }

        // Flash a brief "filled" highlight
        ['address_line1','city','state','zip','country'].forEach(function (n) {
            const el = document.querySelector('[name="' + n + '"]');
            if (el && el.value) {
                el.style.transition = 'background 0.3s';
                el.style.background = '#f0fdf4';
                setTimeout(function () { el.style.background = ''; }, 1200);
            }
        });
    }

    function setVal(name, value) {
        const el = document.querySelector('[name="' + name + '"]');
        if (el && value) el.value = value;
    }

    function setValIfEmpty(name, value) {
        const el = document.querySelector('[name="' + name + '"]');
        if (el && !el.value.trim() && value) el.value = value;
    }

    function esc(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function hide() {
        dropdown.style.display = 'none';
        activeQuery = '';
    }

    // ── Dismiss on outside click / Escape ─────────────────────────────
    document.addEventListener('click', function (e) {
        if (!wrapper.contains(e.target)) hide();
    });

    nameInput.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') hide();
    });
})();
</script>
@endpush
