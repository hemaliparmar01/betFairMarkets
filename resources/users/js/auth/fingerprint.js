const uuid = () => window.crypto?.randomUUID?.() || 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (character) => { const value = Math.floor(Math.random() * 16); return (character === 'x' ? value : (value & 3) | 8).toString(16); });

const deviceId = () => {
    const key = 'bfmarkets-device';
    let value = '';
    try { value = localStorage.getItem(key) || ''; } catch { /* storage can be disabled */ }
    if (!value) {
        value = uuid();
        try { localStorage.setItem(key, value); } catch { /* keep the in-memory id */ }
    }
    document.cookie = `bfmarkets-device=${encodeURIComponent(value)}; path=/; max-age=31536000; SameSite=Lax`;
    return value;
};

const webglVendor = () => {
    const canvas = document.createElement('canvas');
    const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
    if (!gl) return '';
    const debug = gl.getExtension('WEBGL_debug_renderer_info');
    return debug ? gl.getParameter(debug.UNMASKED_VENDOR_WEBGL) || '' : '';
};

const canvasFingerprint = () => {
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');
    if (!context) return '';
    context.textBaseline = 'top';
    context.font = '14px Arial';
    context.fillStyle = '#101828';
    context.fillText('BetGuru fingerprint', 2, 2);
    context.fillStyle = '#2563eb';
    context.fillRect(110, 4, 30, 10);
    return canvas.toDataURL();
};

const fontsFingerprint = async () => {
    if (!document.fonts) return '';
    try {
        await document.fonts.ready;
        return [...document.fonts].map(({ family }) => family || '').filter(Boolean).sort().join('|');
    } catch { return ''; }
};

const hash = async (source) => {
    if (!window.crypto?.subtle || typeof TextEncoder === 'undefined') return '';
    try {
        const bytes = await window.crypto.subtle.digest('SHA-256', new TextEncoder().encode(source));
        return [...new Uint8Array(bytes)].map((byte) => byte.toString(16).padStart(2, '0')).join('');
    } catch { return ''; }
};

export const collectFingerprint = async () => {
    const data = {
        device_id: deviceId(),
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || '',
        screen_resolution: `${window.screen.width}x${window.screen.height}`,
        webgl_vendor: webglVendor(),
    };
    const source = [
        navigator.userAgent || '', data.timezone, data.screen_resolution, data.webgl_vendor,
        canvasFingerprint(), await fontsFingerprint(), navigator.language || '',
        navigator.platform || '', navigator.hardwareConcurrency || '', data.device_id,
    ].join('|');
    data.fingerprint_hash = await hash(source);
    return data;
};

export const fillFingerprintFields = async (root) => {
    const data = await collectFingerprint();
    Object.entries(data).forEach(([name, value]) => {
        root.querySelectorAll(`[name="${name}"]`).forEach((input) => input.value = value);
    });
    return data;
};
