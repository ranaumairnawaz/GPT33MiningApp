<?php
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='/login';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user->execute([$user_id]);
$userData = $user->fetch();

// Fetch site settings
$stmt = $pdo->query("SELECT whatsapp_link, telegram_link FROM settings LIMIT 1");
$settings = $stmt->fetch();
?>

<div class="full-screen-view" style="min-height: 100vh; background: var(--bs-body-bg); display: flex; flex-direction: column;">
    <header class="view-header">
        <a href="javascript:history.back()"><i class="fa-solid fa-arrow-left"></i></a>
        <h2>Protocol Hub</h2>
    </header>

    <div class="account-page" style="padding: 20px; padding-bottom: 100px; flex: 1;">

    <!-- Profile Section -->
    <div class="card"
        style="display: flex; align-items: center; gap: 15px; padding: 20px; background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.1);">
        <div
            style="width: 60px; height: 60px; border-radius: 12px; background: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: white;">
            <i class="fa-solid fa-user"></i>
        </div>
        <div style="flex: 1;">
            <h3 style="margin: 0; font-size: 1.1rem;"><?php echo htmlspecialchars($userData['full_name']); ?></h3>
            <div style="color: var(--text-muted); font-size: 0.8rem;">
                <?php echo htmlspecialchars($userData['email']); ?>
            </div>
        </div>
        <i class="fa-solid fa-chevron-right" style="color: var(--text-muted); font-size: 0.8rem;"></i>
    </div>

    <!-- ID Card -->
    <div class="card"
        style="padding: 12px 20px; margin-top: -10px; display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
        <div style="font-size: 0.75rem; color: var(--text-muted);">ID: <span
                style="color: var(--text-main); font-family: monospace;"><?php echo $userData['referral_code']; ?></span>
        </div>
        <i class="fa-regular fa-copy" style="color: var(--primary); cursor: pointer;" onclick="copyID()"></i>
    </div>

    <!-- Balance Overview -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px;">
        <div class="card" style="padding: 15px; margin-bottom: 0;">
            <div
                style="display: flex; align-items: center; gap: 8px; color: var(--primary); font-size: 0.75rem; margin-bottom: 5px;">
                <i class="fa-solid fa-wallet"></i> <span>Deposit</span>
            </div>
            <div style="font-size: 1.2rem; font-weight: 800;">
                $<?php echo number_format($userData['deposit_balance'], 2); ?></div>
        </div>
        <div class="card" style="padding: 15px; margin-bottom: 0;">
            <div
                style="display: flex; align-items: center; gap: 8px; color: var(--accent); font-size: 0.75rem; margin-bottom: 5px;">
                <i class="fa-solid fa-chart-line"></i> <span>Earnings</span>
            </div>
            <div style="font-size: 1.2rem; font-weight: 800;">
                $<?php echo number_format($userData['earnings_balance'], 2); ?></div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 20px 0;">
        <a href="deposit" data-link class="btn" style="padding: 12px; font-size: 0.9rem; box-shadow: none;">Deposit</a>
        <a href="withdraw" data-link class="btn btn-outline"
            style="padding: 12px; font-size: 0.9rem; border-color: var(--glass-border); color: var(--text-main);">Withdraw</a>
    </div>

    <!-- Categorized Menu -->

    <!-- Transactions Category -->
    <div
        style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin: 25px 0 10px 5px; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-layer-group"></i> Transactions
    </div>
    <div class="card" style="padding: 0; overflow: hidden;">
        <?php
        $menu_items = [
            ['icon' => 'fa-credit-card', 'color' => 'var(--primary)', 'label' => 'Make A Deposit', 'link' => 'deposit'],
            ['icon' => 'fa-wallet', 'color' => 'var(--secondary)', 'label' => 'Make A Withdraw', 'link' => 'withdraw'],
            ['icon' => 'fa-bullhorn', 'color' => '#10b981', 'label' => 'Broadcast Feed', 'link' => 'feed'],
            ['icon' => 'fa-receipt', 'color' => 'var(--accent)', 'label' => 'Transaction Logs', 'link' => 'transactions']
        ];
        foreach ($menu_items as $item): ?>
            <a href="<?php echo $item['link']; ?>" data-link
                style="display: flex; align-items: center; gap: 15px; padding: 15px 20px; text-decoration: none; color: var(--text-main); border-bottom: 1px solid var(--glass-border);">
                <div
                    style="width: 32px; height: 32px; border-radius: 8px; background: rgba(255,255,255,0.03); display: flex; align-items: center; justify-content: center; color: <?php echo $item['color']; ?>; font-size: 0.9rem;">
                    <i class="fa-solid <?php echo $item['icon']; ?>"></i>
                </div>
                <span style="font-size: 0.9rem; font-weight: 600; flex: 1;"><?php echo $item['label']; ?></span>
                <i class="fa-solid fa-chevron-right" style="color: var(--text-muted); font-size: 0.7rem;"></i>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Investments Category -->
    <div
        style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin: 25px 0 10px 5px; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-microchip"></i> Investments
    </div>
    <div class="card" style="padding: 0; overflow: hidden;">
        <a href="invest" data-link
            style="display: flex; align-items: center; gap: 15px; padding: 15px 20px; text-decoration: none; color: var(--text-main); border-bottom: 1px solid var(--glass-border);">
            <div
                style="width: 32px; height: 32px; border-radius: 8px; background: rgba(255,255,255,0.03); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 0.9rem;">
                <i class="fa-solid fa-microchip"></i>
            </div>
            <span style="font-size: 0.9rem; font-weight: 600; flex: 1;">Investment Plans</span>
            <i class="fa-solid fa-chevron-right" style="color: var(--text-muted); font-size: 0.7rem;"></i>
        </a>
        <a href="investments" data-link
            style="display: flex; align-items: center; gap: 15px; padding: 15px 20px; text-decoration: none; color: var(--text-main);">
            <div
                style="width: 32px; height: 32px; border-radius: 8px; background: rgba(255,255,255,0.03); display: flex; align-items: center; justify-content: center; color: var(--secondary); font-size: 0.9rem;">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <span style="font-size: 0.9rem; font-weight: 600; flex: 1;">My Investments</span>
            <i class="fa-solid fa-chevron-right" style="color: var(--text-muted); font-size: 0.7rem;"></i>
        </a>
    </div>

    <!-- Referrals Category -->
    <div
        style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin: 25px 0 10px 5px; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-users"></i> Referrals & Rewards
    </div>
    <div class="card" style="padding: 0; overflow: hidden;">
        <a href="referrals" data-link
            style="display: flex; align-items: center; gap: 15px; padding: 15px 20px; text-decoration: none; color: var(--text-main); border-bottom: 1px solid var(--glass-border);">
            <div
                style="width: 32px; height: 32px; border-radius: 8px; background: rgba(255,255,255,0.03); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 0.9rem;">
                <i class="fa-solid fa-users"></i>
            </div>
            <span style="font-size: 0.9rem; font-weight: 600; flex: 1;">My Referrals</span>
            <i class="fa-solid fa-chevron-right" style="color: var(--text-muted); font-size: 0.7rem;"></i>
        </a>
        <a href="#"
            style="display: flex; align-items: center; gap: 15px; padding: 15px 20px; text-decoration: none; color: var(--text-main); border-bottom: 1px solid var(--glass-border);">
            <div
                style="width: 32px; height: 32px; border-radius: 8px; background: rgba(255,255,255,0.03); display: flex; align-items: center; justify-content: center; color: var(--secondary); font-size: 0.9rem;">
                <i class="fa-solid fa-gift"></i>
            </div>
            <span style="font-size: 0.9rem; font-weight: 600; flex: 1;">Referral Plans</span>
            <i class="fa-solid fa-chevron-right" style="color: var(--text-muted); font-size: 0.7rem;"></i>
        </a>
        <a href="#"
            style="display: flex; align-items: center; gap: 15px; padding: 15px 20px; text-decoration: none; color: var(--text-main);">
            <div
                style="width: 32px; height: 32px; border-radius: 8px; background: rgba(255,255,255,0.03); display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 0.9rem;">
                <i class="fa-solid fa-crown"></i>
            </div>
            <span style="font-size: 0.9rem; font-weight: 600; flex: 1;">My Ranks</span>
            <i class="fa-solid fa-chevron-right" style="color: var(--text-muted); font-size: 0.7rem;"></i>
        </a>
    </div>

    <!-- Account Category -->
    <div
        style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin: 25px 0 10px 5px; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-circle-user"></i> Account
    </div>
    <div class="card" style="padding: 0; overflow: hidden;">
        <a href="profile" data-link
            style="display: flex; align-items: center; gap: 15px; padding: 15px 20px; text-decoration: none; color: var(--text-main); border-bottom: 1px solid var(--glass-border);">
            <div
                style="width: 32px; height: 32px; border-radius: 8px; background: rgba(255,255,255,0.03); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 0.9rem;">
                <i class="fa-solid fa-circle-info"></i>
            </div>
            <span style="font-size: 0.9rem; font-weight: 600; flex: 1;">My Profile</span>
            <i class="fa-solid fa-chevron-right" style="color: var(--text-muted); font-size: 0.7rem;"></i>
        </a>
        <a href="#"
            style="display: flex; align-items: center; gap: 15px; padding: 15px 20px; text-decoration: none; color: var(--text-main); border-bottom: 1px solid var(--glass-border);">
            <div
                style="width: 32px; height: 32px; border-radius: 8px; background: rgba(255,255,255,0.03); display: flex; align-items: center; justify-content: center; color: var(--secondary); font-size: 0.9rem;">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <span style="font-size: 0.9rem; font-weight: 600; flex: 1;">Security</span>
            <i class="fa-solid fa-chevron-right" style="color: var(--text-muted); font-size: 0.7rem;"></i>
        </a>
        <a href="support" data-link
            style="display: flex; align-items: center; gap: 15px; padding: 15px 20px; text-decoration: none; color: var(--text-main);">
            <div
                style="width: 32px; height: 32px; border-radius: 8px; background: rgba(255,255,255,0.03); display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 0.9rem;">
                <i class="fa-solid fa-headset"></i>
            </div>
            <span style="font-size: 0.9rem; font-weight: 600; flex: 1;">Help & Support</span>
            <i class="fa-solid fa-chevron-right" style="color: var(--text-muted); font-size: 0.7rem;"></i>
        </a>
    </div>

    <!-- Logout Button -->
    <a href="logout" class="card"
        style="display: flex; align-items: center; gap: 15px; padding: 15px 20px; text-decoration: none; color: #ef4444; background: rgba(239, 68, 68, 0.05); border-color: rgba(239, 68, 68, 0.1); margin-top: 30px;">
        <div
            style="width: 32px; height: 32px; border-radius: 8px; background: rgba(239, 68, 68, 0.1); display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
            <i class="fa-solid fa-sign-out-alt"></i>
        </div>
        <span style="font-size: 0.9rem; font-weight: 700; flex: 1;">Logout Account</span>
        <i class="fa-solid fa-chevron-right" style="opacity: 0.5; font-size: 0.7rem;"></i>
    </a>

