import { defineConfig } from '@playwright/test'

// Tests navigateur : `npm run test:e2e` (nécessite `npm run build` au préalable).
// Le serveur tourne en environnement `e2e` (.env.e2e) sur une base SQLite jetable.
const PORT = 8799

export default defineConfig({
    testDir: 'tests/e2e',
    fullyParallel: false,
    workers: 1,
    retries: process.env.CI ? 1 : 0,
    reporter: [['list']],
    timeout: 60_000,
    use: {
        baseURL: `http://127.0.0.1:${PORT}`,
        locale: 'fr-FR',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    webServer: {
        command: `sh -c "rm -f database/e2e.sqlite && touch database/e2e.sqlite && php artisan migrate --force --quiet && php artisan serve --host=127.0.0.1 --port=${PORT}"`,
        url: `http://127.0.0.1:${PORT}/login`,
        env: { APP_ENV: 'e2e' },
        reuseExistingServer: false,
        timeout: 60_000,
    },
})
