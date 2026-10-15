import puppeteer from 'puppeteer-core';
import path from 'path';

const ARTIFACT_DIR = 'C:\\Users\\ranje\\.gemini\\antigravity\\brain\\466c9621-ef1b-4151-8418-1a59e2fd51c6\\screenshots';

async function testShine() {
    const browser = await puppeteer.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    try {
        const page = await browser.newPage();
        await page.setViewport({ width: 390, height: 844, deviceScaleFactor: 2, isMobile: true });
        await page.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle0' });

        // Let animation spin for 1.2s to reach bottom/side
        await new Promise(r => setTimeout(r, 1200));

        await page.screenshot({ path: path.join(ARTIFACT_DIR, 'home_cards_shining_border_frame1.png') });
        console.log('Saved home_cards_shining_border_frame1.png');

        await new Promise(r => setTimeout(r, 1000));
        await page.screenshot({ path: path.join(ARTIFACT_DIR, 'home_cards_shining_border_frame2.png') });
        console.log('Saved home_cards_shining_border_frame2.png');
    } finally {
        await browser.close();
    }
}

testShine().catch(console.error);
