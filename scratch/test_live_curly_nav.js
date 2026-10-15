import puppeteer from 'puppeteer-core';
import path from 'path';

const ARTIFACT_DIR = 'C:\\Users\\ranje\\.gemini\\antigravity\\brain\\466c9621-ef1b-4151-8418-1a59e2fd51c6\\screenshots';

async function testAllTabs() {
    const browser = await puppeteer.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    try {
        const page = await browser.newPage();
        await page.setViewport({ width: 390, height: 844, deviceScaleFactor: 2, isMobile: true });
        
        // 1. Instant dev login
        await page.goto('http://127.0.0.1:8000/dev/login-as/player1@fsaludoclub.com', { waitUntil: 'networkidle0' });
        console.log('Logged in as player1@fsaludoclub.com!');

        // 2. Home Tab Screenshot
        await page.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle0' });
        await new Promise(r => setTimeout(r, 400));
        await page.screenshot({ path: path.join(ARTIFACT_DIR, 'curly_tab_home.png') });
        console.log('Saved curly_tab_home.png');

        // 3. Wallet Tab
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle0' }),
            page.click('[data-tab="wallet"]')
        ]);
        await new Promise(r => setTimeout(r, 400));
        await page.screenshot({ path: path.join(ARTIFACT_DIR, 'curly_tab_wallet.png') });
        console.log('Saved curly_tab_wallet.png');

        // 4. Refer Tab
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle0' }),
            page.click('[data-tab="refer"]')
        ]);
        await new Promise(r => setTimeout(r, 400));
        await page.screenshot({ path: path.join(ARTIFACT_DIR, 'curly_tab_refer.png') });
        console.log('Saved curly_tab_refer.png');

        // 5. Support Tab
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle0' }),
            page.click('[data-tab="support"]')
        ]);
        await new Promise(r => setTimeout(r, 400));
        await page.screenshot({ path: path.join(ARTIFACT_DIR, 'curly_tab_support.png') });
        console.log('Saved curly_tab_support.png');

        // 6. Profile Tab
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle0' }),
            page.click('[data-tab="profile"]')
        ]);
        await new Promise(r => setTimeout(r, 400));
        await page.screenshot({ path: path.join(ARTIFACT_DIR, 'curly_tab_profile.png') });
        console.log('Saved curly_tab_profile.png');

    } finally {
        await browser.close();
    }
}

testAllTabs();
