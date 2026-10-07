<?php
// Calculate Solvency Reserve Metrics
$total_deposits = (float)$pdo->query("SELECT SUM(amount) FROM transactions WHERE type='deposit' AND status='approved'")->fetchColumn() ?: 0;
$total_withdrawals = (float)$pdo->query("SELECT SUM(amount) FROM transactions WHERE type='withdrawal' AND status='completed'")->fetchColumn() ?: 0;

$gross_reserves = $total_deposits - $total_withdrawals;
$user_liabilities = (float)$pdo->query("SELECT SUM(deposit_balance + earnings_balance) FROM users WHERE is_admin = 0")->fetchColumn() ?: 0;
$active_staked = (float)$pdo->query("SELECT SUM(invested_amount) FROM user_investments WHERE status='active'")->fetchColumn() ?: 0;

$total_liabilities = $user_liabilities + $active_staked;
$reserve_ratio = $total_liabilities > 0 ? $gross_reserves / $total_liabilities : 1;

if ($reserve_ratio >= 1.2) {
    $solvency_class = 'bg-success bg-opacity-10 text-success border border-success border-opacity-20';
    $solvency_status = '🟢 FULLY SOLVENT';
    $solvency_tip = 'Reserves exceed total user deposit & yield liabilities by +' . round(($reserve_ratio - 1) * 100) . '%.';
} elseif ($reserve_ratio >= 0.8) {
    $solvency_class = 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-20';
    $solvency_status = '🟡 FRACTIONAL RESERVE';
    $solvency_tip = 'Platform operates on safe fractional reserve capacity. Monitor liquidity outlays.';
} else {
    $solvency_class = 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-20';
    $solvency_status = '🔴 LIQUIDITY RISK EXPOSURE';
    $solvency_tip = 'Total reserves are below outstanding liabilities. Limit high-payout manual approvals.';
}

// Calculate Cron Health latency
$last_cron_time = $pdo->query("SELECT created_at FROM transactions WHERE type IN ('hourly_profit', 'profit') ORDER BY created_at DESC LIMIT 1")->fetchColumn();
$cron_status_html = '';

if ($last_cron_time) {
    $diff_seconds = time() - strtotime($last_cron_time);
    $cron_diff_minutes = round($diff_seconds / 60);
    if ($cron_diff_minutes <= 75) {
        $cron_status_html = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-20 px-3 py-2 rounded-pill fw-bold"><i class="fa-solid fa-circle-check me-1"></i> CRON RUNNING (Last run: ' . $cron_diff_minutes . 'm ago)</span>';
    } else {
        $cron_status_html = '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-20 px-3 py-2 rounded-pill fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i> CRON STALLED (' . $cron_diff_minutes . 'm latency)</span>';
    }
} else {
    $cron_status_html = '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-20 px-3 py-2 rounded-pill fw-bold"><i class="fa-solid fa-circle-info me-1"></i> CRON IDLE (No yield runs)</span>';
}

