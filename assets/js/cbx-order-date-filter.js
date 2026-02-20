jQuery(document).ready(function($) {
    if ($('#cbx-daterange').length === 0) return;

    $('#cbx-daterange').daterangepicker({
        autoUpdateInput: false,          // We'll set it manually
        locale: {
            format: 'YYYY-MM-DD',
            separator: ' - ',
            applyLabel: 'Apply',
            cancelLabel: 'Cancel',
            fromLabel: 'From',
            toLabel: 'To',
            customRangeLabel: 'Custom Range',
            daysOfWeek: moment.weekdaysMin(),
            monthNames: moment.monthsShort(),
            firstDay: 1  // Monday start (change if needed)
        },
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'This Week': [moment().startOf('week'), moment()],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        alwaysShowCalendars: true,       // Always show calendar even with preset
        opens: 'left',
        drops: 'down'
    }, function(start, end, label) {
        // Update visible input
        $('#cbx-daterange').val(start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD'));

        // Update hidden fields
        $('#cbx_start_date').val(start.format('YYYY-MM-DD'));
        $('#cbx_end_date').val(end.format('YYYY-MM-DD'));
        $('#cbx_date_range').val(label === 'Custom Range' ? 'custom' : label.toLowerCase().replace(/\s+/g, '_'));

        // Auto-submit the form (page reloads with filter applied)
        $('#cbx-daterange').closest('form').submit();
    });

    // If pre-selected value exists, set it on load
    var startVal = $('#cbx_start_date').val();
    var endVal   = $('#cbx_end_date').val();
    if (startVal && endVal) {
        $('#cbx-daterange').val(startVal + ' - ' + endVal);
    }
});