<?php
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='/login';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user data for balance
$stmt = $pdo->prepare("SELECT deposit_balance, earnings_balance FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$total_balance = ($user['deposit_balance'] ?? 0) + ($user['earnings_balance'] ?? 0);

// Fetch active plans
$stmt = $pdo->query("SELECT * FROM plans WHERE is_active = 1");
$plans = $stmt->fetchAll();

// Fetch existing active investments for this user
$stmt = $pdo->prepare("SELECT plan_id, SUM(invested_amount) as total, MIN(created_at) as started FROM user_investments WHERE user_id = ? AND status = 'active' GROUP BY plan_id");
$stmt->execute([$user_id]);
$my_investments = $stmt->fetchAll(PDO::FETCH_UNIQUE);
?>

<div class="full-screen-view"
    style="min-height: 100vh; background: var(--bs-body-bg); display: flex; flex-direction: column;">
    <header class="view-header">
        <a href="javascript:handleInvestBack()"><i class="fa-solid fa-arrow-left"></i></a>
        <h2 id="view-title">AI Computing Plans</h2>
    </header>

    <div class="invest-container" style="padding: 20px; padding-bottom: 100px; flex: 1;">

        <!-- Step 1: Select Plan -->
        <div id="plan-selection">
            <div class="section-title" style="margin-top: 10px;">AI Computing Plans</div>
            <div style="display: grid; gap: 20px;">
                <?php foreach ($plans as $plan):
                    $has_invest = isset($my_investments[$plan['id']]);
                    $invest_data = $my_investments[$plan['id']] ?? null;
                    ?>
                    <div class="card" onclick='selectPlan(<?php echo json_encode($plan); ?>)'
                        style="padding: 0; overflow: hidden; cursor: pointer; transition: var(--transition); border: 1px solid <?php echo $has_invest ? 'rgba(16, 185, 129, 0.3)' : 'var(--glass-border)'; ?>; position: relative;">

                        <!-- Already Invested Ribbon -->
                        <?php if ($has_invest): ?>
                            <div
                                style="position: absolute; top: 12px; right: -30px; background: var(--accent); color: white; padding: 5px 40px; font-size: 0.6rem; font-weight: 800; transform: rotate(45deg); box-shadow: 0 0 15px rgba(16, 185, 129, 0.4); z-index: 10;">
                                INVESTED
                            </div>
                        <?php endif; ?>

                        <!-- Plan Header -->
                        <div
                            style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%); padding: 20px; border-bottom: 1px solid var(--glass-border);">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <h3
                                    style="margin: 0; color: var(--primary); display: flex; align-items: center; gap: 10px;">
                                    <i class="fa-solid fa-microchip"></i> <?php echo htmlspecialchars($plan['name']); ?>
                                </h3>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div>
                                    <div style="font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase;">
                                        Hourly Profit</div>
                                    <div style="font-size: 1.2rem; font-weight: 800; color: var(--accent);">
                                        <?php echo $plan['hourly_rate']; ?>%</div>
                                </div>
                                <div>
                                    <div style="font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase;">
                                        Daily Profit</div>
                                    <div style="font-size: 1.2rem; font-weight: 800; color: var(--accent);">
                                        <?php echo number_format($plan['hourly_rate'] * 24, 2); ?>%</div>
                                </div>
                            </div>
                        </div>

                        <!-- Investment Info or Action -->
                        <div
                            style="padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.1);">
                            <?php if ($has_invest): ?>
                                <div style="display: flex; gap: 20px;">
                                    <div>
                                        <span
                                            style="display: block; font-size: 0.55rem; text-transform: uppercase; color: var(--text-muted);">My
                                            Investment</span>
                                        <span
                                            style="font-weight: 700; color: var(--accent); font-size: 0.9rem;">$<?php echo number_format($invest_data['total'], 2); ?></span>
                                    </div>
                                    <div>
                                        <span
                                            style="display: block; font-size: 0.55rem; text-transform: uppercase; color: var(--text-muted);">Since</span>
                                        <span
                                            style="font-weight: 600; color: var(--text-main); font-size: 0.8rem;"><?php echo date('M j, Y', strtotime($invest_data['started'])); ?></span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">
                                    <span style="display: block; font-size: 0.6rem; text-transform: uppercase;">Investment Range</span>
                                    <span
                                        style="font-weight: 700; color: var(--text-main);">$<?php echo number_format($plan['min_invest'], 2); ?> - $<?php echo number_format($plan['max_invest'], 2); ?></span>
                                </div>
                            <?php endif; ?>
                            <div
                                style="width: 35px; height: 35px; border-radius: 50%; background: var(--glass); display: flex; align-items: center; justify-content: center; color: var(--primary);">
                                <i class="fa-solid fa-plus"></i>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Step 2: Confirm Investment -->
        <div id="invest-confirm" style="display: none;">

            <!-- Balance Info Card -->
            <div class="card"
                style="padding: 15px; background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.1); margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-wallet" style="color: var(--accent);"></i>
                    <span style="font-size: 0.85rem; font-weight: 600;">Your Total Balance</span>
                </div>
                <div style="font-weight: 800; color: var(--accent); font-size: 1rem;">
                    $<?php echo number_format($total_balance, 2); ?></div>
            </div>

            <div class="card"
                style="padding: 25px; border: 1px solid rgba(139, 92, 246, 0.2); background: radial-gradient(circle at top right, rgba(139, 92, 246, 0.05), transparent), var(--bg-card);">
                <div style="text-align: center; margin-bottom: 25px;">
                    <div
                        style="width: 60px; height: 60px; border-radius: 50%; background: rgba(139, 92, 246, 0.1); display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto; color: var(--secondary); font-size: 1.5rem; box-shadow: var(--neon-purple);">
                        <i class="fa-solid fa-vault"></i>
                    </div>
                    <h2 id="conf-plan-name" style="margin: 0;">Plan Name</h2>
                    <p style="font-size: 0.8rem; color: var(--text-muted);">Confirm your investment details</p>
                </div>

                <form id="invest-form" data-ajax-form data-action="/api/invest" data-callback="onInvestSuccess"
                    method="POST">
                    <input type="hidden" name="plan_id" id="form-plan-id">

                    <div class="form-group mb-4">
                        <label id="invest-amount-label">Enter Investment Amount (Min: $0 - Max: $0)</label>
                        <div style="position: relative;">
                            <i class="fa-solid fa-dollar-sign"
                                style="position: absolute; left: 15px; top: 15px; color: var(--text-muted);"></i>
                            <input type="number" name="amount" id="invest-amount-input" class="form-control"
                                style="padding-left: 45px; font-weight: 800;" step="0.01" required>
                        </div>
                    </div>

                    <!-- Calculator Preview -->
                    <div class="card"
                        style="background: rgba(0,0,0,0.2); padding: 20px; border-color: rgba(255,255,255,0.05);">
                        <div
                            style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 15px; letter-spacing: 1px;">
                            Profit Projection</div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div>
                                <div style="font-size: 0.65rem; color: var(--text-muted);">Hourly Profit</div>
                                <div id="p-hourly" style="font-size: 1.1rem; font-weight: 800; color: var(--accent);">
                                    $0.00</div>
                            </div>
                            <div>
                                <div style="font-size: 0.65rem; color: var(--text-muted);">Daily Profit</div>
                                <div id="p-daily" style="font-size: 1.1rem; font-weight: 800; color: var(--accent);">
                                    $0.00</div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn"
                        style="background: var(--gradient); box-shadow: var(--neon-purple); margin-top: 10px;">
                        Confirm Investment <i class="fa-solid fa-check-double" style="margin-left: 8px;"></i>
                    </button>
                </form>
            </div>
        </div>

    </div>

    <script>
        let activeRate = 0;
        let activeMin = 0;
        let activeMax = 0;

        function selectPlan(plan) {
            document.getElementById('plan-selection').style.display = 'none';
            document.getElementById('invest-confirm').style.display = 'block';

            document.getElementById('conf-plan-name').textContent = plan.name;
            document.getElementById('form-plan-id').value = plan.id;
            
            const minStr = parseFloat(plan.min_invest).toFixed(2);
            const maxStr = parseFloat(plan.max_invest).toFixed(2);
            document.getElementById('invest-amount-label').textContent = `Enter Investment Amount (Min: $${minStr} - Max: $${maxStr})`;
            
            const input = document.getElementById('invest-amount-input');
            input.value = plan.min_invest;
            input.min = plan.min_invest;
            input.max = plan.max_invest;

            activeRate = parseFloat(plan.hourly_rate);
            activeMin = parseFloat(plan.min_invest);
            activeMax = parseFloat(plan.max_invest);

            // Update header title
            document.getElementById('view-title').textContent = 'Confirm Plan';

            calcProfit();
        }

        function showPlans() {
            document.getElementById('plan-selection').style.display = 'block';
            document.getElementById('invest-confirm').style.display = 'none';
            document.getElementById('view-title').textContent = 'AI Computing Plans';
        }

        function handleInvestBack() {
            if (document.getElementById('invest-confirm').style.display === 'block') {
                showPlans();
            } else {
                history.back();
            }
        }

        function calcProfit() {
            const input = document.getElementById('invest-amount-input');
            const amount = parseFloat(input.value) || 0;
            const hourly = (amount * (activeRate / 100));
            const daily = hourly * 24;

            document.getElementById('p-hourly').textContent = '$' + hourly.toFixed(4);
            document.getElementById('p-daily').textContent = '$' + daily.toFixed(4);
        }

        document.getElementById('invest-amount-input').addEventListener('input', calcProfit);

        window.onInvestSuccess = (data) => {
            navigateTo('dashboard');
        };
    </script>
</div>