/* global jQuery */
(function ($) {
    // --- Single source of truth for readonly ---
    const READ_ONLY = (function () {
        // prefer explicit window flag from app.blade
        if (typeof window.READ_ONLY !== 'undefined') return !!window.READ_ONLY;
        // fallback: sheet-wrap data attr
        const v = document.querySelector('.sheet-wrap')?.getAttribute('data-readonly');
        return String(v) === 'true';
    })();
    window.READ_ONLY = READ_ONLY;

    // --- Always-on: Attachments menu + viewer (works for all roles) ---
    initPoAttachments(); // call this BEFORE any early return

    function initPoAttachments() {
        // 1) shared state
        window.poattState = window.poattState || { indexUrl: null, uploadUrl: null, csrf: null, items: [], idx: 0 };
        window.poattViewer = window.poattViewer || { items: [], idx: 0, zoom: 1, fit: 'w' }; // <— move here

        // ----- helpers you already have -----
        function setAttCount(n) { const b = document.getElementById('poatt-count'); if (b) b.textContent = String(n); }
        function fetchList(url) {
            return $.getJSON(url).then(res => {
                const items = Array.isArray(res) ? res : (res.items || []);
                poattState.items = items;
                // if upload modal is open, render list there (function you already have)
                if ($('#poatt-list').length) renderPoattList(items);
                setAttCount(items.length);
                return items;
            });
        }

        window.poattFetchList = fetchList;

        // ===== two-pane viewer pieces (reuse your existing implementations) =====
        // paste your renderSideList, updateZoomLabel, applyFitForImage, renderPreview, poOpenStacked here
        // (unchanged from your current file)

        // --- Menu open/close (dropdown) ---
        $(document).on('click', '.att-trigger', function (e) {
            // In READ_ONLY we’ll open viewer directly (handled below), so skip dropdown there
            if (window.READ_ONLY) return;
            e.stopPropagation();
            const $wrap = $(this).closest('.att-actions');
            const $menu = $wrap.find('.att-menu');
            const isOpen = $menu.hasClass('is-open');

            $('.att-menu').removeClass('is-open');
            $('.att-actions').removeClass('open');
            $('.att-trigger').attr('aria-expanded', 'false');

            if (!isOpen) {
                $menu.addClass('is-open');
                $wrap.addClass('open');
                $(this).attr('aria-expanded', 'true');
            }
        });

        // outside click closes
        $(document).off('click.attGlobal').on('click.attGlobal', function () {
            $('.att-menu').removeClass('is-open');
            $('.att-actions').removeClass('open');
            $('.att-trigger').attr('aria-expanded', 'false');
        });

        // Manage uploads (only rendered for users who can update)
        $(document).on('click', '.js-att-manage', function () {
            const $t = $(this).closest('.att-actions').find('.att-trigger');
            poattState.indexUrl = $t.data('index-url');
            poattState.uploadUrl = $t.data('upload-url');
            poattState.csrf = $t.data('csrf');
            $('.att-menu').removeClass('is-open');
            $('#poatt-upload').removeClass('poatt-hidden').attr('aria-hidden', 'false');
            window.poattFetchList(window.poattState.indexUrl);
        });

        // View attachments (works for all roles)
        function openViewerFromTrigger($btn) {
            const endpoint = $btn.data('endpoint');
            const bundle = $btn.data('bundle-url');
            $.getJSON(endpoint)
                .done(res => {
                    const items = Array.isArray(res) ? res : (res.items || []);
                    setAttCount(items.length);
                    poOpenStacked(items, res?.bundle_url || bundle || null);
                })
                .fail(() => alert('Could not load attachments.'));
        }

        $(document).on('click', '.js-att-view', function () {
            const $t = $(this).closest('.att-actions').find('.att-trigger');
            $('.att-menu').removeClass('is-open');
            openViewerFromTrigger($t);
        });

        // BONUS: avoid “0 flash” on badge if server gave us a count
        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.querySelector('.att-trigger');
            const badge = document.getElementById('poatt-count');
            if (!btn || !badge) return;
            const initial = btn.getAttribute('data-initial-count');
            if (initial !== null) badge.textContent = initial;
            window.updatePoAttCount = n => { if (badge) badge.textContent = String(n); };
        });

        // Side list click -> change file
        $(document).off('click.poatt', '#poatt-side .poatt-itembtn')
            .on('click.poatt', '#poatt-side .poatt-itembtn', function (e) {
                e.preventDefault();
                const i = Number($(this).data('i') || 0);
                renderPreview(i);
            });

        // Close button
        $(document).off('click.poatt', '#poatt-stacked .poatt-close')
            .on('click.poatt', '#poatt-stacked .poatt-close', function (e) {
                e.preventDefault();
                const $m = $('#poatt-stacked');
                $m.addClass('poatt-hidden').attr('aria-hidden', 'true');
                // cleanup
                $(window).off('resize.poatt');
                $('#poatt-canvas').off('wheel.poatt');
            });

        // Click outside content to close
        $(document).off('mousedown.poatt', '#poatt-stacked').on('mousedown.poatt', '#poatt-stacked', function (e) {
            // close only if the backdrop itself was clicked
            if (e.target === this) {
                $('#poatt-stacked .poatt-close').trigger('click');
            }
        });

        // ESC to close
        $(document).off('keydown.poatt').on('keydown.poatt', function (e) {
            if (e.key === 'Escape' && $('#poatt-stacked').is(':visible')) {
                $('#poatt-stacked .poatt-close').trigger('click');
            }
        });

        // Existing: open viewer directly in READ_ONLY
        if (window.READ_ONLY) {
            $(document).off('click.att_ro', '.att-trigger').on('click.att_ro', '.att-trigger', function (e) {
                e.preventDefault();
                openViewerFromTrigger($(this));
            });
        }
    }

    function initMonthFilterDropdown() {
        const wraps = document.querySelectorAll('.dd-month .ddm');
        if (!wraps.length) return;
        wraps.forEach((wrap) => {
            if (wrap.dataset.ddmInited === '1') return;
            wrap.dataset.ddmInited = '1';

            const trigger = wrap.querySelector('.ddm__trigger');
            const menu = wrap.querySelector('.ddm__menu');
            const label = wrap.querySelector('.ddm__text');
            const hidden = document.getElementById('monthVal');

            const open = () => { menu.classList.add('is-open'); wrap.setAttribute('aria-expanded', 'true'); };
            const close = () => { menu.classList.remove('is-open'); wrap.setAttribute('aria-expanded', 'false'); };

            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                const isOpen = menu.classList.contains('is-open');
                document.querySelectorAll('.ddm__menu.is-open').forEach(m => m.classList.remove('is-open'));
                isOpen ? close() : open();
            });

            menu.addEventListener('click', (e) => {
                const item = e.target.closest('.ddm__item');
                if (!item) return;
                hidden && (hidden.value = item.getAttribute('data-value'));
                label && (label.textContent = item.textContent.trim());
                menu.querySelectorAll('.ddm__item').forEach(i => i.classList.remove('is-active'));
                item.classList.add('is-active');
                close();
                document.getElementById('poMonthFilter')?.submit();
            });

            document.addEventListener('click', (e) => { if (!wrap.contains(e.target)) close(); });
            trigger.addEventListener('keydown', (e) => {
                if (['Enter', ' '].includes(e.key)) { e.preventDefault(); open(); }
                if (e.key === 'Escape') close();
            });
        });
    }

    initMonthFilterDropdown();                    // immediate try
    $(initMonthFilterDropdown);                   // DOM-ready fallback
    window.addEventListener('load', initMonthFilterDropdown); // full-load fallback

    if (READ_ONLY) {
        $('#poHdrForm, #poRowsTbl').find('input:not([type="hidden"]), textarea, select')
            .prop('readonly', true).prop('disabled', true).addClass('locked-input');
        $('#jsAddRow, .icon-save, .icon-del, [type="submit"][form="poHdrForm"]')
            .prop('disabled', true).addClass('is-disabled');
        $('.status-trigger, .status-item, .tax-kind-btn')
            .addClass('is-disabled').attr('aria-disabled', 'true');
        $('#poHdrForm, .row-form, form.js-confirm').on('submit', e => { e.preventDefault(); return false; });

        return; // ← critical: prevents recalc() & listeners from attaching
    }

    // ---------- helpers ----------

    // Decimal normalizer for unit price (dot = decimal separator)
    function normDec(val) {
        if (val == null) return '0';
        let s = String(val).trim();
        // remove currency text/spaces
        s = s.replace(/idr|rp|\s/gi, '');
        // treat comma as decimal if there is no dot
        if (s.includes(',') && !s.includes('.')) s = s.replace(',', '.');
        // strip everything except digits and one dot
        s = s.replace(/[^0-9.]/g, '');
        const firstDot = s.indexOf('.');
        if (firstDot !== -1) {
            // remove any extra dots after the first
            s = s.slice(0, firstDot + 1) + s.slice(firstDot + 1).replace(/\./g, '');
        }
        // empty or lone dot -> 0
        if (s === '' || s === '.') s = '0';
        return s;
    }

    // Pretty print decimal without thousands grouping, up to 4 dp
    function fmtDec(n, maxDp = 4) {
        const x = Number(n);
        if (!isFinite(x)) return '0';
        // toFixed then trim trailing zeros
        let s = x.toFixed(maxDp);
        s = s.replace(/\.?0+$/, '');
        return s;
    }

    // "IDR 1,234,567" – integer only
    function fmtIDRInt(n) {
        const x = Math.round(Number(n) || 0);
        return 'IDR ' + x.toLocaleString('en-US');
    }

    // "IDR 34,111.765" (up to 4 dp, trims trailing zeros)
    function fmtIDRGroup(n) {
        const x = Number(n) || 0;
        // to 4 dp then trim zeros
        let s = x.toFixed(4).replace(/0+$/, '').replace(/\.$/, '');
        const parts = s.split('.');
        const intFmt = Number(parts[0] || 0).toLocaleString('en-US'); // 34,111
        return 'IDR ' + (parts[1] ? intFmt + '.' + parts[1] : intFmt);
    }

    function setTaxLabel(kind) {
        const map = { ppn: 'PAJAK PERTAMBAHAN NILAI (PPN)', pph: 'PAJAK PENGHASILAN (PPH)', none: 'NO TAX' };
        $('#tax-kind-label-text').text(map[(kind || 'ppn').toLowerCase()] || map.ppn);
    }

    function recalcCurrencyRow($tr) {
        const qty = parseFloat(String($tr.find('input[name*="[qty]"], input[name="qty"]').val() || '').replace(',', '.')) || 0;
        const unit = parseFloat(normDec($tr.find('input[name*="[price_aed]"], input[name="price_aed"]').val())) || 0;
        const total = qty * unit;
        const totalInt = Math.round(total);
        $tr.find('.amount-aed').text(fmtIDRInt(totalInt));
    }

    function recalc() {
        let subtotal = 0;

        // per-row totals (sum integers)
        $('#poRowsTbl tbody tr').each(function () {
            const $tr = $(this);
            const qty = parseFloat(String($tr.find('input[name="qty"], input[name*="[qty]"]').val() || '').replace(',', '.')) || 0;
            const unit = parseFloat(normDec($tr.find('input[name="price_aed"], input[name*="[price_aed]"]').val())) || 0;

            const rowTotalInt = Math.round(qty * unit);
            subtotal += rowTotalInt;
            $tr.find('.amount-aed').text(fmtIDRInt(rowTotalInt));
        });

        // manual tax → integer rupiah
        const kind = getTaxKindSafe();
        setTaxLabel(kind);
        let tax = 0;
        if (kind !== 'none') tax = Math.round(parseFloat(normDec($('#taxAmount').val() || '0')) || 0);

        // footer
        const total = subtotal + tax;
        $('#ftSubtotal').text(fmtIDRInt(subtotal));
        if (!$('#ftTax').find('input').length) $('#ftTax').text(fmtIDRInt(tax));
        $('#ftTotal').text(fmtIDRInt(total));

        if (typeof updateAmountWordsIDR === 'function') updateAmountWordsIDR(total);
    }

    // Recalc when qty / price change (delegated so new rows work too)
    $(document).on(
        'change',
        '#poRowsTbl input[name="price_aed"], #poRowsTbl input[name*="[price_aed]"], ' +
        '#poRowsTbl input[name="qty"], #poRowsTbl input[name*="[qty]"]',
        recalc
    );

    // On load: just recalc once
    $(function () {
        recalc();
    });

    function numberToWordsID(n) {
        n = Math.floor(Math.abs(Number(n) || 0));
        const s = ['nol', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
        function terbilang(x) {
            if (x < 12) return s[x];
            if (x < 20) return terbilang(x - 10) + ' belas';
            if (x < 100) return terbilang(Math.floor(x / 10)) + ' puluh' + (x % 10 ? ' ' + terbilang(x % 10) : '');
            if (x < 200) return 'seratus' + (x - 100 ? ' ' + terbilang(x - 100) : '');
            if (x < 1000) return terbilang(Math.floor(x / 100)) + ' ratus' + (x % 100 ? ' ' + terbilang(x % 100) : '');
            if (x < 2000) return 'seribu' + (x - 1000 ? ' ' + terbilang(x - 1000) : '');
            if (x < 1000000) return terbilang(Math.floor(x / 1000)) + ' ribu' + (x % 1000 ? ' ' + terbilang(x % 1000) : '');
            if (x < 1000000000) return terbilang(Math.floor(x / 1000000)) + ' juta' + (x % 1000000 ? ' ' + terbilang(x % 1000000) : '');
            if (x < 1000000000000) return terbilang(Math.floor(x / 1000000000)) + ' miliar' + (x % 1000000000 ? ' ' + terbilang(x % 1000000000) : '');
            return terbilang(Math.floor(x / 1000000000000)) + ' triliun' + (x % 1000000000000 ? ' ' + terbilang(x % 1000000000000) : '');
        }
        return terbilang(n);
    }

    function updateAmountWordsIDR(totalNumber) {
        const n = Math.abs(Number(totalNumber) || 0);

        const whole = Math.floor(n);
        let words = numberToWordsID(whole);
        if (!words) return;

        // Build fractional part as spoken digits: e.g. 0.9576 -> "koma sembilan lima tujuh enam"
        const fracStr = (n - whole).toFixed(4).slice(2).replace(/0+$/, ''); // keep up to 4 dp, trim trailing zeros
        if (fracStr) {
            const fracWords = fracStr.split('')
                .map(d => numberToWordsID(parseInt(d, 10)))
                .join(' ');
            words += ' rupiah koma ' + fracWords;
        } else {
            words += ' rupiah';
        }

        // Capitalize first letter
        words = words.charAt(0).toUpperCase() + words.slice(1);
        $('#amountWords').text(words);
    }

    // 2) Blur/commit → do per-field finalization, then full footer recompute
    $(document).on('blur',
        '#poRowsTbl input[name="price_aed"], #poRowsTbl input[name*="[price_aed]"], ' +
        '#poRowsTbl input[name="qty"], #poRowsTbl input[name*="[qty]"]',
        function () {
            if (this.name === 'price_aed' || /\[price_aed\]/.test(this.name)) {
                this.value = fmtDec(normDec(this.value), 4);  // price
            } else if (this.name === 'qty' || /\[qty\]/.test(this.name)) {
                // keep up to 4dp, trim zeros
                const v = (this.value || '').toString().replace(',', '.');
                this.value = fmtDec(v, 4);
            }
            recalc();
        }
    );

    // 3) Tax: live typing updates totals; blur snaps to plain digits (1.234.567 → 1234567)
    $(document).on('input', '#taxAmount', recalc);

    $(document).on('blur', '#taxAmount', function () {
        const v = Math.round(Number(normDec(this.value)) || 0);
        this.value = String(v);            // store as plain digits
        recalc();
    });

    // 1) Live typing → lightweight per-row recompute (no footer)
    $(document).on('input',
        '#poRowsTbl input[name="price_aed"], #poRowsTbl input[name*="[price_aed]"], ' +
        '#poRowsTbl input[name="qty"], #poRowsTbl input[name*="[qty]"]',
        function () { recalcCurrencyRow($(this).closest('tr')); }
    );

    // ---------- CREATE page ----------
    const isCreate = $('#poCreateForm').length > 0;
    if (isCreate) {

        // ---- Company Name anti-autofill sync ----
        const $supVis = $('#supCompanyVis');
        const $supHid = $('#supCompany');

        // initial mirror (for old() values)
        if ($supVis.length && $supHid.length && !$supVis.val()) {
            $supVis.val($supHid.val() || '');
        }

        // keep hidden in sync on typing/paste/pick
        $supVis.on('input change', function () {
            $supHid.val($(this).val() || '');
        });

        const $tbody = $('#poRowsTbl tbody');

        // --- per-row currency total ---
        function recalcCreateRow($tr) {
            const qty = parseFloat(String($tr.find('input[name*="[qty]"]').val() || '').replace(',', '.')) || 0;
            const unit = parseFloat(normDec($tr.find('input[name*="[price_aed]"]').val())) || 0;
            const total = qty * unit;
            $tr.find('.amount-aed').text(fmtIDRGroup(total));
        }

        function renumberCreate() {
            $tbody.find('tr').each(function (i) {
                $(this).find('.row-no').text(i + 1);
            });
        }

        function addCreateRow(data = {}) {
            const i = $tbody.find('tr').length + 1;
            const html = `
            <tr>
            <td class="center col-no row-no">${i}</td>
            <td class="col-sku"><input name="rows[${i}][sku]" class="po-input" value="${(data.sku || '').replace(/"/g, '&quot;')}"></td>
            <td class="col-brand"><input name="rows[${i}][brand]" class="po-input" value="${(data.brand || '').replace(/"/g, '&quot;')}"></td>
            <td class="col-desc"><textarea name="rows[${i}][description]" rows="1" class="po-input">${(data.description || '').replace(/</g, '&lt;')}</textarea></td>
            <td class="right col-qty"><input name="rows[${i}][qty]" class="po-input" value="${data.qty ?? 1}"></td>
            <td class="right col-unitprice"><input name="rows[${i}][price_aed]" class="po-input js-aed" inputmode="decimal" value="${data.price_aed || ''}"></td>
            <td class="right col-total amount-aed">IDR 0.00</td>
            <td class="right col-actions">
                <button type="button" class="attach-btnmini danger js-del-row">Remove</button>
            </td>
            </tr>`;
            const $row = $(html);
            $tbody.append($row);
            recalcCreateRow($row); // compute new row total immediately
        }

        // Put this right here (after addCreateRow / recalcCreateRow exist)
        $tbody.on('input blur', 'input[name*="[qty]"], input[name*="[price_aed]"]', function () {
            recalcCreateRow($(this).closest('tr'));
        });

        // add row
        $('#jsAddRow').on('click', () => addCreateRow());

        // delete row (fix selector to match your button)
        $tbody.on('click', '.js-del-row', function () {
            $(this).closest('tr').remove();
            renumberCreate();
        });

        // normalize + renumber on submit (CREATE)
        $('#poCreateForm').off('submit.__create_fix').on('submit.__create_fix', function () {
            const $tbody = $('#poRowsTbl tbody');

            // reindex to rows[0], rows[1], ... (prevents sparse indexes)
            $tbody.find('tr').each(function (i) {
                $(this).find('[name^="rows["]').each(function () {
                    this.name = this.name.replace(/rows\[\d+\]/, 'rows[' + i + ']');
                });

                // unit price → keep DECIMAL string
                const $p = $(this).find('input[name$="[price_aed]"]');
                if ($p.length) $p.val(normDec($p.val()));

                // qty → normalize decimal (allow dot)
                const $q = $(this).find('input[name$="[qty]"]');
                if ($q.length) $q.val(($q.val() || '').toString().replace(',', '.').trim());
            });
        });

        // === AUTOFILL FROM PREVIOUS POs (Company Name) ===
        (function initSupplierTypeahead() {
            const $form = $('#poCreateForm');
            const findURL = $form.data('find-url');
            const getURL = $form.data('get-url');
            if (!findURL) return;

            const $supVis = $('#supCompanyVis');
            const $supHid = $('#supCompany');
            const $menu = $('#supMenu');
            const $auto = $supVis.closest('.auto-wrap'); // wrapper around the field + menu
            const $tbody = $('#poRowsTbl tbody');

            let supHot = false; // user is intentionally in the supplier box
            let lastSupPointerTs = 0;          // last real mouse/touch inside the box

            function isSupFocused() {
                return supHot && document.activeElement === $supVis[0];
            }

            // hot-zone tracking
            $auto.on('pointerdown focusin', () => { supHot = true; lastSupPointerTs = Date.now(); });
            $auto.on('focusout', (e) => {
                if (!$auto[0].contains(e.relatedTarget)) { supHot = false; $menu.prop('hidden', true); }
            });

            // global outside click/focus hides
            $(document).on('pointerdown focusin', function (e) {
                if (!$(e.target).closest('.auto-wrap').length) {
                    supHot = false;
                    $menu.prop('hidden', true);
                }
            });

            $supVis.on('focusin', async function () {
                if (!isSupFocused()) return;
                const typed = ($supVis.val() || '').trim();
                const userClicked = (Date.now() - lastSupPointerTs) < 400;
                if (userClicked || typed) {
                    const list = await apiFind(typed || '');
                    renderSupplierList(list, { force: true });
                }
            });

            function apiFind(q) {
                return $.ajax({
                    url: findURL,
                    data: { q: q || '', type: 'supplier' },
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).then(r => Array.isArray(r) ? r : []).catch(() => []);
            }

            function apiGet(id) {
                if (!getURL || !id) return Promise.resolve(null);
                return $.ajax({
                    url: getURL,
                    data: { id },
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).catch(() => null);
            }

            function renderSupplierList(list, opts = {}) {
                const items = list.length ? list : [{ _no: true, sup_company: 'No matches' }];
                const html = items.map(r => r._no
                    ? `<div class="item" data-id="">${r.sup_company}</div>`
                    : `<div class="item"
                        data-id="${r.id}"
                        data-sup_company="${(r.sup_company || '').replace(/"/g, '&quot;')}"
                        data-sup_address="${(r.sup_address || '').replace(/"/g, '&quot;')}"
                        data-sup_phone="${(r.sup_phone || '').replace(/"/g, '&quot;')}"
                        data-sup_email="${(r.sup_email || '').replace(/"/g, '&quot;')}"
                        data-sup_npwp="${(r.sup_npwp || '').replace(/"/g, '&quot;')}">
                        <div>${r.sup_company || ''}</div>
                    </div>`
                ).join('');

                $menu.html(html);
                $menu.prop('hidden', !(opts.force && isSupFocused()) && !isSupFocused());
            }

            function fillSupplierOnly(p) {
                $('#supCompanyVis').val(p.sup_company || '');
                $('#supCompany').val(p.sup_company || ''); // keep hidden in sync
                $('[name="sup_address"]').val(p.sup_address || '');
                $('[name="sup_phone"]').val(p.sup_phone || '');
                $('[name="sup_email"]').val(p.sup_email || '');
                $('[name="sup_npwp"]').val(p.sup_npwp || '');
            }

            // Format IDR integer to a user-typed value (we keep it plain; blur will pretty it)
            function idrIntToInput(n) {
                n = Number(n || 0);
                return String(n);
            }

            // Put PO payload into the CREATE form
            function fillEntireForm(payload) {
                if (!payload || !payload.po) return;

                const po = payload.po;
                // --- Header ---
                $('input[name="po_number"]').val(po.po_number || ''); // keep if you want duplicate-friendly; else set ''
                $('input[name="po_date"]').val(po.po_date || '');
                $('input[name="address"]').val(po.address || '');
                $('input[name="ppn_rate"]').val(po.ppn_rate ?? 0);

                // Tax kind (hidden input + visible label)
                const kind = (po.tax_kind || 'ppn').toLowerCase();
                $('input[name="tax_kind"]').val(kind);
                const $kindItem = $('.tax-menu .tax-item').removeClass('is-active')
                    .filter(`[data-val="${kind}"]`).addClass('is-active');
                $('#tax-kind-label').text($kindItem.text() || 'PPN / PPH');

                // Status (hidden input + label)
                if (po.status) {
                    $('input[name="status"]').val(po.status);
                    $('#po-status-label').text(
                        po.status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
                    );
                    $('.status-menu .status-item').removeClass('is-active')
                        .filter(`[data-val="${po.status}"]`).addClass('is-active');
                }

                // --- Supplier (left box) ---
                $supVis.val(po.sup_company || '');
                $supHid.val(po.sup_company || '');
                $('[name="sup_address"]').val(po.sup_address || '');
                $('[name="sup_phone"]').val(po.sup_phone || '');
                $('[name="sup_email"]').val(po.sup_email || '');
                $('[name="sup_npwp"]').val(po.sup_npwp || '');

                // --- Ship To ---
                $('[name="ship_to_recipient"]').val(po.ship_to_recipient || '');
                $('[name="ship_to_address"]').val(po.ship_to_address || '');
                $('[name="ship_to_phone"]').val(po.ship_to_phone || '');

                // --- Payment / Delivery ---
                $('[name="payment_terms"]').val(po.payment_terms || '');
                $('[name="delivery_time"]').val(po.delivery_time || '');
                $('[name="delivery_terms"]').val(po.delivery_terms || '');
                $('[name="conditions_terms"]').val(po.conditions_terms || '');

                // --- Rows ---
                $tbody.empty();
                const rows = Array.isArray(payload.rows) ? payload.rows : [];
                rows.forEach(r => {
                    addCreateRow({
                        sku: r.sku || '',
                        brand: r.brand || '',
                        description: r.description || '',
                        qty: r.qty ?? '',
                        price_aed: idrIntToInput(r.price_aed) // plain int; blur will format, recalc will total
                    });
                });

                // Recalc totals + words
                $('#poRowsTbl tbody tr').each(function () { recalcCurrencyRow($(this)); });
                recalc();
            }

            // filter on input
            const debounce = (f, ms) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => f(...a), ms); }; };
            $supVis.on('input', debounce(async function () {
                if (!isSupFocused()) { $menu.prop('hidden', true); return; }
                const q = $(this).val().trim();
                const list = await apiFind(q || '');         // empty → recent
                renderSupplierList(list, { force: true });
            }, 150));

            $menu.on('mousedown', '.item', e => e.preventDefault()); // avoid blur before click
            $menu.on('click', '.item', async function () {
                const $it = $(this);
                const id = $it.data('id');
                if (!id) { $menu.prop('hidden', true); supHot = false; return; }

                // Fill left box immediately
                fillSupplierOnly({
                    sup_company: readData($it, 'sup_company'),
                    sup_address: readData($it, 'sup_address'),
                    sup_phone: readData($it, 'sup_phone'),
                    sup_email: readData($it, 'sup_email'),
                    sup_npwp: readData($it, 'sup_npwp')
                });

                console.log('Picked:', {
                    id,
                    sup_company: readData($it, 'sup_company'),
                    sup_address: readData($it, 'sup_address'),
                    sup_phone: readData($it, 'sup_phone'),
                    sup_email: readData($it, 'sup_email'),
                    sup_npwp: readData($it, 'sup_npwp')
                });

                // Then hydrate whole form from server (if available)
                const full = await apiGet(id);
                if (full) fillEntireForm(full);

                $menu.prop('hidden', true);
                supHot = false;
            });

            $supVis.on('keydown', async function (e) {
                if (!isSupFocused()) return;
                if (e.key === 'ArrowDown' || e.key === 'Enter') {
                    e.preventDefault();
                    const typed = ($supVis.val() || '').trim();
                    const list = await apiFind(typed || '');
                    renderSupplierList(list, { force: true });
                }
            });

            // Delay close on blur (so menu clicks register)
            $supVis.on('blur', () => setTimeout(() => $menu.prop('hidden', true), 120));

            // Keep hidden value synced on manual typing
            if ($supVis.length && $supHid.length && !$supVis.val()) {
                $supVis.val($supHid.val() || '');
            }
            $supVis.on('input change', function () { $supHid.val($(this).val() || ''); });
        })();

        // first empty row
        if ($tbody.find('tr').length === 0) addCreateRow();
    }

    // ---------- SHOW page ----------
    const isShow = $('#poHdrForm').length > 0 && !isCreate;
    if (isShow) {
        // Before PATCH a single row → normalize its fields
        $('#poRowsTbl').on('submit', 'form.row-form', function () {
            const $f = $(this);
            const $usd = $f.find('input[name="price_aed"]');
            if ($usd.length) $usd.val(normDec($usd.val()));
        });

        // Add row (AJAX)
        $('#jsAddRow').on('click', function () {
            const $btn = $(this);
            const url = $btn.data('add-url');
            const csrf = $btn.data('csrf');
            const updateT = $btn.data('update-url-template');
            const deleteT = $btn.data('delete-url-template');

            $.ajax({
                url, method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                data: { description: 'New item' }
            }).done(function (res) {
                if (!res || !res.row) return;
                const r = res.row;
                // prefer DB row number (unique per sheet); fallback to client count
                const i = (r.no != null) ? r.no : ($('#poRowsTbl tbody tr').length + 1);

                const usdPretty = (r.price_aed != null) ? fmtDec(normDec(r.price_aed)) : '';
                const updateUrl = updateT.replace('__ROW__', r.id);
                const deleteUrl = deleteT.replace('__ROW__', r.id);

                const rowHtml = `
                    <tr data-row-id="${r.id}">
                    <td class="center">${i}</td>
                    <td><input name="sku"   class="po-input" form="row-${r.id}" value="${(r.sku || '').replace(/"/g, '&quot;')}"></td>
                    <td><input name="brand" class="po-input" form="row-${r.id}" value="${(r.brand || '').replace(/"/g, '&quot;')}"></td>
                    <td class="col-desc"><textarea name="description" rows="1" class="po-input" form="row-${r.id}">${(r.description || '').replace(/</g, '&lt;')}</textarea></td>
                    <td class="right"><input name="qty"        class="po-input" inputmode="decimal" form="row-${r.id}" value="${r.qty ?? 1}"></td>
                    <td class="right"><input name="price_aed"  class="po-input" inputmode="decimal" form="row-${r.id}" value="${usdPretty}"></td>
                    <td class="right amount-aed">IDR 0.00</td>
                    <td class="right">
                        <div class="icon-actions">
                        <form id="row-${r.id}" class="row-form" method="POST" action="${updateUrl}">
                            <input type="hidden" name="_token"  value="${csrf}">
                            <input type="hidden" name="_method" value="PATCH">
                            <button class="icon-btn icon-save" type="submit" title="Save" aria-label="Save">
                            <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"></circle><path d="M9 12l2 2 4-4"></path>
                            </svg><span class="sr-only">Save</span>
                            </button>
                        </form>
                        <form class="inline-form js-confirm" method="POST" action="${deleteUrl}" data-confirm="Delete this row?">
                            <input type="hidden" name="_token"  value="${csrf}">
                            <input type="hidden" name="_method" value="DELETE">
                            <button class="icon-btn icon-del" type="submit" title="Delete" aria-label="Delete">
                            <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                                <path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                                <path d="M10 11v6"></path><path d="M14 11v6"></path>
                            </svg><span class="sr-only">Delete</span>
                            </button>
                        </form>
                        </div>
                    </td>
                    </tr>`;
                $('#poRowsTbl tbody').append(rowHtml);
                recalcCurrencyRow($('#poRowsTbl tbody tr').last());
                recalc();
            }).fail(function (xhr) {
                console.error('Add row failed:', xhr.status, xhr.responseText);
                alert('Could not add row.');
            });
        });

        // Delete a row (AJAX)
        $(document).on('submit', 'form.js-confirm', function (e) {
            e.preventDefault();
            const $f = $(this);
            if (!confirm($f.data('confirm') || 'Delete this row?')) return;

            $.ajax({
                url: $f.attr('action'), method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                data: $f.serialize()
            }).done(function () {
                const $tr = $f.closest('tr'); $tr.remove();
                $('#poRowsTbl tbody tr').each(function (i) { $(this).find('td:first').text(i + 1); });
                recalc();
            }).fail(function () { alert('Delete failed.'); });
        });

        // Delete whole PO button
        $('#btnDeletePo').on('click', function () {
            if (confirm('Delete this Purchase Order? This cannot be undone.')) {
                $('#deletePoForm').trigger('submit');
            }
        });
    }

    function getTaxKindSafe() {
        let k = String($('#tax-kind').val() || $('.tax-kind-btn.is-active').data('val') || '').trim().toLowerCase();
        if (!/^(ppn|pph|none)$/.test(k)) k = 'ppn';  // fallback to a valid value
        $('#tax-kind').val(k); // keep DOM in sync just in case
        return k;
    }

    // ==== BULK SAVE (header + all rows) on "Save PO" ====
    $(document).on('submit', '#poHdrForm', function (e) {
        e.preventDefault();

        var $form = $(this);
        var url = $form.data('update-url');
        var csrf = $form.data('csrf');

        var kind = getTaxKindSafe();
        var manualTax = normDec($('#taxAmount').val() || '');

        // 1) Collect header fields
        var payload = {
            _token: csrf,
            _method: 'PATCH',

            po_number: $('input[name="po_number"]').val() || '',
            po_date: $('input[name="po_date"]').val() || '',
            address: ($('textarea[name="address"]').val() ?? $('input[name="address"]').val() ?? ''),
            ppn_rate: (kind === 'none') ? '' : (manualTax || '0'),
            tax_kind: kind,
            status: $('input[name="status"]').val() || 'open',

            // Supplier
            sup_company: $('input[name="sup_company"]').val() || '',
            sup_address: $('textarea[name="sup_address"]').val() || '',
            sup_phone: $('input[name="sup_phone"]').val() || '',
            sup_email: $('input[name="sup_email"]').val() || '',
            sup_npwp: $('input[name="sup_npwp"]').val() || '',

            // Ship To
            ship_to_recipient: $('input[name="ship_to_recipient"]').val() || '',
            ship_to_address: $('textarea[name="ship_to_address"]').val() || '',
            ship_to_phone: $('input[name="ship_to_phone"]').val() || '',

            // Payment / Delivery
            payment_terms: $('textarea[name="payment_terms"]').val() || '',
            delivery_time: $('input[name="delivery_time"]').val() || '',
            delivery_terms: $('input[name="delivery_terms"]').val() || '',

            // Conditions & Terms (textarea)
            conditions_terms: $('textarea[name="conditions_terms"]').val() || '',
        };

        delete payload.tax_value_idr;

        // 2) Collect ALL visible rows from the table
        payload.rows = [];
        $('#poRowsTbl tbody tr').each(function () {
            var $tr = $(this);
            var id = $tr.data('row-id');
            var sku = $tr.find('input[name="sku"]').val() || '';
            var brand = $tr.find('input[name="brand"]').val() || '';
            var desc = $tr.find('textarea[name="description"]').val() || '';
            var qty = $tr.find('input[name="qty"]').val() || '';
            // normalize qty: allow one decimal dot, convert comma → dot, strip junk
            qty = qty.toString().trim().replace(',', '.');
            qty = qty.replace(/[^0-9.]/g, '');
            var d = qty.indexOf('.');
            if (d !== -1) qty = qty.slice(0, d + 1) + qty.slice(d + 1).replace(/\./g, '');
            if (qty === '' || qty === '.') qty = '0';
            var unit = $tr.find('input[name="unit"]').val() || '';

            var aed = normDec($tr.find('input[name="price_aed"]').val() || '');

            var keep = $.trim(desc) !== '' || $.trim(qty) !== '' || $.trim(sku) !== '' || $.trim(brand) !== '';
            if (!keep) return;

            payload.rows.push({ id: id || null, sku, brand, description: desc, price_aed: aed, qty, unit });
        });

        // 3) POST via AJAX (so we don’t navigate away)
        var $btn = $('.sheet-head-actions [type="submit"][form="poHdrForm"]');
        $btn.prop('disabled', true).text('Saving…');

        $.ajax({
            url: url,
            method: 'POST',
            data: payload,
            success: function () {
                recalc();

                const latest = $('textarea[name="conditions_terms"]').val() || '';

                // ---- find (or create) the PREVIEW box (not the textarea block) ----
                // we pick the .po-box whose title is exactly "Conditions & Terms"
                // AND that does NOT contain the textarea (so we don't hit the input card)
                let $wrap = $('.po-box').filter(function () {
                    const $box = $(this);
                    const title = $.trim($box.find('.po-box-title').first().text());
                    const hasTextarea = $box.find('textarea[name="conditions_terms"]').length > 0;
                    return title === 'Conditions & Terms' && !hasTextarea;
                });

                if (!$wrap.length && $.trim(latest) !== '') {
                    // Create preview block right after the textarea card
                    const $anchor = $('textarea[name="conditions_terms"]').closest('.po-box');
                    $wrap = $(`
                        <div class="po-box" id="termsBoxPreview" style="margin-top:16px;">
                            <div class="po-box-title">Conditions &amp; Terms</div>
                            <div id="termsPreview" class="terms-plain"></div>
                        </div>
                    `);
                    $anchor.after($wrap);
                }

                // ---- update preview if present ----
                const $preview = $wrap.find('#termsPreview');
                if ($preview.length) {
                    // .terms-plain has white-space: pre-wrap, so .text() preserves line breaks & spaces
                    $preview.text(latest);

                    // hide if empty, show otherwise
                    if ($.trim(latest) === '') {
                        $wrap.hide();
                    } else {
                        $wrap.show();
                    }
                }

                // button UI
                $btn.text('Saved ✓');
                setTimeout(function () { $btn.prop('disabled', false).text('Save PO'); }, 800);
            },
            error: function (xhr) {
                // Better error surfacing so you see what failed
                let msg = 'Failed to save PO. Please try again.';
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('\n');
                } else if (xhr.status === 419) {
                    msg = 'Session expired (CSRF). Refresh the page and try again.';
                }
                alert(msg);
                $btn.prop('disabled', false).text('Save PO');
            }
        });
    });

    // ----- PO Attachments modal -----
    const poattState = (window.poattState = window.poattState || { indexUrl: null, uploadUrl: null, csrf: null, items: [], idx: 0 });

    function openModal($m) { $m.removeClass('poatt-hidden').attr('aria-hidden', 'false'); }
    function closeModal($m) { $m.addClass('poatt-hidden').attr('aria-hidden', 'true'); }

    // ----- LIST (upload/manage) -----
    // modern 2-col card list (no view/download buttons here—only delete)

    function renderPoattList(rawItems) {
        const $list = $('#poatt-list').empty().addClass('poatt-list--cards'); // ensure 2-col grid
        const items = Array.isArray(rawItems) ? rawItems : (rawItems?.items || []);

        if (!items.length) {
            $list.append('<div class="poatt-muted">No files uploaded.</div>');
            return;
        }

        items.forEach(it => {
            // backend field normalization
            const name = it.original_name || it.name || 'File';
            const sizeHuman = it.size_human || (it.size ? ((Number(it.size) / 1024).toFixed(1) + ' KB') : '');
            const uploadedAt = it.uploaded_at ? (' · uploaded ' + it.uploaded_at) : '';
            const id = it.id;

            // 2 columns: meta (left) + delete (right)
            $list.append(`
            <div class="poatt-item">
                <div class="poatt-meta">
                <div class="poatt-name" title="${name.replace(/"/g, '&quot;')}">${name}</div>
                <div class="poatt-sub">${sizeHuman}${uploadedAt}</div>
                </div>
                <div class="poatt-actions">
                <button class="poatt-delete js-poatt-del" data-id="${id}" aria-label="Delete attachment">Delete</button>
                </div>
            </div>
            `);
        });
    }

    // Open upload/manage modal (paperclip)
    $(document).on('click', '.js-poatt-open-upload', function () {
        const $btn = $(this);
        window.poattState.indexUrl = $btn.data('index-url');
        window.poattState.uploadUrl = $btn.data('upload-url');
        window.poattState.csrf = $btn.data('csrf');

        openModal($('#poatt-upload'));
        window.poattFetchList(window.poattState.indexUrl);
    });

    // close modal (same as before)
    $(document).on('click', '#poatt-upload .poatt-close', () => closeModal($('#poatt-upload')));
    $(document).on('click', '#poatt-upload', function (e) { if (e.target === this) closeModal($('#poatt-upload')); });

    /* ----------------- Modernized upload bar hooks ----------------- */
    /* UI ids/classes expected from your HTML:
       #poatt-files (hidden input[type=file]),
       #poatt-browse (button),
       #poatt-msg (status line),
       #poatt-drop (drag area)
    */

    // Browse button -> trigger hidden file input
    $(document).on('click', '#poatt-browse', function () {
        $('#poatt-files').trigger('click');
    });

    // Show selected filenames
    $(document).on('change', '#poatt-files', function () {
        const files = this.files || [];
        $('#poatt-msg').text(files.length ? Array.from(files).map(f => f.name).join(', ') : 'Select files to upload…');
    });

    // Drag & drop styling + assignment
    $(document).on('dragenter dragover', '#poatt-drop', function (e) {
        e.preventDefault(); e.stopPropagation();
        $(this).addClass('is-hover');
    });
    $(document).on('dragleave drop', '#poatt-drop', function (e) {
        e.preventDefault(); e.stopPropagation();
        $(this).removeClass('is-hover');
    });
    let poattDroppedFiles = null;
    $(document).on('drop', '#poatt-drop', function (e) {
        const dt = e.originalEvent.dataTransfer;
        poattDroppedFiles = (dt && dt.files && dt.files.length) ? dt.files : null;
        $('#poatt-msg').text(poattDroppedFiles ? Array.from(poattDroppedFiles).map(f => f.name).join(', ') : '');
    });

    /* ----------------- Upload ----------------- */
    $(document).on('submit', '#poatt-form', function (e) {
        e.preventDefault();
        const inputFiles = $('#poatt-files')[0]?.files || [];
        const files = (poattDroppedFiles && poattDroppedFiles.length) ? poattDroppedFiles : inputFiles;
        if (!files.length) return;

        const fd = new FormData();
        for (let i = 0; i < files.length; i++) fd.append('files[]', files[i]);
        poattDroppedFiles = null;

        $('#poatt-msg').text('Uploading…');

        $.ajax({
            url: poattState.uploadUrl,
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': poattState.csrf, 'X-Requested-With': 'XMLHttpRequest' }
        }).done(() => {
            $('#poatt-files').val('');
            $('#poatt-msg').text('Uploaded ✓');
            window.poattFetchList(window.poattState.indexUrl);
            setTimeout(() => $('#poatt-msg').text(''), 900);
        }).fail(() => {
            $('#poatt-msg').text('Upload failed');
        });
    });

    /* ----------------- Delete ----------------- */
    $(document).on('click', '.js-poatt-del', function () {
        const id = $(this).data('id');
        if (!confirm('Delete this file?')) return;

        $.ajax({
            url: `/po/attachments/${id}`,
            method: 'POST',
            data: { _method: 'DELETE', _token: poattState.csrf },
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).done(() => window.poattFetchList(window.poattState.indexUrl))
            .fail(() => alert('Delete failed.'));
    });

    // ===== New two-pane viewer =====

    function renderSideList() {
        const $side = $('#poatt-side').empty();
        poattViewer.items.forEach((it, i) => {
            const name = it.name || it.original_name || 'Attachment';
            const $btn = $(`
      <button type="button" class="poatt-itembtn${i === poattViewer.idx ? ' is-active' : ''}" data-i="${i}">
        <span class="truncate">${name}</span>
      </button>
    `);
            $side.append($btn);
        });
    }

    function updateZoomLabel() {
        $('#poatt-zoomval').text(Math.round(poattViewer.zoom * 100) + '%');
    }

    function applyFitForImage($wrap) {
        const $canvas = $('#poatt-canvas');
        const cw = $canvas.innerWidth();

        // Reset scale first, then compute "fit width"
        poattViewer.zoom = 1;
        $wrap.css('transform', 'scale(1)');

        const img = $wrap.find('img')[0];
        if (!img || !img.naturalWidth) return;

        if (poattViewer.fit === 'w') {
            const scale = cw / img.naturalWidth;
            poattViewer.zoom = Math.max(0.1, Math.min(scale, 4));
            $wrap.css('transform', `scale(${poattViewer.zoom})`);
        }
        updateZoomLabel();
    }

    function renderPreview(index) {
        poattViewer.idx = index;
        const it = poattViewer.items[index] || {};
        const name = it.name || it.original_name || 'Attachment';
        const mime = (it.mime || '').toLowerCase();
        const view = it.view || it.view_url || it.url || '';
        const download = it.download || it.download_url || view || '#';

        // header dl current file
        $('#poatt-dl-one').attr('href', download).attr('download', name);

        // mark active in side list
        $('#poatt-side .poatt-itembtn').removeClass('is-active').eq(index).addClass('is-active');

        // reset canvas
        const $canvas = $('#poatt-canvas').empty();

        // Decide renderer
        const isImg = /image\/|\.png$|\.jpe?g$|\.gif$|\.webp$|\.bmp$|\.svg$/i.test(mime) || /\.(png|jpe?g|gif|webp|bmp|svg)$/i.test(view);

        poattViewer.zoom = 1;
        poattViewer.fit = 'w';
        updateZoomLabel();

        if (isImg) {
            const $wrap = $('<div class="poatt-imgwrap"></div>');
            const $img = $(`<img class="poatt-media" alt="">`).attr('src', view);
            $wrap.append($img);
            $('#poatt-canvas').empty().append($wrap);

            // Re-enable toolbar for images (PDF branch disables them)
            $('.poatt-zoom, .poatt-fit').prop('disabled', false).removeClass('is-disabled');
            updateZoomLabel(); // optional: ensures label shows 100% after reset

            // Fit once the image is loaded
            $img.on('load', () => applyFitForImage($wrap));

            // Ctrl/⌘ + wheel zoom
            $('#poatt-canvas').off('wheel.poatt').on('wheel.poatt', function (e) {
                if (!(e.ctrlKey || e.metaKey)) return;
                e.preventDefault();
                const delta = e.originalEvent.deltaY;
                const step = delta > 0 ? -0.1 : 0.1;
                poattViewer.zoom = Math.max(0.1, Math.min(4, poattViewer.zoom + step));
                poattViewer.fit = '';
                $wrap.css('transform', `scale(${poattViewer.zoom})`);
                updateZoomLabel();
            });

            // Toolbar buttons
            $('.poatt-zoom').off('click.poatt').on('click.poatt', function () {
                const dir = $(this).data('zoom');
                const step = dir === '+' ? 0.1 : -0.1;
                poattViewer.zoom = Math.max(0.1, Math.min(4, poattViewer.zoom + step));
                poattViewer.fit = '';
                $wrap.css('transform', `scale(${poattViewer.zoom})`);
                updateZoomLabel();
            });

            $('.poatt-fit').off('click.poatt').on('click.poatt', function () {
                const fit = $(this).data('fit'); // 'w' or '1'
                if (fit === '1') {
                    poattViewer.fit = '';
                    poattViewer.zoom = 1;
                    $wrap.css('transform', 'scale(1)');
                    updateZoomLabel();
                } else {
                    poattViewer.fit = 'w';
                    applyFitForImage($wrap);
                }
            });

            // Refit on resize
            $(window).off('resize.poatt').on('resize.poatt', () => {
                if (poattViewer.fit === 'w') applyFitForImage($wrap);
            });

        } else {
            // PDF or other doc: use built-in viewer toolbar
            const src = view
                ? (() => {
                    const hasHash = view.includes('#');
                    const joiner = hasHash ? '&' : '#';
                    return `${view}${joiner}toolbar=1&navpanes=0&view=FitH`;
                })()
                : '';
            const $iframe = $(`<iframe class="poatt-pdf" src="${src}" loading="eager"></iframe>`);
            $canvas.append($iframe);

            // Disable custom zoom buttons for PDFs
            $('.poatt-zoom, .poatt-fit').prop('disabled', true).addClass('is-disabled');
            $('#poatt-zoomval').text('PDF');
        }
    }

    function poOpenStacked(items, bundleUrl) {
        // Fill state & UI
        poattViewer.items = Array.isArray(items) ? items : (items?.items || []);
        poattViewer.idx = 0;
        renderSideList();
        renderPreview(0);

        // download all visibility
        if (bundleUrl) { $('#poatt-dl-all').attr('href', bundleUrl).show(); }
        else { $('#poatt-dl-all').hide(); }

        // Open modal
        const $m = $('#poatt-stacked');
        $m.removeClass('poatt-hidden').attr('aria-hidden', 'false');
    }

    // Eye button: fetch list → open stacked viewer
    $(document).on('click', '.js-poatt-open-viewer', function () {
        const endpoint = $(this).data('endpoint');          // /po/{po}/attachments (index)
        const bundle = $(this).data('bundle-url');        // optional bundle route

        $.getJSON(endpoint).done(res => {
            const items = Array.isArray(res) ? res : (res.items || []);
            const bundleUrl = (res && res.bundle_url) ? res.bundle_url : (bundle || null);
            poOpenStacked(items, bundleUrl);
        }).fail(() => alert('Could not load attachments.'));
    });

    $('.js-modernize-select').each(function () {
        var $sel = $(this);

        // Wrap and build menu
        $sel.wrap('<div class="select-modern"></div>');
        var $wrap = $sel.parent();
        $wrap.append('<div class="select-trigger" aria-hidden="true"></div>');
        var menuHtml = '<div class="select-menu" role="listbox">';
        $sel.find('option').each(function () {
            var v = $(this).val(), t = $(this).text();
            var sel = $sel.val() == v ? ' aria-selected="true"' : '';
            menuHtml += '<div class="select-item" role="option" data-value="' + $('<div>').text(v).html() + '"' + sel + '>' + t + '</div>';
        });
        menuHtml += '</div>';
        $wrap.append(menuHtml);

        var $menu = $wrap.find('.select-menu');

        // Open/close: click on the select opens our menu (and immediately blur to avoid native popup)
        $sel.on('mousedown', function (e) {
            e.preventDefault(); // stop native dropdown
            $('.select-modern').not($wrap).removeClass('open');
            $wrap.toggleClass('open');
        });

        // Choose item
        $menu.on('click', '.select-item', function () {
            var v = $(this).data('value'), t = $(this).text();
            $sel.val(v).trigger('change');      // update real select (form submission OK)
            $menu.find('.select-item').attr('aria-selected', 'false');
            $(this).attr('aria-selected', 'true');
            $wrap.removeClass('open');
        });

        // Reflect external changes (e.g., server-side selected or programmatic)
        $sel.on('change', function () {
            var v = $sel.val();
            $menu.find('.select-item').each(function () {
                $(this).attr('aria-selected', $(this).data('value') == v ? 'true' : 'false');
            });
        });

        // Click outside to close
        $(document).on('click', function (e) {
            if (!$wrap.is(e.target) && $wrap.has(e.target).length === 0) {
                $wrap.removeClass('open');
            }
        });

        // Keyboard: open on ArrowDown/Space/Enter
        $sel.on('keydown', function (e) {
            if (['ArrowDown', 'Enter', ' '].includes(e.key)) {
                e.preventDefault(); $wrap.addClass('open');
            }
        });
    });

    // open/close
    $(document).on('click', '.status-trigger', function (e) {
        e.stopPropagation();
        const $wrap = $(this).closest('.status-actions');
        const $menu = $wrap.find('.status-menu');

        // close others
        $('.status-menu').not($menu).removeClass('is-open');
        $('.status-actions').not($wrap).removeClass('open');

        // toggle this one
        $menu.toggleClass('is-open');
        $wrap.toggleClass('open');
        $(this).attr('aria-expanded', $menu.hasClass('is-open') ? 'true' : 'false');
    });

    // choose option
    $(document).on('click', '.status-item', function (e) {
        e.preventDefault();
        const $wrap = $(this).closest('.status-actions');
        const val = $(this).data('val');
        const label = $(this).text();

        $wrap.find('input[name="status"]').val(val);
        $wrap.find('#po-status-label').text(label);
        $wrap.find('.status-item').removeClass('is-active');
        $(this).addClass('is-active');

        $wrap.find('.status-menu').removeClass('is-open');
        $wrap.removeClass('open');
    });

    // click outside closes
    $(document).on('click', function () {
        $('.status-menu').removeClass('is-open');
        $('.status-actions').removeClass('open');
    });

    function initPoTotals() {
        // if table is present, compute both row totals and footers
        if ($('#poRowsTbl').length) {
            $('#poRowsTbl tbody tr').each(function () { recalcCurrencyRow($(this)); });
            recalc();
        }
    }

    // DOM ready
    $(initPoTotals);

    // extra safety if scripts are in <head> or assets load late
    window.addEventListener('load', initPoTotals);

    // 4) Conditions & Terms preview keeps syncing
    $(document).on('input', 'textarea[name="conditions_terms"]', function () {
        var txt = $(this).val() || '';
        var $plain = $('.terms-plain');
        if ($plain.length) $plain.text(txt);
    });

    // Tax kind buttons -> update hidden + label + toggle only the input
    $(document).on('click', '.tax-kind-btn', function () {
        var val = $(this).data('val'); // 'ppn' | 'pph' | 'none'
        $('#tax-kind').val(val);

        $('.tax-kind-btn').removeClass('is-active');
        $(this).addClass('is-active');

        setTaxLabel(val);

        var $amt = $('#taxAmount');
        if (val === 'none') {
            $amt.prop('disabled', true).val('').addClass('is-hidden');
        } else {
            $amt.prop('disabled', false).removeClass('is-hidden');
        }

        recalc();
    });

    function readData($el, key) {
        // try underscore form first (data-sup_company), then camelCase for hyphen form (data-sup-company)
        const camel = key.replace(/_([a-z])/g, (_, c) => c.toUpperCase());

        return (
            $el.data(key) ??
            $el.data(camel) ??
            $el.attr('data-' + key) ??
            $el.attr('data-' + key.replace(/_/g, '-')) ??
            ''
        );
    }

})(jQuery);
