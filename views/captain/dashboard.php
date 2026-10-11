<?php
/**
 * Captain Roster Dashboard View
 * FOT Knights Arena — Chess Tournament
 */
?>
<div class="card">
    <h2 style="font-size: 1.5rem; margin-bottom: 1rem; font-family: var(--font-display);">Squad Roster</h2>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 70px;">Board</th>
                    <th>Name</th>
                    <th>Reg Number</th>
                    <th>Gender</th>
                    <th>Batch</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $m): ?>
                    <tr>
                        <td>Board <?= (int)$m['member_order'] ?></td>
                        <td style="font-weight: 600;">
                            <?= htmlspecialchars($m['name']) ?> 
                            <?= $m['is_captain'] ? '<span class="member-badge-captain" style="margin-left: 5px;">★ Captain</span>' : '' ?>
                        </td>
                        <td><?= htmlspecialchars($m['reg_number']) ?></td>
                        <td><?= htmlspecialchars($m['gender']) ?></td>
                        <td><?= htmlspecialchars($m['batch_year']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
