<?php
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='/login';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user data for total stats
$stmt = $pdo->prepare("SELECT deposit_balance, earnings_balance FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch active investment stats
$stmt = $pdo->prepare("SELECT SUM(invested_amount) as total_active FROM user_investments WHERE user_id = ? AND status = 'active'");
$stmt->execute([$user_id]);
$total_active = $stmt->fetchColumn() ?: 0;

// Fetch user investments
$stmt = $pdo->prepare("
    SELECT ui.*, p.name as plan_name, p.hourly_rate 
    FROM user_investments ui
    JOIN plans p ON ui.plan_id = p.id
    WHERE ui.user_id = ?
    ORDER BY ui.created_at DESC
");
$stmt->execute([$user_id]);
$investments = $stmt->fetchAll();
?>

<div class="full-screen-view" style="min-height: 100vh; background: var(--bs-body-bg); display: flex; flex-direction: column;">
    <header class="view-header">
        <a href="javascript:history.back()"><i class="fa-solid fa-arrow-left"></i></a>
        <h2>Active Stakes</h2>
    </header>

    <div class="investments-container" style="padding: 20px; padding-bottom: 100px; flex: 1;">

    <!-- Quick Stats Overview -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px;">
        <div class="card" style="margin-bottom: 0; padding: 15px; background: linear-gradient(135deg, rgba(5, 150, 105, 0.1) 0%, transparent 100%);">
            <div style="font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;">Active Capital</div>
            <div style="font-size: 1.2rem; font-weight: 800; color: var(--primary);">$<?php echo number_format($total_active, 2); ?></div>
        </div>
        <div class="card" style="margin-bottom: 0; padding: 15px; background: linear-gradient(135deg, rgba(52, 211, 153, 0.1) 0%, transparent 100%);">
            <div style="font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;">Total Nodes</div>
            <div style="font-size: 1.2rem; font-weight: 800; color: var(--accent);"><?php echo count($investments); ?> Units</div>
        </div>
    </div>

    <?php if (empty($investments)): ?>
        <div class="card" style="text-align: center; padding: 60px 20px; border-style: dashed; background: transparent;">
            <div style="width: 80px; height: 80px; background: var(--glass); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px auto; color: var(--text-muted);">
                <i class="fa-solid fa-microchip" style="font-size: 2.5rem; opacity: 0.3;"></i>
            </div>
            <h5 style="font-weight: 700; margin-bottom: 10px;">No Active Clusters</h5>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 25px;">Initialize your first AI computing unit to start generating passive yield.</p>
            <a href="invest" data-link class="btn" style="width: auto; padding: 15px 40px; box-shadow: 0 10px 20px var(--primary-glow);">
                <i class="fa-solid fa-plus me-2"></i> Launch New Unit
            </a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <?php foreach ($investments as $i): ?>
                <div class="card" style="padding: 0; position: relative; overflow: hidden; border: 1px solid <?php echo $i['status'] === 'active' ? 'var(--glass-border)' : 'rgba(255,255,255,0.05)'; ?>;">
                    
                    <!-- Progress Header -->
                    <div style="padding: 20px; background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 38px; height: 38px; border-radius: 10px; background: <?php echo $i['status'] === 'active' ? 'var(--primary)' : 'var(--text-muted)'; ?>; display: flex; align-items: center; justify-content: center; color: white; box-shadow: <?php echo $i['status'] === 'active' ? '0 0 15px var(--primary-glow)' : 'none'; ?>;">
                                <i class="fa-solid fa-server" style="font-size: 1rem;"></i>
                            </div>
                            <div>
                                <div style="font-size: 0.85rem; font-weight: 800; color: var(--text-main);"><?php echo htmlspecialchars($i['plan_name']); ?></div>
                                <div style="font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase;"><?php echo $i['status'] === 'active' ? 'Node Online' : 'Node Offline'; ?></div>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <span class="badge" style="background: <?php echo $i['status'] === 'active' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(255,255,255,0.05)'; ?>; color: <?php echo $i['status'] === 'active' ? 'var(--accent)' : 'var(--text-muted)'; ?>; padding: 6px 12px; border-radius: 8px; font-size: 0.65rem; font-weight: 800; letter-spacing: 0.5px; border: 1px solid <?php echo $i['status'] === 'active' ? 'rgba(16, 185, 129, 0.2)' : 'transparent'; ?>;">
                                <?php echo strtoupper($i['status']); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Core Metrics -->
                    <div style="padding: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <div style="font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">Capital Magnitude</div>
                            <div style="display: flex; align-items: baseline; gap: 4px;">
                                <span style="font-size: 1.3rem; font-weight: 900; color: white;">$<?php echo number_format($i['invested_amount'], 2); ?></span>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">USD</span>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">Protocol Yield</div>
                            <div style="font-size: 1.2rem; font-weight: 800; color: var(--accent);"><?php echo number_format($i['hourly_rate'], 4); ?>% <span style="font-size: 0.7rem; color: var(--text-muted); font-weight: 400;">/hr</span></div>
                        </div>
                    </div>

                    <!-- Real-time Accrual Footer -->
                    <div style="padding: 15px 20px; background: rgba(5, 150, 105, 0.03); display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--glass-border);">
                        <div>
                            <div style="font-size: 0.6rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 2px;">Accumulated Intelligence</div>
                            <div style="font-weight: 800; color: var(--primary); font-size: 1rem;">
                                +$<?php 
                                    $hours = (time() - strtotime($i['created_at'])) / 3600;
                                    $earned = $i['invested_amount'] * ($i['hourly_rate'] / 100) * $hours;
                                    echo number_format($earned, 4);
                                ?>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 0.6rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 2px;">Timestamp</div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: var(--text-main);"><i class="fa-regular fa-clock me-1"></i> <?php echo date('M d, H:i', strtotime($i['created_at'])); ?></div>
                        </div>
                    </div>

                    <!-- Glowing Indicator (Only for active) -->
                    <?php if ($i['status'] === 'active'): ?>
                        <div style="position: absolute; bottom: 0; left: 0; width: 100%; height: 2px; background: linear-gradient(90deg, transparent, var(--primary), transparent); opacity: 0.5;"></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .investments-container .card {
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s ease;
    }
    .investments-container .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        border-color: var(--primary) !important;
    }
</style>
</div>
