<?php
$deposit_methods = $pdo->query("SELECT * FROM payment_methods")->fetchAll();
$withdrawal_methods = $pdo->query("SELECT * FROM withdrawal_methods")->fetchAll();
$plans = $pdo->query("SELECT * FROM plans")->fetchAll();
?>

<div class="row mb-5 align-items-center">
    <div class="col">
        <h2 class="fw-800 m-0 text-dark">Operational Protocols</h2>
        <p class="text-muted m-0">Configure investment architectures and payment gateways</p>
    </div>
</div>


<div class="row g-4 mb-5">
    <!-- Deposit Methods -->
    <div class="col-xl-6">
        <div class="card h-100 overflow-hidden">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-800 m-0 text-dark">Inflow Gateways</h5>
                <button class="btn btn-sm btn-dark fw-bold px-3" onclick="openMethodModal('deposit')">
                    <i class="fa-solid fa-plus me-1"></i> NEW
                </button>
            </div>
            <div class="table-responsive p-4">
                <table class="table align-middle datatable-basic">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Gateway</th>
                            <th>Thresholds</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deposit_methods as $m): ?>
                        <tr>
                            <td><span class="fw-800 text-muted small">#<?php echo $m['id']; ?></span></td>
                            <td class="text-dark fw-bold small"><?php echo htmlspecialchars($m['name']); ?></td>
                            <td>
                                <div class="text-muted" style="font-size: 0.75rem;">Limit: <span class="text-dark fw-bold">$<?php echo number_format($m['min_amount']); ?>+</span></div>
                            </td>
                            <td>
                                <?php if ($m['is_active']): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success">LIVE</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-muted">OFFLINE</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button onclick="editMethod('deposit', <?php echo htmlspecialchars(json_encode($m)); ?>)" class="btn btn-sm btn-light border fw-bold px-3">
                                    <i class="fa-solid fa-pen-nib"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Withdrawal Methods -->
    <div class="col-xl-6">
        <div class="card h-100 overflow-hidden">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-800 m-0 text-dark">Outflow Gateways</h5>
                <button class="btn btn-sm btn-dark fw-bold px-3" onclick="openMethodModal('withdraw')">
                    <i class="fa-solid fa-plus me-1"></i> NEW
                </button>
            </div>
            <div class="table-responsive p-4">
                <table class="table align-middle datatable-basic">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Gateway</th>
                            <th>Thresholds</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($withdrawal_methods as $m): ?>
                        <tr>
                            <td><span class="fw-800 text-muted small">#<?php echo $m['id']; ?></span></td>
                            <td class="text-dark fw-bold small"><?php echo htmlspecialchars($m['name']); ?></td>
                            <td>
                                <div class="text-muted" style="font-size: 0.75rem;">Limit: <span class="text-dark fw-bold">$<?php echo number_format($m['min_amount']); ?>+</span></div>
                            </td>
                            <td>
                                <?php if ($m['is_active']): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success">LIVE</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-muted">OFFLINE</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button onclick="editMethod('withdraw', <?php echo htmlspecialchars(json_encode($m)); ?>)" class="btn btn-sm btn-light border fw-bold px-3">
                                    <i class="fa-solid fa-pen-nib"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<!-- Method Modal -->
