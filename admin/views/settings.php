<?php
$settings = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch();
?>

<div class="row mb-5 align-items-end">
    <div class="col">
        <h2 class="fw-800 m-0 text-dark">System Architecture</h2>
        <p class="text-muted m-0">Manage global operational parameters and network protocols</p>
    </div>
</div>

<form action="/api/admin_actions?action=save_settings" method="POST" enctype="multipart/form-data">
    <div class="row g-4">
        <!-- Global Configuration -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    Core Network Settings
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Platform Identity</label>
                            <input type="text" name="site_name" class="form-control"
                                value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Exchange Rate (1 USD = X)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0 px-3">$</span>
                                <input type="number" name="currency_rate" step="0.0001" class="form-control"
                                    value="<?php echo $settings['currency_rate']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">WhatsApp Support</label>
                            <input type="text" name="whatsapp_link" class="form-control"
                                value="<?php echo htmlspecialchars($settings['whatsapp_link']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Telegram Channel</label>
                            <input type="text" name="telegram_link" class="form-control"
                                value="<?php echo htmlspecialchars($settings['telegram_link']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Dynamic Deposit Bonus</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" name="deposit_bonus" class="form-control"
                                    value="<?php echo htmlspecialchars($settings['deposit_bonus'] ?? '0.00'); ?>" required>
                                <span class="input-group-text bg-light border-0 text-muted">%</span>
                            </div>
                            <div class="form-text text-muted" style="font-size: 0.72rem;">Set to 0.00 to disable bonuses. Bonus percentage added to approved funding automatically.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Broadcast Followers Base</label>
                            <div class="input-group">
                                <input type="number" min="0" max="1000000" name="broadcast_followers_base" class="form-control"
                                    value="<?php echo htmlspecialchars($settings['broadcast_followers_base'] ?? '3450'); ?>" required>
                                <span class="input-group-text bg-light border-0 text-muted">users</span>
                            </div>
                            <div class="form-text text-muted" style="font-size: 0.72rem;">Base count shown in Broadcast Feed channel subheader. Real user count is dynamically added to this base.</div>
                        </div>
                        <div class="col-12">
                            <div class="p-4 rounded-4 bg-light border-0">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold m-0 text-dark">Broadcast Announcement</h6>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="announcement_active"
                                            value="1" <?php echo $settings['announcement_active'] ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                                <textarea name="announcement_text" class="form-control bg-white border-0 mb-3" rows="3"
                                    placeholder="Enter marquee message for all users..."><?php echo htmlspecialchars($settings['announcement_text']); ?></textarea>
                                
                                <label class="form-label fw-bold small text-muted text-uppercase">Visual Assets (Image)</label>
                                <input type="file" name="announcement_image" class="form-control bg-white border-0 py-2">
                                <?php if ($settings['announcement_image']): ?>
                                    <div class="mt-2">
                                        <img src="/<?php echo $settings['announcement_image']; ?>" class="img-fluid rounded-3 border" style="max-height: 80px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Operational Status -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    Operational Status
                </div>
                <div class="card-body">
                    <div class="p-4 rounded-4 bg-danger bg-opacity-5 border-0 mb-4 text-center">
                        <i class="fa-solid fa-lock-open fs-2 text-danger mb-3 d-block"></i>
                        <h6 class="fw-bold text-dark mb-1">Maintenance Protocol</h6>
                        <p class="text-muted small mb-3">When active, only administrators can access the system front-end.</p>
                        <div class="form-check form-switch d-inline-block">
                            <input class="form-check-input" type="checkbox" name="is_maintenance" value="1" <?php echo $settings['is_maintenance'] ? 'checked' : ''; ?> style="width: 3em; height: 1.5em;">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-dark w-100 py-3 fw-bold rounded-4 shadow-sm">
                        <i class="fa-solid fa-cloud-arrow-up me-2"></i> Deploy System Updates
                    </button>
                </div>
            </div>
        </div>

        <!-- Referral Commissions -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    Multi-Level Reward Architecture
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <div class="col">
                                <div class="p-3 rounded-4 bg-light border-0 text-center">
                                    <label class="form-label fw-bold small text-muted text-uppercase d-block mb-2">Level <?php echo $i; ?></label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" name="ref_level_<?php echo $i; ?>"
                                            class="form-control text-center fw-bold bg-white border-0"
                                            value="<?php echo $settings['ref_level_' . $i]; ?>">
                                        <span class="input-group-text bg-white border-0 text-muted">%</span>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="row g-4 mt-2">
    <!-- Support Channels -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="fw-800 m-0 text-dark">Support Channels</h5>
                <button class="btn btn-sm btn-dark px-3 fw-bold rounded-3" onclick="openSupportLinkModal()">
                    <i class="fa-solid fa-plus me-1"></i> ADD
                </button>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php
                    $links = $pdo->query("SELECT * FROM support_links ORDER BY id DESC")->fetchAll();
                    foreach ($links as $link): ?>
                        <div class="col-12">
                            <div class="p-3 rounded-4 border d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-3">
                                    <div style="width: 40px; height: 40px; background: <?php echo $link['color_bg']; ?>; color: white; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                        <i class="<?php echo $link['icon']; ?>"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark small"><?php echo htmlspecialchars($link['platform_name']); ?></div>
                                        <div class="text-muted text-truncate" style="font-size: 0.65rem; max-width: 150px;"><?php echo htmlspecialchars($link['link']); ?></div>
                                    </div>
                                </div>
                                <a href="/api/admin_actions?action=delete_support_link&id=<?php echo $link['id']; ?>"
                                    class="btn btn-sm text-danger p-0" onclick="return confirm('Disconnect this channel?')">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Member Tiers -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="fw-800 m-0 text-dark">Member Tiers</h5>
                <button class="btn btn-sm btn-dark px-3 fw-bold rounded-3" onclick="openTierModal()">
                    <i class="fa-solid fa-plus me-1"></i> NEW
                </button>
            </div>
            <div class="table-responsive p-4">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Tier</th>
                            <th>Threshold</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $tiers = $pdo->query("SELECT * FROM tiers ORDER BY min_investment ASC")->fetchAll();
                        foreach ($tiers as $t): ?>
                            <tr>
                                <td>
                                    <span class="badge" style="background: <?php echo $t['color']; ?>; color: white; padding: 6px 12px; border-radius: 8px;">
                                        <i class="<?php echo $t['icon']; ?> me-1"></i> <?php echo $t['name']; ?>
                                    </span>
                                </td>
                                <td><span class="text-muted small fw-bold">$<?php echo number_format($t['min_investment']); ?>+</span></td>
                                <td class="text-end">
                                    <button onclick='editTier(<?php echo json_encode($t); ?>)' class="btn btn-sm btn-light border fw-bold px-2">
                                        <i class="fa-solid fa-pen-nib"></i>
                                    </button>
                                    <a href="/api/admin_actions?action=delete_tier&id=<?php echo $t['id']; ?>"
                                        class="btn btn-sm btn-light border text-danger fw-bold px-2" onclick="return confirm('Delete this tier?')">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-3">
    <!-- Live Feed Broadcaster -->
    <div class="col-12">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-dark text-white py-3 px-4 rounded-top-4 d-flex justify-content-between align-items-center">
                <h5 class="m-0 fw-bold"><i class="fa-solid fa-bullhorn me-2"></i> Broadcast Feed Broadcaster</h5>
                <span class="badge bg-primary px-3 py-2 rounded-pill fw-bold" style="font-size:0.7rem;">TELEGRAM CHANNEL MODE</span>
            </div>
            <div class="card-body p-4">
                <form data-ajax-form data-reload action="/api/admin_actions?action=post_to_feed" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase">Broadcast Notice Message</label>
                        <textarea name="feed_message" class="form-control rounded-3 border-light-subtle" rows="5" placeholder="⚡ <b>PROMO DISCOUNT PROTOCOL ENGAGED!</b>&#10;&#10;Upgrade your AI cluster hash stakes inside the next 12 hours to secure an automatic +15% yield boost! 📈" required></textarea>
                        <div class="form-text text-muted small mt-2">
                            <i class="fa-solid fa-circle-info me-1"></i> Supports secure styling: <b>&lt;b&gt;bold text&lt;/b&gt;</b>, <i>&lt;i&gt;italic text&lt;/i&gt;</i>, and emojis. This message will be instantly beamed to all investors viewing the Broadcast Feed page!
                        </div>
                    </div>
                    <button type="submit" class="btn btn-dark py-3 px-5 fw-bold rounded-pill shadow-sm">
                        <i class="fa-solid fa-paper-plane me-2"></i> BROADCAST SIGNAL
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-3">
    <!-- Database Schema Repair Tool -->
    <div class="col-12">
        <div class="card shadow-sm border-0 rounded-4" style="border: 1px solid rgba(220, 53, 69, 0.15) !important;">
            <div class="card-header bg-danger text-white py-3 px-4 rounded-top-4 d-flex justify-content-between align-items-center">
                <h5 class="m-0 fw-bold"><i class="fa-solid fa-screwdriver-wrench me-2"></i> Database Schema Sync & Repair</h5>
                <span class="badge bg-white text-danger px-3 py-2 rounded-pill fw-bold" style="font-size:0.7rem;">SYSTEM RECOVERY</span>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small">
                    If you are migrating or upgrading the platform to a live production server, the existing database tables (like <b>transactions</b>) might be missing critical value ENUMs (such as <code>'hourly_profit'</code>, <code>'manual_adjustment'</code>, or <code>'investment'</code>). Running this utility will automatically repair the schema, sync all columns, and enable perfect, real-time logging.
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <form data-ajax-form data-reload action="/api/admin_actions?action=sync_database_schema" method="POST" style="margin: 0;">
                        <button type="submit" class="btn btn-danger py-3 px-5 fw-bold rounded-pill shadow-sm" onclick="return confirm('Initiate database repair and column modification?')">
                            <i class="fa-solid fa-database me-2"></i> REPAIR & SYNC LIVE DATABASE
                        </button>
                    </form>
                    <a href="/api/admin_actions?action=download_db_backup" class="btn btn-dark py-3 px-5 fw-bold rounded-pill shadow-sm" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center; margin: 0;">
                        <i class="fa-solid fa-download me-2"></i> DOWNLOAD SQL BACKUP
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Support Link Modal -->
<div class="modal fade" id="supportLinkModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-5 shadow-lg">
            <div class="modal-header border-0 p-4">
                <h5 class="fw-800 text-dark m-0">Support Protocol</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form data-ajax-form data-reload action="/api/admin_actions?action=add_support_link" method="POST">
                <div class="modal-body p-4 pt-0">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold small text-muted text-uppercase">Platform Name</label>
                            <input type="text" name="platform_name" class="form-control" placeholder="e.g. WhatsApp" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small text-muted text-uppercase">Link</label>
                            <input type="url" name="link" class="form-control" placeholder="https://..." required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Icon Class</label>
                            <input type="text" name="icon" class="form-control" value="fa-brands fa-whatsapp" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Color</label>
                            <input type="color" name="color_bg" class="form-control form-control-color w-100" value="#25d366" style="height: 48px;">
                        </div>
                    </div>
                </div>
                <div class="p-4 pt-0">
                    <button type="submit" class="btn btn-dark w-100 py-3 fw-bold rounded-4 shadow-sm">DEPLOY</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tier Modal -->