</div>

<script>
    function copyID() {
        const id = "<?php echo substr(md5($userData['id']), 0, 16); ?>";
        navigator.clipboard.writeText(id).then(() => {
            showToast('ID copied to clipboard');
        });
    }
</script>

<!-- External Support Links -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px;">
    <?php if (!empty($settings['whatsapp_link'])): ?>
        <a href="<?php echo htmlspecialchars($settings['whatsapp_link']); ?>" target="_blank" class="card"
            style="text-align: center; text-decoration: none; padding: 15px; margin-bottom: 0; background: #25d366; border: none; color: white;">
            <i class="fa-brands fa-whatsapp" style="font-size: 1.8rem; margin-bottom: 8px;"></i>
            <div style="font-weight: 600; font-size: 0.9rem;">WhatsApp</div>
        </a>
    <?php endif; ?>

    <?php if (!empty($settings['telegram_link'])): ?>
        <a href="<?php echo htmlspecialchars($settings['telegram_link']); ?>" target="_blank" class="card"
            style="text-align: center; text-decoration: none; padding: 15px; margin-bottom: 0; background: #0088cc; border: none; color: white;">
            <i class="fa-brands fa-telegram" style="font-size: 1.8rem; margin-bottom: 8px;"></i>
            <div style="font-weight: 600; font-size: 0.9rem;">Telegram</div>
        </a>
    <?php endif; ?>
