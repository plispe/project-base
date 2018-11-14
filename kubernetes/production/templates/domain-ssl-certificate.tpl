secretGenerator[+]:
  name: domain-${DOMAIN_ID}-ssl-certificate
  commands: 
    tls.key: "cat /tmp/domain-ssl-certificates/${DOMAIN_ID}/tls.key || true"
    tls.crt: "cat /tmp/domain-ssl-certificates/${DOMAIN_ID}/tls.crt || true"
    ca.crt: "cat /tmp/domain-ssl-certificates/${DOMAIN_ID}/ca.crt || true"