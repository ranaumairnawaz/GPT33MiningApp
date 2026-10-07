<?php
// Fetch stats
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0")->fetchColumn();
$pending_deposits = $pdo->query("SELECT COUNT(*) FROM transactions WHERE type = 'deposit' AND status = 'pending'")->fetchColumn();
$pending_withdrawals = $pdo->query("SELECT COUNT(*) FROM transactions WHERE type = 'withdrawal' AND status = 'pending'")->fetchColumn();
$total_invested = $pdo->query("SELECT SUM(invested_amount) FROM user_investments WHERE status = 'active'")->fetchColumn() ?: 0;
?>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="text-muted small fw-bold text-uppercase letter-spacing-1">Network Assets</div>
                    <div class="icon-shape bg-primary bg-opacity-10 text-primary rounded-circle p-2" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-users-viewfinder"></i>
                    </div>
                </div>
                <h2 class="fw-800 mb-1 text-dark"><?php echo number_format($total_users); ?></h2>
                <div class="small text-muted d-flex align-items-center gap-1">
                    <i class="fa-solid fa-circle-check text-success" style="font-size: 0.5rem;"></i>
                    Registered Investors
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="text-muted small fw-bold text-uppercase letter-spacing-1">Pending Inflow</div>
                    <div class="icon-shape bg-warning bg-opacity-10 text-warning rounded-circle p-2" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-hand-holding-dollar"></i>
                    </div>
                </div>
                <h2 class="fw-800 mb-1 text-warning"><?php echo number_format($pending_deposits); ?></h2>
                <div class="small text-muted d-flex align-items-center gap-1">
                    <i class="fa-solid fa-spinner fa-spin" style="font-size: 0.5rem;"></i>
                    Awaiting Verification
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="text-muted small fw-bold text-uppercase letter-spacing-1">Pending Outflow</div>
                    <div class="icon-shape bg-danger bg-opacity-10 text-danger rounded-circle p-2" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-money-bill-transfer"></i>
                    </div>
                </div>
                <h2 class="fw-800 mb-1 text-danger"><?php echo number_format($pending_withdrawals); ?></h2>
                <div class="small text-muted d-flex align-items-center gap-1">
                    <i class="fa-solid fa-triangle-exclamation text-danger" style="font-size: 0.5rem;"></i>
                    Requires Attention
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="text-muted small fw-bold text-uppercase letter-spacing-1">Network Stake</div>
                    <div class="icon-shape bg-success bg-opacity-10 text-success rounded-circle p-2" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-chart-line-up"></i>
                    </div>
                </div>
                <h2 class="fw-800 mb-1 text-success">$<?php echo number_format($total_invested, 2); ?></h2>
                <div class="small text-muted d-flex align-items-center gap-1">
                    <i class="fa-solid fa-bolt-lightning text-warning" style="font-size: 0.5rem;"></i>
                    Active Capital Exposure
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-800 m-0 text-dark">Financial Growth</h5>
                    <p class="text-muted small m-0">30-day liquidity and movement trend</p>
                </div>
                <a href="index.php?route=analytics" class="btn btn-sm btn-light px-3 fw-bold">Detailed Analysis</a>
            </div>
            <div class="card-body">
                <div style="height: 320px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="fw-800 m-0 text-dark">Intelligence Shortcuts</h5>
                <p class="text-muted small m-0">Quick access to platform protocols</p>
            </div>
            <div class="card-body d-flex flex-column gap-3">
                <a href="index.php?route=security" class="d-flex align-items-center gap-3 p-3 rounded-4 bg-light text-decoration-none transition-all">
                    <div class="icon-shape bg-danger bg-opacity-10 text-danger rounded-3 p-2">
                        <i class="fa-solid fa-shield-virus"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark small">Network Quarantine</div>
                        <div class="text-muted" style="font-size: 0.65rem;">Manage IP blacklists</div>
                    </div>
                    <i class="fa-solid fa-chevron-right text-muted small"></i>
                </a>
                
                <a href="index.php?route=settings" class="d-flex align-items-center gap-3 p-3 rounded-4 bg-light text-decoration-none transition-all">
                    <div class="icon-shape bg-warning bg-opacity-10 text-warning rounded-3 p-2">
                        <i class="fa-solid fa-gear"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark small">Global Systems</div>
                        <div class="text-muted" style="font-size: 0.65rem;">Toggle platform modes</div>
                    </div>
                    <i class="fa-solid fa-chevron-right text-muted small"></i>
                </a>

                <div class="mt-auto p-4 rounded-4 bg-primary bg-opacity-5 border-0">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="m-0 fw-bold text-primary small">Integrity Status</h6>
                        <span class="badge bg-primary text-white" style="font-size: 0.55rem;">ENCRYPTED</span>
                    </div>
                    <div class="progress mb-2" style="height: 8px; background: #fff;">
                        <div class="progress-bar bg-primary" style="width: 100%;"></div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted" style="font-size: 0.65rem;">
                        <span>System Stability</span>
                        <span class="text-primary fw-bold">OPTIMAL</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    async function initDashboard() {
        try {
            const resp = await fetch('/api/admin_actions?action=get_analytics');
            const result = await resp.json();
            if (result.status === 'success') {
                const ctx = document.getElementById('revenueChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: result.data.labels,
                        datasets: [
                            {
                                label: 'Deposits',
                                data: result.data.deposits,
                                borderColor: '#3b82f6',
                                backgroundColor: 'rgba(59, 130, 246, 0.05)',
                                fill: true,
                                tension: 0.5,
                                borderWidth: 3,
                                pointRadius: 0,
                                pointHoverRadius: 6
                            },
                            {
                                label: 'Withdrawals',
                                data: result.data.withdrawals,
                                borderColor: '#ef4444',
                                backgroundColor: 'rgba(239, 68, 68, 0.05)',
                                fill: true,
                                tension: 0.5,
                                borderWidth: 3,
                                pointRadius: 0,
                                pointHoverRadius: 6
                            },
                            {
                                label: 'Net Flow (P/L)',
                                data: result.data.net,
                                borderColor: '#10b981',
                                backgroundColor: 'transparent',
                                fill: false,
                                tension: 0.5,
                                borderWidth: 4,
                                borderDash: [5, 5],
                                pointRadius: 0,
                                pointHoverRadius: 6
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { intersect: false, mode: 'index' },
                        plugins: {
                            legend: { 
                                display: true,
                                position: 'top',
                                align: 'end',
                                labels: {
                                    boxWidth: 8,
                                    usePointStyle: true,
                                    font: { size: 10, weight: '700' }
                                }
                            }
                        },
                        scales: {
                            y: {
                                border: { display: false },
                                grid: { color: '#f3f4f6' },
                                ticks: { color: '#9ca3af', font: { size: 11, weight: '600' } }
                            },
                            x: {
                                border: { display: false },
                                grid: { display: false },
                                ticks: { color: '#9ca3af', font: { size: 11, weight: '600' } }
                            }
                        }
                    }
                });
            }
        } catch(e) {
            console.error('Failed to load chart data', e);
        }
    }
    initDashboard();
</script>
