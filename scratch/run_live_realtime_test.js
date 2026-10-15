import puppeteer from 'puppeteer-core';
import path from 'path';
import { execSync } from 'child_process';

const ARTIFACT_DIR = 'C:\\Users\\ranje\\.gemini\\antigravity\\brain\\466c9621-ef1b-4151-8418-1a59e2fd51c6\\screenshots';

async function run() {
    console.log('--- STEP 1: Seeding Fresh Open Battle for Player A ---');
    const seedOutput = execSync('php scratch/seed_test_battle.php').toString();
    console.log(seedOutput);

    const matchIdMatch = seedOutput.match(/Battle ID: (\d+)/);
    const battleId = matchIdMatch ? matchIdMatch[1] : null;
    if (!battleId) {
        console.error('Failed to extract battle ID');
        return;
    }
    console.log(`Target Battle ID for live test: ${battleId}`);

    const browser = await puppeteer.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--autoplay-policy=no-user-gesture-required']
    });

    try {
        console.log('--- STEP 2: Player A (Creator 3520) Opens Battles Page ---');
        const pageA = await browser.newPage();
        await pageA.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true, deviceScaleFactor: 2 });
        await pageA.goto('http://127.0.0.1:8000/dev/login-as/3520', { waitUntil: 'networkidle0' });
        await pageA.goto('http://127.0.0.1:8000/battles', { waitUntil: 'networkidle0' });

        // Clear any old local storage events to start clean
        await pageA.evaluate(() => {
            localStorage.removeItem('ludo_last_played_sound_event');
            sessionStorage.removeItem('ludo_last_seen_req_event');
            localStorage.setItem('ludo_battle_sound_enabled', 'true');
        });

        // Click to unlock audio
        await pageA.click('#create-battle');

        // Spy on chime function
        await pageA.evaluate(() => {
            window.playedChimeCount = 0;
            const originalChime = window.playBattleRequestChime;
            window.playBattleRequestChime = function () {
                window.playedChimeCount++;
                if (typeof originalChime === 'function') originalChime.apply(this, arguments);
            };
        });

        // Verify Player A sees the card in own-open state with DELETE button
        const cardA = await pageA.$(`.ludo-battle-card[data-battle-id="${battleId}"]`);
        const hasDeleteBtn = await pageA.$(`.ludo-battle-card[data-battle-id="${battleId}"] .battle-action--delete`);
        console.log(`Player A Card found: ${!!cardA}, Has DELETE button: ${!!hasDeleteBtn}`);

        console.log('--- STEP 3: Player B (Joiner 4889) Accepts & Plays the Battle ---');
        const pageB = await browser.newPage();
        await pageB.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true, deviceScaleFactor: 2 });
        await pageB.goto('http://127.0.0.1:8000/dev/login-as/4889', { waitUntil: 'networkidle0' });
        await pageB.goto('http://127.0.0.1:8000/battles', { waitUntil: 'networkidle0' });

        const playBtnB = await pageB.$(`.ludo-battle-card[data-battle-id="${battleId}"] .ludo-btn-play`);
        if (!playBtnB) {
            console.error(`Play button for battle ${battleId} not found on Player B page!`);
            return;
        }

        console.log('Player B clicks PLAY button...');
        await playBtnB.click();
        await pageB.waitForSelector('.modal.show .ludo-btn-accept-play');
        await new Promise(r => setTimeout(r, 300));

        console.log('Player B clicks Accept & Play...');
        await pageB.click('.modal.show .ludo-btn-accept-play');
        await new Promise(r => setTimeout(r, 1000));

        console.log('--- STEP 4: Monitoring Player A Screen for Real-Time Notification & Card Transition ---');
        await pageA.bringToFront();
        await pageA.waitForFunction(() => {
            const toast = document.getElementById('ludoBattleRequestToast');
            return toast && toast.style.display === 'block';
        }, { timeout: 12000 });
        console.log('SUCCESS: Real-time Toast displayed on Player A screen without reloading!');

        await new Promise(r => setTimeout(r, 600));

        // Capture Mobile Screenshot of Toast & Updated Card
        await pageA.screenshot({ path: path.join(ARTIFACT_DIR, 'realtime_request_toast_mobile.png') });
        console.log('Saved realtime_request_toast_mobile.png');

        // Capture Desktop Screenshot
        await pageA.setViewport({ width: 1280, height: 850, deviceScaleFactor: 2 });
        await pageA.screenshot({ path: path.join(ARTIFACT_DIR, 'realtime_request_toast_desktop.png') });
        console.log('Saved realtime_request_toast_desktop.png');

        // Check DOM state on Player A
        const stats = await pageA.evaluate((bId) => {
            const toast = document.getElementById('ludoBattleRequestToast');
            const card = document.querySelector(`.ludo-battle-card[data-battle-id="${bId}"]`);
            const startBtn = card ? card.querySelector('.battle-action--start') : null;
            const cancelBtn = card ? card.querySelector('.battle-action--cancel') : null;
            return {
                chimeCount: window.playedChimeCount,
                toastVisible: toast.style.display === 'block',
                opponentName: document.getElementById('ludoToastOpponentName').textContent,
                toastUid: document.getElementById('ludoToastUid').textContent,
                cardIsRequested: card ? card.classList.contains('battle-card-requested') : false,
                hasStartBtn: !!startBtn,
                hasCancelBtn: !!cancelBtn
            };
        }, battleId);

        console.log('Player A Verification Stats:', JSON.stringify(stats, null, 2));

        console.log('--- STEP 5: Verifying Duplicate Prevention Across Next Polling Cycles ---');
        await new Promise(r => setTimeout(r, 4000));
        const afterPollCount = await pageA.evaluate(() => window.playedChimeCount);
        console.log(`Chime count after subsequent poll: ${afterPollCount} (expected: 1)`);

        if (stats.chimeCount === 1 && afterPollCount === 1) {
            console.log('VERIFICATION PASSED: Chime played exactly once! No duplicates.');
        } else {
            console.log(`WARNING: Chime count was ${stats.chimeCount}, then ${afterPollCount}`);
        }

        // Click Toast Action Button to verify scroll-to-card and highlight
        console.log('--- STEP 6: Testing Toast Action Button Click ---');
        await pageA.click('#ludoToastActionBtn');
        const isToastClosed = await pageA.evaluate(() => document.getElementById('ludoBattleRequestToast').style.display === 'none');
        console.log(`Toast closed after clicking action: ${isToastClosed}`);

        // Test Sound Toggle Button
        console.log('--- STEP 7: Testing Sound Toggle (Mute) ---');
        await pageA.click('#ludoSoundToggleBtn');
        const soundLabelAfter = await pageA.$eval('#ludoSoundLabel', el => el.textContent);
        console.log(`Sound toggle state after click: ${soundLabelAfter}`);

    } catch (err) {
        console.error('Error during live test:', err);
    } finally {
        await browser.close();
    }
}

run();
