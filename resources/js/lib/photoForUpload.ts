// Plain helpers (no Vue state) live in lib/; composables/ is for useXxx composables.
/**
 * A photo, made ready ON THE PHONE before it is sent: decoded by the browser (iPhone Safari reads its own HEIC),
 * turned upright from the camera's orientation tag (`createImageBitmap` applies it), shrunk so the long side is
 * at most 1600px, and saved as JPEG — readable by the server, and ~300 KB instead of 4 MB on church wifi.
 *
 * If the browser cannot read the file, the ORIGINAL is returned and the server decides (it re-processes every
 * photo anyway: CreateChildPhotoJob).
 */
const MAX_SIDE = 1600

export async function photoForUpload(file: File): Promise<File> {
    try {
        const bitmap = await createImageBitmap(file)
        const scale = Math.min(1, MAX_SIDE / Math.max(bitmap.width, bitmap.height))
        const canvas = document.createElement('canvas')
        canvas.width = Math.round(bitmap.width * scale)
        canvas.height = Math.round(bitmap.height * scale)
        canvas.getContext('2d')?.drawImage(bitmap, 0, 0, canvas.width, canvas.height)
        bitmap.close()

        const blob = await new Promise<Blob | null>((resolve) =>
            canvas.toBlob(resolve, 'image/jpeg', 0.85),
        )
        if (blob === null) return file

        return new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' })
    } catch {
        return file
    }
}
