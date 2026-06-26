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

export const SERVICE_FOR_BOOKING_QUERY = /* GraphQL */ `
  query ServiceForBooking($id: ID!) {
    service(id: $id, idType: DATABASE_ID) {
      databaseId
      title
      price
      duration
      company {
        title
        slug
      }
    }
  }
`;

export const AVAILABLE_SLOTS_QUERY = /* GraphQL */ `
  query AvailableSlots($serviceId: ID!, $date: String!) {
    availableSlots(serviceId: $serviceId, date: $date)
  }
`;

export const CREATE_BOOKING_MUTATION = /* GraphQL */ `
  mutation CreateServiceBooking($input: CreateServiceBookingInput!) {
    createServiceBooking(input: $input) {
      bookingDatabaseId
      status
      date
      time
    }
  }
`;

export const LOGIN_MUTATION = /* GraphQL */ `
  mutation Login($username: String!, $password: String!) {
    login(input: { clientMutationId: "web", username: $username, password: $password }) {
      authToken
    }
  }
`;

export const MY_BOOKINGS_QUERY = /* GraphQL */ `
  query MyBookings {
    myBookings {
      databaseId
      date
      time
      status
      serviceName
      companyName
    }
  }
`;

export const MY_FAVORITES_QUERY = /* GraphQL */ `
  ${COMPANY_CARD_FRAGMENT}
  query MyFavorites {
    myFavorites {
      ...CompanyCard
    }
  }
`;

export const CANCEL_BOOKING_MUTATION = /* GraphQL */ `
  mutation CancelBooking($id: ID!) {
    cancelBooking(input: { clientMutationId: "web", bookingId: $id }) {
      status
    }
  }
`;

export const TOGGLE_FAVORITE_MUTATION = /* GraphQL */ `
  mutation ToggleFavorite($id: ID!) {
    toggleFavorite(input: { clientMutationId: "web", companyId: $id }) {
      isFavorite
      companyIds
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
