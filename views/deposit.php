<?php
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='/login';</script>";
    exit;
}

require_once __DIR__ . '/../includes/currency_helper.php';

// Fetch deposit methods
$stmt = $pdo->query("SELECT * FROM payment_methods WHERE is_active = 1");
$methods = $stmt->fetchAll();

// Update rates dynamically if needed
foreach ($methods as &$m) {
    if ($m['currency'] !== 'USD' && $m['currency'] !== 'USDT') {
        $m['rate'] = get_dynamic_rate($m['currency']);
    }
}
unset($m);

// Fetch active deposit bonus setting
$settings_stmt = $pdo->query("SELECT deposit_bonus FROM settings LIMIT 1");
$settings = $settings_stmt->fetch();
$deposit_bonus = (float)($settings['deposit_bonus'] ?? 0);
?>

<div class="full-screen-view" style="min-height: 100vh; background: var(--bs-body-bg); display: flex; flex-direction: column;">
    <header class="view-header">
        <a href="javascript:handleDepositBack()"><i class="fa-solid fa-arrow-left"></i></a>
        <h2 id="view-title">Deposit Intelligence</h2>
    </header>

    <div class="deposit-container" style="padding: 20px; padding-bottom: 100px; flex: 1;">
    
    <!-- Step 1: Select Method -->
    <div id="method-selection">
        <?php if ($deposit_bonus > 0): ?>
            <div class="card" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.12) 0%, rgba(168, 85, 247, 0.12) 100%); border: 1px solid rgba(99, 102, 241, 0.25); border-radius: 16px; padding: 18px; margin-bottom: 20px; display: flex; flex-direction: row; align-items: center; gap: 15px; box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.05); color: var(--bs-body-color);">
                <div style="width: 48px; height: 48px; min-width: 48px; border-radius: 12px; background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); display: flex; align-items: center; justify-content: center; color: white; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);">
                    <i class="fa-solid fa-gift" style="font-size: 1.4rem;"></i>
                </div>
                <div style="flex: 1;">
                    <div style="font-size: 0.72rem; font-weight: 700; color: #a855f7; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px;">Exclusive Funding Offer</div>
                    <h4 style="margin: 0; font-size: 0.98rem; font-weight: 800; color: inherit;"><?php echo number_format($deposit_bonus, 2); ?>% Deposit Bonus Active!</h4>
                    <p style="margin: 4px 0 0 0; font-size: 0.75rem; color: var(--text-muted); line-height: 1.4;">Receive an automatic <?php echo number_format($deposit_bonus, 2); ?>% extra balance on all approved deposits instantly.</p>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="section-title" style="margin-top: 10px;">Select Payment Method</div>
        <div style="display: grid; gap: 15px;">
            <?php foreach ($methods as $method): ?>
                <div class="card method-card" 
                     onclick="selectMethod(<?php echo htmlspecialchars(json_encode($method)); ?>)"
                     style="display: flex; align-items: center; gap: 20px; padding: 20px; cursor: pointer; transition: var(--transition); border: 1px solid var(--glass-border); background: rgba(255,255,255,0.02);">
                    <div style="width: 50px; height: 50px; border-radius: 12px; background: rgba(59, 130, 246, 0.1); display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-building-columns" style="font-size: 1.5rem; color: var(--primary);"></i>
                    </div>
                    <div style="flex: 1;">
                        <h3 style="margin: 0; font-size: 1.1rem;"><?php echo htmlspecialchars($method['name']); ?></h3>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">Instant processing • 24/7</div>
                    </div>
                    <i class="fa-solid fa-chevron-right" style="color: var(--text-muted); font-size: 0.9rem;"></i>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Step 2: Payment Details -->
    <div id="payment-details" style="display: none;">
        <div onclick="showMethods()" style="display: inline-flex; align-items: center; gap: 8px; color: var(--text-muted); font-size: 0.85rem; cursor: pointer; margin-bottom: 20px;">
            <i class="fa-solid fa-arrow-left"></i> Back
        </div>

        <div class="card" style="padding: 25px; border: 1px solid rgba(59, 130, 246, 0.2); background: radial-gradient(circle at top right, rgba(59, 130, 246, 0.05), transparent), var(--bg-card);">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 25px;">
                <div id="selected-icon" style="width: 45px; height: 45px; border-radius: 10px; background: var(--primary); display: flex; align-items: center; justify-content: center; color: white;">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
                <div>
                    <h2 id="selected-method-name" style="margin: 0; font-size: 1.2rem;">Method Deposit</h2>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Add funds to your account</div>
                </div>
            </div>

            <h3 style="text-align: center; font-size: 1rem; margin-bottom: 20px;">Payment Details</h3>

            <div id="qr-container" style="display: none; text-align: center; margin-bottom: 25px;">
                <p style="font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 10px;">Scan to Initiate Transfer</p>
                <img id="method-qr" src="" class="img-fluid rounded-4 shadow-lg border border-primary border-opacity-25" style="max-width: 180px; padding: 10px; background: white;">
            </div>

            <div id="dynamic-fields" style="display: grid; gap: 12px; margin-bottom: 20px;">
                <!-- Fields injected here -->
            </div>

            <div id="range-text" style="text-align: center; font-size: 0.8rem; color: var(--accent); font-weight: 700; margin-bottom: 20px;">
                Secure & Instant Transfer
            </div>

            <!-- Instructions -->
            <div style="padding: 15px; background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.1); border-radius: 12px; margin-bottom: 25px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px; color: var(--primary); font-weight: 700; font-size: 0.85rem;">
                    <i class="fa-solid fa-circle-info"></i> Instructions
                </div>
                <div id="method-instructions" style="font-size: 0.75rem; color: var(--text-muted); line-height: 1.6;">
                    1. Send the amount to the provided details.<br>
                    2. Take a screenshot of the confirmation.<br>
                    3. Enter the Amount and Transaction ID below.
                </div>
            </div>

            <form id="deposit-form" data-ajax-form data-action="/api/deposit" data-callback="onDepositSuccess" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="method_id" id="form-method-id">
                
                <div class="form-group">
                    <label>Deposit Amount (USD)</label>
                    <div style="position: relative;">
                        <i class="fa-solid fa-dollar-sign" style="position: absolute; left: 15px; top: 15px; color: var(--text-muted);"></i>
                        <input type="number" step="0.01" name="amount" id="deposit-amount" class="form-control" placeholder="Enter amount" style="padding-left: 45px;" required>
                    </div>
                </div>

                <div id="conversion-display" style="display: none; padding: 15px; border-radius: 12px; background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); margin-bottom: 20px; text-align: center;">
                    <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px;">Payment magnitude with protocol fees</div>
                    <div style="font-size: 1.1rem; font-weight: 800; color: var(--accent);">
                        You will send: <span id="conv-value">0.00</span> <span id="conv-currency">USD</span>
                    </div>
                    <div id="fee-breakdown" style="font-size: 0.65rem; color: var(--text-muted); mt-2">
                        Includes: <span id="fee-fixed">$0.00</span> fixed + <span id="fee-percent">0%</span> variable fee
                    </div>
                    <div style="font-size: 0.6rem; color: var(--text-muted); margin-top: 5px;">Rate: 1 USD = <span id="conv-rate">1.00</span> <span id="conv-currency-small">USD</span></div>
                </div>

                <div class="form-group">
                    <label>Payment Screenshot (Required)</label>
                    <div style="position: relative;">
                        <i class="fa-solid fa-image" style="position: absolute; left: 15px; top: 15px; color: var(--text-muted);"></i>
                        <input type="file" name="screenshot" class="form-control" accept="image/*" style="padding-left: 45px;" required>
                    </div>
                    <p style="font-size: 0.65rem; color: var(--text-muted); margin-top: 5px;">Upload a clear screenshot of your payment confirmation.</p>
                </div>

                <div class="form-group">
                    <label>Transaction ID (Optional)</label>
                    <div style="position: relative;">
                        <i class="fa-solid fa-hashtag" style="position: absolute; left: 15px; top: 15px; color: var(--text-muted);"></i>
                        <input type="text" name="transaction_id" class="form-control" placeholder="Enter TID if available" style="padding-left: 45px;">
                    </div>
                    <p style="font-size: 0.65rem; color: var(--text-muted); margin-top: 5px;">Enter the ID from your payment receipt (if any).</p>
                </div>

                <button type="submit" class="btn" style="margin-top: 10px;">
                    Submit Deposit <i class="fa-solid fa-paper-plane" style="margin-left: 8px;"></i>
                </button>
            </form>
        </div>
    </div>

