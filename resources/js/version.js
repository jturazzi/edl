// Version de l'application : config('app.version'), injectée à l'exécution par Laravel
// (window.__APP_VERSION__), avec repli sur la valeur intégrée au build si elle existe.
export const APP_VERSION = window.__APP_VERSION__ || import.meta.env.VITE_APP_VERSION || ''
