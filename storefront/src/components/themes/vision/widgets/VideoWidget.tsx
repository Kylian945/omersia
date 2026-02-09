import { getPaddingClasses, getMarginClasses } from "@/lib/widget-helpers";
import { validateSpacingConfig } from "@/lib/css-variable-sanitizer";

interface VideoWidgetProps {
  props: {
    type?: string;
    url?: string;
    aspectRatio?: string;
    autoplay?: boolean;
    loop?: boolean;
    muted?: boolean;
    padding?: Record<string, unknown>;
    margin?: Record<string, unknown>;
  };
}

export function VideoWidget({ props }: VideoWidgetProps) {
  const type = props?.type || 'youtube';
  const url = props?.url || '';
  const aspectRatio = props?.aspectRatio || '16/9';
  const autoplay = props?.autoplay || false;
  const loop = props?.loop || false;
  const muted = props?.muted || false;

  // Validate and get spacing classes
  const paddingConfig = validateSpacingConfig(props?.padding);
  const marginConfig = validateSpacingConfig(props?.margin);
  const paddingClasses = getPaddingClasses(paddingConfig);
  const marginClasses = getMarginClasses(marginConfig);
  const spacingClasses = `${paddingClasses} ${marginClasses}`.trim();

  if (!url) {
    return (
      <div
        className={`w-full border border-dashed flex items-center justify-center text-xs ${spacingClasses}`}
        style={{
          aspectRatio,
          borderRadius: 'var(--theme-border-radius, 12px)',
          backgroundColor: 'var(--theme-card-bg, #f9fafb)',
          borderColor: 'var(--theme-border-default, #e5e7eb)',
          color: 'var(--theme-muted-color, #6b7280)',
        }}
      >
        Vidéo
      </div>
    );
  }

  // Extract video ID for YouTube/Vimeo
  const getEmbedUrl = () => {
    if (type === 'youtube') {
      const videoId = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&]+)/)?.[1];
      if (!videoId) return null;
      return `https://www.youtube.com/embed/${videoId}`;
    }

    if (type === 'vimeo') {
      const videoId = url.match(/vimeo\.com\/(\d+)/)?.[1];
      if (!videoId) return null;
      return `https://player.vimeo.com/video/${videoId}`;
    }

    return url;
  };

  const embedUrl = getEmbedUrl();

  if (!embedUrl) {
    return (
      <div
        className={`w-full border flex items-center justify-center text-xs p-4 ${spacingClasses}`}
        style={{
          aspectRatio,
          borderRadius: 'var(--theme-border-radius, 12px)',
          backgroundColor: 'var(--theme-error-bg, #fee2e2)',
          borderColor: 'var(--theme-error-color, #ef4444)',
          color: 'var(--theme-error-color, #ef4444)',
        }}
      >
        URL vidéo invalide
      </div>
    );
  }

  if (type === 'upload') {
     
    return (
      <video
        src={embedUrl}
        controls
        autoPlay={autoplay}
        loop={loop}
        muted={muted}
        className={`w-full h-auto ${spacingClasses}`}
        style={{
          aspectRatio,
          borderRadius: 'var(--theme-border-radius, 12px)',
        }}
      />
    );
  }

  // YouTube or Vimeo iframe
  return (
    <div
      className={`relative w-full overflow-hidden ${spacingClasses}`}
      style={{
        aspectRatio,
        borderRadius: 'var(--theme-border-radius, 12px)',
      }}
    >
      <iframe
        src={embedUrl}
        className="absolute inset-0 w-full h-full"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
        allowFullScreen
      />
    </div>
  );
}
