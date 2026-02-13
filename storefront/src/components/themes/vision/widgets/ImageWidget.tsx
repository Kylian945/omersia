import { OptimizedImage } from '@/components/common/OptimizedImage';
import { getAspectRatioClass, getObjectFitClass, getObjectPositionClass } from '@/lib/widget-helpers';
import { validateAspectRatio, validateObjectFit, validateObjectPosition, validateNumericSize } from '@/lib/css-variable-sanitizer';

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
  const placeholder = (
    <div
      className="w-full h-full border border-dashed flex items-center justify-center text-xs"
      style={{
        borderRadius: 'var(--theme-border-radius, 12px)',
        backgroundColor: 'var(--theme-card-bg, #f9fafb)',
        borderColor: 'var(--theme-border-default, #e5e7eb)',
        color: 'var(--theme-muted-color, #6b7280)',
      }}
    >
      Bloc image
    </div>
  );

  if (!props?.url) {
    return placeholder;
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

  const imageClassName = `${objectFitClass} ${objectPositionClass}`;

  // next/image with fill requires a constrained container height.
  if (safeAspectRatio === 'auto' && !safeHeight) {
    return (
      <OptimizedImage
        src={props.url}
        alt={props.alt || ''}
        width={safeWidth || 1200}
        height={900}
        className={`w-full h-auto ${imageClassName}`}
        style={style}
        fallback={placeholder}
      />
    );
  }

  return (
    <div className={`relative w-full overflow-hidden ${aspectRatioClass}`} style={style}>
      <OptimizedImage
        src={props.url}
        alt={props.alt || ''}
        fill
        sizes="100vw"
        className={imageClassName}
        fallback={placeholder}
      />
    </div>
  );
}
