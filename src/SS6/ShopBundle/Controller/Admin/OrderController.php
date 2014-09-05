<?php

namespace SS6\ShopBundle\Controller\Admin;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use SS6\ShopBundle\Form\Admin\Order\OrderFormData;
use SS6\ShopBundle\Form\Admin\Order\OrderFormType;
use SS6\ShopBundle\Form\Admin\Order\OrderItemFormData;
use SS6\ShopBundle\Model\AdminNavigation\MenuItem;
use SS6\ShopBundle\Model\PKGrid\QueryBuilderDataSource;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class OrderController extends Controller {
	
	/**
	 * @Route("/order/edit/{id}", requirements={"id" = "\d+"})
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param int $id
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 */
	public function editAction(Request $request, $id) {
		$flashMessageTwig = $this->get('ss6.shop.flash_message.twig_sender.admin');
		/* @var $flashMessageTwig \SS6\ShopBundle\Model\FlashMessage\TwigSender */
		$orderStatusRepository = $this->get('ss6.shop.order.order_status_repository');
		/* @var $orderStatusRepository \SS6\ShopBundle\Model\Order\Status\OrderStatusRepository */
		$orderRepository = $this->get('ss6.shop.order.order_repository');
		/* @var $orderRepository \SS6\ShopBundle\Model\Order\OrderRepository */
		
		$order = $orderRepository->getById($id);
		$allOrderStauses = $orderStatusRepository->findAll();
		$form = $this->createForm(new OrderFormType($allOrderStauses));
		
		try {
			$orderData = new OrderFormData();

			if (!$form->isSubmitted()) {
				$customer = $order->getCustomer();
				$customerId = null;
				if ($order->getCustomer() !== null) {
					$customerId = $customer->getId();
				}

				/* @var $order \SS6\ShopBundle\Model\Order\Order */
				$orderData->setId($order->getId());
				$orderData->setOrderNumber($order->getNumber());
				$orderData->setStatusId($order->getStatus()->getId());
				$orderData->setCustomerId($customerId);
				$orderData->setFirstName($order->getFirstName());
				$orderData->setLastName($order->getLastName());
				$orderData->setEmail($order->getEmail());
				$orderData->setTelephone($order->getTelephone());
				$orderData->setCompanyName($order->getCompanyName());
				$orderData->setCompanyNumber($order->getCompanyNumber());
				$orderData->setCompanyTaxNumber($order->getCompanyTaxNumber());
				$orderData->setStreet($order->getStreet());
				$orderData->setCity($order->getCity());
				$orderData->setPostcode($order->getPostcode());
				$orderData->setDeliveryContactPerson($order->getDeliveryContactPerson());
				$orderData->setDeliveryCompanyName($order->getDeliveryCompanyName());
				$orderData->setDeliveryTelephone($order->getDeliveryTelephone());
				$orderData->setDeliveryStreet($order->getDeliveryStreet());
				$orderData->setDeliveryCity($order->getDeliveryCity());
				$orderData->setDeliveryPostcode($order->getDeliveryPostcode());
				$orderData->setNote($order->getNote());

				$orderItemsData = array();
				foreach ($order->getItems() as $orderItem) {
					$orderItemFormData = new OrderItemFormData();
					$orderItemFormData->setId($orderItem->getId());
					$orderItemFormData->setName($orderItem->getName());
					$orderItemFormData->setPrice($orderItem->getPriceWithVat());
					$orderItemFormData->setQuantity($orderItem->getQuantity());
					$orderItemsData[] = $orderItemFormData;
				}
				$orderData->setItems($orderItemsData);
			}
			
			$form->setData($orderData);
			$form->handleRequest($request);
				
			if ($form->isValid()) {
				$orderFacade = $this->get('ss6.shop.order.order_facade');
				/* @var $orderFacade \SS6\ShopBundle\Model\Order\OrderFacade */

				$order = $orderFacade->edit($id, $orderData);

				$flashMessageTwig->addSuccess('Byla upravena objednávka č.'
						. ' <strong><a href="{{ url }}">{{ number }}</a></strong>', array(
					'number' => $order->getNumber(),
					'url' => $this->generateUrl('admin_order_edit', array('id' => $order->getId())),
				));
				return $this->redirect($this->generateUrl('admin_order_list'));
			}
		} catch (\SS6\ShopBundle\Model\Order\Status\Exception\OrderStatusNotFoundException $e) {
			$flashMessageTwig->addError('Zadaný stav objednávky nebyl nalezen, prosím překontrolujte zadané údaje');
		} catch (\SS6\ShopBundle\Model\Customer\Exception\UserNotFoundException $e) {
			$flashMessageTwig->addError('Zadaný zákazník nebyl nalezen, prosím překontrolujte zadané údaje');
		}

		if ($form->isSubmitted() && !$form->isValid()) {
			$flashMessageTwig->addError('Prosím zkontrolujte si správnost vyplnění všech údajů');
		}

		$breadcrumb = $this->get('ss6.shop.admin_navigation.breadcrumb');
		/* @var $breadcrumb \SS6\ShopBundle\Model\AdminNavigation\Breadcrumb */
		$breadcrumb->replaceLastItem(new MenuItem('Editace objednávky - č. ' . $order->getNumber()));
		
		return $this->render('@SS6Shop/Admin/Content/Order/edit.html.twig', array(
			'form' => $form->createView(),
			'order' => $order,
		));
	}

	/**
	 * @Route("/order/list/")
	 */
	public function listAction() {
		$administratorGridFacade = $this->get('ss6.shop.administrator.administrator_grid_facade');
		/* @var $administratorGridFacade \SS6\ShopBundle\Model\Administrator\AdministratorGridFacade */
		$administrator = $this->getUser();
		/* @var $administrator \SS6\ShopBundle\Model\Administrator\Administrator */
		$orderRepository = $this->get('ss6.shop.order.order_repository');
		/* @var $orderRepository \SS6\ShopBundle\Model\Order\OrderRepository */
		$gridFactory = $this->get('ss6.shop.pkgrid.factory');
		/* @var $gridFactory \SS6\ShopBundle\Model\PKGrid\PKGridFactory */

		$queryBuilder = $orderRepository->getOrdersListQueryBuilder();
		$queryBuilder
			->select('
				o.id,
				o.number,
				o.createdAt,
				MAX(os.name) AS statusName,
				o.totalPrice,
				(CASE WHEN o.companyName IS NOT NULL
							THEN o.companyName
							ELSE CONCAT(o.firstName, \' \', o.lastName)
						END) AS customerName')
			->join('o.status', 'os')
			->groupBy('o.id');
		$dataSource = new QueryBuilderDataSource($queryBuilder);

		$grid = $gridFactory->create('orderList', $dataSource);
		$grid->allowPaging();
		$grid->setDefaultOrder('number');

		$grid->addColumn('number', 'o.number', 'Č. objednávky', true);
		$grid->addColumn('created_at', 'o.createdAt', 'Vytvořena', true);
		$grid->addColumn('customer_name', 'customerName', 'Zákazník', true);
		$grid->addColumn('status_name', 'statusName', 'Stav', true);
		$grid->addColumn('total_price', 'o.totalPrice', 'Celková cena', true)->setClassAttribute('text-right');


		$grid->setActionColumnClassAttribute('table-col table-col-10');
		$grid->addActionColumn('edit', 'Upravit', 'admin_order_edit', array('id' => 'id'));
		$grid->addActionColumn('delete', 'Smazat', 'admin_order_delete', array('id' => 'id'))
			->setConfirmMessage('Opravdu si přejete objednávku smazat?');

		$administratorGridFacade->restoreAndRememberGridLimit($administrator, $grid);

		return $this->render('@SS6Shop/Admin/Content/Order/list.html.twig', array(
			'gridView' => $grid->createView(),
		));
	}

	/**
	 * @Route("/order/delete/{id}", requirements={"id" = "\d+"})
	 * @param int $id
	 */
	public function deleteAction($id) {
		$flashMessageTwig = $this->get('ss6.shop.flash_message.twig_sender.admin');
		/* @var $flashMessageTwig \SS6\ShopBundle\Model\FlashMessage\TwigSender */
		$orderRepository = $this->get('ss6.shop.order.order_repository');
		/* @var $orderRepository \SS6\ShopBundle\Model\Order\OrderRepository */

		$orderNumber = $orderRepository->getById($id)->getNumber();
		$orderFacade = $this->get('ss6.shop.order.order_facade');
		/* @var $orderFacade \SS6\ShopBundle\Model\Order\OrderFacade */
		$orderFacade->deleteById($id);

		$flashMessageTwig->addSuccess('Objednávka č. <strong>{{ number }}</strong> byla smazána', array(
			'number' => $orderNumber,
		));
		return $this->redirect($this->generateUrl('admin_order_list'));
	}
}