$top_investors = $pdo->query("
    SELECT u.full_name, u.email, SUM(i.invested_amount) as total_stake 
    FROM users u 
    JOIN user_investments i ON u.id = i.user_id 
    WHERE i.status = 'active' 
    GROUP BY u.id 
    ORDER BY total_stake DESC 
    LIMIT 5
")->fetchAll();
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="fw-800 m-0 text-dark">Financial Intelligence</h2>
        <p class="text-muted m-0">Dynamic cash flow margins & liquidity solvency indexes</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end gap-3 flex-wrap">
        <?php echo $cron_status_html; ?>
        <div class="btn-group rounded-pill p-1 bg-white border shadow-sm">
            <button class="btn btn-sm btn-dark rounded-pill px-3 fw-bold filter-btn" onclick="switchPeriod('24h', this)">24H</button>
            <button class="btn btn-sm btn-light rounded-pill px-3 fw-bold filter-btn" onclick="switchPeriod('3d', this)">3D</button>
            <button class="btn btn-sm btn-light rounded-pill px-3 fw-bold filter-btn" onclick="switchPeriod('7d', this)">7D</button>
            <button class="btn btn-sm btn-light rounded-pill px-3 fw-bold filter-btn active" onclick="switchPeriod('30d', this)">30D</button>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Solvency Indicator -->
    <div class="col-lg-4">
        <div class="card h-100 p-4 border-0 shadow-sm rounded-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="badge px-3 py-2 rounded-pill fw-bold text-uppercase <?php echo $solvency_class; ?>" style="font-size:0.65rem;">
                    <?php echo $solvency_status; ?>
                </span>
                <span class="text-muted fw-bold small text-uppercase" style="font-size: 0.65rem;">Solvency Ratio</span>
            </div>
            <h1 class="fw-900 text-dark m-0" style="font-size: 2.8rem; letter-spacing: -0.05em;"><?php echo number_format($reserve_ratio, 2); ?>x</h1>
            <p class="text-muted small m-0 mt-3"><?php echo $solvency_tip; ?></p>
        </div>
    </div>

    <!-- Multi-Period Dynamic Indicators -->
    <div class="col-lg-8">
        <div class="row g-3 h-100">
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 p-3 border-0 shadow-sm rounded-4">
                    <div class="text-muted fw-bold text-uppercase small mb-2" style="font-size:0.6rem;">Gross Deposits</div>
                    <h3 class="fw-800 text-dark mb-0" id="kpi-deposits">$0.00</h3>
                    <div class="small text-success mt-2 fw-bold" style="font-size: 0.65rem;">
                        Inbound Cash Flow
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 p-3 border-0 shadow-sm rounded-4">
                    <div class="text-muted fw-bold text-uppercase small mb-2" style="font-size:0.6rem;">Gross Payouts</div>
                    <h3 class="fw-800 text-dark mb-0" id="kpi-withdrawals">$0.00</h3>
                    <div class="small text-danger mt-2 fw-bold" style="font-size: 0.65rem;">
                        Outbound Liquidity
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 p-3 border-0 shadow-sm rounded-4">
                    <div class="text-muted fw-bold text-uppercase small mb-2" style="font-size:0.6rem;">Referral Cost</div>
                    <h3 class="fw-800 text-dark mb-0" id="kpi-bonuses">$0.00</h3>
                    <div class="small text-primary mt-2 fw-bold" style="font-size: 0.65rem;">
                        Affiliate Expense
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 p-3 border-0 shadow-sm rounded-4">
                    <div class="text-muted fw-bold text-uppercase small mb-2" style="font-size:0.6rem;">Net Cash Flow</div>
                    <h3 class="fw-800 text-dark mb-0" id="kpi-net">$0.00</h3>
                    <div class="small text-dark mt-2 fw-bold" id="kpi-margin-badge" style="font-size: 0.65rem;">
                        Margin: 0%
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card h-100 border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-800 m-0 text-dark">Cash Flow Trends</h5>
                <span id="chart-period-badge" class="badge bg-light text-dark fw-bold border px-3 py-2 rounded-pill small">30 DAYS WINDOW</span>
            </div>
            <div class="card-body p-4">
                <div style="height: 380px;">
                    <canvas id="detailedChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100 border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                <h5 class="fw-800 m-0 text-dark">HNW Wealth Nodes</h5>
            </div>
            <div class="card-body p-4 d-flex flex-column gap-3">
                <?php foreach ($top_investors as $i => $inv): ?>
                <div class="d-flex align-items-center gap-3 p-3 rounded-4 bg-light border-0">
                    <div style="width: 40px; height: 40px; background: var(--bs-body-bg); border: 1px solid var(--border-color); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--text-main); font-weight: 800; font-size: 0.9rem; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                        <?php echo $i+1; ?>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="fw-bold text-dark small text-truncate"><?php echo htmlspecialchars($inv['full_name']); ?></div>
                        <div class="text-muted text-truncate" style="font-size: 0.65rem;"><?php echo htmlspecialchars($inv['email']); ?></div>
                    </div>
                    <div class="text-primary fw-800 small">$<?php echo number_format($inv['total_stake'], 0); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    let analyticsChart;

    async function switchPeriod(period, btn) {
        // Handle filter active buttons
        if (btn) {
            document.querySelectorAll('.filter-btn').forEach(b => {
                b.classList.remove('btn-dark');
                b.classList.remove('active');
                b.classList.add('btn-light');
            });
            btn.classList.remove('btn-light');
            btn.classList.add('btn-dark');
            btn.classList.add('active');
        }

        const labelsMap = {
            '24h': '24 HOURS DYNAMICS',
            '3d': '3 DAYS DYNAMICS',
            '7d': '7 DAYS DYNAMICS',
            '30d': '30 DAYS DYNAMICS'
        };
        document.getElementById('chart-period-badge').textContent = labelsMap[period] || 'DYNAMICS';

        try {
            const resp = await fetch(`/api/admin_actions?action=get_analytics&period=${period}`);
            const result = await resp.json();

            if (result.status === 'success') {
                // Update KPI counts
                document.getElementById('kpi-deposits').textContent = '$' + result.summary.gross_inflow.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                document.getElementById('kpi-withdrawals').textContent = '$' + result.summary.gross_outflow.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                document.getElementById('kpi-bonuses').textContent = '$' + result.summary.network_bonuses.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                
                const netEl = document.getElementById('kpi-net');
                netEl.textContent = '$' + result.summary.net_profit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                if (result.summary.net_profit >= 0) {
                    netEl.className = 'fw-800 text-success mb-0';
                } else {
                    netEl.className = 'fw-800 text-danger mb-0';
                }

                const marginBadge = document.getElementById('kpi-margin-badge');
                marginBadge.textContent = `Margin: ${result.summary.profit_margin}%`;
                if (result.summary.profit_margin >= 30) {
                    marginBadge.className = 'small text-success mt-2 fw-bold';
                } else if (result.summary.profit_margin >= 0) {
                    marginBadge.className = 'small text-dark mt-2 fw-bold';
                } else {
                    marginBadge.className = 'small text-danger mt-2 fw-bold';
                }

                // Render or update Chart.js
                const ctx = document.getElementById('detailedChart').getContext('2d');
                if (analyticsChart) {
                    analyticsChart.data.labels = result.data.labels;
                    analyticsChart.data.datasets[0].data = result.data.deposits;
                    analyticsChart.data.datasets[1].data = result.data.withdrawals;
                    analyticsChart.update();
                } else {
                    analyticsChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: result.data.labels,
                            datasets: [
                                {
                                    label: 'Deposits',
                                    data: result.data.deposits,
                                    backgroundColor: '#4f46e5',
                                    borderRadius: 6
                                },
                                {
                                    label: 'Withdrawals',
                                    data: result.data.withdrawals,
                                    backgroundColor: '#ef4444',
                                    borderRadius: 6
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: { 
                                    border: { display: false },
                                    grid: { color: 'rgba(148, 163, 184, 0.08)' }, 
                                    ticks: { color: '#94a3b8', font: { size: 10, weight: '600' } } 
                                },
                                x: { 
                                    border: { display: false },
                                    grid: { display: false }, 
                                    ticks: { color: '#94a3b8', font: { size: 10, weight: '600' } } 
                                }
                            },
                            plugins: {
                                legend: { 
                                    position: 'bottom',
                                    labels: { color: '#64748b', font: { size: 12, weight: '600' }, padding: 20 } 
                                }
                            }
                        }
                    });
                }
            }
        } catch (e) {
            console.error('Failed to load period analytical dataset:', e);
        }
    }

    // Default trigger onload
    document.addEventListener('DOMContentLoaded', () => {
        switchPeriod('30d');
    });
</script>
