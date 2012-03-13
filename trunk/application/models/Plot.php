<?php


class Elm_Model_Plot extends Colony_Model_Abstract
{
	/**
	 * @var array
	 */
	private $_users = array();

	/**
	 * @var array
	 */
	private $_watchers = array();

	/**
	 * @var array
	 */
	private $_pendingUsers = array();

	/**
	 * @var array
	 */
	private $_images = array();

	/**
	 * @var array
	 */
	private $_comments = array();

	/**
	 * Construct
	 */
	public function _construct()
    {
        $this->_init('plot');
    }

	/**
	 * Before save
	 */
	public function _beforeSave()
	{
	}

	/**
     * Load plot by lat & long
     *
     * @param   string $lat
     * @param   string $long
     * @return  Elm_Model_Plot
     */
    public function loadByLatLong($lat, $long)
    {
        $this->_getResource()->loadByLatLong($this, $lat, $long);
        return $this;
    }

	/**
	 * Returns all plots
	 *
	 * @return mixed
	 */
	public function getAllPlots()
	{
		return $this->_getResource()->getAllPlots();
	}

	/**
	 * Get users
	 *
	 * @return mixed
	 */
	public function getUsers()
	{
		if (count($this->_users) < 1 && count($this->getUserIds()) > 0) {
			foreach ($this->getUserIds() as $id => $role) {
				$user = Bootstrap::getModel('user')->load($id);
				$user->setUserRole($role);
				$this->_users[] = $user;
			}
		}

		return $this->_users;
	}

	/**
	 * @TODO Create a Plot_User that extends a normal user
	 * @TODO Create individual user objects (gardener, owner, watcher)
	 * @return array
	 */
	public function getPendingUsers()
	{
		if (count($this->_pendingUsers) < 1) {
			$users = $this->_getResource()->getPendingUsers($this);
			foreach ($users as $id => $role) {
				$user = Bootstrap::getModel('user')->load($id);
				$user->setUserRole($role);
				$this->_pendingUsers[] = $user;
			}
		}

		return $this->_pendingUsers;
	}

	/**
	 * Get users
	 *
	 * @return mixed
	 */
	public function getWatchers()
	{
		if (count($this->_watchers) < 1 && count($this->getUserIds()) > 0) {
			foreach ($this->getUserIds() as $id => $role) {
				if ($role == Elm_Model_Resource_Plot::ROLE_WATCHER) {
					$user = Bootstrap::getModel('user')->load($id);
					$user->setUserRole($role);
					$this->_watchers[] = $user;
				}
			}
		}
		return $this->_watchers;
	}

	/**
	 * Returns top level status updates associated to the plot
	 *
	 * @param null $limit
	 * @return array
	 */
	public function getFeed($limit = null)
	{
		if (count($this->_comments) < 1) {
			$this->_comments = Bootstrap::getModel('plot_status')->getByPlotId($this->getId(), $limit);
		}
		return $this->_comments;
	}

    /**
     * Send email with new account specific information
     *
	 * @TODO send new plot email - http://stackoverflow.com/questions/1218191/how-can-i-make-email-template-in-zend-framework
	 *
	 * @param string $backUrl
     * @return Elm_Model_User
     */
    public function sendNewPlotEmail($backUrl = '')
    {
		try {
			$EmailTemplate = new Elm_Model_Email_Template(array('template' => 'new-plot.phtml'));
			$EmailTemplate->setParams(array(
				'plot' => $this
			));
			$EmailTemplate->send(array('email' => 'collin.bourdage@gmail.com', 'name' => 'Collin Bourdage'));
		} catch(Exception $e) {
			Bootstrap::logException($e);
		}
        return $this;
    }

	/**
	 * Creates a new status post for current plot
	 *
	 * @return Elm_Model_Plot
	 */
	public function createNewPlotStatus()
	{
		$status = Bootstrap::getModel('plot/status');
		$status->setPlotId($this->getId())
			->setUserId($this->getUserId())
			->setType('text')
			->setTitle('New Plot!')
			->setContent(sprintf('<a href="%s">%s</a> created!', $this->getUrl(), $this->getName()));
		$status->save();
		return $this;
	}

	/**
	 * Associates a user and a plot with an assigned role
	 *
	 * @param $userId int
	 * @param $role string
	 * @param bool $approved
	 * @return Elm_Model_Plot
	 */
	public function associateUser($userId, $role, $approved = false)
	{
		if (!$this->_getResource()->isUserAssociated($this, $userId, $role)) {
			$this->_getResource()->associateUser($this, $userId, $role, $approved);
		}
        return $this;
	}

	public function approveUser($userId, $role)
	{
		$this->_getResource()->updateAssociatedUser($this, $userId, $role, true);
		return $this;
	}

	public function denyUser($userId, $role)
	{
		$this->_getResource()->updateAssociatedUser($this, $userId, $role, -1);
		return $this;
	}

