<?php
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='/login';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user->execute([$user_id]);
$userData = $user->fetch();
?>

<div class="profile-page" style="padding-bottom: 100px;">
    
    <!-- Profile Form -->
    <div class="card">
        <div style="text-align: center; margin-bottom: 25px;">
            <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 2rem; color: white; margin: 0 auto 15px auto; box-shadow: var(--neon-blue);">
                <i class="fa-solid fa-user"></i>
            </div>
            <h3 style="margin: 0;"><?php echo htmlspecialchars($userData['full_name']); ?></h3>
            <p style="font-size: 0.8rem; color: var(--text-muted);">Joined <?php echo date('M j, Y', strtotime($userData['created_at'])); ?></p>
        </div>

        <form id="profile-form" data-ajax-form data-action="/api/profile_update" data-callback="onProfileUpdate">
            <div class="form-group">
                <label>Full Name</label>
                <div style="position: relative;">
                    <i class="fa-regular fa-user" style="position: absolute; left: 15px; top: 15px; color: var(--text-muted);"></i>
                    <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($userData['full_name']); ?>" style="padding-left: 40px;" required>
                </div>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <div style="position: relative;">
                    <i class="fa-regular fa-envelope" style="position: absolute; left: 15px; top: 15px; color: var(--text-muted);"></i>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($userData['email']); ?>" style="padding-left: 40px;" required>
                </div>
            </div>

            <div style="margin-top: 25px;">
                <button type="submit" class="btn">Update Profile <i class="fa-solid fa-check" style="margin-left: 8px;"></i></button>
            </div>
        </form>
    </div>

    <!-- Security Section Link -->
    <div class="card" style="display: flex; align-items: center; gap: 15px; padding: 15px 20px; background: rgba(139, 92, 246, 0.05); border: 1px solid rgba(139, 92, 246, 0.1);">
        <div style="width: 35px; height: 35px; border-radius: 8px; background: rgba(139, 92, 246, 0.1); display: flex; align-items: center; justify-content: center; color: var(--secondary);">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        <div style="flex: 1;">
            <div style="font-weight: 700; font-size: 0.9rem;">Security Settings</div>
            <div style="font-size: 0.7rem; color: var(--text-muted);">Password & Two-Factor</div>
        </div>
        <i class="fa-solid fa-chevron-right" style="color: var(--text-muted); font-size: 0.8rem;"></i>
    </div>

</div>

<script>
    window.onProfileUpdate = (data) => {
        window.router();
    };
</script>
