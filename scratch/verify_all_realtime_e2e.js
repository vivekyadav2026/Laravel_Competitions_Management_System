import puppeteer from 'puppeteer-core';
import path from 'path';
import { execSync } from 'child_process';

const ARTIFACT_DIR = 'C:\\Users\\ranje\\.gemini\\antigravity\\brain\\466c9621-ef1b-4151-8418-1a59e2fd51c6\\screenshots\\realtime_sync';

async function main() {
    console.log('=== STEP 1: Seeding Test Fixtures ===');
    const seedOutput = execSync('php scratch/seed_test_realtime.php').toString();
    console.log('Seed output:', seedOutput);
    const data = JSON.parse(seedOutput.trim());

    const browser = await puppeteer.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--autoplay-policy=no-user-gesture-required']
    });

    try {
        console.log('\n=== FLOW 1 & 2: Deposit Submission, Admin Live Alert & Instant Player Credit ===');
        
        // 1. Isolated Admin Browser Context
        const contextAdmin = await browser.createBrowserContext();
        const pageAdmin = await contextAdmin.newPage();
        await pageAdmin.evaluateOnNewDocument(() => {
            Object.defineProperty(document, 'hidden', { value: false, configurable: true });
        });
        pageAdmin.on('console', msg => console.log('[Admin Console]', msg.text()));
        pageAdmin.on('pageerror', err => console.log('[Admin Error]', err));
        pageAdmin.on('dialog', async dialog => {
            console.log('Admin Page Dialog:', dialog.type(), dialog.message());
            await dialog.accept();
        });
        await pageAdmin.setViewport({ width: 1280, height: 800 });
        await pageAdmin.goto(`http://127.0.0.1:8000/dev/login-as/${data.admin_id}`, { waitUntil: 'networkidle0' });
        await pageAdmin.goto('http://127.0.0.1:8000/admin/ludo/deposits', { waitUntil: 'networkidle0' });
        console.log('Admin opened Deposits desk at /admin/ludo/deposits');

        // 2. Isolated Player A Browser Context
        const contextPlayerA = await browser.createBrowserContext();
        const pagePlayer = await contextPlayerA.newPage();
        await pagePlayer.evaluateOnNewDocument(() => {
            Object.defineProperty(document, 'hidden', { value: false, configurable: true });
        });
        pagePlayer.on('console', msg => console.log('[Player Console]', msg.text()));
        pagePlayer.on('pageerror', err => console.log('[Player Error]', err));
        pagePlayer.on('dialog', async dialog => {
            console.log('Player Page Dialog:', dialog.type(), dialog.message());
            await dialog.accept();
        });
        await pagePlayer.setViewport({ width: 420, height: 880, isMobile: true, hasTouch: true });
        await pagePlayer.goto(`http://127.0.0.1:8000/dev/login-as/${data.player_a_id}`, { waitUntil: 'networkidle0' });
        await pagePlayer.goto('http://127.0.0.1:8000/wallet', { waitUntil: 'networkidle0' });
        console.log('Player opened /wallet');

        // Get initial player coins
        const initialCoins = await pagePlayer.$eval('#playerWalletTotalBalance', el => parseInt(el.textContent.replace(/[^\d]/g, ''), 10));
        console.log(`Player initial balance: ₹${initialCoins}`);

        // Player selects ₹500 pill and enters UTR
        const testUtr = 'UTR' + Date.now();
        console.log(`Player selecting ₹500 and submitting deposit with UTR = ${testUtr}`);
        
        const pill500 = await pagePlayer.$('.ludo-amount-pill[data-amount="500"]');
        if (pill500) {
            await pill500.click();
        } else {
            await pagePlayer.$eval('#formDepositAmount', el => el.value = '500');
        }

        await pagePlayer.type('#inputUtrNumber', testUtr);
        await pagePlayer.click('#btnSubmitUtr');

        // Verify deposit shows in player recent list
        await pagePlayer.waitForFunction(utr => {
            const list = document.getElementById('userPaymentRequestsList');
            return list && list.innerText.includes(utr);
        }, { timeout: 8000 }, testUtr);
        console.log('Deposit successfully created and rendered in player wallet list.');

        // Now switch to Admin Page and wait for live row without reload
        console.log('Watching Admin Deposits desk for real-time live feed insertion...');
        await pageAdmin.bringToFront();

        await pageAdmin.waitForFunction(utr => {
            const table = document.getElementById('adminDepositsTableBody');
            return table && table.innerText.includes(utr);
        }, { timeout: 15000 }, testUtr);
        console.log('SUCCESS: Admin Deposits desk dynamically received the pending deposit row without reload!');

        await new Promise(r => setTimeout(r, 600));
        await pageAdmin.screenshot({ path: path.join(ARTIFACT_DIR, '01_realtime_deposit_admin_alert.png') });
        console.log('Saved 01_realtime_deposit_admin_alert.png');

        // Admin clicks Approve button on this row
        console.log('Admin approving deposit via AJAX...');
        const approved = await pageAdmin.evaluate(utr => {
            const row = Array.from(document.querySelectorAll('#adminDepositsTableBody tr')).find(r => r.innerText.includes(utr));
            if (!row) return false;
            const approveBtn = row.querySelector('.btn-ajax-approve') || row.querySelector('button.btn-success');
            if (approveBtn) {
                approveBtn.click();
                return true;
            }
            return false;
        }, testUtr);
        console.log('Approve button clicked:', approved);

        // Wait for admin row status to update
        await new Promise(r => setTimeout(r, 2000));

        // Switch to Player Page and verify instant approved status and balance update WITHOUT reload
        console.log('Watching Player /wallet for real-time approval detection and balance update...');
        await pagePlayer.bringToFront();

        await pagePlayer.waitForFunction((utr, expectedMin) => {
            const list = document.getElementById('userPaymentRequestsList');
            const hasApprovedBadge = list && list.innerText.includes(utr) && (list.innerText.includes('Approved') || list.innerText.includes('सफल'));
            const balanceEl = document.getElementById('playerWalletTotalBalance');
            const currentCoins = balanceEl ? parseInt(balanceEl.textContent.replace(/[^\d]/g, ''), 10) : 0;
            return hasApprovedBadge && currentCoins >= expectedMin;
        }, { timeout: 15000 }, testUtr, initialCoins + 500);

        const updatedCoins = await pagePlayer.$eval('#playerWalletTotalBalance', el => parseInt(el.textContent.replace(/[^\d]/g, ''), 10));
        console.log(`SUCCESS: Player balance dynamically updated from ₹${initialCoins} to ₹${updatedCoins} without reloading!`);

        await pagePlayer.screenshot({ path: path.join(ARTIFACT_DIR, '02_realtime_deposit_player_approved.png') });
        console.log('Saved 02_realtime_deposit_player_approved.png');


        console.log('\n=== FLOW 4 & 5: Real-time Room Code Sharing and Arena Synchronization ===');
        
        // 1. Player A Arena Page (using isolated Player A context)
        const pageArenaA = await contextPlayerA.newPage();
        await pageArenaA.evaluateOnNewDocument(() => {
            Object.defineProperty(document, 'hidden', { value: false, configurable: true });
        });
        await pageArenaA.setViewport({ width: 420, height: 880, isMobile: true, hasTouch: true });
        await pageArenaA.goto(`http://127.0.0.1:8000/battles/${data.battle_id}`, { waitUntil: 'networkidle0' });
        console.log(`Player A joined match arena at /battles/${data.battle_id}`);

        // 2. Isolated Player B Browser Context
        const contextPlayerB = await browser.createBrowserContext();
        const pageArenaB = await contextPlayerB.newPage();
        await pageArenaB.evaluateOnNewDocument(() => {
            Object.defineProperty(document, 'hidden', { value: false, configurable: true });
        });
        await pageArenaB.setViewport({ width: 420, height: 880, isMobile: true, hasTouch: true });
        await pageArenaB.goto(`http://127.0.0.1:8000/dev/login-as/${data.player_b_id}`, { waitUntil: 'networkidle0' });
        await pageArenaB.goto(`http://127.0.0.1:8000/battles/${data.battle_id}`, { waitUntil: 'networkidle0' });
        console.log(`Player B joined match arena at /battles/${data.battle_id}`);

        // Verify Player B does not have room code yet
        const initialHasCodeB = await pageArenaB.evaluate(() => {
            const el = document.getElementById('roomCodeText');
            return el ? el.textContent.trim() : null;
        });
        console.log('Player B initial room code displayed:', initialHasCodeB || 'None (correct)');

        // Player A sets room code
        const sharedCode = '09812345';
        console.log(`Player A setting room code to: ${sharedCode}...`);
        await pageArenaA.bringToFront();
        await pageArenaA.type('#inputRoomCode', sharedCode);
        await pageArenaA.click('#btnSubmitRoomCode');

        // Verify Player A sees the active room code box
        await pageArenaA.waitForSelector('#roomCodeText', { timeout: 6000 });
        const codeA = await pageArenaA.$eval('#roomCodeText', el => el.textContent.trim());
        console.log(`Player A screen rendered room code: ${codeA}`);
        await pageArenaA.screenshot({ path: path.join(ARTIFACT_DIR, '03_realtime_roomcode_shared_playerA.png') });
        console.log('Saved 03_realtime_roomcode_shared_playerA.png');

        // Check Player B screen WITHOUT reloading
        console.log('Watching Player B arena for real-time room code arrival without reload...');
        await pageArenaB.bringToFront();

        await pageArenaB.waitForFunction(expected => {
            const el = document.getElementById('roomCodeText');
            return el && el.textContent.trim() === expected;
        }, { timeout: 12000 }, sharedCode);

        const codeB = await pageArenaB.$eval('#roomCodeText', el => el.textContent.trim());
        console.log(`SUCCESS: Player B arena dynamically received room code ${codeB} without reload!`);

        await new Promise(r => setTimeout(r, 600));
        await pageArenaB.screenshot({ path: path.join(ARTIFACT_DIR, '04_realtime_roomcode_received_playerB.png') });
        console.log('Saved 04_realtime_roomcode_received_playerB.png');

        console.log('\n=== ALL REAL-TIME VERIFICATION CHECKS PASSED PERFECTLY! ===');

    } catch (err) {
        console.error('Verification failed with error:', err);
        process.exitCode = 1;
    } finally {
        await browser.close();
    }
}

main();
