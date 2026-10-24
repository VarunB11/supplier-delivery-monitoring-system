/**
 * SUPPLIER PERFORMANCE EVALUATION MODULE (Stage 1 Static)
 * Mathematical Formulation, Reliability Metrics, Ratings & Comparative Charts
 */

document.addEventListener('DOMContentLoaded', () => {
  const tableBody = document.getElementById('performance-table-body');
  const searchInput = document.getElementById('perf-search');
  const categoryFilter = document.getElementById('perf-category-filter');
  const ratingFilter = document.getElementById('perf-rating-filter');
  let performanceChart = null;

  function populateCategoryDropdown() {
    if (!categoryFilter) return;
    const suppliers = DataStore.getSuppliers();
    const categories = [...new Set(suppliers.map(s => s.category))].sort();
    categoryFilter.innerHTML = '<option value="">All Categories</option>' +
      categories.map(c => `<option value="${c}">${c}</option>`).join('');
  }

  function renderPerformance() {
    const suppliers = DataStore.getSuppliers();
    const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
    const selectedCat = categoryFilter ? categoryFilter.value : '';
    const selectedRating = ratingFilter ? ratingFilter.value : '';

    const metricsList = suppliers.map(s => {
      const metrics = CalculationEngine.getSupplierMetrics(s.id);
      return {
        supplier: s,
        metrics
      };
    });

    const filtered = metricsList.filter(item => {
      const s = item.supplier;
      const m = item.metrics;
      const matchSearch = s.company_name.toLowerCase().includes(query) ||
                          s.id.toLowerCase().includes(query) ||
                          s.category.toLowerCase().includes(query);
      const matchCat = !selectedCat || s.category === selectedCat;
      const matchRating = !selectedRating || m.rating === selectedRating;
      return matchSearch && matchCat && matchRating;
    });

    if (tableBody) {
      if (filtered.length === 0) {
        tableBody.innerHTML = `
          <tr>
            <td colspan="10" style="text-align:center; padding:3rem 1rem; color:var(--slate-500);">
              <p style="font-weight:600;">No supplier performance records match your criteria.</p>
            </td>
          </tr>
        `;
      } else {
        tableBody.innerHTML = filtered.map(item => {
          const s = item.supplier;
          const m = item.metrics;

          return `
            <tr>
              <td>
                <div class="cell-strong">${s.company_name}</div>
                <div class="cell-code" style="font-size:0.75rem;">${s.id} &bull; ${s.category}</div>
              </td>
              <td style="text-align:center;">
                <span style="font-weight:700;">${m.totalEligible}</span>
                <div class="cell-muted" style="font-size:0.7rem;">of ${m.totalOrders} POs</div>
              </td>
              <td style="text-align:center; color:var(--success-600); font-weight:700;">
                ${m.onTimeCount}
              </td>
              <td style="text-align:center; color:${m.lateCount > 0 ? 'var(--danger-600)' : 'var(--slate-600)'}; font-weight:700;">
                ${m.lateCount}
              </td>
              <td>
                ${m.onTimePercentageRaw !== null ? `
                  <div style="display:flex; justify-content:space-between; font-size:0.8rem; margin-bottom:0.25rem;">
                    <span style="font-weight:700;">${m.onTimePercentage}%</span>
                  </div>
                  <div class="progress-container">
                    <div class="progress-bar ${m.onTimePercentageRaw >= 80 ? 'progress-success' : m.onTimePercentageRaw >= 60 ? 'progress-warning' : 'progress-danger'}" 
                         style="width:${Math.max(5, m.onTimePercentageRaw)}%;"></div>
                  </div>
                ` : `
                  <span class="cell-muted">N/A (0 deliveries)</span>
                `}
              </td>
              <td style="text-align:center; font-weight:600; color:${parseFloat(m.avgDelayDays) > 0 ? 'var(--danger-600)' : 'var(--success-600)'};">
                ${m.avgDelayDays} d
              </td>
              <td>
                <div style="display:flex; justify-content:space-between; font-size:0.8rem; margin-bottom:0.25rem;">
                  <span>${m.qtyFulfilmentPercentage}%</span>
                </div>
                <div class="progress-container">
                  <div class="progress-bar ${parseFloat(m.qtyFulfilmentPercentage) >= 95 ? 'progress-success' : 'progress-warning'}" 
                       style="width:${Math.min(100, parseFloat(m.qtyFulfilmentPercentage))}%;"></div>
                </div>
              </td>
              <td style="text-align:center;">
                ${m.overallScore !== null ? `
                  <span style="font-size:1.15rem; font-weight:800; color:var(--navy-900);">${m.overallScore}</span>
                  <span style="font-size:0.7rem; color:var(--slate-400);">/100</span>
                ` : `
                  <span class="cell-muted">—</span>
                `}
              </td>
              <td>
                ${UI.getRatingBadge(m.rating, m.ratingClass)}
              </td>
              <td>
                <button class="btn btn-outline btn-sm" onclick="showEvaluationDetails('${s.id}')">
                  Formula
                </button>
              </td>
            </tr>
          `;
        }).join('');
      }
    }

    renderChart(filtered);
  }

  function renderChart(metricsList) {
    const canvas = document.getElementById('chart-supplier-comparison');
    if (!canvas || !window.Chart) return;

    const ratedSuppliers = metricsList.filter(item => item.metrics.totalEligible > 0);
    const labels = ratedSuppliers.map(item => item.supplier.company_name.split(' ')[0]);
    const onTimeData = ratedSuppliers.map(item => item.metrics.onTimePercentageRaw);
    const scoreData = ratedSuppliers.map(item => item.metrics.overallScore);

    if (performanceChart) {
      performanceChart.destroy();
    }

    const ctx = canvas.getContext('2d');
    performanceChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels,
        datasets: [
          {
            label: 'On-Time Delivery Rate (%)',
            data: onTimeData,
            backgroundColor: 'rgba(37, 99, 235, 0.85)',
            borderColor: '#1d4ed8',
            borderWidth: 1,
            borderRadius: 4
          },
          {
            label: 'Weighted Composite Score (/100)',
            data: scoreData,
            backgroundColor: 'rgba(16, 185, 129, 0.85)',
            borderColor: '#059669',
            borderWidth: 1,
            borderRadius: 4
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            max: 100,
            ticks: {
              callback: val => `${val}%`
            },
            grid: {
              color: '#f1f5f9'
            }
          },
          x: {
            grid: {
              display: false
            }
          }
        },
        plugins: {
          legend: {
            position: 'top',
            labels: {
              font: { family: 'Inter', weight: 600, size: 12 }
            }
          },
          tooltip: {
            callbacks: {
              label: ctx => `${ctx.dataset.label}: ${ctx.raw}%`
            }
          }
        }
      }
    });
  }

  // Formula Breakdown Modal Trigger
  window.showEvaluationDetails = function(supplierId) {
    const s = DataStore.getSuppliers().find(item => item.id === supplierId);
    if (!s) return;
    const m = CalculationEngine.getSupplierMetrics(supplierId);

    const modal = document.getElementById('formula-modal');
    if (!modal) return;

    document.getElementById('modal-formula-sup-name').textContent = `${s.company_name} (${s.id})`;
    document.getElementById('formula-on-time-math').textContent = m.totalEligible > 0
      ? `(${m.onTimeCount} on-time deliveries / ${m.totalEligible} eligible completed deliveries) × 100 = ${m.onTimePercentage}%`
      : 'No completed deliveries recorded yet (0 / 0 = N/A).';

    document.getElementById('formula-delay-math').textContent = m.totalEligible > 0
      ? `Sum of late days for completed deliveries / ${m.totalEligible} eligible deliveries = ${m.avgDelayDays} days average`
      : '0 days (No eligible deliveries).';

    document.getElementById('formula-qty-math').textContent = 
      `(${m.totalReceivedQty} received units / ${m.totalOrderedQty} ordered units) × 100 = ${m.qtyFulfilmentPercentage}%`;

    document.getElementById('formula-composite-math').textContent = m.totalEligible > 0
      ? `(0.70 × ${m.onTimePercentage}%) + (0.30 × ${m.qtyFulfilmentPercentage}%) = ${m.overallScore} / 100 → Rating: ${m.rating}`
      : 'Unrated (Insufficient completed delivery volume for evaluation).';

    modal.classList.add('active');
  };

  const btnCloseFormula = document.getElementById('btn-close-formula-modal');
  if (btnCloseFormula) {
    btnCloseFormula.addEventListener('click', () => {
      document.getElementById('formula-modal').classList.remove('active');
    });
  }

  if (searchInput) searchInput.addEventListener('input', renderPerformance);
  if (categoryFilter) categoryFilter.addEventListener('change', renderPerformance);
  if (ratingFilter) ratingFilter.addEventListener('change', renderPerformance);

  populateCategoryDropdown();
  renderPerformance();
});
