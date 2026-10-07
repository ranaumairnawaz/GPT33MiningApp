<?php
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='/login';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch site settings for legacy support links
$stmt = $pdo->query("SELECT whatsapp_link, telegram_link FROM settings LIMIT 1");
$settings = $stmt->fetch();

// Fetch dynamic support links
$support_links = $pdo->query("SELECT * FROM support_links WHERE is_active = 1 ORDER BY id ASC")->fetchAll();
?>

<div class="support-page" style="padding-bottom: 100px;">
    
    <!-- External Support Channels -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 15px; margin-bottom: 25px;">
        <?php if (!empty($settings['whatsapp_link'])): ?>
        <a href="<?php echo htmlspecialchars($settings['whatsapp_link']); ?>" target="_blank" class="card" style="text-align: center; text-decoration: none; padding: 20px; background: linear-gradient(135deg, #25d366, #128c7e); border: none; color: white; box-shadow: 0 10px 20px rgba(37, 211, 102, 0.2);">
            <i class="fa-brands fa-whatsapp" style="font-size: 2rem; margin-bottom: 10px;"></i>
            <div style="font-weight: 700; font-size: 0.8rem;">WhatsApp</div>
            <div style="font-size: 0.65rem; opacity: 0.9;">Contact Support</div>
        </a>
        <?php endif; ?>
        
        <?php if (!empty($settings['telegram_link'])): ?>
        <a href="<?php echo htmlspecialchars($settings['telegram_link']); ?>" target="_blank" class="card" style="text-align: center; text-decoration: none; padding: 20px; background: linear-gradient(135deg, #0088cc, #006699); border: none; color: white; box-shadow: 0 10px 20px rgba(0, 136, 204, 0.2);">
            <i class="fa-brands fa-telegram" style="font-size: 2rem; margin-bottom: 10px;"></i>
            <div style="font-weight: 700; font-size: 0.8rem;">Telegram</div>
            <div style="font-size: 0.65rem; opacity: 0.9;">Join Channel</div>
        </a>
        <?php endif; ?>

        <?php foreach ($support_links as $link): ?>
        <a href="<?php echo htmlspecialchars($link['link']); ?>" target="_blank" class="card" style="text-align: center; text-decoration: none; padding: 20px; background: <?php echo $link['color_bg']; ?>; border: none; color: white; box-shadow: 0 10px 20px rgba(0,0,0,0.1);">
            <i class="<?php echo htmlspecialchars($link['icon']); ?>" style="font-size: 2rem; margin-bottom: 10px;"></i>
            <div style="font-weight: 700; font-size: 0.8rem;"><?php echo htmlspecialchars($link['platform_name']); ?></div>
            <div style="font-size: 0.65rem; opacity: 0.9;">Connect Now</div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Live Chat Link -->
    <a href="/chat" data-link style="text-decoration: none; color: inherit;">
        <div class="card" style="padding: 25px; background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(37, 99, 235, 0.05)); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 24px; position: relative; overflow: hidden;">
            <div style="position: absolute; top: -20px; right: -20px; font-size: 8rem; opacity: 0.03; transform: rotate(-15deg);">
                <i class="fa-solid fa-comments"></i>
            </div>
            
            <div style="display: flex; align-items: center; gap: 20px;">
                <div style="width: 60px; height: 60px; background: var(--primary); border-radius: 18px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);">
                    <i class="fa-solid fa-headset"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800;">Secure Live Chat</h3>
                    <p style="margin: 5px 0 0; font-size: 0.8rem; color: var(--text-muted);">Coordinate with GPT33 intelligence protocols in real-time.</p>
                </div>
            </div>
            
            <div style="margin-top: 25px; padding: 12px; background: rgba(16, 185, 129, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: space-between; border: 1px solid rgba(16, 185, 129, 0.2);">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 8px; height: 8px; border-radius: 50%; background: #10b981; animation: pulse 2s infinite;"></div>
                    <span style="font-size: 0.75rem; font-weight: 700; color: #10b981;">SUPPORT AGENTS ONLINE</span>
                </div>
                <div style="font-size: 0.75rem; font-weight: 700; color: var(--primary);">
                    START CONVERSATION <i class="fa-solid fa-arrow-right ms-1"></i>
                </div>
            </div>
        </div>
    </a>

</div>

<script>
    (function() {
        const chatBox = document.getElementById('chat-messages');
        const chatInput = document.getElementById('chat-input');
        let isPolling = true;

        const loadMessages = async () => {
            if (!isPolling) return;
            try {
                const response = await fetch('/api/chat?action=get_messages', {
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
            const userId = <?php echo $user_id; ?>;
            const content = messages.map(msg => `
                <div style="max-width: 85%; padding: 12px 16px; border-radius: 18px; font-size: 0.9rem; line-height: 1.4; ${msg.sender_id == userId ? 'align-self: flex-end; background: var(--primary); color: white; border-bottom-right-radius: 4px;' : 'align-self: flex-start; background: var(--bg-dark); color: var(--text-main); border-bottom-left-radius: 4px; border: 1px solid var(--glass-border);'}">
                    ${msg.message}
                    <div style="font-size: 0.65rem; margin-top: 5px; opacity: 0.6; text-align: right;">${msg.time}</div>
                </div>
            `).join('');
            
            if (chatBox.innerHTML !== content) {
                chatBox.innerHTML = content;
                chatBox.scrollTop = chatBox.scrollHeight;
            }
        };

        window.onChatSent = (data) => {
            chatInput.value = '';
            loadMessages();
        };

        const chatTimer = setInterval(loadMessages, 4000);
        loadMessages();

        // Cleanup polling when navigating away (for SPA)
        window.addEventListener('popstate', () => {
            isPolling = false;
            clearInterval(chatTimer);
        }, { once: true });
    })();
</script>
