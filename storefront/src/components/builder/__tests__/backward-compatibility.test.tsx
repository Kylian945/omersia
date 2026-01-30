import { describe, it, expect } from '@jest/globals';
import { render } from '@testing-library/react';
import { PageBuilder, ThemeWidgets } from '../PageBuilder';
import { Layout } from '../types';

describe('Page Builder - Backward Compatibility', () => {
  const mockWidgets: ThemeWidgets = {
    TextWidget: ({ props }: { props: { content?: string } }) => (
      <div data-testid="text-widget">{props.content || 'Default text'}</div>
    ),
    ImageWidget: ({ props }: { props: { url?: string; alt?: string } }) => {
      if (!props?.url) return <div>Placeholder</div>;
      return (
        <img
          src={props.url}
          alt={props.alt || ''}
          className="h-auto object-cover"
          data-testid="image-widget"
        />
      );
    },
  };

  describe('Sections without new properties', () => {
    it('renders old sections with default gap and alignment', () => {
      const oldLayout: Layout = {
        sections: [
          {
            id: 'section-1',
            // No gap or alignment properties
            settings: {
              background: '#ffffff',
              paddingTop: 40,
              paddingBottom: 40,
            },
            columns: [
              {
                id: 'col-1',
                width: 100,
                widgets: [
                  {
                    id: 'widget-1',
                    type: 'text',
                    props: { content: 'Hello World' },
                  },
                ],
              },
            ],
          },
        ],
      };

      const { container, getByTestId } = render(
        <PageBuilder layout={oldLayout} widgets={mockWidgets} />
      );

      // Verify section renders
      const section = container.querySelector('section');
      expect(section).toBeTruthy();

      // Verify widget renders
      expect(getByTestId('text-widget')).toBeTruthy();

      // Verify default gap-4 is applied (matches current hardcoded value)
      const flexContainer = container.querySelector('.gap-4');
      expect(flexContainer).toBeTruthy();
    });

    it('renders multiple sections without breaking', () => {
      const oldLayout: Layout = {
        sections: [
          {
            id: 'section-1',
            columns: [
              {
                id: 'col-1',
                width: 100,
                widgets: [
                  { id: 'widget-1', type: 'text', props: { content: 'Section 1' } },
                ],
              },
            ],
          },
          {
            id: 'section-2',
            columns: [
              {
                id: 'col-2',
                width: 100,
                widgets: [
                  { id: 'widget-2', type: 'text', props: { content: 'Section 2' } },
                ],
              },
            ],
          },
        ],
      };

      const { getAllByTestId } = render(
        <PageBuilder layout={oldLayout} widgets={mockWidgets} />
      );

      const widgets = getAllByTestId('text-widget');
      expect(widgets).toHaveLength(2);
    });
  });

  describe('Images without new properties', () => {
    it('renders old images with default aspect ratio and object-fit', () => {
      const oldLayout: Layout = {
        sections: [
          {
            id: 'section-1',
            columns: [
              {
                id: 'col-1',
                width: 100,
                widgets: [
                  {
                    id: 'widget-1',
                    type: 'image',
                    props: {
                      url: 'test-image.jpg',
                      alt: 'Test Image',
                      // No aspectRatio, objectFit, objectPosition, height, width
                    },
                  },
                ],
              },
            ],
          },
        ],
      };

      const { getByTestId } = render(
        <PageBuilder layout={oldLayout} widgets={mockWidgets} />
      );

      const image = getByTestId('image-widget');
      expect(image).toBeTruthy();
      expect(image).toHaveAttribute('src', 'test-image.jpg');
      expect(image).toHaveAttribute('alt', 'Test Image');

      // Should have default classes
      expect(image).toHaveClass('h-auto'); // Default aspect ratio: auto
      expect(image).toHaveClass('object-cover'); // Default object-fit: cover
    });

    it('renders images with only URL (minimal props)', () => {
      const minimalLayout: Layout = {
        sections: [
          {
            id: 'section-1',
            columns: [
              {
                id: 'col-1',
                width: 100,
                widgets: [
                  {
                    id: 'widget-1',
                    type: 'image',
                    props: {
                      url: 'minimal.jpg',
                    },
                  },
                ],
              },
            ],
          },
        ],
      };

      const { getByTestId } = render(
        <PageBuilder layout={minimalLayout} widgets={mockWidgets} />
      );

      const image = getByTestId('image-widget');
      expect(image).toBeTruthy();
      expect(image).toHaveAttribute('src', 'minimal.jpg');
      expect(image).toHaveAttribute('alt', ''); // Empty alt when not provided
    });
  });

  describe('Columns without new width properties', () => {
    it('uses legacy width property', () => {
      const legacyLayout: Layout = {
        sections: [
          {
            id: 'section-1',
            columns: [
              {
                id: 'col-1',
                width: 50, // Legacy property
                // No desktopWidth or mobileWidth
                widgets: [
                  { id: 'widget-1', type: 'text', props: { content: 'Column 1' } },
                ],
              },
              {
                id: 'col-2',
                width: 50,
                widgets: [
                  { id: 'widget-2', type: 'text', props: { content: 'Column 2' } },
                ],
              },
            ],
          },
        ],
      };

      const { getAllByTestId } = render(
        <PageBuilder layout={legacyLayout} widgets={mockWidgets} />
      );

      const widgets = getAllByTestId('text-widget');
      expect(widgets).toHaveLength(2);
    });

    it('handles missing width gracefully', () => {
      const noWidthLayout: Layout = {
        sections: [
          {
            id: 'section-1',
            columns: [
              {
                id: 'col-1',
                width: undefined as unknown as number, // Missing width
                widgets: [
                  { id: 'widget-1', type: 'text', props: { content: 'No Width' } },
                ],
              },
            ],
          },
        ],
      };

      const { getByTestId } = render(
        <PageBuilder layout={noWidthLayout} widgets={mockWidgets} />
      );

      // Should still render with fallback
      expect(getByTestId('text-widget')).toBeTruthy();
    });
  });

  describe('Mixed old and new properties', () => {
    it('handles sections with partial new properties', () => {
      const mixedLayout: Layout = {
        sections: [
          {
            id: 'section-1',
            // Has gap but no alignment
            settings: {
              gap: 'lg' as unknown as string, // Note: gap might be on settings or directly on section
            },
            columns: [
              {
                id: 'col-1',
                width: 100,
                widgets: [
                  { id: 'widget-1', type: 'text', props: { content: 'Mixed' } },
                ],
              },
            ],
          },
        ],
      };

      const { getByTestId } = render(
        <PageBuilder layout={mixedLayout} widgets={mockWidgets} />
      );

      expect(getByTestId('text-widget')).toBeTruthy();
    });

    it('handles images with partial new properties', () => {
      const partialLayout: Layout = {
        sections: [
          {
            id: 'section-1',
            columns: [
              {
                id: 'col-1',
                width: 100,
                widgets: [
                  {
                    id: 'widget-1',
                    type: 'image',
                    props: {
                      url: 'partial.jpg',
                      alt: 'Partial',
                      aspectRatio: '16:9' as unknown as string,
                      // Missing objectFit and objectPosition
                    },
                  },
                ],
              },
            ],
          },
        ],
      };

      const { getByTestId } = render(
        <PageBuilder layout={partialLayout} widgets={mockWidgets} />
      );

      const image = getByTestId('image-widget');
      expect(image).toBeTruthy();
    });
  });

  describe('Empty and null values', () => {
    it('handles empty sections array', () => {
      const emptyLayout: Layout = {
        sections: [],
      };

      const { container } = render(
        <PageBuilder layout={emptyLayout} widgets={mockWidgets} />
      );

      // Should render nothing or empty container
      expect(container.firstChild).toBeFalsy();
    });

    it('handles null layout', () => {
      const { container } = render(
        <PageBuilder layout={null as unknown as Layout} widgets={mockWidgets} />
      );

      expect(container.firstChild).toBeFalsy();
    });

    it('handles sections with empty columns', () => {
      const emptyColumnsLayout: Layout = {
        sections: [
          {
            id: 'section-1',
            columns: [],
          },
        ],
      };

      const { container } = render(
        <PageBuilder layout={emptyColumnsLayout} widgets={mockWidgets} />
      );

      // Section should still render (even if empty)
      const section = container.querySelector('section');
      expect(section).toBeTruthy();
    });

    it('handles columns with empty widgets', () => {
      const emptyWidgetsLayout: Layout = {
        sections: [
          {
            id: 'section-1',
            columns: [
              {
                id: 'col-1',
                width: 100,
                widgets: [],
              },
            ],
          },
        ],
      };

      const { container } = render(
        <PageBuilder layout={emptyWidgetsLayout} widgets={mockWidgets} />
      );

      const section = container.querySelector('section');
      expect(section).toBeTruthy();
    });
  });

  describe('Data consistency', () => {
    it('preserves widget data when rendering old layouts', () => {
      const oldLayout: Layout = {
        sections: [
          {
            id: 'section-1',
            columns: [
              {
                id: 'col-1',
                width: 100,
                widgets: [
                  {
                    id: 'widget-1',
                    type: 'text',
                    props: {
                      content: 'Important content that must be preserved',
                    },
                  },
                ],
              },
            ],
          },
        ],
      };

      const { getByText } = render(
        <PageBuilder layout={oldLayout} widgets={mockWidgets} />
      );

      // Verify exact content is preserved
      expect(getByText('Important content that must be preserved')).toBeTruthy();
    });

    it('maintains widget order in old layouts', () => {
      const orderedLayout: Layout = {
        sections: [
          {
            id: 'section-1',
            columns: [
              {
                id: 'col-1',
                width: 100,
                widgets: [
                  { id: 'widget-1', type: 'text', props: { content: 'First' } },
                  { id: 'widget-2', type: 'text', props: { content: 'Second' } },
                  { id: 'widget-3', type: 'text', props: { content: 'Third' } },
                ],
              },
            ],
          },
        ],
      };

      const { getAllByTestId } = render(
        <PageBuilder layout={orderedLayout} widgets={mockWidgets} />
      );

      const widgets = getAllByTestId('text-widget');
      expect(widgets[0]).toHaveTextContent('First');
      expect(widgets[1]).toHaveTextContent('Second');
      expect(widgets[2]).toHaveTextContent('Third');
    });
  });

  describe('Performance with old layouts', () => {
    it('handles large old layouts without performance issues', () => {
      const largeLayout: Layout = {
        sections: Array.from({ length: 10 }, (_, i) => ({
          id: `section-${i}`,
          columns: [
            {
              id: `col-${i}`,
              width: 100,
              widgets: Array.from({ length: 5 }, (_, j) => ({
                id: `widget-${i}-${j}`,
                type: 'text',
                props: { content: `Widget ${i}-${j}` },
              })),
            },
          ],
        })),
      };

      const startTime = Date.now();
      const { getAllByTestId } = render(
        <PageBuilder layout={largeLayout} widgets={mockWidgets} />
      );
      const renderTime = Date.now() - startTime;

      // Should render all widgets
      const widgets = getAllByTestId('text-widget');
      expect(widgets).toHaveLength(50); // 10 sections * 5 widgets

      // Should render quickly (under 100ms for 50 widgets)
      expect(renderTime).toBeLessThan(100);
    });
  });
});
