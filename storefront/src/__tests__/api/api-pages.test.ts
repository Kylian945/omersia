/** @jest-environment node */

import { describe, it, expect, jest, beforeEach } from '@jest/globals';

jest.mock('@/lib/api-http', () => ({
  apiJson: jest.fn(),
}));

// eslint-disable-next-line @typescript-eslint/no-var-requires
const { getPageBySlug } = require('@/lib/api-pages') as {
  getPageBySlug: (slug: string, locale?: string) => Promise<unknown>;
};
// eslint-disable-next-line @typescript-eslint/no-var-requires
const { apiJson } = require('@/lib/api-http') as { apiJson: jest.Mock };
const mockApiJson = apiJson;

type MockPageResponse = {
  id: number;
  slug: string;
  title: string;
  meta_title?: string | null;
  meta_description?: string | null;
  layout?: { sections: unknown[] };
  blocks?: { sections: unknown[] };
  content_json?: { sections: unknown[] };
};

function okResponse(data: MockPageResponse) {
  return { res: { ok: true, status: 200 } as Response, data };
}
function errorResponse(status = 404) {
  return { res: { ok: false, status } as Response, data: null };
}

const basePage: MockPageResponse = {
  id: 1,
  slug: 'about',
  title: 'A propos',
  meta_title: 'SEO Title',
  meta_description: 'SEO Desc',
  layout: { sections: [] },
};

describe('getPageBySlug', () => {
  beforeEach(() => {
    mockApiJson.mockReset();
  });

  describe('success cases', () => {
    it('returns a Page object when ok', async () => {
      mockApiJson.mockResolvedValueOnce(okResponse(basePage) as never);
      const result = await getPageBySlug('about');
      expect(result).not.toBeNull();
      expect(result?.id).toBe(1);
      expect(result?.slug).toBe('about');
      expect(result?.meta_title).toBe('SEO Title');
    });

    it('calls apiJson with correct path and locale', async () => {
      mockApiJson.mockResolvedValueOnce(okResponse(basePage) as never);
      await getPageBySlug('about', 'fr');
      expect(mockApiJson).toHaveBeenCalledWith(
        '/pages/about?locale=fr',
        expect.objectContaining({ cache: 'no-store' })
      );
    });

    it('defaults to locale fr', async () => {
      mockApiJson.mockResolvedValueOnce(okResponse(basePage) as never);
      await getPageBySlug('about');
      const arg = (mockApiJson.mock.calls[0] as unknown[])[0] as string;
      expect(arg).toContain('locale=fr');
    });

    it('uses layout field when present', async () => {
      const layout = { sections: [{ id: 's1', columns: [] }] };
      mockApiJson.mockResolvedValueOnce(okResponse({ ...basePage, layout }) as never);
      const result = await getPageBySlug('home');
      expect(result?.layout).toEqual(layout);
    });

    it('falls back to blocks when layout absent', async () => {
      const blocks = { sections: [{ id: 's2', columns: [] }] };
      mockApiJson.mockResolvedValueOnce(
        okResponse({ id: 5, slug: 'legal', title: 'Legal', blocks }) as never
      );
      const result = await getPageBySlug('legal');
      expect(result?.layout).toEqual(blocks);
    });

    it('falls back to content_json when layout and blocks absent', async () => {
      const content_json = { sections: [{ id: 's3', columns: [] }] };
      mockApiJson.mockResolvedValueOnce(
        okResponse({ id: 6, slug: 'privacy', title: 'Privacy', content_json }) as never
      );
      const result = await getPageBySlug('privacy');
      expect(result?.layout).toEqual(content_json);
    });

    it('returns empty sections when all layout fields absent', async () => {
      mockApiJson.mockResolvedValueOnce(
        okResponse({ id: 7, slug: 'terms', title: 'CGV' }) as never
      );
      const result = await getPageBySlug('terms');
      expect(result?.layout).toEqual({ sections: [] });
    });

    it('layout takes priority over blocks', async () => {
      const layout = { sections: [{ id: 'from-layout', columns: [] }] };
      const blocks = { sections: [{ id: 'from-blocks', columns: [] }] };
      mockApiJson.mockResolvedValueOnce(
        okResponse({ id: 13, slug: 'dual', title: 'Dual', layout, blocks }) as never
      );
      const result = await getPageBySlug('dual');
      expect(result?.layout).toEqual(layout);
    });
  });

  describe('error cases', () => {
    it('returns null on 404', async () => {
      mockApiJson.mockResolvedValueOnce(errorResponse(404) as never);
      expect(await getPageBySlug('missing')).toBeNull();
    });

    it('returns null on 500', async () => {
      mockApiJson.mockResolvedValueOnce(errorResponse(500) as never);
      expect(await getPageBySlug('broken')).toBeNull();
    });

    it('returns null when data is null with ok response', async () => {
      mockApiJson.mockResolvedValueOnce({
        res: { ok: true } as Response,
        data: null,
      } as never);
      expect(await getPageBySlug('empty')).toBeNull();
    });
  });

  describe('locale handling', () => {
    it('uses custom locale in path', async () => {
      mockApiJson.mockResolvedValueOnce(okResponse(basePage) as never);
      await getPageBySlug('about', 'en');
      expect(mockApiJson).toHaveBeenCalledWith(
        '/pages/about?locale=en',
        expect.any(Object)
      );
    });
  });
});
