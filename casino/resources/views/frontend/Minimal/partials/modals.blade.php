<!-- Auth Modals -->
<div id="modal-login" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Login</h2>
        <form id="login-form">
            @csrf
            <div class="form-group">
                <label for="login-username">Username</label>
                <input type="text" id="login-username" name="username" required>
            </div>
            <div class="form-group">
                <label for="login-password">Password</label>
                <input type="password" id="login-password" name="password" required>
            </div>
            <button type="submit" class="btn-primary">Login</button>
            <div class="form-message error"></div>
        </form>
    </div>
</div>

<div id="modal-register" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Register</h2>
        <form id="register-form">
            @csrf
            <div class="form-group">
                <label for="register-email">Email</label>
                <input type="email" id="register-email" name="email" required>
            </div>
            <div class="form-group">
                <label for="register-username">Username</label>
                <input type="text" id="register-username" name="username" required>
            </div>
            <div class="form-group">
                <label for="register-password">Password</label>
                <input type="password" id="register-password" name="password" required>
            </div>
            <div class="form-group">
                <label for="register-password-confirm">Confirm Password</label>
                <input type="password" id="register-password-confirm" name="password_confirmation" required>
            </div>
            <button type="submit" class="btn-primary">Register</button>
            <div class="form-message error"></div>
        </form>
    </div>
</div>

<!-- Profile Modal -->
<div id="modal-profile" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Profile</h2>
        <div id="profile-data">
            <div class="loading">Loading...</div>
            <!-- Data will be injected here via JS -->
        </div>
    </div>
</div>