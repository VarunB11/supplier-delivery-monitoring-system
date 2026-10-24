/**
 * REPORTS AND ANALYTICS MODULE (Stage 1 Static)
 * Comprehensive Filtering, Summary KPIs, Print Engine & Safe CSV Export
 */

document.addEventListener('DOMContentLoaded', () => {
  const reportTypeSelect = document.getElementById('report-type');
  const supplierSelect = document.getElementById('report-supplier');
  const statusSelect = document.getElementById('report-status');
  const startDateInput = document.getElementById('report-start-date');
  const endDateInput = document.getElementById('report-end-date');
  const btnFilter = document.getElementById('btn-apply-report-filters');
  const btnReset = document.getElementById('btn-reset-report-filters');
  const btnPrint = document.getElementById('btn-print-report');
  const btnExport = document.getElementById('btn-export-csv');

  const reportTableHead = document.getElementById('report-table-head');
  const reportTableBody = document.getElementById('report-table-body');
  const summaryCountEl = document.getElementById('report-summary-count');
  const summaryValueEl = document.getElementById('report-summary-value');
  const summaryDelayedEl = document.getElementById('report-summary-delayed');
  const summaryOntimeEl = document.getElementById('report-summary-ontime');

  let currentExportData = [];

  function populateSuppliers() {
    if (!supplierSelect) return;
    const suppliers = DataStore.getSuppliers();
    supplierSelect.innerHTML = '<option value="">All Suppliers</option>' +
      suppliers.map(s => `<option value="${s.id}">${s.company_name} (${s.id})</option>`).join('');
  }

  function generateReport() {
    const type = reportTypeSelect ? reportTypeSelect.value : 'orders';
    const supplierId = supplierSelect ? supplierSelect.value : '';
    const status = statusSelect ? statusSelect.value : '';
    const startDate = startDateInput ? startDateInput.value : '';
    const endDate = endDateInput ? endDateInput.value : '';

    const orders = DataStore.getOrders();
    const deliveries = DataStore.getDeliveries();
    const suppliers = DataStore.getSuppliers();

    let rows = [];
    let headers = [];
    let totalVal = 0;
    let delayedCount = 0;
    let onTimeCount = 0;

    if (type === 'orders') {
      headers = ['PO Number', 'Supplier Name', 'Order Date', 'Expected Date', 'Description', 'Quantity', 'Total Amount', 'Status'];

      const filteredOrders = orders.filter(o => {
        const matchSup = !supplierId || o.supplier_id === supplierId;
        const matchStatus = !status || o.status === status;
        const matchStart = !startDate || o.order_date >= startDate;
        const matchEnd = !endDate || o.order_date <= endDate;
        return matchSup && matchStatus && matchStart && matchEnd;
      });

      rows = filteredOrders.map(o => {
        const sup = suppliers.find(s => s.id === o.supplier_id) || { company_name: 'Unknown' };
        totalVal += Number(o.total_amount || 0);
        if (o.status === 'Delayed') delayedCount++;
        if (o.status === 'Completed') onTimeCount++;

        return {
          cols: [
            `<span class="cell-code">${o.id}</span>`,
            `<div class="cell-strong">${sup.company_name}</div><div class="cell-muted">${o.supplier_id}</div>`,
            UI.formatDate(o.order_date),
            UI.formatDate(o.expected_date),
            `<span title="${o.description}">${o.description.substring(0, 35)}...</span>`,
            Number(o.quantity).toLocaleString(),
            `<span class="cell-strong">${UI.formatCurrency(o.total_amount)}</span>`,
            UI.getStatusBadge(o.status)
          ],
          raw: [o.id, sup.company_name, o.order_date, o.expected_date, o.description, o.quantity, o.total_amount, o.status]
        };
      });

    } else if (type === 'deliveries') {
      headers = ['Delivery ID', 'PO Number', 'Supplier', 'Expected Date', 'Actual Date', 'Received / Ordered', 'Delay Status', 'Delivery Status'];

      const filteredDeliveries = deliveries.filter(d => {
        const matchSup = !supplierId || d.supplier_id === supplierId;
        const matchStatus = !status || d.status === status;
        const matchStart = !startDate || d.expected_date >= startDate;
        const matchEnd = !endDate || d.expected_date <= endDate;
        return matchSup && matchStatus && matchStart && matchEnd;
      });

      rows = filteredDeliveries.map(d => {
        const sup = suppliers.find(s => s.id === d.supplier_id) || { company_name: 'Unknown' };
        const delayInfo = CalculationEngine.calculateDelayDays(d.expected_date, d.actual_date, d.status);
        if (delayInfo.isLate) delayedCount++;
        if (d.status === 'Delivered' && !delayInfo.isLate) onTimeCount++;

        const linkedPO = orders.find(o => o.id === d.order_id);
        if (linkedPO) totalVal += Number(linkedPO.total_amount || 0);

        return {
          cols: [
            `<span class="cell-code">${d.id}</span>`,
            `<span class="cell-code" style="color:var(--primary-600);">${d.order_id}</span>`,
            `<div class="cell-strong">${sup.company_name}</div>`,
            UI.formatDate(d.expected_date),
            d.actual_date ? UI.formatDate(d.actual_date) : 'Pending',
            `${d.received_qty} / ${d.ordered_qty}`,
            delayInfo.isLate ? `<span style="color:var(--danger-600); font-weight:700;">+${delayInfo.delay}d late</span>` : `<span style="color:var(--success-600);">On Schedule</span>`,
            UI.getStatusBadge(d.status)
          ],
          raw: [d.id, d.order_id, sup.company_name, d.expected_date, d.actual_date || 'Pending', `${d.received_qty}/${d.ordered_qty}`, delayInfo.text, d.status]
        };
      });

    } else if (type === 'delayed') {
      headers = ['PO Number', 'Delivery ID', 'Supplier', 'Expected Date', 'Days Overdue/Late', 'Ordered Qty', 'Status', 'Remarks'];

      const delayedDeliveries = deliveries.filter(d => {
        const delayInfo = CalculationEngine.calculateDelayDays(d.expected_date, d.actual_date, d.status);
        const isDelayed = delayInfo.isLate || d.status === 'Delayed';
        const matchSup = !supplierId || d.supplier_id === supplierId;
        return isDelayed && matchSup;
      });

      rows = delayedDeliveries.map(d => {
        const sup = suppliers.find(s => s.id === d.supplier_id) || { company_name: 'Unknown' };
        const delayInfo = CalculationEngine.calculateDelayDays(d.expected_date, d.actual_date, d.status);
        delayedCount++;

        const linkedPO = orders.find(o => o.id === d.order_id);
        if (linkedPO) totalVal += Number(linkedPO.total_amount || 0);

        return {
          cols: [
            `<span class="cell-code">${d.order_id}</span>`,
            `<span class="cell-code">${d.id}</span>`,
            `<div class="cell-strong">${sup.company_name}</div>`,
            UI.formatDate(d.expected_date),
            `<span style="color:var(--danger-600); font-weight:700;">+${delayInfo.delay} Days</span>`,
            `${d.ordered_qty}`,
            UI.getStatusBadge(d.status),
            `<span title="${d.remarks}">${d.remarks || '—'}</span>`
          ],
          raw: [d.order_id, d.id, sup.company_name, d.expected_date, delayInfo.delay, d.ordered_qty, d.status, d.remarks]
        };
      });

    } else if (type === 'performance') {
      headers = ['Supplier ID', 'Company Name', 'Category', 'Eligible Completed', 'On-Time Deliveries', 'On-Time %', 'Avg Delay', 'Score', 'Rating'];

      const filteredSuppliers = suppliers.filter(s => {
        const matchSup = !supplierId || s.id === supplierId;
        const matchStatus = !status || s.status === status;
        return matchSup && matchStatus;
      });

      rows = filteredSuppliers.map(s => {
        const m = CalculationEngine.getSupplierMetrics(s.id);
        if (m.lateCount > 0) delayedCount += m.lateCount;
        onTimeCount += m.onTimeCount;

        return {
          cols: [
            `<span class="cell-code">${s.id}</span>`,
            `<div class="cell-strong">${s.company_name}</div>`,
            s.category,
            `${m.totalEligible}`,
            `${m.onTimeCount}`,
            `${m.onTimePercentage}%`,
            `${m.avgDelayDays} d`,
            `<span style="font-weight:800; font-size:1.05rem;">${m.overallScore !== null ? m.overallScore : '—'}</span>`,
            UI.getRatingBadge(m.rating, m.ratingClass)
          ],
          raw: [s.id, s.company_name, s.category, m.totalEligible, m.onTimeCount, m.onTimePercentage + '%', m.avgDelayDays, m.overallScore || 'N/A', m.rating]
        };
      });
    }

    // Update summary metrics
    if (summaryCountEl) summaryCountEl.textContent = rows.length;
    if (summaryValueEl) summaryValueEl.textContent = UI.formatCurrency(totalVal);
    if (summaryDelayedEl) summaryDelayedEl.textContent = delayedCount;
    if (summaryOntimeEl) summaryOntimeEl.textContent = onTimeCount;

    // Render Table
    if (reportTableHead) {
      reportTableHead.innerHTML = `<tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr>`;
    }

    if (reportTableBody) {
      if (rows.length === 0) {
        reportTableBody.innerHTML = `
          <tr>
            <td colspan="${headers.length}" style="text-align:center; padding:3rem; color:var(--slate-500);">
              No records found for the selected report filters.
            </td>
          </tr>
        `;
      } else {
        reportTableBody.innerHTML = rows.map(r => `<tr>${r.cols.map(c => `<td>${c}</td>`).join('')}</tr>`).join('');
      }
    }

    // Store raw for CSV export
    currentExportData = {
      title: type.toUpperCase() + '_REPORT',
      headers,
      rows: rows.map(r => r.raw)
    };
  }

  // Safe CSV Exporter with proper escaping
  function exportCSV() {
    if (!currentExportData || !currentExportData.rows || currentExportData.rows.length === 0) {
      UI.showToast('No data available to export.', 'warning');
      return;
    }

    const escapeCSV = (str) => {
      if (str === null || str === undefined) return '""';
      const s = String(str).replace(/"/g, '""');
      return `"${s}"`;
    };

    let csvContent = '\uFEFF'; // UTF-8 BOM
    csvContent += currentExportData.headers.map(escapeCSV).join(',') + '\r\n';

    currentExportData.rows.forEach(row => {
      csvContent += row.map(escapeCSV).join(',') + '\r\n';
    });

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    const dateStr = new Date().toISOString().split('T')[0];
    link.setAttribute('download', `${currentExportData.title}_${dateStr}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);

    UI.showToast('Report CSV successfully downloaded.', 'success');
  }

  // Setup Event Listeners
  if (btnFilter) btnFilter.addEventListener('click', generateReport);
  if (reportTypeSelect) reportTypeSelect.addEventListener('change', generateReport);
  if (btnReset) {
    btnReset.addEventListener('click', () => {
      if (reportTypeSelect) reportTypeSelect.value = 'orders';
      if (supplierSelect) supplierSelect.value = '';
      if (statusSelect) statusSelect.value = '';
      if (startDateInput) startDateInput.value = '';
      if (endDateInput) endDateInput.value = '';
      generateReport();
    });
  }
  if (btnPrint) {
    btnPrint.addEventListener('click', () => {
      window.print();
    });
  }
  if (btnExport) btnExport.addEventListener('click', exportCSV);

  populateSuppliers();
  generateReport();
});
