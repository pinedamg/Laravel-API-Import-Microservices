import http from 'k6/http';
import { check, sleep } from 'k6';

// Base URL of the service within the Docker network
const BASE_URL = 'http://laravel.test';

// Get configuration from environment variables, with defaults
const VUS = __ENV.VUS || 10;
const DURATION = __ENV.DURATION || '30s';

export const options = {
  vus: VUS,
  duration: DURATION,
  thresholds: {
    // The rate of successful checks should be higher than 99%
    'checks': ['rate>0.99'],
    // 95% of requests must complete below 800ms
    'http_req_duration': ['p(95)<800'],
  },
};

export default function () {
  // Define a date range for the search query
  const startDate = '2025-01-01T00:00:00';
  const endDate = '2025-12-31T23:59:59';

  // Make a GET request to the search endpoint
  const res = http.get(`${BASE_URL}/api/search?starts_at=${startDate}&ends_at=${endDate}`);

  // Check if the response was successful
  check(res, {
    'is status 200': (r) => r.status === 200,
  });

  // Wait for 1 second before making another request
  sleep(1);
}
