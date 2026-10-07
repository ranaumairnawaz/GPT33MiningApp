<?php
// Get unique users who have sent messages
$stmt = $pdo->query("
    SELECT DISTINCT u.id, u.full_name, u.email, 
    (SELECT message FROM chat_messages WHERE sender_id = u.id OR receiver_id = u.id ORDER BY created_at DESC LIMIT 1) as last_msg,
    (SELECT created_at FROM chat_messages WHERE sender_id = u.id OR receiver_id = u.id ORDER BY created_at DESC LIMIT 1) as last_time,
    (SELECT COUNT(*) FROM chat_messages WHERE sender_id = u.id AND is_read = 0) as unread_count
    FROM users u
    JOIN chat_messages m ON u.id = m.sender_id OR u.id = m.receiver_id
    WHERE u.is_admin = 0
    ORDER BY last_time DESC
");
$chats = $stmt->fetchAll();

$quick_replies = [
    "Hello! How can we assist you today?",
    "Your withdrawal has been processed successfully.",
    "Please provide your transaction ID (TID) for verification.",
    "Your deposit is currently under review by our finance team.",
    "We are performing system maintenance, some features may be delayed.",
    "Thank you for contacting GPT33 support!"
];
?>

<div class="row g-0 overflow-hidden shadow-sm border-0 rounded-5"
    style="height: calc(100vh - 160px); background: var(--card-bg);">

    <!-- User List Pane -->
    <div class="col-md-4 col-lg-3 border-end d-flex flex-column"
        style="border-color: var(--border-color) !important; background: var(--bs-body-bg);">
        <div class="p-4 border-bottom d-flex justify-content-between align-items-center"
            style="border-color: var(--border-color) !important;">
            <h6 class="m-0 fw-800 text-dark">Conversations</h6>
            <button class="btn btn-sm btn-primary rounded-circle" onclick="openNewChatModal()" title="Start New Conversation">
                <i class="fa-solid fa-plus"></i>
            </button>
        </div>
        <div class="flex-grow-1 overflow-auto custom-scrollbar">
            <?php foreach ($chats as $c): ?>
                <div onclick="loadUserChat(this, <?php echo $c['id']; ?>, '<?php echo htmlspecialchars($c['full_name']); ?>')"
                    class="chat-user-item p-4 border-bottom cursor-pointer transition-all position-relative"
                    style="border-color: var(--border-color) !important;">
                    <div class="d-flex align-items-center gap-3">
                        <div
                            style="width: 45px; height: 45px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--text-main); font-weight: 800; box-shadow: var(--soft-shadow);">
                            <?php echo strtoupper(substr($c['full_name'], 0, 1)); ?>
                        </div>
                        <div class="overflow-hidden flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div class="fw-bold text-dark small mb-0"><?php echo htmlspecialchars($c['full_name']); ?>
                                </div>
                                <?php if ($c['unread_count'] > 0): ?>
                                    <span class="badge bg-danger rounded-pill"
                                        style="font-size: 0.6rem;"><?php echo $c['unread_count']; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="text-muted text-truncate" style="font-size: 0.75rem;">

                                <?php echo htmlspecialchars($c['last_msg']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Active Chat Pane -->
    <div class="col-md-8 col-lg-9 d-flex flex-column" style="height: 100%; background: var(--card-bg);">
        <!-- Chat Header -->
        <div class="p-3 border-bottom d-flex align-items-center justify-content-between flex-shrink-0"
            style="border-color: var(--border-color) !important;">
            <div class="d-flex align-items-center gap-3">
                <div id="active-user-avatar"
                    style="width: 40px; height: 40px; background: var(--primary-accent); border-radius: 12px; display: none; align-items: center; justify-content: center; color: white;">
                    <i class="fa-solid fa-user-astronaut"></i>
                </div>
                <div>
                    <h6 class="m-0 fw-800 text-dark" id="active-user-name">Select Intelligence Node</h6>
                    <div id="active-user-status" class="text-success small fw-bold"
                        style="font-size: 0.65rem; display: none;">
                        <i class="fa-solid fa-circle me-1" style="font-size: 0.4rem;"></i> SECURE CONNECTION
                    </div>
                </div>
            </div>

            <div class="dropdown" id="quick-reply-dropdown" style="display: none;">
                <button class="btn btn-sm btn-light border dropdown-toggle fw-bold" data-bs-toggle="dropdown">
                    <i class="fa-solid fa-bolt-lightning me-1 text-warning"></i> Quick Protocols
                </button>
                <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-4 p-2">
                    <?php foreach ($quick_replies as $reply): ?>
                        <li><a class="dropdown-item small rounded-3 py-2 fw-bold" href="javascript:void(0)"
                                onclick="insertQuickReply('<?php echo addslashes($reply); ?>')"><?php echo $reply; ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Messages Area -->
        <div id="admin-chat-messages" class="flex-grow-1 p-4 overflow-y-auto d-flex flex-column gap-4 custom-scrollbar"
            style="min-height: 0; background: var(--bs-body-bg);">
            <div class="text-center my-auto py-5 opacity-25">
                <i class="fa-solid fa-tower-broadcast d-block mb-3"
                    style="font-size: 3rem; color: var(--text-main);"></i>
                <h6 class="fw-800 uppercase letter-spacing-1 text-dark">Support Intelligence Hub</h6>
                <p class="small text-muted">Choose an active session to begin administrative response</p>
            </div>
        </div>

        <!-- Input Area -->
        <div class="p-4 border-top flex-shrink-0"
            style="border-color: var(--border-color) !important; background: var(--card-bg);">
            <form id="admin-chat-form" data-ajax-form data-action="/api/chat?action=admin_send"
                data-callback="onAdminChatSent">
                <div class="input-group gap-3">
                    <input type="hidden" name="receiver_id" id="chat-receiver-id">
                    <input type="text" name="message" id="admin-chat-input"
                        class="form-control border-0 py-3 px-4 rounded-4 fw-bold"
                        placeholder="Compose intelligence response..." autocomplete="off" disabled required
                        style="background: var(--bs-body-bg) !important; color: var(--text-main) !important;">
                    <button type="submit" class="btn btn-dark px-4 rounded-4 shadow-sm" disabled
                        style="background: var(--primary-accent); border: none;">
                        <i class="fa-solid fa-paper-plane-top"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- New Chat Modal -->
<div class="modal fade" id="newChatModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-5 border-0 shadow-lg">
            <div class="modal-header border-bottom p-4" style="border-color: var(--border-color) !important;">
                <h5 class="modal-title fw-800">Initialize New Communication</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fa-solid fa-search"></i></span>
                        <input type="text" id="user-search-input" class="form-control bg-light border-0" placeholder="Search investor ID, name or email...">
                    </div>
                </div>
                <div id="user-search-results" class="overflow-auto custom-scrollbar" style="max-height: 300px;">
                    <div class="text-center py-4 text-muted small">Enter search parameters to locate node...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .chat-user-item:hover {
        background: var(--bs-body-bg);
        opacity: 0.8;
    }

    .chat-user-item.active {
        background: var(--bs-body-bg);
        border-left: 4px solid var(--primary-accent) !important;
    }

    .msg-bubble {
        max-width: 75%;
        padding: 16px 20px;
        border-radius: 24px;
        font-size: 0.95rem;
        font-weight: 500;
        position: relative;
        box-shadow: var(--soft-shadow);
    }

    .msg-admin {
        align-self: flex-end;
        background: var(--primary-accent);
        color: #fff;
        border-bottom-right-radius: 4px;
    }

    .msg-user {
        align-self: flex-start;
        background: var(--card-bg);
        color: var(--text-main);
        border: 1px solid var(--border-color);
        border-bottom-left-radius: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: var(--border-color);
        border-radius: 10px;
    }
</style>

<script>
    let currentUserId = null;
    let refreshInterval = null;
    let listRefreshInterval = null;

    async function loadUserChat(el, userId, name) {
        currentUserId = userId;
        document.getElementById('active-user-name').textContent = name;
        document.getElementById('active-user-avatar').style.display = 'flex';
        document.getElementById('active-user-status').style.display = 'block';
        document.getElementById('quick-reply-dropdown').style.display = 'block';
        document.getElementById('chat-receiver-id').value = userId;
        document.getElementById('admin-chat-input').disabled = false;
        document.querySelector('#admin-chat-form button').disabled = false;

        document.querySelectorAll('.chat-user-item').forEach(item => item.classList.remove('active'));
        if (el) el.classList.add('active');

        const badge = el?.querySelector('.badge.bg-danger');
        if (badge) badge.remove();

        fetch('/api/chat?action=mark_as_read&user_id=' + userId);

        const chatBox = document.getElementById('admin-chat-messages');
        fetchMessages().then(() => {
            chatBox.scrollTop = chatBox.scrollHeight;
        });

        if (refreshInterval) clearInterval(refreshInterval);
        refreshInterval = setInterval(fetchMessages, 5000);
    }

    async function fetchChatList() {
        try {
            const resp = await fetch('/api/chat?action=get_chat_list');
            const data = await resp.json();
            if (data.status === 'success') {
                const listContainer = document.querySelector('.flex-grow-1.overflow-auto.custom-scrollbar');
                const badgeCount = document.querySelector('.badge.bg-primary.rounded-pill');
                if (badgeCount) badgeCount.textContent = data.chats.length;

                listContainer.innerHTML = data.chats.map(c => `
                    <div onclick="loadUserChat(this, ${c.id}, '${c.full_name}')" 
                         class="chat-user-item p-4 border-bottom cursor-pointer transition-all position-relative ${currentUserId == c.id ? 'active' : ''}" 
                         style="border-color: var(--border-color) !important;">
                        <div class="d-flex align-items-center gap-3">
                            <div style="width: 45px; height: 45px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--text-main); font-weight: 800; box-shadow: var(--soft-shadow);">
                                ${c.full_name.charAt(0).toUpperCase()}
                            </div>
                            <div class="overflow-hidden flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="fw-bold text-dark small mb-0">${c.full_name}</div>
                                    ${c.unread_count > 0 ? `<span class="badge bg-danger rounded-pill" style="font-size: 0.6rem;">${c.unread_count}</span>` : ''}
                                </div>
                                <div class="text-muted text-truncate" style="font-size: 0.75rem;">${c.last_msg || 'No messages yet'}</div>
                            </div>
                        </div>
                    </div>
                `).join('');
            }
        } catch (e) {
            console.error('Failed to fetch chat list', e);
        }
    }

    function insertQuickReply(text) {
        const input = document.getElementById('admin-chat-input');
        input.value = text;
        input.focus();
    }

    async function fetchMessages() {
        if (!currentUserId) return;
        const resp = await fetch('/api/chat?action=get_messages&user_id=' + currentUserId);
        const data = await resp.json();
        if (data.status === 'success') {
            const chatBox = document.getElementById('admin-chat-messages');
            const isAtBottom = chatBox.scrollHeight - chatBox.scrollTop <= chatBox.clientHeight + 100;

            chatBox.innerHTML = data.messages.map(msg => `
                <div class="msg-bubble ${msg.is_admin ? 'msg-admin' : 'msg-user'}">
                    ${msg.message}
                    <div style="font-size: 0.7rem; margin-top: 8px; opacity: 0.6; text-align: right;">${msg.time}</div>
                </div>
            `).join('');

            if (isAtBottom) {
                chatBox.scrollTop = chatBox.scrollHeight;
            }
        }
    }

    window.onAdminChatSent = (data) => {
        document.getElementById('admin-chat-input').value = '';
        const chatBox = document.getElementById('admin-chat-messages');
        fetchMessages().then(() => {
            chatBox.scrollTop = chatBox.scrollHeight;
        });
        fetchChatList(); // Refresh list immediately after sending
    };

    // Initial list refresh setup
    listRefreshInterval = setInterval(fetchChatList, 5000);

    // New Chat Functions
    let newChatModal;
    
    function initModal() {
        const modalEl = document.getElementById('newChatModal');
        if (!modalEl) return;
        
        if (typeof bootstrap !== 'undefined') {
            newChatModal = new bootstrap.Modal(modalEl);
        } else {
            setTimeout(initModal, 100);
        }
    }
    
    initModal();

    const searchInput = document.getElementById('user-search-input');
    const resultsContainer = document.getElementById('user-search-results');

    function openNewChatModal() {
        if (!newChatModal) {
            initModal();
            setTimeout(openNewChatModal, 100);
            return;
        }
        newChatModal.show();
        searchInput.value = '';
        resultsContainer.innerHTML = '<div class="text-center py-4 text-muted small">Enter search parameters to locate node...</div>';
    }

    searchInput.addEventListener('input', async (e) => {
        const query = e.target.value;
        if (query.length < 2) {
            resultsContainer.innerHTML = '<div class="text-center py-4 text-muted small">Enter search parameters to locate node...</div>';
            return;
        }

        try {
            const resp = await fetch('/api/admin_actions?action=search_users&query=' + encodeURIComponent(query));
            const data = await resp.json();
            if (data.status === 'success') {
                if (data.users.length === 0) {
                    resultsContainer.innerHTML = '<div class="text-center py-4 text-muted small">No nodes matched the criteria.</div>';
                } else {
                    resultsContainer.innerHTML = data.users.map(u => `
                        <div onclick="startChatWith(${u.id}, '${u.full_name.replace(/'/g, "\\'")}')" 
                             class="p-3 mb-2 rounded-4 border border-light bg-light bg-opacity-50 cursor-pointer hover-shadow transition-all d-flex align-items-center gap-3">
                            <div style="width: 40px; height: 40px; background: var(--primary-accent); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 800;">
                                ${u.full_name.charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <div class="fw-bold small text-dark">${u.full_name}</div>
                                <div class="text-muted" style="font-size: 0.65rem;">${u.email}</div>
                            </div>
                        </div>
                    `).join('');
                }
            }
        } catch (e) {
            console.error('Search error', e);
        }
    });

    function startChatWith(userId, name) {
        newChatModal.hide();
        loadUserChat(null, userId, name);
    }
</script>
