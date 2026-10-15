import puppeteer from 'puppeteer-core';
import path from 'path';

const ARTIFACT_DIR = 'C:\\Users\\ranje\\.gemini\\antigravity\\brain\\466c9621-ef1b-4151-8418-1a59e2fd51c6\\screenshots';

async function run() {
    const browser = await puppeteer.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--autoplay-policy=no-user-gesture-required']
    });

    try {
        console.log('1. Setting up Player A (Creator, User 3520)...');
        const pageA = await browser.newPage();
        await pageA.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true, deviceScaleFactor: 2 });
        await pageA.goto('http://127.0.0.1:8000/dev/login-as/3520', { waitUntil: 'networkidle0' });
        await pageA.goto('http://127.0.0.1:8000/battles', { waitUntil: 'networkidle0' });

        // Click anywhere to ensure user gesture / audio unlock
        await pageA.click('#create-battle');

        // Verify Sound Toggle state
        const soundText = await pageA.$eval('#ludoSoundLabel', el => el.textContent);
        console.log('Player A Sound status:', soundText);

        // Spy on Web Audio synthesis execution
        await pageA.evaluate(() => {
            window.playedChimeCount = 0;
            const originalPlay = window.playBattleRequestChime;
            window.playBattleRequestChime = function() {
                window.playedChimeCount++;
                if (typeof originalPlay === 'function') originalPlay.apply(this, arguments);
            };
        });

        // 2. Setting up Player B (Joiner, User 4889)...
        console.log('2. Setting up Player B (Joiner, User 4889)...');
        const pageB = await browser.newPage();
        await pageB.goto('http://127.0.0.1:8000/dev/login-as/4889', { waitUntil: 'networkidle0' });
        await pageB.goto('http://127.0.0.1:8000/battles', { waitUntil: 'networkidle0' });

        // Player B finds Player A's open battle or any open battle
        const playBtn = await pageB.$(`.ludo-battle-card[data-battle-id="1265"] .ludo-btn-play`) || await pageB.$('.battle-card--opponent-open .ludo-btn-play');
        if (!playBtn) {
            console.log('No open battle card found for Player B');
            return;
        }

        console.log('3. Player B clicks Play and confirms join...');
        await playBtn.click();
        await pageB.waitForSelector('.modal.show .ludo-btn-accept-play');
        await pageB.click('.modal.show .ludo-btn-accept-play');
        await new Promise(r => setTimeout(r, 600));
        console.log('Player B join request submitted.');

        // 4. In Page A: Wait for real-time polling to detect the incoming request
        console.log('4. Waiting for Player A polling to receive incoming request and trigger alert...');
        await pageA.waitForSelector('#ludoBattleRequestToast[style*="display: block"]', { timeout: 8000 });
        console.log('Toast notification appeared on Player A screen!');

        // Wait a moment for animations
        await new Promise(r => setTimeout(r, 500));

        // Capture screenshot of Player A's screen showing the toast notification and updated card
        await pageA.screenshot({ path: path.join(ARTIFACT_DIR, 'realtime_request_toast_mobile.png') });
        console.log('Saved realtime_request_toast_mobile.png');

        // Check desktop layout as well
        await pageA.setViewport({ width: 1280, height: 850, deviceScaleFactor: 2 });
        await pageA.screenshot({ path: path.join(ARTIFACT_DIR, 'realtime_request_toast_desktop.png') });
        console.log('Saved realtime_request_toast_desktop.png');

        // 5. Verify audio chime played and duplicate was prevented
        const audioStats = await pageA.evaluate(() => {
            return {
                playedCount: window.playedChimeCount,
                toastDisplayed: document.getElementById('ludoBattleRequestToast').style.display,
                opponentName: document.getElementById('ludoToastOpponentName').textContent,
                battleUid: document.getElementById('ludoToastUid').textContent,
                cardHasRequestedClass: !!document.querySelector('.battle-card-requested')
            };
        });
        console.log('Audio & Notification Stats:', JSON.stringify(audioStats, null, 2));

        // Wait another 3.5s for next poll cycle to verify no duplicate chime
        await new Promise(r => setTimeout(r, 3500));
        const afterPollCount = await pageA.evaluate(() => window.playedChimeCount);
        console.log('Chime count after subsequent poll cycle:', afterPollCount);

        if (afterPollCount === 1) {
            console.log('SUCCESS: Exactly 1 chime played! No duplicate sound on subsequent polling!');
        } else {
            console.log('WARNING: Chime count was:', afterPollCount);
        }

        // Test Sound Toggle (Mute)
        await pageA.click('#ludoSoundToggleBtn');
        const mutedText = await pageA.$eval('#ludoSoundLabel', el => el.textContent);
        console.log('After clicking toggle:', mutedText);

    } catch (err) {
        console.error('Error during real-time sound test:', err);
    } finally {
        await browser.close();
    }
}

run();
