<?php

/**
 * Get mails adress class
 * @package YetiForce.Action
 * @license YetiForce Public License 3.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class OSSMail_GetMail_Action extends Vtiger_Action_Controller
{

	/**
	 * Function to check permission
	 * @param \App\Request $request
	 * @throws \App\Exceptions\NoPermitted
	 */
	public function checkPermission(\App\Request $request)
	{
		$moduleName = $request->getModule();
		$currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($moduleName)) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED', 406);
		}
		if (!\App\Privilege::isPermitted($request->getByType('sourceModule', 2), 'DetailView', $request->getInteger('sourceRecord'))) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD', 406);
		}
	}

	public function process(\App\Request $request)
	{
		$moduleName = $request->getModule();
		$sourceRecord = $request->getInteger('sourceRecord');
		$sourceModule = $request->getByType('sourceModule', 2);
		$maxEmails = $request->has('maxEmails') ? $request->getInteger('maxEmails') : 0;

		$emails = [];
		$emailFields = OSSMailScanner_Record_Model::getEmailSearch($sourceModule);
		$recordModel = Vtiger_Record_Model::getInstanceById($sourceRecord, $sourceModule);
		$name = $recordModel->getName();
		foreach ($emailFields as $emailField) {
			$email = $recordModel->get($emailField['fieldname']);
			if (!empty($email)) {
				$emails[] = [
					'name' => $name,
					'fieldlabel' => App\Language::translate($emailField['fieldlabel'], $emailField['name']),
					'email' => $email
				];
				if ($maxEmails === 1) {
					break;
				}
			}
		}

		if (count($emails) > 1) {
			$viewController = new Vtiger_Index_View();
			$viewer = $viewController->getViewer($request);
			$viewer->assign('EMAILS', $emails);
			$viewer->view('GetMails.tpl', $moduleName);
		} else {
			$email = '';
			if (count($emails) == 1) {
				$email = $emails[0]['email'];
			}
			$response = new Vtiger_Response();
			$response->setResult($email);
			$response->emit();
		}
	}
}
