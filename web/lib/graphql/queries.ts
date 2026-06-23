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
    averageRating
    reviewCount
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

export const COMPANY_BY_SLUG_QUERY = /* GraphQL */ `
  ${COMPANY_CARD_FRAGMENT}
  query CompanyBySlug($slug: ID!) {
    company(id: $slug, idType: SLUG) {
      ...CompanyCard
      content
      services {
        databaseId
        title
        price
        duration
      }
      reviews {
        databaseId
        date
        author
        rating
        text
        verified
      }
      hours {
        day
        open
        close
      }
      gallery {
        sourceUrl
        altText
      }
    }
  }
`;

export const ALL_COMPANY_SLUGS_QUERY = /* GraphQL */ `
  query AllCompanySlugs {
    companies(first: 1000) {
      nodes {
        slug
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
