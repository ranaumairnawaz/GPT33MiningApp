<?php
$stmt = $pdo->query("SELECT * FROM users WHERE is_admin = 1 ORDER BY created_at DESC");
$admins = $stmt->fetchAll();
?>

<div class="row mb-5 align-items-center">
    <div class="col">
        <h2 class="fw-800 m-0 text-dark">Security Council</h2>
        <p class="text-muted m-0">Manage institutional administrators and access control protocols</p>
    </div>
    <div class="col-auto">
        <button class="btn btn-dark px-4 py-2 fw-bold rounded-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addAdminModal">
            <i class="fa-solid fa-plus me-2"></i> AUTHORIZE NEW ADMIN
        </button>
    </div>
</div>

<div class="card">
    <div class="table-responsive p-4">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Admin Identity</th>
                    <th>Email Protocol</th>
                    <th>Authorization Date</th>
                    <th>Status</th>
                    <th class="text-end">Operations</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $a): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div style="width: 40px; height: 40px; background: var(--bs-body-bg); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--text-main); font-weight: 800; border: 1px solid var(--border-color);">
                                <?php echo strtoupper(substr($a['full_name'], 0, 1)); ?>
                            </div>
                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($a['full_name']); ?></div>
                        </div>
                    </td>
                    <td>
                        <span class="text-muted small fw-bold"><?php echo htmlspecialchars($a['email']); ?></span>
                    </td>
                    <td>
                        <span class="text-muted small"><?php echo date('M d, Y', strtotime($a['created_at'])); ?></span>
                    </td>
                    <td>
                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">ACTIVE</span>
                    </td>
                    <td class="text-end">
                        <?php if ($a['id'] != $_SESSION['user_id']): ?>
                        <a href="/api/admin_actions?action=revoke_admin&user_id=<?php echo $a['id']; ?>" class="btn btn-sm btn-outline-danger px-3 fw-bold rounded-3" onclick="return confirm('Revoke administrative privileges from this node?')">
                            REVOKE ACCESS
                        </a>
                        <?php else: ?>
                            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill">YOU</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 p-4">
                <h5 class="fw-800 text-dark m-0">Authorize Institutional Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form data-ajax-form data-reload action="/api/admin_actions?action=authorize_admin" method="POST">
                <div class="modal-body p-4 pt-0">
                    <p class="text-muted small mb-4">Enter the email address of an existing user to grant them administrative privileges.</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase">User Email Address</label>
                        <input type="email" name="email" class="form-control py-3" placeholder="user@example.com" required>
                    </div>
                    <div class="alert alert-warning border-0 rounded-3 small">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i> This user will have full access to all administrative functions.
                    </div>
                </div>
                <div class="p-4 pt-0">
                    <button type="submit" class="btn btn-dark w-100 py-3 fw-bold rounded-3 shadow-sm">GRANT PRIVILEGES</button>
                </div>
            </form>
        </div>
    </div>
</div>
