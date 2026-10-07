<?php
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='/login';</script>";
    exit;
}

// Fetch user balance
$stmt = $pdo->prepare("SELECT deposit_balance, earnings_balance FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

require_once __DIR__ . '/../includes/currency_helper.php';

// Fetch active withdrawal methods
$stmt = $pdo->query("SELECT * FROM withdrawal_methods WHERE is_active = 1");
$methods = $stmt->fetchAll();

// Update rates dynamically if needed
foreach ($methods as &$m) {
    if ($m['currency'] !== 'USD' && $m['currency'] !== 'USDT') {
        $m['rate'] = get_dynamic_rate($m['currency']);
    }
}
unset($m);
?>

<div class="full-screen-view" style="min-height: 100vh; background: var(--bs-body-bg); display: flex; flex-direction: column;">
    <header class="view-header">
        <a href="javascript:handleWithdrawBack()"><i class="fa-solid fa-arrow-left"></i></a>
        <h2 id="w-view-title">Disbursement Protocol</h2>
    </header>

    <div class="withdraw-container" style="padding: 20px; padding-bottom: 100px; flex: 1;">
    
    <!-- Balance Card -->
    <div class="card" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%); border: 1px solid rgba(16, 185, 129, 0.2); text-align: center; padding: 25px; margin-bottom: 25px;">
        <div style="width: 50px; height: 50px; border-radius: 50%; background: rgba(16, 185, 129, 0.1); display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto; color: var(--accent); font-size: 1.5rem; box-shadow: var(--neon-green);">
            <i class="fa-solid fa-wallet"></i>
        </div>
        <p style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;">Available for Withdrawal</p>
        <h1 style="color: var(--accent); font-size: 2.2rem; font-weight: 800; margin: 0;">$<?php echo number_format($user['deposit_balance'] + $user['earnings_balance'], 2); ?></h1>
    </div>

    <!-- Step 1: Select Method -->
    <div id="withdraw-selection">
        <div class="section-title" style="margin-top: 10px;">Select Withdrawal Method</div>
        <div style="display: grid; gap: 15px;">
            <?php foreach ($methods as $method): ?>
                <div class="card" 
                     onclick='selectWithdrawMethod(<?php echo json_encode($method); ?>)'
                     style="display: flex; align-items: center; gap: 20px; padding: 20px; cursor: pointer; transition: var(--transition); border: 1px solid var(--glass-border); background: rgba(255,255,255,0.02);">
                    <div style="width: 45px; height: 45px; border-radius: 10px; background: rgba(16, 185, 129, 0.1); display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-money-bill-transfer" style="font-size: 1.2rem; color: var(--accent);"></i>
                    </div>
                    <div style="flex: 1;">
                        <h3 style="margin: 0; font-size: 1rem;"><?php echo htmlspecialchars($method['name']); ?></h3>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">24-48h Processing • Secured</div>
                    </div>
                    <i class="fa-solid fa-chevron-right" style="color: var(--text-muted); font-size: 0.8rem;"></i>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Step 2: Withdrawal Form -->
    <div id="withdraw-form-view" style="display: none;">
        <div onclick="showWithdrawMethods()" style="display: inline-flex; align-items: center; gap: 8px; color: var(--text-muted); font-size: 0.85rem; cursor: pointer; margin-bottom: 20px;">
            <i class="fa-solid fa-arrow-left"></i> Back to Methods
        </div>

        <div class="card" style="padding: 25px; border: 1px solid rgba(16, 185, 129, 0.2); background: radial-gradient(circle at top right, rgba(16, 185, 129, 0.05), transparent), var(--bg-card);">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 25px;">
                <div id="w-selected-icon" style="width: 45px; height: 45px; border-radius: 10px; background: var(--accent); display: flex; align-items: center; justify-content: center; color: white;">
                    <i class="fa-solid fa-landmark"></i>
                </div>
                <div>
                    <h2 id="w-selected-method-name" style="margin: 0; font-size: 1.2rem;">Method</h2>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Enter your payout details</div>
                </div>
            </div>

            <form id="withdraw-form" data-ajax-form data-action="/api/withdraw" data-callback="onWithdrawSuccess" method="POST">
                <input type="hidden" name="method_id" id="w-form-method-id">
                
                <div class="form-group">
                    <label>Account Holder Name</label>
                    <div style="position: relative;">
                        <i class="fa-regular fa-user" style="position: absolute; left: 15px; top: 15px; color: var(--text-muted);"></i>
                        <input type="text" name="account_name" class="form-control" placeholder="Full name on account" style="padding-left: 45px;" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Account / Wallet Address</label>
                    <div style="position: relative;">
                        <i class="fa-solid fa-hashtag" style="position: absolute; left: 15px; top: 15px; color: var(--text-muted);"></i>
                        <input type="text" name="account_number" class="form-control" placeholder="Enter number or address" style="padding-left: 45px;" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Amount (USD)</label>
                    <div style="position: relative;">
                        <i class="fa-solid fa-dollar-sign" style="position: absolute; left: 15px; top: 15px; color: var(--text-muted);"></i>
                        <input type="number" step="0.01" name="amount" id="withdraw-amount" class="form-control" placeholder="0.00" style="padding-left: 45px;" required>
                    </div>
                </div>

                <div id="w-conversion-display" style="display: none; padding: 15px; border-radius: 12px; background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.1); margin-bottom: 20px; text-align: center;">
                    <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px;">Disbursement magnitude with protocol fees</div>
                    <div style="font-size: 1.1rem; font-weight: 800; color: var(--accent);">
                        You will receive: <span id="w-conv-value">0.00</span> <span id="w-conv-currency">USD</span>
                    </div>
                    <div id="w-fee-breakdown" style="font-size: 0.65rem; color: var(--text-muted); mt-2">
                        Deductions: <span id="w-fee-fixed">$0.00</span> fixed + <span id="w-fee-percent">0%</span> variable fee
                    </div>
                    <div style="font-size: 0.6rem; color: var(--text-muted); margin-top: 5px;">Rate: 1 USD = <span id="w-conv-rate">1.00</span> <span id="w-conv-currency-small">USD</span></div>
                </div>

                <!-- Instructions -->
                <div id="w-instructions-box" style="padding: 15px; background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.1); border-radius: 12px; margin-bottom: 25px; display: none;">
                    <div style="display: flex; align-items: center; gap: 8px; color: var(--accent); font-weight: 700; font-size: 0.8rem; margin-bottom: 8px;">
                        <i class="fa-solid fa-circle-info"></i> Instructions
                    </div>
                    <div id="w-instructions-text" style="font-size: 0.7rem; color: var(--text-muted); line-height: 1.5;"></div>
                </div>

                <button type="submit" class="btn" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: var(--neon-green); margin-top: 10px;">
                    Request Payout <i class="fa-solid fa-paper-plane" style="margin-left: 8px;"></i>
                </button>
            </form>
        </div>
    </div>

