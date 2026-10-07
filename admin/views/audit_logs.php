<?php
$admin_id = $_GET['admin_id'] ?? '';
$action_filter = $_GET['action_type'] ?? '';

$query = "SELECT l.*, u.full_name as admin_name FROM admin_logs l JOIN users u ON l.admin_id = u.id WHERE 1=1";
$params = [];

if ($admin_id) {
    $query .= " AND l.admin_id = ?";
    $params[] = $admin_id;
}

if ($action_filter) {
    $query .= " AND l.action LIKE ?";
    $params[] = "%$action_filter%";
}

$query .= " ORDER BY l.created_at DESC LIMIT 100";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$admins = $pdo->query("SELECT id, full_name FROM users WHERE is_admin = 1")->fetchAll();
?>

<div class="row mb-5 align-items-center">
    <div class="col">
        <h2 class="fw-800 m-0 text-dark">Audit Intelligence</h2>
        <p class="text-muted m-0">Monitor administrative operations and system modifications</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-5 mb-4 p-4">
    <form method="GET" class="row g-3 align-items-end">
        <input type="hidden" name="route" value="audit_logs">
        <div class="col-md-4">
            <label class="form-label fw-bold small text-muted text-uppercase">Admin Node</label>
            <select name="admin_id" class="form-select bg-light border-0 py-3 rounded-4">
                <option value="">All Administrators</option>
                <?php foreach ($admins as $a): ?>
                    <option value="<?php echo $a['id']; ?>" <?php echo $admin_id == $a['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($a['full_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold small text-muted text-uppercase">Action Fragment</label>
            <input type="text" name="action_type" class="form-control bg-light border-0 py-3 rounded-4" placeholder="e.g. balance, ban..." value="<?php echo htmlspecialchars($action_filter); ?>">
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-dark w-100 py-3 fw-bold rounded-4">EXECUTE QUERY</button>
        </div>
    </form>
</div>

<div class="card border-0 shadow-sm rounded-5 overflow-hidden">
    <div class="table-responsive p-4">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th class="ps-3">Timestamp</th>
                    <th>Administrator</th>
                    <th>Action</th>
                    <th>Intelligence Details</th>
                    <th class="text-end pe-3">IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted small">No audit trails detected for this query</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($logs as $l): ?>
                <tr>
                    <td class="ps-3">
                        <div class="text-dark small fw-bold"><?php echo date('M d, H:i:s', strtotime($l['created_at'])); ?></div>
                    </td>
                    <td>
                        <div class="text-dark fw-bold small"><?php echo htmlspecialchars($l['admin_name']); ?></div>
                        <div class="text-muted small" style="font-size: 0.6rem;">ID: #<?php echo $l['admin_id']; ?></div>
                    </td>
                    <td>
                        <span class="badge bg-dark bg-opacity-10 text-dark rounded-pill px-3 fw-bold" style="font-size: 0.6rem;">
                            <?php echo strtoupper(str_replace('_', ' ', $l['action'])); ?>
                        </span>
                    </td>
                    <td>
                        <div class="text-muted small fw-bold" style="word-break: break-all; max-width: 300px;">
                            <?php echo htmlspecialchars($l['details']); ?>
                        </div>
                    </td>
                    <td class="text-end pe-3">
                        <code class="small text-primary fw-bold"><?php echo $l['ip_address']; ?></code>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
