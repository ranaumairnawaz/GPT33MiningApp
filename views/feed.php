<?php
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='/login';</script>";
    exit;
}

// Fetch total users count & base follower count for dynamic followers count
try {
    $user_count_stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $real_users = (int)$user_count_stmt->fetchColumn();
    
    $settings_stmt = $pdo->query("SELECT broadcast_followers_base FROM settings LIMIT 1");
    $settings = $settings_stmt->fetch();
    $base_followers = (int)($settings['broadcast_followers_base'] ?? 3450);
} catch (Exception $e) {
    $real_users = 0;
    $base_followers = 3450;
}
$followers_count = $base_followers + $real_users;
?>

<div class="full-screen-view" style="height: 100vh; max-height: 100vh; background: radial-gradient(circle at top, #1e293b, #0f172a); display: flex; flex-direction: column; position: relative; overflow: hidden;">
    
    <!-- Telegram Channel Header -->
    <header style="display: flex; align-items: center; gap: 15px; padding: 15px 20px; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255,255,255,0.08); position: fixed; top: 0; left: 0; right: 0; height: 75px; z-index: 100; box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);">
        <a href="dashboard" data-link style="color: white; font-size: 1.2rem; text-decoration: none; padding-right: 5px; display: flex; align-items: center;">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        
        <!-- Animated Channel Avatar -->
        <div style="position: relative; display: flex; align-items: center;">
            <div style="width: 45px; height: 45px; border-radius: 50%; background: linear-gradient(135deg, #0ea5e9, #2563eb); display: flex; align-items: center; justify-content: center; box-shadow: 0 0 15px rgba(14, 165, 233, 0.4); border: 2px solid rgba(255,255,255,0.1);">
                <i class="fa-solid fa-bullhorn" style="font-size: 1.2rem; color: white;"></i>
            </div>
            <span style="position: absolute; bottom: 0; right: 0; width: 12px; height: 12px; background: #10b981; border-radius: 50%; border: 2px solid #0f172a;" title="Live Connection Active"></span>
        </div>
        
        <!-- Channel Info -->
        <div style="flex: 1;">
            <h3 style="margin: 0; font-size: 1rem; font-weight: 800; color: white; display: flex; align-items: center; gap: 6px;">
                Broadcast Feed
                <i class="fa-solid fa-circle-check" style="color: #0ea5e9; font-size: 0.85rem;" title="Verified Broadcast Channel"></i>
            </h3>
            <div style="font-size: 0.7rem; color: #94a3b8; display: flex; align-items: center; gap: 8px;">
                <span><?php echo number_format($followers_count); ?> followers</span>
                <span style="width: 3px; height: 3px; background: #475569; border-radius: 50%;"></span>
                <span style="color: #38bdf8; font-weight: 600;">official broadcast</span>
            </div>
        </div>

        <button onclick="refreshFeed()" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); width: 38px; height: 38px; border-radius: 50%; color: white; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.3s;">
            <i class="fa-solid fa-arrows-rotate" id="refresh-icon"></i>
        </button>
    </header>

    <!-- Simulated Telegram Wallpaper Grid -->
    <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-image: radial-gradient(rgba(255,255,255,0.02) 1px, transparent 1px); background-size: 20px 20px; pointer-events: none; z-index: 1;"></div>

    <!-- Live Channel Messages Container -->
    <div id="feed-container" style="flex: 1; overflow-y: auto; padding: 95px 20px 90px 20px; display: flex; flex-direction: column-reverse; gap: 20px; z-index: 2; scroll-behavior: smooth; height: 100vh; max-height: 100vh;">
        <!-- Dynamically loaded messages render here (Newest at top) -->
        <div id="feed-loader" style="text-align: center; padding: 40px 0; color: var(--text-muted);">
            <div class="spinner-border text-primary" role="status" style="width: 2.5rem; height: 2.5rem;">
                <span class="visually-hidden">Syncing signal feeds...</span>
            </div>
            <p style="margin-top: 15px; font-size: 0.85rem; letter-spacing: 1px; text-transform: uppercase;">Syncing cryptographic feeds...</p>
        </div>
    </div>

    <!-- Muted Telegram Channel Broadcast Bottom Bar -->
    <footer style="background: rgba(15, 23, 42, 0.9); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border-top: 1px solid rgba(255,255,255,0.08); padding: 15px 20px; text-align: center; position: fixed; bottom: 0; left: 0; right: 0; height: 70px; z-index: 100; display: flex; align-items: center; justify-content: center; box-shadow: 0 -4px 30px rgba(0, 0, 0, 0.2);">
        <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255,255,255,0.05); padding: 8px 20px; border-radius: 30px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-size: 0.75rem; color: #94a3b8; max-width: 90%;">
            <i class="fa-solid fa-bell-slash" style="color: #64748b; font-size: 0.75rem;"></i>
            <span>Muted. You can only view broadcasts sent by administrators.</span>
        </div>
    </footer>

</div>

