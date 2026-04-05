const KEY_HEX: string = (import.meta.env.VITE_API_ENCRYPT_KEY as string) ?? ''

let _cryptoKey: CryptoKey | null = null

function hexToBytes(hex: string): Uint8Array {
  const bytes = new Uint8Array(hex.length / 2)
  for (let i = 0; i < hex.length; i += 2) {
    bytes[i / 2] = parseInt(hex.substring(i, i + 2), 16)
  }
  return bytes
}

async function getCryptoKey(): Promise<CryptoKey> {
  if (_cryptoKey) return _cryptoKey
  if (!KEY_HEX || KEY_HEX.length !== 64) {
    throw new Error('VITE_API_ENCRYPT_KEY is missing or invalid')
  }
  const raw = hexToBytes(KEY_HEX)
  _cryptoKey = await crypto.subtle.importKey('raw', raw, { name: 'AES-CBC' }, false, ['decrypt'])
  return _cryptoKey
}

export async function decryptPayload(encrypted: string): Promise<unknown> {
  const raw = Uint8Array.from(atob(encrypted), (c) => c.charCodeAt(0))
  const iv = raw.slice(0, 16)
  const ciphertext = raw.slice(16)
  const key = await getCryptoKey()
  const decrypted = await crypto.subtle.decrypt({ name: 'AES-CBC', iv }, key, ciphertext)
  return JSON.parse(new TextDecoder().decode(decrypted))
}