</div>
</div>

<script>
    const chatBox = document.getElementById('chat-messages');
    const chatInput = document.getElementById('chat-input');

    const loadMessages = async () => {
        try {
            const response = await fetch('api/chat?action=get_messages', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();

            if (data.status === 'success') {
                renderMessages(data.messages);
            }
        } catch (e) {
            console.error('Chat load error:', e);
        }
    };

    const renderMessages = (messages) => {
        const userId = <?php echo $_SESSION['user_id']; ?>;
        chatBox.innerHTML = messages.map(msg => `
            <div style="max-width: 80%; padding: 10px 15px; border-radius: 15px; font-size: 0.9rem; ${msg.sender_id == userId ? 'align-self: flex-end; background: var(--primary); color: white;' : 'align-self: flex-start; background: var(--glass); color: var(--text-main);'}">
                ${msg.message}
                <div style="font-size: 0.6rem; margin-top: 5px; opacity: 0.7; text-align: right;">${msg.time}</div>
            </div>
        `).join('');
        chatBox.scrollTop = chatBox.scrollHeight;
    };

    window.onChatSend = (data) => {
        chatInput.value = '';
        loadMessages();
    };

    // Auto-refresh chat every 5 seconds (only if element exists)
    (function () {
        let chatInterval = setInterval(() => {
            if (!document.getElementById('chat-messages')) {
                clearInterval(chatInterval);
                return;
            }
            loadMessages();
        }, 5000);
    })();
    loadMessages();
</script>
</div>
