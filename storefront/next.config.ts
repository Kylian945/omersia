import type { NextConfig } from "next";

const nextConfig: NextConfig = {
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