</div>

<script>
    let activeWMethod = null;

    function selectWithdrawMethod(method) {
        activeWMethod = method;
        document.getElementById('withdraw-selection').style.display = 'none';
        document.getElementById('withdraw-form-view').style.display = 'block';
        
        document.getElementById('w-selected-method-name').textContent = method.name;
        document.getElementById('w-form-method-id').value = method.id;

        // Conversion logic
        const convBox = document.getElementById('w-conversion-display');
        const hasFees = (parseFloat(method.fixed_fee) > 0 || parseFloat(method.percent_fee) > 0);
        
        if ((method.currency && method.currency !== 'USD') || hasFees) {
            convBox.style.display = 'block';
            document.getElementById('w-conv-currency').textContent = method.currency || 'USD';
            document.getElementById('w-conv-currency-small').textContent = method.currency || 'USD';
            document.getElementById('w-conv-rate').textContent = parseFloat(method.rate || 1).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 8});
            updateWithdrawConversion();
        } else {
            convBox.style.display = 'none';
        }
        
        const instBox = document.getElementById('w-instructions-box');
        const instText = document.getElementById('w-instructions-text');
        
        if (method.instructions) {
            instText.innerHTML = method.instructions.replace(/\n/g, '<br>');
            instBox.style.display = 'block';
        } else {
            instBox.style.display = 'none';
        }

        // Update header title
        document.getElementById('w-view-title').textContent = method.name;
    }

    function updateWithdrawConversion() {
        if (!activeWMethod) return;
        const amount = parseFloat(document.getElementById('withdraw-amount').value) || 0;
        const fixedFee = parseFloat(activeWMethod.fixed_fee) || 0;
        const percentFee = parseFloat(activeWMethod.percent_fee) || 0;
        
        const totalFeeUSD = fixedFee + (amount * (percentFee / 100));
        const netUSD = Math.max(0, amount - totalFeeUSD);
        const converted = netUSD * (parseFloat(activeWMethod.rate) || 1);
        
        document.getElementById('w-conv-value').textContent = converted.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 8});
        document.getElementById('w-fee-fixed').textContent = '$' + fixedFee.toFixed(2);
        document.getElementById('fee-percent' in window ? '' : 'w-fee-percent').textContent = percentFee + '%';
        
        // Ensure percent fee element ID is correct
        const pFeeEl = document.getElementById('w-fee-percent');
        if (pFeeEl) pFeeEl.textContent = percentFee + '%';
    }

    document.getElementById('withdraw-amount').addEventListener('input', updateWithdrawConversion);

    function showWithdrawMethods() {
        document.getElementById('withdraw-selection').style.display = 'block';
        document.getElementById('withdraw-form-view').style.display = 'none';
        document.getElementById('w-view-title').textContent = 'Disbursement Protocol';
    }

    function handleWithdrawBack() {
        if (document.getElementById('withdraw-form-view').style.display === 'block') {
            showWithdrawMethods();
        } else {
            history.back();
        }
    }

    window.onWithdrawSuccess = (data) => {
        navigateTo('transactions');
    };
</script>
</div>
