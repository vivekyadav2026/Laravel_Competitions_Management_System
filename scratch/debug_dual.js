import puppeteer from 'puppeteer-core';
import { execSync } from 'child_process';

(async () => {
    const seedOutput = execSync('php scratch/seed_test_battle.php').toString();
    console.log(seedOutput);
    const battleId = seedOutput.match(/Battle ID: (\d+)/)[1];

    const browser = await puppeteer.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--autoplay-policy=no-user-gesture-required']
    });

    const pageA = await browser.newPage();
    pageA.on('console', msg => console.log('PAGE A LOG:', msg.text()));
    pageA.on('pageerror', err => console.log('PAGE A ERROR:', err.message));
    pageA.on('requestfailed', req => console.log('PAGE A REQ FAIL:', req.url(), req.failure()?.errorText));

    await pageA.goto('http://127.0.0.1:8000/dev/login-as/3520', { waitUntil: 'networkidle0' });
    await pageA.goto('http://127.0.0.1:8000/battles', { waitUntil: 'networkidle0' });
    await pageA.click('#create-battle'); // gesture

    const pageB = await browser.newPage();
    pageB.on('console', msg => console.log('PAGE B LOG:', msg.text()));
    pageB.on('pageerror', err => console.log('PAGE B ERROR:', err.message));
    await pageB.goto('http://127.0.0.1:8000/dev/login-as/4889', { waitUntil: 'networkidle0' });
    await pageB.goto('http://127.0.0.1:8000/battles', { waitUntil: 'networkidle0' });

    const playBtn = await pageB.$(`.ludo-battle-card[data-battle-id="${battleId}"] .ludo-btn-play`);
    console.log('Play button on B found:', !!playBtn);
    await playBtn.click();
    await pageB.waitForSelector('.modal.show .ludo-btn-accept-play');
    await pageB.click('.modal.show .ludo-btn-accept-play');
    console.log('Clicked Accept & Play on B');

    await new Promise(r => setTimeout(r, 1500));
    await pageA.bringToFront();

    for (let i = 0; i < 6; i++) {
        await new Promise(r => setTimeout(r, 1500));
        const res = await pageA.evaluate(() => {
            const toast = document.getElementById('ludoBattleRequestToast');
            return {
                toastDisplay: toast ? toast.style.display : 'not found',
                lastPlayedSound: localStorage.getItem('ludo_last_played_sound_event'),
                docHidden: document.hidden
            };
        });
        console.log(`Page A Poll Check ${i}:`, JSON.stringify(res));
    }

    await browser.close();
})();
