<?php

require_once 'controllers/Profile/AbstractController.php';

// @TODO CHECK ON OBJECT FOR SAVING "CITY, ST" VALUES

class Elm_ProfileController extends Elm_Profile_AbstractController
{
	/**
	 * Default 404
	 */
	public function noRouteAction()
	{
	}

	/**
	 * Index/view action
	 */
	public function indexAction()
	{
		$this->_forward('view');
	}

	/**
	 * Index/view action
	 */
	public function viewAction()
	{
		$this->_init();
		$this->_initLayout();
	}

	/**
	 * Login and login post actions
	 *
	 * @return void
	 */
	public function loginAction()
	{
		$session = $this->_getSession();
        if ($session->isLoggedIn()) {
            $this->_redirect('/profile/');
            return;
        }

		$form = new Elm_Model_Form_User_Login();
		if ($this->getRequest()->isPost()) {
            $post = $this->getRequest()->getPost();
            if ($form->isValid($post)) {
                try {
                    $session->login($post['email'], $post['password']);
        			if (preg_match('/(logout)/i', $session->beforeAuthUrl)) {
						$session->beforeAuthUrl = '/profile/';
					}
					Elm::log($session->beforeAuthUrl);
					$this->_redirect($session->beforeAuthUrl);
                } catch (Colony_Exception $e) {
                    $session->addError($e->getMessage());
                } catch (Exception $e) {
					$session->addError($e->getMessage());
                }
            } else {
                $session->addError('Login and password are required.');
            }
        }

		$this->view->headTitle()->prepend('Account Login');
		$this->view->form = $form;
	}

	/**
	 * Login ajax action
	 *
	 * @return void
	 */
	public function loginAjaxAction()
	{
		$this->_initAjax();
		$this->_helper->viewRenderer->setNoRender(true);

		$response = array();
		$session = $this->_getSession();
        if ($session->isLoggedIn()) {
			$response = array(
				'success' => true,
				'error' => false,
				'location' => $this->getUrl('profile')
			);
        } else {
			$form = new Elm_Model_Form_User_Login();
			if ($this->getRequest()->isPost()) {
				$post = $this->getRequest()->getPost();
				if ($form->isValid($post)) {
					try {
						$session->login($post['email'], $post['password']);
						$response = array(
							'success' => true,
							'error' => false,
							//'location' => $this->getUrl(null, array('alias' => $session->getUser()->getAlias(), '_route' => 'user'))
							'location' => $this->getUrl('profile')
						);
					} catch (Exception $e) {
						$response = array(
							'success' => false,
							'error' => true,
							'message' => $e->getMessage()
						);
					}
				} else {
					$response = array(
						'success' => false,
						'error' => true,
						'message' =>'Login and password are required.'
					);
				}
			}
		}

		$this->_helper->json->sendJson($response);
	}

	/**
	 * Logout action request
	 *
	 * @return void
	 */
	public function logoutAction()
	{
		$this->_getSession()->logout()->beforeAuthUrl = $this->getCurrentUrl();
        $this->_redirect('/');
	}

	/**
	 * Registration and registration post action
	 *
	 * @return void
	 */
	public function createAction()
	{
		$session = $this->_getSession();
        if ($session->isLoggedIn()) {
            //$this->_redirect('/u/' . $session->getUser()->alias);
			$this->_redirect('/profile/');
            return;
        }

		$form = new Elm_Model_Form_User_Create();
		if ($this->getRequest()->isPost()) {
			$errors = array();
			$post = $this->getRequest()->getPost();
			$user = Elm::getModel('user');
			if ($form->isValid($post)) {
				$user->setData($post)->setPassword($post['password']);
				$user->save();

				// Increment inviteCode
				$inviteCode = Elm::getSingleton('inviteCode')->load($post['invite_code']);
				$inviteCode->increment()->save();

				// setup session, send email, add messages, move on
				$session->setUserAsLoggedIn($user);
				$user->sendNewAccountEmail($session->beforeAuthUrl);
				$session->addSuccess(sprintf("Glad to have you on board, %s!", $user->getFirstname()));
				//$url = '/u/' . $user->getAlias();
				$this->_redirect('/profile/');
				return;
			}
			else {
				if (is_array($errors)) {
					foreach ($errors as $errorMessage) {
						$session->addError($errorMessage);
					}
				} else {
					$session->addError($this->__('Invalid user data'));
				}
			}
		}

		$this->view->headTitle()->prepend('Create Account');
		$this->view->form = $form;
	}

