<?php
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch active investment stats
$stmt = $pdo->prepare("
    SELECT SUM(invested_amount) as total_invested, SUM(hourly_rate) as total_hourly_rate 
    FROM user_investments ui
    JOIN plans p ON ui.plan_id = p.id
    WHERE ui.user_id = ? AND ui.status = 'active'
");
$stmt->execute([$user_id]);
$invest_stats = $stmt->fetch();

$total_invested = $invest_stats['total_invested'] ?? 0;
$total_hourly_rate = $invest_stats['total_hourly_rate'] ?? 0;

// Fetch total deposited
$stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'deposit' AND status = 'approved'");
$stmt->execute([$user_id]);
$total_deposit = $stmt->fetchColumn() ?: 0;

// Fetch total earnings (profit + referral)
$stmt = $pdo->prepare("SELECT SUM(total_profit) FROM user_investments WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_investment_profit = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'referral_commission' AND status = 'approved'");
$stmt->execute([$user_id]);
$total_referral_profit = $stmt->fetchColumn() ?: 0;

// Calculate "Pending" Live Accrual (profit since last calc for smooth dashboard counter)
$stmt = $pdo->prepare("
    SELECT ui.invested_amount, p.hourly_rate, 
           (TIMESTAMPDIFF(SECOND, IFNULL(ui.last_profit_calc, ui.created_at), NOW())) as seconds_elapsed
    FROM user_investments ui
    JOIN plans p ON ui.plan_id = p.id
    WHERE ui.user_id = ? AND ui.status = 'active'
");
$stmt->execute([$user_id]);
$active_invs = $stmt->fetchAll();

$pending_accrual = 0;
foreach ($active_invs as $inv) {
    $hourly_profit = (float) $inv['invested_amount'] * ((float) $inv['hourly_rate'] / 100);
    $profit_per_sec = $hourly_profit / 3600;
    $pending_accrual += $profit_per_sec * (int) $inv['seconds_elapsed'];
}

$accurate_balance = (float) $user['earnings_balance'] + $pending_accrual;
$total_earnings = $total_investment_profit + $total_referral_profit + $pending_accrual;

// Fetch all plans for calculator
$stmt = $pdo->query("SELECT * FROM plans WHERE is_active = 1");
$all_plans = $stmt->fetchAll();
?>

<div class="dashboard-container" style="padding-bottom: 100px;">

    <!-- Premium Live Earning Card -->
    <div class="card"
        style="margin-top: 10px; padding: 25px; text-align: center; border: 1px solid rgba(59, 130, 246, 0.2); background: radial-gradient(circle at top right, rgba(59, 130, 246, 0.1), transparent), var(--bg-card); box-shadow: var(--neon-blue);">
        <div
            style="display: flex; justify-content: center; align-items: center; gap: 10px; color: var(--primary); margin-bottom: 15px;">
            <i class="fa-solid fa-microchip"
                style="font-size: 1.2rem; filter: drop-shadow(0 0 5px var(--primary));"></i>
            <span style="font-weight: 700; font-size: 0.9rem; letter-spacing: 1px; text-transform: uppercase;">Live
                Earnings</span>
        </div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 5px;">Current Balance</div>
        <h1 class="live-counter" data-balance="<?php echo $accurate_balance; ?>"
            data-hourly-rate="<?php echo $total_hourly_rate; ?>" data-invested="<?php echo $total_invested; ?>"
            style="font-size: 2.8rem; font-weight: 800; background: linear-gradient(to bottom, #fff, #94a3b8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; filter: drop-shadow(0 0 10px rgba(255,255,255,0.2)); letter-spacing: -1px; margin-bottom: 20px;">
            $<?php echo number_format($accurate_balance, 8); ?>
        </h1>

        <!-- Member Tier Badge -->
        <?php
        $tier_stmt = $pdo->prepare("SELECT * FROM tiers WHERE name = ?");
        $tier_stmt->execute([$user['tier'] ?? 'Bronze']);
        $tier_data = $tier_stmt->fetch() ?: ['color' => '#3b82f6', 'icon' => 'fa-solid fa-crown'];
        ?>
        <div style="margin-bottom: 20px;">
            <span
                style="background: rgba(255,255,255,0.05); border: 1px solid <?php echo $tier_data['color']; ?>; color: <?php echo $tier_data['color']; ?>; padding: 5px 18px; border-radius: 50px; font-size: 0.7rem; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; box-shadow: 0 0 15px <?php echo $tier_data['color']; ?>44;">
                <i class="<?php echo $tier_data['icon']; ?> me-1"></i> <?php echo $user['tier'] ?? 'Bronze'; ?> Status
            </span>
        </div>

        <div
            style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--glass-border); border-radius: 50px; padding: 10px 25px; display: inline-flex; align-items: center; gap: 15px; font-size: 0.85rem;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-bolt" style="color: var(--primary); font-size: 0.7rem;"></i>
                <span style="color: var(--text-muted);">Next cycle:</span>
                <span id="next-cycle" style="font-weight: 700; font-family: monospace; font-size: 1rem;">00 : 00 :
                    00</span>
            </div>
        </div>
    </div>

    <!-- Start Earning Button (Conditional) -->
    <?php if ($total_invested <= 0): ?>
        <a href="invest" data-link class="btn"
            style="padding: 18px; margin-bottom: 25px; font-size: 1.1rem; background: linear-gradient(90deg, #3b82f6, #a855f7, #10b981); background-size: 200% auto; animation: gradientShift 3s linear infinite; box-shadow: 0 10px 40px rgba(139, 92, 246, 0.3);">
            <i class="fa-solid fa-play"></i> Start Earning Now <i class="fa-solid fa-wand-magic-sparkles"></i>
        </a>
    <?php endif; ?>

    <!-- Referral Link Card -->
    <div class="card"
        style="padding: 15px; background: rgba(139, 92, 246, 0.05); border: 1px solid rgba(139, 92, 246, 0.1);">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
            <div
                style="width: 35px; height: 35px; border-radius: 10px; background: rgba(139, 92, 246, 0.1); display: flex; align-items: center; justify-content: center; color: var(--secondary);">
                <i class="fa-solid fa-users"></i>
            </div>
            <div style="flex: 1;">
                <div style="font-size: 0.85rem; font-weight: 700;">Your Referral Link</div>
                <div
                    style="font-size: 0.7rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px;">
                    https://gpt33.online/register?ref=<?php echo htmlspecialchars($user['referral_code']); ?></div>
            </div>
            <div style="display: flex; gap: 8px;">
                <button class="icon-btn" onclick="copyReferralLink('https://gpt33.online/register?ref=<?php echo htmlspecialchars($user['referral_code']); ?>')"
                    style="background: rgba(59, 130, 246, 0.1); color: var(--primary); border: none; width: 35px; height: 35px; border-radius: 8px; cursor: pointer;"><i
                        class="fa-regular fa-copy"></i></button>
                <button class="icon-btn"
                    style="background: rgba(16, 185, 129, 0.1); color: var(--accent); border: none; width: 35px; height: 35px; border-radius: 8px; cursor: pointer;"><i
                        class="fa-solid fa-share-nodes"></i></button>
            </div>
        </div>
    </div>

    <!-- Total Portfolio Value -->
    <div class="card"
        style="background: linear-gradient(135deg, var(--primary) 0%, #10b981 100%); border: none; padding: 25px; margin-bottom: 25px; position: relative; overflow: hidden; box-shadow: 0 15px 30px var(--primary-glow);">
        <div
            style="position: absolute; top: -20px; right: -20px; font-size: 8rem; color: rgba(255,255,255,0.05); transform: rotate(-15deg);">
            <i class="fa-solid fa-vault"></i>
        </div>
        <div style="position: relative; z-index: 1;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                <div>
                    <div
                        style="font-size: 0.75rem; color: rgba(255,255,255,0.8); text-transform: uppercase; font-weight: 700; letter-spacing: 1px; margin-bottom: 5px;">
                        Total Portfolio Value</div>
                    <div style="font-size: 2.2rem; font-weight: 900; color: white; letter-spacing: -1px;">
                        $<?php echo number_format($user['deposit_balance'] + $accurate_balance, 2); ?></div>
                </div>
                <div
                    style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); padding: 8px 15px; border-radius: 10px; color: white; font-size: 0.7rem; font-weight: 800;">
                    <i class="fa-solid fa-shield-halved"></i> SECURED
                </div>
            </div>
            <div style="display: flex; gap: 20px;">
                <div>
                    <div style="font-size: 0.6rem; color: rgba(255,255,255,0.7); text-transform: uppercase;">Capital
                        Inflow</div>
                    <div style="font-size: 0.9rem; font-weight: 700; color: white;">
                        $<?php echo number_format($user['deposit_balance'], 2); ?></div>
                </div>
                <div style="width: 1px; background: rgba(255,255,255,0.2);"></div>
                <div>
                    <div style="font-size: 0.6rem; color: rgba(255,255,255,0.7); text-transform: uppercase;">Yield
                        Accrual</div>
                    <div style="font-size: 0.9rem; font-weight: 700; color: #34d399;">
                        +$<?php echo number_format($accurate_balance, 2); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Payout Marquee & Feed Button -->
    <div style="margin-bottom: 25px;">
        <div
            style="background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.1); border-radius: 12px; padding: 10px 15px; display: flex; align-items: center; gap: 15px; overflow: hidden; margin-bottom: 10px;">
            <div
                style="white-space: nowrap; font-size: 0.65rem; font-weight: 800; color: var(--accent); text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-circle-check fa-beat"></i> LIVE PAYOUTS
            </div>
            <marquee behavior="scroll" direction="left" scrollamount="4"
                style="font-size: 0.75rem; color: var(--text-muted);">
                <?php
                $stmt = $pdo->query("SELECT u.full_name, t.amount, t.created_at FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.type = 'withdrawal' AND t.status = 'completed' ORDER BY t.created_at DESC LIMIT 10");
                $payouts = $stmt->fetchAll();
                if (empty($payouts)) {
                    echo "System liquidity stable. Processing global distributions... &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                } else {
                    foreach ($payouts as $p) {
                        $name = substr($p['full_name'], 0, 3) . '***';
                        echo "Successfully disbursed <span style='color: var(--accent); font-weight: 700;'>$" . number_format($p['amount'], 2) . "</span> to member {$name} &nbsp;&nbsp; • &nbsp;&nbsp; ";
                    }
                }
                ?>
            </marquee>
        </div>
        <a href="feed" data-link class="card"
            style="display: flex; align-items: center; justify-content: center; gap: 10px; padding: 15px; margin: 0; background: linear-gradient(90deg, rgba(16, 185, 129, 0.1), rgba(59, 130, 246, 0.1)); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 12px; text-decoration: none; color: white; transition: var(--transition); box-shadow: 0 4px 15px rgba(16, 185, 129, 0.1);">
            <i class="fa-solid fa-bullhorn" style="color: var(--accent); animation: pulse 2s infinite;"></i>
            <span style="font-size: 0.8rem; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;">View Official Broadcast Feed 📣</span>
            <i class="fa-solid fa-arrow-trend-up" style="color: var(--accent); font-size: 0.8rem;"></i>
        </a>
    </div>
    <!-- Stats Bar -->
    <div class="card"
        style="padding: 12px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.2); border: 1px solid var(--glass-border); border-radius: 12px;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex: 1; padding-right: 15px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-wallet" style="color: var(--primary); font-size: 0.8rem;"></i>
                <span
                    style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Deposit</span>
            </div>
            <span style="font-weight: 800; font-size: 0.95rem;">$<?php echo number_format($total_deposit, 2); ?></span>
        </div>
        <div style="width: 1px; height: 15px; background: var(--glass-border);"></div>
        <div style="display: flex; align-items: center; justify-content: space-between; flex: 1; padding-left: 15px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-chart-line" style="color: var(--accent); font-size: 0.8rem;"></i>
                <span
                    style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Earnings</span>
            </div>
            <span style="font-weight: 800; font-size: 0.95rem;">$<?php echo number_format($total_earnings, 2); ?></span>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 25px;">
        <a href="deposit" class="card" data-link
            style="text-align: center; text-decoration: none; padding: 20px 10px; margin-bottom: 0; background: linear-gradient(to bottom, #059669, #10b981); border: none; box-shadow: 0 10px 20px rgba(5, 150, 105, 0.2);">
            <div
                style="width: 45px; height: 45px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto; font-size: 1.4rem;">
                <i class="fa-solid fa-credit-card"></i>
            </div>
            <div style="font-weight: 700; font-size: 0.95rem;">Deposit</div>
            <div style="font-size: 0.65rem; opacity: 0.8;">Add funds</div>
        </a>
        <a href="invest" class="card" data-link
            style="text-align: center; text-decoration: none; padding: 20px 10px; margin-bottom: 0; background: linear-gradient(to bottom, #0ea5e9, #2563eb); border: none; box-shadow: 0 10px 20px rgba(14, 165, 233, 0.2);">
            <div
                style="width: 45px; height: 45px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto; font-size: 1.4rem;">
                <i class="fa-solid fa-microchip"></i>
            </div>
            <div style="font-weight: 700; font-size: 0.95rem;">Invest</div>
            <div style="font-size: 0.65rem; opacity: 0.8;">AI Plans</div>
        </a>
        <a href="withdraw" class="card" data-link
            style="text-align: center; text-decoration: none; padding: 20px 10px; margin-bottom: 0; background: linear-gradient(to bottom, #f59e0b, #d97706); border: none; box-shadow: 0 10px 20px rgba(245, 158, 11, 0.2);">
            <div
                style="width: 45px; height: 45px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto; font-size: 1.4rem;">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div style="font-weight: 700; font-size: 0.95rem;">Withdraw</div>
            <div style="font-size: 0.65rem; opacity: 0.8;">Cash out</div>
        </a>
    </div>


    <!-- PWA Install Button -->
    <div class="pwa-install-btn card" onclick="installApp()"
        style="display: none; align-items: center; gap: 15px; padding: 15px 20px; background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(139, 92, 246, 0.2) 100%); border: 1px solid var(--primary); margin-bottom: 25px; cursor: pointer;">
        <div
            style="width: 40px; height: 40px; border-radius: 10px; background: var(--primary); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; box-shadow: 0 0 15px var(--primary);">
            <i class="fa-solid fa-download"></i>
        </div>
        <div style="flex: 1;">
            <div style="font-weight: 700; font-size: 0.95rem;">Install Official App</div>
            <div style="font-size: 0.7rem; color: var(--text-muted);">Faster access & native experience</div>
        </div>
        <i class="fa-solid fa-chevron-right" style="color: var(--primary); font-size: 0.8rem;"></i>
    </div>

    <!-- Yield Calculator Card -->
    <div class="section-title">
        <i class="fa-solid fa-calculator"></i> Yield Forecast
    </div>
    <div class="card"
        style="padding: 25px; border: 1px solid rgba(59, 130, 246, 0.2); background: radial-gradient(circle at top right, rgba(59, 130, 246, 0.05), transparent), var(--bg-card); margin-bottom: 25px;">
        <div style="display: grid; gap: 20px;">
            <div class="form-group">
                <label
                    style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 8px; display: block;">Select
                    Architecture</label>
                <select id="inline-calc-plan" class="form-control"
                    style="background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); padding: 12px; color: white;">
                    <?php foreach ($all_plans as $p): ?>
                        <option value="<?php echo $p['hourly_rate']; ?>" data-price="<?php echo $p['fixed_price']; ?>">
                            <?php echo htmlspecialchars($p['name']); ?> (<?php echo $p['hourly_rate']; ?>%/hr)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div
                    style="background: rgba(16, 185, 129, 0.05); padding: 15px; border-radius: 15px; border: 1px solid rgba(16, 185, 129, 0.1); text-align: center;">
                    <div
                        style="font-size: 0.6rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px;">
                        Daily Yield</div>
                    <div id="inline-calc-daily" style="font-weight: 800; color: var(--accent); font-size: 1.1rem;">$0.00
                    </div>
                </div>
                <div
                    style="background: rgba(16, 185, 129, 0.05); padding: 15px; border-radius: 15px; border: 1px solid rgba(16, 185, 129, 0.1); text-align: center;">
                    <div
                        style="font-size: 0.6rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px;">
                        Monthly Yield</div>
                    <div id="inline-calc-monthly" style="font-weight: 800; color: var(--accent); font-size: 1.1rem;">
                        $0.00</div>
                </div>
            </div>

            <div
                style="background: rgba(59, 130, 246, 0.05); padding: 15px; border-radius: 15px; border: 1px solid rgba(59, 130, 246, 0.1); text-align: center;">
                <div
                    style="font-size: 0.6rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px;">
                    Total Weekly Accumulation</div>
                <div id="inline-calc-weekly" style="font-weight: 800; color: var(--primary); font-size: 1.2rem;">$0.00
                </div>
            </div>
        </div>
    </div>

    <div class="section-title">
        <i class="fa-solid fa-microchip"></i> Investments
    </div>
    <div class="card" style="padding: 0; overflow: hidden;">
        <a href="invest" data-link
            style="display: flex; align-items: center; gap: 15px; padding: 15px 20px; text-decoration: none; color: var(--text-main); border-bottom: 1px solid var(--glass-border);">
            <div
                style="width: 32px; height: 32px; border-radius: 8px; background: rgba(59, 130, 246, 0.05); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 0.9rem;">
                <i class="fa-solid fa-microchip"></i>
            </div>
            <span style="font-size: 0.9rem; font-weight: 600; flex: 1;">Investment Plans</span>
            <i class="fa-solid fa-chevron-right" style="color: var(--text-muted); font-size: 0.7rem;"></i>
        </a>
        <a href="investments" data-link
            style="display: flex; align-items: center; gap: 15px; padding: 15px 20px; text-decoration: none; color: var(--text-main);">
            <div
                style="width: 32px; height: 32px; border-radius: 8px; background: rgba(139, 92, 246, 0.05); display: flex; align-items: center; justify-content: center; color: var(--secondary); font-size: 0.9rem;">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <span style="font-size: 0.9rem; font-weight: 600; flex: 1;">My Investments</span>
            <i class="fa-solid fa-chevron-right" style="color: var(--text-muted); font-size: 0.7rem;"></i>
        </a>
    </div>

    <div class="section-title">
        <i class="fa-solid fa-users"></i> Referrals & Rewards
    </div>
    <div class="card" style="padding: 0; overflow: hidden;">
        <a href="referrals" data-link
            style="display: flex; align-items: center; gap: 15px; padding: 15px 20px; text-decoration: none; color: var(--text-main); border-bottom: 1px solid var(--glass-border);">
            <div
                style="width: 32px; height: 32px; border-radius: 8px; background: rgba(59, 130, 246, 0.05); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 0.9rem;">
                <i class="fa-solid fa-users"></i>
            </div>
            <span style="font-size: 0.9rem; font-weight: 600; flex: 1;">My Referrals</span>
            <i class="fa-solid fa-chevron-right" style="color: var(--text-muted); font-size: 0.7rem;"></i>
        </a>
        <a href="transactions" data-link
            style="display: flex; align-items: center; gap: 15px; padding: 15px 20px; text-decoration: none; color: var(--text-main);">
            <div
                style="width: 32px; height: 32px; border-radius: 8px; background: rgba(16, 185, 129, 0.05); display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 0.9rem;">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <span style="font-size: 0.9rem; font-weight: 600; flex: 1;">Team Commissions</span>
            <i class="fa-solid fa-chevron-right" style="color: var(--text-muted); font-size: 0.7rem;"></i>
        </a>
    </div>

    <div class="section-title">
        <i class="fa-solid fa-circle-user"></i> Account
    </div>
    <div class="card" style="padding: 0; overflow: hidden;">
        <a href="profile" data-link
            style="display: flex; align-items: center; gap: 15px; padding: 15px 20px; text-decoration: none; color: var(--text-main); border-bottom: 1px solid var(--glass-border);">
            <div
                style="width: 32px; height: 32px; border-radius: 8px; background: rgba(59, 130, 246, 0.05); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 0.9rem;">
                <i class="fa-solid fa-circle-info"></i>
            </div>
            <span style="font-size: 0.9rem; font-weight: 600; flex: 1;">My Profile</span>
            <i class="fa-solid fa-chevron-right" style="color: var(--text-muted); font-size: 0.7rem;"></i>
        </a>
        <a href="support" data-link
            style="display: flex; align-items: center; gap: 15px; padding: 15px 20px; text-decoration: none; color: var(--text-main);">
            <div
                style="width: 32px; height: 32px; border-radius: 8px; background: rgba(16, 185, 129, 0.05); display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 0.9rem;">
                <i class="fa-solid fa-headset"></i>
            </div>
            <span style="font-size: 0.9rem; font-weight: 600; flex: 1;">Help & Support</span>
            <i class="fa-solid fa-chevron-right" style="color: var(--text-muted); font-size: 0.7rem;"></i>
        </a>
    </div>