</div>

<script>
    let activeMethod = null;

    function selectMethod(method) {
        activeMethod = method;
        document.getElementById('method-selection').style.display = 'none';
        document.getElementById('payment-details').style.display = 'block';
        
        document.getElementById('selected-method-name').textContent = method.name + ' Deposit';
        document.getElementById('form-method-id').value = method.id;

        // Currency conversion setup
        const convBox = document.getElementById('conversion-display');
        const hasFees = (parseFloat(method.fixed_fee) > 0 || parseFloat(method.percent_fee) > 0);
        
        if ((method.currency && method.currency !== 'USD' && method.currency !== 'USDT') || hasFees) {
            convBox.style.display = 'block';
            document.getElementById('conv-currency').textContent = method.currency || 'USD';
            document.getElementById('conv-currency-small').textContent = method.currency || 'USD';
            document.getElementById('conv-rate').textContent = parseFloat(method.rate || 1).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 8});
            updateConversion();
        } else {
            convBox.style.display = 'none';
        }

        // QR Code Setup
        const qrBox = document.getElementById('qr-container');
        if (method.qr_code) {
            qrBox.style.display = 'block';
            document.getElementById('method-qr').src = '/' + method.qr_code;
        } else {
            qrBox.style.display = 'none';
        }
        
        // Dynamic Fields
        const fieldsBox = document.getElementById('dynamic-fields');
        fieldsBox.innerHTML = '';
        try {
            const fields = JSON.parse(method.copyable_fields || '[]');
            fields.forEach((f, i) => {
                fieldsBox.innerHTML += `
                    <div class="card" style="padding: 12px 20px; margin-bottom: 0; background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,0.05);">
                        <div style="font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px;">${f.label}</div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div id="copy-val-${i}" style="font-size: 1rem; font-weight: 800; letter-spacing: 0.5px; word-break: break-all;">${f.value}</div>
                            <i class="fa-regular fa-copy" style="color: var(--primary); cursor: pointer; padding: 5px;" onclick="copyToClipboard('${f.value}')"></i>
                        </div>
                    </div>
                `;
            });
        } catch(e) { console.error('Parse error', e); }

        if (method.details) {
            document.getElementById('method-instructions').innerHTML = method.details.replace(/\n/g, '<br>');
        }

        // Update header title
        document.getElementById('view-title').textContent = method.name;
    }

    function updateConversion() {
        if (!activeMethod || !activeMethod.rate) return;
        const amount = parseFloat(document.getElementById('deposit-amount').value) || 0;
        const fixedFee = parseFloat(activeMethod.fixed_fee) || 0;
        const percentFee = parseFloat(activeMethod.percent_fee) || 0;
        
        const totalUSD = amount + fixedFee + (amount * (percentFee / 100));
        const converted = totalUSD * parseFloat(activeMethod.rate);
        
        document.getElementById('conv-value').textContent = converted.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 8});
        document.getElementById('fee-fixed').textContent = '$' + fixedFee.toFixed(2);
        document.getElementById('fee-percent').textContent = percentFee + '%';
    }

    document.getElementById('deposit-amount').addEventListener('input', updateConversion);

    function showMethods() {
        document.getElementById('method-selection').style.display = 'block';
        document.getElementById('payment-details').style.display = 'none';
        document.getElementById('view-title').textContent = 'Deposit Intelligence';
    }

    function handleDepositBack() {
        if (document.getElementById('payment-details').style.display === 'block') {
            showMethods();
        } else {
            history.back();
        }
    }

    function copyToClipboard(val) {
        navigator.clipboard.writeText(val).then(() => showToast('Copied to clipboard'));
    }

    window.onDepositSuccess = (data) => {
        navigateTo('transactions');
    };
</script>
</div>
