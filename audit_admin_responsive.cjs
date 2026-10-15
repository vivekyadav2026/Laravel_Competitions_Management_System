const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE_URL = 'http://127.0.0.1:8000';
const ARTIFACT_DIR = 'C:\\Users\\ranje\\.gemini\\antigravity\\brain\\466c9621-ef1b-4151-8418-1a59e2fd51c6\\screenshots\\admin_responsive';
const PUBLIC_DIR = path.join(__dirname, 'public', 'screenshots', 'admin_responsive');

const VIEWPORTS = [
    { name: 'mobile_320', width: 320, height: 720 },
    { name: 'mobile_360', width: 360, height: 740 },
    { name: 'mobile_375', width: 375, height: 812 },
    { name: 'mobile_390', width: 390, height: 844 },
    { name: 'mobile_430', width: 430, height: 932 },
    { name: 'tablet_768', width: 768, height: 1024 },
    { name: 'tablet_1024', width: 1024, height: 768 },
    { name: 'desktop_1280', width: 1280, height: 800 }
];

const SCREENS = [
    { id: 'login', path: '/admin/login', name: 'Admin Login', auth: false },
    { id: 'dashboard', path: '/admin/ludo/dashboard', name: 'Admin Dashboard', auth: true },
    { id: 'users_index', path: '/admin/ludo/users', name: 'User Management', auth: true },
    { id: 'users_show', path: '/admin/ludo/users/1', name: 'User Profile & History', auth: true },
    { id: 'matches_index', path: '/admin/ludo/matches', name: 'Matches & Battles Index', auth: true },
    { id: 'matches_show', path: '/admin/ludo/matches/1', name: 'Match Details Desk', auth: true },
    { id: 'tournaments_index', path: '/admin/ludo/tournaments', name: 'Tournaments Management', auth: true },
    { id: 'tournaments_show', path: '/admin/ludo/tournaments/1', name: 'Tournament Bracket Desk', auth: true },
    { id: 'disputes_index', path: '/admin/ludo/disputes', name: 'Dispute Desk Index', auth: true },
    { id: 'disputes_show', path: '/admin/ludo/disputes/66', name: 'Dispute Resolution Desk', auth: true },
    { id: 'kyc_index', path: '/admin/ludo/kyc', name: 'KYC Document Desk', auth: true },
    { id: 'tickets_index', path: '/admin/ludo/tickets', name: 'Support Tickets Desk', auth: true },
    { id: 'tickets_show', path: '/admin/ludo/tickets/77', name: 'Support Ticket Discussion', auth: true },
    { id: 'ledger_index', path: '/admin/ludo/ledger', name: 'Points Ledger Desk', auth: true },
    { id: 'deposits_index', path: '/admin/ludo/deposits', name: 'UPI QR Deposit Desk', auth: true },
    { id: 'withdrawals_index', path: '/admin/ludo/withdrawals', name: 'Withdrawal Payout Desk', auth: true },
    { id: 'referrals_index', path: '/admin/ludo/referrals', name: 'Referral Management Desk', auth: true },
    { id: 'reports_index', path: '/admin/ludo/reports', name: 'Reports & Analytics', auth: true },
    { id: 'audit_index', path: '/admin/ludo/audit-logs', name: 'Administrative Audit Trail', auth: true },
    { id: 'content_index', path: '/admin/ludo/content', name: 'CMS & Rules Content Editor', auth: true },
    { id: 'settings_index', path: '/admin/ludo/settings', name: 'Platform Settings Desk', auth: true }
];

