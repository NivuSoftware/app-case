export function createScannerDetector({ onScan, minLength = 6, maxDurationMs = 250, resetGapMs = 120, idleMs = 90 }) {
  let buffer = '';
  let startedAt = 0;
  let lastKeyAt = 0;
  let idleTimer = null;

  function reset() {
    buffer = '';
    startedAt = 0;
    lastKeyAt = 0;

    if (idleTimer) {
      clearTimeout(idleTimer);
      idleTimer = null;
    }
  }

  function scheduleScan() {
    if (idleTimer) clearTimeout(idleTimer);

    idleTimer = setTimeout(async () => {
      const duration = lastKeyAt - startedAt;
      const scanValue = buffer;
      const looksLikeScanner =
        scanValue.length >= minLength &&
        duration >= 0 &&
        duration <= maxDurationMs;

      reset();

      if (!looksLikeScanner || !scanValue) return;
      await onScan(scanValue);
    }, idleMs);
  }

  function registerPrintableKey(key) {
    const now = Date.now();

    if (!startedAt || now - lastKeyAt > resetGapMs) {
      buffer = key;
      startedAt = now;
    } else {
      buffer += key;
    }

    lastKeyAt = now;
    scheduleScan();
  }

  function handleKeydown(event) {
    const key = event.key;
    const isPrintable = key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey;

    if (isPrintable) {
      registerPrintableKey(key);
      return { consumed: false };
    }

    if (key === 'Backspace' || key === 'Delete' || key === 'Enter') {
      reset();
    }

    return { consumed: false };
  }

  return {
    handleKeydown,
    reset,
  };
}
