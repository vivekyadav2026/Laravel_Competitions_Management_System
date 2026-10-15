import puppeteer from 'puppeteer-core';
import path from 'path';
import { execSync } from 'child_process';

const ARTIFACT_DIR = 'C:\\Users\\ranje\\.gemini\\antigravity\\brain\\466c9621-ef1b-4151-8418-1a59e2fd51c6\\screenshots\\realtime_sync';

async function main() {
    console.log('======================================================================');
    console.log('  FSALUDOCLUB — WIN/LOSS REAL-TIME SYNCHRONIZATION E2E VERIFICATION  ');
    console.log('======================================================================\n');

    // 1. Seed Fixtures
    console.log('=== STEP 1: Seeding Test Fixtures & Isolated Test Accounts ===');
    const seedOutput = execSync('php scratch/seed_test_realtime.php').toString();
    console.log('Seed output:', seedOutput.trim());
    const fixture = JSON.parse(seedOutput.trim());

    const browser = await puppeteer.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--autoplay-policy=no-user-gesture-required']
    });

    try {
        // ---------------------------------------------------------------------
        // STEP 2: Open Admin Panel in Isolated Context 1
        // ---------------------------------------------------------------------
        console.log('\n=== STEP 2: Opening Admin Panel at /admin/ludo/matches ===');
        const adminContext = await browser.createBrowserContext();
        const pageAdmin = await adminContext.newPage();
        await pageAdmin.evaluateOnNewDocument(() => {
            Object.defineProperty(document, 'hidden', { value: false, configurable: true });
        });
        pageAdmin.on('console', msg => console.log('[Admin Console]', msg.text()));
        pageAdmin.on('pageerror', err => console.log('[Admin Page Error]', err));
        pageAdmin.on('dialog', async dialog => {
            console.log('[Admin Dialog]', dialog.type(), dialog.message());
            await dialog.accept();
        });
        await pageAdmin.setViewport({ width: 1280, height: 800 });
        await pageAdmin.goto(`http://127.0.0.1:8000/dev/login-as/${fixture.admin_id}`, { waitUntil: 'networkidle0' });
        await pageAdmin.goto('http://127.0.0.1:8000/admin/ludo/matches', { waitUntil: 'networkidle0' });
        console.log('Admin live desk opened successfully at /admin/ludo/matches.');

        // ---------------------------------------------------------------------
        // STEP 3: Isolated Player A (Context 2) Creates Battle
        // ---------------------------------------------------------------------
        console.log('\n=== STEP 3: Player A Creates Test Battle (100 Coins Stake) ===');
        const playerAContext = await browser.createBrowserContext();
        const pagePlayerA = await playerAContext.newPage();
        await pagePlayerA.evaluateOnNewDocument(() => {
            Object.defineProperty(document, 'hidden', { value: false, configurable: true });
        });
        pagePlayerA.on('console', msg => console.log('[Player A Console]', msg.text()));
        pagePlayerA.on('pageerror', err => console.log('[Player A Error]', err));
        pagePlayerA.on('dialog', async dialog => {
            console.log('[Player A Dialog]', dialog.type(), dialog.message());
            await dialog.accept();
        });
        await pagePlayerA.setViewport({ width: 420, height: 880, isMobile: true, hasTouch: true });
        await pagePlayerA.goto(`http://127.0.0.1:8000/dev/login-as/${fixture.player_a_id}`, { waitUntil: 'networkidle0' });
        await pagePlayerA.goto('http://127.0.0.1:8000/battles', { waitUntil: 'networkidle0' });

        const initialCoinsA = await pagePlayerA.$eval('.user-coins-display', el => parseInt(el.textContent.replace(/[^\d]/g, ''), 10));
        console.log(`Player A initial balance: 🪙 ${initialCoinsA}`);

        // Set 100 coins and submit battle creation
        await pagePlayerA.$eval('#ludoBattleAmountInput', el => el.value = '100');
        await pagePlayerA.click('#btnSetBattle');
        await pagePlayerA.waitForFunction(() => {
            const card = document.querySelector('#open-battles-list .ludo-battle-card');
            const coinsEl = document.querySelector('.user-coins-display');
            const coins = coinsEl ? parseInt(coinsEl.textContent.replace(/[^\d]/g, ''), 10) : 0;
            return card !== null || coins === 1400;
        }, { timeout: 10000 });

        // Get created battle from DB
        const createdBattleJson = execSync(`php -r "require 'vendor/autoload.php'; \$app = require_once 'bootstrap/app.php'; \$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); \$b = App\\Models\\LudoBattle::where('creator_id', ${fixture.player_a_id})->where('status', 'open')->latest()->first(); echo json_encode(['id' => \$b->id, 'battle_uid' => \$b->battle_uid]);"`).toString();
        const createdBattle = JSON.parse(createdBattleJson.trim());
        const battleId = createdBattle.id;
        const battleUid = createdBattle.battle_uid;
        console.log(`Battle created: ID ${battleId}, UID ${battleUid}`);

        const coinsAfterCreateA = await pagePlayerA.$eval('.user-coins-display', el => parseInt(el.textContent.replace(/[^\d]/g, ''), 10));
        console.log(`Player A balance after stake deduction: 🪙 ${coinsAfterCreateA} (Deducted: 🪙 ${initialCoinsA - coinsAfterCreateA})`);

        // ---------------------------------------------------------------------
        // STEP 4: Isolated Player B (Context 3) Joins & Match Starts
        // ---------------------------------------------------------------------
        console.log('\n=== STEP 4: Player B Joins Battle & Match Starts ===');
        const playerBContext = await browser.createBrowserContext();
        const pagePlayerB = await playerBContext.newPage();
        await pagePlayerB.evaluateOnNewDocument(() => {
            Object.defineProperty(document, 'hidden', { value: false, configurable: true });
        });
        pagePlayerB.on('console', msg => console.log('[Player B Console]', msg.text()));
        pagePlayerB.on('pageerror', err => console.log('[Player B Error]', err));
        pagePlayerB.on('dialog', async dialog => {
            console.log('[Player B Dialog]', dialog.type(), dialog.message());
            await dialog.accept();
        });
        await pagePlayerB.setViewport({ width: 420, height: 880, isMobile: true, hasTouch: true });
        await pagePlayerB.goto(`http://127.0.0.1:8000/dev/login-as/${fixture.player_b_id}`, { waitUntil: 'networkidle0' });
        await pagePlayerB.goto('http://127.0.0.1:8000/battles', { waitUntil: 'networkidle0' });

        const initialCoinsB = await pagePlayerB.$eval('.user-coins-display', el => parseInt(el.textContent.replace(/[^\d]/g, ''), 10));
        console.log(`Player B initial balance: 🪙 ${initialCoinsB}`);

        // Player B joins the battle via join POST
        console.log(`Player B joining battle ${battleId}...`);
        await pagePlayerB.evaluate(async (bId) => {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch(`/battles/${bId}/join`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            return res.json();
        }, battleId);

        // Player A starts the battle
        console.log('Player A starting the battle...');
        await pagePlayerA.bringToFront();
        await pagePlayerA.evaluate(async (bId) => {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            await fetch(`/battles/${bId}/start`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
        }, battleId);

        // Navigate both players to Match Arena
        await pagePlayerA.goto(`http://127.0.0.1:8000/battles/${battleId}`, { waitUntil: 'networkidle0' });
        await pagePlayerB.goto(`http://127.0.0.1:8000/battles/${battleId}`, { waitUntil: 'networkidle0' });
        console.log('Both players navigated to Match Arena.');

        // Player A sets room code
        const testRoomCode = '76543210';
        console.log(`Player A setting room code: ${testRoomCode}...`);
        await pagePlayerA.bringToFront();
        await pagePlayerA.type('#inputRoomCode', testRoomCode);
        await pagePlayerA.click('#btnSubmitRoomCode');

        // Player A verifies room code displayed on their screen
        await pagePlayerA.waitForSelector('#roomCodeText', { timeout: 8000 });
        console.log('Player A screen rendered room code.');

        // Switch to Player B
        console.log('Watching Player B arena for real-time room code receipt without reload...');
        await pagePlayerB.bringToFront();
        await pagePlayerB.waitForFunction(expected => {
            const el = document.getElementById('roomCodeText');
            return el && el.textContent.trim() === expected;
        }, { timeout: 15000 }, testRoomCode);
        console.log('SUCCESS: Player B received room code dynamically without reloading!');

        // ---------------------------------------------------------------------
        // STEP 5: Player A Submits "WON" Result Claim
        // ---------------------------------------------------------------------
        console.log('\n=== STEP 5: Player A Submits "WON" Claim (Zero-Reload) ===');
        await pagePlayerA.bringToFront();
        await pagePlayerA.select('#selectResultClaim', 'won');
        await pagePlayerA.click('#btnSubmitResultClaim');

        // Verify Player A arena switches to submitted card WITHOUT page refresh
        await pagePlayerA.waitForSelector('#myClaimSubmittedCard', { timeout: 8000 });
        const claimTextA = await pagePlayerA.$eval('#myClaimDisplay', el => el.textContent.trim());
        console.log(`Player A screen rendered claim: "${claimTextA}"`);

        await new Promise(r => setTimeout(r, 600));
        await pagePlayerA.screenshot({ path: path.join(ARTIFACT_DIR, '05_playerA_claimed_won.png') });
        console.log('Saved screenshot: 05_playerA_claimed_won.png');

        // Check Player B screen WITHOUT refreshing: Notice of opponent claim appears
        console.log('Checking Player B screen for real-time notice of Player A claim without reload...');
        await pagePlayerB.bringToFront();
        await pagePlayerB.waitForFunction(() => {
            const notice = document.getElementById('opponentPreClaimNotice');
            return notice && notice.innerText.toUpperCase().includes('WON');
        }, { timeout: 12000 });
        console.log('SUCCESS: Player B screen detected Player A "WON" claim in real time without refreshing!');

        // ---------------------------------------------------------------------
        // STEP 6: Player B Submits Conflicting "WON" Claim -> Dispute Triggered
        // ---------------------------------------------------------------------
        console.log('\n=== STEP 6: Player B Submits Conflicting "WON" Claim (Dispute Triggered) ===');
        await pagePlayerB.select('#selectResultClaim', 'won');
        await pagePlayerB.click('#btnSubmitResultClaim');

        // Verify Player B arena shows dispute review alert box
        await pagePlayerB.waitForFunction(() => {
            const box = document.getElementById('disputeReviewAlertBox');
            return box && box.innerText.includes('administrative review / dispute');
        }, { timeout: 8000 });
        console.log('SUCCESS: Player B arena displays Dispute / Payout Frozen alert banner!');

        await new Promise(r => setTimeout(r, 600));
        await pagePlayerB.screenshot({ path: path.join(ARTIFACT_DIR, '06_playerB_dispute_triggered.png') });
        console.log('Saved screenshot: 06_playerB_dispute_triggered.png');

        // Check Player A screen WITHOUT reloading: Player A should also receive the dispute alert
        console.log('Checking Player A arena for real-time dispute banner receipt without reload...');
        await pagePlayerA.bringToFront();
        await pagePlayerA.waitForFunction(() => {
            const box = document.getElementById('disputeReviewAlertBox');
            return box && box.innerText.includes('administrative review / dispute');
        }, { timeout: 12000 });
        console.log('SUCCESS: Player A arena synchronized dispute state in real-time without reloading!');

        // ---------------------------------------------------------------------
        // STEP 7: Verify Admin Receives Alert and Row WITHOUT Refreshing
        // ---------------------------------------------------------------------
        console.log('\n=== STEP 7: Checking Admin Desk at /admin/ludo/matches (Zero-Reload) ===');
        await pageAdmin.bringToFront();

        // Wait for admin matches table to dynamically reflect the dispute row
        await pageAdmin.waitForFunction((uid) => {
            const row = document.getElementById('matchRow' + uid) || Array.from(document.querySelectorAll('#adminMatchesTableBody tr')).find(r => r.innerText.includes(uid));
            if (!row) return false;
            return row.innerText.includes('DISPUTED');
        }, { timeout: 15000 }, battleUid);
        console.log(`SUCCESS: Admin matches desk dynamically updated row for ${battleUid} with DISPUTED status without reloading!`);

        // Check badge counts and no duplicate rows
        const rowCount = await pageAdmin.evaluate((uid) => {
            return Array.from(document.querySelectorAll('#adminMatchesTableBody tr')).filter(r => r.innerText.includes(uid)).length;
        }, battleUid);
        console.log(`Admin matches table row count for ${battleUid}: ${rowCount} (Expected: exactly 1)`);
        if (rowCount !== 1) {
            throw new Error(`Duplicate rows detected in admin matches feed for ${battleUid}!`);
        }

        await new Promise(r => setTimeout(r, 800));
        await pageAdmin.screenshot({ path: path.join(ARTIFACT_DIR, '07_admin_dispute_alert_received.png') });
        console.log('Saved screenshot: 07_admin_dispute_alert_received.png');

        // ---------------------------------------------------------------------
        // STEP 8: Admin Settles Result via Admin Interface
        // ---------------------------------------------------------------------
        console.log(`\n=== STEP 8: Admin Finalizes Settlement for Battle ${battleId} ===`);
        await pageAdmin.goto(`http://127.0.0.1:8000/admin/ludo/matches/${battleId}`, { waitUntil: 'networkidle0' });

        // Select verdict: award_creator
        await pageAdmin.select('form[action*="/resolve"] select[name="decision"]', 'award_creator');
        await pageAdmin.type('form[action*="/resolve"] input[name="reason"]', 'Verified legitimate victory from submitted screenshot proof.');
        await pageAdmin.click('form[action*="/resolve"] button[type="submit"]');
        await pageAdmin.waitForNavigation({ waitUntil: 'networkidle0' });
        console.log('Admin executed result settlement: Creator (Player A) awarded victory!');

        await new Promise(r => setTimeout(r, 600));
        await pageAdmin.screenshot({ path: path.join(ARTIFACT_DIR, '08_admin_settlement_awarded.png') });
        console.log('Saved screenshot: 08_admin_settlement_awarded.png');

        // ---------------------------------------------------------------------
        // STEP 9: Verify Real-Time Victory & Balance Sync for Player A (Zero-Reload)
        // ---------------------------------------------------------------------
        console.log('\n=== STEP 9: Watching Player A Screen for Real-Time Victory Sync (Zero-Reload) ===');
        await pagePlayerA.bringToFront();

        const expectedCoinsA = initialCoinsA - 100 + 190; // Stake deducted, prize added
        await pagePlayerA.waitForFunction((expectedCoins) => {
            const banner = document.getElementById('matchCompletedBannerContainer');
            const hasBanner = banner && banner.innerText.includes('Battle Finished') && banner.innerText.includes('Winner: Arjun Sharma');
            const coinsEl = document.querySelector('.user-coins-display');
            const coins = coinsEl ? parseInt(coinsEl.textContent.replace(/[^\d]/g, ''), 10) : 0;
            return hasBanner && coins === expectedCoins;
        }, { timeout: 15000 }, expectedCoinsA);

        const finalCoinsA = await pagePlayerA.$eval('.user-coins-display', el => parseInt(el.textContent.replace(/[^\d]/g, ''), 10));
        console.log(`SUCCESS: Player A arena dynamically displayed victory banner and balance updated to 🪙 ${finalCoinsA} without reloading!`);

        await new Promise(r => setTimeout(r, 600));
        await pagePlayerA.screenshot({ path: path.join(ARTIFACT_DIR, '09_playerA_victory_synced.png') });
        console.log('Saved screenshot: 09_playerA_victory_synced.png');

        // ---------------------------------------------------------------------
        // STEP 10: Verify Real-Time Completion Sync for Player B (Zero-Reload)
        // ---------------------------------------------------------------------
        console.log('\n=== STEP 10: Watching Player B Screen for Real-Time Completion Sync (Zero-Reload) ===');
        await pagePlayerB.bringToFront();

        const expectedCoinsB = initialCoinsB - 100; // Stake deducted, lost
        await pagePlayerB.waitForFunction((expectedCoins) => {
            const banner = document.getElementById('matchCompletedBannerContainer');
            const hasBanner = banner && banner.innerText.includes('Battle Finished');
            const coinsEl = document.querySelector('.user-coins-display');
            const coins = coinsEl ? parseInt(coinsEl.textContent.replace(/[^\d]/g, ''), 10) : 0;
            return hasBanner && coins === expectedCoins;
        }, { timeout: 15000 }, expectedCoinsB);

        const finalCoinsB = await pagePlayerB.$eval('.user-coins-display', el => parseInt(el.textContent.replace(/[^\d]/g, ''), 10));
        console.log(`SUCCESS: Player B arena dynamically completed with 🪙 ${finalCoinsB} coins without reloading!`);

        await new Promise(r => setTimeout(r, 600));
        await pagePlayerB.screenshot({ path: path.join(ARTIFACT_DIR, '10_playerB_completed_synced.png') });
        console.log('Saved screenshot: 10_playerB_completed_synced.png');

        // ---------------------------------------------------------------------
        // STEP 11: Idempotency & Database Ledger Integrity Checks
        // ---------------------------------------------------------------------
        console.log('\n=== STEP 11: Auditing Database Ledger & Idempotency Rules ===');
        const auditOutput = execSync(`php scratch/audit_battle_settlement.php ${battleId}`).toString();
        const auditData = JSON.parse(auditOutput.trim());
        console.log('Ledger Audit Result:', auditData);

        if (auditData.payout_count !== 1) {
            throw new Error(`Ledger integrity failure: expected exactly 1 payout, found ${auditData.payout_count}`);
        }
        if (auditData.refund_count !== 0) {
            throw new Error(`Ledger integrity failure: expected 0 refunds for settled battle, found ${auditData.refund_count}`);
        }
        if (auditData.player_a_coins !== expectedCoinsA || auditData.player_b_coins !== expectedCoinsB) {
            throw new Error(`Balance mismatch: Player A=${auditData.player_a_coins} (exp ${expectedCoinsA}), Player B=${auditData.player_b_coins} (exp ${expectedCoinsB})`);
        }
        console.log('SUCCESS: Ledger audit confirms exact atomic payout with zero double-crediting!');

        // ---------------------------------------------------------------------
        // STEP 12: Unauthorized Access & Privacy Enforcement
        // ---------------------------------------------------------------------
        console.log('\n=== STEP 12: Verifying Unauthorized Player Privacy & Access Blocks ===');
        const unauthOutput = execSync(`php scratch/check_unauthorized_privacy.php ${battleId} ${fixture.player_c_id}`).toString();
        const unauthData = JSON.parse(unauthOutput.trim());
        console.log('Unauthorized Access Check Output:', unauthData);

        if (unauthData.authorized !== false || unauthData.room_code !== null || unauthData.creator_claim !== null) {
            throw new Error(`Privacy breach: Unrelated Player C was able to inspect private match state: ${JSON.stringify(unauthData)}`);
        }
        console.log('SUCCESS: Privacy strictly enforced. Unrelated player receives authorized=false, null room code, and null claims.');

        console.log('\n======================================================================');
        console.log('  ALL 12 REAL-TIME SYNCHRONIZATION SCENARIOS VERIFIED SUCCESSFULLY!  ');
        console.log('======================================================================');

    } catch (err) {
        console.error('\n*** E2E VERIFICATION FAILED ***', err);
        process.exitCode = 1;
    } finally {
        await browser.close();
    }
}

main();
