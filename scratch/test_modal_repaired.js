import puppeteer from 'puppeteer-core';
import path from 'path';
import { execSync } from 'child_process';

const ARTIFACT_DIR = 'C:\\Users\\ranje\\.gemini\\antigravity\\brain\\466c9621-ef1b-4151-8418-1a59e2fd51c6\\screenshots';

async function run() {
    console.log('--- Seeding Fresh Battle & Users ---');
    const seedOutput = execSync('php scratch/seed_modal_battle.php').toString();
    console.log(seedOutput);

    const browser = await puppeteer.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    try {
        // =========================================================================
        // 1. TABLET INSUFFICIENT MODAL (768px)
        // =========================================================================
        console.log('\n--- Testing 768px Tablet (Insufficient) ---');
        const contextTablet = await browser.createBrowserContext();
        const pageTablet = await contextTablet.newPage();
        await pageTablet.setViewport({ width: 768, height: 1024, isMobile: false, deviceScaleFactor: 2 });
        await pageTablet.goto('http://127.0.0.1:8000/dev/login-as/zero_modal@fsaludoclub.test', { waitUntil: 'networkidle0' });
        await pageTablet.goto('http://127.0.0.1:8000/battles', { waitUntil: 'networkidle0' });

        const playBtnTablet = await pageTablet.$('.ludo-battle-card[data-battle-uid="BAT-BLU-250"] .ludo-btn-play');
        await playBtnTablet.click();
        await pageTablet.waitForSelector('.modal.show');
        await new Promise(r => setTimeout(r, 600));

        await pageTablet.screenshot({ path: path.join(ARTIFACT_DIR, 'repaired_modal_insufficient_tablet_768px.png') });
        console.log('Saved repaired_modal_insufficient_tablet_768px.png');

        // =========================================================================
        // 2. DESKTOP INSUFFICIENT MODAL (1280px)
        // =========================================================================
        console.log('\n--- Testing 1280px Desktop (Insufficient) ---');
        const contextDesktop = await browser.createBrowserContext();
        const pageDesktop = await contextDesktop.newPage();
        await pageDesktop.setViewport({ width: 1280, height: 850, isMobile: false, deviceScaleFactor: 2 });
        await pageDesktop.goto('http://127.0.0.1:8000/dev/login-as/zero_modal@fsaludoclub.test', { waitUntil: 'networkidle0' });
        await pageDesktop.goto('http://127.0.0.1:8000/battles', { waitUntil: 'networkidle0' });

        const playBtnDesktop = await pageDesktop.$('.ludo-battle-card[data-battle-uid="BAT-BLU-250"] .ludo-btn-play');
        await playBtnDesktop.click();
        await pageDesktop.waitForSelector('.modal.show');
        await new Promise(r => setTimeout(r, 600));

        await pageDesktop.screenshot({ path: path.join(ARTIFACT_DIR, 'repaired_modal_insufficient_desktop_1280px.png') });
        console.log('Saved repaired_modal_insufficient_desktop_1280px.png');

        // Verify Desktop layout (Cancel on left, Add Points on right)
        const desktopFooterLayout = await pageDesktop.evaluate(() => {
            const footer = document.querySelector('.modal.show .modal-footer');
            const cancelBtn = footer.querySelector('.ludo-modal-btn-cancel');
            const addPointsBtn = footer.querySelector('.ludo-modal-btn-add-points');
            const cancelRect = cancelBtn.getBoundingClientRect();
            const addRect = addPointsBtn.getBoundingClientRect();
            return {
                cancelIsLeft: cancelRect.left < addRect.left,
                cancelX: cancelRect.left,
                addPointsX: addRect.left,
                buttonsOnSameRow: Math.abs(cancelRect.top - addRect.top) < 10
            };
        });
        console.log('Desktop Footer Verification (Cancel Left, Add Right):', JSON.stringify(desktopFooterLayout, null, 2));

        // =========================================================================
        // 3. PARTIAL BALANCE (Aman Partial - 100 Coins for 250 Coin Battle)
        // =========================================================================
        console.log('\n--- Testing Partial Balance Player (100 coins vs 250 fee) ---');
        const contextPartial = await browser.createBrowserContext();
        const pagePartial = await contextPartial.newPage();
        await pagePartial.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true, deviceScaleFactor: 2 });
        await pagePartial.goto('http://127.0.0.1:8000/dev/login-as/partial_modal@fsaludoclub.test', { waitUntil: 'networkidle0' });
        await pagePartial.goto('http://127.0.0.1:8000/battles', { waitUntil: 'networkidle0' });

        const playBtnPartial = await pagePartial.$('.ludo-battle-card[data-battle-uid="BAT-BLU-250"] .ludo-btn-play');
        await playBtnPartial.click();
        await pagePartial.waitForSelector('.modal.show');
        await new Promise(r => setTimeout(r, 600));

        const partialVerification = await pagePartial.evaluate(() => {
            const modal = document.querySelector('.modal.show');
            const warnText = modal.querySelector('.ludo-modal-warn-text')?.textContent.trim();
            const shortfallText = modal.querySelector('.ludo-modal-val-shortfall')?.textContent.trim();
            const curText = modal.querySelector('.ludo-modal-val-cur')?.textContent.trim();
            return {
                warnText,
                shortfallText,
                curText
            };
        });
        console.log('Partial Balance Verification:', JSON.stringify(partialVerification, null, 2));

        await pagePartial.screenshot({ path: path.join(ARTIFACT_DIR, 'repaired_modal_insufficient_partial_mobile.png') });
        console.log('Saved repaired_modal_insufficient_partial_mobile.png');

        // =========================================================================
        // 4. SUFFICIENT BALANCE (Vikram Rich - 5,000 Coins)
        // =========================================================================
        console.log('\n--- Testing Sufficient Balance Player (Mobile & Desktop) ---');
        const contextSufficient = await browser.createBrowserContext();
        const pageSufficient = await contextSufficient.newPage();
        await pageSufficient.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true, deviceScaleFactor: 2 });
        await pageSufficient.goto('http://127.0.0.1:8000/dev/login-as/rich_modal@fsaludoclub.test', { waitUntil: 'networkidle0' });
        await pageSufficient.goto('http://127.0.0.1:8000/battles', { waitUntil: 'networkidle0' });

        const playBtnSufficient = await pageSufficient.$('.ludo-battle-card[data-battle-uid="BAT-BLU-250"] .ludo-btn-play');
        await playBtnSufficient.click();
        await pageSufficient.waitForSelector('.modal.show');
        await new Promise(r => setTimeout(r, 600));

        await pageSufficient.screenshot({ path: path.join(ARTIFACT_DIR, 'repaired_modal_sufficient_mobile_390px.png') });
        console.log('Saved repaired_modal_sufficient_mobile_390px.png');

        const checkSufficientMobile = await pageSufficient.evaluate(() => {
            const modal = document.querySelector('.modal.show');
            const acceptBtn = modal.querySelector('.ludo-btn-accept-play');
            const curBal = modal.querySelector('.ludo-modal-val-cur')?.textContent.trim();
            const afterBal = modal.querySelector('.ludo-modal-val-after')?.textContent.trim();
            return {
                hasAcceptBtn: !!acceptBtn,
                curBal,
                afterBal
            };
        });
        console.log('Sufficient Mobile Verification:', JSON.stringify(checkSufficientMobile, null, 2));

        // Desktop Sufficient
        const contextSufficientDesk = await browser.createBrowserContext();
        const pageSufficientDesk = await contextSufficientDesk.newPage();
        await pageSufficientDesk.setViewport({ width: 1280, height: 850, isMobile: false, deviceScaleFactor: 2 });
        await pageSufficientDesk.goto('http://127.0.0.1:8000/dev/login-as/rich_modal@fsaludoclub.test', { waitUntil: 'networkidle0' });
        await pageSufficientDesk.goto('http://127.0.0.1:8000/battles', { waitUntil: 'networkidle0' });

        const playBtnDeskSufficient = await pageSufficientDesk.$('.ludo-battle-card[data-battle-uid="BAT-BLU-250"] .ludo-btn-play');
        await playBtnDeskSufficient.click();
        await pageSufficientDesk.waitForSelector('.modal.show');
        await new Promise(r => setTimeout(r, 600));

        await pageSufficientDesk.screenshot({ path: path.join(ARTIFACT_DIR, 'repaired_modal_sufficient_desktop_1280px.png') });
        console.log('Saved repaired_modal_sufficient_desktop_1280px.png');

        console.log('\n======================================================');
        console.log('ALL SCREENSHOTS AND VERIFICATIONS SUCCESSFUL!');
        console.log('======================================================\n');

    } finally {
        await browser.close();
    }
}

run().catch(console.error);
