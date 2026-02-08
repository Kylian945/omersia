import { getAspectRatioClass, getObjectFitClass, getObjectPositionClass } from '@/lib/widget-helpers';
import { validateAspectRatio, validateObjectFit, validateObjectPosition, validateNumericSize } from '@/lib/css-variable-sanitizer';
import Image from 'next/image';
import { normalizeImageUrl } from '@/lib/image-loader';

interface ImageWidgetProps {
  props: {
    url?: string;
    alt?: string;
    aspectRatio?: '1:1' | '4:3' | '16:9' | '2:1' | '21:9' | 'auto';
    height?: number;
    width?: number;
    objectFit?: 'contain' | 'cover' | 'fill' | 'scale-down';
    objectPosition?: 'top' | 'center' | 'bottom' | 'left' | 'right';
  };
}

export function ImageWidget({ props }: ImageWidgetProps) {
  if (!props?.url) {
    return (
      <div
        className="w-full h-full border border-dashed flex items-center justify-center text-xs"
        style={{
          borderRadius: 'var(--theme-border-radius, 12px)',
          backgroundColor: 'var(--theme-card-bg, #f9fafb)',
          borderColor: 'var(--theme-border-default, #e5e7eb)',
          color: 'var(--theme-muted-color, #6b7280)',
        }}
      >
        Image
      </div>
    );
  }

  // Validate all props for security
  const safeAspectRatio = validateAspectRatio(props.aspectRatio);
  const safeObjectFit = validateObjectFit(props.objectFit);
  const safeObjectPosition = validateObjectPosition(props.objectPosition);
  const safeHeight = validateNumericSize(props.height);
  const safeWidth = validateNumericSize(props.width);

  // Get Tailwind CSS classes
  const aspectRatioClass = getAspectRatioClass(safeAspectRatio);
  const objectFitClass = getObjectFitClass(safeObjectFit);
  const objectPositionClass = getObjectPositionClass(safeObjectPosition);

  // Build inline styles
  const style: React.CSSProperties = {
    borderRadius: 'var(--theme-border-radius, 12px)',
  };

  if (safeHeight) {
    style.height = `${safeHeight}px`;
  }

  if (safeWidth) {
    style.width = `${safeWidth}px`;
  }

  const normalizedUrl = normalizeImageUrl(props.url);

  return (
    <div
      className={`relative w-full ${aspectRatioClass}`}
      style={style}
    >
      <Image
        src={normalizedUrl}
        alt={props.alt || ""}
        fill
        sizes="100vw"
        unoptimized
        className={`${objectFitClass} ${objectPositionClass}`}
      />
    </div>
  );
}
