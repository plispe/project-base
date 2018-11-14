spec.rules[+]:
    host: ${DOMAIN_URL}
    http:
        paths:
        -   path: /
            backend:
                serviceName: webserver-php-fpm
                servicePort: 8080