// ================================
// 🧰 Utility Functions
// ================================

// Safe JSON parser — ensures only one read from the response body
 async function safeParseJSON(response) {
    const text = await response.text();
    try {
        return JSON.parse(text);
    } catch (e) {
        console.error("⚠️ Server returned invalid JSON:", text);
        throw new Error("Server returned invalid JSON or a PHP error occurred.");
    }
}

// Log helper for consistent debugging
 function log(...args) {
    console.log('🔍 [ForeverTunes]', ...args);
}

// Loader toggle for buttons (used in payment flow)
 function toggleLoader(button, show = true) {
    if (!button) return;
    const text = button.querySelector('.pay-button-text');
    const loader = button.querySelector('.loader-container');
    button.disabled = show;
    text?.classList.toggle('hidden', show);
    loader?.classList.toggle('hidden', !show);
}
