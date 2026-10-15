import puppeteer from 'puppeteer-core';

(async () => {
    const browser = await puppeteer.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    const page = await browser.newPage();
    page.on('console', msg => console.log('LOG:', msg.text()));
    page.on('pageerror', err => console.log('ERROR:', err.message));

    await page.goto('http://127.0.0.1:8000/dev/login-as/3520');
    await page.goto('http://127.0.0.1:8000/battles');

    const result = await page.evaluate(async () => {
        const syncUrl = document.querySelector('meta[name="app-sync-url"]')?.content || '/battles/sync-state';
        const res = await fetch('/battles/sync-state', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        
        console.log('Got sync data in page:', JSON.stringify(data.creator_pending_start?.event_id));

        if (typeof window.showBattleRequestToast === 'function' && data.creator_pending_start) {
            window.showBattleRequestToast(data.creator_pending_start);
        }

        const toast = document.getElementById('ludoBattleRequestToast');
        return {
            hasShowToast: typeof window.showBattleRequestToast === 'function',
            hasPlayChime: typeof window.playBattleRequestChime === 'function',
            toastDisplay: toast ? toast.style.display : null,
            toastText: toast ? toast.innerText : null
        };
    });

    console.log('Result:', JSON.stringify(result, null, 2));
    await browser.close();
})();
