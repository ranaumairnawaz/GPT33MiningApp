<?php
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='/login';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch all notifications for this user
$stmt = $pdo->prepare("SELECT * FROM user_notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

// Mark all as read when opening this page
$pdo->prepare("UPDATE user_notifications SET is_read = 1 WHERE user_id = ?")->execute([$user_id]);
?>

<div class="full-screen-view" style="min-height: 100vh; background: var(--bs-body-bg); display: flex; flex-direction: column;">
    
    <!-- Header -->
    <header style="padding: 20px; background: var(--bg-card); border-bottom: 1px solid var(--glass-border); display: flex; align-items: center; gap: 15px; position: sticky; top: 0; z-index: 100;">
        <a href="javascript:history.back()" style="color: var(--text-main); font-size: 1.2rem;">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <h2 style="margin: 0; font-size: 1.2rem; font-weight: 800;">Intelligence Alerts</h2>
    </header>

    <!-- Notifications List -->
    <div style="flex: 1; padding: 20px;">
        <?php if (empty($notifications)): ?>
            <div style="text-align: center; margin-top: 100px; opacity: 0.3;">
                <i class="fa-solid fa-bell-slash" style="font-size: 4rem; margin-bottom: 20px;"></i>
                <h3 style="font-size: 1.1rem; font-weight: 700;">No Active Alerts</h3>
                <p style="font-size: 0.8rem;">Your intelligence feed is currently synchronized.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php foreach ($notifications as $n): ?>
                    <a href="<?php echo htmlspecialchars($n['link'] ?: '#'); ?>" data-link style="text-decoration: none; color: inherit;">
                        <div class="card" style="padding: 18px; border: 1px solid <?php echo $n['is_read'] ? 'var(--glass-border)' : 'rgba(59, 130, 246, 0.3)'; ?>; background: <?php echo $n['is_read'] ? 'rgba(255,255,255,0.02)' : 'rgba(59, 130, 246, 0.05)'; ?>; border-radius: 18px; position: relative; overflow: hidden;">
                            <?php if (!$n['is_read']): ?>
                                <div style="position: absolute; top: 12px; right: 12px; width: 8px; height: 8px; background: var(--primary); border-radius: 50%; box-shadow: 0 0 10px var(--primary);"></div>
                            <?php endif; ?>
                            
                            <div style="display: flex; gap: 15px; align-items: flex-start;">
                                <div style="width: 40px; height: 40px; border-radius: 12px; background: <?php echo $n['type'] == 'chat' ? 'rgba(59, 130, 246, 0.1)' : 'rgba(245, 158, 11, 0.1)'; ?>; display: flex; align-items: center; justify-content: center; color: <?php echo $n['type'] == 'chat' ? 'var(--primary)' : '#f59e0b'; ?>; flex-shrink: 0;">
                                    <i class="fa-solid <?php 
                                        switch($n['type']) {
                                            case 'chat': echo 'fa-comments'; break;
                                            case 'deposit': echo 'fa-vault'; break;
                                            case 'withdrawal': echo 'fa-money-bill-transfer'; break;
                                            case 'investment': echo 'fa-microchip'; break;
                                            case 'referral': echo 'fa-users-viewfinder'; break;
                                            case 'yield': echo 'fa-chart-line'; break;
                                            default: echo 'fa-bell';
                                        }
                                    ?>"></i>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-size: 0.9rem; font-weight: 600; line-height: 1.4; margin-bottom: 5px; color: var(--text-main);">
                                        <?php echo htmlspecialchars($n['message']); ?>
                                    </div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted); display: flex; align-items: center; gap: 5px;">
                                        <i class="fa-regular fa-clock"></i>
                                        <?php echo date('M d, H:i', strtotime($n['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<style>
    /* Full screen mode overrides */
    .route-notifications #main-header, 
    .route-notifications #bottom-nav {
        display: none !important;
    }
    
    .route-notifications #app-content {
        padding: 0 !important;
        margin: 0 !important;
    }
</style>
