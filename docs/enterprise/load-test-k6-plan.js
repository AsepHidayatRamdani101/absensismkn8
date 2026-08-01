import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
    scenarios: {
        users100: {
            executor: 'constant-vus',
            vus: 100,
            duration: '3m',
        },
    },
    thresholds: {
        http_req_duration: ['p(95)<2500'],
        http_req_failed: ['rate<0.01'],
    },
};

const BASE_URL = __ENV.BASE_URL || 'https://example.sch.id';

export default function () {
    const res = http.get(`${BASE_URL}/up`);
    check(res, {
        'status is 200': (r) => r.status === 200,
    });
    sleep(1);
}
