<?php
$stmt = $pdo->query("
    SELECT t.*, u.full_name, u.email 
    FROM transactions t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.type = 'deposit' 
    ORDER BY t.created_at DESC
");
$deposits = $stmt->fetchAll();
?>

<div class="row mb-5 align-items-center">
    <div class="col">
        <h2 class="fw-800 m-0 text-dark">Inflow Verification</h2>
        <p class="text-muted m-0">Validate and approve institutional capital funding requests</p>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="table-responsive p-4">
        <table class="table align-middle datatable-basic">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Investor Identity</th>
                    <th>Funding Amount</th>
                    <th>Gateway Signature</th>
                    <th>Receipt Date</th>
                    <th>State</th>
                    <th class="text-end">Operations</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($deposits)): ?>
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted small opacity-50">No pending funding requests in the queue.</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($deposits as $d): ?>
                <tr>
                    <td>
                        <span class="fw-800 text-muted small">#<?php echo $d['id']; ?></span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div style="width: 40px; height: 40px; background: #f3f4f6; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #1f2937; font-weight: 800;">
                                <?php echo strtoupper(substr($d['full_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <div class="fw-bold text-dark small"><?php echo htmlspecialchars($d['full_name']); ?></div>
                                <div class="text-muted" style="font-size: 0.65rem;"><?php echo htmlspecialchars($d['email']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="fw-800 text-primary small">$<?php echo number_format($d['amount'], 2); ?></div>
                    </td>
                    <td>
                        <div class="text-dark small fw-bold text-uppercase"><?php echo htmlspecialchars($d['method_name']); ?></div>
                        <code class="text-muted" style="font-size: 0.65rem;"><?php echo htmlspecialchars($d['account_details']); ?></code>
                    </td>
                    <td>
                        <div class="small text-dark fw-bold"><?php echo date('M d, Y', strtotime($d['created_at'])); ?></div>
                        <div class="text-muted" style="font-size: 0.65rem;"><?php echo date('H:i A', strtotime($d['created_at'])); ?> UTC</div>
                    </td>
                    <td>
                        <?php 
                        $badge_class = 'bg-secondary';
                        if ($d['status'] === 'pending') $badge_class = 'bg-warning';
                        if ($d['status'] === 'approved') $badge_class = 'bg-success';
                        if ($d['status'] === 'rejected') $badge_class = 'bg-danger';
                        ?>
                        <span class="badge <?php echo $badge_class; ?> bg-opacity-10 text-<?php echo str_replace('bg-', '', $badge_class); ?>">
                            <?php echo strtoupper($d['status']); ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <?php if ($d['status'] === 'pending'): ?>
                        <div class="d-flex gap-2 justify-content-end">
                            <button onclick='verifyTransaction(<?php echo json_encode($d); ?>)' class="btn btn-sm btn-dark px-3 fw-bold rounded-3 shadow-sm">
                                <i class="fa-solid fa-microscope me-1"></i> VERIFY
                            </button>
                        </div>
                        <?php else: ?>
                            <span class="text-muted small fw-bold">ARCHIVED</span>
                        <?php endif; ?>
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
    };

    window.submitAudit = async function(status) {
        if (!activeTx) return;
        const action = activeTx.type === 'deposit' ? 'update_deposit_status' : 'update_withdrawal_status';
        try {
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
});
</script>
