import type { NextConfig } from 'next';

const nextConfig: NextConfig = {
  reactStrictMode: true,
  // На машине есть посторонний lockfile выше по дереву — фиксируем корень на web/.
  outputFileTracingRoot: import.meta.dirname,
  images: {
    // Разрешаем картинки из WordPress (uploads). Хост берём из env.
    remotePatterns: [
      {
        protocol: 'http',
        hostname: 'localhost',
        port: '8080',
        pathname: '/wp-content/uploads/**',
      },
    ],
  },
};

export default nextConfig;
