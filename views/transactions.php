<?php
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='/login';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch all transaction types
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$all_transactions = $stmt->fetchAll();

$deposits = array_filter($all_transactions, fn($t) => $t['type'] === 'deposit');
$withdrawals = array_filter($all_transactions, fn($t) => $t['type'] === 'withdrawal');
$referrals = array_filter($all_transactions, fn($t) => $t['type'] === 'referral_commission');
$earnings = array_filter($all_transactions, fn($t) => $t['type'] === 'hourly_profit');
?>

<div class="full-screen-view" style="min-height: 100vh; background: var(--bs-body-bg); display: flex; flex-direction: column;">
    <header class="view-header">
        <a href="javascript:history.back()"><i class="fa-solid fa-arrow-left"></i></a>
        <h2>Intelligence Logs</h2>
    </header>

    <div class="transactions-page" style="padding: 20px; padding-bottom: 100px; flex: 1;">

    <!-- Tab Bar -->
    <div class="card"
        style="padding: 5px; display: flex; gap: 5px; background: rgba(0,0,0,0.2); border-radius: 15px; margin-bottom: 25px;">
        <button class="tab-btn active" onclick="switchTab('deposits')"
            style="flex: 1; padding: 10px; border: none; border-radius: 12px; background: none; color: var(--text-muted); cursor: pointer; transition: var(--transition); display: flex; flex-direction: column; align-items: center; gap: 5px;">
            <i class="fa-solid fa-credit-card"></i>
            <span style="font-size: 0.65rem; font-weight: 700;">Deposits</span>
        </button>
        <button class="tab-btn" onclick="switchTab('withdrawals')"
            style="flex: 1; padding: 10px; border: none; border-radius: 12px; background: none; color: var(--text-muted); cursor: pointer; transition: var(--transition); display: flex; flex-direction: column; align-items: center; gap: 5px;">
            <i class="fa-solid fa-wallet"></i>
            <span style="font-size: 0.65rem; font-weight: 700;">Withdrawals</span>
        </button>
        <button class="tab-btn" onclick="switchTab('referrals')"
            style="flex: 1; padding: 10px; border: none; border-radius: 12px; background: none; color: var(--text-muted); cursor: pointer; transition: var(--transition); display: flex; flex-direction: column; align-items: center; gap: 5px;">
            <i class="fa-solid fa-users"></i>
            <span style="font-size: 0.65rem; font-weight: 700;">Referrals</span>
        </button>
        <button class="tab-btn" onclick="switchTab('earnings')"
            style="flex: 1; padding: 10px; border: none; border-radius: 12px; background: none; color: var(--text-muted); cursor: pointer; transition: var(--transition); display: flex; flex-direction: column; align-items: center; gap: 5px;">
            <i class="fa-solid fa-chart-line"></i>
            <span style="font-size: 0.65rem; font-weight: 700;">Earnings</span>
        </button>
    </div>

    <!-- Tab Content -->
    <div id="deposits-pane" class="tab-pane active">
        <?php renderTransactionList($deposits, 'No deposits found', 'Your deposits will appear here once available.'); ?>
    </div>
    <div id="withdrawals-pane" class="tab-pane" style="display: none;">
        <?php renderTransactionList($withdrawals, 'No withdrawals found', 'Your withdrawal requests will appear here.'); ?>
    </div>
    <div id="referrals-pane" class="tab-pane" style="display: none;">
        <?php renderTransactionList($referrals, 'No referral commissions', 'Commissions from your team will appear here.'); ?>
    </div>
    <div id="earnings-pane" class="tab-pane" style="display: none;">
        <?php renderTransactionList($earnings, 'No hourly earnings', 'Your AI mining profits will appear here every hour.'); ?>
    </div>

</div>

<?php
function renderTransactionList($list, $emptyTitle, $emptyDesc)
{
    if (empty($list)): ?>
        <div style="text-align: center; padding: 60px 20px;">
            <div
                style="width: 70px; height: 70px; border-radius: 50%; background: rgba(255,255,255,0.03); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; color: var(--text-muted); font-size: 2rem; border: 1px dashed var(--glass-border);">
                <i class="fa-solid fa-circle-exclamation"></i>
            </div>
            <h3 style="font-size: 1.1rem; margin-bottom: 8px;"><?php echo $emptyTitle; ?></h3>
            <p style="color: var(--text-muted); font-size: 0.85rem;"><?php echo $emptyDesc; ?></p>
        </div>
    <?php else: ?>
        <?php foreach ($list as $t):
            $color = ($t['type'] === 'withdrawal' || $t['type'] === 'investment') ? '#ef4444' : 'var(--accent)';
            $icon = $t['type'] === 'deposit' ? 'fa-vault' : ($t['type'] === 'withdrawal' ? 'fa-money-bill-transfer' : 'fa-coins');
            $type_map = [
                'deposit' => 'Capital Inflow',
                'withdrawal' => 'Capital Outflow',
                'investment' => 'Cluster Stake',
                'payout' => 'Yield Accrual',
                'hourly_profit' => 'Yield Accrual',
                'manual_adjustment' => 'Balance Corrective',
                'referral_commission' => 'Network Bonus',
                'interest' => 'Staking Reward'
            ];
            $display_type = $type_map[$t['type']] ?? str_replace('_', ' ', $t['type']);
            ?>
            <div class="card"
                style="padding: 15px; margin-bottom: 12px; display: flex; flex-direction: row; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                <div style="display: flex; gap: 15px; align-items: center;">
                    <div
                        style="width: 40px; height: 40px; border-radius: 10px; background: var(--glass); display: flex; align-items: center; justify-content: center; font-size: 1rem; color: <?php echo $color; ?>;">
                        <i class="fa-solid <?php echo $icon; ?>"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600; font-size: 0.9rem;"><?php echo $display_type; ?></div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">
                            <?php echo date('M j, Y • H:i', strtotime($t['created_at'])); ?>
                        </div>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: 800; font-size: 1rem; color: <?php echo $color; ?>;">
                        <?php echo ($t['type'] === 'withdrawal' || $t['type'] === 'investment') ? '-' : '+'; ?>$<?php echo number_format($t['amount'], 2); ?>
                    </div>
                    <div
                        style="font-size: 0.65rem; text-transform: uppercase; font-weight: 700; color: <?php echo ($t['status'] === 'approved' || $t['status'] === 'completed' ? 'var(--accent)' : ($t['status'] === 'pending' ? '#f59e0b' : '#ef4444')); ?>;">
                        <?php echo $t['status']; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif;
}
?>

<style>
    .tab-btn.active {
        background: var(--primary) !important;
        color: white !important;
        box-shadow: var(--neon-blue);
    }
</style>

<script>
    function switchTab(tab) {
        document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));

        document.getElementById(tab + '-pane').style.display = 'block';
        event.currentTarget.classList.add('active');
    }
</script>
</div>
