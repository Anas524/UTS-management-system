/* global jQuery */
(function ($) {
    // ---------- helpers ----------

    function setTaxLabel(kind, rate) {
        const clean = (Number(rate) || 0)
            .toLocaleString('en-US', { maximumFractionDigits: 2 })
            .replace(/\.00$/, '');
        $('#ftTaxLabel').text('TAX ' + clean + '%');
    }

    function recalcCurrencyRow($tr) {
        const qty = parseFloat(($tr.find('input[name*="[qty]"], input[name="qty"]').val() || '').replace(',', '.')) || 0;
        const unit = parseFloat(normalizeMoneyStr($tr.find('input[name*="[price_aed]"], input[name="price_aed"]').val())) || 0;
        const total = qty * unit;
        $tr.find('.amount-aed').text(
            'IDR ' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        );
    }

    function numberToWordsEN(n) {
        n = Math.floor(Math.abs(Number(n) || 0));
        if (n === 0) return 'zero';
        const a = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten',
            'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
        const b = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];
        function chunk(x) {
            let str = '', h = Math.floor(x / 100), r = x % 100, t = Math.floor(r / 10), u = r % 10;
            if (h) str += a[h] + ' hundred';
            if (r) { if (str) str += ' '; if (r < 20) str += a[r]; else { str += b[t]; if (u) str += '-' + a[u]; } }
            return str;
        }
        const units = ['', ' thousand', ' million', ' billion', ' trillion'];
        let words = [], i = 0;
        while (n > 0 && i < units.length) {
            const c = n % 1000;
            if (c) words.unshift(chunk(c) + units[i]);
            n = Math.floor(n / 1000); i++;
        }
        return words.join(' ');
    }

    function recalc() {
        let subtotal = 0;

        const fmtIDR = v =>
            'IDR ' + Number(v || 0).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

        $('#poRowsTbl tbody tr').each(function () {
            const $tr = $(this);

            // read qty + unit using the same normalization as elsewhere
            const qty = parseFloat(($tr.find('input[name="qty"], input[name*="[qty]"]').val() || '')
                .toString().replace(',', '.')) || 0;

            const unit = parseFloat(normalizeMoneyStr(
                $tr.find('input[name="price_aed"], input[name*="[price_aed]"]').val()
            )) || 0;

            const rowTotal = qty * unit;
            subtotal += rowTotal;

            // always push the per-row total into the cell
            $tr.find('.amount-aed').text(fmtIDR(rowTotal));
        });

        const rate = Number($('input[name="ppn_rate"]').val() || 0);
        const tax = subtotal * rate / 100;
        const total = subtotal + tax;

        $('#ftSubtotal').text(fmtIDR(subtotal));
        $('#ftTax').text(fmtIDR(tax));
        $('#ftTotal').text(fmtIDR(total));

        updateAmountWordsAED(total);
    }

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

    function updateAmountWordsAED(totalNumber) {
        const rupiah = numberToWordsID(Math.floor(Math.abs(Number(totalNumber) || 0)));
        if (rupiah) {
            const txt = rupiah.charAt(0).toUpperCase() + rupiah.slice(1) + ' rupiah';
            $('#amountWords').text(txt);
        }
    }

    function prettyAED(s) { s = normalizeMoneyStr(s); return s ? 'IDR ' + s : ''; }

    $(document).on('blur',
        '#poRowsTbl input[name="price_aed"], #poRowsTbl input[name*="[price_aed]"]',
        function () { this.value = prettyAED(this.value); recalc(); }
    );

    function normalizeMoneyStr(s) {
        if (!s) return '';
        s = String(s).replace(/[^\d.,-]/g, '');
        if (s.includes(',') && !s.includes('.')) s = s.replace(',', '.'); else s = s.replace(/,/g, '');
        return s;
    }

    function applyPreparedNameToPage(name) {
        const clean = (name || '').trim();
        document.querySelectorAll('.js-prep').forEach(el => {
            const fallback = el.getAttribute('data-fallback') || 'the purchaser';
            el.textContent = clean !== '' ? clean : fallback;
        });
    }

    // Common live bindings
    $(document)
        .on('input',
            '#poRowsTbl input[name="price_aed"], #poRowsTbl input[name*="[price_aed]"], ' +
            '#poRowsTbl input[name="qty"], #poRowsTbl input[name*="[qty]"], input[name="ppn_rate"]',
            recalc)
        .on('change', 'select[name="tax_kind"]', recalc);

    $(document).on('input blur',
        '#poRowsTbl input[name="price_aed"], #poRowsTbl input[name*="[price_aed]"], ' +
        '#poRowsTbl input[name="qty"], #poRowsTbl input[name*="[qty]"]',
        function () { recalcCurrencyRow($(this).closest('tr')); }
    );

    // ---------- CREATE page ----------
    const isCreate = $('#poCreateForm').length > 0;
    if (isCreate) {

        const $tbody = $('#poRowsTbl tbody');

        // --- per-row currency total ---
        function recalcCreateRow($tr) {
            const qty = parseFloat(($tr.find('input[name*="[qty]"]').val() || '').replace(',', '.')) || 0;
            const unit = parseFloat(normalizeMoneyStr($tr.find('input[name*="[price_aed]"]').val())) || 0;
            const total = qty * unit;
            $tr.find('.amount-aed').text(
                'IDR ' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
            );
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

                // unit price → plain decimal (no currency/commas)
                const $p = $(this).find('input[name$="[price_aed]"]');
                if ($p.length) $p.val(normalizeMoneyStr($p.val()));

                // qty → plain number string (no commas). Keep '' if truly empty.
                const $q = $(this).find('input[name$="[qty]"]');
                if ($q.length) $q.val(($q.val() || '').replace(/,/g, '').trim());
            });
        });

        // === AUTOFILL FROM PREVIOUS POs (Supplier / PO Number) ===
        // Requires the Blade to add:
        //  - <input id="fldSupplier"> + <div id="supMenu" class="po-autolist" hidden></div>
        //  - <input id="fldPoNumber"> + <div id="poMenu"  class="po-autolist" hidden></div>
        //  - #poCreateForm has data-find-url and data-get-url

        (function initPoAutofill() {
            const $form = $('#poCreateForm');
            const findURL = $form.data('find-url');
            const getURL = $form.data('get-url');
            if (!findURL || !getURL) return; // guard if not present

            const $fldSupplier = $('#fldSupplier');
            const $fldPoNumber = $('#fldPoNumber');
            const $supMenu = $('#supMenu');
            const $poMenu = $('#poMenu');

            // Helpers
            const fmtIDR = v => 'IDR ' + Number(v || 0).toLocaleString('en-US', { maximumFractionDigits: 0 });
            const escapeAttr = s => String(s ?? '').replace(/"/g, '&quot;');
            const nl2 = s => String(s ?? '').replace(/</g, '&lt;');

            function renderList($menu, results, onPick) {
                const html = (results && results.length ? results : [{ id: '', _no: true, prepared_by: 'No matches' }])
                    .map(r => {
                        if (r._no) return `<div class="item" data-id="">${r.prepared_by}</div>`;
                        const line1 = r.po_number ? r.po_number : (r.prepared_by || '');
                        const line2 = [r.prepared_by, r.po_number, r.po_date].filter(Boolean).join(' • ');
                        return `<div class="item" data-id="${r.id}">
                                    <div>
                                        <div>${line1}</div>
                                        <div class="meta">${line2}</div>
                                    </div>
                                </div>`;
                    }).join('');
                $menu.html(html).prop('hidden', false);
                $menu.find('.item').on('click', function () {
                    const id = $(this).data('id');
                    if (!id) { $menu.prop('hidden', true); return; }
                    onPick(id);
                });
            }

            function clearRows() { $tbody.empty(); }

            function addRowFromData(r, i) {
                const qty = Number(r.qty ?? 0);
                const priceFils = parseInt(r.price_aed ?? 0, 10);     // backend cents
                const unitIDR = Math.round(priceFils / 100);        // -> whole rupiah number for create form
                const amount = Math.round(unitIDR * qty);

                const rowHtml = `
      <tr>
        <td class="center col-no row-no">${i + 1}</td>
        <td class="col-sku"><input name="rows[${i + 1}][sku]" class="po-input" value="${escapeAttr(r.sku)}"></td>
        <td class="col-brand"><input name="rows[${i + 1}][brand]" class="po-input" value="${escapeAttr(r.brand)}"></td>
        <td class="col-desc"><textarea name="rows[${i + 1}][description]" rows="1" class="po-input">${nl2(r.description)}</textarea></td>
        <td class="right col-qty"><input name="rows[${i + 1}][qty]" class="po-input" value="${qty || ''}"></td>
        <td class="right col-unitprice"><input name="rows[${i + 1}][price_aed]" class="po-input js-aed" inputmode="decimal" value="${unitIDR || ''}"></td>
        <td class="right col-total amount-aed">${fmtIDR(amount)}</td>
        <td class="right col-actions">
          <button type="button" class="attach-btnmini danger js-del-row">Remove</button>
        </td>
      </tr>`;
                const $row = $(rowHtml);
                $tbody.append($row);
                // reuse your existing per-row calc to keep UI consistent
                (function recalcCreateRow($tr) {
                    const q = parseFloat(($tr.find('input[name*="[qty]"]').val() || '').replace(',', '.')) || 0;
                    const u = parseFloat(normalizeMoneyStr($tr.find('input[name*="[price_aed]"]').val())) || 0;
                    const t = q * u;
                    $tr.find('.amount-aed').text('IDR ' + t.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                })($row);
            }

            // Fill header inputs from payload
            function fillHeader(po) {
                function set(name, v) { const $el = $form.find(`[name="${name}"]`); if ($el.length) { $el.val(v ?? ''); } }
                if (po.prepared_by != null) $fldSupplier.val(po.prepared_by);
                if (po.po_number != null) $fldPoNumber.val(po.po_number);

                set('po_date', po.po_date ?? '');
                set('npwp', po.npwp ?? '');
                set('ppn_rate', po.ppn_rate ?? 0);
                set('address', po.address ?? '');

                set('sup_company', po.sup_company ?? '');
                set('sup_address', po.sup_address ?? '');
                set('sup_phone', po.sup_phone ?? '');
                set('sup_email', po.sup_email ?? '');
                set('sup_contact_person', po.sup_contact_person ?? '');
                set('sup_contact_phone', po.sup_contact_phone ?? '');
                set('sup_contact_email', po.sup_contact_email ?? '');
            }

            // Server calls
            function apiFind(q, type) {
                return $.ajax({
                    url: $('#poCreateForm').data('find-url'),
                    data: { q: q || '', type },
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).then(res => Array.isArray(res) ? res : []).catch(() => []);
            }
            function apiGet(id) {
                return $.getJSON(getURL, { id }).then(r => r);
            }

            async function pick(id) {
                try {
                    const data = await apiGet(id);
                    const po = data.po ?? data;
                    const rows = data.rows ?? po.rows ?? [];

                    fillHeader(po);
                    clearRows();
                    rows.forEach((r, i) => addRowFromData(r, i));

                    // Recalc footers from current DOM using your existing recalc()
                    recalc();
                } finally {
                    $supMenu.prop('hidden', true);
                    $poMenu.prop('hidden', true);
                }
            }

            // Search on focus and input (Supplier)
            $fldSupplier.on('focus input', async function () {
                const q = $(this).val().trim();
                const res = await apiFind(q, 'supplier');
                renderList($supMenu, res, pick);
            });

            // Search on focus and input (PO number)
            $fldPoNumber.on('focus input', async function () {
                const q = $(this).val().trim();
                const res = await apiFind(q, 'number');
                renderList($poMenu, res, pick);
            });

            // Click outside to close menus
            $(document).on('click', function (e) {
                if (!$supMenu.is(e.target) && $supMenu.has(e.target).length === 0 && e.target !== $fldSupplier[0]) $supMenu.prop('hidden', true);
                if (!$poMenu.is(e.target) && $poMenu.has(e.target).length === 0 && e.target !== $fldPoNumber[0]) $poMenu.prop('hidden', true);
            });
        })();
        // === /AUTOFILL FROM PREVIOUS POs ===

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
            if ($usd.length) $usd.val(normalizeMoneyStr($usd.val()));
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
                const i = $('#poRowsTbl tbody tr').length + 1;
                const usdPretty = (r.price_aed != null) ? ('IDR ' + (r.price_aed / 100).toFixed(2)) : '';
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
            }).fail(function () { alert('Could not add row.'); });
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

    // ==== BULK SAVE (header + all rows) on "Save PO" ====
    $(document).on('submit', '#poHdrForm', function (e) {
        e.preventDefault();

        var $form = $(this);
        var url = $form.data('update-url');
        var csrf = $form.data('csrf');

        // 1) Collect header fields
        var payload = {
            _token: csrf,
            _method: 'PATCH',
            prepared_by: $('input[name="prepared_by"]').val() || '',
            po_number: $('input[name="po_number"]').val() || '',
            po_date: $('input[name="po_date"]').val() || '',
            vendor: $('input[name="vendor"]').val() || '',
            npwp: $('input[name="npwp"]').val() || '',
            address: $('input[name="address"]').val() || '',
            ppn_rate: $('input[name="ppn_rate"]').val() || '',
            tax_kind: $('select[name="tax_kind"]').val() || 'VAT',
            status: $('input[name="status"]').val() || 'open',
            sup_company: $('input[name="sup_company"]').val() || '',
            sup_address: $('textarea[name="sup_address"]').val() || '',
            sup_phone: $('input[name="sup_phone"]').val() || '',
            sup_email: $('input[name="sup_email"]').val() || '',
            sup_contact_person: $('input[name="sup_contact_person"]').val() || '',
            sup_contact_phone: $('input[name="sup_contact_phone"]').val() || '',
            sup_contact_email: $('input[name="sup_contact_email"]').val() || '',
            currency: $('input[name="currency"], select[name="currency"]').val() || ''
        };

        // 2) Collect ALL visible rows from the table
        payload.rows = [];
        $('#poRowsTbl tbody tr').each(function (i, tr) {
            var $tr = $(tr);
            var id = $tr.data('row-id'); // from <tr data-row-id="...">

            // read inputs bound to the per-row form, but values are in the DOM anyway
            var sku = $tr.find('input[name="sku"]').val() || '';
            var brand = $tr.find('input[name="brand"]').val() || '';
            var desc = $tr.find('textarea[name="description"]').val() || '';
            var qty = $tr.find('input[name="qty"]').val() || '';
            var unit = $tr.find('input[name="unit"]').val() || '';

            var aed = ($tr.find('input[name="price_aed"]').val() || '');
            aed = (aed + '').replace(/[^\d.,-]/g, '');
            if (aed.indexOf(',') !== -1 && aed.indexOf('.') === -1) {
                aed = aed.replace(',', '.');   // "12,34" -> "12.34"
            } else {
                aed = aed.replace(/,/g, '');   // drop thousands commas
            }

            // keep this row if it has any meaningful content (match controller’s logic)
            var keep = $.trim(desc) !== '' || $.trim(qty) !== '' || $.trim(sku) !== '' || $.trim(brand) !== '';
            if (!keep) return;

            var row = {
                id: id || null,
                sku: sku,
                brand: brand,
                description: desc,
                price_aed: aed,        // string, server converts to cents
                qty: qty,
                unit: unit
            };
            payload.rows.push(row);
        });

        // 3) POST via AJAX (so we don’t navigate away)
        var $btn = $('.sheet-head-actions [type="submit"][form="poHdrForm"]');
        $btn.prop('disabled', true).text('Saving…');

        $.ajax({
            url: url,
            method: 'POST',
            data: payload,
            success: function (resp) {
                applyPreparedNameToPage(payload.prepared_by);

                recalc();
                // success toast
                // (optionally re-fetch rows or simply show a message + refresh totals)
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
    // ---- State
    let poattState = {
        indexUrl: null,
        uploadUrl: null,
        csrf: null,
        items: [],
        idx: 0
    };

    function openModal($m) { $m.removeClass('poatt-hidden').attr('aria-hidden', 'false'); }
    function closeModal($m) { $m.addClass('poatt-hidden').attr('aria-hidden', 'true'); }

    // ----- LIST (upload/manage) -----
    // modern 2-col card list (no view/download buttons here—only delete)

    function renderList(rawItems) {
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

    function fetchList(url) {
        return $.getJSON(url).then(res => {
            const items = Array.isArray(res) ? res : (res.items || []);
            poattState.items = items;
            renderList(items);
            setAttCount(items.length);
            return items;
        });
    }

    // Open upload/manage modal (paperclip)
    $(document).on('click', '.js-poatt-open-upload', function () {
        const $btn = $(this);
        poattState.indexUrl = $btn.data('index-url');
        poattState.uploadUrl = $btn.data('upload-url');
        poattState.csrf = $btn.data('csrf');

        openModal($('#poatt-upload'));
        fetchList(poattState.indexUrl);
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
    $(document).on('drop', '#poatt-drop', function (e) {
        const dt = e.originalEvent.dataTransfer;
        if (dt && dt.files && dt.files.length) {
            const $input = $('#poatt-files')[0];
            // assign files to the hidden input
            $input.files = dt.files;
            $('#poatt-msg').text(Array.from(dt.files).map(f => f.name).join(', '));
        }
    });

    /* ----------------- Upload ----------------- */
    $(document).on('submit', '#poatt-form', function (e) {
        e.preventDefault();

        const $files = $('#poatt-files');
        const files = $files[0]?.files || [];
        if (!files.length) return;

        const fd = new FormData();
        for (let i = 0; i < files.length; i++) fd.append('files[]', files[i]);

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
            fetchList(poattState.indexUrl);
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
        }).done(() => fetchList(poattState.indexUrl))
            .fail(() => alert('Delete failed.'));
    });

    // ===== New two-pane viewer =====

    let poattViewer = { items: [], idx: 0, zoom: 1, fit: 'w' };

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
            $canvas.append($wrap);

            // Fit once the image is loaded
            $img.on('load', () => applyFitForImage($wrap));

            // Ctrl/⌘ + wheel zoom
            $canvas.off('wheel.poatt').on('wheel.poatt', function (e) {
                if (!(e.ctrlKey || e.metaKey)) return; // only zoom when ctrl/cmd
                e.preventDefault();
                const delta = e.originalEvent.deltaY;
                const step = delta > 0 ? -0.1 : 0.1;
                poattViewer.zoom = Math.max(0.1, Math.min(4, poattViewer.zoom + step));
                poattViewer.fit = ''; // leave fit mode
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
            const src = view ? (view + (view.includes('#') ? '' : '#') + 'toolbar=1&navpanes=0&view=FitH') : '';
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

    // Side list click -> load selected
    $(document).on('click', '#poatt-side .poatt-itembtn', function () {
        const i = Number($(this).data('i') || 0);
        renderPreview(i);
    });

    // Close viewer
    $(document).on('click', '#poatt-stacked .poatt-close', function () {
        const $m = $('#poatt-stacked');
        $m.addClass('poatt-hidden').attr('aria-hidden', 'true');
        // cleanup listeners
        $(window).off('resize.poatt');
        $('#poatt-canvas').off('wheel.poatt');
    });

    function setAttCount(n) {
        const b = document.getElementById('poatt-count');
        if (b) b.textContent = String(n);
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

    // open/close dropdown
    $(document).on('click', '.att-trigger', function (e) {
        e.stopPropagation();
        const $wrap = $(this).closest('.att-actions');
        const $menu = $wrap.find('.att-menu');
        const isOpen = $menu.hasClass('is-open');

        // close others
        $('.att-menu').removeClass('is-open');
        $('.att-actions').removeClass('open');
        $('.att-trigger').attr('aria-expanded', 'false');

        // toggle this one
        if (!isOpen) {
            $menu.addClass('is-open');
            $wrap.addClass('open');
            $(this).attr('aria-expanded', 'true');
        }
    });

    // click outside closes
    $(document).on('click', function () {
        $('.att-menu').removeClass('is-open');
        $('.att-actions').removeClass('open');
        $('.att-trigger').attr('aria-expanded', 'false');
    });

    // Manage uploads (opens your upload modal with state)
    $(document).on('click', '.js-att-manage', function () {
        const $t = $(this).closest('.att-actions').find('.att-trigger');
        window.poattState = window.poattState || {};
        poattState.indexUrl = $t.data('index-url');
        poattState.uploadUrl = $t.data('upload-url');
        poattState.csrf = $t.data('csrf');
        $('.att-menu').removeClass('is-open');
        openModal($('#poatt-upload'));
        fetchList(poattState.indexUrl);
    });

    // View attachments (opens stacked viewer)
    $(document).on('click', '.js-att-view', function () {
        const $t = $(this).closest('.att-actions').find('.att-trigger');
        const endpoint = $t.data('endpoint');
        const bundle = $t.data('bundle-url');
        $('.att-menu').removeClass('is-open');

        $.getJSON(endpoint).done(res => {
            const items = Array.isArray(res) ? res : (res.items || []);
            setAttCount(items.length);
            poOpenStacked(items, res.bundle_url || bundle || null);
        }).fail(() => alert('Could not load attachments.'));
    });

    document.addEventListener('DOMContentLoaded', () => {
        const btn = document.querySelector('.att-trigger');
        const badge = document.getElementById('poatt-count');
        if (!btn || !badge) return;

        // Use server-rendered count if present (prevents “0” flash)
        const initial = btn.getAttribute('data-initial-count');
        if (initial !== null) badge.textContent = initial;

        // Your existing upload/delete logic should call this after it mutates files:
        window.updatePoAttCount = function (n) {
            if (badge) badge.textContent = String(n);
        };
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

    const wrap = document.querySelector('.dd-month .ddm');
    if (wrap) {
        const trigger = wrap.querySelector('.ddm__trigger');
        const menu = wrap.querySelector('.ddm__menu');
        const label = wrap.querySelector('.ddm__text');
        const hidden = document.getElementById('monthVal');

        function open() {
            menu.classList.add('is-open');
            wrap.setAttribute('aria-expanded', 'true');
        }
        function close() {
            menu.classList.remove('is-open');
            wrap.setAttribute('aria-expanded', 'false');
        }

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = menu.classList.contains('is-open');
            document.querySelectorAll('.ddm__menu.is-open').forEach(m => m.classList.remove('is-open'));
            isOpen ? close() : open();
        });

        menu.addEventListener('click', (e) => {
            const item = e.target.closest('.ddm__item');
            if (!item) return;
            const val = item.getAttribute('data-value');
            const text = item.textContent.trim();

            hidden.value = val;
            label.textContent = text;

            menu.querySelectorAll('.ddm__item').forEach(i => i.classList.remove('is-active'));
            item.classList.add('is-active');

            close();
            document.getElementById('poMonthFilter')?.submit();
        });

        document.addEventListener('click', (e) => {
            if (!wrap.contains(e.target)) close();
        });

        trigger.addEventListener('keydown', (e) => {
            if (['Enter', ' '].includes(e.key)) { e.preventDefault(); open(); }
            if (e.key === 'Escape') close();
        });
    }

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

})(jQuery);
