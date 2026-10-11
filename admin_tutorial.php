<?php
/**
 * Administrator Tutorial & Operational Handbook
 * FOT Knights Arena — Chess Tournament Platform
 */

require_once __DIR__ . '/src/Services/AuthService.php';
AuthService::startSession();
$is_authenticated = AuthService::isAdminLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Tutorial &amp; Handbook — FOT Knights Arena</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --cream: #FAF7EE;
            --cream-card: #F2EDE0;
            --cream-dark: #E8E1CE;
            --ink: #0A0A0A;
            --ink-muted: #71717A;
            --red: #C0392B;
            --red-hover: #962D22;
            --rule: #D3CABA;
            --rule-light: #E8E1CE;
            --font-display: 'Cinzel', 'Georgia', serif;
            --font-body: 'EB Garamond', 'Georgia', serif;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: var(--font-body); background: var(--cream); color: var(--ink); line-height: 1.7; min-height: 100vh; padding-bottom: 5rem; }
        .container { max-width: 1180px; margin: 0 auto; padding: 0 1.5rem; }
        
        /* Header */
        .admin-header { border-bottom: 2px solid var(--ink); padding: 1.25rem 0; background: var(--cream); position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .admin-header-inner { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
        .admin-brand { display: flex; align-items: center; gap: 0.85rem; text-decoration: none; color: var(--ink); }
        .admin-brand-icon { width: 38px; height: 38px; background: var(--ink); color: var(--cream); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; }
        .admin-brand-title { font-family: var(--font-display); font-weight: 700; font-size: 1.25rem; line-height: 1.1; }
        .admin-brand-subtitle { font-size: 0.85rem; color: var(--ink-muted); text-transform: uppercase; }
        .admin-nav { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
        .btn-link { font-family: var(--font-display); font-size: 0.95rem; text-decoration: none; color: var(--ink); padding: 0.4rem 0.8rem; font-weight: 600; }
        .btn-link:hover { color: var(--red); }
        .btn-primary { background: var(--red); color: #FFF !important; padding: 0.45rem 1rem; border-radius: 2px; }
        .btn-primary:hover { background: var(--red-hover); }

        /* Hero */
        .tutorial-hero { padding: 3rem 0 2rem; border-bottom: 1px solid var(--rule); margin-bottom: 2.5rem; }
        .tutorial-badge { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.25rem 0.75rem; background: #FFEBEE; color: var(--red); font-family: var(--font-display); font-size: 0.8rem; font-weight: 700; border: 1px solid #FFCDD2; text-transform: uppercase; margin-bottom: 1rem; }
        .tutorial-title { font-family: var(--font-display); font-size: 2.5rem; font-weight: 700; line-height: 1.2; margin-bottom: 0.75rem; color: var(--ink); }
        .tutorial-lead { font-size: 1.25rem; color: var(--ink-muted); max-width: 820px; line-height: 1.6; }

        /* Layout Grid */
        .tutorial-layout { display: grid; grid-template-columns: 280px 1fr; gap: 2.5rem; align-items: flex-start; }
        @media (max-width: 900px) {
            .tutorial-layout { grid-template-columns: 1fr; }
        }

        /* Sidebar Nav */
        .tutorial-sidebar { position: sticky; top: 90px; background: #FFF; border: 1px solid var(--rule); padding: 1.5rem; box-shadow: 2px 2px 0 rgba(0,0,0,0.03); max-height: calc(100vh - 120px); overflow-y: auto; }
        .sidebar-heading { font-family: var(--font-display); font-size: 1rem; font-weight: 700; text-transform: uppercase; color: var(--ink); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--ink); }
        .sidebar-menu { list-style: none; }
        .sidebar-item { margin-bottom: 0.5rem; }
        .sidebar-link { display: block; font-size: 0.95rem; text-decoration: none; color: var(--ink-muted); padding: 0.35rem 0.5rem; border-radius: 2px; transition: all 0.2s; line-height: 1.4; }
        .sidebar-link:hover, .sidebar-link.active { background: var(--cream); color: var(--red); font-weight: 600; padding-left: 0.75rem; }

        /* Guide Cards */
        .guide-section { background: #FFF; border: 1px solid var(--rule); margin-bottom: 2rem; box-shadow: 2px 2px 0 rgba(0,0,0,0.03); }
        .guide-header { padding: 1.25rem 1.75rem; background: var(--cream-card); border-bottom: 1px solid var(--rule); display: flex; align-items: center; justify-content: space-between; }
        .guide-title { font-family: var(--font-display); font-size: 1.4rem; font-weight: 700; color: var(--ink); display: flex; align-items: center; gap: 0.6rem; }
        .guide-body { padding: 1.75rem; font-size: 1.05rem; }
        .guide-body p { margin-bottom: 1rem; }
        .guide-body ul, .guide-body ol { margin-left: 1.5rem; margin-bottom: 1.25rem; }
        .guide-body li { margin-bottom: 0.5rem; }

        /* Step workflow */
        .step-box { display: flex; gap: 1rem; margin: 1.25rem 0; background: var(--cream); padding: 1.25rem; border-left: 4px solid var(--ink); }
        .step-num { width: 32px; height: 32px; background: var(--ink); color: #FFF; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-family: var(--font-display); font-weight: 700; flex-shrink: 0; }
        .step-content h4 { font-family: var(--font-display); font-size: 1.15rem; margin-bottom: 0.25rem; color: var(--ink); }
        .step-content p { margin: 0; font-size: 0.95rem; color: #333; }

        /* Alert / Callout boxes */
        .tip-box { background: #E8F5E9; border-left: 4px solid #2E7D32; padding: 1rem 1.25rem; margin: 1.25rem 0; color: #1B5E20; font-size: 0.95rem; }
        .warn-box { background: #FFEBEE; border-left: 4px solid var(--red); padding: 1rem 1.25rem; margin: 1.25rem 0; color: #B71C1C; font-size: 0.95rem; }
        .info-box { background: #E3F2FD; border-left: 4px solid #1565C0; padding: 1rem 1.25rem; margin: 1.25rem 0; color: #0D47A1; font-size: 0.95rem; }

        /* Tables inside guide */
        .tutorial-table { width: 100%; border-collapse: collapse; margin: 1rem 0 1.5rem; font-size: 0.95rem; }
        .tutorial-table th { background: var(--cream-dark); padding: 0.65rem 1rem; border: 1px solid var(--rule); font-family: var(--font-display); text-align: left; }
        .tutorial-table td { padding: 0.65rem 1rem; border: 1px solid var(--rule-light); }
        
        .badge { display: inline-block; padding: 0.2rem 0.55rem; font-size: 0.75rem; font-family: var(--font-display); font-weight: 700; text-transform: uppercase; border-radius: 2px; }
        .badge-green { background: #E8F5E9; color: #2E7D32; border: 1px solid #A5D6A7; }
        .badge-gold { background: #FFF8E1; color: #F57F17; border: 1px solid #FFE082; }
        .badge-red { background: #FFEBEE; color: #C62828; border: 1px solid #EF9A9A; }

        @media print {
            .admin-header, .tutorial-sidebar, .btn-link, .print-hide { display: none !important; }
            .tutorial-layout { display: block; }
            body { background: #FFF; color: #000; }
            .guide-section { border: 1px solid #CCC; page-break-inside: avoid; }
        }
    </style>
</head>
<body>

    <!-- ═══ TOP BAR ═══ -->
    <header class="admin-header">
        <div class="container admin-header-inner">
            <a href="admin.php" class="admin-brand">
                <div class="admin-brand-icon">♔</div>
                <div>
                    <div class="admin-brand-title">FOT Knights Arena</div>
                    <div class="admin-brand-subtitle">Administrator Handbook &amp; Tutorial</div>
                </div>
            </a>
            <nav class="admin-nav">
                <a href="admin.php" class="btn-link">← Return to Admin Portal</a>
                <a href="index.php" class="btn-link">Public Site ↗</a>
                <button type="button" onclick="window.print()" class="btn-link btn-primary print-hide" style="border: none; cursor: pointer;">
                    🖨️ Print Handbook
                </button>
            </nav>
        </div>
    </header>

    <div class="container">
        <!-- ═══ HERO ═══ -->
        <div class="tutorial-hero">
            <span class="tutorial-badge">Official Training Manual</span>
            <h1 class="tutorial-title">Administrator System Guide</h1>
            <p class="tutorial-lead">
                Comprehensive operational handbook for tournament organizers and chief arbiters. Learn how to verify squads, control public visibility, enforce drop boards, generate round-robin pairings, and enter match outcomes.
            </p>
        </div>

        <!-- ═══ TWO-COLUMN GUIDE LAYOUT ═══ -->
        <div class="tutorial-layout">
            
            <!-- Sidebar Navigation -->
            <aside class="tutorial-sidebar print-hide">
                <div class="sidebar-heading">Navigation Menu</div>
                <ul class="sidebar-menu">
                    <li class="sidebar-item"><a href="#lifecycle" class="sidebar-link">1. Tournament Lifecycle</a></li>
                    <li class="sidebar-item"><a href="#registration" class="sidebar-link">2. Registration &amp; Teams</a></li>
                    <li class="sidebar-item"><a href="#toggles" class="sidebar-link">3. Live Switches &amp; Clock</a></li>
                    <li class="sidebar-item"><a href="#arbiters" class="sidebar-link">4. Arbiters &amp; Officials</a></li>
                    <li class="sidebar-item"><a href="#rounds" class="sidebar-link">5. Rounds &amp; Drop Deadlines</a></li>
                    <li class="sidebar-item"><a href="#pairings" class="sidebar-link">6. Pairings &amp; 6×6 Matrix</a></li>
                    <li class="sidebar-item"><a href="#results" class="sidebar-link">7. Results &amp; Point Calc</a></li>
                    <li class="sidebar-item"><a href="#standings" class="sidebar-link">8. Standings &amp; Overrides</a></li>
                    <li class="sidebar-item"><a href="#printing" class="sidebar-link">9. Board Slips &amp; CSV Export</a></li>
                    <li class="sidebar-item"><a href="#security" class="sidebar-link">10. Admin Password &amp; Safety</a></li>
                </ul>
            </aside>

            <!-- Guide Content -->
            <main>

                <!-- SECTION 1 -->
                <section class="guide-section" id="lifecycle">
                    <div class="guide-header">
                        <h2 class="guide-title"><span>🚀</span> 1. Tournament Lifecycle &amp; Overview</h2>
                    </div>
                    <div class="guide-body">
                        <p>
                            FOT Knights Arena is designed specifically for collegiate rapid chess tournaments following FIDE rapid regulations and University of Ruhuna faculty constraints.
                        </p>
                        <div class="step-box">
                            <div class="step-num">A</div>
                            <div class="step-content">
                                <h4>Pre-Tournament Setup</h4>
                                <p>Appoint arbiters, review team registrations, verify eligibility constraints (gender and batch mix), and lock registration when quota is filled.</p>
                            </div>
                        </div>
                        <div class="step-box">
                            <div class="step-num">B</div>
                            <div class="step-content">
                                <h4>Round Preparation &amp; Lineups</h4>
                                <p>Configure drop deadlines for each round. Team captains select which reserve players sit out (enforcing exactly 4 active boards).</p>
                            </div>
                        </div>
                        <div class="step-box">
                            <div class="step-num">C</div>
                            <div class="step-content">
                                <h4>Pairing &amp; Live Match Play</h4>
                                <p>Generate pairings using the 6×6 cross-table matrix, print official board score sheets, and publish round matchups.</p>
                            </div>
                        </div>
                        <div class="step-box">
                            <div class="step-num">D</div>
                            <div class="step-content">
                                <h4>Result Adjudication &amp; Standings</h4>
                                <p>Record board outcomes (<code>1-0</code>, <code>0.5-0.5</code>, <code>0-1</code>). Game Points and Match Points recalculate instantly live.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SECTION 2 -->
                <section class="guide-section" id="registration">
                    <div class="guide-header">
                        <h2 class="guide-title"><span>📝</span> 2. Team Registration &amp; Approval Workflow</h2>
                    </div>
                    <div class="guide-body">
                        <p>
                            All squads register on the public homepage. To prevent unvetted entries from corrupting the tournament standings, all new teams are placed in <strong>Pending</strong> status by default.
                        </p>
                        
                        <h4>Eligibility Rules Verified by System:</h4>
                        <ul>
                            <li><strong>Squad Size:</strong> 4 to 6 registered players per team.</li>
                            <li><strong>Female Quota:</strong> Every squad <em>must include at least 1 female player</em>.</li>
                            <li><strong>Academic Batches:</strong> Squad must represent <em>at least two distinct student batch years</em>.</li>
                            <li><strong>Registration Number:</strong> Must match official faculty syntax: <code>TG/YYYY/NNNN</code>.</li>
                        </ul>

                        <div class="step-box">
                            <div class="step-num">1</div>
                            <div class="step-content">
                                <h4>Review Pending Squads</h4>
                                <p>Navigate to <strong>Section 2: Registered Teams &amp; Rosters</strong> on the Admin Dashboard. Filter by "Pending Only" to review new submissions.</p>
                            </div>
                        </div>
                        <div class="step-box">
                            <div class="step-num">2</div>
                            <div class="step-content">
                                <h4>Approve or Reject</h4>
                                <p>Click <span class="badge badge-green">✓ Approve</span> to immediately admit the team into the arena standings. Click <span class="badge badge-red">✕ Reject</span> if the team failed registration terms.</p>
                            </div>
                        </div>
                        <div class="step-box">
                            <div class="step-num">3</div>
                            <div class="step-content">
                                <h4>Closing Registration</h4>
                                <p>When the tournament roster is full (e.g. 6 teams), flip the <strong>Team Registration Status</strong> switch to <strong>Close Registration</strong>. The public website will immediately display the "Registration Closed" announcement.</p>
                            </div>
                        </div>

                        <div class="tip-box">
                            ⚡ <strong>Instant AJAX Approval:</strong> Clicking Approve or Reject updates the database instantly without refreshing the page or losing your scroll position.
                        </div>
                    </div>
                </section>

                <!-- SECTION 3 -->
                <section class="guide-section" id="toggles">
                    <div class="guide-header">
                        <h2 class="guide-title"><span>⏱️</span> 3. Instant Switches, Countdown &amp; Public Visibility</h2>
                    </div>
                    <div class="guide-body">
                        <p>
                            Organizers can control public visibility directly from <strong>Section 1: Scoreboard &amp; Standings Manager</strong>:
                        </p>
                        <table class="tutorial-table">
                            <thead>
                                <tr>
                                    <th>Switch Name</th>
                                    <th>Status Badges</th>
                                    <th>Homepage Effect</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Scoreboard Visibility</strong></td>
                                    <td><span class="badge badge-green">● LIVE</span> / <span class="badge badge-gold">○ HIDDEN</span></td>
                                    <td>Hides the public standings table before the tournament starts or during confidential round tallying.</td>
                                </tr>
                                <tr>
                                    <td><strong>Countdown Timer</strong></td>
                                    <td><span class="badge badge-green">● LIVE</span> / <span class="badge badge-gold">○ HIDDEN</span></td>
                                    <td>Displays real-time countdown ticker synchronized to venue local time (Asia/Colombo UTC+05:30).</td>
                                </tr>
                                <tr>
                                    <td><strong>Registration Status</strong></td>
                                    <td><span class="badge badge-green">● OPEN</span> / <span class="badge badge-red">🔒 CLOSED</span></td>
                                    <td>Disables public registration and renders the official "Registration Closed" hero notice with organizer contact details.</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="info-box">
                            💡 <strong>Smart Active Round Overrides:</strong> When an active round has an open drop deadline, the homepage countdown ticker automatically shifts from the tournament start time to the <em>round submission deadline</em>!
                        </div>
                    </div>
                </section>

                <!-- SECTION 4 -->
                <section class="guide-section" id="arbiters">
                    <div class="guide-header">
                        <h2 class="guide-title"><span>⚖️</span> 4. Appointing Arbiters &amp; Officials</h2>
                    </div>
                    <div class="guide-body">
                        <p>
                            FOT Knights Arena supports full multi-arbiter management. Arbiters oversee dispute resolution, board drops, and time violations.
                        </p>
                        <ol>
                            <li>Click the <strong>⚖️ Arbiters</strong> navigation shortcut in the admin header or scroll to <strong>Section 6</strong>.</li>
                            <li>Click <strong>+ Add Arbiter</strong> to reveal the registration card.</li>
                            <li>Enter the arbiter's <strong>Full Name</strong> (e.g. <em>IA / FA Sunimal Perera</em>), <strong>Role</strong> (<em>Chief Arbiter, Deputy Arbiter, Sector Arbiter</em>), optional <strong>FIDE ID</strong>, and official <strong>Contact Phone/Email</strong>.</li>
                            <li>Set the <strong>Display Priority Order</strong> (Priority 1 displays first, suitable for Chief Arbiter).</li>
                            <li>Click <strong>Save Arbiter</strong>. The panel publishes immediately on the public homepage alongside event details.</li>
                        </ol>
                        <div class="tip-box">
                            ✏️ <strong>Inline Editing:</strong> Click <strong>✏️ Edit</strong> next to any arbiter in the table to modify their title or contact info without leaving the page.
                        </div>
                    </div>
                </section>

                <!-- SECTION 5 -->
                <section class="guide-section" id="rounds">
                    <div class="guide-header">
                        <h2 class="guide-title"><span>🔄</span> 5. Rounds &amp; Drop Board Deadlines</h2>
                    </div>
                    <div class="guide-body">
                        <p>
                            In collegiate tournaments, teams have 4 to 6 players, but <strong>every match is played on exactly 4 boards</strong>. For 5-player or 6-player teams, 1 or 2 players must be designated as "Drop Boards" (reserves) for that round.
                        </p>
                        <div class="step-box">
                            <div class="step-num">1</div>
                            <div class="step-content">
                                <h4>Setting Round Deadlines</h4>
                                <p>In <strong>Section 3: Rounds Manager</strong>, create or edit Round numbers (1 to 5), labels (e.g. <em>Round 1 — Opening Clash</em>), and drop deadlines (e.g. <em>10 Oct 2026, 08:30 AM</em>).</p>
                            </div>
                        </div>
                        <div class="step-box">
                            <div class="step-num">2</div>
                            <div class="step-content">
                                <h4>Captain Self-Submission</h4>
                                <p>Captains log into the <strong>Captain Portal (`captain.php`)</strong> using their team name and password to select their drops before the deadline ticker reaches zero.</p>
                            </div>
                        </div>
                        <div class="step-box">
                            <div class="step-num">3</div>
                            <div class="step-content">
                                <h4>Automated System Fallback</h4>
                                <p>If a captain forgets to submit before the deadline, the system automatically designates the lowest-order reserve members as drops so the match can proceed.</p>
                            </div>
                        </div>
                        <div class="step-box">
                            <div class="step-num">4</div>
                            <div class="step-content">
                                <h4>Admin Drop Override</h4>
                                <p>Arbiters can manually override drop selections for any team at any time using the <strong>Admin Drop Override</strong> tool in Section 3.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SECTION 6 -->
                <section class="guide-section" id="pairings">
                    <div class="guide-header">
                        <h2 class="guide-title"><span>⚔️</span> 6. Pairings Generator &amp; 6×6 Cross-Table Matrix</h2>
                    </div>
                    <div class="guide-body">
                        <p>
                            Round-Robin tournaments require every team to play against all 5 opposing squads across 5 rounds without duplicate matchups.
                        </p>
                        <h4>Interactive 6×6 Cross-Table Matrix:</h4>
                        <p>
                            Located in <strong>Section 5: Pairings &amp; Results Manager</strong>, the visual matrix shows:
                        </p>
                        <ul>
                            <li><strong>Diagonal Cells:</strong> Grayed out (teams cannot play against themselves).</li>
                            <li><strong>Scheduled Match Cells:</strong> Displays round badges (e.g. <span class="badge" style="background:#E3F2FD; color:#1565C0;">R1 (Scheduled)</span> or <span class="badge badge-green">R2 (Completed)</span>).</li>
                            <li><strong>Unpaired Cells:</strong> Labeled with a dash (<code>—</code>), signaling available matchups.</li>
                        </ul>
                        <div class="step-box">
                            <div class="step-num">⚡</div>
                            <div class="step-content">
                                <h4>Creating a Pairing</h4>
                                <p>Select the <strong>Round Number</strong>, <strong>Team A (White)</strong>, and <strong>Team B (Black)</strong>, then click <strong>Schedule Match Pairing</strong>. The system validates that neither team is already scheduled in that round.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SECTION 7 -->
                <section class="guide-section" id="results">
                    <div class="guide-header">
                        <h2 class="guide-title"><span>♟️</span> 7. Board Results Entry &amp; Automatic Points Engine</h2>
                    </div>
                    <div class="guide-body">
                        <p>
                            When a match finishes, arbiters record board outcomes on each of the 4 active boards.
                        </p>
                        <ol>
                            <li>Locate the match in Section 5 and click <strong>✏️ Enter / Edit Results</strong>.</li>
                            <li>Verify player rosters for <strong>Board 1, Board 2, Board 3, and Board 4</strong> (the dropdowns automatically filter out dropped reserve members).</li>
                            <li>Select board results from the dropdowns:
                                <ul>
                                    <li><code>1-0</code> (White Win: Team A +1.0 GP, Team B +0.0 GP)</li>
                                    <li><code>0.5-0.5</code> (Draw: Team A +0.5 GP, Team B +0.5 GP)</li>
                                    <li><code>0-1</code> (Black Win: Team A +0.0 GP, Team B +1.0 GP)</li>
                                </ul>
                            </li>
                            <li>Change status to <strong>Completed</strong> and click <strong>Save Match Results</strong>.</li>
                        </ol>

                        <h4>Automatic Point Recalculation Engine:</h4>
                        <table class="tutorial-table">
                            <thead>
                                <tr>
                                    <th>Match Result</th>
                                    <th>Game Points (GP)</th>
                                    <th>Match Points (MP) Awarded</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Match Win</strong></td>
                                    <td>2.5 - 1.5, 3.0 - 1.0, 3.5 - 0.5, 4.0 - 0.0</td>
                                    <td><strong style="color:#1E6B23;">2 Match Points</strong></td>
                                </tr>
                                <tr>
                                    <td><strong>Match Draw</strong></td>
                                    <td>2.0 - 2.0</td>
                                    <td><strong style="color:#1565C0;">1 Match Point</strong> each</td>
                                </tr>
                                <tr>
                                    <td><strong>Match Loss (with boards won)</strong></td>
                                    <td>1.5 - 2.5, 1.0 - 3.0, 0.5 - 3.5</td>
                                    <td><strong>0 Match Points</strong></td>
                                </tr>
                                <tr style="background: #FFF0F0;">
                                    <td><strong>Match Loss (All 4 boards lost)</strong></td>
                                    <td>0.0 - 4.0 (Whitewash / Default)</td>
                                    <td><strong style="color:var(--red);">-1 Match Point Penalty</strong></td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="warn-box">
                            ⚠️ <strong>Configurable Match Point Rules:</strong> While the FOT Arena default awards 2 MP for Win, 1 MP for Draw, 0 MP for Loss, and -1 MP for a 0-GP loss, <strong>organizers can change this system at any time</strong>.
                        </div>

                        <h4>How to Change the Match Point Scoring System:</h4>
                        <ol>
                            <li>Click the <strong>⚙️ Match Points</strong> link in the admin header navigation or go to <strong>#matchpoints-manager</strong>.</li>
                            <li>Select a pre-configured scheme from the dropdown:
                                <ul>
                                    <li><strong>FOT Arena Penalty:</strong> Win = 2 MP, Draw = 1 MP, Loss = 0 MP, 0-GP Loss = -1 MP</li>
                                    <li><strong>Standard FIDE / Olympiad:</strong> Win = 2 MP, Draw = 1 MP, Loss = 0 MP, 0-GP Loss = 0 MP</li>
                                    <li><strong>3-Point Win Scheme:</strong> Win = 3 MP, Draw = 1 MP, Loss = 0 MP, 0-GP Loss = 0 MP</li>
                                    <li><strong>Custom:</strong> Enter any custom numeric integer values for Win, Draw, Normal Loss, and 0-GP Loss.</li>
                                </ul>
                            </li>
                            <li>Click <strong>💾 Apply Scoring System &amp; Recalculate</strong>.</li>
                            <li>The system will update the database settings and <strong>automatically recalculate the Match Points and Standings for all completed matches</strong> instantly!</li>
                        </ol>
                    </div>
                </section>

                <!-- SECTION 8 -->
                <section class="guide-section" id="standings">
                    <div class="guide-header">
                        <h2 class="guide-title"><span>📊</span> 8. Standings Overrides &amp; Arbiter Notes</h2>
                    </div>
                    <div class="guide-body">
                        <p>
                            Tournament standings are sorted by the official priority sequence:
                        </p>
                        <div style="background: var(--cream); border: 1px solid var(--rule); padding: 1rem 1.25rem; font-family: var(--font-display); font-size: 1.1rem; text-align: center; margin-bottom: 1.25rem;">
                            1. Match Points (MP) &nbsp;→&nbsp; 2. Game Points (GP) &nbsp;→&nbsp; 3. Sonneborn-Berger Tiebreak (SB)
                        </div>
                        <p>
                            If an arbiter needs to adjust points due to a committee dispute, penalty deduction, or unplayed bye:
                        </p>
                        <ul>
                            <li>Go to <strong>Section 1: Edit Match Scores &amp; Results</strong>.</li>
                            <li>Modify P, W, D, L, GP, or MP values directly in the editable fields.</li>
                            <li>Add arbiter notes in the <strong>Status / Arbiter Notes</strong> field (e.g. <em>"Round 1 Bye"</em> or <em>"+0.5 Arbiter Adjustment"</em>).</li>
                            <li>Click <strong>💾 Save &amp; Publish Standings</strong>.</li>
                        </ul>
                    </div>
                </section>

                <!-- SECTION 9 -->
                <section class="guide-section" id="printing">
                    <div class="guide-header">
                        <h2 class="guide-title"><span>🖨️</span> 9. Board Slips &amp; Excel CSV Export</h2>
                    </div>
                    <div class="guide-body">
                        <p>
                            Ensure smooth tournament day operations with built-in printing and data exports:
                        </p>
                        <div class="step-box">
                            <div class="step-num">📋</div>
                            <div class="step-content">
                                <h4>Printable Final 4-Board Lineups</h4>
                                <p>Scroll to <strong>Section 4: Printable Final Boards</strong>. Select the round and click <strong>🖨️ Print Round Lineups</strong>. The printer-friendly layout formats 4 playing boards, designated drop players, and arbiter signature lines.</p>
                            </div>
                        </div>
                        <div class="step-box">
                            <div class="step-num">📥</div>
                            <div class="step-content">
                                <h4>Export to Excel / CSV</h4>
                                <p>Click <strong>📥 Export CSV</strong> in the admin header. The system instantly downloads a UTF-8 BOM CSV containing all registered teams, contact numbers, and member rosters compatible with Microsoft Excel and Google Sheets.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SECTION 10 -->
                <section class="guide-section" id="security">
                    <div class="guide-header">
                        <h2 class="guide-title"><span>🔑</span> 10. Administrator Security &amp; Password Changes</h2>
                    </div>
                    <div class="guide-body">
                        <p>
                            Protect the integrity of the tournament system by changing the default administrator password (<code>admin123</code>):
                        </p>
                        <ol>
                            <li>Click <strong>🔑 Password</strong> in the top header or scroll to <strong>Section 7</strong>.</li>
                            <li>Enter your <strong>Current Password</strong>.</li>
                            <li>Enter a new password (minimum 6 characters) and re-enter to confirm.</li>
                            <li>Click <strong>Update Password</strong>. The password hash is securely stored with strong bcrypt hashing (`PASSWORD_DEFAULT`).</li>
                        </ol>
                        <div class="tip-box">
                            🔒 <strong>Session Security:</strong> Always click <strong>Logout</strong> from the top navigation bar when leaving public administration kiosks.
                        </div>
                    </div>
                </section>

                <!-- BOTTOM QUICK ACTIONS -->
                <div style="background: var(--cream-card); border: 2px solid var(--ink); padding: 2rem; text-align: center; margin-top: 2rem;">
                    <h3 style="font-family: var(--font-display); font-size: 1.5rem; margin-bottom: 0.5rem; color: var(--ink);">
                        Ready to Manage FOT Knights Arena?
                    </h3>
                    <p style="color: var(--ink-muted); margin-bottom: 1.5rem;">
                        Jump back into the admin dashboard to start officiating matches.
                    </p>
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <a href="admin.php" class="btn-primary btn-link" style="padding: 0.75rem 2rem; font-size: 1.05rem;">
                            ← Go to Admin Dashboard
                        </a>
                        <a href="index.php" class="btn-link" style="background: #FFF; border: 1px solid var(--rule); padding: 0.75rem 1.5rem; font-size: 1.05rem;">
                            View Public Site ↗
                        </a>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Smooth Scrolling for Guide Links -->
    <script>
    document.querySelectorAll('.sidebar-link').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            var targetId = this.getAttribute('href');
            var target = document.querySelector(targetId);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                document.querySelectorAll('.sidebar-link').forEach(function(l) { l.classList.remove('active'); });
                this.classList.add('active');
            }
        });
    });
    </script>
</body>
</html>