	/**
	 * Checks if the user is associated with this plot
	 *
	 * @param $user
	 * @param bool $isApproved
	 * @return bool
	 */
	public function isAssociated($user, $isApproved = false)
	{
		return $this->_getResource()->isUserAssociated($this, $user->getId(), null, $isApproved);


		/** Deprecated */
		foreach ($this->getUsers() as $u) {
			if ($user->getId() == $u->getId()) {
				foreach ($u->getUserRole() as $role) {
					if ($role['is_approved'] >= 0 && (
							$role['role'] != Elm_Model_Resource_Plot::ROLE_CREATOR
							&& $role['role'] != Elm_Model_Resource_Plot::ROLE_WATCHER
					)) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Checks if the user is the owner/creator of the plot
	 *
	 * @param $user
	 * @return bool
	 */
	public function isOwner($user)
	{
		return $this->_getResource()->isUserAssociated(
			$this,
			$user->getId(),
			array(Elm_Model_Resource_Plot::ROLE_OWNER, Elm_Model_Resource_Plot::ROLE_CREATOR)
		);

		/** Deprecated */
		foreach ($this->getUsers() as $u) {
			if ($user->getId() == $u->getId()) {
				foreach ($u->getUserRole() as $role) {
					if ($role['is_approved'] && (
							$role['role'] == Elm_Model_Resource_Plot::ROLE_CREATOR
							|| $role['role'] == Elm_Model_Resource_Plot::ROLE_OWNER
					)) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Checks if the user is watching this plot
	 *
	 * @param $user
	 * @return bool
	 */
	public function isWatching($user)
	{
		return $this->_getResource()->isUserAssociated($this, $user->getId(), Elm_Model_Resource_Plot::ROLE_WATCHER);

		/** Deprecated */
		foreach ($this->getWatchers() as $u) {
			if ($user->getId() == $u->getId()) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns a plots url
	 *
	 * @return string
	 */
	public function getUrl()
	{
		$helper = new Elm_View_Helper_Url();
		$url = $helper->url(null, array('p' => $this->getId(), '_route' => 'plot'));
		return $url;
	}

	/**
	 * Returns the plots images
	 * @return array
	 */
	public function getImages()
	{
		if (!$this->_images) {
			$this->_images = $this->_getResource()->getImages($this->getId());
		}

		return $this->_images;
	}

	/**
	 * Adds a new image to the plot
	 *
	 * @param array $params
	 * @return bool
	 */
	public function addImages($params)
	{
		$session = Bootstrap::getSingleton('user/session');
		$destination = Elm_Model_Plot_Image::getImageDestination($this);

		$adapter = new Zend_File_Transfer_Adapter_Http();
		$adapter->setDestination($destination)
			->addValidator('Size', false, Elm_Model_Plot_Image::MAX_FILE_SIZE)
			->addValidator('Extension', false, 'jpg,png,gif,jpeg');

		$files = $adapter->getFileInfo();
		$successful = 0;
		foreach ($files as $file => $info) {
			// Skip empty
			if ($info['name'] == null) {
				continue;
			}

			list($temp, $ext) = explode('.', $info['name']);
			$newFilename = md5($temp) . '.' . $ext;
			$imageIdx = preg_replace('/[^\d]/', '', $file);

			try {
				// Rename filter
				$adapter->addFilter('Rename', array(
					'target' => $destination . DIRECTORY_SEPARATOR . $newFilename,
					'overwrite' => true
				));

				// Receive and save
				if ($adapter->receive($file)) {
					$image = new Elm_Model_Plot_Image();
					$image->setData($info);
					$image->setData('exif_data', exif_read_data($info['destination'] . DIRECTORY_SEPARATOR . $newFilename));
					$image->setPlotId($this->getId())
						->setCaption($params['caption'][$imageIdx]);

					// Set image data
					$image->setThumbnail(Elm_Model_Plot_Image::getImageUrl($this) . '/' . $newFilename);
					$image->setFull(Elm_Model_Plot_Image::getImageUrl($this) . '/' . $newFilename);
					$image->save();
					$successful++;
				} else {
					$errors = $adapter->getMessages();
					foreach ($errors as $e) {
						$session->addError($e);
					}
				}
			} catch (Exception $e) {
				Bootstrap::logException($e);
				$session->addException($e);
			}
		}

		if ($successful > 0) {
			$session->addSuccess('Successfully uploaded images');
		}
	}

	/**
	 * @param string|array $images
	 */
	public function removeImages($images)
	{
		if (!is_array($images)) {
			$images = array($images);
		}

		$ctr = 0;
		foreach ($images as $id) {
			$image = Bootstrap::getModel('plot_image')->load($id);
			$image->delete();
			$ctr++;
		}

		$session = Bootstrap::getSingleton('user/session');
		if ($ctr > 1) {
			$session->addSuccess('Images removed');
		} elseif ($ctr == 1) {
			$session->addSuccess('Image removed');
		}
	}
}
