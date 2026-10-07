<?php
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='/login';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

// Stats calculations
$direct_count = $pdo->prepare("SELECT COUNT(*) FROM users WHERE referred_by = ?");
$direct_count->execute([$user_id]);
$direct = $direct_count->fetchColumn();

// Total commissions
$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM transactions WHERE user_id = ? AND type = 'referral_commission' AND status = 'completed'");
$stmt->execute([$user_id]);
$total_comm = $stmt->fetch()['total'] ?? 0;

// Fetch referral list (Direct only for now)
$stmt = $pdo->prepare("SELECT full_name, email, created_at FROM users WHERE referred_by = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$referrals = $stmt->fetchAll();
// Fetch user's referral code
$stmt = $pdo->prepare("SELECT referral_code FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$my_code = $stmt->fetchColumn();
?>

<div class="full-screen-view" style="min-height: 100vh; background: var(--bs-body-bg); display: flex; flex-direction: column;">
    <header class="view-header">
        <a href="javascript:history.back()"><i class="fa-solid fa-arrow-left"></i></a>
        <h2>Network Intelligence</h2>
    </header>

    <div class="referrals-page" style="padding: 20px; padding-bottom: 100px; flex: 1;">
    
    <!-- Stats Grid -->
    <div class="card" style="background: rgba(139, 92, 246, 0.05); border: 1px solid rgba(139, 92, 246, 0.1); padding: 20px;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
            <div style="width: 35px; height: 35px; border-radius: 8px; background: rgba(16, 185, 129, 0.1); display: flex; align-items: center; justify-content: center; color: var(--accent);">
                <i class="fa-solid fa-users-viewfinder"></i>
            </div>
            <div>
                <div style="font-weight: 700; font-size: 0.95rem;">Referral Statistics</div>
                <div style="font-size: 0.7rem; color: var(--text-muted);">Your network performance</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
            <div style="text-align: center; padding: 10px; background: rgba(255,255,255,0.02); border-radius: 12px;">
                <div style="font-size: 1.1rem; font-weight: 800; color: var(--primary);"><?php echo $direct; ?></div>
                <div style="font-size: 0.6rem; color: var(--text-muted); text-transform: uppercase;">Total</div>
            </div>
            <div style="text-align: center; padding: 10px; background: rgba(255,255,255,0.02); border-radius: 12px;">
                <div style="font-size: 1.1rem; font-weight: 800; color: var(--primary);"><?php echo $direct; ?></div>
                <div style="font-size: 0.6rem; color: var(--text-muted); text-transform: uppercase;">Direct</div>
            </div>
            <div style="text-align: center; padding: 10px; background: rgba(255,255,255,0.02); border-radius: 12px;">
                <div style="font-size: 1.1rem; font-weight: 800; color: var(--primary);">0</div>
                <div style="font-size: 0.6rem; color: var(--text-muted); text-transform: uppercase;">Indirect</div>
            </div>

            <div style="text-align: center; padding: 10px; background: rgba(255,255,255,0.02); border-radius: 12px;">
                <div style="font-size: 1.1rem; font-weight: 800; color: var(--accent);">$<?php echo number_format($total_comm, 2); ?></div>
                <div style="font-size: 0.6rem; color: var(--text-muted); text-transform: uppercase;">Total Com.</div>
            </div>
            <div style="text-align: center; padding: 10px; background: rgba(255,255,255,0.02); border-radius: 12px;">
                <div style="font-size: 1.1rem; font-weight: 800; color: var(--accent);">$<?php echo number_format($total_comm, 2); ?></div>
                <div style="font-size: 0.6rem; color: var(--text-muted); text-transform: uppercase;">Direct Com.</div>
            </div>
            <div style="text-align: center; padding: 10px; background: rgba(255,255,255,0.02); border-radius: 12px;">
                <div style="font-size: 1.1rem; font-weight: 800; color: var(--accent);">$0.00</div>
                <div style="font-size: 0.6rem; color: var(--text-muted); text-transform: uppercase;">Indirect Com.</div>
            </div>
        </div>

        <div class="card" style="margin-top: 20px; padding: 15px; margin-bottom: 0; background: rgba(59, 130, 246, 0.03); border: 1px solid rgba(59, 130, 246, 0.1);">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                <i class="fa-solid fa-link" style="color: var(--primary); font-size: 0.8rem;"></i>
                <span style="font-weight: 700; font-size: 0.85rem;">Your Referral Link</span>
            </div>
            <div style="display: flex; gap: 10px;">
                <input type="text" readonly value="https://gpt33.online/register?ref=<?php echo htmlspecialchars($my_code); ?>" class="form-control" style="font-size: 0.75rem; background: rgba(0,0,0,0.2); border-color: rgba(59, 130, 246, 0.2);">
                <button onclick="copyRef()" style="background: var(--primary); border: none; color: white; width: 40px; border-radius: 10px; cursor: pointer; box-shadow: var(--neon-blue);"><i class="fa-regular fa-copy"></i></button>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div style="margin: 25px 0 15px 5px; display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 0.9rem; color: var(--text-main);">
        <i class="fa-solid fa-filter" style="color: var(--primary);"></i> Filters
    </div>
    <div class="card" style="padding: 15px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
            <div>
                <label style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px; display: block;">Level</label>
                <select class="form-control" style="font-size: 0.85rem; padding: 10px;">
                    <option>All Levels</option>
                    <option>Level 1</option>
                    <option>Level 2</option>
                    <option>Level 3</option>
                </select>
            </div>
            <div>
                <label style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px; display: block;">Investment</label>
                <select class="form-control" style="font-size: 0.85rem; padding: 10px;">
                    <option>All</option>
                    <option>Active</option>
                    <option>Inactive</option>
                </select>
            </div>
        </div>
        <label style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px; display: block;">Search by Email</label>
        <input type="text" class="form-control" placeholder="Enter email..." style="font-size: 0.85rem; padding: 10px;">
    </div>

    <!-- Visual Network Map -->
    <div style="margin: 25px 0 15px 5px; display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 0.9rem; color: var(--text-main);">
        <i class="fa-solid fa-sitemap" style="color: var(--primary);"></i> Network Visualization
    </div>
    <div class="card" style="padding: 20px; overflow: hidden;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
            <div style="width: 35px; height: 35px; border-radius: 10px; background: var(--glass); display: flex; align-items: center; justify-content: center; color: var(--primary);">
                <i class="fa-solid fa-diagram-project"></i>
            </div>
            <div>
                <div style="font-weight: 800; font-size: 1rem; letter-spacing: -0.5px;">Architecture Map</div>
                <div style="font-size: 0.7rem; color: var(--text-muted);">Hierarchical node mapping (Level 1-5)</div>
            </div>
            <button onclick="loadNetworkTree()" style="margin-left: auto; background: none; border: 1px solid var(--glass-border); color: var(--text-muted); padding: 5px 12px; border-radius: 8px; font-size: 0.7rem; cursor: pointer; transition: var(--transition);">
                <i class="fa-solid fa-rotate"></i> Refresh
            </button>
        </div>

        <div id="network-tree-container" style="min-height: 150px; position: relative;">
            <div id="network-tree-loader" style="text-align: center; padding: 40px 0;">
                <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 1.5rem; color: var(--primary); margin-bottom: 10px;"></i>
                <p style="font-size: 0.8rem; color: var(--text-muted);">Syncing nodes...</p>
            </div>
            <div id="network-tree-content" style="display: none;"></div>
        </div>
    </div>

    <!-- Referral List -->
    <div class="card" style="padding: 20px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
            <div style="width: 35px; height: 35px; border-radius: 8px; background: rgba(59, 130, 246, 0.1); display: flex; align-items: center; justify-content: center; color: var(--primary);">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <div>
                <div style="font-weight: 700; font-size: 0.95rem;">Referral List</div>
                <div style="font-size: 0.7rem; color: var(--text-muted);">Your network members</div>
            </div>
        </div>

        <?php if (empty($referrals)): ?>
            <div style="text-align: center; padding: 40px 0;">
                <div style="width: 60px; height: 60px; border-radius: 50%; background: rgba(255,255,255,0.03); display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto; color: var(--text-muted); font-size: 1.5rem;">
                    <i class="fa-solid fa-users-slash"></i>
                </div>
                <p style="color: var(--text-muted); font-size: 0.9rem;">No referrals found</p>
            </div>
        <?php else: ?>
            <div style="display: grid; gap: 12px;">
                <?php foreach ($referrals as $ref): ?>
                    <div style="display: flex; align-items: center; gap: 15px; padding: 12px; background: rgba(255,255,255,0.02); border-radius: 12px; border: 1px solid var(--glass-border);">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: var(--glass); display: flex; align-items: center; justify-content: center; font-size: 1rem; color: var(--text-muted);">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; font-size: 0.9rem;"><?php echo htmlspecialchars($ref['full_name']); ?></div>
                            <div style="font-size: 0.7rem; color: var(--text-muted);"><?php echo htmlspecialchars($ref['email']); ?></div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 0.6rem; color: var(--text-muted); text-transform: uppercase;">Level 1</div>
                            <div style="font-size: 0.7rem; font-weight: 700; color: var(--accent);">Joined <?php echo date('M j', strtotime($ref['created_at'])); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
    async function loadNetworkTree() {
        const loader = document.getElementById('network-tree-loader');
        const content = document.getElementById('network-tree-content');
        
        loader.style.display = 'block';
        content.style.display = 'none';

        try {
            const resp = await fetch('/api/referral_tree.php');
            const data = await resp.json();

            if (data.status === 'success') {
                content.innerHTML = renderTreeNodes(data.tree);
                loader.style.display = 'none';
                content.style.display = 'block';
            } else {
                throw new Error(data.message);
            }
        } catch (err) {
            loader.innerHTML = `<p style="color: #ef4444; font-size: 0.8rem;"><i class="fa-solid fa-triangle-exclamation"></i> Sync failed</p>`;
        }
    }

    function renderTreeNodes(nodes, level = 1) {
        if (!nodes || nodes.length === 0) {
            return level === 1 ? '<p style="text-align: center; color: var(--text-muted); font-size: 0.8rem; padding: 20px;">No downstream nodes connected.</p>' : '';
        }

        let html = `<div class="tree-level level-${level}" style="padding-left: ${level > 1 ? '15px' : '0'}; border-left: ${level > 1 ? '1px dashed var(--glass-border)' : 'none'}; margin-top: 10px;">`;
        
        nodes.forEach(node => {
            const statusColor = node.is_active ? 'var(--primary)' : 'var(--text-muted)';
            const statusIcon = node.is_active ? 'fa-circle-check' : 'fa-circle';
            
            html += `
                <div class="tree-node" style="margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 10px; padding: 10px; background: rgba(255,255,255,0.02); border-radius: 12px; border: 1px solid var(--glass-border);">
                        <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--glass); display: flex; align-items: center; justify-content: center; color: ${statusColor}; font-size: 0.8rem;">
                            <i class="fa-solid ${statusIcon}"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 700; font-size: 0.8rem;">${node.full_name}</div>
                            <div style="font-size: 0.65rem; color: var(--text-muted);">${node.email}</div>
                        </div>
                        <div style="font-size: 0.6rem; font-weight: 800; color: var(--primary); text-transform: uppercase;">L${level}</div>
                    </div>
                    ${node.children && node.children.length > 0 ? renderTreeNodes(node.children, level + 1) : ''}
                </div>
            `;
        });

        html += `</div>`;
        return html;
    }

    // Auto-load on entry
    document.addEventListener('DOMContentLoaded', loadNetworkTree);

    function copyRef() {
        const input = document.querySelector('input[readonly]');
        input.select();
        document.execCommand('copy');
        showToast('Referral link copied!');
    }
</script>
</div>
