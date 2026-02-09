import { describe, it, expect } from '@jest/globals';
import { validateCSSPercentage, validateCSSCalcValue } from '../css-sanitizer';

describe('css-sanitizer', () => {
  describe('validateCSSPercentage', () => {
    describe('valid cases', () => {
      it('should accept 0 and return 0', () => {
        expect(validateCSSPercentage(0)).toBe(0);
      });

      it('should accept 50 and return 50', () => {
        expect(validateCSSPercentage(50)).toBe(50);
      });

      it('should accept 100 and return 100', () => {
        expect(validateCSSPercentage(100)).toBe(100);
      });

      it('should round 50.5 to 51', () => {
        expect(validateCSSPercentage(50.5)).toBe(51);
      });

      it('should round 50.4 to 50', () => {
        expect(validateCSSPercentage(50.4)).toBe(50);
      });

      it('should round 0.4 to 0', () => {
        expect(validateCSSPercentage(0.4)).toBe(0);
      });

      it('should accept numbers as strings and convert them', () => {
        expect(validateCSSPercentage('50')).toBe(50);
        expect(validateCSSPercentage('75')).toBe(75);
      });
    });

    describe('invalid cases - out of range', () => {
      it('should return 100 for negative numbers', () => {
        expect(validateCSSPercentage(-10)).toBe(100);
        expect(validateCSSPercentage(-1)).toBe(100);
        expect(validateCSSPercentage(-999)).toBe(100);
      });

      it('should return 100 for values above 100', () => {
        expect(validateCSSPercentage(150)).toBe(100);
        expect(validateCSSPercentage(101)).toBe(100);
        expect(validateCSSPercentage(999)).toBe(100);
      });

      it('should round 100.6 to 101 then fallback to 100', () => {
        // 100.6 rounds to 101, which is > 100, so returns 100
        expect(validateCSSPercentage(100.6)).toBe(100);
      });
    });

    describe('invalid cases - wrong types', () => {
      it('should return 100 for string with units', () => {
        expect(validateCSSPercentage('50px')).toBe(100);
        expect(validateCSSPercentage('50%')).toBe(100);
        expect(validateCSSPercentage('50em')).toBe(100);
        expect(validateCSSPercentage('50rem')).toBe(100);
      });

      it('should return 0 for null (Number(null) = 0)', () => {
        // Note: Number(null) = 0, which is valid, so it returns 0
        expect(validateCSSPercentage(null)).toBe(0);
      });

      it('should return 100 for undefined (Number(undefined) = NaN)', () => {
        // Number(undefined) = NaN, which is invalid, so returns 100
        expect(validateCSSPercentage(undefined)).toBe(100);
      });

      it('should return 100 for NaN', () => {
        expect(validateCSSPercentage(NaN)).toBe(100);
      });

      it('should return 100 for Infinity', () => {
        expect(validateCSSPercentage(Infinity)).toBe(100);
        expect(validateCSSPercentage(-Infinity)).toBe(100);
      });

      it('should return 100 for objects', () => {
        expect(validateCSSPercentage({})).toBe(100);
        expect(validateCSSPercentage({ value: 50 })).toBe(100);
      });

      it('should handle arrays (Number([]) = 0, Number([50]) = 50)', () => {
        // Number([]) = 0, which is valid
        expect(validateCSSPercentage([])).toBe(0);
        // Number([50]) = 50, which is valid
        expect(validateCSSPercentage([50])).toBe(50);
        // Number([1,2]) = NaN, which is invalid
        expect(validateCSSPercentage([1, 2])).toBe(100);
      });

      it('should handle boolean (Number(true) = 1, Number(false) = 0)', () => {
        // Number(true) = 1, which is valid (1%)
        expect(validateCSSPercentage(true)).toBe(1);
        // Number(false) = 0, which is valid (0%)
        expect(validateCSSPercentage(false)).toBe(0);
      });
    });

    describe('XSS injection attempts', () => {
      it('should sanitize CSS injection with closing brace and script', () => {
        const malicious = '50}; script{alert(1)}';
        expect(validateCSSPercentage(malicious)).toBe(100);
      });

      it('should sanitize CSS injection with background URL', () => {
        const malicious = '50%}body{background:url(javascript:alert(1))}';
        expect(validateCSSPercentage(malicious)).toBe(100);
      });

      it('should sanitize CSS injection with style closing tag', () => {
        const malicious = '50</style><script>alert(1)</script>';
        expect(validateCSSPercentage(malicious)).toBe(100);
      });

      it('should sanitize CSS injection with expression', () => {
        const malicious = '50; expression(alert(1))';
        expect(validateCSSPercentage(malicious)).toBe(100);
      });

      it('should sanitize CSS injection with import', () => {
        const malicious = '50; @import url(javascript:alert(1))';
        expect(validateCSSPercentage(malicious)).toBe(100);
      });

      it('should sanitize calc injection attempt', () => {
        const malicious = 'calc(100% - 50px)';
        expect(validateCSSPercentage(malicious)).toBe(100);
      });

      it('should sanitize var injection attempt', () => {
        const malicious = 'var(--malicious)';
        expect(validateCSSPercentage(malicious)).toBe(100);
      });
    });

    describe('edge cases', () => {
      it('should handle very small decimals', () => {
        expect(validateCSSPercentage(0.001)).toBe(0);
        expect(validateCSSPercentage(0.1)).toBe(0);
      });

      it('should handle numbers close to boundaries', () => {
        expect(validateCSSPercentage(99.9)).toBe(100);
        expect(validateCSSPercentage(0.6)).toBe(1);
      });

      it('should handle string numbers with whitespace', () => {
        expect(validateCSSPercentage(' 50 ')).toBe(50);
        expect(validateCSSPercentage('\n75\t')).toBe(75);
      });
    });
  });

  describe('validateCSSCalcValue', () => {
    it('should return safe CSS percentage string for valid numbers', () => {
      expect(validateCSSCalcValue(0)).toBe('0%');
      expect(validateCSSCalcValue(50)).toBe('50%');
      expect(validateCSSCalcValue(100)).toBe('100%');
    });

    it('should round decimals and return percentage string', () => {
      expect(validateCSSCalcValue(50.5)).toBe('51%');
      expect(validateCSSCalcValue(50.4)).toBe('50%');
    });

    it('should return 100% for invalid inputs', () => {
      expect(validateCSSCalcValue(-10)).toBe('100%');
      expect(validateCSSCalcValue(150)).toBe('100%');
      expect(validateCSSCalcValue('50px')).toBe('100%');
      // Note: null converts to 0, which is valid
      expect(validateCSSCalcValue(null)).toBe('0%');
      // undefined converts to NaN, which is invalid
      expect(validateCSSCalcValue(undefined)).toBe('100%');
      expect(validateCSSCalcValue(NaN)).toBe('100%');
    });

    it('should sanitize XSS injection attempts', () => {
      expect(validateCSSCalcValue('50}; script{alert(1)}')).toBe('100%');
      expect(validateCSSCalcValue('50%}body{background:red}')).toBe('100%');
      expect(validateCSSCalcValue('calc(100% - 50px)')).toBe('100%');
    });

    it('should return properly formatted percentage string', () => {
      const result = validateCSSCalcValue(75);
      expect(result).toMatch(/^\d+%$/);
      expect(result).toBe('75%');
    });
  });
});