</div>

<!-- Calculator Modal -->
<div id="calc-modal" class="modal-overlay"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(10px); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
    <div class="card"
        style="width: 100%; max-width: 400px; padding: 30px; border: 1px solid var(--glass-border); background: var(--bg-card); position: relative; animation: slideUp 0.3s ease-out;">
        <div onclick="closeCalculator()"
            style="position: absolute; top: 20px; right: 20px; color: var(--text-muted); cursor: pointer; font-size: 1.2rem;">
            <i class="fa-solid fa-xmark"></i>
        </div>

        <div style="text-align: center; margin-bottom: 25px;">
            <div
                style="width: 50px; height: 50px; border-radius: 12px; background: rgba(59, 130, 246, 0.1); display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto; color: var(--primary); font-size: 1.4rem;">
                <i class="fa-solid fa-calculator"></i>
            </div>
            <h2 style="margin: 0; font-size: 1.4rem; font-weight: 800;">Yield Calculator</h2>
            <p style="font-size: 0.8rem; color: var(--text-muted);">Forecast your AI computing returns</p>
        </div>

        <div style="display: grid; gap: 20px;">
            <div class="form-group">
                <label
                    style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 8px; display: block;">Select
                    Architecture</label>
                <select id="modal-calc-plan" class="form-control"
                    style="background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); padding: 12px;">
                    <?php foreach ($all_plans as $p): ?>
                        <option value="<?php echo $p['hourly_rate']; ?>" data-price="<?php echo $p['fixed_price']; ?>">
                            <?php echo htmlspecialchars($p['name']); ?> (<?php echo $p['hourly_rate']; ?>%/hr)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label
                    style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 8px; display: block;">Architecture
                    Entry Fee</label>
                <div
                    style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 12px; font-weight: 800; font-size: 1.2rem; color: var(--text-main); border: 1px solid var(--glass-border);">
                    $<span id="modal-calc-amount">0.00</span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div
                    style="background: rgba(16, 185, 129, 0.05); padding: 15px; border-radius: 15px; border: 1px solid rgba(16, 185, 129, 0.1); text-align: center;">
                    <div
                        style="font-size: 0.6rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px;">
                        Daily Yield</div>
                    <div id="modal-calc-daily" style="font-weight: 800; color: var(--accent); font-size: 1.1rem;">$0.00
                    </div>
                </div>
                <div
                    style="background: rgba(16, 185, 129, 0.05); padding: 15px; border-radius: 15px; border: 1px solid rgba(16, 185, 129, 0.1); text-align: center;">
                    <div
                        style="font-size: 0.6rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px;">
                        Monthly Yield</div>
                    <div id="modal-calc-monthly" style="font-weight: 800; color: var(--accent); font-size: 1.1rem;">
                        $0.00</div>
                </div>
            </div>

            <div
                style="background: rgba(59, 130, 246, 0.05); padding: 15px; border-radius: 15px; border: 1px solid rgba(59, 130, 246, 0.1); text-align: center;">
                <div
                    style="font-size: 0.6rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px;">
                    Total Weekly Accumulation</div>
                <div id="modal-calc-weekly" style="font-weight: 800; color: var(--primary); font-size: 1.2rem;">$0.00
                </div>
            </div>
        </div>

        <button onclick="closeCalculator()" class="btn"
            style="margin-top: 25px; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); color: var(--text-main);">
            Close Interface
        </button>
    </div>
