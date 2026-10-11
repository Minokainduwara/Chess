<?php
/**
 * Administrator Password Change View Partial
 * FOT Knights Arena — Chess Tournament
 */
?>
<!-- Change Password Manager -->
<div class="team-card" id="password-manager" style="margin-top: 2rem; border-top: 3px solid var(--ink);">
    <div class="team-card-header" style="background: var(--cream);">
        <div>
            <span style="font-family: var(--font-display); font-size: 1.35rem; font-weight: 700; color: var(--ink);">
                🔑 Change Administrator Password
            </span>
            <div style="font-size: 0.85rem; color: var(--ink-muted); margin-top: 0.25rem;">
                Update the password for administrator account: <strong><?= htmlspecialchars($admin_user ?? 'admin') ?></strong>
            </div>
        </div>
    </div>
    <div style="padding: 1.5rem; max-width: 550px;">
        <form method="POST" action="admin.php#password-manager">
            <input type="hidden" name="change_admin_password" value="1">
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label class="form-label" for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Enter current password" required autocomplete="current-password">
            </div>
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label class="form-label" for="new_password">New Password (Min. 6 characters)</label>
                <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Enter new password" minlength="6" required autocomplete="new-password">
            </div>
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label" for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter new password" minlength="6" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn-submit" style="width: auto; padding: 0.65rem 1.75rem;">
                Update Password
            </button>
        </form>
    </div>
</div>