async function runAudit() {
    console.log('Starting Complete Admin Panel Mobile Responsiveness Audit...');
    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
    });

    const page = await browser.newPage();
    const results = [];

    // Helper to audit a single screen across all viewports
    async function auditScreen(screen) {
        console.log(`\nAuditing [${screen.id}] ${screen.name} (${screen.path})...`);
        const screenResult = {
            id: screen.id,
            name: screen.name,
            path: screen.path,
            viewports: {}
        };

        for (const vp of VIEWPORTS) {
            await page.setViewport({ width: vp.width, height: vp.height });
            let response;
            try {
                response = await page.goto(`${BASE_URL}${screen.path}`, { waitUntil: 'networkidle0', timeout: 15000 });
            } catch (err) {
                console.error(`  Error loading ${screen.path} at ${vp.width}px:`, err.message);
            }

            const status = response ? response.status() : 500;

            const metrics = await page.evaluate(() => {
                const docWidth = document.documentElement.offsetWidth;
                const scrollWidth = document.documentElement.scrollWidth;
                const windowWidth = window.innerWidth;
                const hasHorizontalOverflow = scrollWidth > windowWidth;

                let overflowingElements = [];
                if (hasHorizontalOverflow) {
                    const all = document.querySelectorAll('*');
                    for (let el of all) {
                        const rect = el.getBoundingClientRect();
                        if (rect.right > windowWidth + 1) {
                            overflowingElements.push({
                                tag: el.tagName,
                                class: el.className,
                                id: el.id,
                                right: rect.right,
                                width: rect.width
                            });
                            if (overflowingElements.length >= 5) break;
                        }
                    }
                }

                const tables = document.querySelectorAll('table');
                let tablesWrappedInResponsive = true;
                tables.forEach(t => {
                    if (!t.closest('.table-responsive')) {
                        tablesWrappedInResponsive = false;
                    }
                });

                return {
                    docWidth,
                    scrollWidth,
                    windowWidth,
                    hasHorizontalOverflow,
                    overflowDiff: scrollWidth - windowWidth,
                    overflowingElements,
                    hasTables: tables.length > 0,
                    tablesWrappedInResponsive
                };
            });

            const isPass = !metrics.hasHorizontalOverflow && status < 400;
            screenResult.viewports[vp.name] = {
                status,
                isPass,
                scrollWidth: metrics.scrollWidth,
                windowWidth: metrics.windowWidth,
                overflowDiff: metrics.overflowDiff,
                overflowingElements: metrics.overflowingElements
            };

            const mark = isPass ? '✅ PASS' : '❌ FAIL';
            console.log(`  ${vp.name} (${vp.width}px): ${mark} (status ${status}, scrollWidth: ${metrics.scrollWidth}px, window: ${metrics.windowWidth}px)`);

            // Capture screenshots for 375px (standard mobile), 320px (compact mobile), and 1280px (desktop)
            if (['mobile_320', 'mobile_375', 'desktop_1280'].includes(vp.name)) {
                const shotName = `${screen.id}_${vp.name}.png`;
                const artPath = path.join(ARTIFACT_DIR, shotName);
                const pubPath = path.join(PUBLIC_DIR, shotName);
                await page.screenshot({ path: artPath, fullPage: false });
                fs.copyFileSync(artPath, pubPath);
            }
        }

        results.push(screenResult);
    }

    // 1. Audit Admin Login screen FIRST (while unauthenticated)
    await auditScreen(SCREENS[0]);

    // 2. Authenticate as admin
    console.log('\nAuthenticating as admin@royalludo.com...');
    await page.setViewport({ width: 1280, height: 800 });
    await page.goto(`${BASE_URL}/admin/login`, { waitUntil: 'networkidle0' });
    await page.type('#adminEmail', 'admin@royalludo.com');
    await page.type('#adminPassword', 'Admin@12345');
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0' }),
        page.click('#adminSubmitBtn')
    ]);
    console.log('Logged in successfully. Current URL:', page.url());

    // 3. Audit Shell & Navigation interactions on mobile
    console.log('\n--- Auditing Shell & Navigation Interactions on Mobile (375px) ---');
    await page.setViewport({ width: 375, height: 812 });
    await page.goto(`${BASE_URL}/admin/ludo/dashboard`, { waitUntil: 'networkidle0' });

    // Check sidebar initially hidden
    const initialSidebarPos = await page.evaluate(() => {
        const sb = document.getElementById('adminSidebar');
        if (!sb) return null;
        const rect = sb.getBoundingClientRect();
        return { left: rect.left, right: rect.right, width: rect.width };
    });
    console.log('Sidebar initial position (mobile):', initialSidebarPos);

    // Open sidebar via hamburger
    await page.click('#sidebarToggleBtn');
    await new Promise(r => setTimeout(r, 400));
    const openSidebarPos = await page.evaluate(() => {
        const sb = document.getElementById('adminSidebar');
        const bd = document.getElementById('sidebarBackdrop');
        return {
            left: sb ? sb.getBoundingClientRect().left : null,
            isOpenClass: sb ? sb.classList.contains('show') : false,
            backdropShown: bd ? bd.classList.contains('show') : false,
            bodyLocked: document.body.classList.contains('admin-sidebar-open')
        };
    });
    console.log('Sidebar opened position:', openSidebarPos);

    // Capture screenshot of open drawer
    const drawerShotPath = path.join(ARTIFACT_DIR, 'admin_mobile_sidebar_drawer_375px.png');
    await page.screenshot({ path: drawerShotPath });
    fs.copyFileSync(drawerShotPath, path.join(PUBLIC_DIR, 'admin_mobile_sidebar_drawer_375px.png'));
    console.log('Saved mobile drawer screenshot.');

    // Close via backdrop click
    await page.click('#sidebarBackdrop');
    await new Promise(r => setTimeout(r, 400));
    const closedSidebarPos = await page.evaluate(() => {
        const sb = document.getElementById('adminSidebar');
        return {
            left: sb ? sb.getBoundingClientRect().left : null,
            isOpenClass: sb ? sb.classList.contains('show') : false,
            bodyLocked: document.body.classList.contains('admin-sidebar-open')
        };
    });
    console.log('Sidebar closed via backdrop:', closedSidebarPos);

    // Open sidebar again, close via close button
    await page.click('#sidebarToggleBtn');
    await new Promise(r => setTimeout(r, 400));
    await page.click('#sidebarCloseBtn');
    await new Promise(r => setTimeout(r, 400));
    const closedViaBtn = await page.evaluate(() => {
        const sb = document.getElementById('adminSidebar');
        return sb ? !sb.classList.contains('show') : false;
    });
    console.log('Sidebar closed via close button:', closedViaBtn);

    // Test notification dropdown on mobile
    await page.click('#adminNotificationsMenuBtn');
    await new Promise(r => setTimeout(r, 300));
    const dropdownPos = await page.evaluate(() => {
        const dd = document.querySelector('.admin-notification-dropdown');
        if (!dd) return null;
        const rect = dd.getBoundingClientRect();
        return {
            left: rect.left,
            right: rect.right,
            width: rect.width,
            windowWidth: window.innerWidth,
            isWithinViewport: rect.left >= 0 && rect.right <= window.innerWidth
        };
    });
    console.log('Notification dropdown mobile bounds:', dropdownPos);
    await page.click('.admin-topbar');
    await new Promise(r => setTimeout(r, 200));

    // 4. Audit remaining authenticated screens (screens 1 to 20)
    console.log('\n--- Auditing Remaining Authenticated Screens Across 8 Viewports ---');
    for (let i = 1; i < SCREENS.length; i++) {
        await auditScreen(SCREENS[i]);
    }

    // Write audit data json
    const jsonPath = path.join(__dirname, 'admin_responsive_audit_results.json');
    fs.writeFileSync(jsonPath, JSON.stringify({
        timestamp: new Date().toISOString(),
        shellTests: {
            initialSidebarPos,
            openSidebarPos,
            closedSidebarPos,
            closedViaBtn,
            dropdownPos
        },
        screens: results
    }, null, 2));

    console.log(`\nAudit completed! Results saved to ${jsonPath}`);
    await browser.close();
}

runAudit().catch(err => {
    console.error('Audit script encountered error:', err);
    process.exit(1);
});
