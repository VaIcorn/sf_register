<?php
namespace Evoweb\SfRegister\Controller;
/***************************************************************
 * Copyright notice
 *
 * (c) 2011-13 Sebastian Fischer <typo3@evoweb.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * An frontend user edit controller
 */
class FeuserEditController extends \Evoweb\SfRegister\Controller\FeuserController {
	/**
	 * Form action
	 *
	 * @return void
	 */
	public function formAction() {
		/** @var \TYPO3\CMS\Extbase\Mvc\Request $originalRequest */
		$originalRequest = $this->request->getOriginalRequest();
		if ($originalRequest !== NULL && $originalRequest->hasArgument('user') &&
				\Evoweb\SfRegister\Services\Login::isLoggedIn()) {
			$userData = $originalRequest->getArgument('user');

			if ($userData['uid'] != $GLOBALS['TSFE']->fe_user->user['uid']) {
				/** @var $user \Evoweb\SfRegister\Domain\Model\FrontendUser */
				$user = $this->userRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
			} else {
				/** @var \TYPO3\CMS\Extbase\Property\PropertyMapper $propertyMapper */
				$propertyMapper = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Property\\PropertyMapper');
				$user = $propertyMapper->convert($userData, 'Evoweb\\SfRegister\\Domain\\Model\\FrontendUser');
				$user = $this->moveTempFile($user);
			}
		} else {
			/** @var \Evoweb\SfRegister\Domain\Model\FrontendUser $user */
			$user = $this->objectManager->get('Evoweb\\SfRegister\\Domain\\Model\\FrontendUser');
		}

		if ($originalRequest->hasArgument('temporaryImage')) {
			$this->view->assign('temporaryImage', $originalRequest->getArgument('temporaryImage'));
		}

		$this->signalSlotDispatcher->dispatch(
			__CLASS__,
			__FUNCTION__,
			array(
				'user' => &$user,
				'settings' => $this->settings,
			)
		);

		$this->view->assign('user', $user);
	}

	/**
	 * Preview action
	 *
	 * @param \Evoweb\SfRegister\Domain\Model\FrontendUser $user
	 * @return void
	 * @validate $user Evoweb.SfRegister:User
	 */
	public function previewAction(\Evoweb\SfRegister\Domain\Model\FrontendUser $user) {
		$user = $this->moveTempFile($user);

		$user->prepareDateOfBirth();

		if ($this->request->hasArgument('temporaryImage')) {
			$this->view->assign('temporaryImage', $this->request->getArgument('temporaryImage'));
		}

		$this->signalSlotDispatcher->dispatch(
			__CLASS__,
			__FUNCTION__,
			array(
				'user' => &$user,
				'settings' => $this->settings,
			)
		);

		$this->view->assign('user', $user);
	}

	/**
	 * Save action
	 *
	 * @param \Evoweb\SfRegister\Domain\Model\FrontendUser $user
	 * @return void
	 * @validate $user Evoweb.SfRegister:User
	 */
	public function saveAction(\Evoweb\SfRegister\Domain\Model\FrontendUser $user) {
		$user = $this->moveImageFile($user);

		if ($this->isNotifyAdmin('PostEdit') || $this->isNotifyUser('PostEdit')) {
			$user->setDisable(TRUE);
		}

		$user->prepareDateOfBirth();

		$this->signalSlotDispatcher->dispatch(
			__CLASS__,
			__FUNCTION__,
			array(
				'user' => &$user,
				'settings' => $this->settings,
			)
		);

		$user = $this->sendEmails($user, 'PostEdit');

		$this->userRepository->update($user);

		$this->objectManager
			->get('Evoweb\\SfRegister\\Services\\Session')
			->remove('captchaWasValidPreviously');

		if ($this->settings['forwardToEditAfterSave']) {
			$this->forward('form');
		}
	}
}

?>
