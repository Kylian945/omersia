import type { NextConfig } from "next";

const cspReportOnly = [
  "default-src 'self'",
  "base-uri 'self'",
  "object-src 'none'",
  "frame-ancestors 'none'",
  "img-src 'self' data: https: http:",
  "font-src 'self' data: https:",
  "style-src 'self' 'unsafe-inline'",
  "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
  "connect-src 'self' https: http: ws: wss:",
].join("; ");

const nextConfig: NextConfig = {
  async headers() {
    return [
      {
        source: "/:path*",
        headers: [
          {
            key: "Content-Security-Policy-Report-Only",
            value: cspReportOnly,
          },
          {
            key: "X-Frame-Options",
            value: "DENY",
          },
          {
            key: "X-Content-Type-Options",
            value: "nosniff",
          },
          {
            key: "Referrer-Policy",
            value: "strict-origin-when-cross-origin",
          },
        ],
      },
    ];
  },
  images: {
    // Allow local images with query strings (for image proxy)
    localPatterns: [
      {
        pathname: "/api/**",
        // Omitting search allows all query strings
      },
    ],
    remotePatterns: [
      // Local development patterns
      {
        protocol: "http",
        hostname: "localhost",
        port: "8000",
        pathname: "/**",
      },
      {
        protocol: "http",
        hostname: "localhost",
        pathname: "/**",
      },
      {
        protocol: "http",
        hostname: "127.0.0.1",
        port: "8000",
        pathname: "/**",
      },
      {
        protocol: "http",
        hostname: "127.0.0.1",
        pathname: "/**",
      },
      // Docker internal
      {
        protocol: "http",
        hostname: "backend",
        port: "8001",
        pathname: "/**",
      },
      {
        protocol: "http",
        hostname: "backend",
        pathname: "/**",
      },
      // Nginx proxy (Docker internal)
      {
        protocol: "http",
        hostname: "nginx",
        port: "80",
        pathname: "/**",
      },
      {
        protocol: "http",
        hostname: "nginx",
        pathname: "/**",
      },
      // Docker host (for server-side image fetching in Docker)
      {
        protocol: "http",
        hostname: "host.docker.internal",
        port: "8000",
        pathname: "/**",
      },
      {
        protocol: "http",
        hostname: "host.docker.internal",
        pathname: "/**",
      },
      // Allow any subdomain for production flexibility
      {
        protocol: "https",
        hostname: "**.localhost",
        pathname: "/**",
      },
    ],
    // Image optimization enabled
  },
};

export default nextConfig;
