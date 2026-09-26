import { Dark } from 'quasar'

// Thème : « auto » suit le réglage de l'appareil ; le choix de l'utilisateur est mémorisé
const THEME_KEY = 'edl-theme'
export const THEMES = ['auto', 'light', 'dark']
export function applyTheme(mode) {
    Dark.set(mode === 'dark' ? true : mode === 'light' ? false : 'auto')
    document.querySelector('meta[name=theme-color]')?.setAttribute('content', Dark.isActive ? '#0b1220' : '#2563eb')
}
export function storedTheme() {
    try { return THEMES.includes(localStorage.getItem(THEME_KEY)) ? localStorage.getItem(THEME_KEY) : 'auto' } catch { return 'auto' }
}
export function saveTheme(mode) {
    try { localStorage.setItem(THEME_KEY, mode) } catch { /* stockage indisponible */ }
    applyTheme(mode)
}
