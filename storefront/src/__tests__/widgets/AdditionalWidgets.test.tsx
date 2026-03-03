import { describe, it, expect } from '@jest/globals';
import { render, screen, fireEvent } from '@testing-library/react';
import { ButtonWidget } from '@/components/themes/vision/widgets/ButtonWidget';
import { VideoWidget } from '@/components/themes/vision/widgets/VideoWidget';
import { HeroBanner } from '@/components/themes/vision/widgets/HeroBannerWidget';
import { AccordionWidget } from '@/components/themes/vision/widgets/AccordionWidget';
import { TabsWidget } from '@/components/themes/vision/widgets/TabsWidget';
import { Testimonials } from '@/components/themes/vision/widgets/TestimonialsWidget';
import { FeaturesBar } from '@/components/themes/vision/widgets/FeaturesBarWidget';

describe('ButtonWidget', () => {
  it('renders default label and href', () => {
    const { container } = render(<ButtonWidget props={{}} />);
    expect(screen.getByText('En savoir plus')).toBeTruthy();

    const link = container.querySelector('a');
    expect(link?.getAttribute('href')).toBe('#');
  });

  it('renders custom label and href', () => {
    const { container } = render(<ButtonWidget props={{ label: 'Acheter', url: '/products' }} />);
    expect(screen.getByText('Acheter')).toBeTruthy();

    const link = container.querySelector('a');
    expect(link?.getAttribute('href')).toBe('/products');
  });
});

describe('VideoWidget', () => {
  it('renders placeholder when no url is provided', () => {
    render(<VideoWidget props={{}} />);
    expect(screen.getByText('Vidéo')).toBeTruthy();
  });

  it('renders an error for invalid youtube url', () => {
    render(<VideoWidget props={{ type: 'youtube', url: 'https://youtube.com/watch' }} />);
    expect(screen.getByText('URL vidéo invalide')).toBeTruthy();
  });

  it('renders youtube iframe for valid url', () => {
    const { container } = render(
      <VideoWidget props={{ type: 'youtube', url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' }} />
    );

    const iframe = container.querySelector('iframe');
    expect(iframe).toBeTruthy();
    expect(iframe?.getAttribute('src')).toContain('youtube.com/embed/dQw4w9WgXcQ');
  });

  it('renders vimeo iframe for valid url', () => {
    const { container } = render(
      <VideoWidget props={{ type: 'vimeo', url: 'https://vimeo.com/123456789' }} />
    );

    const iframe = container.querySelector('iframe');
    expect(iframe).toBeTruthy();
    expect(iframe?.getAttribute('src')).toContain('player.vimeo.com/video/123456789');
  });
});

describe('HeroBanner', () => {
  it('renders default title', () => {
    render(<HeroBanner />);
    expect(screen.getByText('Bienvenue sur notre boutique')).toBeTruthy();
  });

  it('renders badge and CTA labels when provided', () => {
    render(
      <HeroBanner
        badge="Nouveau"
        primaryCta={{ text: 'Découvrir', href: '/products' }}
        secondaryCta={{ text: 'Contact', href: '/contact' }}
      />
    );

    expect(screen.getByText('Nouveau')).toBeTruthy();
    expect(screen.getByText('Découvrir')).toBeTruthy();
    expect(screen.getByText('Contact')).toBeTruthy();
  });
});

describe('AccordionWidget', () => {
  it('renders null with empty items', () => {
    const { container } = render(<AccordionWidget props={{ items: [] }} />);
    expect(container.firstChild).toBeNull();
  });

  it('renders accordion items', () => {
    render(
      <AccordionWidget
        props={{
          items: [
            { title: 'Question 1', content: 'Réponse 1' },
            { title: 'Question 2', content: 'Réponse 2' },
          ],
        }}
      />
    );

    expect(screen.getByText('Question 1')).toBeTruthy();
    expect(screen.getByText('Question 2')).toBeTruthy();
    expect(screen.getByText('Réponse 1')).toBeTruthy();
  });
});

describe('TabsWidget', () => {
  it('renders first tab content by default and switches on click', () => {
    render(
      <TabsWidget
        props={{
          items: [
            { title: 'Tab A', content: 'Contenu A' },
            { title: 'Tab B', content: 'Contenu B' },
          ],
        }}
      />
    );

    expect(screen.getByText('Contenu A')).toBeTruthy();
    fireEvent.click(screen.getByRole('button', { name: 'Tab B' }));
    expect(screen.getByText('Contenu B')).toBeTruthy();
  });
});

describe('Testimonials', () => {
  it('renders testimonials content', () => {
    render(
      <Testimonials
        title="Avis clients"
        testimonials={[
          { name: 'Alice', role: 'Cliente', content: 'Excellent produit', rating: 5 },
        ]}
      />
    );

    expect(screen.getByText('Avis clients')).toBeTruthy();
    expect(screen.getByText('Alice')).toBeTruthy();
    expect(screen.getByText('Cliente')).toBeTruthy();
    expect(screen.getByText('“Excellent produit”')).toBeTruthy();
  });
});

describe('FeaturesBar', () => {
  it('renders default features', () => {
    render(<FeaturesBar />);
    expect(screen.getByText('Livraison gratuite')).toBeTruthy();
  });

  it('renders custom features', () => {
    render(
      <FeaturesBar
        features={[
          {
            icon: 'Truck',
            title: 'Expédition rapide',
            description: 'Sous 24h',
          },
        ]}
      />
    );

    expect(screen.getByText('Expédition rapide')).toBeTruthy();
    expect(screen.getByText('Sous 24h')).toBeTruthy();
  });
});
