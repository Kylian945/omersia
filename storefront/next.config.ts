import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  /* config options here */
  images: {
    remotePatterns: [
      {
        protocol: "http",
        hostname: "localhost",
        port: "8000",
        pathname: "/storage/**", // autorise toutes les images dans /storage/
      },
    ],
    unoptimized: true,
  },
};

export default nextConfig;