	/**
	 * Registration and registration post action
	 *
	 * @return void
	 */
	public function createAjaxAction()
	{
		$this->_initAjax();
		$this->_helper->viewRenderer->setNoRender(true);

		$response = array();
		$session = $this->_getSession();
        if ($session->isLoggedIn()) {
            $response = array(
				'success' => true,
				'error' => false,
				'location' => $this->getUrl(null, array('alias' => $session->getUser()->getAlias(), '_route' => 'user'))
			);
        } else {
			$form = new Elm_Model_Form_User_Create();
			$form->removeElement('location');
			if ($this->getRequest()->isPost()) {
				$post = $this->getRequest()->getPost();
				if ($form->isValid($post)) {
					$user = Elm::getModel('user');
					$user->setData($post)->setPassword($post['password']);
					$user->save();

					// setup session, send email, add messages, move on
					$session->setUserAsLoggedIn($user);
					$user->sendNewAccountEmail($session->beforeAuthUrl);
					$response = array(
						'success' => true,
						'error' => false,
						'location' => $this->getUrl(null, array('alias' => $user->getAlias(), '_route' => 'user'))
					);
				}
				else {
					$response = array(
						'success' => false,
						'error' => true,
						'message' =>'Check all fields are filled out.'
					);
				}
			}
		}

		$this->_helper->json->sendJson($response);
	}

	/**
	 * Users settings page
	 *
	 * @return mixed
	 */
	public function settingsAction()
	{
		$session = $this->_getSession();
		if (!$session->isLoggedIn()) {
			$this->_redirect('/profile/login');
			return;
		}

		$this->view->headTitle()->prepend('Settings');
		$this->view->headTitle()->prepend($session->user->getFirstname() . ' ' . $session->user->getLastname());

		$form = new Elm_Model_Form_User_Settings();
		$form->setAction('/profile/settings-save');
		$this->view->form = $form;
	}

	/**
	 * Users settings save action
	 *
	 * @return mixed
	 */
	public function settingsSaveAction()
	{
		if (!$this->getRequest()->isPost()) {
			$this->_redirect('/profile/settings');
			return;
		}

		$this->_init();
		$session = $this->_getSession();

		$form = new Elm_Model_Form_User_Settings();
		$post = $this->getRequest()->getParams();
		if ($form->isValid($post)) {
			try {
				$user = $session->getUser();
				$user->addData($post)->save();
				$session->addSuccess('Successfully saved your settings!');
			} catch (Colony_Exception $e) {
				$session->addError($e->getMessage());
			} catch (Exception $e) {
				$session->addError($e->getMessage());
			}
		} else {
			$session->addError('Check all fields are filled out!');
		}

		$this->_redirect('/profile/settings');
	}

	/**
	 *
	 */
	public function confirmationAction()
	{
		if ($key = $this->getRequest()->getParam('uid', null)) {
			$user = new Elm_Model_User();
			if ($user->checkConfirmationKey($key)) {
				if (!$user->getIsConfirmed()) {
					$user->setIsConfirmed(true)->save();
					$user->sendConfirmedAccountEmail();
					$this->_getSession()->addSuccess("Excellent! Let's get growin'.");
				}
				$this->_forward('confirmed');
			} else {
				$this->_getSession()->addError('Gasp! This key does not match anything on file.');
			}
		}
	}

	/**
	 *
	 */
	public function confirmedAction()
	{
		//$this->_init();
	}

	/**
	 *
	 */
	public function saveAction()
	{
		$this->_initAjax();
		$session = $this->_getSession();
		if (!$session->isLoggedIn()) {
			$response = array(
				'success' => false,
				'error' => true,
				'location' => $this->_helper->url('profile/login')
			);
        } else {
			if ($this->getRequest()->isPost()) {
				$field = $this->getRequest()->getPost('user_update');
				$user = $this->_getSession()->getUser();
				$user->setData($field, $this->getRequest()->getPost($field))
					->save();
				$response = array(
					'success' => true,
					'error' => false,
					'message' =>'Ah, success!',
					'value' => $user->getData($field)
				);
			} else {
				$response = array(
					'success' => false,
					'error' => true,
					'message' =>'Oops! Check required fields and try again.'
				);
			}
		}

		$this->_helper->json->sendJson($response);
	}

	/**
	 * User image upload action
	 */
	public function imageUploadAction()
	{
		if ($this->getRequest()->isPost()) {
			Elm::getModel('user/image')->upload(
				Elm::getModel('user')->load($this->_getSession()->getUser()->getId('u')),
				$this->getRequest()->getParams()
			);
		}
		$this->_redirect('/profile/');
	}
}