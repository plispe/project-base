#! /bin/bash
domains_yaml="domains_urls: [{id: 1, url: http://shop.shopsys.local}, {id: 2, url: http://shop2.shopsys.local}]"
domains_count=$(echo $domains_yaml | yq read - 'domains_urls.[*].id' | wc -l)
domains_last_index=`expr $domains_count - 1`
domain_prefix="http://"

for line_number in $(seq $domains_count)
do 
    DOMAIN_ID=$(echo $domains_yaml | yq read - 'domains_urls.[*].id' | head -"$line_number" - | tail -1)
    DOMAIN_ID=$(echo -e ${DOMAIN_ID#-})
    DOMAIN_URL=$(echo $domains_yaml | yq read - 'domains_urls.[*].url' | head -"$line_number" - | tail -1)
    DOMAIN_URL=$(echo -e ${DOMAIN_URL#-})
    DOMAIN_URL=$(echo -e ${DOMAIN_URL#$domain_prefix})
        
    ./render-template.sh ./templates/ingress-rule.tpl > update-script.yaml
    yq write --inplace --script update-script.yaml ../ingress.yml

    # ./render-template.sh ./templates/ingress-tls.tpl > update-script.yaml
    # yq write --inplace --script update-script.yaml ../ingress.yml

    # ./render-template.sh ./templates/domain-ssl-certificate.tpl > update-script.yaml
    # yq write --inplace --script update-script.yaml ./base/kustomization.yaml
done
echo $domains_yaml > ../../app/config/domains_urls.yml   
rm -rf update-script.yaml