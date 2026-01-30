import { describe, it, expect, jest } from '@jest/globals';
import { render } from '@testing-library/react';
import { ContainerWidget } from '../ContainerWidget';
import type { Column, WidgetBase } from '@/components/builder/types';

const mockRenderWidget = jest.fn((widget: WidgetBase) => (
  <div data-testid={`widget-${widget.id}`}>
    {widget.type}
  </div>
));

describe('ContainerWidget', () => {
  beforeEach(() => {
    mockRenderWidget.mockClear();
  });

  describe('Basic rendering', () => {
    it('renders with empty columns', () => {
      const { container } = render(
        <ContainerWidget
          props={{ columns: [] }}
          renderWidget={mockRenderWidget}
        />
      );

      const gridContainer = container.querySelector('.grid');
      expect(gridContainer).toBeTruthy();
    });

    it('renders with default gap class', () => {
      const { container } = render(
        <ContainerWidget
          props={{ columns: [] }}
          renderWidget={mockRenderWidget}
        />
      );

      const gridContainer = container.querySelector('.gap-4');
      expect(gridContainer).toBeTruthy();
    });

    it('renders columns with widgets', () => {
      const columns: Column[] = [
        {
          id: 'col-1',
          width: 100,
          widgets: [
            { id: 'widget-1', type: 'text', props: {} },
            { id: 'widget-2', type: 'button', props: {} },
          ],
        },
      ];

      render(
        <ContainerWidget
          props={{ columns }}
          renderWidget={mockRenderWidget}
        />
      );

      expect(mockRenderWidget).toHaveBeenCalledTimes(2);
    });
  });

  describe('Background and padding', () => {
    it('applies default background color', () => {
      const { container } = render(
        <ContainerWidget
          props={{ columns: [] }}
          renderWidget={mockRenderWidget}
        />
      );

      const innerDiv = container.querySelector('[style*="background"]');
      expect(innerDiv).toBeTruthy();
      expect(innerDiv).toHaveStyle({ backgroundColor: '#ffffff' });
    });

    it('applies custom background color', () => {
      const { container } = render(
        <ContainerWidget
          props={{ background: '#ff0000', columns: [] }}
          renderWidget={mockRenderWidget}
        />
      );

      const innerDiv = container.querySelector('[style*="background"]');
      expect(innerDiv).toBeTruthy();
      expect(innerDiv).toHaveStyle({ backgroundColor: 'rgb(255, 0, 0)' });
    });

    it('applies padding via inline styles when using legacy paddingTop/paddingBottom', () => {
      const { container } = render(
        <ContainerWidget
          props={{
            paddingTop: 40,
            paddingBottom: 40,
            columns: []
          }}
          renderWidget={mockRenderWidget}
        />
      );

      const innerDiv = container.querySelector('[style*="padding"]');
      expect(innerDiv).toBeTruthy();
      expect(innerDiv).toHaveStyle({
        paddingTop: '40px',
        paddingBottom: '40px',
      });
    });

    it('applies custom padding', () => {
      const { container } = render(
        <ContainerWidget
          props={{
            paddingTop: 20,
            paddingBottom: 60,
            columns: [],
          }}
          renderWidget={mockRenderWidget}
        />
      );

      const innerDiv = container.querySelector('[style*="padding"]');
      expect(innerDiv).toBeTruthy();
      expect(innerDiv).toHaveStyle({
        paddingTop: '20px',
        paddingBottom: '60px',
      });
    });

    it('handles zero padding', () => {
      const { container } = render(
        <ContainerWidget
          props={{
            paddingTop: 0,
            paddingBottom: 0,
            columns: [],
          }}
          renderWidget={mockRenderWidget}
        />
      );

      const innerDiv = container.querySelector('[style*="padding"]');
      expect(innerDiv).toBeTruthy();
      expect(innerDiv).toHaveStyle({
        paddingTop: '0px',
        paddingBottom: '0px',
      });
    });
  });

  describe('Column widths', () => {
    it('applies column width correctly using grid', () => {
      const columns: Column[] = [
        {
          id: 'col-1',
          width: 50,
          widgets: [],
        },
      ];

      const { container } = render(
        <ContainerWidget
          props={{ columns }}
          renderWidget={mockRenderWidget}
        />
      );

      const gridContainer = container.querySelector('.grid');
      expect(gridContainer).toBeTruthy();
      expect(gridContainer).toHaveStyle({
        gridTemplateColumns: '0.5fr',
      });
    });

    it('handles full width column', () => {
      const columns: Column[] = [
        {
          id: 'col-1',
          width: 100,
          widgets: [],
        },
      ];

      const { container } = render(
        <ContainerWidget
          props={{ columns }}
          renderWidget={mockRenderWidget}
        />
      );

      const gridContainer = container.querySelector('.grid');
      expect(gridContainer).toBeTruthy();
      expect(gridContainer).toHaveStyle({
        gridTemplateColumns: '1fr',
      });
    });

    it('handles multiple columns with different widths', () => {
      const columns: Column[] = [
        { id: 'col-1', width: 33, widgets: [] },
        { id: 'col-2', width: 67, widgets: [] },
      ];

      const { container } = render(
        <ContainerWidget
          props={{ columns }}
          renderWidget={mockRenderWidget}
        />
      );

      const gridContainer = container.querySelector('.grid');
      expect(gridContainer).toBeTruthy();
      expect(gridContainer).toHaveStyle({
        gridTemplateColumns: '0.33fr 0.67fr',
      });
    });
  });

  describe('Backward compatibility', () => {
    it('works without gap and alignment props (old pages)', () => {
      const columns: Column[] = [
        {
          id: 'col-1',
          width: 100,
          widgets: [{ id: 'widget-1', type: 'text', props: {} }],
        },
      ];

      const { container } = render(
        <ContainerWidget
          props={{ columns }}
          renderWidget={mockRenderWidget}
        />
      );

      // Should render with default gap-4 (current hardcoded value)
      const gridContainer = container.querySelector('.gap-4');
      expect(gridContainer).toBeTruthy();

      // Widgets should render
      expect(mockRenderWidget).toHaveBeenCalledTimes(1);
    });

    it('uses default values when props are undefined', () => {
      const { container } = render(
        <ContainerWidget
          props={{ columns: [] }}
          renderWidget={mockRenderWidget}
        />
      );

      const gridContainer = container.querySelector('.grid');
      expect(gridContainer).toBeTruthy();
      expect(gridContainer).toHaveClass('gap-4'); // Default
    });
  });

  describe('Widget rendering', () => {
    it('renders all widgets in all columns', () => {
      const columns: Column[] = [
        {
          id: 'col-1',
          width: 50,
          widgets: [
            { id: 'widget-1', type: 'text', props: {} },
            { id: 'widget-2', type: 'button', props: {} },
          ],
        },
        {
          id: 'col-2',
          width: 50,
          widgets: [{ id: 'widget-3', type: 'image', props: {} }],
        },
      ];

      const { getByTestId } = render(
        <ContainerWidget
          props={{ columns }}
          renderWidget={mockRenderWidget}
        />
      );

      expect(getByTestId('widget-widget-1')).toBeTruthy();
      expect(getByTestId('widget-widget-2')).toBeTruthy();
      expect(getByTestId('widget-widget-3')).toBeTruthy();
    });

    it('handles empty widget arrays', () => {
      const columns: Column[] = [
        { id: 'col-1', width: 100, widgets: [] },
      ];

      const { container } = render(
        <ContainerWidget
          props={{ columns }}
          renderWidget={mockRenderWidget}
        />
      );

      expect(mockRenderWidget).not.toHaveBeenCalled();
      expect(container.querySelector('.space-y-3')).toBeTruthy();
    });

    it('passes correct widget data to renderWidget', () => {
      const widget: WidgetBase = {
        id: 'widget-1',
        type: 'custom',
        props: { customProp: 'value' },
      };

      const columns: Column[] = [
        {
          id: 'col-1',
          width: 100,
          widgets: [widget],
        },
      ];

      render(
        <ContainerWidget
          props={{ columns }}
          renderWidget={mockRenderWidget}
        />
      );

      expect(mockRenderWidget).toHaveBeenCalledWith(widget);
    });
  });

  describe('Edge cases', () => {
    it('handles undefined columns prop', () => {
      const { container } = render(
        <ContainerWidget
          props={{}}
          renderWidget={mockRenderWidget}
        />
      );

      const gridContainer = container.querySelector('.grid');
      expect(gridContainer).toBeTruthy();
    });

    it('handles null background', () => {
      const { container } = render(
        <ContainerWidget
          props={{ background: null as unknown as string, columns: [] }}
          renderWidget={mockRenderWidget}
        />
      );

      const innerDiv = container.querySelector('[style*="background"]');
      expect(innerDiv).toBeTruthy();
      // Should use default
      expect(innerDiv).toHaveStyle({ backgroundColor: '#ffffff' });
    });

    it('handles negative padding values', () => {
      const { container } = render(
        <ContainerWidget
          props={{
            paddingTop: -10,
            paddingBottom: -20,
            columns: [],
          }}
          renderWidget={mockRenderWidget}
        />
      );

      const innerDiv = container.querySelector('[style*="padding"]');
      expect(innerDiv).toBeTruthy();
      // Should render with negative values (browser will handle)
      expect(innerDiv).toHaveStyle({
        paddingTop: '-10px',
        paddingBottom: '-20px',
      });
    });

    it('handles very large padding values', () => {
      const { container } = render(
        <ContainerWidget
          props={{
            paddingTop: 1000,
            paddingBottom: 2000,
            columns: [],
          }}
          renderWidget={mockRenderWidget}
        />
      );

      const innerDiv = container.querySelector('[style*="padding"]');
      expect(innerDiv).toBeTruthy();
      expect(innerDiv).toHaveStyle({
        paddingTop: '1000px',
        paddingBottom: '2000px',
      });
    });
  });

  describe('CSS classes', () => {
    it('applies flex and flex-wrap classes', () => {
      const { container } = render(
        <ContainerWidget
          props={{ columns: [] }}
          renderWidget={mockRenderWidget}
        />
      );

      const gridContainer = container.querySelector('.grid');
      expect(gridContainer).toBeTruthy();
    });

    it('applies space-y-3 to column content', () => {
      const columns: Column[] = [
        {
          id: 'col-1',
          width: 100,
          widgets: [
            { id: 'widget-1', type: 'text', props: {} },
          ],
        },
      ];

      const { container } = render(
        <ContainerWidget
          props={{ columns }}
          renderWidget={mockRenderWidget}
        />
      );

      const columnContent = container.querySelector('.space-y-3');
      expect(columnContent).toBeTruthy();
    });

    it('applies border-radius from CSS variable', () => {
      const { container } = render(
        <ContainerWidget
          props={{ columns: [] }}
          renderWidget={mockRenderWidget}
        />
      );

      // The border-radius is on the first styled div (child of SmartContainer)
      const styledDiv = container.querySelector('[style*="background"]');
      expect(styledDiv).toBeTruthy();
      expect(styledDiv).toHaveStyle({
        borderRadius: 'var(--theme-border-radius, 12px)',
      });
    });
  });
});
