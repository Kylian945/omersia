import { describe, it, expect } from '@jest/globals';
import { render, screen } from '@testing-library/react';
import { HeadingWidget } from '@/components/themes/vision/widgets/HeadingWidget';
import { TextWidget } from '@/components/themes/vision/widgets/TextWidget';
import { SpacerWidget } from '@/components/themes/vision/widgets/SpacerWidget';

describe('HeadingWidget', () => {
  it('renders without crash with default props', () => {
    const { container } = render(
      <HeadingWidget props={{}} />
    );
    expect(container.firstChild).not.toBeNull();
  });

  it('renders the provided text', () => {
    render(
      <HeadingWidget props={{ text: 'Mon Titre', tag: 'h1' }} />
    );
    expect(screen.getByText('Mon Titre')).toBeTruthy();
  });

  it('renders as h1 tag when tag is h1', () => {
    const { container } = render(
      <HeadingWidget props={{ text: 'Titre H1', tag: 'h1' }} />
    );
    const heading = container.querySelector('h1');
    expect(heading).not.toBeNull();
    expect(heading?.textContent).toBe('Titre H1');
  });

  it('renders as h2 tag by default when no tag provided', () => {
    const { container } = render(
      <HeadingWidget props={{ text: 'Titre H2 par défaut' }} />
    );
    const heading = container.querySelector('h2');
    expect(heading).not.toBeNull();
  });

  it('renders as h3 tag when tag is h3', () => {
    const { container } = render(
      <HeadingWidget props={{ text: 'Titre H3', tag: 'h3' }} />
    );
    const heading = container.querySelector('h3');
    expect(heading).not.toBeNull();
  });

  it('renders default text when text prop is empty', () => {
    const { container } = render(
      <HeadingWidget props={{ tag: 'h2' }} />
    );
    // When text is undefined, it falls back to "Titre"
    expect(container.textContent).toBe('Titre');
  });

  it('applies text-center class when align is center', () => {
    const { container } = render(
      <HeadingWidget props={{ text: 'Centré', tag: 'h2', align: 'center' }} />
    );
    const heading = container.querySelector('h2');
    expect(heading?.className).toContain('text-center');
  });

  it('applies text-left class by default', () => {
    const { container } = render(
      <HeadingWidget props={{ text: 'Gauche', tag: 'h2' }} />
    );
    const heading = container.querySelector('h2');
    expect(heading?.className).toContain('text-left');
  });
});

describe('TextWidget', () => {
  it('renders without crash with empty props', () => {
    const { container } = render(
      <TextWidget props={{}} />
    );
    expect(container.firstChild).not.toBeNull();
  });

  it('renders HTML content', () => {
    const { container } = render(
      <TextWidget props={{ html: '<p>Paragraphe de test</p>' }} />
    );
    const paragraph = container.querySelector('p');
    expect(paragraph).not.toBeNull();
    expect(paragraph?.textContent).toBe('Paragraphe de test');
  });

  it('sanitizes HTML content to prevent XSS', () => {
    const { container } = render(
      <TextWidget props={{ html: '<p>Safe</p><script>alert("xss")</script>' }} />
    );
    // Script tag should be removed by sanitizer
    const scripts = container.querySelectorAll('script');
    expect(scripts.length).toBe(0);
  });

  it('renders a div wrapper', () => {
    const { container } = render(
      <TextWidget props={{ html: '<p>Contenu</p>' }} />
    );
    expect(container.querySelector('div')).not.toBeNull();
  });

  it('renders with prose class for typography', () => {
    const { container } = render(
      <TextWidget props={{ html: '<p>Text</p>' }} />
    );
    const div = container.querySelector('div');
    expect(div?.className).toContain('prose');
  });

  it('handles undefined html gracefully', () => {
    // Should not throw when html is undefined
    expect(() => render(<TextWidget props={{}} />)).not.toThrow();
  });
});

describe('SpacerWidget', () => {
  it('renders without crash with default props', () => {
    const { container } = render(
      <SpacerWidget props={{}} />
    );
    expect(container.firstChild).not.toBeNull();
  });

  it('applies the provided size as height in pixels', () => {
    const { container } = render(
      <SpacerWidget props={{ size: 40 }} />
    );
    const spacer = container.firstChild as HTMLElement;
    expect(spacer?.style.height).toBe('40px');
  });

  it('uses default height of 32px when no size provided', () => {
    const { container } = render(
      <SpacerWidget props={{}} />
    );
    const spacer = container.firstChild as HTMLElement;
    expect(spacer?.style.height).toBe('32px');
  });

  it('applies custom size correctly', () => {
    const { container } = render(
      <SpacerWidget props={{ size: 80 }} />
    );
    const spacer = container.firstChild as HTMLElement;
    expect(spacer?.style.height).toBe('80px');
  });

  it('handles size of 0', () => {
    const { container } = render(
      <SpacerWidget props={{ size: 0 }} />
    );
    // size=0 is a valid number, so it renders as 0px
    const spacer = container.firstChild as HTMLElement;
    expect(spacer?.style.height).toBe('0px');
  });

  it('renders a div element', () => {
    const { container } = render(
      <SpacerWidget props={{ size: 16 }} />
    );
    expect(container.querySelector('div')).not.toBeNull();
  });
});
