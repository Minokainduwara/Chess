<?php
/**
 * Individual Player Standings Section (Top 10 with pagination)
 * FOT Knights Arena — Chess Tournament
 */
?>
    <div class="page-rail"><hr class="section-rule--light section-rule"></div>

    <!-- ═══ INDIVIDUAL STANDINGS ═══ -->
    <section class="standings page-rail" id="individual-standings" aria-labelledby="indiv-heading">
        <div style="display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 2rem;">
            <h2 class="standings-heading" id="indiv-heading" style="margin-bottom: 0;">
                <span aria-hidden="true">🎖️</span> Individual Player Standings
            </h2>
        </div>
        <div class="standings-table-wrap gtouch-swipe">
            <table class="standings-table" id="indivTable">
                <thead>
                    <tr>
                        <th style="width: 70px;">Rank</th>
                        <th>Player</th>
                        <th>Team</th>
                        <th style="text-align: center; width: 80px;">Score</th>
                    </tr>
                </thead>
                <tbody id="indivBody">
                    <!-- Populated by JavaScript -->
                </tbody>
            </table>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
            <button id="prevIndivBtn" class="btn-primary" style="padding: 5px 15px; border-radius: 4px; font-weight: bold; background: var(--ink); color: #fff; cursor: pointer; border: none;" disabled>&larr; Prev</button>
            <span id="indivPageInfo" style="font-weight: 600; color: var(--ink-light);">Page 1</span>
            <button id="nextIndivBtn" class="btn-primary" style="padding: 5px 15px; border-radius: 4px; font-weight: bold; background: var(--ink); color: #fff; cursor: pointer; border: none;">Next &rarr;</button>
        </div>
        <script>
            const indivData = <?php echo json_encode($individual_standings ?? []); ?>;
            let indivPage = 0;
            const indivPerPage = 10;
            
            function renderIndivTable() {
                const tbody = document.getElementById('indivBody');
                tbody.innerHTML = '';
                
                if (!indivData || indivData.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding: 2.5rem 1rem; color: var(--ink-light);"><span style="font-size: 1.5rem; display: block; margin-bottom: 0.5rem;">♟️</span>Official player rankings will update here.</td></tr>';
                    document.getElementById('prevIndivBtn').style.display = 'none';
                    document.getElementById('nextIndivBtn').style.display = 'none';
                    document.getElementById('indivPageInfo').style.display = 'none';
                    return;
                }
                
                const start = indivPage * indivPerPage;
                const end = Math.min(start + indivPerPage, indivData.length);
                
                for (let i = start; i < end; i++) {
                    const row = indivData[i];
                    const rank = i + 1;
                    let rankHtml = `<span class="rank-num">${rank}</span>`;
                    if (rank === 1) rankHtml = `<span class="rank-badge rank-gold">♔ 1</span>`;
                    else if (rank === 2) rankHtml = `<span class="rank-badge rank-silver">♕ 2</span>`;
                    else if (rank === 3) rankHtml = `<span class="rank-badge rank-bronze">♗ 3</span>`;
                    
                    const tr = document.createElement('tr');
                    if (rank <= 3) tr.className = `rank-podium rank-${rank} gtouch-tap`;
                    else tr.className = 'gtouch-tap';
                    
                    tr.innerHTML = `
                        <td>${rankHtml}</td>
                        <td style="font-weight: 600; font-size: 1.05rem;">${row.name}</td>
                        <td style="color: var(--ink-light); font-weight: 500;">${row.team_name}</td>
                        <td style="text-align: center; font-weight: 600; color: var(--ink);">${parseFloat(row.individual_score || 0).toFixed(1)}</td>
                    `;
                    tbody.appendChild(tr);
                }
                
                const prevBtn = document.getElementById('prevIndivBtn');
                const nextBtn = document.getElementById('nextIndivBtn');
                
                prevBtn.disabled = indivPage === 0;
                prevBtn.style.opacity = prevBtn.disabled ? '0.5' : '1';
                prevBtn.style.cursor = prevBtn.disabled ? 'not-allowed' : 'pointer';
                
                nextBtn.disabled = end >= indivData.length;
                nextBtn.style.opacity = nextBtn.disabled ? '0.5' : '1';
                nextBtn.style.cursor = nextBtn.disabled ? 'not-allowed' : 'pointer';
                
                document.getElementById('indivPageInfo').textContent = `Showing ${start + 1} - ${end} of ${indivData.length}`;
            }
            
            document.getElementById('prevIndivBtn').addEventListener('click', () => {
                if (indivPage > 0) { indivPage--; renderIndivTable(); }
            });
            document.getElementById('nextIndivBtn').addEventListener('click', () => {
                if ((indivPage + 1) * indivPerPage < indivData.length) { indivPage++; renderIndivTable(); }
            });
            
            renderIndivTable();
        </script>
    </section>
