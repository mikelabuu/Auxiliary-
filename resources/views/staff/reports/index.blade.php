@extends('layouts.admin')
@section('title', 'Advanced Reports Engine')
@section('page-title', 'Advanced Reports Engine')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 mb-1 fw-bold">Analytics & Reporting</h2>
            <p class="text-muted small">Generate and export detailed hotel performance data.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" onclick="generateReport()">
                <i class="fas fa-sync-alt me-1"></i> Update Results
            </button>
            <button class="btn btn-outline-secondary btn-sm px-3" onclick="resetView()">
                <i class="fas fa-undo me-1"></i> Reset
            </button>
            <button id="exportBtn" class="btn btn-success btn-sm px-3" onclick="exportReport()" disabled>
                <i class="fas fa-file-excel me-1"></i> Export Excel
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-uppercase text-muted">Report Category</label>
                    <select id="report_type" class="form-select form-select-sm custom-select">
                        <option value="booking">Booking Report</option>
                        <option value="payment">Financial Report</option>
                        <option value="combined">Combined Overview</option>
                    </select>
                    <select id="column_set" class="d-none">
                        <option value="booking_summary">Booking Summary</option>
                        <option value="financial">Financial</option>
                        <option value="combined">Combined</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-uppercase text-muted">Timeframe</label>
                    <div class="input-group input-group-sm">
                        <select id="date_type" class="form-select" style="max-width: 35%;">
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                            <option value="range">Custom</option>
                        </select>
                        <input type="month" id="date_month" class="form-control">
                        <select id="date_year" class="form-select" style="display:none;"></select>
                        <input type="date" id="date_from" class="form-control" style="display:none;">
                        <input type="date" id="date_to" class="form-control" style="display:none;">
                    </div>
                </div>

                <div class="col-md-2" id="group_booking_status">
                    <label class="form-label fw-semibold small text-uppercase text-muted">Booking Status</label>
                    <select id="booking_status" class="form-select form-select-sm status-select-scroll" multiple>
                        <option value="all" selected>All Statuses</option>
                        <option value="pending_discount">Pending Discount</option>
                        <option value="pending_payment">Pending Payment</option>
                        <option value="paid">Paid</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="no_show">No Show</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>

                <div class="col-md-2" id="group_payment_status">
                    <label class="form-label fw-semibold small text-uppercase text-muted">Payment Status</label>
                    <select id="payment_status" class="form-select form-select-sm status-select-scroll" multiple>
                        <option value="all" selected>All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="success">Successful</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>

                <div class="col-md-2" id="group_gateway">
                    <label class="form-label fw-semibold small text-uppercase text-muted">Gateway</label>
                    <select id="gateway" class="form-select form-select-sm status-select-scroll" multiple>
                        <option value="all" selected>All Gateways</option>
                        <option value="sandbox">Sandbox</option>
                        <option value="manual">Manual/Walk-in</option>
                    </select>
                </div>
            </div>

            <div id="reportSummary" class="mt-3 d-flex flex-wrap gap-2 border-top pt-3" style="display:none !important;"></div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive" id="reportTableContainer">
                <div id="reportTable">
                     <div class="p-5 text-center text-muted">
                        <i class="fas fa-chart-line fa-3x mb-3 opacity-25"></i>
                        <p class="mb-0">Select your criteria and click <strong>Update Results</strong>.</p>
                     </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
function showSkeleton() {
    let skeletonHtml = `
        <table class="table mb-0">
            <thead><tr><th colspan="6">Loading Data...</th></tr></thead>
            <tbody>
                ${Array(5).fill('<tr><td colspan="6"><div class="skeleton-row"></div></td></tr>').join('')}
            </tbody>
        </table>`;
    $('#reportTable').html(skeletonHtml);
}
function buildPayload() {

    const filters = {};

    let booking = cleanFilter('#booking_status');
    let payment = cleanFilter('#payment_status');
    let gateway = cleanFilter('#gateway');

    if (booking) filters.booking_status = booking;
    if (payment) filters.payment_status = payment;
    if (gateway) filters.gateway = gateway;

    let dateType = $('#date_type').val();

    let dateRange = {
        type: dateType,
        value: null
    };

    if (dateType === 'monthly') {
        dateRange.value = $('#date_month').val();
    }

    if (dateType === 'yearly') {
        dateRange.value = $('#date_year').val();
    }

    if (dateType === 'range') {
        dateRange.value = {
            from: $('#date_from').val(),
            to: $('#date_to').val()
        };
    }

    return {
        report_type: $('#report_type').val(),
        column_set: $('#column_set').val(),
        date_range: dateRange,
        filters: filters
    };
}


