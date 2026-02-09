/**
 * Logger Service
 *
 * Centralise tous les logs de l'application.
 *
 * En développement : logs dans la console normalement
 * En production : logs désactivés pour éviter d'exposer des détails sensibles
 *
 * Usage :
 *   import { logger } from '@/lib/logger';
 *   logger.error('API call failed', error);
 *   logger.warn('Deprecated feature used');
 *   logger.info('User logged in');
 */

type LogLevel = 'error' | 'warn' | 'info' | 'debug';

interface LogMessage {
  level: LogLevel;
  message: string;
  data?: unknown;
  timestamp: string;
}

const isDevelopment = process.env.NODE_ENV === 'development';
const isTest = process.env.NODE_ENV === 'test';

/**
 * Log error messages
 * En production : silencieux (peut être étendu pour envoyer à un service externe)
 */
function error(message: string, data?: unknown): void {
  if (isDevelopment || isTest) {
    console.error(`[ERROR] ${message}`, data ?? '');
  }
  // En production : on pourrait envoyer à Sentry, LogRocket, etc.
  // Pour l'instant : silencieux pour ne pas exposer d'infos sensibles
}

/**
 * Log warning messages
 */
function warn(message: string, data?: unknown): void {
  if (isDevelopment || isTest) {
    console.warn(`[WARN] ${message}`, data ?? '');
  }
}

/**
 * Log info messages
 */
function info(message: string, data?: unknown): void {
  if (isDevelopment) {
    console.log(`[INFO] ${message}`, data ?? '');
  }
}

/**
 * Log debug messages (development only)
 */
function debug(message: string, data?: unknown): void {
  if (isDevelopment) {
    console.log(`[DEBUG] ${message}`, data ?? '');
  }
}

/**
 * Exported logger instance
 */
export const logger = {
  error,
  warn,
  info,
  debug,
};

/**
 * Type export for external services
 */
export type { LogMessage, LogLevel };
