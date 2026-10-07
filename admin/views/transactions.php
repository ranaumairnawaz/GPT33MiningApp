<?php
$type_filter = $_GET['type'] ?? '';
$where = "";
$params = [];

if ($type_filter) {
    $where = " WHERE t.type = ?";
    $params[] = $type_filter;
}

$stmt = $pdo->prepare("
    SELECT t.*, u.full_name, u.email 
    FROM transactions t 
    JOIN users u ON t.user_id = u.id 
    $where 
    ORDER BY t.created_at DESC
");
$stmt->execute($params);
$transactions = $stmt->fetchAll();

$types = ['deposit', 'withdrawal', 'referral_commission', 'investment', 'profit', 'manual_adjustment'];
?>

<div class="row mb-5 align-items-center">
    <div class="col">
        <h2 class="fw-800 m-0 text-dark">Transaction Ledger</h2>
        <p class="text-muted m-0">Global financial movement and audit logs</p>
    </div>
    <div class="col-auto">
        <div class="d-flex gap-3 align-items-center">
            <span class="text-muted small fw-bold text-uppercase">Filter Segment:</span>
            <select class="form-select bg-light border-0 py-2 px-3 rounded-3 fw-bold small" style="width: 200px;" onchange="filterType(this.value)">
                <option value="">All Operations</option>
                <?php 
                $filter_map = [
                    'deposit' => 'Capital Inflow',
                    'withdrawal' => 'Capital Outflow',
                    'investment' => 'Cluster Stake',
                    'profit' => 'Yield Accrual',
                    'referral_commission' => 'Network Bonus',
                    'manual_adjustment' => 'Balance Corrective'
                ];
                foreach ($filter_map as $val => $label): ?>
                    <option value="<?php echo $val; ?>" <?php echo $type_filter == $val ? 'selected' : ''; ?>>
                        <?php echo $label; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="table-responsive p-4">
        <table class="table align-middle datatable-basic">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Investor Entity</th>
                    <th>Operation Type</th>
                    <th>Capital Flow</th>
                    <th>Interface/Log</th>
                    <th class="text-end">Verification</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted small opacity-50">No operations found in the ledger.</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($transactions as $t): ?>
                <tr>
                    <td>
                        <div class="small text-dark fw-bold"><?php echo date('M d, Y', strtotime($t['created_at'])); ?></div>
                        <div class="text-muted" style="font-size: 0.65rem;"><?php echo date('H:i:s', strtotime($t['created_at'])); ?> UTC</div>
                    </td>
                    <td>
                        <div class="fw-bold text-dark small"><?php echo htmlspecialchars($t['full_name']); ?></div>
                        <div class="text-muted" style="font-size: 0.65rem;"><?php echo htmlspecialchars($t['email']); ?></div>
                    </td>
                    <td>
                        <?php 
                        $type_map = [
                            'deposit' => 'Capital Inflow',
                            'withdrawal' => 'Capital Outflow',
                            'investment' => 'Cluster Stake',
                            'payout' => 'Yield Accrual',
                            'profit' => 'Yield Accrual',
                            'manual_adjustment' => 'Balance Corrective',
                            'referral_commission' => 'Network Bonus',
                            'interest' => 'Staking Reward'
                        ];
                        $display_type = $type_map[$t['type']] ?? str_replace('_', ' ', $t['type']);

                        $badge_class = 'bg-secondary';
                        if ($t['type'] == 'deposit') $badge_class = 'bg-success';
                        if ($t['type'] == 'withdrawal') $badge_class = 'bg-danger';
                        if ($t['type'] == 'investment') $badge_class = 'bg-primary';
                        if ($t['type'] == 'referral_commission') $badge_class = 'bg-info';
                        if ($t['type'] == 'profit' || $t['type'] == 'payout') $badge_class = 'bg-warning';
                        ?>
                        <span class="badge <?php echo $badge_class; ?> bg-opacity-10 text-<?php echo str_replace('bg-', '', $badge_class); ?>">
                            <?php echo strtoupper($display_type); ?>
                        </span>
                    </td>
                    <td class="fw-800 small <?php echo ($t['type'] == 'withdrawal' || $t['type'] == 'investment') ? 'text-danger' : 'text-primary'; ?>">
                        <?php echo ($t['type'] == 'withdrawal' || $t['type'] == 'investment') ? '-' : '+'; ?>
                        $<?php echo number_format($t['amount'], 2); ?>
                    </td>
                    <td class="small text-muted">
                        <div class="text-dark fw-bold"><?php echo htmlspecialchars($t['method_name'] ?? 'System Core'); ?></div>
                        <?php if ($t['account_details']): ?>
                            <div class="text-muted" style="font-size: 0.65rem; max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($t['account_details']); ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <div class="d-flex flex-column align-items-end gap-1">
                            <?php
                            $s_class = 'secondary';
                            if ($t['status'] == 'approved' || $t['status'] == 'completed') $s_class = 'success';
                            if ($t['status'] == 'pending') $s_class = 'warning';
                            if ($t['status'] == 'rejected') $s_class = 'danger';
                            ?>
                            <span class="badge bg-<?php echo $s_class; ?> bg-opacity-10 text-<?php echo $s_class; ?>">
                                <?php echo strtoupper($t['status']); ?>
                            </span>
                            <?php if ($t['status'] == 'pending' && ($t['type'] == 'deposit' || $t['type'] == 'withdrawal')): ?>
                                <button onclick='verifyTransaction(<?php echo json_encode($t); ?>)' class="btn btn-sm btn-primary border-0 py-1 px-2 fw-bold" style="font-size: 0.6rem;">
                                    <i class="fa-solid fa-shield-check"></i> VERIFY
                                </button>
                            <?php elseif ($t['screenshot']): ?>
                                <a href="../<?php echo $t['screenshot']; ?>" target="_blank" class="btn btn-sm btn-light border-0 py-1 px-2 text-primary fw-bold" style="font-size: 0.6rem;">
                                    <i class="fa-solid fa-eye"></i> View Proof
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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
    const vModal = new bootstrap.Modal(document.getElementById('verifyModal'));
    let activeTx = null;

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
    }

    window.submitAudit = async function(status) {
        if (!activeTx) return;
        const action = activeTx.type === 'deposit' ? 'update_deposit_status' : 'update_withdrawal_status';
        
        try {
            const resp = await fetch(`/api/admin_actions?action=${action}&id=${activeTx.id}&status=${status}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await resp.json();
            
            if (result.status === 'success') {
                showToast(result.message, 'success');
                vModal.hide();
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(result.message || 'Audit failed', 'danger');
            }
        } catch (err) {
            showToast('Connection error', 'danger');
        }
    }

    window.filterType = function(val) {
        if (val) {
            window.location.href = 'index.php?route=transactions&type=' + val;
        } else {
            window.location.href = 'index.php?route=transactions';
        }
    }
});
</script>