window.generateReport = function(page = 1) {
    let payload = buildPayload();
    renderSummary(payload);
    showSkeleton(); // Trigger loader
    
    axios.post('/staff/reports/generate?page=' + page, payload)
        .then(res => renderTable(res.data))
        .catch(err => {
            console.error(err);
            $('#reportTable').html('<div class="p-4 text-danger">Error loading report. Please try again.</div>');
        });
}

function renderTable(data) {
    let rows = data.data;
    if (!rows.length) {
        renderEmptyState();
        toggleExport(false);
        return;
    }

    toggleExport(true);
    let columns = Object.keys(rows[0]);

    // Color map for the badges
    const statusColors = {
        'paid': 'success',
        'success': 'success',
        'completed': 'success',
        'pending': 'warning',
        'pending_payment': 'warning',
        'cancelled': 'danger',
        'failed': 'danger',
        'no_show': 'secondary',
        'expired': 'secondary'
    };

    let html = '<table class="table table-hover mb-0 border-top">';
    html += '<thead><tr>';
    columns.forEach(col => {
        html += `<th>${col.replace('_', ' ')}</th>`;
    });
    html += '</tr></thead><tbody>';

    // SINGLE LOOP for rows
    rows.forEach(row => {
        html += '<tr>';
        columns.forEach(col => {
            let val = row[col] ?? '-';
            let cleanVal = String(val).toLowerCase().trim();

            // Apply modern badge styling if the value matches a status
            if (statusColors.hasOwnProperty(cleanVal)) {
                let colorClass = statusColors[cleanVal];
                val = `<span class="badge-pill badge-${colorClass}-soft">${val.replace('_', ' ')}</span>`;
            }

            html += `<td>${val}</td>`;
        });
        html += '</tr>';
    });
    
    html += '</tbody></table>';

    // Pagination UI
    let prevDisabled = data.current_page <= 1 ? 'disabled' : '';
    let nextDisabled = data.current_page >= data.last_page ? 'disabled' : '';

    html += `
    <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center">
        <div class="small text-muted">Showing page <strong>${data.current_page}</strong> of ${data.last_page}</div>
        <div class="btn-group">
            <button class="btn btn-outline-primary btn-sm px-3" ${prevDisabled} onclick="generateReport(${data.current_page - 1})">
                <i class="fas fa-chevron-left me-1"></i> Previous
            </button>
            <button class="btn btn-outline-primary btn-sm px-3" ${nextDisabled} onclick="generateReport(${data.current_page + 1})">
                Next <i class="fas fa-chevron-right ms-1"></i>
            </button>
        </div>
    </div>`;

    $('#reportTable').html(html);
}

window.exportReport = function() {

    let payload = buildPayload();

    axios.post('/staff/reports/export', payload, {
        responseType: 'blob'
    }).then(response => {

        let blob = new Blob([response.data]);
        let link = document.createElement('a');

        let disposition = response.headers['content-disposition'];
        let filename = 'report.xlsx';

        if (disposition && disposition.includes('filename=')) {
            let match = disposition.match(/filename="?(.+)"?/);
            if (match.length > 1) {
                filename = match[1];
            }
        }

        link.href = window.URL.createObjectURL(blob);
        link.download = filename;
        link.click();
    });
}


// UI behavior
// UI behavior
$('#report_type').change(function () {
    let type = $(this).val();
    
    // 1. Update Card Accent Color
    $('.card').first().removeClass('card-booking card-payment card-combined').addClass('card-' + type);
    
    // 2. Map report types to column sets
    let map = {
        booking: 'booking_summary',
        payment: 'financial',
        combined: 'combined'
    };
    $('#column_set').val(map[type]);

    // 3. RESET FILTERS (Critical Fix)
    // When switching categories, reset all status selects to "all"
    $('#booking_status, #payment_status, #gateway').val(['all']);

    // 4. Update UI Visibility
    updateFilters(type);
    
    // 5. CLEAR PREVIEWS
    // Clear the table and the summary bar so old data doesn't linger
    $('#reportTable').html(`
        <div class="p-5 text-center text-muted">
            <i class="fas fa-chart-line fa-3x mb-3 opacity-25"></i>
            <p class="mb-0">Select your criteria and click <strong>Update Results</strong>.</p>
        </div>
    `);
    
    $('#reportSummary').hide().html(''); // Completely empty the summary
    toggleExport(false);
});

