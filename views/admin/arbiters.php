<?php
/**
 * Administrator Arbiters Management View Partial
 * FOT Knights Arena — Chess Tournament
 */
?>
<!-- Arbiters Management Section -->
<div class="team-card" id="arbiters-manager" style="margin-top: 2rem; border-top: 3px solid var(--red);">
    <div class="team-card-header" style="background: var(--cream);">
        <div>
            <span style="font-family: var(--font-display); font-size: 1.35rem; font-weight: 700; color: var(--ink);">
                ⚖️ Tournament Arbiters & Officials
            </span>
            <div style="font-size: 0.85rem; color: var(--ink-muted); margin-top: 0.25rem;">
                Manage tournament arbiters (Chief Arbiter, Deputy Arbiters, Sector Arbiters). These details are displayed publicly on the tournament website.
            </div>
        </div>
        <div>
            <button type="button" class="btn-action" onclick="toggleAddArbiterForm()" style="background: var(--ink); color: #fff;">
                + Add Arbiter
            </button>
        </div>
    </div>

    <!-- Add Arbiter Form (Collapsible) -->
    <div id="add-arbiter-panel" style="display: none; padding: 1.5rem; background: #FAF7EE; border-bottom: 2px dashed var(--rule);">
        <h4 style="font-family: var(--font-display); margin-bottom: 1rem; color: var(--ink);">Add New Arbiter / Official</h4>
        <form method="POST" action="admin.php#arbiters-manager">
            <input type="hidden" name="add_arbiter" value="1">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="arb_name">Full Name *</label>
                    <input type="text" id="arb_name" name="name" class="form-control" placeholder="e.g. IA / FA Sunimal Perera" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="arb_role">Official Role / Designation *</label>
                    <input type="text" id="arb_role" name="role" class="form-control" placeholder="e.g. Chief Arbiter, Deputy Chief Arbiter" value="Arbiter" required list="arbiter_roles">
                    <datalist id="arbiter_roles">
                        <option value="Chief Arbiter">
                        <option value="Deputy Chief Arbiter">
                        <option value="Senior Arbiter">
                        <option value="Sector Arbiter">
                        <option value="Arbiter">
                        <option value="Match Arbiter">
                    </datalist>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="arb_fide">FIDE ID / License / Title</label>
                    <input type="text" id="arb_fide" name="fide_id" class="form-control" placeholder="e.g. IA (FIDE ID: 9942001)">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="arb_contact">Contact (Phone / Email)</label>
                    <input type="text" id="arb_contact" name="contact" class="form-control" placeholder="e.g. +94 77 123 4567 / arbiter@ruh.ac.lk">
                </div>
                <div class="form-group" style="margin-bottom: 0; max-width: 140px;">
                    <label class="form-label" for="arb_order">Display Priority</label>
                    <input type="number" id="arb_order" name="display_order" class="form-control" value="1" min="1" step="1">
                </div>
            </div>
            <div style="display: flex; gap: 0.75rem;">
                <button type="submit" class="btn-submit" style="width: auto; padding: 0.65rem 1.75rem;">Save Arbiter</button>
                <button type="button" class="btn-action" onclick="toggleAddArbiterForm()">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Arbiters List -->
    <div style="padding: 1.5rem; overflow-x: auto;">
        <?php if (empty($all_arbiters)): ?>
            <div style="text-align: center; padding: 2.5rem 1rem; color: var(--ink-muted);">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">⚖️</div>
                <div style="font-family: var(--font-display); font-size: 1.15rem; font-weight: 700; margin-bottom: 0.25rem;">No Arbiters Added Yet</div>
                <p style="font-size: 0.95rem;">Click the <strong>+ Add Arbiter</strong> button above to register the Chief Arbiter and other officials for this tournament.</p>
            </div>
        <?php else: ?>
            <table class="roster-table" style="min-width: 700px;">
                <thead>
                    <tr>
                        <th style="width: 80px;">Order</th>
                        <th>Name</th>
                        <th>Role / Designation</th>
                        <th>FIDE / License</th>
                        <th>Contact</th>
                        <th style="text-align: right; width: 180px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_arbiters as $arb): ?>
                        <tr id="arbiter-row-<?= $arb['id'] ?>">
                            <td>
                                <span class="badge" style="background: var(--cream-dark); color: var(--ink); font-weight: bold;">
                                    #<?= htmlspecialchars($arb['display_order']) ?>
                                </span>
                            </td>
                            <td>
                                <strong style="font-family: var(--font-display); font-size: 1.05rem;"><?= htmlspecialchars($arb['name']) ?></strong>
                            </td>
                            <td>
                                <?php $arb_role = $arb['role'] ?? 'Arbiter'; ?>
                                <span class="badge <?= (stripos($arb_role, 'chief') !== false) ? 'badge-captain' : 'badge-gold' ?>">
                                    <?= htmlspecialchars($arb_role) ?>
                                </span>
                            </td>
                            <td>
                                <?= !empty($arb['fide_id']) ? htmlspecialchars($arb['fide_id']) : '<span style="color: var(--ink-muted);">—</span>' ?>
                            </td>
                            <td>
                                <?= !empty($arb['contact']) ? htmlspecialchars($arb['contact']) : '<span style="color: var(--ink-muted);">—</span>' ?>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <button type="button" class="btn-action" style="padding: 0.3rem 0.65rem; font-size: 0.8rem;" onclick="toggleEditArbiter(<?= $arb['id'] ?>)">
                                    ✏️ Edit
                                </button>
                                <form method="POST" action="admin.php#arbiters-manager" style="display: inline-block; margin-left: 0.35rem;" onsubmit="return confirm('Are you sure you want to remove arbiter <?= htmlspecialchars(addslashes($arb['name'] ?? '')) ?>?');">
                                    <input type="hidden" name="delete_arbiter" value="1">
                                    <input type="hidden" name="arbiter_id" value="<?= $arb['id'] ?>">
                                    <button type="submit" class="btn-delete" style="padding: 0.3rem 0.65rem;">
                                        ✕ Remove
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <!-- Inline Edit Row -->
                        <tr id="arbiter-edit-row-<?= $arb['id'] ?>" style="display: none; background: #FFF9E6;">
                            <td colspan="6" style="padding: 1.25rem 1.5rem;">
                                <form method="POST" action="admin.php#arbiters-manager">
                                    <input type="hidden" name="update_arbiter" value="1">
                                    <input type="hidden" name="arbiter_id" value="<?= $arb['id'] ?>">
                                    <div style="font-family: var(--font-display); font-weight: 700; margin-bottom: 0.75rem; color: var(--ink);">
                                        Edit Arbiter #<?= $arb['id'] ?>: <?= htmlspecialchars($arb['name'] ?? '') ?>
                                    </div>
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                                        <div>
                                            <label class="form-label">Full Name *</label>
                                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($arb['name'] ?? '') ?>" required>
                                        </div>
                                        <div>
                                            <label class="form-label">Role / Designation *</label>
                                            <input type="text" name="role" class="form-control" value="<?= htmlspecialchars($arb['role'] ?? 'Arbiter') ?>" required list="arbiter_roles">
                                        </div>
                                        <div>
                                            <label class="form-label">FIDE ID / License</label>
                                            <input type="text" name="fide_id" class="form-control" value="<?= htmlspecialchars($arb['fide_id'] ?? '') ?>">
                                        </div>
                                        <div>
                                            <label class="form-label">Contact</label>
                                            <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($arb['contact'] ?? '') ?>">
                                        </div>
                                        <div style="max-width: 120px;">
                                            <label class="form-label">Order</label>
                                            <input type="number" name="display_order" class="form-control" value="<?= (int)$arb['display_order'] ?>" min="1">
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <button type="submit" class="btn-submit" style="width: auto; padding: 0.5rem 1.25rem; font-size: 0.9rem;">
                                            Save Changes
                                        </button>
                                        <button type="button" class="btn-action" style="padding: 0.5rem 1rem; font-size: 0.9rem;" onclick="toggleEditArbiter(<?= $arb['id'] ?>)">
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleAddArbiterForm() {
    var p = document.getElementById('add-arbiter-panel');
    if (!p) return;
    p.style.display = (p.style.display === 'none' || p.style.display === '') ? 'block' : 'none';
    if (p.style.display === 'block') {
        var n = document.getElementById('arb_name');
        if (n) n.focus();
    }
}

function toggleEditArbiter(id) {
    var editRow = document.getElementById('arbiter-edit-row-' + id);
    if (!editRow) return;
    editRow.style.display = (editRow.style.display === 'none' || editRow.style.display === '') ? 'table-row' : 'none';
}
</script>
