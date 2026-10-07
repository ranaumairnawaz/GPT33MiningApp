<?php
$stmt = $pdo->query("SELECT * FROM banned_ips ORDER BY created_at DESC");
$ips = $stmt->fetchAll();
?>

<div class="row mb-5 align-items-center">
    <div class="col">
        <h2 class="fw-800 m-0 text-dark">Security Intelligence</h2>
        <p class="text-muted m-0">Manage network access and monitor perimeter threats</p>
    </div>
</div>

<div class="row g-4">
    <!-- IP Blacklist -->
    <div class="col-lg-8">
        <div class="card h-100 overflow-hidden">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-800 m-0 text-dark">Global IP Blacklist</h5>
                <span class="badge bg-danger bg-opacity-10 text-danger"><?php echo count($ips); ?> Quarantine Nodes</span>
            </div>
            <div class="table-responsive p-4">
                <table class="table align-middle datatable-basic">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Network Address</th>
                            <th>Quarantine Date</th>
                            <th>Intelligence Reason</th>
                            <th class="text-end">Operations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ips as $ip): ?>
                        <tr>
                            <td><span class="fw-800 text-muted small">#<?php echo $ip['id']; ?></span></td>
                            <td><code class="text-primary fw-bold" style="letter-spacing: 0.05em;"><?php echo htmlspecialchars($ip['ip_address']); ?></code></td>
                            <td class="small text-muted fw-bold"><?php echo date('M d, Y H:i', strtotime($ip['created_at'])); ?></td>
                            <td class="small text-muted italic"><?php echo htmlspecialchars($ip['reason'] ?? 'Manual quarantine'); ?></td>
                            <td class="text-end">
                                <a href="/api/admin_actions?action=unban_ip&id=<?php echo $ip['id']; ?>" class="btn btn-sm btn-light border px-3 fw-bold rounded-3">
                                    <i class="fa-solid fa-unlock-keyhole me-1"></i> RELEASE
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Manual Block Form -->
    <div class="col-lg-4">
        <div class="card mb-4 p-4">
            <div class="card-header border-0 p-0 mb-4">
                <h5 class="fw-800 m-0 text-dark">Manual Quarantine</h5>
            </div>
            <form action="/api/admin_actions?action=ban_ip" method="POST">
                <div class="mb-4">
                    <label class="form-label fw-bold small text-muted text-uppercase">Target IP Address</label>
                    <input type="text" name="ip" class="form-control bg-light border-0 py-3 rounded-4" placeholder="e.g. 154.22.4.9" required>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold small text-muted text-uppercase">Security Reason</label>
                    <textarea name="reason" class="form-control bg-light border-0 py-3 rounded-4" rows="3" placeholder="Identify suspicious behavior patterns..."></textarea>
                </div>
                <button type="submit" class="btn btn-danger w-100 fw-bold py-3 rounded-4 shadow-sm">
                    <i class="fa-solid fa-ban me-2"></i> Execute Network Block
                </button>
            </form>
        </div>

        <div class="p-4 rounded-5 bg-primary bg-opacity-5 border-0">
            <div class="d-flex align-items-center gap-3 text-primary">
                <div class="icon-shape bg-primary bg-opacity-10 text-primary rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <h6 class="mb-1 fw-800">Perimeter Protocol</h6>
                    <p class="small mb-0 opacity-75 fw-bold" style="line-height: 1.4;">Instant revocation of all institutional interfaces for the target network signature.</p>
                </div>
            </div>
        </div>
    </div>
</div>
