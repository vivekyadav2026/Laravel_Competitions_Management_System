import puppeteer from 'puppeteer-core';

async function checkSync() {
    const browser = await puppeteer.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: 'new',
        args: ['--no-sandbox']
    });

    const page = await browser.newPage();
    await page.goto('http://127.0.0.1:8000/dev/login-as/3520', { waitUntil: 'networkidle0' });
    const res = await page.goto('http://127.0.0.1:8000/battles/sync-state', { waitUntil: 'networkidle0' });
    const text = await res.text();
    console.log('SyncState Output for User 3520:', text);
    await browser.close();
}

checkSync().catch(console.error);
