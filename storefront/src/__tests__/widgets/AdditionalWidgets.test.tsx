import { describe, it, expect } from '@jest/globals';
import { render, screen, fireEvent } from '@testing-library/react';
import { ButtonWidget } from '@/components/themes/vision/widgets/ButtonWidget';
import { VideoWidget } from '@/components/themes/vision/widgets/VideoWidget';
import { HeroBanner } from '@/components/themes/vision/widgets/HeroBannerWidget';
import { AccordionWidget } from '@/components/themes/vision/widgets/AccordionWidget';
import { TabsWidget } from '@/components/themes/vision/widgets/TabsWidget';
import { Testimonials } from '@/components/themes/vision/widgets/TestimonialsWidget';
import { FeaturesBar } from '@/components/themes/vision/widgets/FeaturesBarWidget';

// ===== ButtonWidget =====
describe('ButtonWidget', () => {
  it('renders default label when no label provided', () => {
    render(<ButtonWidget props={{}} />);
    expect(screen.getByText('En savoir plus')).toBeTruthy();
  });

  it('renders custom label', () => {
    render(<ButtonWidget props={{ label: 'Acheter' }} />);
    expect(screen.getByText('Acheter')).toBeTruthy();
  });

  it('renders link with default href #', () => {
    const { container } = render(<ButtonWidget props={{}} />);
    const link = container.querySelector('a');
    expect(link).toBeTruthy();
    expect(link?.getAttribute('href')).toBe('#');
  });

  it('renders link with custom url', () => {
    const { container } = render(<ButtonWidget props={{ url: '/products' }} />);
    const link = container.querySelector('a');
    expect(link?.getAttribute('href')).toBe('/products');
  });
});

// ===== VideoWidget =====
describe('VideoWidget', () => {
  it('renders placeholder when no url', () => {
    render(<VideoWidget props={{}} />);
    expect(screen.getByText('Vidéo')).toBeTruthy();
  });

  it('renders error for invalid youtube url', () => {
    render(<VideoWidget props={{ type: 'youtube', url: 'https://youtube.com/watch' }} />);
    expect(screen.getByText('URL vidéo invalide')).toBeTruthy();
  });

  it('renders iframe for valid youtube url', () => {
    const { container } = render(
      <VideoWidget props={{ type: 'youtube', url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' }} />
    );
    const iframe = container.querySelector('iframe');
    expect(iframe).toBeTruthy();
    expect(iframe?.getAttribute('src')).toContain('youtube.com/embed/dQw4w9WgXcQ');
  });

  it('renders iframe for valid vimeo url', () => {
    const { container } = render(
      <VideoWidget props={{ type: 'vimeo', url: 'https://vimeo.com/123456789' }} />
    );
    const iframe = container.querySelector('iframe');
    expect(iframe).toBeTruthy();
    expect(iframe?.getAttribute('src')).toContain('player.