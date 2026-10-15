import puppeteer from 'puppeteer-core';
import path from 'path';

const ARTIFACT_DIR = 'C:\\Users\\ranje\\.gemini\\antigravity\\brain\\466c9621-ef1b-4151-8418-1a59e2fd51c6\\screenshots';

async function checkAdminDeposits() {
    const browser = await puppeteer.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    try {
        const page = await browser.newPage();
        await page.setViewport({ width: 1280, height: 800 });
        
        // 1. Login as superadmin
        await page.goto('http://127.0.0.1:8000/dev/login-as/admin@fsaludoclub.com', { waitUntil: 'networkidle0' });

        // 2. Visit admin deposits desk
        const response = await page.goto('http://127.0.0.1:8000/admin/ludo/deposits', { waitUntil: 'networkidle0' });
        console.log('Status:', response.status());

        await page.screenshot({ path: path.join(ARTIFACT_DIR, 'admin_deposits_desk_verified.png') });
        console.log('Saved admin_deposits_desk_verified.png');
    } finally {
        await browser.close();
    }
}

checkAdminDeposits();