// date behavior
$('#date_type').change(function () {
    updateDateUI($(this).val());
});


//filter restrictions

function updateFilters(type) {

    if (type === 'booking') {
        $('#group_booking_status').show();
        $('#group_payment_status').hide();
        $('#group_gateway').hide();
    }

    if (type === 'payment') {
        $('#group_booking_status').hide();
        $('#group_payment_status').show();
        $('#group_gateway').show();
    }

    if (type === 'combined') {
        $('#group_booking_status').show();
        $('#group_payment_status').show();
        $('#group_gateway').show();
    }
}

$('#report_type').change(function () {
    updateFilters($(this).val());
});

$(document).ready(function () {

    let currentYear = new Date().getFullYear();
    let startYear = 2025;

    let yearSelect = $('#date_year');

    let type = $('#report_type').val();

    for (let y = currentYear; y >= startYear; y--) {
        yearSelect.append(`<option value="${y}">${y}</option>`);
    }
    
    updateDateUI($('#date_type').val());
    updateFilters(type);
    toggleExport(false);
});

//no data found state
function renderEmptyState() {

    $('#reportTable').html(`
        <div class="alert alert-warning mt-3">
            <strong>No matching records found.</strong><br>
            Try:
            <ul>
                <li>Expanding the date range</li>
                <li>Removing some filters</li>
                <li>Switching report type</li>
            </ul>
        </div>
    `);
}
//filter preview
function renderSummary(payload) {
    // 1. Clear the container and start with the label
    let html = `<span class="small text-muted me-2 align-self-center"><i class="fas fa-filter me-1"></i> ACTIVE:</span>`;
    
    // 2. Report Type Chip
    html += `<span class="filter-badge bg-primary text-white border-0">
                <i class="fas fa-file-alt me-1"></i> ${payload.report_type.toUpperCase()}
             </span>`;
    
    // 3. Date Chip
    let dateVal = typeof payload.date_range.value === 'object' 
        ? `${payload.date_range.value.from} to ${payload.date_range.value.to}`
        : payload.date_range.value;
        
    html += `<span class="filter-badge bg-info text-white border-0">
                <i class="fas fa-calendar-alt me-1"></i> ${dateVal || 'All Time'}
             </span>`;

    // 4. Dynamic Filter Chips (Status/Gateway)
    Object.keys(payload.filters || {}).forEach(key => {
        let values = payload.filters[key];
        
        // Only show the chip if something is selected and it's not just "all"
        if (values && values.length > 0 && !values.includes('all')) {
            // Clean up the key name (e.g., booking_status -> Status)
            let label = key.replace('_status', '').replace('_', ' ');
            
            // Join the selected values (e.g., "paid, completed")
            let selectedText = values.map(v => v.replace('_', ' ')).join(', ');

            html += `
                <span class="filter-badge border-primary text-primary bg-white border">
                    <strong class="text-uppercase" style="font-size: 0.65rem;">${label}:</strong> ${selectedText}
                </span>`;
        }
    });

    // 5. Update the UI - make sure to remove the !important from the display style in HTML
    $('#reportSummary').html(html).removeClass('d-none').addClass('d-flex').show();
}

//disabling of export button when no data
function toggleExport(state) {
    $('#exportBtn').prop('disabled', !state);
}

function cleanFilter(id) {
    let val = $(id).val();

    if (!val || val.includes('all')) {
        return null;
    }

    return val;
}

function updateDateUI(type) {

    if (type === 'monthly') {
        $('#date_month').show();
        $('#date_year').hide();
        $('#date_from, #date_to').hide();
    }

    if (type === 'yearly') {
        $('#date_month').hide();
        $('#date_year').show();
        $('#date_from, #date_to').hide();
    }

    if (type === 'range') {
        $('#date_month').hide();
        $('#date_year').hide();
        $('#date_from, #date_to').show();
    }
}

function clearTable() {
    $('#reportTable').html('');
    $('#reportSummary').hide();
    toggleExport(false);
}

function resetView() {

    // Reset filters to ALL
    $('#booking_status').val(['all']);
    $('#payment_status').val(['all']);
    $('#gateway').val(['all']);

    // Reset date
    $('#date_type').val('monthly');
    $('#date_month').val('');
    $('#date_year').val('');
    $('#date_from').val('');
    $('#date_to').val('');

    updateDateUI('monthly');

    // Reset report type
    $('#report_type').val('booking').trigger('change');

    clearTable();
}
</script>
@endpush
