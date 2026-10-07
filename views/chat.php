<?php
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='/login';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->query("SELECT site_name FROM settings LIMIT 1");
$settings = $stmt->fetch();
$site_name = $settings['site_name'] ?? 'GPT33';
?>

<div class="full-screen-chat" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: var(--bs-body-bg); z-index: 1000; display: flex; flex-direction: column;">
    
    <!-- Chat Header -->
    <header style="padding: 15px 20px; background: var(--bg-card); border-bottom: 1px solid var(--glass-border); display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="/support" data-link style="color: var(--text-main); font-size: 1.2rem;">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div style="display: flex; align-items: center; gap: 10px;">
                <img src="/assets/img/logo.png" alt="Logo" style="height: 30px; width: auto; border-radius: 6px;">
                <div style="line-height: 1.2;">
                    <div style="font-weight: 800; font-size: 1rem; color: var(--text-main);"><?php echo htmlspecialchars($site_name); ?></div>
                    <div style="font-size: 0.65rem; color: #10b981; font-weight: 700;">
                        <i class="fa-solid fa-circle" style="font-size: 0.4rem; vertical-align: middle;"></i> SECURE PROTOCOL ACTIVE
                    </div>
                </div>
            </div>
        </div>
        <div style="width: 40px; height: 40px; background: rgba(59, 130, 246, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary);">
            <i class="fa-solid fa-headset"></i>
        </div>
    </header>

    <!-- Messages Area -->
    <div id="chat-messages" style="flex: 1; overflow-y: auto; padding: 25px; display: flex; flex-direction: column; gap: 20px; background: radial-gradient(circle at center, rgba(59, 130, 246, 0.03) 0%, transparent 70%);">
        <div style="text-align: center; margin-top: 50px; opacity: 0.3;">
            <i class="fa-solid fa-shield-halved" style="font-size: 3rem; margin-bottom: 15px;"></i>
            <div style="font-weight: 700; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">End-to-End Encryption Enabled</div>
            <p style="font-size: 0.7rem;">Your communication with GPT33 intelligence is audited and secured.</p>
        </div>
    </div>

    <!-- Input Area -->
    <div style="padding: 20px; background: var(--bg-card); border-top: 1px solid var(--glass-border); padding-bottom: calc(20px + env(safe-area-inset-bottom));">
        <form id="user-chat-form" data-ajax-form data-action="/api/chat?action=send" data-callback="onChatSent" method="POST" style="display: flex; gap: 12px; align-items: center; background: rgba(0,0,0,0.2); padding: 8px; border-radius: 20px; border: 1px solid var(--glass-border);">
            <input type="text" name="message" id="chat-input" class="form-control" placeholder="Type your message..." autocomplete="off" required style="border: none !important; background: transparent !important; box-shadow: none !important; padding: 10px 15px; color: var(--text-main) !important;">
            <button type="submit" class="btn" style="width: 45px; height: 45px; border-radius: 50%; padding: 0; box-shadow: none; flex-shrink: 0; background: var(--primary); display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-paper-plane" style="font-size: 1rem;"></i>
            </button>
        </form>
    </div>

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
            const content = messages.map(msg => {
                const isMine = msg.sender_id == userId;
                return `
                    <div style="max-width: 80%; ${isMine ? 'align-self: flex-end;' : 'align-self: flex-start;'}">
                        <div style="padding: 14px 18px; border-radius: 22px; font-size: 0.95rem; line-height: 1.5; ${isMine ? 'background: linear-gradient(135deg, var(--primary), #2563eb); color: white; border-bottom-right-radius: 4px;' : 'background: rgba(255,255,255,0.05); color: var(--text-main); border-bottom-left-radius: 4px; border: 1px solid var(--glass-border);'}">
                            ${msg.message}
                        </div>
                        <div style="font-size: 0.65rem; margin-top: 6px; opacity: 0.5; ${isMine ? 'text-align: right;' : 'text-align: left;'}">
                            ${isMine ? '<i class="fa-solid fa-check-double" style="margin-right: 5px;"></i>' : ''} ${msg.time}
                        </div>
                    </div>
                `;
            }).join('');
            
            if (chatBox.innerHTML !== content) {
                const isAtBottom = chatBox.scrollHeight - chatBox.scrollTop <= chatBox.clientHeight + 100;
                chatBox.innerHTML = content;
                if (isAtBottom) {
                    chatBox.scrollTop = chatBox.scrollHeight;
                }
            }
        };

        window.onChatSent = (data) => {
            chatInput.value = '';
            loadMessages();
            chatBox.scrollTop = chatBox.scrollHeight;
        };

        const chatTimer = setInterval(loadMessages, 3000);
        loadMessages();

        // Cleanup polling when navigating away (for SPA)
        window.addEventListener('popstate', () => {
            isPolling = false;
            clearInterval(chatTimer);
        }, { once: true });

        // Initial scroll
        setTimeout(() => {
            chatBox.scrollTop = chatBox.scrollHeight;
        }, 500);
    })();
</script>

<style>
    /* Hide default header/footer when in full screen chat */
    .route-chat #main-header, 
    .route-chat #bottom-nav {
        display: none !important;
    }
    
    .route-chat #app-content {
        padding: 0 !important;
        margin: 0 !important;
    }
</style>
