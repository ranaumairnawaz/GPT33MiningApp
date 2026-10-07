<?php
$user_id = $_GET['id'] ?? 0;
if (!$user_id) {
    echo "User ID missing";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo "User not found";
    exit;
}

// Stats for this user
$total_deposited = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'deposit' AND status = 'approved'");
$total_deposited->execute([$user_id]);
$deposited = $total_deposited->fetchColumn() ?: 0;

$total_withdrawn = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'withdrawal' AND status = 'completed'");
$total_withdrawn->execute([$user_id]);
$withdrawn = $total_withdrawn->fetchColumn() ?: 0;

$active_invested = $pdo->prepare("SELECT SUM(invested_amount) FROM user_investments WHERE user_id = ? AND status = 'active'");
$active_invested->execute([$user_id]);
$invested = $active_invested->fetchColumn() ?: 0;

$total_profit = $pdo->prepare("SELECT SUM(total_profit) FROM user_investments WHERE user_id = ?");
$total_profit->execute([$user_id]);
$profit = $total_profit->fetchColumn() ?: 0;

// Transactions for this user
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll();

// Investments for this user
$stmt = $pdo->prepare("SELECT i.*, p.name as plan_name FROM user_investments i JOIN plans p ON i.plan_id = p.id WHERE i.user_id = ? ORDER BY i.created_at DESC");
$stmt->execute([$user_id]);
$user_investments = $stmt->fetchAll();
?>

