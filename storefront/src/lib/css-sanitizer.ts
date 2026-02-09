/**
 * Validates and sanitizes CSS percentage values to prevent injection attacks
 * @param value - Raw value from backend (could be number, string, null, etc.)
 * @returns Safe integer between 0-100
 */
export function validateCSSPercentage(value: unknown): number {
  const num = Number(value);

  // Only allow finite numbers between 0 and 100
  if (!Number.isFinite(num) || num < 0 || num > 100) {
    return 100; // Safe fallback
  }

  // Round to nearest integer to prevent decimal injection attacks
  return Math.round(num);
}

/**
 * Validates and returns safe CSS calc value for use in calc() expressions
 * @param value - Raw percentage value
 * @returns Safe CSS percentage string (e.g., "50%")
 */
export function validateCSSCalcValue(value: unknown): string {
  const safeNum = validateCSSPercentage(value);
  return `${safeNum}%`;
}
