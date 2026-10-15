import puppeteer from 'puppeteer-core';
import fs from 'fs';
import path from 'path';

const ARTIFACT_DIR = 'C:/Users/ranje/.gemini/antigravity/brain/466c9621-ef1b-4151-8418-1a59e2fd51c6/screenshots';
if (!fs.existsSync(ARTIFACT_DIR)) {
    fs.mkdirSync(ARTIFACT_DIR, { recursive: true });
}

async function run() {
    const browser = await puppeteer.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage', '--disable-gpu']
    });

    try {
        const page = await browser.newPage();
        
        // 1. Log in instantly as Player A (Arjun Sharma, ID: 3520)
        console.log('Logging in as Player A (3520)...');
        await page.goto('http://127.0.0.1:8000/dev/login-as/3520', { waitUntil: 'networkidle0' });
        await page.waitForSelector('.ludo-battle-card');

        // Capture 1: Mobile 390px (Exact pixel match comparison)
        await page.setViewport({ width: 390, height: 844, deviceScaleFactor: 2 });
        await new Promise(r => setTimeout(r, 600));
        await page.screenshot({ path: path.join(ARTIFACT_DIR, 'pixel_match_cards_mobile_390px.png'), fullPage: false });
        console.log('Saved pixel_match_cards_mobile_390px.png');

        // Capture 2: Focus on the Open Battles section (card close-up)
        const openBattlesEl = await page.$('#open-battles-section');
        if (openBattlesEl) {
            await openBattlesEl.screenshot({ path: path.join(ARTIFACT_DIR, 'pixel_match_cards_focus_mobile.png') });
            console.log('Saved pixel_match_cards_focus_mobile.png');
        }

        // Capture 3: Mobile 430px (iPhone Pro Max)
        await page.setViewport({ width: 430, height: 932, deviceScaleFactor: 2 });
        await new Promise(r => setTimeout(r, 400));
        await page.screenshot({ path: path.join(ARTIFACT_DIR, 'pixel_match_cards_mobile_430px.png'), fullPage: false });
        console.log('Saved pixel_match_cards_mobile_430px.png');

        // Capture 4: Tablet 768px
        await page.setViewport({ width: 768, height: 1024, deviceScaleFactor: 2 });
        await new Promise(r => setTimeout(r, 400));
        await page.screenshot({ path: path.join(ARTIFACT_DIR, 'pixel_match_cards_tablet_768px.png'), fullPage: false });
        console.log('Saved pixel_match_cards_tablet_768px.png');

        // Capture 5: Desktop 1280px
        await page.setViewport({ width: 1280, height: 850, deviceScaleFactor: 2 });
        await new Promise(r => setTimeout(r, 400));
        await page.screenshot({ path: path.join(ARTIFACT_DIR, 'pixel_match_cards_desktop_1280px.png'), fullPage: false });
        console.log('Saved pixel_match_cards_desktop_1280px.png');

        // Capture 6: Click PLAY on Blue Card to show confirmation modal in action
        const playBtn = await page.$('.battle-card--opponent-open .ludo-btn-play');
        if (playBtn) {
            await playBtn.click();
            await page.waitForSelector('.modal.show');
            await new Promise(r => setTimeout(r, 400));
            await page.screenshot({ path: path.join(ARTIFACT_DIR, 'pixel_match_confirmation_modal.png') });
            console.log('Saved pixel_match_confirmation_modal.png');
        }

        console.log('All screenshots captured successfully!');
    } catch (err) {
        console.error('Error during capture:', err);
    } finally {
        await browser.close();
    }
}

run();