<div class="modal fade shadow" id="bs-method-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-5 overflow-hidden">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="modal-title fw-800 text-dark" id="method-modal-title">Gateway Interface</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="method-form" data-ajax-form data-reload action="/api/admin_actions?action=save_method" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="method-id">
                    <input type="hidden" name="type" id="method-type">
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted text-uppercase">Gateway Name</label>
                        <input type="text" name="name" id="method-name" class="form-control py-3 rounded-4" placeholder="e.g. Bitcoin Network" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted text-uppercase">Operational Logistics</label>
                        <textarea name="details" id="method-details" class="form-control py-3 rounded-4" rows="2" placeholder="Instructions or address details..." required></textarea>
                    </div>

                    <!-- Copyable Fields Section -->
                    <div id="copyable-fields-container" class="mb-4" style="display: none;">
                        <label class="form-label fw-bold small text-muted text-uppercase d-flex justify-content-between">
                            INTELLIGENCE FIELDS
                            <button type="button" class="btn btn-link btn-sm p-0 text-primary fw-bold text-decoration-none" onclick="addCopyableField()" style="font-size: 0.7rem;">+ ADD FIELD</button>
                        </label>
                        <div id="copyable-list" class="d-flex flex-column gap-2 mb-2"></div>
                        <input type="hidden" name="copyable_fields" id="copyable-json">
                        
                        <div class="mt-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">QR Code Interface (Optional)</label>
                            <input type="file" name="qr_code" class="form-control py-2 rounded-4" accept="image/*">
                            <div id="current-qr" class="mt-2" style="display: none;">
                                <img id="qr-preview" src="" class="img-fluid rounded-3 border" style="max-height: 100px;">
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Gateway Currency</label>
                            <input type="text" name="currency" id="method-currency" class="form-control py-3 rounded-4" placeholder="e.g. BTC, USDT, NGN" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Exchange Rate (1 USD = X)</label>
                            <input type="number" name="rate" id="method-rate" step="0.00000001" class="form-control py-3 rounded-4" required>
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Min Threshold ($)</label>
                            <input type="number" name="min_amount" id="method-min" step="0.01" class="form-control py-3 rounded-4" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Max Capacity ($)</label>
                            <input type="number" name="max_amount" id="method-max" step="0.01" class="form-control py-3 rounded-4" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Fixed Fee ($)</label>
                            <input type="number" name="fixed_fee" id="method-fixed-fee" step="0.01" class="form-control py-3 rounded-4" value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Percentage Fee (%)</label>
                            <input type="number" name="percent_fee" id="method-percent-fee" step="0.01" class="form-control py-3 rounded-4" value="0.00">
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold small text-muted text-uppercase">Service Status</label>
                        <select name="is_active" id="method-status" class="form-select py-3 rounded-4">
                            <option value="1">Online</option>
                            <option value="0">Offline</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light py-3 px-4 fw-bold rounded-4" data-bs-dismiss="modal">CANCEL</button>
                    <button type="submit" class="btn btn-dark py-3 px-4 fw-bold rounded-4 shadow-sm">SAVE GATEWAY</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var methodModal = new bootstrap.Modal(document.getElementById('bs-method-modal'));


        window.openMethodModal = function(type) {
            document.getElementById('method-form').reset();
            document.getElementById('method-id').value = '';
            document.getElementById('method-type').value = type;
            document.getElementById('method-currency').value = 'USD';
            document.getElementById('method-rate').value = '1.00000000';
            document.getElementById('method-fixed-fee').value = '0.00';
            document.getElementById('method-percent-fee').value = '0.00';
            document.getElementById('method-modal-title').textContent = (type === 'deposit' ? 'Add Inflow Gateway' : 'Add Outflow Gateway');
            document.getElementById('copyable-fields-container').style.display = (type === 'deposit' ? 'block' : 'none');
            document.getElementById('copyable-list').innerHTML = '';
            methodModal.show();
        };

        window.editMethod = function(type, method) {
            document.getElementById('method-id').value = method.id;
            document.getElementById('method-type').value = type;
            document.getElementById('method-name').value = method.name;
            document.getElementById('method-details').value = method.details || method.instructions;
            document.getElementById('method-min').value = method.min_amount;
            document.getElementById('method-max').value = method.max_amount;
            document.getElementById('method-status').value = method.is_active;
            document.getElementById('method-currency').value = method.currency || 'USD';
            document.getElementById('method-rate').value = method.rate || '1.00000000';
            document.getElementById('method-fixed-fee').value = method.fixed_fee || '0.00';
            document.getElementById('method-percent-fee').value = method.percent_fee || '0.00';
            document.getElementById('method-modal-title').textContent = (type === 'deposit' ? 'Edit Inflow Gateway' : 'Edit Outflow Gateway');
            const cContainer = document.getElementById('copyable-fields-container');
            const qrPreviewDiv = document.getElementById('current-qr');
            const qrPreviewImg = document.getElementById('qr-preview');
            
            if (type === 'deposit') {
                cContainer.style.display = 'block';
                const list = document.getElementById('copyable-list');
                list.innerHTML = '';
                try {
                    const fields = JSON.parse(method.copyable_fields || '[]');
                    fields.forEach(f => addCopyableField(f.label, f.value));
                } catch(e) {}

                if (method.qr_code) {
                    qrPreviewImg.src = '/' + method.qr_code;
                    qrPreviewDiv.style.display = 'block';
                } else {
                    qrPreviewDiv.style.display = 'none';
                }
            } else {
                cContainer.style.display = 'none';
            }
            methodModal.show();
        };

        window.addCopyableField = function(label = '', value = '') {
            const div = document.createElement('div');
            div.className = 'd-flex gap-2';
            div.innerHTML = `
                <input type="text" class="form-control py-2 rounded-3 fw-bold small f-label" placeholder="Label" value="${label}">
                <input type="text" class="form-control py-2 rounded-3 fw-bold small f-value" placeholder="Value" value="${value}">
                <button type="button" class="btn btn-sm btn-light border" onclick="this.parentElement.remove()"><i class="fa-solid fa-times"></i></button>
            `;
            document.getElementById('copyable-list').appendChild(div);
        };

        document.getElementById('method-form').addEventListener('submit', (e) => {
            const type = document.getElementById('method-type').value;
            if (type === 'deposit') {
                const fields = [];
                document.querySelectorAll('#copyable-list > div').forEach(div => {
                    const label = div.querySelector('.f-label').value;
                    const value = div.querySelector('.f-value').value;
                    if (label && value) fields.push({label, value});
                });
                document.getElementById('copyable-json').value = JSON.stringify(fields);
            }
        });
    });
</script>
