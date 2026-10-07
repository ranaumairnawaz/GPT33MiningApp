<?php
$stmt = $pdo->query("
    SELECT l.*, u.full_name, u.email 
    FROM admin_logs l 
    JOIN users u ON l.admin_id = u.id 
    ORDER BY l.created_at DESC 
    LIMIT 100
");
$logs = $stmt->fetchAll();
?>

<div class="row mb-5 align-items-center">
    <div class="col">
        <h2 class="fw-800 m-0 text-dark">Activity Intelligence</h2>
        <p class="text-muted m-0">Audit trail of all administrative operations and protocol executions</p>
    </div>
</div>

<div class="card">
    <div class="table-responsive p-4">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Administrator</th>
                    <th>Action Executed</th>
                    <th>Intelligence Data</th>
                    <th>Network ID (IP)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted small opacity-50">No administrative logs detected in the system.</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($logs as $l): ?>
                <tr>
                    <td>
                        <div class="small text-dark fw-bold"><?php echo date('M d, H:i', strtotime($l['created_at'])); ?></div>
                        <div class="text-muted" style="font-size: 0.65rem;"><?php echo date('s', strtotime($l['created_at'])); ?>ms accuracy</div>
                    </td>
                    <td>
                        <div class="fw-bold text-dark small"><?php echo htmlspecialchars($l['full_name']); ?></div>
                        <div class="text-muted" style="font-size: 0.65rem;"><?php echo htmlspecialchars($l['email']); ?></div>
                    </td>
                    <td>
                        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill" style="font-size: 0.6rem;">
                            <?php echo strtoupper(str_replace('_', ' ', $l['action'])); ?>
                        </span>
                    </td>
                    <td>
                        <div class="text-muted small text-truncate" style="max-width: 250px;" title="<?php echo htmlspecialchars($l['details']); ?>">
                            <?php echo htmlspecialchars($l['details']); ?>
                        </div>
                    </td>
                    <td>
                        <code class="text-muted small"><?php echo $l['ip_address']; ?></code>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
