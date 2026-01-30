/**
 * @jest-environment node
 */

import {
  getGapClass,
  getAlignmentClass,
  getAspectRatioClass,
  getObjectFitClass,
  getObjectPositionClass,
} from '../widget-helpers';

describe('Widget Helpers', () => {
  describe('getGapClass', () => {
    test('returns correct gap classes', () => {
      expect(getGapClass('none')).toBe('gap-0');
      expect(getGapClass('xs')).toBe('gap-1');
      expect(getGapClass('sm')).toBe('gap-2');
      expect(getGapClass('md')).toBe('gap-4');
      expect(getGapClass('lg')).toBe('gap-6');
      expect(getGapClass('xl')).toBe('gap-8');
    });

    test('returns default gap-4 for invalid values', () => {
      expect(getGapClass('invalid')).toBe('gap-4');
      expect(getGapClass(undefined)).toBe('gap-4');
    });
  });

  describe('getAlignmentClass', () => {
    test('returns correct alignment classes', () => {
      expect(getAlignmentClass('start')).toBe('items-start');
      expect(getAlignmentClass('center')).toBe('items-center');
      expect(getAlignmentClass('end')).toBe('items-end');
      expect(getAlignmentClass('stretch')).toBe('items-stretch');
      expect(getAlignmentClass('baseline')).toBe('items-baseline');
    });

    test('returns default items-stretch for invalid values', () => {
      expect(getAlignmentClass('invalid')).toBe('items-stretch');
      expect(getAlignmentClass(undefined)).toBe('items-stretch');
    });
  });

  describe('getAspectRatioClass', () => {
    test('returns correct aspect ratio classes', () => {
      expect(getAspectRatioClass('1:1')).toBe('aspect-square');
      expect(getAspectRatioClass('4:3')).toBe('aspect-[4/3]');
      expect(getAspectRatioClass('16:9')).toBe('aspect-video');
      expect(getAspectRatioClass('2:1')).toBe('aspect-[2/1]');
      expect(getAspectRatioClass('21:9')).toBe('aspect-[21/9]');
      expect(getAspectRatioClass('auto')).toBe('h-auto');
    });

    test('returns default h-auto for invalid values', () => {
      expect(getAspectRatioClass('invalid')).toBe('h-auto');
      expect(getAspectRatioClass(undefined)).toBe('h-auto');
    });
  });

  describe('getObjectFitClass', () => {
    test('returns correct object-fit classes', () => {
      expect(getObjectFitClass('contain')).toBe('object-contain');
      expect(getObjectFitClass('cover')).toBe('object-cover');
      expect(getObjectFitClass('fill')).toBe('object-fill');
      expect(getObjectFitClass('scale-down')).toBe('object-scale-down');
    });

    test('returns default object-cover for invalid values', () => {
      expect(getObjectFitClass('invalid')).toBe('object-cover');
      expect(getObjectFitClass(undefined)).toBe('object-cover');
    });
  });

  describe('getObjectPositionClass', () => {
    test('returns correct object-position classes', () => {
      expect(getObjectPositionClass('top')).toBe('object-top');
      expect(getObjectPositionClass('center')).toBe('object-center');
      expect(getObjectPositionClass('bottom')).toBe('object-bottom');
      expect(getObjectPositionClass('left')).toBe('object-left');
      expect(getObjectPositionClass('right')).toBe('object-right');
    });

    test('returns default object-center for invalid values', () => {
      expect(getObjectPositionClass('invalid')).toBe('object-center');
      expect(getObjectPositionClass(undefined)).toBe('object-center');
    });
  });

  describe('Edge cases and backward compatibility', () => {
    test('all helpers handle empty strings', () => {
      expect(getGapClass('')).toBe('gap-4');
      expect(getAlignmentClass('')).toBe('items-stretch');
      expect(getAspectRatioClass('')).toBe('h-auto');
      expect(getObjectFitClass('')).toBe('object-cover');
      expect(getObjectPositionClass('')).toBe('object-center');
    });

    test('all helpers are case-sensitive', () => {
      expect(getGapClass('MD')).toBe('gap-4'); // Falls back to default
      expect(getAlignmentClass('CENTER')).toBe('items-stretch');
      expect(getAspectRatioClass('AUTO')).toBe('h-auto');
      expect(getObjectFitClass('COVER')).toBe('object-cover');
    });

    test('all helpers return valid Tailwind classes', () => {
      expect(getGapClass('random')).toMatch(/^gap-/);
      expect(getAlignmentClass('random')).toMatch(/^items-/);
      expect(getAspectRatioClass('random')).toMatch(/^(h-auto|aspect-)/);
      expect(getObjectFitClass('random')).toMatch(/^object-/);
      expect(getObjectPositionClass('random')).toMatch(/^object-/);
    });

    test('can be chained for complex styling', () => {
      const containerClasses = `flex ${getGapClass('lg')} ${getAlignmentClass('center')}`;
      expect(containerClasses).toBe('flex gap-6 items-center');

      const imageClasses = `${getAspectRatioClass('16:9')} ${getObjectFitClass('cover')} ${getObjectPositionClass('top')}`;
      expect(imageClasses).toBe('aspect-video object-cover object-top');
    });
  });
});
