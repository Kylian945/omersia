import Link from "next/link";
import { ReactNode } from "react";

type ButtonVariant = "primary" | "secondary";
type ButtonSize = "sm" | "md" | "lg";

interface ButtonProps {
  children: ReactNode;
  href?: string;
  variant?: ButtonVariant;
  size?: ButtonSize;
  className?: string;
  onClick?: () => void;
  type?: "button" | "submit" | "reset";
  disabled?: boolean;
}

export function Button({
  children,
  href,
  variant = "primary",
  size = "md",
  className = "",
  onClick,
  type = "button",
  disabled = false,
}: ButtonProps) {
  const sizeClasses = {
    sm: "px-4 py-1.5 text-xs",
    md: "px-4 py-2 text-sm",
    lg: "px-6 py-2.5 text-base",
  };

  const variantClasses = {
    primary: disabled
      ? "border bg-[var(--theme-primary)] border-[var(--theme-primary)] text-[var(--theme-button-primary-text,#ffffff)] transition-opacity"
      : "border bg-[var(--theme-primary)] border-[var(--theme-primary)] text-[var(--theme-button-primary-text,#ffffff)] hover:opacity-90 transition-opacity",
    secondary: disabled
      ? "border border-[var(--theme-primary,#000)] bg-white text-[var(--theme-button-secondary-text,var(--theme-primary,#000))] transition-colors"
      : "border border-[var(--theme-primary,#000)] bg-white text-[var(--theme-button-secondary-text,var(--theme-primary,#000))] hover:bg-gray-100 transition-colors",
  };

  const baseClassName = `inline-flex items-center justify-center font-medium ${sizeClasses[size]} ${variantClasses[variant]} ${disabled ? "opacity-50 cursor-not-allowed" : ""} ${className}`;

  const buttonStyle = {
    borderRadius: "var(--theme-button-radius, 8px)",
  };

  if (href && !disabled) {
    return (
      <Link href={href} className={baseClassName} style={buttonStyle}>
        {children}
      </Link>
    );
  }

  if (href && disabled) {
    return (
      <span className={baseClassName} style={buttonStyle}>
        {children}
      </span>
    );
  }

  return (
    <button
      type={type}
      onClick={onClick}
      className={baseClassName}
      style={buttonStyle}
      disabled={disabled}
    >
      {children}
    </button>
  );
}