<script>
    // Live Feed Scripting
    (function() {
        let isRefreshing = false;

        const loadFeed = async (silent = false) => {
            if (isRefreshing) return;
            isRefreshing = true;
            
            const loader = document.getElementById('feed-loader');
            const icon = document.getElementById('refresh-icon');
            if (icon) icon.classList.add('fa-spin');

            try {
                const response = await fetch('api/feed', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();

                if (data.status === 'success') {
                    renderFeed(data.feed);
                }
            } catch (e) {
                console.error("Live feed fetch failure:", e);
            } finally {
                isRefreshing = false;
                if (icon) icon.classList.remove('fa-spin');
                if (loader) loader.style.display = 'none';
            }
        };

        const renderFeed = (items) => {
            const container = document.getElementById('feed-container');
            if (!container) return;

            if (items.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 40px; background: rgba(255,255,255,0.02); border-radius: 15px; border: 1px solid rgba(255,255,255,0.05);">
                        <i class="fa-solid fa-satellite-dish" style="font-size: 2.5rem; color: var(--primary); opacity: 0.5; margin-bottom: 15px;"></i>
                        <h4 style="margin: 0; font-size: 1rem; color: white;">Awaiting Server Approvals</h4>
                        <p style="font-size: 0.75rem; color: #94a3b8; margin: 5px 0 0 0;">Platform distributions will broadcast in real-time as administrators complete pending withdrawal tickets.</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = items.map(item => {
                const isWithdrawal = item.type === 'withdrawal';
                const badgeColor = isWithdrawal ? '#10b981' : '#a855f7';
                const badgeIcon = isWithdrawal ? 'fa-wallet' : 'fa-bullhorn';
                const badgeLabel = isWithdrawal ? 'CAPITAL DISBURSEMENT' : 'OFFICIAL NOTICE';
                
                return `
                    <div class="feed-bubble-card" style="align-self: flex-start; max-width: 95%; width: 100%; max-width: 450px; background: rgba(30, 41, 59, 0.75); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.08); border-radius: 18px; padding: 18px; display: flex; flex-direction: column; gap: 12px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3); animation: slideUp 0.3s ease-out; position: relative; border-left: 4px solid ${badgeColor}; box-sizing: border-box;">
                        
                        <!-- Top Category Tag -->
                        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.65rem;">
                            <span style="background: ${badgeColor}15; color: ${badgeColor}; padding: 4px 10px; border-radius: 30px; font-weight: 800; letter-spacing: 0.5px; display: flex; align-items: center; gap: 5px; text-transform: uppercase;">
                                <i class="fa-solid ${badgeIcon}"></i> ${badgeLabel}
                            </span>
                            <span style="color: #64748b; font-family: monospace;">${item.time_formatted}</span>
                        </div>
                        
                        <!-- Rich Message Body -->
                        <div style="font-size: 0.85rem; line-height: 1.6; color: #e2e8f0; font-family: 'Inter', sans-serif;">
                            ${item.message}
                        </div>
                        
                        <!-- High Converting Quick Action Link (Entices user to deposit) -->
                        <div style="margin-top: 5px; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 12px; display: flex; justify-content: flex-end;">
                            <a href="invest" data-link style="background: linear-gradient(135deg, ${badgeColor}20, ${badgeColor}35); border: 1px solid ${badgeColor}40; color: white; padding: 8px 16px; border-radius: 10px; font-size: 0.75rem; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 6px; transition: 0.2s;" onmouseover="this.style.background='${badgeColor}'" onmouseout="this.style.background='linear-gradient(135deg, ${badgeColor}20, ${badgeColor}35)'">
                                <i class="fa-solid fa-bolt" style="color: #ef4444;"></i>
                                ${isWithdrawal ? 'Stake Assets & Earn Passive Yield' : 'View Investment Architectures'}
                                <i class="fa-solid fa-arrow-right" style="font-size: 0.65rem;"></i>
                            </a>
                        </div>

                        <!-- Telegram bubble corner decoration simulating Telegram's message tail -->
                        <div style="position: absolute; bottom: 10px; left: -8px; width: 0; height: 0; border-top: 8px solid transparent; border-bottom: 8px solid transparent; border-right: 8px solid rgba(30, 41, 59, 0.7); pointer-events: none;"></div>
                    </div>
                `;
            }).join('');
        };

        // Make refreshFeed globally accessible
        window.refreshFeed = () => {
            loadFeed();
        };

        // Initial trigger
        loadFeed();

        // Background polling loop - queries every 6 seconds to capture new withdrawals dynamically
        let pollInterval = setInterval(() => {
            if (!document.getElementById('feed-container')) {
                clearInterval(pollInterval);
                return;
            }
            loadFeed(true);
        }, 6000);

    })();
</script>

<style>
    /* Styling upgrades for Telegram channel elements */
    .feed-bubble-card {
        transition: transform 0.2s, border-color 0.2s;
    }
    .feed-bubble-card:hover {
        transform: translateY(-2px);
        border-color: rgba(255, 255, 255, 0.15) !important;
    }
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
