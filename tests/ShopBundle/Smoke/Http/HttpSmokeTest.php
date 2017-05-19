<?php

namespace Tests\ShopBundle\Smoke\Http;

use Shopsys\ShopBundle\Component\Router\Security\RouteCsrfProtector;
use Shopsys\ShopBundle\Controller\Front\ProductController;
use Shopsys\ShopBundle\DataFixtures\Base\PricingGroupDataFixture;
use Shopsys\ShopBundle\DataFixtures\Base\UnitDataFixture as BaseUnitDataFixture;
use Shopsys\ShopBundle\DataFixtures\Base\VatDataFixture;
use Shopsys\ShopBundle\DataFixtures\Demo\OrderDataFixture;
use Shopsys\ShopBundle\DataFixtures\Demo\UnitDataFixture as DemoUnitDataFixture;
use Shopsys\ShopBundle\DataFixtures\Demo\UserDataFixture;
use Symfony\Component\HttpFoundation\Request;
use Tests\ShopBundle\Smoke\Http\Auth\BasicHttpAuth;
use Tests\ShopBundle\Smoke\Http\Auth\NoAuth;

class HttpSmokeTest extends HttpSmokeTestCase
{
    const DEFAULT_ID_VALUE = 1;

    protected function setUp()
    {
        parent::setUp();

        self::$kernel->getContainer()->get('shopsys.shop.component.domain')
            ->switchDomainById(1);
    }

    /**
     * @param \Tests\ShopBundle\Smoke\Http\RouteConfigCustomizer $routeConfigCustomizer
     */
    protected function customizeRouteConfigs(RouteConfigCustomizer $routeConfigCustomizer)
    {
        $this->filterRoutesForTesting($routeConfigCustomizer);
        $this->configureGeneralRules($routeConfigCustomizer);
        $this->configureAdminRoutes($routeConfigCustomizer);
        $this->configureFrontendRoutes($routeConfigCustomizer);
    }

    /**
     * @param \Tests\ShopBundle\Smoke\Http\RouteConfigCustomizer $routeConfigCustomizer
     */
    public function filterRoutesForTesting(RouteConfigCustomizer $routeConfigCustomizer)
    {
        $routeConfigCustomizer
            ->customize(function (RouteConfig $config) {
                if (!$config->isHttpMethodAllowed('GET')) {
                    $config->skipRoute('Only routes supporting GET method are tested.');
                }
            })
            ->customize(function (RouteConfig $config) {
                if (preg_match('~^(/admin)?/_~', $config->getRoutePath())) {
                    $config->skipRoute('Internal routes (prefixed with "/_") are not tested.');
                }
            })
            ->customize(function (RouteConfig $config) {
                if ($config->getRouteCondition() === 'request.isXmlHttpRequest()') {
                    $config->skipRoute('AJAX-only routes are not tested.');
                }
            })
            ->customize(function (RouteConfig $config) {
                if (!preg_match('~^(admin|front)_~', $config->getRouteName())) {
                    $config->skipRoute('Only routes for front-end and administration are tested.');
                }
            })
            ->customizeByRouteName(['admin_login_check', 'front_login_check'], function (RouteConfig $config) {
                $config->skipRoute(
                    'Used by firewall to catch login requests. '
                    . 'See http://symfony.com/doc/current/reference/configuration/security.html#check-path'
                );
            })
            ->customizeByRouteName(['front_image', 'front_image_without_type'], function (RouteConfig $config) {
                $config->skipRoute('There are no images in the shop when the tests are processed.');
            })
            ->customizeByRouteName('admin_domain_selectdomain', function (RouteConfig $config) {
                $config->skipRoute('Used only for internal setting of selected domain by tab control in admin.');
            })
            ->customizeByRouteName('admin_feed_generate', function (RouteConfig $config) {
                $config->skipRoute('Do not rewrite XML feed by test products.');
            })
            ->customizeByRouteName('admin_logout', function (RouteConfig $config) {
                $config->skipRoute('There is different security configuration in TEST environment.');
            })
            ->customizeByRouteName('admin_unit_delete', function (RouteConfig $config) {
                $config->skipRoute('temporarily not tested until it will be optimized in US-1517.');
            })
            ->customizeByRouteName('admin_domain_list', function (RouteConfig $config) {
                if ($this->isSingleDomain()) {
                    $config->skipRoute('Domain list in administration is not available when only 1 domain exists.');
                }
            });
    }

