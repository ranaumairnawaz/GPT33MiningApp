<?php
$stmt = $pdo->query("
    SELECT i.*, u.full_name, u.email, p.name as plan_name, p.hourly_rate 
    FROM user_investments i 
    JOIN users u ON i.user_id = u.id 
    JOIN plans p ON i.plan_id = p.id 
    ORDER BY i.created_at DESC
");
$investments = $stmt->fetchAll();
?>

<div class="row mb-5 align-items-center">
    <div class="col">
        <h2 class="fw-800 m-0 text-dark">Active Asset Monitoring</h2>
        <p class="text-muted m-0">Live tracking of all institutional computing stakes</p>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="table-responsive p-4">
        <table class="table align-middle datatable-basic">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Initiation Date</th>
                    <th>Investor Entity</th>
                    <th>Architecture Plan</th>
                    <th>Principal Stake</th>
                    <th>Accumulated Yield</th>
                    <th>Status</th>
                    <th class="text-end">Last Sync</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($investments as $i): ?>
                <tr>
                    <td>
                        <span class="fw-800 text-muted small">#<?php echo $i['id']; ?></span>
                    </td>
                    <td>
                        <div class="small text-dark fw-bold"><?php echo date('M d, Y', strtotime($i['created_at'])); ?></div>
                        <div class="text-muted" style="font-size: 0.65rem;">System Authenticated</div>
                    </td>
                    <td>
                        <div class="fw-bold text-dark small"><?php echo htmlspecialchars($i['full_name']); ?></div>
                        <div class="text-muted" style="font-size: 0.65rem;"><?php echo htmlspecialchars($i['email']); ?></div>
                    </td>
                    <td>
                        <div class="text-dark small fw-bold text-uppercase"><?php echo htmlspecialchars($i['plan_name']); ?></div>
                        <div class="text-primary fw-bold" style="font-size: 0.65rem;"><?php echo $i['hourly_rate']; ?>% HOURLY</div>
                    </td>
                    <td class="text-dark fw-800 small">$<?php echo number_format($i['invested_amount'], 2); ?></td>
                    <td class="text-success fw-800 small">+$<?php echo number_format($i['total_profit'], 4); ?></td>
                    <td>
                        <span class="badge bg-<?php echo $i['status'] == 'active' ? 'success' : 'secondary'; ?> bg-opacity-10 text-<?php echo $i['status'] == 'active' ? 'success' : 'secondary'; ?>">
                            <?php echo strtoupper($i['status']); ?>
                        </span>
                    </td>
                    <td class="text-end small text-muted">
                        <div class="text-dark fw-bold"><?php echo date('H:i:s', strtotime($i['last_profit_calc'])); ?></div>
                        <div style="font-size: 0.65rem;">UTC SYNC</div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
