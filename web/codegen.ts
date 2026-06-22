import type { CodegenConfig } from '@graphql-codegen/cli';

/**
 * Генерация TypeScript-типов из схемы WPGraphQL.
 * Требует запущенный WordPress. Запуск: `npm run codegen`.
 * Сгенерированные файлы кладём в lib/graphql/generated (в .gitignore не нужны —
 * можно коммитить для воспроизводимости CI; решим в Спринте 1).
 */
const config: CodegenConfig = {
  schema: process.env.WORDPRESS_GRAPHQL_ENDPOINT ?? 'http://localhost:8080/graphql',
  documents: ['app/**/*.{ts,tsx}', 'lib/**/*.{ts,tsx}', 'components/**/*.{ts,tsx}'],
  ignoreNoDocuments: true,
  generates: {
    'lib/graphql/generated/': {
      preset: 'client',
      config: {
        useTypeImports: true,
      },
    },
  },
};

export default config;
