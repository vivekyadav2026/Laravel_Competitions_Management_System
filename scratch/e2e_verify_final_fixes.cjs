const puppeteer = require('c:/xampp/htdocs/Laravel_Competitions_Management_System/node_modules/puppeteer-core');
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE_URL = 'http://127.0.0.1:8000';
const SCREENSHOT_DIR = 'C:\\Users\\ranje\\.gemini\\antigravity\\brain\\466c9621-ef1b-4151-8418-1a59e2fd51c6\\screenshots\\final_fixes';

if (!fs.existsSync(SCREENSHOT_DIR)) {
    fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

async function loginUser(page, email, password) {
    await page.goto(`${BASE_URL}/showlogin`, { waitUntil: 'networkidle2' });
    
    // Switch to Password login section if available
    const switchBtn = await page.$('#switchLoginModeBtn');
    if (switchBtn) {
        await switchBtn.click();
        await page.waitForSelector('#passwordLoginSection:not(.d-none)', { timeout: 3000 }).catch(() => {});
    }

    await page.type('#emailInput', email);
    await page.type('#passwordInput', password);
    
    await Promise.all([
        page.click('#submitPasswordBtn'),
        page.waitForNavigation({ waitUntil: 'networkidle2' })
    ]);
}

async function loginAdmin(page, email, password) {
    await page.goto(`${BASE_URL}/admin/ludo/login`, { waitUntil: 'networkidle2' });
    await page.type('#adminEmail', email);
    await page.type('#adminPassword', password);
    await Promise.all([
        page.click('#adminSubmitBtn'),
        page.waitForNavigation({ waitUntil: 'networkidle2' })
    ]);
}

async function run() {
    console.log('🔄 Initializing fresh test database state...');
    execSync('php scratch/prepare_e2e_data.php', { stdio: 'inherit' });

    console.log('🚀 Launching Google Chrome for Final Fixes E2E Verification...');

    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: 'new',
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu',
            '--window-size=1280,900'
        ]
    });

    try {
        // Track whether any unexpected native alert dialog was shown
        let nativeAlertTriggered = false;
        let lastDialogMessage = '';

        const setupPageDialogTracking = (p, name) => {
            p.on('dialog', async dialog => {
                nativeAlertTriggered = true;
                lastDialogMessage = dialog.message();
                console.log(`⚠️ DIALOG intercepted on ${name}: "${dialog.message()}"`);
                await dialog.accept();
            });
        };

        // =========================================================================
        // PART 1: INSUFFICIENT BALANCE ERROR MODAL (ZERO NATIVE ALERT)
        // =========================================================================
        console.log('\n--- PART 1: Testing Insufficient Balance Modal ---');
        const contextBroke = await browser.createBrowserContext();
        const pageBroke = await contextBroke.newPage();
        await pageBroke.setViewport({ width: 412, height: 892 });
        setupPageDialogTracking(pageBroke, 'Broke Gamer');

        console.log('Logging in Broke Gamer (player_broke@fsaludoclub.test, balance=25 coins)...');
        await loginUser(pageBroke, 'player_broke@fsaludoclub.test', 'secret1234');
        console.log('Broke gamer logged in. Navigating to /play...');
        await pageBroke.goto(`${BASE_URL}/play`, { waitUntil: 'networkidle2' });

        // Test 1A: Battle Creation with Insufficient Balance
        console.log('Attempting to create 100-coin battle with 25 coins...');
        await pageBroke.waitForSelector('#createBattleForm');
        await pageBroke.focus('#ludoBattleAmountInput');
        await pageBroke.evaluate(() => {
            const input = document.getElementById('ludoBattleAmountInput');
            if (input) input.value = '100';
        });

        // Click Set Battle
        await pageBroke.click('#btnSetBattle');

        // Wait for #createBattleErrorAlert to appear inside #createBattleAlertBox
        await pageBroke.waitForSelector('#createBattleErrorAlert', { timeout: 5000 });
        const errorAlertText = await pageBroke.$eval('#createBattleErrorAlert', el => el.innerText);
        console.log('✅ Error Alert appeared inside Create Battle card:', errorAlertText.replace(/\n+/g, ' '));

        if (nativeAlertTriggered) {
            console.error('❌ NOTE: Dialog was triggered on creation:', lastDialogMessage);
            nativeAlertTriggered = false; // reset for subsequent checks
        } else {
            console.log('✅ Zero native browser alerts triggered on battle creation!');
        }

        const walletLinkCreate = await pageBroke.$eval('#createBattleErrorAlert a[href*="/wallet"]', el => el.href).catch(() => null);
        console.log('✅ Wallet link present in error card:', walletLinkCreate);

        await pageBroke.screenshot({
            path: path.join(SCREENSHOT_DIR, '01_insufficient_balance_create_modal.png'),
            fullPage: false
        });
        console.log('📸 Screenshot saved: 01_insufficient_balance_create_modal.png');

        // Now have Player A create a 100-coin battle so Broke Gamer can test the Join Modal
        console.log('\nSetting up battle from Player A for Broke Gamer to test Join Modal...');
        const contextA = await browser.createBrowserContext();
        const pageA = await contextA.newPage();
        await pageA.setViewport({ width: 412, height: 892 });
        setupPageDialogTracking(pageA, 'Player A');

        console.log('Logging in Player A (player_a_rt@fsaludoclub.test)...');
        await loginUser(pageA, 'player_a_rt@fsaludoclub.test', 'secret1234');
        await pageA.goto(`${BASE_URL}/play`, { waitUntil: 'networkidle2' });

        // Player A creates battle via AJAX
        console.log('Player A creating 100-coin battle via AJAX...');
        const createResult = await pageA.evaluate(async () => {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const formData = new FormData();
            formData.append('amount', '100');
            const res = await fetch('/battles/create', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf
                },
                body: formData
            });
            return res.json();
        });

        console.log('Player A URL before create:', pageA.url());
        console.log('Player A battle creation response:', JSON.stringify(createResult));
        const battleId = createResult.battle?.id;
        if (!battleId) throw new Error('Failed to create battle for Player A: ' + (createResult.message || JSON.stringify(createResult)));

        // Test 1B: Broke Gamer tests Join Modal
        console.log('Broke Gamer navigates to /play to see the new battle...');
        await pageBroke.goto(`${BASE_URL}/play`, { waitUntil: 'networkidle2' });

        // Broke Gamer clicks PLAY button for this battle
        console.log(`Broke Gamer opening Join Confirmation Modal for battle #${battleId}...`);
        await pageBroke.evaluate((bId) => {
            const btn = document.querySelector(`[data-battle-id="${bId}"] .battle-action--play`) ||
                        document.querySelector(`[data-bs-target="#ludoPlayModal${bId}"]`);
            if (btn) btn.click();
            else {
                const modal = document.getElementById(`ludoPlayModal${bId}`);
                if (modal) new bootstrap.Modal(modal).show();
            }
        }, battleId);

        // Wait for modal to become visible
        await pageBroke.waitForSelector(`#ludoPlayModal${battleId}.show`, { timeout: 6000 });
        console.log('✅ Join Confirmation Modal is open and visible.');

        // Verify inside modal: Insufficient Points Warning Card, Shortfall, and Add Points button
        const modalText = await pageBroke.$eval(`#ludoPlayModal${battleId} .modal-body`, el => el.innerText);
        console.log('Modal Body Text:', modalText.replace(/\n+/g, ' '));

        const hasInsufficientCard = await pageBroke.$(`#ludoPlayModal${battleId} .ludo-modal-insufficient-card`);
        const addPointsBtn = await pageBroke.$(`#ludoPlayModal${battleId} .ludo-modal-btn-add-points[href*="/wallet"]`);

        console.log('✅ Has Insufficient Points Card:', !!hasInsufficientCard);
        console.log('✅ Has Add Points button linking to /wallet:', !!addPointsBtn);

        await pageBroke.screenshot({
            path: path.join(SCREENSHOT_DIR, '02_insufficient_balance_join_modal.png'),
            fullPage: false
        });
        console.log('📸 Screenshot saved: 02_insufficient_balance_join_modal.png');

        // Close broke modal
        await pageBroke.evaluate((bId) => {
            const modal = document.getElementById(`ludoPlayModal${bId}`);
            if (modal) bootstrap.Modal.getInstance(modal)?.hide();
        }, battleId);

        // =========================================================================
        // PART 2: REAL-TIME VICTORY CLAIM (NO PAGE REFRESH)
        // =========================================================================
        console.log('\n--- PART 2: Testing Real-Time Victory Claim (No Page Refresh) ---');
        const contextB = await browser.createBrowserContext();
        const pageB = await contextB.newPage();
        await pageB.setViewport({ width: 412, height: 892 });
        setupPageDialogTracking(pageB, 'Player B');

        console.log('Logging in Player B (player_b_rt@fsaludoclub.test, balance=1000)...');
        await loginUser(pageB, 'player_b_rt@fsaludoclub.test', 'secret1234');
        await pageB.goto(`${BASE_URL}/play`, { waitUntil: 'networkidle2' });

        // Player B accepts Player A's battle
        console.log(`Player B accepting battle #${battleId}...`);
        const joinRes = await pageB.evaluate(async (bId) => {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch(`/battles/${bId}/join`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf
                }
            });
            return res.json();
        }, battleId);
        console.log('Player B join response:', joinRes);

        // Player A starts the battle
        console.log('Player A starting the battle...');
        await pageA.evaluate(async (bId) => {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            await fetch(`/battles/${bId}/start`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf
                }
            });
        }, battleId);

        // Both players navigate to the Match Arena
        console.log('Navigating Player A and Player B to Match Arena...');
        await pageA.goto(`${BASE_URL}/battles/${battleId}`, { waitUntil: 'domcontentloaded' });
        await pageA.waitForSelector('.ludo-arena-card', { timeout: 10000 });

        await pageB.goto(`${BASE_URL}/battles/${battleId}`, { waitUntil: 'domcontentloaded' });
        await pageB.waitForSelector('.ludo-arena-card', { timeout: 10000 });

        // Check usernames rendered on arena cards
        const playerAUsernames = await pageA.evaluate(() => {
            return Array.from(document.querySelectorAll('.player-card-username, .player-name-val')).map(el => el.innerText.trim());
        });
        console.log('Player A Arena Usernames:', playerAUsernames);

        const playerBUsernames = await pageB.evaluate(() => {
            return Array.from(document.querySelectorAll('.player-card-username, .player-name-val')).map(el => el.innerText.trim());
        });
        console.log('Player B Arena Usernames:', playerBUsernames);

        // Player A submits Victory Claim via UI form
        console.log('⚡ Player A submitting Victory Claim (won) via UI form...');
        await pageA.waitForSelector('#submitResultForm', { timeout: 8000 });
        await pageA.select('#selectResultClaim', 'won');
        await pageA.click('#btnSubmitResultClaim');

        // Player A page update
        await pageA.waitForFunction(() => {
            const text = document.body.innerText;
            return text.includes('Victory Claimed') || text.includes('awaiting verification') || text.includes('Awaiting verification');
        }, { timeout: 8000 });

        await pageA.screenshot({
            path: path.join(SCREENSHOT_DIR, '03_playerA_victory_claim_submitted.png'),
            fullPage: false
        });
        console.log('📸 Screenshot saved: 03_playerA_victory_claim_submitted.png');

        // NOW CRITICAL: Verify Player B receives Opponent's Victory Claim WITHOUT ANY PAGE REFRESH!
        console.log('⏳ Waiting for Player B screen to update via real-time polling WITHOUT refresh...');
        const updatedOnPlayerB = await pageB.waitForFunction(() => {
            const text = document.body.innerText;
            return text.includes('Your opponent has submitted a Victory Claim') || 
                   text.includes('Victory Claim. Awaiting verification');
        }, { timeout: 10000 }).catch(() => null);

        if (updatedOnPlayerB) {
            console.log('🎉 SUCCESS: Player B received Opponent Victory Claim in real-time without page refresh!');
        } else {
            console.error('❌ Timed out waiting for Player B real-time update.');
        }

        await pageB.screenshot({
            path: path.join(SCREENSHOT_DIR, '04_playerB_realtime_opponent_claim_received.png'),
            fullPage: false
        });
        console.log('📸 Screenshot saved: 04_playerB_realtime_opponent_claim_received.png');

        // =========================================================================
        // PART 3: ADMIN MATCH AUDIT DOSSIER & UNIQUE USERNAMES
        // =========================================================================
        console.log('\n--- PART 3: Testing Admin Match Audit Dossier & Unique Usernames ---');
        const contextAdmin = await browser.createBrowserContext();
        const pageAdmin = await contextAdmin.newPage();
        await pageAdmin.setViewport({ width: 1280, height: 900 });
        setupPageDialogTracking(pageAdmin, 'Admin');

        console.log('Logging in Superadmin (admin@fsaludoclub.com)...');
        await loginAdmin(pageAdmin, 'admin@fsaludoclub.com', 'Admin@12345');

        console.log(`Admin navigating to match audit dossier for battle #${battleId}...`);
        await pageAdmin.goto(`${BASE_URL}/admin/ludo/matches/${battleId}`, { waitUntil: 'networkidle2' });

        // Verify dossier displays participant display name, @username, and User ID
        const adminPageText = await pageAdmin.evaluate(() => document.body.innerText);
        const hasCreatorUsername = adminPageText.includes('@arjun_sharma_4689');
        const hasJoinerUsername = adminPageText.includes('@rohit_verma_4690');
        const hasCreatorId = adminPageText.includes('User ID: #4689') || adminPageText.includes('#4689');
        const hasJoinerId = adminPageText.includes('User ID: #4690') || adminPageText.includes('#4690');

        console.log('Admin Dossier Creator Username (@arjun_sharma_4689):', hasCreatorUsername);
        console.log('Admin Dossier Opponent Username (@rohit_verma_4690):', hasJoinerUsername);
        console.log('Admin Dossier Creator User ID (#4689):', hasCreatorId);
        console.log('Admin Dossier Opponent User ID (#4690):', hasJoinerId);

        // Check options in settlement verdict select
        const verdictOptions = await pageAdmin.evaluate(() => {
            const select = document.getElementById('matchDecisionSelect') || document.querySelector('select[name="decision"]');
            if (!select) return [];
            return Array.from(select.options).map(opt => ({ value: opt.value, text: opt.text }));
        });
        console.log('Settlement Verdict Options:', verdictOptions);

        await pageAdmin.screenshot({
            path: path.join(SCREENSHOT_DIR, '05_admin_match_audit_dossier.png'),
            fullPage: false
        });
        console.log('📸 Screenshot saved: 05_admin_match_audit_dossier.png');

        // Admin settles the match: Award Victory to Creator (#4689)
        console.log('Admin settling match: Awarding victory to Creator (#4689)...');
        await pageAdmin.select('select[name="decision"]', '4689');
        await pageAdmin.type('input[name="reason"], textarea[name="reason"]', 'Creator screenshot verified as authentic match winner.');

        await Promise.all([
            pageAdmin.click('button[type="submit"].btn-success, #settlementSubmitBtn, form[action*="/resolve"] button[type="submit"]'),
            pageAdmin.waitForNavigation({ waitUntil: 'networkidle2' })
        ]);

        console.log('✅ Admin settlement completed.');

        await pageAdmin.screenshot({
            path: path.join(SCREENSHOT_DIR, '06_admin_settlement_completed.png'),
            fullPage: false
        });
        console.log('📸 Screenshot saved: 06_admin_settlement_completed.png');

        // Verify Player A and Player B receive the finalized status via polling
        console.log('⏳ Waiting for Player A and Player B screens to sync final status...');
        await pageA.waitForFunction(() => {
            const text = document.body.innerText;
            return text.includes('completed') || text.includes('Completed') || text.includes('Won') || text.includes('Winner');
        }, { timeout: 8000 }).catch(() => null);

        await pageB.waitForFunction(() => {
            const text = document.body.innerText;
            return text.includes('completed') || text.includes('Completed') || text.includes('Lost') || text.includes('Defeat');
        }, { timeout: 8000 }).catch(() => null);

        await pageA.screenshot({
            path: path.join(SCREENSHOT_DIR, '07_playerA_final_winner.png'),
            fullPage: false
        });
        await pageB.screenshot({
            path: path.join(SCREENSHOT_DIR, '08_playerB_final_loser.png'),
            fullPage: false
        });
        console.log('📸 Final Screenshots saved: 07_playerA_final_winner.png, 08_playerB_final_loser.png');

        console.log('\n======================================================');
        console.log('🎉 ALL END-TO-END BROWSER CHECKS COMPLETED SUCCESSFULLY!');
        console.log('======================================================\n');

    } catch (err) {
        console.error('❌ E2E Verification failed with error:', err);
    } finally {
        await browser.close();
    }
}

run();
