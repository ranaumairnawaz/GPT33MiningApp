<?php
$stmt = $pdo->query("
    SELECT t.*, u.full_name, u.email 
    FROM transactions t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.type = 'withdrawal' 
    ORDER BY t.created_at DESC
");
$withdrawals = $stmt->fetchAll();
?>

<div class="row mb-5 align-items-center">
    <div class="col">
        <h2 class="fw-800 m-0 text-dark">Outflow Disbursement</h2>
        <p class="text-muted m-0">Process and approve institutional capital payout transactions</p>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="table-responsive p-4">
        <table class="table align-middle datatable-basic">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Recipient Identity</th>
                    <th>Disbursement Amount</th>
                    <th>Gateway Signature</th>
                    <th>Request Date</th>
                    <th>State</th>
                    <th class="text-end">Operations</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($withdrawals as $w): ?>
                <tr>
                    <td>
                        <span class="fw-800 text-muted small">#<?php echo $w['id']; ?></span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div style="width: 40px; height: 40px; background: #f3f4f6; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #1f2937; font-weight: 800;">
                                <?php echo strtoupper(substr($w['full_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <div class="fw-bold text-dark small"><?php echo htmlspecialchars($w['full_name']); ?></div>
                                <div class="text-muted" style="font-size: 0.65rem;"><?php echo htmlspecialchars($w['email']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="fw-800 text-danger small">$<?php echo number_format($w['amount'], 2); ?></div>
                    </td>
                    <td>
                        <div class="text-dark small fw-bold text-uppercase"><?php echo htmlspecialchars($w['method_name']); ?></div>
                        <div class="d-flex align-items-center gap-2">
                            <code class="text-muted" id="acc-<?php echo $w['id']; ?>" style="font-size: 0.65rem;"><?php echo htmlspecialchars($w['account_details']); ?></code>
                            <button class="btn btn-link p-0 text-muted" onclick="copyToClipboard('<?php echo addslashes($w['account_details']); ?>')">
                                <i class="fa-regular fa-copy" style="font-size: 0.7rem;"></i>
                            </button>
                        </div>
                    </td>
                    <td>
                        <div class="small text-dark fw-bold"><?php echo date('M d, Y', strtotime($w['created_at'])); ?></div>
                        <div class="text-muted" style="font-size: 0.65rem;"><?php echo date('H:i A', strtotime($w['created_at'])); ?> UTC</div>
                    </td>
                    <td>
                        <?php 
                        $badge_class = 'bg-secondary';
                        if ($w['status'] === 'pending') $badge_class = 'bg-warning';
                        if ($w['status'] === 'approved') $badge_class = 'bg-success';
                        if ($w['status'] === 'rejected') $badge_class = 'bg-danger';
                        ?>
                        <span class="badge <?php echo $badge_class; ?> bg-opacity-10 text-<?php echo str_replace('bg-', '', $badge_class); ?>">
                            <?php echo strtoupper($w['status'] === 'approved' ? 'PAID' : $w['status']); ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <?php if ($w['status'] === 'pending'): ?>
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="/api/admin_actions?action=update_withdrawal_status&id=<?php echo $w['id']; ?>&status=approved" class="btn btn-sm btn-dark px-3 fw-bold rounded-3 shadow-sm">
                                <i class="fa-solid fa-check me-1"></i> MARK PAID
                            </a>
                            <a href="/api/admin_actions?action=update_withdrawal_status&id=<?php echo $w['id']; ?>&status=rejected" class="btn btn-sm btn-light border px-3 fw-bold rounded-3">
                                <i class="fa-solid fa-ban me-1"></i> REJECT
                            </a>
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
