jQuery(document).ready(function($) {
    if ($('#cbx-daterange').length === 0) return;

    const $input      = $('#cbx-daterange');
    const $startField = $('#cbx_start_date');
    const $endField   = $('#cbx_end_date');
    const $presetField = $('#cbx_date_range');

    $input.daterangepicker({
        autoUpdateInput: false,
        locale: {
            format: 'YYYY-MM-DD',
            separator: ' - ',
            applyLabel: 'Apply',
            cancelLabel: 'Clear',          // Changed label to "Clear" for clarity
            fromLabel: 'From',
            toLabel: 'To',
            customRangeLabel: 'Custom Range',
            daysOfWeek: moment.weekdaysMin(),
            monthNames: moment.monthsShort(),
            firstDay: 1
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
        alwaysShowCalendars: true,
        opens: 'left',
        drops: 'down'
    }, function(start, end, label) {
        // Apply selected range
        $input.val(start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD'));
        $startField.val(start.format('YYYY-MM-DD'));
        $endField.val(end.format('YYYY-MM-DD'));
        $presetField.val(label === 'Custom Range' ? 'custom' : label.toLowerCase().replace(/\s+/g, '_'));

        // Auto-submit
        $input.closest('form').submit();
    });

    // On CANCEL / CLEAR → reset everything and auto-submit (removes filter)
    $input.on('cancel.daterangepicker', function(ev, picker) {
        $input.val('Select date range');           // Reset visible text
        $startField.val('');                       // Clear hidden start
        $endField.val('');                         // Clear hidden end
        $presetField.val('');                      // Clear preset flag

        // Auto-submit form → reloads orders without date filter
        $(this).closest('form').submit();
    });

    // Initial load: show friendly placeholder if no range is set
    var startVal = $startField.val();
    var endVal   = $endField.val();
    if (startVal && endVal) {
        $input.val(startVal + ' - ' + endVal);
    } else {
        $input.val('Select date range');
    }

    $('#cbx-reset-date').on('click', function(e) {
        e.preventDefault();
        $input.val('Select date range');
        $startField.val('');
        $endField.val('');
        $presetField.val('');
        $input.closest('form').submit();
    });
});