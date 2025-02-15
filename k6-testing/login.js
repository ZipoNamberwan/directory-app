import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
    vus: 200,
    duration: '10s'
};

export default function () {
    let url = 'https://kendedes.cathajatim.id/login';

    let res = http.get(url);

    check(res, {
        'Response status is 200': (r) => r.status === 200
    });

    sleep(1);
}
