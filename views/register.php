<div class="auth-container" style="max-width: 450px; margin: 40px auto;">

    <!-- Steps Indicator -->
    <div class="steps-indicator"
        style="display: flex; justify-content: space-between; margin-bottom: 30px; position: relative; padding: 0 20px;">
        <div
            style="position: absolute; top: 15px; left: 40px; right: 40px; height: 2px; background: var(--glass-border); z-index: 1;">
        </div>
        <div id="step-line"
            style="position: absolute; top: 15px; left: 40px; width: 0%; height: 2px; background: var(--primary); z-index: 2; transition: var(--transition);">
        </div>

        <div class="step active" id="step-dot-1" style="z-index: 3; text-align: center;">
            <div
                style="width: 32px; height: 32px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px auto; box-shadow: 0 0 15px rgba(59, 130, 246, 0.5);">
                <i class="fa-solid fa-user" style="font-size: 0.8rem;"></i>
            </div>
            <span style="font-size: 0.7rem; color: var(--primary);">Personal</span>
        </div>
        <div class="step" id="step-dot-2" style="z-index: 3; text-align: center;">
            <div
                style="width: 32px; height: 32px; background: var(--bg-card); border: 2px solid var(--glass-border); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px auto;">
                <i class="fa-solid fa-lock" style="font-size: 0.8rem; color: var(--text-muted);"></i>
            </div>
            <span style="font-size: 0.7rem; color: var(--text-muted);">Security</span>
        </div>
        <div class="step" id="step-dot-3" style="z-index: 3; text-align: center;">
            <div
                style="width: 32px; height: 32px; background: var(--bg-card); border: 2px solid var(--glass-border); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px auto;">
                <i class="fa-solid fa-phone" style="font-size: 0.8rem; color: var(--text-muted);"></i>
            </div>
            <span style="font-size: 0.7rem; color: var(--text-muted);">Contact</span>
        </div>
    </div>

    <div class="card">
        <form id="register-form" data-ajax-form data-action="/api/auth?action=register" action="/api/auth?action=register" method="POST">
            <!-- Step 1: Personal Info -->
            <div id="step-1" class="registration-step">
                <h2 style="text-align: center; margin-bottom: 5px;">Personal Info</h2>
                <p style="text-align: center; color: var(--text-muted); margin-bottom: 25px; font-size: 0.8rem;">Step 1
                    of 3</p>

                <div class="form-group">
                    <label>Full Name</label>
                    <div style="position: relative;">
                        <i class="fa-regular fa-user"
                            style="position: absolute; left: 15px; top: 15px; color: var(--text-muted);"></i>
                        <input type="text" name="full_name" class="form-control" placeholder="Enter your full name"
                            style="padding-left: 45px;" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <div style="position: relative;">
                        <i class="fa-regular fa-envelope"
                            style="position: absolute; left: 15px; top: 15px; color: var(--text-muted);"></i>
                        <input type="email" name="email" class="form-control" placeholder="you@example.com"
                            style="padding-left: 45px;" required>
                    </div>
                </div>

                <button type="button" class="btn" onclick="nextStep(2)">Continue <i class="fa-solid fa-arrow-right"
                        style="margin-left: 8px;"></i></button>
            </div>

            <!-- Step 2: Security -->
            <div id="step-2" class="registration-step" style="display: none;">
                <h2 style="text-align: center; margin-bottom: 5px;">Security</h2>
                <p style="text-align: center; color: var(--text-muted); margin-bottom: 25px; font-size: 0.8rem;">Step 2
                    of 3</p>

                <div class="form-group">
                    <label>Password</label>
                    <div style="position: relative;">
                        <i class="fa-solid fa-lock"
                            style="position: absolute; left: 15px; top: 15px; color: var(--text-muted);"></i>
                        <input type="password" name="password" class="form-control" placeholder="Create a password"
                            style="padding-left: 45px;" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <div style="position: relative;">
                        <i class="fa-solid fa-lock"
                            style="position: absolute; left: 15px; top: 15px; color: var(--text-muted);"></i>
                        <input type="password" name="confirm_password" class="form-control"
                            placeholder="Confirm your password" style="padding-left: 45px;" required>
                    </div>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn btn-outline" onclick="nextStep(1)" style="flex: 1;">Back</button>
                    <button type="button" class="btn" onclick="nextStep(3)" style="flex: 2;">Continue <i
                            class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i></button>
                </div>
            </div>

            <!-- Step 3: Contact & Referral -->
            <div id="step-3" class="registration-step" style="display: none;">
                <h2 style="text-align: center; margin-bottom: 5px;">Final Details</h2>
                <p style="text-align: center; color: var(--text-muted); margin-bottom: 25px; font-size: 0.8rem;">Step 3
                    of 3</p>

                <div class="form-group">
                    <label>Referral Code (Optional)</label>
                    <div style="position: relative;">
                        <i class="fa-solid fa-user-plus"
                            style="position: absolute; left: 15px; top: 15px; color: var(--text-muted);"></i>
                        <input type="text" name="referral_code" class="form-control" placeholder="Enter referral code"
                            style="padding-left: 45px;">
                    </div>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn btn-outline" onclick="nextStep(2)" style="flex: 1;">Back</button>
                    <button type="submit" class="btn" style="flex: 2;">Create Account <i class="fa-solid fa-check"
                            style="margin-left: 8px;"></i></button>
                </div>
            </div>
        </form>

        <div style="text-align: center; margin-top: 30px; font-size: 0.9rem; color: var(--text-muted);">
            Already have an account? <a href="login" data-link
                style="color: var(--primary); text-decoration: none; font-weight: 600;">Login</a>
        </div>
    </div>
</div>

<script>
    function nextStep(step) {
        // Hide all steps
        document.querySelectorAll('.registration-step').forEach(el => el.style.display = 'none');
        // Show target step
        document.getElementById('step-' + step).style.display = 'block';

        // Update indicators
        const stepLine = document.getElementById('step-line');
        stepLine.style.width = ((step - 1) / 2 * 100) + '%';

        // Update dots
        for (let i = 1; i <= 3; i++) {
            const dot = document.getElementById('step-dot-' + i);
            const icon = dot.querySelector('i');
            const span = dot.querySelector('span');
            const circle = dot.querySelector('div');

            if (i <= step) {
                circle.style.background = 'var(--primary)';
                circle.style.borderColor = 'var(--primary)';
                icon.style.color = 'white';
                span.style.color = 'var(--primary)';
                if (i === step) circle.style.boxShadow = '0 0 15px rgba(59, 130, 246, 0.5)';
                else circle.style.boxShadow = 'none';
            } else {
                circle.style.background = 'var(--bg-card)';
                circle.style.borderColor = 'var(--glass-border)';
                icon.style.color = 'var(--text-muted)';
                span.style.color = 'var(--text-muted)';
                circle.style.boxShadow = 'none';
            }
        }
    }

    // Auto-fill referral code from URL
    const urlParams = new URLSearchParams(window.location.search);
    const refCode = urlParams.get('ref');
    if (refCode) {
        document.querySelector('input[name="referral_code"]').value = refCode;
        // Optionally jump to step 3 or just keep it filled
    }
</script>