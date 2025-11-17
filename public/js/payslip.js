// public/js/payslip.js

/* global $ */
$(function () {
    console.log('[payslip] script loaded');

    var $modal = $('#payslipModal');

    if ($modal.length === 0) {
        console.log('[payslip] no #payslipModal found on this page');
        return;
    }

    var $backdrop = $modal.find('.pay-modal-backdrop');

    function openModal() {
        console.log('[payslip] openModal');
        $modal.addClass('pay-modal--open');
        $('body').addClass('body-pay-modal-open');

        // Remove ?new=1 from URL after first open so refresh won't reopen modal
        try {
            var url = new URL(window.location.href);
            if (url.searchParams.has('new')) {
                url.searchParams.delete('new');
                window.history.replaceState({}, '', url.toString());
            }
        } catch (e) {
            console.warn('[payslip] URL update failed', e);
        }
    }

    function closeModal() {
        console.log('[payslip] closeModal');
        $modal.removeClass('pay-modal--open');
        $('body').removeClass('body-pay-modal-open');
    }

    // Delegate click for open button (in case of future dynamic content)
    $(document).on('click', '#btnCreatePayslip, #btnCreatePayslip-empty', function (e) {
        e.preventDefault();
        openModal();
    });

    // Delegate click for any [data-close] inside modal
    $(document).on('click', '#payslipModal [data-close]', function (e) {
        e.preventDefault();
        closeModal();
    });

    // Backdrop click closes modal
    $backdrop.on('click', function () {
        closeModal();
    });

    // ESC key closes modal
    $(document).on('keydown.payslip', function (e) {
        if (e.key === 'Escape' && $modal.hasClass('pay-modal--open')) {
            closeModal();
        }
    });

    // Auto-open when URL has ?new=1 (from dashboard "Create" shortcut)
    var params = new URLSearchParams(window.location.search);
    if (params.get('new') === '1') {
        openModal();
    }
});
