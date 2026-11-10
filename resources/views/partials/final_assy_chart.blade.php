<!-- Final Assy Bar Chart (Stacked PCS per Jenis Defect) -->
<div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4 pb-3 border-b border-gray-100">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-black text-gray-900 tracking-tight uppercase">Grafik Defect Final Assy (PCS)</h2>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                    Sumbu Y = SUM(Quantity)
                </span>
            </div>
            <p class="text-xs text-gray-500 mt-0.5">
                Distribusi kuantitas defect (Top 15 Pareto + Lainnya) stacked per kategori: Internal, Assembly, dan Belum Terklasifikasi. Rework dikecualikan.
            </p>
        </div>
        <div id="chartTotalSummary" class="text-right text-xs font-semibold text-gray-600 hidden sm:block">
            <!-- Diisi secara dinamis oleh JavaScript -->
        </div>
    </div>

    <!-- Chart Container -->
    <div class="relative w-full h-80">
        <canvas id="finalAssyBarChart"></canvas>
        <div id="finalAssyChartEmpty" class="hidden absolute inset-0 flex flex-col items-center justify-center bg-white/90">
            <svg class="w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <p class="text-xs font-semibold text-gray-500">Tidak ada data defect untuk filter ini.</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let finalAssyChartInstance = null;

    function renderFinalAssyChart(labels, datasets) {
        const canvas = document.getElementById('finalAssyBarChart');
        if (!canvas) return;

        if (typeof Chart === 'undefined') {
            console.warn('[FinalAssyChart] Chart.js belum tersedia.');
            return;
        }

        const existing = Chart.getChart(canvas);
        if (existing) {
            existing.destroy();
        }

        const ctx = canvas.getContext('2d');
        finalAssyChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 400
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 7,
                            boxHeight: 7,
                            padding: 14,
                            font: {
                                size: 11,
                                weight: '600',
                                family: 'ui-sans-serif, system-ui, sans-serif'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(17, 24, 39, 0.95)',
                        titleColor: '#ffffff',
                        titleFont: { size: 12, weight: 'bold' },
                        bodyColor: '#e5e7eb',
                        bodyFont: { size: 11 },
                        footerColor: '#fbbf24',
                        footerFont: { size: 11, weight: 'bold' },
                        padding: 10,
                        cornerRadius: 6,
                        boxPadding: 4,
                        callbacks: {
                            label: function (context) {
                                const dataset = context.dataset;
                                const pcs = context.parsed.y ?? 0;
                                const counts = dataset.counts || [];
                                const count = counts[context.dataIndex] ?? 0;
                                return ` ${dataset.label}: ${pcs} PCS (${count} temuan)`;
                            },
                            footer: function (tooltipItems) {
                                let totalPcs = 0;
                                let totalCount = 0;
                                tooltipItems.forEach(function (item) {
                                    totalPcs += (item.parsed.y || 0);
                                    const counts = item.dataset.counts || [];
                                    totalCount += (counts[item.dataIndex] || 0);
                                });
                                return `Total: ${totalPcs} PCS (${totalCount} temuan)`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10,
                                weight: '600'
                            },
                            maxRotation: 45,
                            minRotation: 0,
                            autoSkip: false
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(243, 244, 246, 1)'
                        },
                        title: {
                            display: true,
                            text: 'Kuantitas Defect (PCS)',
                            font: {
                                size: 11,
                                weight: 'bold'
                            },
                            color: '#4b5563'
                        },
                        ticks: {
                            precision: 0,
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });
    }

    let isFetchingChart = false;

    window.fetchFinalAssyChartData = function (onComplete) {
        if (isFetchingChart) return;
        isFetchingChart = true;

        const query = window.location.search;
        const url = '{{ route("final_assy.chart_data") }}' + query;

        fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Gagal mengambil data chart: HTTP ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            const emptyEl = document.getElementById('finalAssyChartEmpty');
            const summaryEl = document.getElementById('chartTotalSummary');

            if (!data.labels || data.labels.length === 0) {
                if (emptyEl) emptyEl.classList.remove('hidden');
                if (summaryEl) summaryEl.innerHTML = '';
                renderFinalAssyChart([], []);
                return;
            }

            if (emptyEl) emptyEl.classList.add('hidden');
            if (summaryEl && data.summary) {
                summaryEl.innerHTML = `<span class="text-gray-900 font-black font-mono">${data.summary.total_pcs} PCS</span> <span class="text-gray-400 font-normal">(${data.summary.total_count} temuan)</span>`;
            }

            renderFinalAssyChart(data.labels, data.datasets);
        })
        .catch(err => {
            console.error('[FinalAssyChart] Fetch error:', err);
        })
        .finally(() => {
            isFetchingChart = false;
            if (typeof onComplete === 'function') onComplete();
        });
    };

    // Panggil saat halaman pertama kali siap
    window.fetchFinalAssyChartData();

    // Polling interval 30 detik khusus untuk chart, jeda saat tab di background (document.hidden)
    const chartPollTimer = setInterval(function () {
        if (document.hidden) {
            return; // Jeda polling saat tab tidak terlihat
        }
        window.fetchFinalAssyChartData();
    }, 30000);

    // Saat tab kembali aktif terlihat, perbarui data chart
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            window.fetchFinalAssyChartData();
        }
    });

    window.addEventListener('beforeunload', function () {
        if (chartPollTimer) clearInterval(chartPollTimer);
    });
});
</script>

