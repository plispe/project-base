<?php

namespace SS6\ShopBundle\Tests\Acceptance\acceptance\PageObject\Front;

use SS6\ShopBundle\Tests\Acceptance\acceptance\PageObject\AbstractPage;

class LoginPage extends AbstractPage {

	/**
	 * @param string $email
	 * @param string $password
	 */
	public function login($email, $password) {
		$this->tester->fillFieldByName('front_login_form[email]', $email);
		$this->tester->fillFieldByName('front_login_form[password]', $password);
		$this->tester->clickByName('front_login_form[login]');
		$this->tester->waitForAjax();
	}

}
