import puppeteer from 'puppeteer-core';
import path from 'path';

const ARTIFACT_DIR = 'C:\\Users\\ranje\\.gemini\\antigravity\\brain\\466c9621-ef1b-4151-8418-1a59e2fd51c6\\screenshots';

async function captureModal(browser, userId, viewport, filename) {
    const page = await browser.newPage();
    try {
        await page.setViewport(viewport);
        await page.goto(`http://127.0.0.1:8000/dev/login-as/${userId}`, { waitUntil: 'networkidle0' });
        await page.goto('http://127.0.0.1:8000/battles', { waitUntil: 'networkidle0' });

        const playBtn = await page.$('.battle-card--opponent-open .ludo-btn-play');
        if (playBtn) {
            await playBtn.click();
            await page.waitForSelector('.modal.show');
            await new Promise(r => setTimeout(r, 450));
            await page.screenshot({ path: path.join(ARTIFACT_DIR, filename) });
            console.log(`Saved ${filename}`);
        } else {
            console.log(`Play button not found for ${filename}`);
        }
    } finally {
        await page.close();
    }
}

async function run() {
    const browser = await puppeteer.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    try {
        // Sufficient Balance (User 3520 has 5,000 coins)
        console.log('Capturing Sufficient Balance Scenarios...');
        await captureModal(browser, 3520, { width: 390, height: 844, isMobile: true, hasTouch: true, deviceScaleFactor: 2 }, 'polished_modal_sufficient_mobile_390px.png');
        await captureModal(browser, 3520, { width: 360, height: 740, isMobile: true, hasTouch: true, deviceScaleFactor: 2 }, 'polished_modal_mobile_360px.png');
        await captureModal(browser, 3520, { width: 430, height: 932, isMobile: true, hasTouch: true, deviceScaleFactor: 2 }, 'polished_modal_mobile_430px.png');
        await captureModal(browser, 3520, { width: 768, height: 1024, deviceScaleFactor: 2 }, 'polished_modal_tablet_768px.png');
        await captureModal(browser, 3520, { width: 1280, height: 850, deviceScaleFactor: 2 }, 'polished_modal_sufficient_desktop.png');

        // Insufficient Balance (User 4502 has 0 coins)
        console.log('Capturing Insufficient Balance Scenarios...');
        await captureModal(browser, 4502, { width: 390, height: 844, isMobile: true, hasTouch: true, deviceScaleFactor: 2 }, 'polished_modal_insufficient_mobile_390px.png');
        await captureModal(browser, 4502, { width: 1280, height: 850, deviceScaleFactor: 2 }, 'polished_modal_insufficient_desktop.png');

        console.log('All modal viewport screenshots captured successfully!');
    } catch (err) {
        console.error('Error during run:', err);
    } finally {
        await browser.close();
    }
}

run();
