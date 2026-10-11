<?php
/**
 * Official Team Registration Section & Dynamic JS
 * FOT Knights Arena — Chess Tournament
 */
$registration_open = $registration_open ?? true;
$registration_closed_message = $registration_closed_message ?? 'Official team registration for FOT Knights Arena is now closed.';
?>
    <div class="page-rail"><hr class="section-rule--light section-rule"></div>

    <!-- ═══ REGISTRATION SECTION ═══ -->
    <section class="registration page-rail" id="register" aria-labelledby="reg-heading">
        <div style="display: flex; align-items: baseline; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem;">
            <h2 class="reg-heading" id="reg-heading" style="margin-bottom: 0;">
                <span aria-hidden="true">♔</span> Official Team Registration
            </h2>
            <?php if (!$registration_open): ?>
                <span class="badge" style="background: #FFEBEE; color: var(--red); border: 1px solid #EF9A9A; font-size: 0.85rem; padding: 0.35rem 0.75rem; font-weight: 700;">
                    🔒 REGISTRATION CLOSED
                </span>
            <?php else: ?>
                <span class="badge badge-green" style="font-size: 0.85rem; padding: 0.35rem 0.75rem; font-weight: 700;">
                    ● REGISTRATION OPEN
                </span>
            <?php endif; ?>
        </div>

        <?php if (!$registration_open): ?>
            <!-- CLOSED STATE HERO CARD -->
            <div style="background: #FFFFFF; border: 2px solid var(--rule); border-top: 5px solid var(--red); padding: 3rem 2rem; margin-top: 1.5rem; text-align: center; border-radius: 4px; box-shadow: 4px 4px 0 rgba(0,0,0,0.05);">
                <div style="font-size: 3.5rem; line-height: 1; margin-bottom: 1rem;">🔒</div>
                <h3 style="font-family: var(--font-display); font-size: 1.85rem; font-weight: 700; color: var(--ink); margin-bottom: 0.75rem;">
                    Registration is Currently Closed
                </h3>
                <p style="font-size: 1.15rem; color: var(--ink-muted); max-width: 620px; margin: 0 auto 1.75rem; line-height: 1.6;">
                    <?= htmlspecialchars($registration_closed_message) ?>
                </p>
                <div style="display: inline-flex; flex-wrap: wrap; gap: 1rem; justify-content: center; align-items: center;">
                    <a href="captain.php" class="btn-submit-reg" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; width: auto; padding: 0.75rem 1.75rem;">
                        <span>🛡️</span> Already Registered? Access Captain Portal
                    </a>
                    <a href="index.php#standings" class="btn-add-member" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; width: auto; padding: 0.75rem 1.75rem;">
                        <span>📊</span> View Tournament Standings
                    </a>
                </div>
                <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px dashed var(--rule); font-size: 0.9rem; color: var(--ink-muted);">
                    If your team needs special assistance or late roster adjustments, please contact the <a href="index.php#arbiters" style="color: var(--red); font-weight: 600;">Tournament Arbiters &amp; Officials</a>.
                </div>
            </div>
    </section>
        <?php return; endif; ?>
        <p class="reg-lead">
            Register your squad for FOT Knights Arena 2026. Each team requires a minimum of 4 and a maximum of 6 registered students. Per tournament rules, every team <strong>must include at least 1 female player</strong> and <strong>represent at least two batches</strong>. All registrations are reviewed by the tournament organizer — your team will appear in the public Arena Standings only after <strong>admin approval</strong>.
        </p>

        <!-- Live Tournament Rules Compliance Bar -->
        <div class="compliance-bar gtouch-swipe" id="complianceBar" aria-live="polite">
            <div class="compliance-title">📋 Live Eligibility Status</div>
            <div class="compliance-pill gtouch-hover" id="pill-members">
                <span class="pill-dot"></span>
                <span class="pill-text">Roster: 4 / 6 Members</span>
            </div>
            <div class="compliance-pill gtouch-hover" id="pill-females">
                <span class="pill-dot"></span>
                <span class="pill-text">Female Quota: 0 / 1 Girl (Required)</span>
            </div>
            <div class="compliance-pill gtouch-hover" id="pill-batches">
                <span class="pill-dot"></span>
                <span class="pill-text">Batches: 0 / 2 (Required)</span>
            </div>
            <div class="compliance-pill gtouch-hover" id="pill-format">
                <span class="pill-dot"></span>
                <span class="pill-text">Reg No Format: TG/YYYY/XXXX</span>
            </div>
        </div>

        <!-- Registration Form -->
        <form id="teamRegForm" novalidate>
            <!-- 1. Team & Contact Info -->
            <div class="form-block">
                <h3 class="form-block-title">
                    <span aria-hidden="true">♜</span> Team Identity & Contact
                </h3>
                <div class="form-grid-3">
                    <div class="input-group">
                        <label class="input-label" for="team_name">Team Name <span class="req">*</span></label>
                        <input type="text" id="team_name" name="team_name" class="input-field" placeholder="e.g. FOT Gambit Kings" required>
                        <span class="input-hint">Unique name representing your squad</span>
                    </div>
                    <div class="input-group">
                        <label class="input-label" for="contact_phone">Captain's Phone <span class="req">*</span></label>
                        <input type="tel" id="contact_phone" name="contact_phone" class="input-field" placeholder="e.g. 0771234567" required>
                        <span class="input-hint">For match pairings & captain briefings</span>
                    </div>
                    <div class="input-group">
                        <label class="input-label" for="captain_password">Captain Password <span class="req">*</span></label>
                        <input type="password" id="captain_password" name="captain_password" class="input-field" placeholder="Enter password" required>
                        <span class="input-hint">For logging into the Captain Portal</span>
                    </div>
                </div>
            </div>

            <!-- 2. Team Members Roster -->
            <div class="form-block">
                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.5rem;">
                    <h3 class="form-block-title" style="margin-bottom: 0;">
                        <span aria-hidden="true">♟</span> Squad Roster (Board 1 to 6)
                    </h3>
                    <span style="font-family: var(--font-display); font-size: 0.95rem; color: var(--ink-light);">
                        * Required: ≥ 1 Girl & ≥ 2 Batches
                    </span>
                </div>

                <div class="members-grid" id="membersGrid">
                    <!-- Cards will be initialized dynamically by JavaScript -->
                </div>

                <div class="form-actions-bar">
                    <button type="button" id="btnAddMember" class="btn-add-member gtouch-tap">
                        <span style="font-size: 1.25rem; line-height: 1;">+</span> Add Reserve Player (Max 6)
                    </button>
                    <button type="submit" id="btnSubmitReg" class="btn-submit-reg gtouch-ripple gtouch-long-press">
                        Submit Team Registration
                        <span aria-hidden="true">→</span>
                        <span style="font-weight: normal; opacity: 0.75; font-size: 0.9rem;">1.e4</span>
                    </button>
                </div>
            </div>

            <!-- Feedback & Receipt Box -->
            <div id="formFeedback" class="form-feedback" aria-live="polite"></div>
        </form>
    </section>

    <!-- Registration Dynamic Handler Script -->
    <script>
    (function() {
        var membersGrid = document.getElementById('membersGrid');
        var btnAddMember = document.getElementById('btnAddMember');
        var btnSubmitReg = document.getElementById('btnSubmitReg');
        var regForm = document.getElementById('teamRegForm');
        var formFeedback = document.getElementById('formFeedback');

        var pillMembers = document.getElementById('pill-members');
        var pillFemales = document.getElementById('pill-females');
        var pillBatches = document.getElementById('pill-batches');
        var pillFormat = document.getElementById('pill-format');

        var currentMemberCount = 4;
        var regPattern = /^TG\/(\d{4})\/(\d{4})$/i;

        function createMemberCardHtml(index) {
            var isCaptain = (index === 1);
            var title = isCaptain ? 'Player #1 — Board 1' : 'Player #' + index + ' — Board ' + index;
            var captainBadge = isCaptain ? '<span class="member-badge-captain">★ Captain</span>' : '';
            var removeBtn = (!isCaptain && index > 4) ? '<button type="button" class="btn-remove-member" onclick="window.removeMember(' + index + ')">✕ Remove</button>' : '';

            return `
            <div class="member-card ${isCaptain ? 'is-captain' : ''}" id="memberCard_${index}">
                <div class="member-card-header">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span class="member-slot-title">${title}</span>
                        ${captainBadge}
                    </div>
                    ${removeBtn}
                </div>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div class="input-group">
                        <label class="input-label" for="member_name_${index}">Full Name <span class="req">*</span></label>
                        <input type="text" id="member_name_${index}" name="members[${index - 1}][name]" class="input-field member-input-name" placeholder="e.g. Kasun Perera" required>
                    </div>
                    <div class="input-group">
                        <label class="input-label" for="member_reg_${index}">
                            Registration Number <span class="req">*</span>
                            <span class="batch-preview" id="batchPreview_${index}" style="display: none;"></span>
                        </label>
                        <input type="text" id="member_reg_${index}" name="members[${index - 1}][reg_number]" class="input-field member-input-reg" placeholder="TG/2023/0001" required maxlength="12" style="text-transform: uppercase;">
                        <span class="input-hint">Format: TG/YYYY/XXXX (e.g. TG/2022/1111 or TG/2023/0001)</span>
                    </div>
                    <div class="input-group">
                        <label class="input-label">Gender <span class="req">*</span></label>
                        <div class="gender-options">
                            <label class="gender-label">
                                <input type="radio" name="members[${index - 1}][gender]" value="Male" class="member-gender" required> ♂ Male
                            </label>
                            <label class="gender-label">
                                <input type="radio" name="members[${index - 1}][gender]" value="Female" class="member-gender" required> ♀ Female (Girl)
                            </label>
                        </div>
                    </div>
                </div>
            </div>`;
        }

        function renderInitialMembers() {
            var html = '';
            for (var i = 1; i <= currentMemberCount; i++) {
                html += createMemberCardHtml(i);
            }
            membersGrid.innerHTML = html;
            bindInputEvents();
            updateLiveChecklist();
        }

        btnAddMember.addEventListener('click', function() {
            if (currentMemberCount < 6) {
                currentMemberCount++;
                var temp = document.createElement('div');
                temp.innerHTML = createMemberCardHtml(currentMemberCount);
                membersGrid.appendChild(temp.firstElementChild);
                bindInputEvents();
                updateLiveChecklist();

                if (currentMemberCount >= 6) {
                    btnAddMember.disabled = true;
                    btnAddMember.innerText = 'Maximum 6 Members Reached';
                }
            }
        });

        window.removeMember = function(index) {
            var card = document.getElementById('memberCard_' + index);
            if (card) {
                card.remove();
                currentMemberCount--;
                reIndexMemberCards();
                btnAddMember.disabled = false;
                btnAddMember.innerHTML = '<span style="font-size: 1.25rem; line-height: 1;">+</span> Add Reserve Player (Max 6)';
                updateLiveChecklist();
            }
        };

        function reIndexMemberCards() {
            var cards = membersGrid.querySelectorAll('.member-card');
            cards.forEach(function(card, idx) {
                var num = idx + 1;
                card.id = 'memberCard_' + num;
                var isCap = (num === 1);
                var titleEl = card.querySelector('.member-slot-title');
                if (titleEl) {
                    titleEl.innerText = isCap ? 'Player #1 — Board 1' : 'Player #' + num + ' — Board ' + num;
                }
                var removeBtn = card.querySelector('.btn-remove-member');
                if (removeBtn) {
                    removeBtn.setAttribute('onclick', 'window.removeMember(' + num + ')');
                }
                var nameInput = card.querySelector('.member-input-name');
                if (nameInput) {
                    nameInput.name = `members[${idx}][name]`;
                    nameInput.id = `member_name_${num}`;
                }
                var regInput = card.querySelector('.member-input-reg');
                if (regInput) {
                    regInput.name = `members[${idx}][reg_number]`;
                    regInput.id = `member_reg_${num}`;
                }
                var genderRadios = card.querySelectorAll('.member-gender');
                genderRadios.forEach(function(radio) {
                    radio.name = `members[${idx}][gender]`;
                });
            });
        }

        function bindInputEvents() {
            membersGrid.querySelectorAll('.member-input-reg').forEach(function(input) {
                input.removeEventListener('input', onRegInput);
                input.addEventListener('input', onRegInput);
            });
            membersGrid.querySelectorAll('.member-gender').forEach(function(input) {
                input.removeEventListener('change', updateLiveChecklist);
                input.addEventListener('change', updateLiveChecklist);
            });
        }

        function onRegInput(e) {
            e.target.value = e.target.value.toUpperCase();
            var val = e.target.value.trim();
            var card = e.target.closest('.member-card');
            var preview = card.querySelector('.batch-preview');

            var match = val.match(regPattern);
            if (match && preview) {
                preview.style.display = 'inline-block';
                preview.innerText = 'Batch ' + match[1];
            } else if (preview) {
                preview.style.display = 'none';
            }
            updateLiveChecklist();
        }

        function updateLiveChecklist() {
            var cards = membersGrid.querySelectorAll('.member-card');
            var totalCount = cards.length;

            if (totalCount >= 4 && totalCount <= 6) {
                setPillStatus(pillMembers, true, `✓ Roster: ${totalCount} / 6 Members (Valid)`);
            } else {
                setPillStatus(pillMembers, false, `Roster: ${totalCount} / 6 (Min 4, Max 6)`);
            }

            var femaleRadios = membersGrid.querySelectorAll('.member-gender[value="Female"]:checked');
            var femaleCount = femaleRadios.length;
            if (femaleCount >= 1) {
                setPillStatus(pillFemales, true, `✓ Female Quota: ${femaleCount} Girl${femaleCount > 1 ? 's' : ''} (Met)`);
            } else {
                setPillStatus(pillFemales, false, `Female Quota: 0 / 1 Girl (Need 1 more)`);
            }

            var batches = [];
            var invalidFormatCount = 0;
            var regInputs = membersGrid.querySelectorAll('.member-input-reg');

            regInputs.forEach(function(input) {
                var val = input.value.trim();
                if (val !== '') {
                    var match = val.match(regPattern);
                    if (match) {
                        batches.push(match[1]);
                    } else {
                        invalidFormatCount++;
                    }
                }
            });

            var uniqueBatches = Array.from(new Set(batches));
            if (uniqueBatches.length >= 2) {
                setPillStatus(pillBatches, true, `✓ Batches: ${uniqueBatches.join(', ')} (${uniqueBatches.length} Batches)`);
            } else if (uniqueBatches.length === 1) {
                setPillStatus(pillBatches, false, `Batches: ${uniqueBatches[0]} only (Need ≥ 2 Batches)`);
            } else {
                setPillStatus(pillBatches, false, `Batches: None detected (Need ≥ 2 Batches)`);
            }

            if (invalidFormatCount === 0 && regInputs.length > 0) {
                setPillStatus(pillFormat, true, `✓ Format: TG/YYYY/XXXX`);
            } else {
                setPillStatus(pillFormat, false, `Format: TG/YYYY/XXXX (${invalidFormatCount} invalid)`);
            }
        }

        function setPillStatus(pill, isValid, text) {
            if (!pill) return;
            pill.className = 'compliance-pill ' + (isValid ? 'valid' : 'invalid');
            var textEl = pill.querySelector('.pill-text');
            if (textEl) textEl.innerText = text;
        }

        renderInitialMembers();

        regForm.addEventListener('submit', function(e) {
            e.preventDefault();

            var cards = membersGrid.querySelectorAll('.member-card');
            var totalCount = cards.length;
            var females = membersGrid.querySelectorAll('.member-gender[value="Female"]:checked').length;
            var batches = [];
            var regNumbers = [];
            var hasDuplicateReg = false;

            cards.forEach(function(card) {
                var regVal = card.querySelector('.member-input-reg').value.trim().toUpperCase();
                var match = regVal.match(regPattern);
                if (match) {
                    batches.push(match[1]);
                }
                if (regVal !== '') {
                    if (regNumbers.includes(regVal)) {
                        hasDuplicateReg = true;
                    }
                    regNumbers.push(regVal);
                }
            });

            var uniqueBatches = Array.from(new Set(batches));
            var clientErrors = [];

            var teamName = document.getElementById('team_name').value.trim();
            if (!teamName) clientErrors.push('Please enter a team name.');

            var contactPhone = document.getElementById('contact_phone').value.trim();
            if (!contactPhone) clientErrors.push("Please enter the team captain's contact phone number.");

            var captainPassword = document.getElementById('captain_password').value.trim();
            if (!captainPassword) clientErrors.push("Please enter a captain password.");

            if (totalCount < 4 || totalCount > 6) {
                clientErrors.push('Every team must have between 4 and 6 members.');
            }
            if (females < 1) {
                clientErrors.push('Tournament rule requirement: Team must have at least 1 female player (girl).');
            }
            if (uniqueBatches.length < 2) {
                clientErrors.push('Tournament rule requirement: Team must represent at least two distinct batches.');
            }
            if (hasDuplicateReg) {
                clientErrors.push('Duplicate registration numbers detected in your team roster.');
            }

            if (clientErrors.length > 0) {
                formFeedback.className = 'form-feedback is-visible';
                formFeedback.innerHTML = `
                    <div style="background: var(--accent-tint); border-left: 4px solid var(--red); padding: 1.25rem 1.5rem; color: var(--red);">
                        <strong style="font-family: var(--font-display); font-size: 1.15rem; display: block; margin-bottom: 0.5rem;">Registration Requirements Not Met:</strong>
                        <ul style="padding-left: 1.5rem; line-height: 1.6;">
                            ${clientErrors.map(e => `<li>${e}</li>`).join('')}
                        </ul>
                    </div>`;
                formFeedback.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            btnSubmitReg.disabled = true;
            btnSubmitReg.innerHTML = 'Verifying & Registering... ⏳';
            formFeedback.className = 'form-feedback is-visible';
            formFeedback.innerHTML = `
                <div style="background: var(--cream-dark); border: 1px solid var(--rule); padding: 1rem 1.5rem; color: var(--ink);">
                    Transmitting team registration dossier to tournament database...
                </div>`;

            var formData = new FormData(regForm);

            fetch('api_register.php', {
                method: 'POST',
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                btnSubmitReg.disabled = false;
                btnSubmitReg.innerHTML = 'Submit Team Registration <span aria-hidden="true">→</span> <span style="font-weight: normal; opacity: 0.75; font-size: 0.9rem;">1.e4</span>';

                if (data.success) {
                    formFeedback.innerHTML = `
                        <div class="receipt-card">
                            <div style="display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid var(--ink); padding-bottom: 0.75rem;">
                                <div>
                                    <h3 class="receipt-title">♔ Team Registration Submitted!</h3>
                                    <p style="color: var(--ink); font-size: 1.05rem;">
                                        <strong>Team: ${data.data.team_name}</strong> (Squad ID: #${data.data.team_id})
                                    </p>
                                </div>
                                <span style="background: var(--ink); color: var(--cream); padding: 0.25rem 0.75rem; font-family: var(--font-display); font-weight: 700; text-transform: uppercase; font-size: 0.85rem;">
                                    Pending Admin Approval
                                </span>
                            </div>
                            <div style="margin: 1.25rem 0; font-size: 1rem; line-height: 1.7; color: var(--ink);">
                                <p><strong>Tournament Verification:</strong></p>
                                <ul class="receipt-list">
                                    <li>✓ <strong>Total Players:</strong> ${data.data.member_count} registered</li>
                                    <li>✓ <strong>Female Quota:</strong> ${data.data.female_count} girl${data.data.female_count == 1 ? '' : 's'} included</li>
                                    <li>✓ <strong>Batches Represented:</strong> ${data.data.batches.join(', ')}</li>
                                    <li>⏳ <strong>Status:</strong> Pending admin approval - the team appears in the Arena Standings once approved</li>
                                </ul>
                                <p style="font-size: 0.95rem; color: var(--ink-light); margin-top: 0.5rem;">
                                    Your entry is awaiting organizer approval. Your captain will receive round pairing alerts before Round 1. You may present your student ID at the registration desk on tournament day.
                                </p>
                            </div>
                            <div style="display: flex; gap: 1rem; margin-top: 1.25rem;">
                                <button type="button" onclick="window.print()" class="btn-submit-reg" style="background: var(--ink); padding: 0.6rem 1.5rem; font-size: 1rem;">
                                    🖨️ Print Confirmation Slip
                                </button>
                                <button type="button" onclick="location.reload()" class="btn-add-member" style="padding: 0.6rem 1.5rem; font-size: 1rem;">
                                    Register Another Team
                                </button>
                            </div>
                        </div>`;
                    regForm.reset();
                    renderInitialMembers();
                    formFeedback.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    var errList = Array.isArray(data.errors) ? data.errors : (data.errors ? Object.values(data.errors) : []);
                    var errorsMarkup = errList.length > 0 ? `
                        <ul style="padding-left: 1.5rem; margin-top: 0.5rem;">
                            ${errList.map(err => `<li>${err}</li>`).join('')}
                        </ul>` : '';

                    formFeedback.innerHTML = `
                        <div style="background: var(--accent-tint); border-left: 4px solid var(--red); padding: 1.25rem 1.5rem; color: var(--red);">
                            <strong style="font-family: var(--font-display); font-size: 1.15rem; display: block;">Registration Error:</strong>
                            <p style="margin-top: 0.25rem;">${data.message || 'Validation failed.'}</p>
                            ${errorsMarkup}
                        </div>`;
                    formFeedback.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            })
            .catch(function(err) {
                btnSubmitReg.disabled = false;
                btnSubmitReg.innerHTML = 'Submit Team Registration <span aria-hidden="true">→</span> <span style="font-weight: normal; opacity: 0.75; font-size: 0.9rem;">1.e4</span>';
                formFeedback.innerHTML = `
                    <div style="background: var(--accent-tint); border-left: 4px solid var(--red); padding: 1.25rem 1.5rem; color: var(--red);">
                        <strong style="font-family: var(--font-display); font-size: 1.15rem; display: block;">Connection Error:</strong>
                        <p style="margin-top: 0.25rem;">Unable to contact the registration server. Please check MySQL database service.</p>
                    </div>`;
            });
        });
    })();
    </script>