    /**
     * @param \Tests\ShopBundle\Smoke\Http\RouteConfigCustomizer $routeConfigCustomizer
     */
    public function configureGeneralRules(RouteConfigCustomizer $routeConfigCustomizer)
    {
        $routeConfigCustomizer
            ->customize(function (RouteConfig $config) {
                foreach ($config->getRouteParameterNames() as $name) {
                    if ($config->isRouteParameterRequired($name) && preg_match('~^(id|.+Id)$~', $name)) {
                        $debugNote = 'Route requires ID parameter "%s". Using %d by default.';
                        $config->changeDefaultRequestDataSet(sprintf($debugNote, $name, self::DEFAULT_ID_VALUE))
                            ->setParameter($name, self::DEFAULT_ID_VALUE);
                    }
                }
            })
            ->customize(function (RouteConfig $config) {
                if (preg_match('~_delete$~', $config->getRouteName())) {
                    $debugNote = 'Add CSRF token for any delete action during test execution. '
                        . '(Routes are protected by RouteCsrfProtector.)';
                    $config->changeDefaultRequestDataSet($debugNote)
                        ->addCallDuringTestExecution(function (RequestDataSet $requestDataSet) {
                            $routeCsrfProtector = self::$kernel->getContainer()
                                ->get('shopsys.shop.router.security.route_csrf_protector');
                            /* @var $routeCsrfProtector \Shopsys\ShopBundle\Component\Router\Security\RouteCsrfProtector */
                            $csrfTokenManager = self::$kernel->getContainer()->get('security.csrf.token_manager');
                            /* @var $csrfTokenManager \Symfony\Component\Security\Csrf\CsrfTokenManager */

                            $tokenId = $routeCsrfProtector->getCsrfTokenId($requestDataSet->getRouteName());
                            $token = $csrfTokenManager->getToken($tokenId);

                            $parameterName = RouteCsrfProtector::CSRF_TOKEN_REQUEST_PARAMETER;
                            $requestDataSet->setParameter($parameterName, $token->getValue());
                        });
                    $config->changeDefaultRequestDataSet('Expect redirect by 302 for any delete action.')
                        ->expectStatusCode(302);
                }
            });
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @param \Tests\ShopBundle\Smoke\Http\RouteConfigCustomizer $routeConfigCustomizer
     */
    public function configureAdminRoutes(RouteConfigCustomizer $routeConfigCustomizer)
    {
        $routeConfigCustomizer
            ->customize(function (RouteConfig $config) {
                if (preg_match('~^admin_~', $config->getRouteName())) {
                    $config->changeDefaultRequestDataSet('Log as "admin" to administration.')
                        ->setAuth(new BasicHttpAuth('admin', 'admin123'));
                }
            })
            ->customize(function (RouteConfig $config) {
                if (preg_match('~^admin_(superadmin_|translation_list$)~', $config->getRouteName())) {
                    $config->changeDefaultRequestDataSet('Only superadmin should be able to see this route.')
                        ->expectStatusCode(404);
                    $config->addExtraRequestDataSet('Should be OK when logged in as "superadmin".')
                        ->setAuth(new BasicHttpAuth('superadmin', 'admin123'))
                        ->expectStatusCode(200);
                }
            })
            ->customizeByRouteName('admin_login', function (RouteConfig $config) {
                $config->changeDefaultRequestDataSet('Admin login should redirect by 302.')
                    ->expectStatusCode(302);
                $config->addExtraRequestDataSet('Admin login should not redirect for users that are not logged in yet.')
                    ->setAuth(new NoAuth())
                    ->expectStatusCode(200);
            })
            ->customizeByRouteName(['admin_login_sso', 'admin_customer_loginasuser'], function (RouteConfig $config) {
                $debugNote = sprintf('Route "%s" should always just redirect.', $config->getRouteName());
                $config->changeDefaultRequestDataSet($debugNote)
                    ->expectStatusCode(302);
            })
            ->customizeByRouteName('admin_administrator_edit', function (RouteConfig $config) {
                $debugNote = 'It is forbidden to edit administrator with ID 1 as it is the superadmin.';
                $config->changeDefaultRequestDataSet($debugNote)
                    ->expectStatusCode(404);
                $config->addExtraRequestDataSet('Editing normal administrator should be OK.')
                    ->setParameter('id', 2)
                    ->expectStatusCode(200);
            })
            ->customizeByRouteName('admin_category_edit', function (RouteConfig $config) {
                $config->changeDefaultRequestDataSet('It is forbidden to edit category with ID 1 as it is the root.')
                    ->expectStatusCode(404);
                $config->addExtraRequestDataSet('Editing normal category should be OK.')
                    ->setParameter('id', 2)
                    ->expectStatusCode(200);
            })
            ->customizeByRouteName('admin_bestsellingproduct_detail', function (RouteConfig $config) {
                $config->changeDefaultRequestDataSet('Category with ID 1 is the root, use ID 2 instead.')
                    ->setParameter('categoryId', 2);
            })
            ->customizeByRouteName('admin_pricinggroup_delete', function (RouteConfig $config) {
                $pricingGroup = $this->getPersistentReference(PricingGroupDataFixture::PRICING_GROUP_PARTNER_DOMAIN_1);
                /* @var $pricingGroup \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup */

                $debugNote = sprintf('Delete pricing group "%s".', $pricingGroup->getName());
                $config->changeDefaultRequestDataSet($debugNote)
                    ->setParameter('id', $pricingGroup->getId());
            })
            ->customizeByRouteName('admin_unit_delete', function (RouteConfig $config) {
                $unit = $this->getPersistentReference(BaseUnitDataFixture::UNIT_PIECES);
                /* @var $unit \Shopsys\ShopBundle\Model\Product\Unit\Unit */
                $newUnit = $this->getPersistentReference(DemoUnitDataFixture::UNIT_CUBIC_METERS);
                /* @var $newUnit \Shopsys\ShopBundle\Model\Product\Unit\Unit */

                $debugNote = sprintf('Delete unit "%s" and replace it by "%s".', $unit->getName('en'), $newUnit->getName('en'));
                $config->changeDefaultRequestDataSet($debugNote)
                    ->setParameter('id', $unit->getId())
                    ->setParameter('newId', $newUnit->getId());
            })
            ->customizeByRouteName('admin_vat_delete', function (RouteConfig $config) {
                $vat = $this->getPersistentReference(VatDataFixture::VAT_SECOND_LOW);
                /* @var $vat \Shopsys\ShopBundle\Model\Pricing\Vat\Vat */
                $newVat = $this->getPersistentReference(VatDataFixture::VAT_LOW);
                /* @var $newVat \Shopsys\ShopBundle\Model\Pricing\Vat\Vat */

                $debugNote = sprintf('Delete VAT "%s" and replace it by "%s".', $vat->getName(), $newVat->getName());
                $config->changeDefaultRequestDataSet($debugNote)
                    ->setParameter('id', $vat->getId())
                    ->setParameter('newId', $newVat->getId());
            });
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @param \Tests\ShopBundle\Smoke\Http\RouteConfigCustomizer $routeConfigCustomizer
     */
    public function configureFrontendRoutes(RouteConfigCustomizer $routeConfigCustomizer)
    {
        $routeConfigCustomizer
            ->customizeByRouteName(['front_customer_edit', 'front_customer_orders'], function (RouteConfig $config) {
                $config->changeDefaultRequestDataSet('Log as demo user "Jaromír Jágr" on pages in client section.')
                    ->setAuth(new BasicHttpAuth('no-reply@netdevelo.cz', 'user123'));
            })
            ->customizeByRouteName(['front_customer_login_as_remembered_user', 'front_promo_code_remove'], function (RouteConfig $config) {
                $debugNote = sprintf('Route "%s" should always just redirect.', $config->getRouteName());
                $config->changeDefaultRequestDataSet($debugNote)
                    ->expectStatusCode(302);
            })
            ->customizeByRouteName(['front_order_index', 'front_order_sent'], function (RouteConfig $config) {
                $debugNote = 'Order page should redirect by 302 as the cart is empty by default.';
                $config->changeDefaultRequestDataSet($debugNote)
                    ->expectStatusCode(302);
            })
            ->customizeByRouteName('front_logout', function (RouteConfig $config) {
                $debugNote = 'Add CSRF token for logout action (configured in app/security.yml) during test execution.';
                $config->changeDefaultRequestDataSet($debugNote)
                    ->addCallDuringTestExecution(function (RequestDataSet $requestDataSet) {
                        $csrfTokenManager = self::$kernel->getContainer()->get('security.csrf.token_manager');
                        /* @var $csrfTokenManager \Symfony\Component\Security\Csrf\CsrfTokenManager */

                        $token = $csrfTokenManager->getToken('frontend_logout');

                        $requestDataSet->setParameter('_csrf_token', $token->getValue());
                    });
                $config->changeDefaultRequestDataSet('Logout action should redirect by 302')
                    ->expectStatusCode(302);
            })
            ->customizeByRouteName('front_article_detail', function (RouteConfig $config) {
                $config->changeDefaultRequestDataSet('Use ID 1 as default article.')
                    ->setParameter('id', 1);
            })
            ->customizeByRouteName('front_brand_detail', function (RouteConfig $config) {
                $config->changeDefaultRequestDataSet('Use ID 1 as default brand.')
                    ->setParameter('id', 1);
            })
            ->customizeByRouteName('front_customer_order_detail_unregistered', function (RouteConfig $config) {
                $order = $this->getPersistentReference(OrderDataFixture::ORDER_PREFIX . '1');
                /* @var $order \Shopsys\ShopBundle\Model\Order\Order */

                $debugNote = sprintf('Use hash of order n. %s for unregistered access.', $order->getNumber());
                $config->changeDefaultRequestDataSet($debugNote)
                    ->setParameter('urlHash', $order->getUrlHash());
            })
            ->customizeByRouteName('front_customer_order_detail_registered', function (RouteConfig $config) {
                $order = $this->getPersistentReference(OrderDataFixture::ORDER_PREFIX . '1');
                /* @var $order \Shopsys\ShopBundle\Model\Order\Order */

                $debugNote = sprintf('Log as demo user "Jaromír Jágr" on front-end to access order n. %s.', $order->getNumber());
                $config->changeDefaultRequestDataSet($debugNote)
                    ->setAuth(new BasicHttpAuth('no-reply@netdevelo.cz', 'user123'))
                    ->setParameter('orderNumber', $order->getNumber());
            })
            ->customizeByRouteName('front_product_detail', function (RouteConfig $config) {
                $config->changeDefaultRequestDataSet('Use ID 1 as default product.')
                    ->setParameter('id', 1);
                $config->addExtraRequestDataSet('See detail of a product that is main variant')
                    ->setParameter('id', 150);
            })
            ->customizeByRouteName('front_product_list', function (RouteConfig $config) {
                $config->changeDefaultRequestDataSet('Use ID 2 as default category (ID 1 is the root).')
                    ->setParameter('id', 2);
                $config->addExtraRequestDataSet('See category that has 500 products in performance data')
                    ->setParameter('id', 8);
                $config->addExtraRequestDataSet('See and filter category that has 500 products in performance data')
                    ->setParameter('id', 8)
                    ->setParameter('product_filter_form', [
                        'inStock' => '1',
                        'parameters' => [
                            41 => [58],
                        ],
                    ]);
                $config->addExtraRequestDataSet('See category that has 7600 products in performance data')
                    ->setParameter('id', 3);
                $config->addExtraRequestDataSet('See and filter category that has 7600 products in performance data')
                    ->setParameter('id', 3)
                    ->setParameter('product_filter_form', [
                        'minimalPrice' => '100',
                        'inStock' => '1',
                        'parameters' => [
                            1 => ['1'],
                        ],
                    ]);
                $config->addExtraRequestDataSet('See category that has 3600 products in performance data')
                    ->setParameter('id', 11);
                $config->addExtraRequestDataSet('See and filter category that has 3600 products in performance data')
                    ->setParameter('id', 11)
                    ->setParameter('product_filter_form', [
                        'minimalPrice' => '100',
                        'inStock' => '1',
                    ]);
            })
            ->customizeByRouteName('front_product_search', function (RouteConfig $config) {
                $config->addExtraRequestDataSet('Search for "a" and filter the results')
                    ->setParameter(ProductController::SEARCH_TEXT_PARAMETER, 'a')
                    ->setParameter('product_filter_form', [
                        'inStock' => '1',
                        'flags' => ['2'],
                        'brands' => ['2', '19'],
                    ]);
            })
            ->customizeByRouteName('front_registration_set_new_password', function (RouteConfig $config) {
                $customer = $this->getPersistentReference(UserDataFixture::USER_WITH_RESET_PASSWORD_HASH);
                /* @var $customer \Shopsys\ShopBundle\Model\Customer\User */
                $config->changeDefaultRequestDataSet('See new password page for customer with reset password hash.')
                    ->setParameter('email', $customer->getEmail())
                    ->setParameter('hash', $customer->getResetPasswordHash());
                $config->addExtraRequestDataSet('Expect redirect when the hash is invalid.')
                    ->setParameter('hash', 'invalidHash')
                    ->expectStatusCode(302);
            });
    }

    /**
     * @param string $name
     * @return object
     */
    private function getPersistentReference($name)
    {
        $persistentReferenceFacade = self::$kernel->getContainer()
            ->get('shopsys.shop.component.data_fixture.persistent_reference_facade');

        /* @var $persistentReferenceFacade \Shopsys\ShopBundle\Component\DataFixture\PersistentReferenceFacade */

        return $persistentReferenceFacade->getReference($name);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleRequest(Request $request)
    {
        $entityManager = self::$kernel->getContainer()->get('doctrine.orm.entity_manager');
        /* @var $entityManager \Doctrine\ORM\EntityManager */

        $entityManager->beginTransaction();
        $response = parent::handleRequest($request);
        $entityManager->rollback();

        return $response;
    }

    /**
     * @return bool
     */
    private function isSingleDomain()
    {
        $domain = self::$kernel->getContainer()->get('shopsys.shop.component.domain');

        /* @var $domain \Shopsys\ShopBundle\Component\Domain\Domain */

        return count($domain->getAll()) === 1;
    }
}
