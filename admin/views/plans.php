<?php
$plans = $pdo->query("SELECT * FROM plans")->fetchAll();
?>

<div class="row mb-5 align-items-center">
    <div class="col">
        <h2 class="fw-800 m-0 text-dark">Investment Protocols</h2>
        <p class="text-muted m-0">Manage fixed-price investment architectures and yields</p>
    </div>
    <div class="col-auto">
        <button class="btn btn-dark fw-bold px-4 py-3 rounded-4 shadow-sm" onclick="openPlanModal()">
            <i class="fa-solid fa-plus me-2"></i> DEPLOY NEW PROTOCOL
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-5 overflow-hidden">
    <div class="table-responsive p-4">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th class="ps-3">Protocol ID</th>
                    <th>Architecture</th>
                    <th>Hourly Yield</th>
                    <th>Investment Range</th>
                    <th>Status</th>
                    <th class="text-end pe-3">Operations</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plans as $p): ?>
                <tr>
                    <td class="ps-3"><span class="fw-800 text-muted small">#<?php echo $p['id']; ?></span></td>
                    <td>
                        <div class="text-dark fw-bold"><?php echo htmlspecialchars($p['name']); ?></div>
                        <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.6rem;">Compute Cluster</div>
                    </td>
                    <td>
                        <span class="text-primary fw-800"><?php echo $p['hourly_rate']; ?>%</span>
                        <div class="text-muted small fw-bold" style="font-size: 0.6rem;">PER HOUR</div>
                    </td>
                    <td>
                        <div class="text-dark fw-900">$<?php echo number_format($p['min_invest'], 2); ?> - $<?php echo number_format($p['max_invest'], 2); ?></div>
                        <div class="text-success small fw-bold" style="font-size: 0.6rem;">ENTRY RANGE</div>
                    </td>
                    <td>
                        <?php if ($p['is_active']): ?>
                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">OPERATIONAL</span>
                        <?php else: ?>
                            <span class="badge bg-secondary bg-opacity-10 text-muted rounded-pill px-3">DEACTIVATED</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end pe-3">
                        <button onclick="editPlan(<?php echo htmlspecialchars(json_encode($p)); ?>)" class="btn btn-sm btn-light border-0 fw-bold px-3 py-2 rounded-3">
                            <i class="fa-solid fa-sliders me-1"></i> CONFIGURE
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Plan Modal -->
<div class="modal fade" id="bs-plan-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-5 overflow-hidden">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="modal-title fw-800 text-dark" id="plan-modal-title">Protocol Configuration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="plan-form" data-ajax-form data-reload action="/api/admin_actions?action=save_plan" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="plan-id">
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted text-uppercase">Architecture Name</label>
                        <input type="text" name="name" id="plan-name" class="form-control bg-light border-0 py-3 rounded-4" placeholder="e.g. Neural Nexus" required>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">Yield Velocity (%/hr)</label>
                            <input type="number" name="hourly_rate" id="plan-rate" step="0.0001" class="form-control bg-light border-0 py-3 rounded-4" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">Min Invest ($)</label>
                            <input type="number" name="min_invest" id="plan-min" step="0.01" class="form-control bg-light border-0 py-3 rounded-4" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">Max Invest ($)</label>
                            <input type="number" name="max_invest" id="plan-max" step="0.01" class="form-control bg-light border-0 py-3 rounded-4" required>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold small text-muted text-uppercase">Operational Status</label>
                        <select name="is_active" id="plan-status" class="form-select bg-light border-0 py-3 rounded-4">
                            <option value="1">Operational</option>
                            <option value="0">Deactivated</option>
                        </select>
                    </div>
                </div>
                <div class="p-4 pt-0">
                    <button type="submit" class="btn btn-dark w-100 py-3 fw-bold rounded-4 shadow-sm">COMMIT ARCHITECTURE</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const planModal = new bootstrap.Modal(document.getElementById('bs-plan-modal'));

    window.openPlanModal = function() {
        document.getElementById('plan-form').reset();
        document.getElementById('plan-id').value = '';
        document.getElementById('plan-modal-title').textContent = 'Deploy New Protocol';
        planModal.show();
    };

    window.editPlan = function(plan) {
        document.getElementById('plan-id').value = plan.id;
        document.getElementById('plan-name').value = plan.name;
        document.getElementById('plan-rate').value = plan.hourly_rate;
        document.getElementById('plan-min').value = plan.min_invest;
        document.getElementById('plan-max').value = plan.max_invest;
        document.getElementById('plan-status').value = plan.is_active;
        document.getElementById('plan-modal-title').textContent = 'Configure Protocol: ' + plan.name;
        planModal.show();
    };
});
</script>
