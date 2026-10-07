<?php
$stmt = $pdo->query("SELECT * FROM users WHERE is_admin = 0 ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>

<div class="row mb-5 align-items-center">
    <div class="col">
        <h2 class="fw-800 m-0 text-dark">Network Intelligence</h2>
        <p class="text-muted m-0">Review and coordinate all registered platform participants</p>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="table-responsive p-4">
        <table class="table align-middle datatable-basic">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Investor Identity</th>
                    <th>Financial Profile</th>
                    <th>Security Node</th>
                    <th>Activation Date</th>
                    <th>Status</th>
                    <th>Tier</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <span class="fw-800 text-muted small">#<?php echo $u['id']; ?></span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div
                                    style="width: 40px; height: 40px; background: #f3f4f6; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #1f2937; font-weight: 800;">
                                    <?php echo strtoupper(substr($u['full_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <a href="index.php?route=user_details&id=<?php echo $u['id']; ?>"
                                        class="fw-bold text-dark text-decoration-none d-block">
                                        <?php echo htmlspecialchars($u['full_name']); ?>
                                    </a>
                                    <div class="text-muted small"><?php echo htmlspecialchars($u['email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="fw-bold text-dark small">$<?php echo number_format($u['deposit_balance'], 2); ?>
                                <span class="text-muted fw-normal" style="font-size: 0.65rem;">DEP</span></div>
                            <div class="text-primary small fw-bold">$<?php echo number_format($u['earnings_balance'], 2); ?>
                                <span class="text-muted fw-normal" style="font-size: 0.65rem;">EARN</span></div>
                        </td>
                        <td>
                            <div class="small text-dark fw-bold"><?php echo $u['last_login_ip'] ?: '0.0.0.0'; ?></div>
                            <div class="text-muted" style="font-size: 0.65rem;">Last Access IP</div>
                        </td>
                        <td class="text-muted small">
                            <?php echo date('M d, Y', strtotime($u['created_at'])); ?>
                        </td>
                        <td>
                            <?php if ($u['is_banned']): ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger">QUARANTINED</span>
                            <?php else: ?>
                                <span class="badge bg-success bg-opacity-10 text-success">ACTIVE NODE</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-primary bg-opacity-10 text-primary fw-bold" style="font-size: 0.6rem;">
                                <i class="fa-solid fa-crown me-1"></i> <?php echo strtoupper($u['tier'] ?? 'BRONZE'); ?>
                            </span>
                        </td>
                        <td class="text-end">
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="/api/admin_actions?action=login_as_user&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-light border fw-bold px-3 rounded-pill shadow-sm text-primary" title="Impersonate Node">
                                <i class="fa-solid fa-user-secret"></i>
                            </a>
                            <a href="index.php?route=user_details&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-light border fw-bold px-4 rounded-pill shadow-sm">
                                <i class="fa-solid fa-eye me-1"></i> VIEW HUB
                            </a>
                        </div>
                    </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

