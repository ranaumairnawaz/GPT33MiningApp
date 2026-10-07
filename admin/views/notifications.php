<?php
// Mark all as read if requested
if (isset($_GET['mark_all_read'])) {
    $pdo->exec("UPDATE admin_notifications SET is_read = 1");
    header("Location: index.php?route=notifications");
    exit;
}

$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$total_stmt = $pdo->query("SELECT COUNT(*) FROM admin_notifications");
$total = $total_stmt->fetchColumn();
$total_pages = ceil($total / $limit);

$stmt = $pdo->prepare("SELECT n.*, u.full_name as user_name FROM admin_notifications n LEFT JOIN users u ON n.user_id = u.id ORDER BY n.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$limit, $offset]);
$notifications = $stmt->fetchAll();
?>

<div class="row mb-5 align-items-center">
    <div class="col">
        <h2 class="fw-800 m-0 text-dark">System Alerts</h2>
        <p class="text-muted m-0">Historical log of all administrative triggers and user actions</p>
    </div>
    <div class="col-auto">
        <a href="index.php?route=notifications&mark_all_read=1" class="btn btn-light border fw-bold px-4 rounded-pill">
            <i class="fa-solid fa-check-double me-2"></i> Mark All as Read
        </a>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="table-responsive p-0">
        <table class="table align-middle m-0">
            <thead>
                <tr class="bg-light bg-opacity-50">
                    <th class="ps-4">Timestamp</th>
                    <th>Intelligence Node</th>
                    <th>Alert Content</th>
                    <th>Protocol Type</th>
                    <th class="text-end pe-4">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($notifications)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted opacity-50">No administrative alerts recorded.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($notifications as $n): ?>
                <tr class="<?php echo !$n['is_read'] ? 'bg-primary bg-opacity-5' : ''; ?>">
                    <td class="ps-4">
                        <div class="small fw-bold text-dark"><?php echo date('M d, H:i', strtotime($n['created_at'])); ?></div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width: 28px; height: 28px; background: #e2e8f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: 800;">
                                <?php echo $n['user_name'] ? strtoupper(substr($n['user_name'], 0, 1)) : 'S'; ?>
                            </div>
                            <span class="small fw-bold"><?php echo htmlspecialchars($n['user_name'] ?? 'System'); ?></span>
                        </div>
                    </td>
                    <td>
                        <div class="small <?php echo !$n['is_read'] ? 'fw-bold text-dark' : 'text-muted'; ?>">
                            <?php echo htmlspecialchars($n['message']); ?>
                        </div>
                    </td>
                    <td>
                        <?php
                        $color = 'secondary';
                        $icon = 'fa-bell';
                        switch($n['type']) {
                            case 'deposit': $color = 'success'; $icon = 'fa-vault'; break;
                            case 'withdrawal': $color = 'warning'; $icon = 'fa-money-bill-transfer'; break;
                            case 'chat': $color = 'info'; $icon = 'fa-comments'; break;
                            case 'registration': $color = 'primary'; $icon = 'fa-user-plus'; break;
                        }
                        ?>
                        <span class="badge bg-<?php echo $color; ?> bg-opacity-10 text-<?php echo $color; ?> small" style="font-size: 0.6rem;">
                            <i class="fa-solid <?php echo $icon; ?> me-1"></i> <?php echo strtoupper($n['type']); ?>
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <a href="<?php echo $n['link']; ?>" class="btn btn-sm btn-light border fw-bold px-3">
                            <i class="fa-solid fa-arrow-right-long"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($total_pages > 1): ?>
<div class="d-flex justify-content-center mt-4">
    <nav>
        <ul class="pagination gap-2">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link border-0 rounded-3 fw-bold" href="index.php?route=notifications&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>
<?php endif; ?>
