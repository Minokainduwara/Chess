<?php
/**
 * Dynamic Round Schedule Section
 * FOT Knights Arena — Chess Tournament
 */
?>
    <div class="page-rail"><hr class="section-rule--light section-rule"></div>

    <!-- ═══ SCHEDULE (Variation notation) ═══ -->
    <section class="schedule page-rail" id="schedule" aria-labelledby="schedule-heading">
        <h2 class="schedule-heading" id="schedule-heading">
            <span aria-hidden="true">⌖</span> Round Schedule
        </h2>
        <ol class="round-list">
            <?php if (!empty($schedule_rounds)): ?>
                <?php foreach ($schedule_rounds as $r): ?>
                    <li class="round-item gtouch-tap">
                        <span class="round-number"><?= (int)$r['round_number'] ?>.</span>
                        <span class="round-date"><?= htmlspecialchars($r['schedule_date'] ?? '10 Oct 2026') ?></span>
                        <span class="round-time"><?= htmlspecialchars($r['schedule_time'] ?? '09:00 – 10:30') ?></span>
                        <?php
                        $status_class = "round-status";
                        if ($r['status'] === 'open') $status_class .= " round-status--active";
                        ?>
                        <span class="<?= $status_class ?>"><?= htmlspecialchars($r['schedule_status'] ?? 'Scheduled') ?></span>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="round-item gtouch-tap">
                    <span class="round-number">-</span>
                    <span class="round-date">TBD</span>
                    <span class="round-time">TBD</span>
                    <span class="round-status">Schedule Pending</span>
                </li>
            <?php endif; ?>
        </ol>
    </section>
