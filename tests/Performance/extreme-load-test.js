import http from 'k6/http';
import { check } from 'k6';

// Base URL of the service within the Docker network
const BASE_URL = 'http://laravel.test';

export const options = {
  scenarios: {
    high_traffic_scenario: {
      executor: 'constant-arrival-rate',
      // The target rate of requests per second.
      rate: 1000,
      // The time unit for the rate.
      timeUnit: '1s',
      // Test duration.
      duration: '2m',
      // Number of VUs to pre-allocate.
      preAllocatedVUs: 200,
      // Maximum number of VUs to use.
      maxVUs: 1000, 

      stages: [
        // Ramp up to 1000 RPS over 30 seconds
        { duration: '30s', target: 1000 },
        // Stay at 1000 RPS for 1 minute
        { duration: '1m', target: 1000 },
        // Ramp up to 5000 RPS over 30 seconds
        { duration: '30s', target: 5000 },
        // Stay at 5000 RPS for 1 minute
        { duration: '1m', target: 5000 },
        // Ramp down to 0
        { duration: '20s', target: 0 },
      ],
    },
  },
  thresholds: {
    'http_req_failed': ['rate<0.01'], // Error rate should be less than 1%
    'http_req_duration': ['p(95)<800'], // 95% of requests must complete below 800ms
  },
};

export default function () {
  const startDate = '2025-01-01T00:00:00';
  const endDate = '2025-12-31T23:59:59';

  const res = http.get(`${BASE_URL}/api/search?starts_at=${startDate}&ends_at=${endDate}`);

  check(res, {
    'is status 200': (r) => r.status === 200,
  });
}
