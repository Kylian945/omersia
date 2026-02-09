import { describe, it, expect } from '@jest/globals';
import { render, screen } from '@testing-library/react';
import { PageBuilder, ThemeWidgets } from '../PageBuilder';
import { Layout } from '../types';

// Mock widgets for testing
const mockWidgets: ThemeWidgets = {
  TextWidget: ({ props }: { props: { content?: string } }) => (
    <div data-testid="text-widget">{props.content || 'Default text'}</div>
  ),
  HeadingWidget: ({ props }: { props: { text?: string; level?: string } }) => (
    <div data-testid="heading-widget">{props.text || 'Default heading'}</div>
  ),
  ButtonWidget: ({ props }: { props: { text?: string } }) => (
    <button data-testid="button-widget">{props.text || 'Click'}</button>
  ),
};

describe('PageBuilder', () => {
  describe('CSS sanitization', () => {
    it('should sanitize malicious desktopWidth values', () => {
      const maliciousLayout: Layout = {
        sections: [
          {
            id: 'section-1',
            columns: [
              {
                id: 'col-1',
                // Attempt CSS injection
                desktopWidth: '50}; body{background:red}' as unknown as number,
                mobileWidth: 100,
                widgets: [],
              },
            ],
          },
        ],
      };

      const { container } = render(<PageBuilder layout={maliciousLayout} widgets={mockWidgets} />);

      // Get the style tag content
      const styleTag = container.querySelector('style');
      expect(styleTag).toBeTruthy();

      if (styleTag) {
        const cssContent = styleTag.innerHTML;
        // Should not contain injection attempt
        expect(cssContent).not.toContain('body{background:red}');
        expect(cssContent).not.toContain('50}');

        // Should contain sanitized value (1fr = 100% fallback for invalid input)
        expect(cssContent).toContain('1fr');
        // Should NOT contain NaN
        expect(cssContent).not.toContain('NaN');
      }

      // The grid system validates and sanitizes width values internally
      // Malicious values are rejected and replaced with safe fallback
    });

    it('should sanitize malicious mobileWidth values', () => {
      const maliciousLayout: Layout = {
        sections: [
          {
            id: 'section-1',
            columns: [
              {
                id: 'col-1',
                desktopWidth: 50,
                // Attempt to inject JavaScript
                mobileWidth: '50</style><script>alert(1)</script>' as unknown as number,
                widgets: [],
              },
            ],
          },
        ],
      };

      const { container } = render(<PageBuilder layout={maliciousLayout} widgets={mockWidgets} />);

      // Check that script is not in the rendered output
      const scriptTags = container.querySelectorAll('script');
      // Should only contain Next.js scripts, not injected ones
      scriptTags.forEach((script) => {
        expect(script.innerHTML).not.toContain('alert(1)');
      });

      // Check CSS for valid mobile width (injection should be sanitized)
      const gridContainer = container.querySelector('[data-section-grid]');
      expect(gridContainer).toBeTruthy();
      if (gridContainer) {
        // Mobile grid template is in inline style
        const inlineGridTemplate = (gridContainer as HTMLElement).style.gridTemplateColumns;
        expect(inlineGridTemplate).toBeTruthy();
        // Malicious mobile width should be sanitized to 100% = 1fr
        expect(inlineGridTemplate).toContain('1fr');
        expect(inlineGridTemplate).not.toContain('NaN');
      }

      // Check that script is not in CSS or anywhere
      const styleTag = container.querySelector('style');
      if (styleTag) {
        const cssContent = styleTag.innerHTML;
        expect(cssContent).not.toContain('</style>');
        expect(cssContent).not.toContain('<script>');
      }
    });

    it('should only accept integer values between 0-100', () => {
      const layout: Layout = {
        sections: [
          {
            id: 'section-1',
            columns: [
              {
                id: 'col-valid',
                desktopWidth: 50,
                mobileWidth: 100,
                widgets: [],
              },
            ],
          },
        ],
      };

      const { container } = render(<PageBuilder layout={layout} widgets={mockWidgets} />);

      const styleTag = container.querySelector('style');
      expect(styleTag).toBeTruthy();
      if (styleTag) {
        const cssContent = styleTag.innerHTML;
        // Desktop: 50% = 0.5fr
        expect(cssContent).toContain('0.5fr');
      }
    });

    it('should handle negative values safely', () => {
      const layout: Layout = {
        sections: [
          {
            id: 'section-1',
            columns: [
              {
                id: 'col-1',
                desktopWidth: -50 as number,
                mobileWidth: -100 as number,
                widgets: [],
              },
            ],
          },
        ],
      };

      const { container } = render(<PageBuilder layout={layout} widgets={mockWidgets} />);

      const styleTag = container.querySelector('style');
      expect(styleTag).toBeTruthy();
      if (styleTag) {
        const cssContent = styleTag.innerHTML;
        // Negative values should fallback to 100% = 1fr
        expect(cssContent).toContain('1fr');
        expect(cssContent).not.toContain('NaN');
      }
    });

    it('should handle values over 100 safely', () => {
      const layout: Layout = {
        sections: [
          {
            id: 'section-1',
            columns: [
              {
                id: 'col-1',
                desktopWidth: 150 as number,
                mobileWidth: 200 as number,
                widgets: [],
              },
            ],
          },
        ],
      };

      const { container } = render(<PageBuilder layout={layout} widgets={mockWidgets} />);

      const styleTag = container.querySelector('style');
      expect(styleTag).toBeTruthy();
      if (styleTag) {
        const cssContent = styleTag.innerHTML;
        // Values over 100 should be clamped to 100% = 1fr
        expect(cssContent).toContain('1fr');
        expect(cssContent).not.toContain('NaN');
      }
    });

    it('should handle decimal values', () => {
      const layout: Layout = {
        sections: [
          {
            id: 'section-1',
            columns: [
              {
                id: 'col-1',
                desktopWidth: 50.7 as number,
                mobileWidth: 33.3 as number,
                widgets: [],
              },
            ],
          },
        ],
      };

      const { container } = render(<PageBuilder layout={layout} widgets={mockWidgets} />);

      const styleTag = container.querySelector('style');
      expect(styleTag).toBeTruthy();
      if (styleTag) {
        const cssContent = styleTag.innerHTML;
        // Decimal values are preserved in fr units (50.7% = 0.507fr)
        expect(cssContent).toContain('0.507fr');
        expect(cssContent).not.toContain('NaN');
      }
    });
  });

  describe('rendering', () => {
    it('should render null for empty layout', () => {
      const { container } = render(<PageBuilder layout={null} widgets={mockWidgets} />);
      expect(container.firstChild).toBeNull();
    });

    it('should render null for undefined layout', () => {
      const { container } = render(<PageBuilder layout={undefined} widgets={mockWidgets} />);
      expect(container.firstChild).toBeNull();
    });

    it('should render null for layout without sections', () => {
      const layout: Layout = {
        sections: [],
      };
      const { container } = render(<PageBuilder layout={layout} widgets={mockWidgets} />);
      expect(container.firstChild).toBeNull();
    });

    it('should render valid layout with widgets', () => {
      const layout: Layout = {
        sections: [
          {
            id: 'section-1',
            columns: [
              {
                id: 'col-1',
                desktopWidth: 100,
                mobileWidth: 100,
                widgets: [
                  {
                    id: 'widget-1',
                    type: 'text',
                    props: {
                      content: 'Hello World',
                    },
                  },
                ],
              },
            ],
          },
        ],
      };

      render(<PageBuilder layout={layout} widgets={mockWidgets} />);

      expect(screen.getByTestId('text-widget')).toBeTruthy();
      expect(screen.getByText('Hello World')).toBeTruthy();
    });

    it('should apply section styles correctly', () => {
      const layout: Layout = {
        sections: [
          {
            id: 'section-1',
            settings: {
              background: '#ff0000',
              paddingTop: 40,
              paddingBottom: 60,
            },
            columns: [
              {
                id: 'col-1',
                desktopWidth: 100,
                mobileWidth: 100,
                widgets: [],
              },
            ],
          },
        ],
      };

      const { container } = render(<PageBuilder layout={layout} widgets={mockWidgets} />);

      const section = container.querySelector('section');
      expect(section).toBeTruthy();
      if (section) {
        expect(section.style.backgroundColor).toBe('rgb(255, 0, 0)'); // #ff0000 in RGB
        expect(section.style.paddingTop).toBe('40px');
        expect(section.style.paddingBottom).toBe('60px');
      }
    });
  });

  describe('XSS prevention integration', () => {
    it('should prevent CSS injection via calc() expressions', () => {
      const layout: Layout = {
        sections: [
          {
            id: 'section-1',
            columns: [
              {
                id: 'col-1',
                desktopWidth: 'calc(100% - 50px)' as unknown as number,
                mobileWidth: 100,
                widgets: [],
              },
            ],
          },
        ],
      };

      const { container } = render(<PageBuilder layout={layout} widgets={mockWidgets} />);

      const styleTag = container.querySelector('style');
      if (styleTag) {
        const cssContent = styleTag.innerHTML;
        // Should not contain the injected calc expression
        expect(cssContent).not.toContain('calc(100% - 50px)');
        // Should contain sanitized value (1fr = 100% fallback)
        expect(cssContent).toContain('1fr');
        expect(cssContent).not.toContain('NaN');
      }
    });

    it('should prevent CSS injection via var() expressions', () => {
      const layout: Layout = {
        sections: [
          {
            id: 'section-1',
            columns: [
              {
                id: 'col-1',
                desktopWidth: 'var(--malicious)' as unknown as number,
                mobileWidth: 100,
                widgets: [],
              },
            ],
          },
        ],
      };

      const { container } = render(<PageBuilder layout={layout} widgets={mockWidgets} />);

      const styleTag = container.querySelector('style');
      if (styleTag) {
        const cssContent = styleTag.innerHTML;
        // Should not contain the injected var() expression
        expect(cssContent).not.toContain('var(--malicious)');
        // Should contain sanitized value (1fr = 100% fallback)
        expect(cssContent).toContain('1fr');
        expect(cssContent).not.toContain('NaN');
      }
    });
  });
});
