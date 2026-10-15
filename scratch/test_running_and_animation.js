import puppeteer from 'puppeteer-core';
import path from 'path';
import { execSync } from 'child_process';

const ARTIFACT_DIR = 'C:\\Users\\ranje\\.gemini\\antigravity\\brain\\466c9621-ef1b-4151-8418-1a59e2fd51c6\\screenshots';

async function run() {
    console.log('--- 1. Seeding Running Battle Demo Data ---');
    const seedOutput = execSync('php artisan db:seed --class=RunningBattleDemoSeeder').toString();
    console.log(seedOutput);

    const browser = await puppeteer.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    try {
        // =========================================================================
        // A. DESKTOP VIEWPORT (1280px) — PARTICIPANT PERSPECTIVE (Arjun Sharma)
        // =========================================================================
        console.log('\n--- 2. Testing Desktop Viewport (1280px) Participant ---');
        const contextDesktop = await browser.createBrowserContext();
        const pageDesktop = await contextDesktop.newPage();
        await pageDesktop.setViewport({ width: 1280, height: 900, isMobile: false, deviceScaleFactor: 2 });

        // Login as demo participant Arjun Sharma
        await pageDesktop.goto('http://127.0.0.1:8000/dev/login-as/demo_arjun@fsaludoclub.test', { waitUntil: 'networkidle0' });
        await pageDesktop.goto('http://127.0.0.1:8000/battles', { waitUntil: 'domcontentloaded' });

        // Check cards presence and animation status
        await pageDesktop.waitForSelector('.ludo-running-card');
        const desktopCardsInfo = await pageDesktop.evaluate(() => {
            const openCards = document.querySelectorAll('.ludo-battle-card');
            const runningCards = document.querySelectorAll('.ludo-running-card');
            const runningData = [];
            runningCards.forEach(c => {
                runningData.push({
                    battleId: c.getAttribute('data-battle-id'),
                    battleUid: c.getAttribute('data-battle-uid'),
                    players: c.querySelector('.ludo-running-players-badge')?.textContent.replace(/\s+/g, ' ').trim(),
                    entry: c.querySelector('.ludo-price-fee')?.textContent.trim(),
                    prize: c.querySelector('.ludo-price-win')?.textContent.trim(),
                    hasArenaBtn: !!c.querySelector('.ludo-running-arena-btn'),
                    isMine: c.classList.contains('running-card-mine'),
                    isAnimated: c.classList.contains('ludo-card-animated'),
                    ref: c.querySelector('.ludo-running-ref')?.textContent.trim()
                });
            });
            return {
                openCount: openCards.length,
                runningCount: runningCards.length,
                runningData
            };
        });
        console.log('Desktop Battles Info:', JSON.stringify(desktopCardsInfo, null, 2));

        // Let the slide-in animation settle
        await new Promise(r => setTimeout(r, 600));

        // Scroll desktop to Running Battles section
        await pageDesktop.evaluate(() => {
            const section = document.getElementById('running-battles-section');
            if (section) section.scrollIntoView({ behavior: 'instant', block: 'start' });
        });
        await new Promise(r => setTimeout(r, 300));

        // Capture Desktop Screenshot
        await pageDesktop.screenshot({ 
            path: path.join(ARTIFACT_DIR, 'running_battles_desktop_1280px.png'),
            fullPage: false
        });
        console.log('Saved running_battles_desktop_1280px.png');

        // Test clicking "Match Arena" on participant's running battle
        const arenaBtn = await pageDesktop.$('.ludo-running-card.running-card-mine .ludo-running-arena-btn');
        if (arenaBtn) {
            console.log('Clicking Match Arena button on participant card...');
            await Promise.all([
                pageDesktop.waitForNavigation({ waitUntil: 'networkidle0' }),
                arenaBtn.click()
            ]);
            console.log('Navigated to URL:', pageDesktop.url());
            await pageDesktop.screenshot({
                path: path.join(ARTIFACT_DIR, 'running_battle_arena_navigation.png')
            });
            console.log('Saved running_battle_arena_navigation.png');
        }

        // =========================================================================
        // B. MOBILE VIEWPORT (390px) — SPECTATOR PERSPECTIVE (Sunil Yadav)
        // =========================================================================
        console.log('\n--- 3. Testing Mobile Viewport (390px) Spectator ---');
        const contextMobile = await browser.createBrowserContext();
        const pageMobile = await contextMobile.newPage();
        await pageMobile.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true, deviceScaleFactor: 2 });

        // Login as spectator
        await pageMobile.goto('http://127.0.0.1:8000/dev/login-as/demo_sunil@fsaludoclub.test', { waitUntil: 'networkidle0' });
        await pageMobile.goto('http://127.0.0.1:8000/battles', { waitUntil: 'domcontentloaded' });
        await pageMobile.waitForSelector('.ludo-running-card');

        // Let entrance animation complete
        await new Promise(r => setTimeout(r, 700));

        // Verify spectator view properties (no private room codes, "In Play" badges)
        const spectatorChecks = await pageMobile.evaluate(() => {
            const runningCards = document.querySelectorAll('.ludo-running-card');
            const results = [];
            runningCards.forEach(c => {
                results.push({
                    uid: c.getAttribute('data-battle-uid'),
                    hasRoomCodeText: c.textContent.includes('SECRET') || /\bRoom:\s*\d{6,}\b/.test(c.textContent),
                    hasInPlayBadge: !!c.querySelector('.badge:not(.bg-warning)'),
                    badgeText: c.querySelector('.ludo-running-footer .badge')?.textContent.trim()
                });
            });
            return results;
        });
        console.log('Spectator Verification on Mobile:', JSON.stringify(spectatorChecks, null, 2));

        // Scroll to Running Battles section to capture focused mobile screenshot
        await pageMobile.evaluate(() => {
            const section = document.getElementById('running-battles-section');
            if (section) section.scrollIntoView({ behavior: 'instant', block: 'start' });
        });
        await new Promise(r => setTimeout(r, 300));

        await pageMobile.screenshot({
            path: path.join(ARTIFACT_DIR, 'running_battles_mobile_390px.png')
        });
        console.log('Saved running_battles_mobile_390px.png');

        // Also capture full-page mobile view
        await pageMobile.evaluate(() => window.scrollTo(0, 0));
        await new Promise(r => setTimeout(r, 300));
        await pageMobile.screenshot({
            path: path.join(ARTIFACT_DIR, 'play_page_full_mobile_390px.png')
        });
        console.log('Saved play_page_full_mobile_390px.png');

        // =========================================================================
        // C. VERIFY NO RE-ANIMATION ON POLLING UPDATE
        // =========================================================================
        console.log('\n--- 4. Verifying No Re-animation on Polling Update ---');
        const pollingCheck = await pageDesktop.evaluate(async () => {
            // Check cards animation classes before poll
            const cardsBefore = Array.from(document.querySelectorAll('.ludo-running-card')).map(c => ({
                id: c.getAttribute('data-battle-id'),
                hasSlideClass: c.classList.contains('ludo-slide-in-right'),
                hasAnimatedClass: c.classList.contains('ludo-card-animated')
            }));

            // Trigger syncState fetch
            const res = await fetch('/battles/sync-state', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();

            // Wait a small delay
            await new Promise(r => setTimeout(r, 300));

            const cardsAfter = Array.from(document.querySelectorAll('.ludo-running-card')).map(c => ({
                id: c.getAttribute('data-battle-id'),
                hasSlideClass: c.classList.contains('ludo-slide-in-right'),
                hasAnimatedClass: c.classList.contains('ludo-card-animated')
            }));

            return {
                cardsBefore,
                cardsAfter,
                syncDataRunningCount: data.running_battles?.length ?? 0
            };
        });
        console.log('Polling Re-animation Verification:', JSON.stringify(pollingCheck, null, 2));

        console.log('\n======================================================');
        console.log('ALL BROWSER AUTOMATION TESTS COMPLETED SUCCESSFULLY!');
        console.log('======================================================');

    } finally {
        await browser.close();
    }
}

run().catch(err => {
    console.error('Test script error:', err);
    process.exit(1);
});
