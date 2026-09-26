/**
 * Réduit une photo avant envoi : les photos de téléphone (4-10 Mo) sont ramenées à ~1920 px de côté en JPEG.
 * Économise le réseau (surtout en 4G) et le poids du PDF. En cas de doute (format non
 * décodable, navigateur ancien, résultat plus lourd), la photo d'origine est conservée.
 */
const MAX_SIDE = 1920
const QUALITY = 0.82
const SKIP_UNDER_BYTES = 350 * 1024

export async function shrinkImage(file) {
    try {
        if (!file.type?.startsWith('image/') || file.type === 'image/gif' || file.type === 'image/svg+xml') return file
        if (typeof createImageBitmap !== 'function' || typeof document === 'undefined') return file

        // `from-image` applique l'orientation EXIF (photos prises en portrait)
        const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' })
        const scale = Math.min(1, MAX_SIDE / Math.max(bitmap.width, bitmap.height))

        if (scale === 1 && file.size <= SKIP_UNDER_BYTES) {
            bitmap.close?.()
            return file
        }

        const canvas = document.createElement('canvas')
        canvas.width = Math.round(bitmap.width * scale)
        canvas.height = Math.round(bitmap.height * scale)
        const ctx = canvas.getContext('2d')
        ctx.fillStyle = '#fff' // PNG transparents : fond blanc plutôt que noir
        ctx.fillRect(0, 0, canvas.width, canvas.height)
        ctx.drawImage(bitmap, 0, 0, canvas.width, canvas.height)
        bitmap.close?.()

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', QUALITY))
        if (!blob || blob.size >= file.size) return file

        const name = (file.name || 'photo').replace(/\.[^.]+$/, '') + '.jpg'
        return new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() })
    } catch {
        return file
    }
}
