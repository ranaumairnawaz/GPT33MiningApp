<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

function respond($status, $message = '')
{
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo json_encode(['status' => $status, 'message' => $message]);
        exit;
    } else {
        if ($status === 'success') {
            $_SESSION['admin_success'] = $message ?: 'Operation completed successfully';
        } else {
            $_SESSION['admin_error'] = $message ?: 'Operation failed';
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/admin/index.php'));
        exit;
    }
}

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    } else {
        header('Location: /login');
    }
    exit;
}

function logAdminAction($pdo, $action, $details = '')
{
    $admin_id = $_SESSION['user_id'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$admin_id, $action, $details, $ip]);
}

$action = $_GET['action'] ?? '';

if ($action === 'toggle_ban') {
    $user_id = $_GET['user_id'] ?? 0;
    $status = $_GET['status'] ?? 0;
    $stmt = $pdo->prepare("UPDATE users SET is_banned = ? WHERE id = ?");
    if ($stmt->execute([$status, $user_id])) {
        logAdminAction($pdo, 'toggle_ban', "User ID: $user_id, Status: " . ($status ? 'Banned' : 'Active'));
        respond('success', 'User status updated');
    }
} elseif ($action === 'authorize_admin') {
    $email = $_POST['email'] ?? '';
    $stmt = $pdo->prepare("UPDATE users SET is_admin = 1 WHERE email = ?");
    if ($stmt->execute([$email])) {
        if ($stmt->rowCount() > 0) {
            logAdminAction($pdo, 'authorize_admin', "Email: $email");
            respond('success', 'Administrative privileges granted');
        } else {
            respond('error', 'User node not detected in network');
        }
    }
} elseif ($action === 'revoke_admin') {
    $user_id = $_GET['user_id'] ?? 0;
    if ($user_id == $_SESSION['user_id'])
        respond('error', 'Self-termination not permitted');
    $stmt = $pdo->prepare("UPDATE users SET is_admin = 0 WHERE id = ?");
    if ($stmt->execute([$user_id])) {
        logAdminAction($pdo, 'revoke_admin', "User ID: $user_id");
        respond('success', 'Administrative privileges revoked');
    }
} elseif ($action === 'ban_ip') {
    $ip = $_GET['ip'] ?? '';
    if (empty($ip))
        respond('error', 'IP address is required');
    $stmt = $pdo->prepare("INSERT IGNORE INTO banned_ips (ip_address) VALUES (?)");
    if ($stmt->execute([$ip])) {
        respond('success', 'IP address quarantined');
    }
} elseif ($action === 'update_deposit_status') {
    $tx_id = $_GET['id'] ?? 0;
    $status = $_GET['status'] ?? '';

    if ($status === 'approved') {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT user_id, amount, status FROM transactions WHERE id = ?");
            $stmt->execute([$tx_id]);
            $tx = $stmt->fetch();

            if ($tx && $tx['status'] === 'pending') {
                // Fetch active deposit bonus setting
                $settings_stmt = $pdo->query("SELECT deposit_bonus FROM settings LIMIT 1");
                $settings = $settings_stmt->fetch();
                $deposit_bonus = (float)($settings['deposit_bonus'] ?? 0);

                $bonus_amount = 0;
                if ($deposit_bonus > 0) {
                    $bonus_amount = $tx['amount'] * ($deposit_bonus / 100);
                }

                $total_credit = $tx['amount'] + $bonus_amount;

                $stmt = $pdo->prepare("UPDATE users SET deposit_balance = deposit_balance + ? WHERE id = ?");
                $stmt->execute([$total_credit, $tx['user_id']]);

                $stmt = $pdo->prepare("UPDATE transactions SET status = 'approved' WHERE id = ?");
                $stmt->execute([$tx_id]);

                // Record a separate transparent transaction for the deposit bonus
                if ($bonus_amount > 0) {
                    $bonus_stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, status, method_name) VALUES (?, 'manual_adjustment', ?, 'completed', ?)");
                    $bonus_stmt->execute([
                        $tx['user_id'],
                        $bonus_amount,
                        "Deposit Bonus (" . number_format($deposit_bonus, 2) . "%) for deposit #" . $tx_id
                    ]);
                }

                logAdminAction($pdo, 'approve_deposit', "Transaction ID: $tx_id, Amount: {$tx['amount']}, Bonus: {$bonus_amount}");
                
                // Notify user
                $notif_msg = "Your deposit of $" . number_format($tx['amount'], 2) . " has been approved.";
                if ($bonus_amount > 0) {
                    $notif_msg .= " Plus a dynamic deposit bonus of $" . number_format($bonus_amount, 2) . " (" . number_format($deposit_bonus, 2) . "%) has been credited!";
                } else {
                    $notif_msg .= " and added to your balance.";
                }
                
                $notif_stmt = $pdo->prepare("INSERT INTO user_notifications (user_id, type, message, link) VALUES (?, 'deposit', ?, '/transactions')");
                $notif_stmt->execute([$tx['user_id'], $notif_msg]);

                $pdo->commit();
                respond('success', 'Funding approved and balance updated');
            } else {
                respond('error', 'Invalid or already processed transaction');
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            respond('error', $e->getMessage());
        }
    } else {
        $stmt = $pdo->prepare("UPDATE transactions SET status = 'rejected' WHERE id = ? AND status = 'pending'");
        if ($stmt->execute([$tx_id])) {
            // Get user_id for notification
            $tx_stmt = $pdo->prepare("SELECT user_id, amount FROM transactions WHERE id = ?");
            $tx_stmt->execute([$tx_id]);
            $tx = $tx_stmt->fetch();
            if ($tx) {
                $notif_msg = "Your deposit of $" . number_format($tx['amount'], 2) . " was rejected. Please contact support if you believe this is an error.";
                $notif_stmt = $pdo->prepare("INSERT INTO user_notifications (user_id, type, message, link) VALUES (?, 'deposit', ?, '/transactions')");
                $notif_stmt->execute([$tx['user_id'], $notif_msg]);
            }

            logAdminAction($pdo, 'reject_deposit', "Transaction ID: $tx_id");
            respond('success', 'Funding request rejected');
        }
    }
} elseif ($action === 'update_withdrawal_status') {
    $tx_id = $_GET['id'] ?? 0;
    $status = $_GET['status'] ?? '';

    if ($status === 'rejected') {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT user_id, amount, status FROM transactions WHERE id = ?");
            $stmt->execute([$tx_id]);
            $tx = $stmt->fetch();

            if ($tx && $tx['status'] === 'pending') {
                $stmt = $pdo->prepare("UPDATE users SET earnings_balance = earnings_balance + ? WHERE id = ?");
                $stmt->execute([$tx['amount'], $tx['user_id']]);

                $stmt = $pdo->prepare("UPDATE transactions SET status = 'rejected' WHERE id = ?");
                $stmt->execute([$tx_id]);

                logAdminAction($pdo, 'reject_withdrawal', "Transaction ID: $tx_id, Amount: {$tx['amount']}");
                $pdo->commit();
                respond('success', 'Disbursement rejected and funds returned');
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            respond('error', 'Internal database error');
        }
    } else {
        $stmt = $pdo->prepare("UPDATE transactions SET status = ? WHERE id = ? AND status = 'pending'");
        if ($stmt->execute([$status, $tx_id])) {
            // Get transaction details
            $tx_stmt = $pdo->prepare("
                SELECT t.user_id, t.amount, t.method_name, u.full_name 
                FROM transactions t 
                JOIN users u ON t.user_id = u.id 
                WHERE t.id = ?
            ");
            $tx_stmt->execute([$tx_id]);
            $tx = $tx_stmt->fetch();
            
            if ($tx) {
                $notif_msg = "Your withdrawal request for $" . number_format($tx['amount'], 2) . " is now " . $status . ".";
                $notif_stmt = $pdo->prepare("INSERT INTO user_notifications (user_id, type, message, link) VALUES (?, 'withdrawal', ?, '/transactions')");
                $notif_stmt->execute([$tx['user_id'], $notif_msg]);
                
                // If approved or completed, insert an attractive, high-converting telegram-like feed entry
                if ($status === 'approved' || $status === 'completed') {
                    $name = htmlspecialchars($tx['full_name']);
                    $parts = explode(' ', $name);
                    if (count($parts) > 1) {
                        $masked_name = $parts[0] . ' ' . substr($parts[1], 0, 1) . '***';
                    } else {
                        $masked_name = substr($name, 0, 3) . '***';
                    }
                    
                    $amt = number_format($tx['amount'], 2);
                    $method = htmlspecialchars($tx['method_name'] ?: 'Secure Gateway');
                    
                    $msg = "⚡ <b>CAPITAL DISBURSEMENT CONFIRMED!</b>\n\n";
                    $msg .= "Congratulations to our investor <b>{$masked_name}</b> for successfully cashing out <b>\${$amt}</b> via <b>{$method}</b>! 💸\n\n";
                    $msg .= "🔥 <i>Our AI High-Performance GPU Clusters are generating maximum yields! Don't let your computing nodes sit idle. Reinvest or buy more hash rate now to scale your hourly passive income!</i> 💻🚀";
                    
                    $feed_stmt = $pdo->prepare("INSERT INTO live_feed (type, user_name, amount, message) VALUES ('withdrawal', ?, ?, ?)");
                    $feed_stmt->execute([$masked_name, $tx['amount'], $msg]);
                }
            }

            logAdminAction($pdo, 'update_withdrawal_status', "Transaction ID: $tx_id, Status: $status");
            respond('success', 'Disbursement status updated');
        }
    }
} elseif ($action === 'save_settings') {
    $site_name = $_POST['site_name'] ?? 'GPT33';
    $currency_rate = $_POST['currency_rate'] ?? 1;
    $wa = $_POST['whatsapp_link'] ?? '';
    $tg = $_POST['telegram_link'] ?? '';
    $r1 = $_POST['ref_level_1'] ?? 0;
    $r2 = $_POST['ref_level_2'] ?? 0;
    $r3 = $_POST['ref_level_3'] ?? 0;
    $r4 = $_POST['ref_level_4'] ?? 0;
    $r5 = $_POST['ref_level_5'] ?? 0;
    $is_maintenance = $_POST['is_maintenance'] ?? 0;
    $ann_text = $_POST['announcement_text'] ?? '';
    $ann_active = $_POST['announcement_active'] ?? 0;
    $deposit_bonus = (float)($_POST['deposit_bonus'] ?? 0);
    $followers_base = (int)($_POST['broadcast_followers_base'] ?? 3450);

    $ann_img = null;
    if (isset($_FILES['announcement_image']) && $_FILES['announcement_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['announcement_image']['name'], PATHINFO_EXTENSION);
        $ann_img = 'assets/img/ann_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['announcement_image']['tmp_name'], __DIR__ . '/../' . $ann_img);
    }

    $sql = "UPDATE settings SET site_name=?, currency_rate=?, whatsapp_link=?, telegram_link=?, ref_level_1=?, ref_level_2=?, ref_level_3=?, ref_level_4=?, ref_level_5=?, is_maintenance=?, announcement_text=?, announcement_active=?, deposit_bonus=?, broadcast_followers_base=?";
    $params = [$site_name, $currency_rate, $wa, $tg, $r1, $r2, $r3, $r4, $r5, $is_maintenance, $ann_text, $ann_active, $deposit_bonus, $followers_base];

    if ($ann_img) {
        $sql .= ", announcement_image=?";
        $params[] = $ann_img;
    }

    $sql .= " WHERE id=1";

    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
        logAdminAction($pdo, 'save_settings', "System configuration updated");
        respond('success', 'System configuration deployed');
    }
} elseif ($action === 'adjust_balance') {
    $user_id = $_POST['user_id'] ?? 0;
    $type = $_POST['balance_type'] ?? '';
    $adj_type = $_POST['adj_type'] ?? 'add';
    $amount = abs($_POST['amount'] ?? 0);
    $note = $_POST['note'] ?? 'Manual adjustment';

    if ($adj_type === 'deduct') {
        $amount = -$amount;
    }

    $pdo->beginTransaction();
    try {
        $column = ($type === 'deposit') ? 'deposit_balance' : 'earnings_balance';
        $stmt = $pdo->prepare("UPDATE users SET $column = $column + ? WHERE id = ?");
        $stmt->execute([$amount, $user_id]);

        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, status, method_name) VALUES (?, 'manual_adjustment', ?, 'completed', ?)");
        $stmt->execute([$user_id, $amount, $note]);

        logAdminAction($pdo, 'adjust_balance', "User ID: $user_id, Type: $type, Amount: $amount, Note: $note");
        $pdo->commit();
        respond('success', 'Liquidity adjusted successfully');
    } catch (Exception $e) {
        $pdo->rollBack();
        respond('error', $e->getMessage());
    }
} elseif ($action === 'save_method') {
    $id = $_POST['id'] ?? '';
    $type = $_POST['type'] ?? '';
    $name = $_POST['name'] ?? '';
    $details = $_POST['details'] ?? '';
    $min = $_POST['min_amount'] ?? 0;
    $max = $_POST['max_amount'] ?? 0;
    $status = $_POST['is_active'] ?? 0;
    $currency = $_POST['currency'] ?? 'USD';
    $rate = $_POST['rate'] ?? 1.0;
    $copyable = $_POST['copyable_fields'] ?? '[]';
    $fixed_fee = $_POST['fixed_fee'] ?? 0;
    $percent_fee = $_POST['percent_fee'] ?? 0;

    $qr_path = null;
    if (isset($_FILES['qr_code']) && $_FILES['qr_code']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['qr_code']['name'], PATHINFO_EXTENSION);
        $qr_name = 'qr_' . time() . '.' . $ext;
        $qr_path = 'assets/img/qr/' . $qr_name;
        if (!is_dir(__DIR__ . '/../assets/img/qr')) {
            mkdir(__DIR__ . '/../assets/img/qr', 0777, true);
        }
        move_uploaded_file($_FILES['qr_code']['tmp_name'], __DIR__ . '/../' . $qr_path);
    }

    if ($type === 'deposit') {
        if ($id) {
            $sql = "UPDATE payment_methods SET name=?, details=?, min_amount=?, max_amount=?, is_active=?, copyable_fields=?, currency=?, rate=?, fixed_fee=?, percent_fee=?";
            $params = [$name, $details, $min, $max, $status, $copyable, $currency, $rate, $fixed_fee, $percent_fee];
            if ($qr_path) {
                $sql .= ", qr_code=?";
                $params[] = $qr_path;
            }
            $sql .= " WHERE id=?";
            $params[] = $id;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } else {
            $stmt = $pdo->prepare("INSERT INTO payment_methods (name, details, min_amount, max_amount, is_active, copyable_fields, currency, rate, qr_code, fixed_fee, percent_fee) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $details, $min, $max, $status, $copyable, $currency, $rate, $qr_path, $fixed_fee, $percent_fee]);
        }
    } else {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE withdrawal_methods SET name=?, instructions=?, min_amount=?, max_amount=?, is_active=?, currency=?, rate=?, fixed_fee=?, percent_fee=? WHERE id=?");
            $stmt->execute([$name, $details, $min, $max, $status, $currency, $rate, $fixed_fee, $percent_fee, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO withdrawal_methods (name, instructions, min_amount, max_amount, is_active, currency, rate, fixed_fee, percent_fee) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $details, $min, $max, $status, $currency, $rate, $fixed_fee, $percent_fee]);
        }
    }
    logAdminAction($pdo, 'save_method', "Type: $type, Name: $name");
    respond('success', 'Gateway configuration saved');
} elseif ($action === 'unban_ip') {
    $id = $_GET['id'] ?? 0;
    $stmt = $pdo->prepare("DELETE FROM banned_ips WHERE id = ?");
    $stmt->execute([$id]);
    respond('success', 'IP address released');
} elseif ($action === 'save_plan') {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $rate = $_POST['hourly_rate'] ?? 0;
    $min_invest = $_POST['min_invest'] ?? 0;
    $max_invest = $_POST['max_invest'] ?? 0;
    $status = $_POST['is_active'] ?? 1;

    if ($id) {
        $stmt = $pdo->prepare("UPDATE plans SET name=?, hourly_rate=?, min_invest=?, max_invest=?, fixed_price=?, is_active=? WHERE id=?");
        $stmt->execute([$name, $rate, $min_invest, $max_invest, $min_invest, $status, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO plans (name, hourly_rate, min_invest, max_invest, fixed_price, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $rate, $min_invest, $max_invest, $min_invest, $status]);
    }
    logAdminAction($pdo, 'save_plan', "Plan: $name, Min: $min_invest, Max: $max_invest");
    respond('success', 'Intelligence plan updated');
} elseif ($action === 'update_tier') {
    $user_id = $_POST['user_id'] ?? 0;
    $tier = $_POST['tier'] ?? 'Bronze';

    $stmt = $pdo->prepare("UPDATE users SET tier = ? WHERE id = ?");
    $stmt->execute([$tier, $user_id]);

    logAdminAction($pdo, 'update_tier', "User ID: $user_id, Tier: $tier");
    respond('success', 'Member status updated');
}


// Keep these for charts and trees
elseif ($action === 'get_analytics') {
    $period = $_GET['period'] ?? '30d';
    $data = ['labels' => [], 'deposits' => [], 'withdrawals' => [], 'net' => []];
    
    if ($period === '24h') {
        // Last 24 hours, hourly steps
        for ($i = 23; $i >= 0; $i--) {
            $hour_time = strtotime("-$i hours");
            $date_str = date('Y-m-d H:00:00', $hour_time);
            $label = date('H:00', $hour_time);
            $data['labels'][] = $label;

            $stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE type='deposit' AND status='approved' AND created_at >= ? AND created_at < ? + INTERVAL 1 HOUR");
            $stmt->execute([$date_str, $date_str]);
            $dep = (float) ($stmt->fetchColumn() ?: 0);
            $data['deposits'][] = $dep;

            $stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE type='withdrawal' AND (status='completed' OR status='approved') AND created_at >= ? AND created_at < ? + INTERVAL 1 HOUR");
            $stmt->execute([$date_str, $date_str]);
            $with = (float) ($stmt->fetchColumn() ?: 0);
            $data['withdrawals'][] = $with;

            $data['net'][] = $dep - $with;
        }
    } else {
        // Daily steps for 3d, 7d, or 30d
        $days = 29;
        if ($period === '3d') $days = 2;
        elseif ($period === '7d') $days = 6;
        
        for ($i = $days; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $data['labels'][] = date('M d', strtotime($date));

            $stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE type='deposit' AND status='approved' AND DATE(created_at) = ?");
            $stmt->execute([$date]);
            $dep = (float) ($stmt->fetchColumn() ?: 0);
            $data['deposits'][] = $dep;

            $stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE type='withdrawal' AND (status='completed' OR status='approved') AND DATE(created_at) = ?");
            $stmt->execute([$date]);
            $with = (float) ($stmt->fetchColumn() ?: 0);
            $data['withdrawals'][] = $with;

            $data['net'][] = $dep - $with;
        }
    }

    // Period aggregated metrics
    $interval_sql = "INTERVAL 30 DAY";
    if ($period === '24h') $interval_sql = "INTERVAL 24 HOUR";
    elseif ($period === '3d') $interval_sql = "INTERVAL 3 DAY";
    elseif ($period === '7d') $interval_sql = "INTERVAL 7 DAY";

    // 1. Gross Inflow
    $gross_inflow = (float)$pdo->query("SELECT SUM(amount) FROM transactions WHERE type='deposit' AND status='approved' AND created_at >= NOW() - $interval_sql")->fetchColumn() ?: 0;
    // 2. Gross Outflow
    $gross_outflow = (float)$pdo->query("SELECT SUM(amount) FROM transactions WHERE type='withdrawal' AND status='completed' AND created_at >= NOW() - $interval_sql")->fetchColumn() ?: 0;
    // 3. Network Bonuses
    $network_bonuses = (float)$pdo->query("SELECT SUM(amount) FROM transactions WHERE type='referral_commission' AND created_at >= NOW() - $interval_sql")->fetchColumn() ?: 0;
    
    $net_profit = $gross_inflow - $gross_outflow - $network_bonuses;
    $profit_margin = $gross_inflow > 0 ? ($net_profit / $gross_inflow) * 100 : 0;

    echo json_encode([
        'status' => 'success', 
        'data' => $data,
        'summary' => [
            'gross_inflow' => $gross_inflow,
            'gross_outflow' => $gross_outflow,
            'network_bonuses' => $network_bonuses,
            'net_profit' => $net_profit,
            'profit_margin' => round($profit_margin, 2)
        ]
    ]);
} elseif ($action === 'run_integrity_scan' && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
    try {
        // Fetch all users
        $stmt = $pdo->query("SELECT id, full_name, email, deposit_balance, earnings_balance, is_banned FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $flagged = [];
        foreach ($users as $u) {
            $u['status'] = $u['is_banned'] ? 'banned' : 'active';
            $user_id = $u['id'];
            
            // 1. Total Approved Deposits
            $stmt_dep = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'deposit' AND status = 'approved'");
            $stmt_dep->execute([$user_id]);
            $total_dep = (float)($stmt_dep->fetchColumn() ?: 0);

            // 2. Total Commissions Earned
            $stmt_com = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'referral_commission' AND status = 'completed'");
            $stmt_com->execute([$user_id]);
            $total_com = (float)($stmt_com->fetchColumn() ?: 0);

            // 3. Total Yield Earnings (Accrued + Completed Investments total_profit)
            $stmt_prof = $pdo->prepare("SELECT SUM(total_profit) FROM user_investments WHERE user_id = ?");
            $stmt_prof->execute([$user_id]);
            $total_prof = (float)($stmt_prof->fetchColumn() ?: 0);

            // 4. Total Outflows (Withdrawals Completed or Pending)
            $stmt_with = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'withdrawal' AND status IN ('completed', 'pending')");
            $stmt_with->execute([$user_id]);
            $total_with = (float)($stmt_with->fetchColumn() ?: 0);

            // 5. Active Stakes (Currently Locked Capital)
            $stmt_stake = $pdo->prepare("SELECT SUM(invested_amount) FROM user_investments WHERE user_id = ? AND status = 'active'");
            $stmt_stake->execute([$user_id]);
            $total_stake = (float)($stmt_stake->fetchColumn() ?: 0);

            // Double-entry expected balance equation
            $expected_total = $total_dep + $total_com + $total_prof - $total_with - $total_stake;
            $current_total = (float)$u['deposit_balance'] + (float)$u['earnings_balance'];

            $diff = abs($current_total - $expected_total);
            if ($diff > 0.05) { // $0.05 margin of safety for floating-point calculations
                $flagged[] = [
                    'id' => $u['id'],
                    'full_name' => $u['full_name'],
                    'email' => $u['email'],
                    'status' => $u['status'],
                    'current_total' => $current_total,
                    'expected_total' => $expected_total,
                    'discrepancy' => $diff
                ];
            }
        }

        echo json_encode([
            'status' => 'success',
            'scanned_count' => count($users),
            'flagged_count' => count($flagged),
            'flagged' => $flagged
        ]);
    } catch (Exception $e) {
        respond('error', 'Audit Failed: ' . $e->getMessage());
    }
} elseif ($action === 'download_db_backup' && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
    try {
        // Generate database backup
        $tables = ['users', 'transactions', 'user_investments', 'plans', 'settings', 'live_feed', 'tiers', 'withdrawal_methods', 'deposit_gateways'];
        $backup = "-- GPT33 Secure Database Backup\n";
        $backup .= "-- Generated on " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($tables as $table) {
            // Check if table exists
            $check = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
            if (!$check) continue;

            $backup .= "-- Table Structure for `$table`\n";
            $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
            $backup .= $createTable[1] . ";\n\n";

            $backup .= "-- Dumping Data for `$table`\n";
            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $keys = array_map(fn($k) => "`$k`", array_keys($row));
                $values = array_map(function($v) use ($pdo) {
                    if ($v === null) return 'NULL';
                    return $pdo->quote($v);
                }, array_values($row));

                $backup .= "INSERT INTO `$table` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n";
            }
            $backup .= "\n";
        }

        // Set headers to force file download
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="gpt33_backup_' . date('Ymd_His') . '.sql"');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $backup;
        exit;
    } catch (Exception $e) {
        respond('error', 'Backup Failed: ' . $e->getMessage());
    }
} elseif ($action === 'get_referral_tree') {
    $user_id = $_GET['user_id'] ?? 0;
    function getDownline($pdo, $parentId, $level = 1)
    {
        if ($level > 5)
            return [];
        $stmt = $pdo->prepare("SELECT id, full_name, email, created_at FROM users WHERE referred_by = ?");
        $stmt->execute([$parentId]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($users as &$u) {
            $u['level'] = $level;
            $u['children'] = getDownline($pdo, $u['id'], $level + 1);
        }
        return $users;
    }
    echo json_encode(['status' => 'success', 'tree' => getDownline($pdo, $user_id)]);
} elseif ($action === 'add_support_link' && $_SESSION['is_admin']) {
    $name = $_POST['platform_name'] ?? '';
    $link = $_POST['link'] ?? '';
    $icon = $_POST['icon'] ?? 'fa-solid fa-link';
    $color = $_POST['color_bg'] ?? '#3b82f6';

    $stmt = $pdo->prepare("INSERT INTO support_links (platform_name, link, icon, color_bg) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$name, $link, $icon, $color])) {
        respond('success', 'Support protocol deployed');
    }
} elseif ($action === 'delete_support_link' && $_SESSION['is_admin']) {
    $id = $_GET['id'] ?? 0;
    $stmt = $pdo->prepare("DELETE FROM support_links WHERE id = ?");
    if ($stmt->execute([$id])) {
        respond('success', 'Support protocol disconnected');
    }
} elseif ($action === 'save_tier') {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $threshold = $_POST['min_investment'] ?? 0;
    $icon = $_POST['icon'] ?? 'fa-solid fa-award';
    $color = $_POST['color'] ?? '#4f46e5';

    if ($id) {
        $stmt = $pdo->prepare("UPDATE tiers SET name=?, min_investment=?, icon=?, color=? WHERE id=?");
        $stmt->execute([$name, $threshold, $icon, $color, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO tiers (name, min_investment, icon, color) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $threshold, $icon, $color]);
    }
    logAdminAction($pdo, 'save_tier', "Tier: $name, Threshold: $threshold");
    respond('success', 'Member tier protocol updated');
} elseif ($action === 'delete_tier') {
    $id = $_GET['id'] ?? 0;
    $stmt = $pdo->prepare("DELETE FROM tiers WHERE id = ?");
    if ($stmt->execute([$id])) {
        logAdminAction($pdo, 'delete_tier', "Tier ID: $id");
        respond('success', 'Member tier protocol deleted');
    }
} elseif ($action === 'get_notifications') {
    $stmt = $pdo->query("SELECT n.*, u.full_name as user_name FROM admin_notifications n LEFT JOIN users u ON n.user_id = u.id WHERE n.is_read = 0 ORDER BY n.created_at DESC LIMIT 5");
    $unread = $stmt->fetchAll();

    $count_stmt = $pdo->query("SELECT COUNT(*) FROM admin_notifications WHERE is_read = 0");
    $count = $count_stmt->fetchColumn();

    echo json_encode(['status' => 'success', 'notifications' => $unread, 'unread_count' => $count]);
    exit;
} elseif ($action === 'mark_notifications_read') {
    $type = $_GET['type'] ?? '';
    if ($type) {
        $stmt = $pdo->prepare("UPDATE admin_notifications SET is_read = 1 WHERE type = ?");
        $stmt->execute([$type]);
    } else {
        $id = $_GET['id'] ?? 0;
        if ($id) {
            $stmt = $pdo->prepare("UPDATE admin_notifications SET is_read = 1 WHERE id = ?");
            $stmt->execute([$id]);
        }
    }
    respond('success', 'Intelligence synchronized');
} elseif ($action === 'login_as_user' && isset($_SESSION['is_admin'])) {
    $user_id = $_GET['id'] ?? 0;
    if ($user_id) {
        // Save admin session state
        $_SESSION['original_admin_id'] = $_SESSION['user_id'];

        // Switch to user session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['is_admin'] = 0; // Temporarily demote session for user view

        header("Location: /dashboard");
        exit;
    }
} elseif ($action === 'restore_admin' && isset($_SESSION['original_admin_id'])) {
    // Revert to admin session
    $_SESSION['user_id'] = $_SESSION['original_admin_id'];
    $_SESSION['is_admin'] = 1;
    unset($_SESSION['original_admin_id']);

    header("Location: /admin/index.php?route=users");
    exit;
}

elseif ($action === 'search_users' && isset($_SESSION['is_admin'])) {
    $query = $_GET['query'] ?? '';
    if (strlen($query) < 2) {
        echo json_encode(['status' => 'success', 'users' => []]);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE is_admin = 0 AND (full_name LIKE ? OR email LIKE ?) LIMIT 10");
    $stmt->execute(['%' . $query . '%', '%' . $query . '%']);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'users' => $users]);
    exit;
} elseif ($action === 'post_to_feed' && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
    $message = $_POST['feed_message'] ?? '';
    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO live_feed (type, user_name, amount, message) VALUES ('admin', 'Admin', 0, ?)");
        if ($stmt->execute([$message])) {
            logAdminAction($pdo, 'post_to_feed', "Manual announcement broadcasted");
            respond('success', 'Announcement published to live feed successfully');
        } else {
            respond('error', 'Failed to publish announcement');
        }
    } else {
        respond('error', 'Broadcast message cannot be empty');
    }
} elseif ($action === 'sync_database_schema' && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
    try {
        // 1. Repair transactions type ENUM to support hourly_profit, manual_adjustment, investment
        $pdo->exec("ALTER TABLE transactions MODIFY COLUMN type ENUM('deposit','withdrawal','referral_commission','manual_adjustment','investment','profit','hourly_profit') NOT NULL");
        
        // 2. Ensure max_invest exists on plans table
        $check = $pdo->query("SHOW COLUMNS FROM plans LIKE 'max_invest'")->fetch();
        if (!$check) {
            $pdo->exec("ALTER TABLE plans ADD COLUMN max_invest DECIMAL(15,2) DEFAULT 10000.00");
            $pdo->exec("UPDATE plans SET max_invest = min_invest * 10");
        }
        
        // 3. Ensure live_feed table exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS live_feed (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type ENUM('admin', 'withdrawal') NOT NULL DEFAULT 'withdrawal',
            user_name VARCHAR(100) NULL,
            amount DECIMAL(15,2) NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        logAdminAction($pdo, 'sync_database_schema', "Database schema synchronized");
        respond('success', 'Database schema successfully synchronized and repaired!');
    } catch (Exception $e) {
        respond('error', 'Sync Failed: ' . $e->getMessage());
    }
}
?>