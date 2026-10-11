<?php
/**
 * Admin Login View Partial
 * FOT Knights Arena — Chess Tournament
 */
?>
<div class="login-wrap">
    <div style="text-align: center; font-size: 2.5rem; margin-bottom: 0.5rem;">♕</div>
    <h1 class="login-title">Organizer Sign In</h1>
    <p class="login-subtitle">Enter administrator credentials to manage tournament rosters.</p>
    <form method="POST" action="admin.php">
        <input type="hidden" name="login_action" value="1">
        <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <input type="text" id="username" name="username" class="form-control" placeholder="Enter username" required autofocus>
        </div>
        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn-submit">Authenticate →</button>
    </form>
</div>
