<?php
// Ledger Integrity Audit View
// Calculate reserve solvency status metrics
$total_deposits = (float)$pdo->query("SELECT SUM(amount) FROM transactions WHERE type='deposit' AND status='approved'")->fetchColumn() ?: 0;
$total_withdrawals = (float)$pdo->query("SELECT SUM(amount) FROM transactions WHERE type='withdrawal' AND status='completed'")->fetchColumn() ?: 0;

$gross_reserves = $total_deposits - $total_withdrawals;
$user_liabilities = (float)$pdo->query("SELECT SUM(deposit_balance + earnings_balance) FROM users WHERE is_admin = 0")->fetchColumn() ?: 0;
$active_staked = (float)$pdo->query("SELECT SUM(invested_amount) FROM user_investments WHERE status='active'")->fetchColumn() ?: 0;

$total_liabilities = $user_liabilities + $active_staked;
$reserve_ratio = $total_liabilities > 0 ? $gross_reserves / $total_liabilities : 1;

if ($reserve_ratio >= 1.2) {
    $solvency_badge = 'bg-success bg-opacity-10 text-success border border-success border-opacity-20';
    $solvency_text = '🟢 PLATFORM SOLVENT & FUNDED';
    $solvency_desc = 'Reserves fully secure and exceed total user deposit & yield liabilities by +' . round(($reserve_ratio - 1) * 100) . '%.';
} elseif ($reserve_ratio >= 0.8) {
    $solvency_badge = 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-20';
    $solvency_text = '🟡 CAUTION - FRACTIONAL LIQUIDITY';
    $solvency_desc = 'Platform is operates on fractional reserve. Monitor outbound withdrawal thresholds and queue velocity.';
} else {
    $solvency_badge = 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-20';
    $solvency_text = '🔴 RISK - CRITICAL UNDERFUNDED';
    $solvency_desc = 'Platform reserves are below safety targets. Limit withdrawal volumes and launch promotional yield campaigns.';
}
?>

