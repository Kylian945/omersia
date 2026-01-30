<?php

declare(strict_types=1);

namespace Omersia\Admin\Config;

class BuilderIcons
{
    /**
     * Get all available icons for the builder
     */
    public static function all(): array
    {
        return [
            // E-commerce
            ['name' => 'Truck', 'component' => 'truck'],
            ['name' => 'Package', 'component' => 'package'],
            ['name' => 'ShoppingCart', 'component' => 'shopping-cart'],
            ['name' => 'ShoppingBag', 'component' => 'shopping-bag'],
            ['name' => 'Gift', 'component' => 'gift'],
            ['name' => 'Tag', 'component' => 'tag'],
            ['name' => 'Percent', 'component' => 'percent'],

            // Payment & Security
            ['name' => 'CreditCard', 'component' => 'credit-card'],
            ['name' => 'ShieldCheck', 'component' => 'shield-check'],
            ['name' => 'Lock', 'component' => 'lock'],
            ['name' => 'DollarSign', 'component' => 'dollar-sign'],
            ['name' => 'Wallet', 'component' => 'wallet'],

            // Communication
            ['name' => 'Phone', 'component' => 'phone'],
            ['name' => 'Mail', 'component' => 'mail'],
            ['name' => 'MessageCircle', 'component' => 'message-circle'],
            ['name' => 'MessageSquare', 'component' => 'message-square'],
            ['name' => 'Send', 'component' => 'send'],
            ['name' => 'Headphones', 'component' => 'headphones'],

            // Social & Feedback
            ['name' => 'Star', 'component' => 'star'],
            ['name' => 'Heart', 'component' => 'heart'],
            ['name' => 'ThumbsUp', 'component' => 'thumbs-up'],
            ['name' => 'Award', 'component' => 'award'],
            ['name' => 'Trophy', 'component' => 'trophy'],
            ['name' => 'Smile', 'component' => 'smile'],
            ['name' => 'Users', 'component' => 'users'],

            // Time & Location
            ['name' => 'Clock', 'component' => 'clock'],
            ['name' => 'Calendar', 'component' => 'calendar'],
            ['name' => 'MapPin', 'component' => 'map-pin'],
            ['name' => 'Map', 'component' => 'map'],
            ['name' => 'Navigation', 'component' => 'navigation'],

            // Actions
            ['name' => 'Check', 'component' => 'check'],
            ['name' => 'CheckCircle', 'component' => 'check-circle'],
            ['name' => 'Zap', 'component' => 'zap'],
            ['name' => 'Sparkles', 'component' => 'sparkles'],
            ['name' => 'TrendingUp', 'component' => 'trending-up'],
            ['name' => 'ArrowRight', 'component' => 'arrow-right'],

            // Returns & Support
            ['name' => 'Undo2', 'component' => 'undo-2'],
            ['name' => 'RotateCcw', 'component' => 'rotate-ccw'],
            ['name' => 'RefreshCw', 'component' => 'refresh-cw'],
            ['name' => 'HelpCircle', 'component' => 'help-circle'],
            ['name' => 'Info', 'component' => 'info'],

            // Other
            ['name' => 'Home', 'component' => 'home'],
            ['name' => 'Bell', 'component' => 'bell'],
            ['name' => 'Settings', 'component' => 'settings'],
            ['name' => 'Eye', 'component' => 'eye'],
            ['name' => 'Download', 'component' => 'download'],
            ['name' => 'Upload', 'component' => 'upload'],
            ['name' => 'Search', 'component' => 'search'],
            ['name' => 'Filter', 'component' => 'filter'],
        ];
    }
}
