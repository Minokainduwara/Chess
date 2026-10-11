<?php
/**
 * Captain Login View
 * FOT Knights Arena — Chess Tournament
 */
?>
<div class="card" style="max-width: 500px; margin: 2rem auto;">
    <h1 class="section-title">Captain Portal</h1>
    <p style="margin-bottom: 1.5rem; color: var(--ink-light);">Log in to manage your team's roster and drop boards.</p>
    <form method="POST">
        <input type="hidden" name="login_action" value="1">
        <div style="margin-bottom: 1.25rem;">
            <label style="font-weight: bold; font-family: var(--font-display);">Team Name</label>
            <input type="text" name="team_name" class="form-control" placeholder="e.g. FOT Gambit Kings" required>
        </div>
        <div style="margin-bottom: 1.5rem;">
            <label style="font-weight: bold; font-family: var(--font-display);">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Enter captain password" required>
        </div>
        <button type="submit" class="btn-primary" style="width: 100%;">Log In</button>
    </form>
</div>
