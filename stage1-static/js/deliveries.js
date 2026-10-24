/**
 * DELIVERY TRACKING MODULE (Stage 1 Static)
 * Delay Days Calculation, Partial Delivery Handling, Status History & Synchronization
 */

document.addEventListener('DOMContentLoaded', () => {
  const tableBody = document.getElementById('deliveries-table-body');
  const searchInput = document.getElementById('delivery-search');
  const statusFilter = document.getElementById('delivery-status-filter');
  const supplierFilter = document.getElementById('delivery-supplier-filter');
  const totalCountEl = document.getElementById('delivery-total-count');

  // Modal elements for updating delivery
  const modal = document.getElementById('delivery-modal');
  const modalTitle = document.getElementById('delivery-modal-title');
  const form = document.getElementById('delivery-form');
  const btnCloseModal = document.getElementById('btn-close-delivery-modal');
  const btnCancelModal = document.getElementById('btn-cancel-delivery-modal');

  // View modal elements
  const viewModal = document.getElementById('view-delivery-modal');
  const btnCloseViewModal = document.getElementById('btn-close-view-delivery-modal');

  let currentEditDelId = null;

  function populateSupplierDropdown() {
    if (!supplierFilter) return;
    const suppliers = DataStore.getSuppliers();
    supplierFilter.innerHTML = '<option value="">All Suppliers</option>' +
      suppliers.map(s => `<option value="${s.id}">${s.company_name} (${s.id})</option>`).join('');
  }

  function renderDeliveries() {
    const deliveries = DataStore.getDeliveries();
    const suppliers = DataStore.getSuppliers();
    const orders = DataStore.getOrders();
    const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
    const selectedStatus = statusFilter ? statusFilter.value : '';
    const selectedSup = supplierFilter ? supplierFilter.value : '';

    const filtered = deliveries.filter(d => {
      const sup = suppliers.find(s => s.id === d.supplier_id);
      const supName = sup ? sup.company_name.toLowerCase() : '';
      const matchSearch = d.id.toLowerCase().includes(query) ||
                          d.order_id.toLowerCase().includes(query) ||
                          supName.includes(query);
      const matchStatus = !selectedStatus || d.status === selectedStatus;
      const matchSup = !selectedSup || d.supplier_id === selectedSup;
      return matchSearch && matchStatus && matchSup;
    });

    if (totalCountEl) {
      totalCountEl.textContent = `${filtered.length} of ${deliveries.length} delivery records`;
    }

    if (!tableBody) return;

    if (filtered.length === 0) {
      tableBody.innerHTML = `
        <tr>
          <td colspan="8" style="text-align:center; padding:3rem 1rem; color:var(--slate-500);">
            <div style="font-size:1.5rem; margin-bottom:0.5rem;">🚚</div>
            <p style="font-weight:600;">No delivery records match your criteria</p>
            <p style="font-size:0.8rem;">Try clearing search filters.</p>
          </td>
        </tr>
      `;
      return;
    }

    tableBody.innerHTML = filtered.map(d => {
      const sup = suppliers.find(s => s.id === d.supplier_id) || { company_name: 'Unknown' };
      const delayInfo = CalculationEngine.calculateDelayDays(d.expected_date, d.actual_date, d.status);
      const isLate = delayInfo.isLate;

      // Quantity fulfillment percentage
      const qtyPct = d.ordered_qty > 0 ? Math.min(100, Math.round((d.received_qty / d.ordered_qty) * 100)) : 0;

      return `
        <tr class="${isLate ? 'row-delayed' : ''}">
          <td class="cell-code">${d.id}</td>
          <td>
            <div class="cell-code" style="color:var(--primary-600);">${d.order_id}</div>
            <div class="cell-muted">${sup.company_name}</div>
          </td>
          <td>
            <div>Exp: ${UI.formatDate(d.expected_date)}</div>
            <div class="cell-muted">Act: ${d.actual_date ? UI.formatDate(d.actual_date) : '<span style="color:var(--slate-400);">Not Received</span>'}</div>
          </td>
          <td>
            <div style="display:flex; justify-content:space-between; font-size:0.8rem; margin-bottom:0.2rem;">
              <span>${Number(d.received_qty).toLocaleString()} / ${Number(d.ordered_qty).toLocaleString()}</span>
              <span style="font-weight:600;">${qtyPct}%</span>
            </div>
            <div class="progress-container" style="height:6px;">
              <div class="progress-bar ${qtyPct === 100 ? 'progress-success' : qtyPct > 0 ? 'progress-warning' : 'progress-primary'}" style="width:${qtyPct}%;"></div>
            </div>
          </td>
          <td>
            ${isLate ? `
              <span style="color:var(--danger-600); font-weight:600; display:inline-flex; align-items:center; gap:0.25rem;">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                +${delayInfo.delay}d ${delayInfo.isOverdue ? 'overdue' : 'late'}
              </span>
            ` : delayInfo.earlyDays ? `
              <span style="color:var(--success-600); font-weight:600;">
                ${delayInfo.earlyDays}d early
              </span>
            ` : d.status === 'Cancelled' ? `
              <span class="cell-muted">—</span>
            ` : `
              <span style="color:var(--success-600); font-weight:600;">On Schedule</span>
            `}
          </td>
          <td>${UI.getStatusBadge(d.status)}</td>
          <td style="max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="${d.remarks}">
            ${d.remarks || '—'}
          </td>
          <td>
            <div style="display:flex; gap:0.4rem;">
              <button class="btn btn-icon btn-sm" title="View Details & History" onclick="viewDelivery('${d.id}')">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              </button>
              ${!DataStore.isReadOnly() && d.status !== 'Cancelled' ? `
                <button class="btn btn-icon btn-sm" title="Update Delivery Status" onclick="openUpdateDeliveryModal('${d.id}')">
                  <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
              ` : DataStore.isReadOnly() ? `
                <span class="badge" style="background:var(--slate-100); color:var(--slate-500); font-size:0.7rem;">Read-Only</span>
              ` : ''}
            </div>
          </td>
        </tr>
      `;
    }).join('');
  }

  // Open Update Delivery Modal
  window.openUpdateDeliveryModal = function(id) {
    if (DataStore.isReadOnly()) {
      DataStore.logActivity('UPDATE_DELIVERY_ATTEMPT', id, 'Action blocked: Auditor role cannot modify delivery receipts.', 'BLOCKED');
      alert('Access Restricted (RBAC Violation):\n\nQuality & Compliance Auditors have read-only privileges. Delivery receipt updates can only be executed by Purchasing Officers or System Administrators.');
      return;
    }

    const delivery = DataStore.getDeliveries().find(d => d.id === id);
    if (!delivery) return;

    currentEditDelId = id;
    modalTitle.textContent = `Update Delivery Progress: ${delivery.id} (${delivery.order_id})`;

    document.getElementById('del-id').value = delivery.id;
    document.getElementById('del-order-id').value = delivery.order_id;
    document.getElementById('del-expected-date').value = delivery.expected_date;
    document.getElementById('del-ordered-qty').value = delivery.ordered_qty;
    
    // Default actual date to today if empty
    document.getElementById('del-actual-date').value = delivery.actual_date || new Date().toISOString().split('T')[0];
    document.getElementById('del-received-qty').value = delivery.received_qty;
    document.getElementById('del-status').value = delivery.status;
    document.getElementById('del-remarks').value = delivery.remarks || '';

    modal.classList.add('active');
  };

  // Close modals
  [btnCloseModal, btnCancelModal].forEach(b => {
    if (b) b.addEventListener('click', () => modal.classList.remove('active'));
  });

  if (btnCloseViewModal) {
    btnCloseViewModal.addEventListener('click', () => viewModal.classList.remove('active'));
  }

  // Handle Update Form Submission with Business Logic Checks
  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();

      const deliveries = DataStore.getDeliveries();
      const orders = DataStore.getOrders();
      const delIndex = deliveries.findIndex(d => d.id === currentEditDelId);
      if (delIndex === -1) return;

      const currentDel = deliveries[delIndex];
      const actual_date = document.getElementById('del-actual-date').value;
      const received_qty = parseFloat(document.getElementById('del-received-qty').value);
      const chosenStatus = document.getElementById('del-status').value;
      const remarks = document.getElementById('del-remarks').value.trim();

      // Validation 1: Prevent negative received quantity
      if (isNaN(received_qty) || received_qty < 0) {
        UI.showToast('Received quantity cannot be negative.', 'danger');
        return;
      }

      // Validation 2: Prevent received quantity exceeding ordered quantity
      if (received_qty > currentDel.ordered_qty) {
        UI.showToast(`Received quantity (${received_qty}) cannot exceed ordered quantity (${currentDel.ordered_qty}) without special over-delivery authorization.`, 'danger');
        return;
      }

      // Validation 3: Logical consistency between received quantity and status
      let finalStatus = chosenStatus;
      if (received_qty === currentDel.ordered_qty && received_qty > 0) {
        finalStatus = 'Delivered';
      } else if (received_qty > 0 && received_qty < currentDel.ordered_qty) {
        finalStatus = 'Partially Delivered';
      } else if (received_qty === 0 && chosenStatus === 'Delivered') {
        UI.showToast('Cannot mark order as "Delivered" when received quantity is 0.', 'danger');
        return;
      }

      // Date check: If marked Delivered, actual delivery date must be provided
      if ((finalStatus === 'Delivered' || finalStatus === 'Partially Delivered') && !actual_date) {
        UI.showToast('Please provide the actual delivery receipt date.', 'danger');
        return;
      }

      const previousStatus = currentDel.status;

      // Update delivery record
      deliveries[delIndex] = {
        ...currentDel,
        actual_date: actual_date || null,
        received_qty,
        status: finalStatus,
        remarks
      };
      DataStore.saveDeliveries(deliveries);

      // Synchronize linked Purchase Order status
      const orderIndex = orders.findIndex(o => o.id === currentDel.order_id);
      if (orderIndex !== -1) {
        if (finalStatus === 'Delivered') {
          orders[orderIndex].status = 'Completed';
        } else if (finalStatus === 'Partially Delivered') {
          orders[orderIndex].status = 'Partially Delivered';
        } else if (finalStatus === 'Delayed') {
          orders[orderIndex].status = 'Delayed';
        } else if (finalStatus === 'Cancelled') {
          orders[orderIndex].status = 'Cancelled';
        }
        DataStore.saveOrders(orders);
      }

      // Append Audit History Log if status or quantities changed
      DataStore.addHistory({
        delivery_id: currentDel.id,
        previous_status: previousStatus,
        new_status: finalStatus,
        notes: `Received qty updated to ${received_qty}/${currentDel.ordered_qty}. Status: ${finalStatus}. Remarks: ${remarks || 'None'}`
      });

      DataStore.logActivity('UPDATE_DELIVERY', currentDel.id, `Recorded goods receipt for ${currentDel.order_id}: ${received_qty}/${currentDel.ordered_qty} units received (Status: ${finalStatus}).`, 'PERMITTED');

      UI.showToast(`Delivery ${currentDel.id} updated successfully.`, 'success');
      modal.classList.remove('active');
      renderDeliveries();
    });
  }

  // View Delivery and its complete change history
  window.viewDelivery = function(id) {
    const delivery = DataStore.getDeliveries().find(d => d.id === id);
    if (!delivery) return;

    const sup = DataStore.getSuppliers().find(s => s.id === delivery.supplier_id) || { company_name: 'Unknown' };
    const order = DataStore.getOrders().find(o => o.id === delivery.order_id) || {};
    const delayInfo = CalculationEngine.calculateDelayDays(delivery.expected_date, delivery.actual_date, delivery.status);

    document.getElementById('view-del-id').textContent = delivery.id;
    document.getElementById('view-del-po').textContent = delivery.order_id;
    document.getElementById('view-del-supplier').textContent = `${sup.company_name} (${delivery.supplier_id})`;
    document.getElementById('view-del-status').innerHTML = UI.getStatusBadge(delivery.status);
    document.getElementById('view-del-exp').textContent = UI.formatDate(delivery.expected_date);
    document.getElementById('view-del-act').textContent = delivery.actual_date ? UI.formatDate(delivery.actual_date) : 'Pending Delivery';
    document.getElementById('view-del-qty').textContent = `${delivery.received_qty} / ${delivery.ordered_qty} units`;
    document.getElementById('view-del-delay').innerHTML = delayInfo.isLate 
      ? `<span style="color:var(--danger-600); font-weight:700;">+${delayInfo.delay} Days (${delayInfo.isOverdue ? 'Overdue' : 'Late'})</span>`
      : delayInfo.earlyDays
      ? `<span style="color:var(--success-600); font-weight:700;">${delayInfo.earlyDays} Days Early (On Time)</span>`
      : `<span style="color:var(--success-600); font-weight:700;">On Schedule</span>`;
    document.getElementById('view-del-remarks').textContent = delivery.remarks || 'No remarks recorded.';

    // Populate Status History Table
    const allHistory = DataStore.getHistory().filter(h => h.delivery_id === id);
    const historyBody = document.getElementById('view-del-history-body');
    if (historyBody) {
      if (allHistory.length === 0) {
        historyBody.innerHTML = `<tr><td colspan="4" style="text-align:center; color:var(--slate-500); padding:1rem;">Initial creation record only.</td></tr>`;
      } else {
        historyBody.innerHTML = allHistory.map(h => `
          <tr>
            <td class="cell-muted" style="white-space:nowrap;">${h.change_date}</td>
            <td>${UI.getStatusBadge(h.previous_status)}</td>
            <td>${UI.getStatusBadge(h.new_status)}</td>
            <td style="font-size:0.8rem;">${h.notes}</td>
          </tr>
        `).join('');
      }
    }

    viewModal.classList.add('active');
  };

  // Filter listeners
  if (searchInput) searchInput.addEventListener('input', renderDeliveries);
  if (statusFilter) statusFilter.addEventListener('change', renderDeliveries);
  if (supplierFilter) supplierFilter.addEventListener('change', renderDeliveries);

  populateSupplierDropdown();
  renderDeliveries();
});
