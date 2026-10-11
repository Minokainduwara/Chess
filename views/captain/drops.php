<?php
/**
 * Captain Drop Boards Management View
 * FOT Knights Arena — Chess Tournament
 */
?>
<div class="card">
    <?php if ($active_round): ?>
        <?php
        date_default_timezone_set('Asia/Colombo');
        $is_open = true;
        if (!empty($active_round['deadline']) && strtotime($active_round['deadline']) < time()) {
            $is_open = false;
        }
        $required_drops = count($members) - 4;
        ?>
        <h2 style="font-size: 1.5rem; margin-bottom: 0.5rem; font-family: var(--font-display);">Round <?= (int)$active_round['round_number'] ?> Drop Boards</h2>
        <p style="color: var(--ink-light); margin-bottom: 1.5rem;">
            Deadline: <?= !empty($active_round['deadline']) ? date('Y-m-d h:i A', strtotime($active_round['deadline'])) : 'Open' ?>
        </p>
        
        <?php if ($is_open && $required_drops > 0): ?>
            <p style="margin-bottom: 1rem; font-weight: bold;">Action Required: Select exactly <?= $required_drops ?> member(s) to drop for this round.</p>
            <form method="POST">
                <input type="hidden" name="drop_action" value="1">
                <input type="hidden" name="round_number" value="<?= (int)$active_round['round_number'] ?>">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="text-align: center; width: 60px;">Drop</th>
                                <th>Order</th>
                                <th>Name</th>
                                <th>Reg Number</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $m): ?>
                                <tr>
                                    <td style="text-align: center;">
                                        <input type="checkbox" name="dropped_members[]" value="<?= (int)$m['id'] ?>" <?= in_array($m['id'], $drops) ? 'checked' : '' ?> style="transform: scale(1.2);">
                                    </td>
                                    <td>Board <?= (int)$m['member_order'] ?></td>
                                    <td><?= htmlspecialchars($m['name']) ?></td>
                                    <td><?= htmlspecialchars($m['reg_number']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <br>
                <button type="submit" class="btn-primary">Save Drop Boards</button>
            </form>
        <?php elseif ($is_open && $required_drops === 0): ?>
            <p>Your team has exactly 4 members. No drops are necessary.</p>
        <?php else: ?>
            <p style="color: var(--red); font-weight:bold; margin-bottom: 1rem;">The deadline for this round has passed. Drop boards are locked.</p>
            <?php
                $actual_drops = $drops;
                $playing_count = 0;
                foreach ($members as $m) {
                    if (!in_array($m['id'], $actual_drops) && $playing_count < 4) {
                        $playing_count++;
                    } elseif (!in_array($m['id'], $actual_drops)) {
                        $actual_drops[] = $m['id'];
                    }
                }
            ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Order</th>
                            <th>Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $m): ?>
                            <tr>
                                <td style="font-weight: bold; color: <?= in_array($m['id'], $actual_drops) ? 'var(--red)' : 'var(--blue)' ?>;">
                                    <?= in_array($m['id'], $actual_drops) ? 'Dropped' : 'Playing' ?>
                                </td>
                                <td>Board <?= (int)$m['member_order'] ?></td>
                                <td><?= htmlspecialchars($m['name']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <p>There are no active rounds at the moment.</p>
    <?php endif; ?>
</div>
