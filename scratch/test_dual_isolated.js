import puppeteer from 'puppeteer-core';
import path from 'path';
import { execSync } from 'child_process';

const ARTIFACT_DIR = 'C:\\Users\\ranje\\.gemini\\antigravity\\brain\\466c9621-ef1b-4151-8418-1a59e2fd51c6\\screenshots';

(async () => {
    console.log('--- STEP 1: Seeding Fresh Open Battle for Player A ---');
    const seedOutput = execSync('php scratch/seed_test_battle.php').toString();
    console.log(seedOutput);
    const battleId = seedOutput.match(/Battle ID: (\d+)/)[1];

    const browser = await puppeteer.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--autoplay-policy=no-user-gesture-required']
    });

    // ISOLATED BROWSER CONTEXT FOR PLAYER A
    const contextA = await browser.createBrowserContext();
    const pageA = await contextA.newPage();
    pageA.on('console', msg => console.log('PAGE A LOG:', msg.text()));
    pageA.on('pageerror', err => console.log('PAGE A ERROR:', err.message));

    await pageA.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true, deviceScaleFactor: 2 });
    await pageA.goto('http://127.0.0.1:8000/dev/login-as/3520', { waitUntil: 'networkidle0' });
    await pageA.goto('http://127.0.0.1:8000/battles', { waitUntil: 'networkidle0' });
    await pageA.click('#create-battle'); // User gesture to unlock Web Audio

    // Track chime playback count on Page A
    await pageA.evaluate(() => {
        window.playedChimeCount = 0;
        const orig = window.playBattleRequestChime;
        window.playBattleRequestChime = function() {
            window.playedChimeCount++;
            if (typeof orig === 'function') orig.apply(this, arguments);
        };
    });

    console.log('Player A loaded battles page and unlocked audio.');

    // ISOLATED BROWSER CONTEXT FOR PLAYER B
    const contextB = await browser.createBrowserContext();
    const pageB = await contextB.newPage();
    await pageB.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true, deviceScaleFactor: 2 });
    await pageB.goto('http://127.0.0.1:8000/dev/login-as/4889', { waitUntil: 'networkidle0' });
    await pageB.goto('http://127.0.0.1:8000/battles', { waitUntil: 'networkidle0' });

    console.log('Player B looking for battle ' + battleId + '...');
    const playBtn = await pageB.$(`.ludo-battle-card[data-battle-id="${battleId}"] .ludo-btn-play`);
    if (!playBtn) {
        console.error('Play button not found on Player B page!');
        await browser.close();
        return;
    }

    console.log('Player B clicking Play and confirming modal...');
    await playBtn.click();
    await pageB.waitForSelector('.modal.show .ludo-btn-accept-play');
    await pageB.click('.modal.show .ludo-btn-accept-play');
    console.log('Player B clicked Accept & Play.');

    console.log('--- Monitoring Player A for Real-Time Toast & Audio Chime ---');
    await pageA.waitForFunction(() => {
        const toast = document.getElementById('ludoBattleRequestToast');
        return toast && toast.style.display === 'block';
    }, { timeout: 15000 });

    console.log('SUCCESS! Real-Time Toast appeared on Player A screen!');

    // Wait a brief moment for CSS animations
    await new Promise(r => setTimeout(r, 600));

    // Capture Mobile Screenshot
    await pageA.screenshot({ path: path.join(ARTIFACT_DIR, 'realtime_request_toast_mobile.png') });
    console.log('Saved realtime_request_toast_mobile.png');

    // Capture Desktop Screenshot
    await pageA.setViewport({ width: 1280, height: 850, deviceScaleFactor: 2 });
    await pageA.screenshot({ path: path.join(ARTIFACT_DIR, 'realtime_request_toast_desktop.png') });
    console.log('Saved realtime_request_toast_desktop.png');

    // Check stats on Player A
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
            cardIsRequested: card ? card.classList.contains('battle-card-requested') : false,
            hasStartBtn: !!startBtn,
            hasCancelBtn: !!cancelBtn
        };
    }, battleId);

    console.log('Player A Verification Stats:', JSON.stringify(stats, null, 2));

    // Wait another 4 seconds to verify no duplicate chime is played on next poll
    await new Promise(r => setTimeout(r, 4000));
    const chimeAfter = await pageA.evaluate(() => window.playedChimeCount);
    console.log(`Chime count after subsequent poll: ${chimeAfter} (expected: 1)`);

    // Test Toast Action Button click (scrolls & pulses card)
    await pageA.click('#ludoToastActionBtn');
    const toastHidden = await pageA.evaluate(() => document.getElementById('ludoBattleRequestToast').style.display === 'none');
    console.log('Toast hidden after action click:', toastHidden);

    // Test Sound Toggle Button (Mute)
    await pageA.click('#ludoSoundToggleBtn');
    const soundState = await pageA.evaluate(() => {
        return {
            label: document.getElementById('ludoSoundLabel')?.textContent,
            mutedInStorage: localStorage.getItem('ludo_battle_sound_enabled') === 'false'
        };
    });
    console.log('Sound toggle state:', JSON.stringify(soundState));

    await browser.close();
    console.log('--- ALL VERIFICATIONS COMPLETE ---');
})();
