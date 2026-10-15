import puppeteer from 'puppeteer-core';
import path from 'path';

const ARTIFACT_DIR = 'C:\\Users\\ranje\\.gemini\\antigravity\\brain\\466c9621-ef1b-4151-8418-1a59e2fd51c6\\screenshots';

async function testCurlyNav() {
    const browser = await puppeteer.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    try {
        const page = await browser.newPage();
        await page.setViewport({ width: 420, height: 400, deviceScaleFactor: 2 });
        await page.goto('file:///C:/xampp/htdocs/Laravel_Competitions_Management_System/scratch/preview_curly_nav.html', { waitUntil: 'networkidle0' });

        await page.screenshot({ path: path.join(ARTIFACT_DIR, 'curly_nav_preview_home.png') });
        console.log('Saved curly_nav_preview_home.png');

        // Click Wallet (index 0)
        await page.click('[data-index="0"]');
        await new Promise(r => setTimeout(r, 450));
        await page.screenshot({ path: path.join(ARTIFACT_DIR, 'curly_nav_preview_wallet.png') });
        console.log('Saved curly_nav_preview_wallet.png');

        // Click Profile (index 4)
        await page.click('[data-index="4"]');
        await new Promise(r => setTimeout(r, 450));
        await page.screenshot({ path: path.join(ARTIFACT_DIR, 'curly_nav_preview_profile.png') });
        console.log('Saved curly_nav_preview_profile.png');
    } finally {
        await browser.close();
    }
}

testCurlyNav();