<div class="modal fade" id="tierModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-5 shadow-lg">
            <div class="modal-header border-0 p-4">
                <h5 class="fw-800 text-dark m-0" id="tierModalTitle">Investor Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form data-ajax-form data-reload action="/api/admin_actions?action=save_tier" method="POST">
                <div class="modal-body p-4 pt-0">
                    <input type="hidden" name="id" id="tier-id">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold small text-muted text-uppercase">Tier Name</label>
                            <input type="text" name="name" id="tier-name" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small text-muted text-uppercase">Threshold ($)</label>
                            <input type="number" name="min_investment" id="tier-threshold" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Icon</label>
                            <input type="text" name="icon" id="tier-icon" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Color</label>
                            <input type="color" name="color" id="tier-color" class="form-control form-control-color w-100" style="height: 48px;">
                        </div>
                    </div>
                </div>
                <div class="p-4 pt-0">
                    <button type="submit" class="btn btn-dark w-100 py-3 fw-bold rounded-4 shadow-sm">SAVE TIER</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sLinkModal = new bootstrap.Modal(document.getElementById('supportLinkModal'));
        window.openSupportLinkModal = () => sLinkModal.show();

        const tModal = new bootstrap.Modal(document.getElementById('tierModal'));
        window.openTierModal = () => {
            document.getElementById('tier-id').value = '';
            document.getElementById('tier-name').value = '';
            document.getElementById('tier-threshold').value = '0';
            document.getElementById('tierModalTitle').textContent = 'Define Investor Status';
            tModal.show();
        };

        window.editTier = (tier) => {
            document.getElementById('tier-id').value = tier.id;
            document.getElementById('tier-name').value = tier.name;
            document.getElementById('tier-threshold').value = tier.min_investment;
            document.getElementById('tier-icon').value = tier.icon;
            document.getElementById('tier-color').value = tier.color;
            document.getElementById('tierModalTitle').textContent = 'Configure Tier: ' + tier.name;
            tModal.show();
        };
    });

    window.onSettingsSave = (data) => {
        if (window.showToast) showToast('System architecture updated successfully');
    };
</script>
