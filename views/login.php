<div class="auth-container" style="max-width: 420px; margin: 60px auto; padding: 0 20px;">

    <!-- Branded Header -->
    <div style="text-align: center; margin-bottom: 40px;">
        <div
            style="width: 70px; height: 70px; background: var(--gradient); border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; font-size: 2rem; color: white; box-shadow: var(--neon-blue); transform: rotate(-10deg);">
            <i class="fa-solid fa-cube"></i>
        </div>
        <h1
            style="font-size: 2.2rem; font-weight: 900; letter-spacing: -1px; margin: 0; background: linear-gradient(to bottom, #fff, #94a3b8); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            GPT33</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 5px;">Enterprise AI Investment Platform</p>
    </div>

    <div class="card"
        style="padding: 35px; border: 1px solid var(--glass-border); background: rgba(20, 26, 38, 0.8); backdrop-filter: blur(20px);">
        <h2 style="font-size: 1.4rem; font-weight: 700; margin-bottom: 10px;">Welcome Back</h2>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 30px;">Login to access your high-yield AI
            plans.</p>

        <form id="login-form" data-ajax-form data-action="/api/auth?action=login" action="/api/auth?action=login" method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <div style="position: relative;">
                    <i class="fa-regular fa-envelope"
                        style="position: absolute; left: 15px; top: 15px; color: var(--text-muted);"></i>
                    <input type="email" name="email" class="form-control" placeholder="you@example.com"
                        style="padding-left: 45px; background: rgba(0,0,0,0.2);" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label>Password</label>
                <div style="position: relative;">
                    <i class="fa-solid fa-lock"
                        style="position: absolute; left: 15px; top: 15px; color: var(--text-muted);"></i>
                    <input type="password" name="password" class="form-control" placeholder="••••••••"
                        style="padding-left: 45px; background: rgba(0,0,0,0.2);" required>
                </div>
            </div>

            <div style="text-align: right; margin-bottom: 25px;">
                <a href="#"
                    style="font-size: 0.8rem; color: var(--primary); text-decoration: none; font-weight: 600;">Forgot
                    Password?</a>
            </div>

            <button type="submit" class="btn"
                style="padding: 15px; font-weight: 700; font-size: 1rem; letter-spacing: 0.5px;">
                Sign In <i class="fa-solid fa-arrow-right-to-bracket" style="margin-left: 10px;"></i>
            </button>
        </form>

        <div style="text-align: center; margin-top: 35px; font-size: 0.9rem; color: var(--text-muted);">
            New to GPT33? <a href="register" data-link
                style="color: var(--primary); text-decoration: none; font-weight: 700; margin-left: 5px;">Create
                Account</a>
        </div>
    </div>

    <div
        style="text-align: center; margin-top: 30px; display: flex; align-items: center; justify-content: center; gap: 15px; color: var(--text-muted); font-size: 0.75rem; opacity: 0.6;">
        <div style="display: flex; align-items: center; gap: 5px;"><i class="fa-solid fa-shield-halved"></i> 256-bit SSL
        </div>
        <div style="width: 4px; height: 4px; background: var(--text-muted); border-radius: 50%;"></div>
        <div style="display: flex; align-items: center; gap: 5px;"><i class="fa-solid fa-lock"></i> Encrypted</div>
    </div>
</div>