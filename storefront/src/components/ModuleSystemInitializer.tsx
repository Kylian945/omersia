'use client';

import { useEffect } from 'react';
import { initializeModuleSystem } from '@/lib/module-system';

/**
 * Initializes the module system on app startup
 *
 * This component should be included once in the root layout
 * to enable the module hook system for shipping, payment, and other modules.
 *
 * Usage in app/layout.tsx:
 * ```tsx
 * import { ModuleSystemInitializer } from '@/components/ModuleSystemInitializer';
 *
 * export default function RootLayout({ children }) {
 *   return (
 *     <html>
 *       <body>
 *         <ModuleSystemInitializer />
 *         {children}
 *       </body>
 *     </html>
 *   );
 * }
 * ```
 */
export function ModuleSystemInitializer() {
  useEffect(() => {
    // Initialize module system once on client mount
    initializeModuleSystem();
  }, []);

  // This component renders nothing, it only initializes the system
  return null;
}
