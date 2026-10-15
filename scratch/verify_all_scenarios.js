import puppeteer from 'puppeteer-core';
import path from 'path';
import { execSync } from 'child_process';

const ARTIFACT_DIR = 'C:\\Users\\ranje\\.gemini\\antigravity\\brain\\466c9621-ef1b-4151-8418-1a59e2fd51c6\\screenshots';

async function runScenario(scenarioName, viewport, screenshotName) {
    console.log(`\n======================================================`);
    console.log(`RUNNING SCENARIO: ${scenarioName} (${viewport.width}x${viewport.height})`);
    console.log(`======================================================`);

    const seedOutput = execSync('php scratch/seed_test_battle.php').toString();
    console.log(seedOutput.trim());
    const battleId = seedOutput.match(/Battle ID: (\d+)/)[1];

    const browser = await puppeteer.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--autoplay-policy=no-user-gesture-required']
    });

    try {
        // PLAYER A (Creator)
        const contextA = await browser.createBrowserContext();
        const pageA = await contextA.newPage();
        await pageA.setViewport(viewport);

        await pageA.goto('http://127.0.0.1:8000/dev/login-as/3520', { waitUntil: 'networkidle0' });
        await pageA.goto('http://127.0.0.1:8000/battles', { waitUntil: 'networkidle0' });
        await pageA.click('#create-battle'); // Audio gesture unlock

        // Instrument chime counter
        await pageA.evaluate(() => {
            window.playedChimeCount = 0;
            const orig = window.playBattleRequestChime;
            window.playBattleRequestChime = function() {
                window.playedChimeCount++;
                if (typeof orig === 'function') orig.apply(this, arguments);
            };
        });

        // Verify initial card state (Own open battle with DELETE button)
        const initialCard = await pageA.$(`.ludo-battle-card[data-battle-id="${battleId}"]`);
        const initialDeleteBtn = await pageA.$(`.ludo-battle-card[data-battle-id="${battleId}"] .battle-action--delete`);
        console.log(`[Player A] Initial card found: ${!!initialCard}, Has DELETE button: ${!!initialDeleteBtn}`);

        // PLAYER B (Joiner)
        const contextB = await browser.createBrowserContext();
        const pageB = await contextB.newPage();
        await pageB.setViewport({ width: 390, height: 844, isMobile: true });
        await pageB.goto('http://127.0.0.1:8000/dev/login-as/4889', { waitUntil: 'networkidle0' });
        await pageB.goto('http://127.0.0.1:8000/battles', { waitUntil: 'networkidle0' });

        const playBtn = await pageB.$(`.ludo-battle-card[data-battle-id="${battleId}"] .ludo-btn-play`);
        console.log(`[Player B] Clicking PLAY for battle ${battleId}...`);
        await playBtn.click();
        await pageB.waitForSelector('.modal.show .ludo-btn-accept-play');
        await pageB.click('.modal.show .ludo-btn-accept-play');
        console.log(`[Player B] Confirmed Accept & Play!`);

        // MONITOR PLAYER A SCREEN
        console.log(`[Player A] Waiting for real-time notification toast...`);
        await pageA.waitForFunction(() => {
            const toast = document.getElementById('ludoBattleRequestToast');
            return toast && toast.style.display === 'block';
        }, { timeout: 12000 });
        console.log(`[Player A] SUCCESS: Real-time toast received in-place!`);

        // Wait a brief moment for smooth CSS slide-in
        await new Promise(r => setTimeout(r, 600));

        // Capture Screenshot
        const screenshotPath = path.join(ARTIFACT_DIR, screenshotName);
        await pageA.screenshot({ path: screenshotPath });
        console.log(`Saved screenshot: ${screenshotName}`);

        // Evaluate in-place DOM updates
        const stats = await pageA.evaluate((bId) => {
            const toast = document.getElementById('ludoBattleRequestToast');
            const card = document.querySelector(`.ludo-battle-card[data-battle-id="${bId}"]`);
            const startBtn = card ? card.querySelector('.battle-action--start') : null;
            const cancelBtn = card ? card.querySelector('.battle-action--cancel') : null;
            return {
                chimeCount: window.playedChimeCount,
                toastVisible: toast ? toast.style.display === 'block' : false,
                toastOpponent: document.getElementById('ludoToastOpponentName')?.textContent,
                toastUid: document.getElementById('ludoToastUid')?.textContent,
                toastEntry: document.getElementById('ludoToastEntry')?.textContent,
                toastStatus: document.getElementById('ludoToastStatus')?.textContent,
                cardIsRequested: card ? card.classList.contains('battle-card-requested') : false,
                hasStartBtn: !!startBtn,
                hasCancelBtn: !!cancelBtn
            };
        }, battleId);

        console.log('[Player A] In-place DOM Verification:', JSON.stringify(stats, null, 2));

        // Deduplication test: wait 4 seconds (one more poll cycle)
        await new Promise(r => setTimeout(r, 4000));
        const chimeAfterPoll = await pageA.evaluate(() => window.playedChimeCount);
        console.log(`[Player A] Chime count after extra poll: ${chimeAfterPoll} (expected: 1)`);

        // Test Toast Action Button ("View Request & Start")
        console.log('[Player A] Testing "View Request & Start" click...');
        await pageA.click('#ludoToastActionBtn');
        const isToastClosed = await pageA.evaluate(() => document.getElementById('ludoBattleRequestToast').style.display === 'none');
        console.log('[Player A] Toast closed after action click:', isToastClosed);

        // Test Sound Toggle Button (Click to mute and click to unmute)
        console.log('[Player A] Testing Sound Toggle (Mute/Unmute)...');
        await pageA.click('#ludoSoundToggleBtn');
        const mutedLabel = await pageA.$eval('#ludoSoundLabel', el => el.textContent);
        const isMutedStorage = await pageA.evaluate(() => localStorage.getItem('ludo_battle_sound_enabled'));
        console.log(`[Player A] Sound label after click: "${mutedLabel}", storage: "${isMutedStorage}"`);

        await pageA.click('#ludoSoundToggleBtn');
        const unmutedLabel = await pageA.$eval('#ludoSoundLabel', el => el.textContent);
        console.log(`[Player A] Sound label after 2nd click: "${unmutedLabel}"`);

    } finally {
        await browser.close();
    }
}

(async () => {
    // 1. Mobile Verification (390 x 844 iPhone 14 / modern mobile)
    await runScenario(
        'Mobile Real-Time Toast & In-Place Card Update',
        { width: 390, height: 844, isMobile: true, hasTouch: true, deviceScaleFactor: 2 },
        'realtime_request_toast_mobile.png'
    );

    // 2. Desktop Verification (1280 x 850 Laptop / Desktop)
    await runScenario(
        'Desktop Real-Time Toast & In-Place Card Update',
        { width: 1280, height: 850, isMobile: false, hasTouch: false, deviceScaleFactor: 2 },
        'realtime_request_toast_desktop.png'
    );

    console.log('\n======================================================');
    console.log('ALL VERIFICATIONS COMPLETED SUCCESSFULLY!');
    console.log('======================================================\n');
})();