<div class="row mb-5 align-items-center">
    <div class="col">
        <h2 class="fw-800 m-0 text-dark">Security Intelligence</h2>
        <p class="text-muted m-0">Ledger integrity scanners & platform solvency auditors</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Solvency Indicator -->
    <div class="col-lg-6">
        <div class="card h-100 p-4 border-0 shadow-sm rounded-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="badge px-3 py-2 rounded-pill fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.05em; <?php echo $solvency_badge; ?>">
                    <?php echo $solvency_text; ?>
                </span>
                <span class="text-muted fw-bold small text-uppercase" style="font-size: 0.65rem;">Solvency Ratio</span>
            </div>
            <h1 class="fw-900 text-dark m-0" style="font-size: 3.5rem; letter-spacing: -0.05em;"><?php echo number_format($reserve_ratio, 2); ?>x</h1>
            <p class="text-muted small m-0 mt-3"><?php echo $solvency_desc; ?></p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100 p-4 border-0 shadow-sm rounded-4">
            <div class="text-muted fw-bold text-uppercase small mb-3 letter-spacing-1" style="font-size: 0.65rem;">Platform Reserves</div>
            <h2 class="fw-800 text-dark mb-0">$<?php echo number_format($gross_reserves, 2); ?></h2>
            <div class="small text-muted mt-3 fw-bold" style="font-size: 0.65rem;">
                <i class="fa-solid fa-vault me-1 text-primary"></i> Liquid Held Assets
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100 p-4 border-0 shadow-sm rounded-4">
            <div class="text-muted fw-bold text-uppercase small mb-3 letter-spacing-1" style="font-size: 0.65rem;">Outstanding Liabilities</div>
            <h2 class="fw-800 text-dark mb-0">$<?php echo number_format($total_liabilities, 2); ?></h2>
            <div class="small text-muted mt-3 fw-bold" style="font-size: 0.65rem;">
                <i class="fa-solid fa-chart-pie me-1 text-warning"></i> Staked & Wallet Balances
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Auditor Trigger Console -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100 d-flex flex-column justify-content-center">
            <div class="mb-4">
                <i class="fa-solid fa-user-shield fa-4x text-primary opacity-75"></i>
            </div>
            <h4 class="fw-800 text-dark mb-2">Ledger Integrity Scan</h4>
            <p class="text-muted small mb-4 px-3">
                Scans the transaction history database of all active investors, validating double-entry integrity to isolate exploits, balance glitches, or unauthorized ledger mutations.
            </p>
            <button id="btn-run-audit" class="btn btn-dark w-100 py-3 fw-bold rounded-pill shadow-sm" onclick="runIntegrityAudit()">
                <i class="fa-solid fa-magnifying-glass-chart me-2"></i> INITIATE AUDIT RUN
            </button>
        </div>
    </div>

    <!-- Audit Diagnostic Results Table -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-800 m-0 text-dark"><i class="fa-solid fa-circle-exclamation me-2 text-danger"></i> Discrepancy Diagnostics</h5>
                <span id="audit-indicator" class="badge bg-light text-dark fw-bold border px-3 py-2 rounded-pill small">IDLE SYSTEM</span>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table align-middle" id="audit-results-table">
                        <thead>
                            <tr>
                                <th class="ps-3" style="font-size: 0.65rem;">Investor Nodes</th>
                                <th style="font-size: 0.65rem;">Database Balance</th>
                                <th style="font-size: 0.65rem;">Ledger Equation</th>
                                <th style="font-size: 0.65rem;">Discrepancy</th>
                                <th class="text-end pe-3" style="font-size: 0.65rem;">Action Link</th>
                            </tr>
                        </thead>
                        <tbody id="audit-results-body">
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted small">
                                    <i class="fa-solid fa-shield-halved fa-2x mb-3 text-muted opacity-50 d-block m-auto"></i>
                                    Click "Initiate Audit Run" to execute transaction verification.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    async function runIntegrityAudit() {
        const btn = document.getElementById('btn-run-audit');
        const indicator = document.getElementById('audit-indicator');
        const tbody = document.getElementById('audit-results-body');

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin me-2"></i> AUDITING LEDGERS...';
        indicator.className = 'badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-20 px-3 py-2 rounded-pill';
        indicator.textContent = 'SCANNING LEDGER LOGS';

        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-5">
                    <i class="fa-solid fa-spinner fa-spin fa-2x text-primary mb-3"></i>
                    <div class="fw-bold small text-muted">Running double-entry verification checks...</div>
                </td>
            </tr>
        `;

        try {
            const resp = await fetch('/api/admin_actions?action=run_integrity_scan');
            const data = await resp.json();

            if (data.status === 'success') {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-rotate-right me-2"></i> RERUN INTEGRITY AUDIT';

                if (data.flagged_count > 0) {
                    indicator.className = 'badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-20 px-3 py-2 rounded-pill';
                    indicator.textContent = `${data.flagged_count} ANOMALIES DETECTED`;

                    tbody.innerHTML = data.flagged.map(f => {
                        const statusBadge = f.status === 'banned' || f.status === 'suspended'
                            ? '<span class="badge bg-danger bg-opacity-10 text-danger rounded-pill fw-bold" style="font-size:0.6rem;">FROZEN</span>'
                            : '<span class="badge bg-success bg-opacity-10 text-success rounded-pill fw-bold" style="font-size:0.6rem;">ACTIVE</span>';

                        return `
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-dark small">${escapeHtml(f.full_name)}</div>
                                    <div class="text-muted small" style="font-size:0.65rem;">${escapeHtml(f.email)}</div>
                                    <div class="mt-1">${statusBadge}</div>
                                </td>
                                <td><div class="fw-bold text-dark small">$${f.current_total.toFixed(2)}</div></td>
                                <td><div class="fw-bold text-muted small">$${f.expected_total.toFixed(2)}</div></td>
                                <td><div class="fw-bold text-danger small">$${f.discrepancy.toFixed(2)}</div></td>
                                <td class="text-end pe-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="index.php?route=user_details&id=${f.id}" class="btn btn-sm btn-light border px-3 fw-bold rounded-3 small text-uppercase" style="font-size:0.65rem;">PROFILE</a>
                                        ${f.status !== 'banned' && f.status !== 'suspended' ? `
                                            <button class="btn btn-sm btn-danger px-3 fw-bold rounded-3 small text-uppercase" style="font-size:0.65rem;" onclick="suspendUserAccount(${f.id}, this)">SUSPEND</button>
                                        ` : ''}
                                    </div>
                                </td>
                            </tr>
                        `;
                    }).join('');
                } else {
                    indicator.className = 'badge bg-success bg-opacity-10 text-success border border-success border-opacity-20 px-3 py-2 rounded-pill';
                    indicator.textContent = '🟢 SYSTEM FULLY INTEGRATED';

                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="fa-solid fa-circle-check text-success fa-3x mb-3"></i>
                                <h5 class="fw-bold text-dark mb-1">Audit Ledger Safe</h5>
                                <p class="text-muted small m-0">Zero discrepancies detected across all investor base records!</p>
                            </td>
                        </tr>
                    `;
                }
            } else {
                throw new Error(data.message || 'Verification execution fault');
            }
        } catch (err) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-2"></i> RETRY AUDIT SCAN';
            indicator.className = 'badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-20 px-3 py-2 rounded-pill';
            indicator.textContent = 'EXECUTION FAILURE';

            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-5 text-danger small">
                        <i class="fa-solid fa-triangle-exclamation fa-2x mb-2"></i><br>
                        ${escapeHtml(err.message)}
                    </td>
                </tr>
            `;
        }
    }

    async function suspendUserAccount(userId, btn) {
        if (!confirm('Are you absolutely sure you want to suspend and freeze this flagged account?')) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>';

        try {
            const resp = await fetch(`/api/admin_actions?action=toggle_ban&user_id=${userId}&status=1`);
            const data = await resp.json();

            if (data.status === 'success') {
                if (window.showToast) window.showToast('Investor node successfully frozen & suspended', 'success');
                runIntegrityAudit(); // Refresh diagnostic table
            } else {
                throw new Error(data.message || 'Suspension request error');
            }
        } catch (err) {
            btn.disabled = false;
            btn.innerHTML = 'SUSPEND';
            if (window.showToast) window.showToast(err.message, 'danger');
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }
</script>