<div class="row mb-5 align-items-center">
    <div class="col">
        <div class="d-flex align-items-center gap-4">
            <a href="index.php?route=users" class="btn btn-light rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <i class="fa-solid fa-chevron-left text-dark"></i>
            </a>
            <div>
                <h2 class="fw-800 m-0 text-dark"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <span class="badge bg-primary bg-opacity-10 text-primary fw-bold" style="font-size: 0.65rem;">
                        <i class="fa-solid fa-crown me-1"></i> <?php echo strtoupper($user['tier'] ?? 'BRONZE'); ?> STATUS
                    </span>
                    <p class="text-muted m-0 fw-bold small text-uppercase letter-spacing-1">Investor Node: #<?php echo $user['id']; ?> &nbsp; • &nbsp; <?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-auto">
        <div class="d-flex gap-3">
            <button class="btn btn-dark py-3 px-4 fw-bold rounded-4 shadow-sm" onclick="openBalanceModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')">
                <i class="fa-solid fa-scale-balanced me-2"></i> ADJUST ASSETS
            </button>
            <div class="dropdown">
                <button class="btn btn-light border py-3 px-4 fw-bold rounded-4" data-bs-toggle="dropdown">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-4 p-2">
                    <li>
                        <a class="dropdown-item rounded-3 py-2 text-primary fw-bold" href="/api/admin_actions?action=login_as_user&id=<?php echo $user['id']; ?>">
                            <i class="fa-solid fa-user-secret me-2"></i> IMPERSONATE NODE
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item rounded-3 py-2 text-info fw-bold" href="javascript:void(0)" onclick="viewReferralTree(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')">
                            <i class="fa-solid fa-network-wired me-2"></i> NETWORK MAP
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item rounded-3 py-2 text-warning fw-bold" href="javascript:void(0)" onclick="openTierModal('<?php echo $user['tier'] ?? 'Bronze'; ?>')">
                            <i class="fa-solid fa-crown me-2"></i> ADJUST TIER
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item rounded-3 py-2 text-danger fw-bold" href="/api/admin_actions?action=toggle_ban&user_id=<?php echo $user['id']; ?>&status=<?php echo $user['is_banned'] ? 0 : 1; ?>">
                            <i class="fa-solid fa-user-slash me-2"></i> <?php echo $user['is_banned'] ? 'REVOKE BAN' : 'EXECUTE BAN'; ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card h-100 p-4">
            <div class="text-muted small fw-bold text-uppercase mb-3 letter-spacing-1">Deposit Capital</div>
            <h2 class="fw-800 text-dark mb-0">$<?php echo number_format($user['deposit_balance'], 2); ?></h2>
            <div class="text-success fw-bold mt-2" style="font-size: 0.65rem;">
                <i class="fa-solid fa-circle-arrow-down me-1"></i> Lifetime Flow: $<?php echo number_format($deposited, 2); ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 p-4">
            <div class="text-muted small fw-bold text-uppercase mb-3 letter-spacing-1">Yield Earnings</div>
            <h2 class="fw-800 text-dark mb-0">$<?php echo number_format($user['earnings_balance'], 2); ?></h2>
            <div class="text-primary fw-bold mt-2" style="font-size: 0.65rem;">
                <i class="fa-solid fa-chart-line me-1"></i> Total Accrued: $<?php echo number_format($profit, 2); ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 p-4">
            <div class="text-muted small fw-bold text-uppercase mb-3 letter-spacing-1">Active Exposure</div>
            <h2 class="fw-800 text-dark mb-0">$<?php echo number_format($invested, 2); ?></h2>
            <div class="text-warning fw-bold mt-2" style="font-size: 0.65rem;">
                <i class="fa-solid fa-microchip me-1"></i> Computing Power
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 p-4">
            <div class="text-muted small fw-bold text-uppercase mb-3 letter-spacing-1">Total Withdrawn</div>
            <h2 class="fw-800 text-dark mb-0">$<?php echo number_format($withdrawn, 2); ?></h2>
            <div class="text-danger fw-bold mt-2" style="font-size: 0.65rem;">
                <i class="fa-solid fa-circle-arrow-up me-1"></i> Net Outflow
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card h-100 overflow-hidden">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-800 m-0 text-dark">Historical Ledger</h5>
                <a href="index.php?route=transactions&user_id=<?php echo $user_id; ?>" class="btn btn-sm btn-light fw-bold px-3">FULL AUDIT</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Timeframe</th>
                            <th>Operation</th>
                            <th>Flow</th>
                            <th class="text-end">State</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td>
                                <div class="text-dark small fw-bold"><?php echo date('M d, H:i', strtotime($t['created_at'])); ?></div>
                            </td>
                            <td>
                                <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.65rem;">
                                    <?php
                                    $type_map = [
                                        'deposit' => 'Capital Inflow',
                                        'withdrawal' => 'Capital Outflow',
                                        'investment' => 'Cluster Stake',
                                        'payout' => 'Yield Accrual',
                                        'manual_adjustment' => 'Balance Corrective',
                                        'referral_commission' => 'Network Bonus',
                                        'interest' => 'Staking Reward'
                                    ];
                                    echo $type_map[$t['type']] ?? str_replace('_', ' ', $t['type']);
                                    ?>
                                </span>
                            </td>
                            <td class="small fw-800 <?php echo ($t['type'] == 'withdrawal' || $t['type'] == 'investment') ? 'text-danger' : 'text-primary'; ?>">
                                <?php echo ($t['type'] == 'withdrawal' || $t['type'] == 'investment') ? '-' : '+'; ?> $<?php echo number_format($t['amount'], 2); ?>
                            </td>
                            <td class="text-end">
                                <div class="d-flex flex-column align-items-end gap-1">
                                    <?php
                                    $s_class = 'secondary';
                                    if ($t['status'] == 'approved' || $t['status'] == 'completed') $s_class = 'success';
                                    if ($t['status'] == 'pending') $s_class = 'warning';
                                    if ($t['status'] == 'rejected') $s_class = 'danger';
                                    ?>
                                    <span class="badge bg-<?php echo $s_class; ?> bg-opacity-10 text-<?php echo $s_class; ?>" style="font-size: 0.6rem;">
                                        <?php echo strtoupper($t['status']); ?>
                                    </span>
                                    <?php if ($t['status'] == 'pending' && ($t['type'] == 'deposit' || $t['type'] == 'withdrawal')): ?>
                                        <button onclick='verifyTransaction(<?php echo json_encode($t); ?>)' class="btn btn-sm btn-primary border-0 py-0 px-2 fw-bold" style="font-size: 0.55rem;"> VERIFY </button>
                                    <?php elseif ($t['screenshot']): ?>
                                        <a href="../<?php echo $t['screenshot']; ?>" target="_blank" class="text-primary fw-bold" style="font-size: 0.55rem; text-decoration: none;"> VIEW PROOF </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header border-0">
                <h5 class="fw-800 m-0 text-dark">Computing Clusters</h5>
            </div>
            <div class="card-body d-flex flex-column gap-3">
                <?php if (empty($user_investments)): ?>
                    <div class="py-5 text-center text-muted small opacity-50">No active clusters detected</div>
                <?php endif; ?>
                <?php foreach ($user_investments as $ui): ?>
                    <div class="p-4 rounded-4 bg-light d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-dark fw-800 small text-uppercase mb-1"><?php echo htmlspecialchars($ui['plan_name']); ?></div>
                            <div class="text-muted" style="font-size: 0.75rem;">Principal Stake: <span class="text-dark fw-bold">$<?php echo number_format($ui['invested_amount'], 2); ?></span></div>
                        </div>
                        <div class="text-end">
                            <div class="text-primary fw-800 small">+$<?php echo number_format($ui['total_profit'], 4); ?></div>
                            <span class="badge <?php echo $ui['status'] == 'active' ? 'bg-success' : 'bg-secondary'; ?> bg-opacity-10 text-<?php echo $ui['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                <?php echo strtoupper($ui['status']); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Balance Adjustment Modal -->
<div class="modal fade" id="balanceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-5 shadow-lg">
            <div class="modal-header border-0 p-4">
                <h5 class="fw-800 text-dark m-0">Asset Correction Interface</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="balance-form" data-ajax-form data-reload action="/api/admin_actions?action=adjust_balance" method="POST">
                <div class="modal-body p-4">
                    <p class="text-muted small mb-4">Target Node: <span id="balance-user-name" class="fw-bold text-dark"></span></p>
                    <input type="hidden" name="user_id" id="balance-user-id">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Financial Segment</label>
                            <select name="balance_type" class="form-select py-3 rounded-4">
                                <option value="deposit">Deposit Capital</option>
                                <option value="earnings">Yield Earnings</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Adjustment Type</label>
                            <select name="adj_type" class="form-select py-3 rounded-4">
                                <option value="add" class="text-success fw-bold">+ Add Funds</option>
                                <option value="deduct" class="text-danger fw-bold">- Deduct Funds</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted text-uppercase">Magnitude ($)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0 px-3 fw-bold">$</span>
                            <input type="number" name="amount" step="0.01" min="0.01" class="form-control bg-light border-0 py-3" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold small text-muted text-uppercase">Protocol Notation</label>
                        <input type="text" name="note" class="form-control bg-light border-0 py-3 rounded-4" placeholder="Audit trail description" required>
                    </div>
                </div>
                <div class="p-4 pt-0">
                    <button type="submit" class="btn btn-dark w-100 py-3 fw-bold rounded-4 shadow-sm">COMMIT ADJUSTMENT</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Referral Tree Modal -->
<div class="modal fade" id="referralModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-5 shadow-lg">
            <div class="modal-header border-0 p-4">
                <h5 class="fw-800 text-dark m-0">Network Architecture Map: <span id="ref-user-name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-0" style="max-height: 60vh; overflow-y: auto;">
                <div id="referral-tree-container" class="small"></div>
            </div>
        </div>
    </div>
</div>

<!-- Tier Adjustment Modal -->
<div class="modal fade" id="tierModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 rounded-5 shadow-lg">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="fw-800 text-dark m-0">Adjust Member Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="tier-form" data-ajax-form data-reload action="/api/admin_actions?action=update_tier" method="POST">
                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                <div class="modal-body p-4">
                    <label class="form-label fw-bold small text-muted text-uppercase">New Status Tier</label>
                    <select name="tier" id="tier-select" class="form-select bg-light border-0 py-3 rounded-4">
                        <?php
                        $tiers = $pdo->query("SELECT name FROM tiers ORDER BY min_investment ASC")->fetchAll();
                        foreach ($tiers as $t): ?>
                            <option value="<?php echo htmlspecialchars($t['name']); ?>"><?php echo htmlspecialchars($t['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="p-4 pt-0">
                    <button type="submit" class="btn btn-dark w-100 py-3 fw-bold rounded-4 shadow-sm">COMMIT STATUS</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Verification Modal -->
<div class="modal fade" id="verifyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-800" id="verifyTitle">Audit Proof</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="verifyProof" class="mb-4 text-center d-none">
                    <p class="text-muted small fw-bold text-uppercase mb-2">Evidence Attachment</p>
                    <a id="proofLink" target="_blank">
                        <img id="proofImg" src="" class="img-fluid rounded-4 shadow-sm border" style="max-height: 300px; cursor: zoom-in;">
                    </a>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="bg-light p-3 rounded-4">
                            <div class="text-muted small fw-bold text-uppercase mb-1">Capital Value</div>
                            <div id="verifyAmount" class="h4 fw-900 text-dark m-0"></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-light p-3 rounded-4">
                            <div class="text-muted small fw-bold text-uppercase mb-1">Log ID (TID)</div>
                            <div id="verifyTID" class="fw-bold text-primary m-0" style="word-break: break-all;"></div>
                        </div>
                    </div>
                </div>
                <div id="verifyAlert" class="alert alert-warning border-0 rounded-4 small mt-4 m-0 d-none">
                    <i class="fa-solid fa-circle-info me-2"></i> Approving this will immediately update the investor's balance.
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 gap-2">
                <button type="button" class="btn btn-light rounded-4 px-4 fw-bold" data-bs-dismiss="modal">Close</button>
                <button type="button" onclick="submitAudit('rejected')" class="btn btn-outline-danger rounded-4 px-4 fw-bold">Reject</button>
                <button type="button" onclick="submitAudit('approved')" class="btn btn-success rounded-4 px-4 fw-bold">Approve</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const bModal = new bootstrap.Modal(document.getElementById('balanceModal'));
    const rModal = new bootstrap.Modal(document.getElementById('referralModal'));
    const vModal = new bootstrap.Modal(document.getElementById('verifyModal'));
    let activeTx = null;

    window.openBalanceModal = function (id, name) {
        document.getElementById('balance-user-id').value = id;
        document.getElementById('balance-user-name').textContent = name;
        bModal.show();
    };

    window.viewReferralTree = async function (id, name) {
        document.getElementById('ref-user-name').textContent = name;
        const container = document.getElementById('referral-tree-container');
        container.innerHTML = '<div class="text-center py-5 text-muted"><i class="fa-solid fa-spinner fa-spin fs-4 mb-3 d-block"></i>Mapping nodes...</div>';
        rModal.show();
        try {
            const resp = await fetch('/api/admin_actions?action=get_referral_tree&user_id=' + id);
            const data = await resp.json();
            if (data.status === 'success') {
                container.innerHTML = renderTree(data.tree);
            }
        } catch (e) {
            container.innerHTML = 'Error mapping network architecture';
        }
    };

    function renderTree(nodes, level = 1) {
        if (!nodes || nodes.length === 0) return level === 1 ? '<div class="text-center text-muted py-4 small">No downstream nodes detected</div>' : '';
        let html = '<div class="ps-3 border-start border-light mt-3">';
        nodes.forEach(u => {
            html += `
                <div class="mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2" style="font-size: 0.6rem;">LVL ${u.level}</span>
                        <span class="text-dark fw-bold">${u.full_name}</span>
                        <span class="text-muted" style="font-size: 0.75rem;">${u.email}</span>
                    </div>
                    ${renderTree(u.children, level + 1)}
                </div>
            `;
        });
        html += '</div>';
        return html;
    }

    window.verifyTransaction = function(tx) {
        activeTx = tx;
        document.getElementById('verifyTitle').textContent = tx.type === 'deposit' ? 'Verify Capital Inflow' : 'Verify Capital Outflow';
        document.getElementById('verifyAmount').textContent = '$' + parseFloat(tx.amount).toLocaleString();
        document.getElementById('verifyTID').textContent = tx.account_details || 'N/A';
        
        const proofDiv = document.getElementById('verifyProof');
        if (tx.screenshot) {
            document.getElementById('proofImg').src = '../' + tx.screenshot;
            document.getElementById('proofLink').href = '../' + tx.screenshot;
            proofDiv.classList.remove('d-none');
        } else {
            proofDiv.classList.add('d-none');
        }

        const alertDiv = document.getElementById('verifyAlert');
        if (tx.type === 'deposit') alertDiv.classList.remove('d-none');
        else alertDiv.classList.add('d-none');
        
        vModal.show();
    };

    window.submitAudit = async function(status) {
        if (!activeTx) return;
        const action = activeTx.type === 'deposit' ? 'update_deposit_status' : 'update_withdrawal_status';
        try {
            // Using absolute path to avoid relative path issues in admin panel
            const resp = await fetch(`/api/admin_actions?action=${action}&id=${activeTx.id}&status=${status}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await resp.json();
            if (result.status === 'success') {
                if (window.showToast) showToast(result.message, 'success');
                vModal.hide();
                setTimeout(() => location.reload(), 1000);
            } else {
                if (window.showToast) showToast(result.message || 'Audit failed', 'danger');
            }
        } catch (err) {
            if (window.showToast) showToast('Connection error', 'danger');
        }
    };
    window.openTierModal = function(currentTier) {
        document.getElementById('tier-select').value = currentTier;
        new bootstrap.Modal(document.getElementById('tierModal')).show();
    };
});
</script>