</div>

<script>

    function copyReferralLink(link) {
        if (!link) {
            link = 'https://gpt33.online/register?ref=<?php echo htmlspecialchars($user['referral_code'] ?? ''); ?>';
        }
        navigator.clipboard.writeText(link).then(() => {
            showToast('Referral link copied!');
        }).catch(err => {
            const temp = document.createElement('textarea');
            temp.value = link;
            document.body.appendChild(temp);
            temp.select();
            document.execCommand('copy');
            document.body.removeChild(temp);
            showToast('Referral link copied!');
        });
    }

    // Countdown for next cycle
    (function () {
        let interval = setInterval(() => {
            const el = document.getElementById('next-cycle');
            if (!el) {
                clearInterval(interval);
                return;
            }
            const now = new Date();
            const minutes = 59 - now.getMinutes();
            const seconds = 59 - now.getSeconds();
            el.textContent = `00:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }, 1000);
    })();

    // Modal Calculator Logic
    function openCalculator() {
        document.getElementById('calc-modal').style.display = 'flex';
        updateModalCalc();
    }

    function closeCalculator() {
        document.getElementById('calc-modal').style.display = 'none';
    }

    function updateModalCalc() {
        const select = document.getElementById('modal-calc-plan');
        if (!select) return;
        const selected = select.options[select.selectedIndex];
        const rate = parseFloat(select.value);
        const amount = parseFloat(selected.getAttribute('data-price')) || 0;

        document.getElementById('modal-calc-amount').textContent = amount.toLocaleString();

        const hourly = amount * (rate / 100);
        const daily = hourly * 24;
        const weekly = daily * 7;
        const monthly = daily * 30;

        document.getElementById('modal-calc-daily').textContent = '$' + daily.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('modal-calc-weekly').textContent = '$' + weekly.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('modal-calc-monthly').textContent = '$' + monthly.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    const modalPlanSelect = document.getElementById('modal-calc-plan');
    if (modalPlanSelect) {
        modalPlanSelect.addEventListener('change', updateModalCalc);
    }

    // Inline Dashboard Calculator Logic
    function updateInlineCalc() {
        const select = document.getElementById('inline-calc-plan');
        if (!select) return;
        const selected = select.options[select.selectedIndex];
        const rate = parseFloat(select.value);
        const amount = parseFloat(selected.getAttribute('data-price')) || 0;

        const hourly = amount * (rate / 100);
        const daily = hourly * 24;
        const weekly = daily * 7;
        const monthly = daily * 30;

        document.getElementById('inline-calc-daily').textContent = '$' + daily.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('inline-calc-weekly').textContent = '$' + weekly.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('inline-calc-monthly').textContent = '$' + monthly.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    const inlinePlanSelect = document.getElementById('inline-calc-plan');
    if (inlinePlanSelect) {
        inlinePlanSelect.addEventListener('change', updateInlineCalc);
        updateInlineCalc(); // Initial calc
    }

    // Header Integration Logic (Dashboard specific)
    (function initDashboardHeader() {
        const actions = document.querySelector('.header-actions');
        if (!actions) return;

        if (document.getElementById('header-calc-btn')) return;

        const btn = document.createElement('button');
        btn.id = 'header-calc-btn';
        btn.innerHTML = '<i class="fa-solid fa-calculator"></i>';
        btn.style.cssText = `
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent);
            border: 1px solid rgba(16, 185, 129, 0.2);
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.3s;
            margin-right: 5px;
        `;
        btn.onclick = openCalculator;

        actions.insertBefore(btn, actions.firstChild);

        // Cleanup when view is destroyed (for SPA)
        window.addEventListener('popstate', () => {
            const b = document.getElementById('header-calc-btn');
            if (b) b.remove();
        }, { once: true });
    })();
</script>