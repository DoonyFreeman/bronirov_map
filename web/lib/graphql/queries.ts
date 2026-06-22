/** GraphQL-запросы каталога. */

export const COMPANY_CARD_FRAGMENT = /* GraphQL */ `
  fragment CompanyCard on Company {
    databaseId
    title
    slug
    excerpt
    phone
    address
    priceFrom
    latitude
    longitude
    featuredImage {
      node {
        sourceUrl
        altText
      }
    }
    cities {
      nodes {
        name
        slug
      }
    }
    serviceCategories {
      nodes {
        name
        slug
      }
    }
  }
`;

export const COMPANIES_QUERY = /* GraphQL */ `
  ${COMPANY_CARD_FRAGMENT}
  query Companies($city: String, $category: String, $first: Int = 24) {
    companies(first: $first, where: { city: $city, category: $category }) {
      nodes {
        ...CompanyCard
      }
    }
  }
`;

export const CATALOG_FILTERS_QUERY = /* GraphQL */ `
  query CatalogFilters {
    cities(first: 100) {
      nodes {
        name
        slug
        count
      }
    }
    serviceCategories(first: 100) {
      nodes {
        name
        slug
        count
      }
    }
  }
`;
