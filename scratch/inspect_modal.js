import puppeteer from 'puppeteer-core';

(async () => {
    const browser = await puppeteer.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 390, height: 844, isMobile: true, deviceScaleFactor: 2 });

    // User with 0 coins
    await page.goto('http://127.0.0.1:8000/dev/login-as/3520');
    // Set coins to 0
    await page.evaluate(async () => {
        // Just navigate to battles
    });

    // Let's create or find user with 0 coins
    // Let's use tinker to set user 3520 coins to 0 for this test or inspect a battle
    await page.goto('http://127.0.0.1:8000/battles');

    // Click first PLAY button
    const playBtn = await page.$('.ludo-btn-play');
    if (!playBtn) {
        console.log('No play button found!');
        await browser.close();
        return;
    }

    await playBtn.click();
    await page.waitForSelector('.modal.show');
    await new Promise(r => setTimeout(r, 600));

    const modalInfo = await page.evaluate(() => {
        const modal = document.querySelector('.modal.show');
        const dialog = modal.querySelector('.modal-dialog');
        const content = modal.querySelector('.modal-content');
        const backdrop = document.querySelector('.modal-backdrop');
        const bottomNav = document.querySelector('.player-bottom-nav') || document.querySelector('.ludo-bottom-nav');

        function getStyles(el) {
            if (!el) return null;
            const cs = window.getComputedStyle(el);
            return {
                tag: el.tagName,
                id: el.id,
                classes: el.className,
                zIndex: cs.zIndex,
                opacity: cs.opacity,
                filter: cs.filter,
                position: cs.position,
                background: cs.background,
                backgroundColor: cs.backgroundColor,
                color: cs.color,
                display: cs.display,
                parentTag: el.parentElement ? el.parentElement.tagName : null,
                parentId: el.parentElement ? el.parentElement.id : null,
                parentClass: el.parentElement ? el.parentElement.className : null
            };
        }

        return {
            modal: getStyles(modal),
            dialog: getStyles(dialog),
            content: getStyles(content),
            backdrop: getStyles(backdrop),
            bottomNav: getStyles(bottomNav),
            bodyHasClass: document.body.className,
            bodyOverflow: window.getComputedStyle(document.body).overflow
        };
    });

    console.log('MODAL COMPUTED INFO:', JSON.stringify(modalInfo, null, 2));

    await page.screenshot({ path: 'scratch/current_modal_inspection.png' });
    console.log('Saved scratch/current_modal_inspection.png');

    await browser.close();
})();
