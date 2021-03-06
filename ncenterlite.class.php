<?php
class ncenterlite extends ModuleObject
{
	// @@@@@@@@@@ 사용자 커스텀 시작
	// 쪽지를 열 mid 지정
	// 쪽지를 열 때 해당 mid에서 열리도록 합니다
	// 비워두면 접속한 페이지에서 열림(기본 동작)
	var $message_mid = '';

	// 노티바(알림바)를 감출 mid - array('mid1', 'mid2', 'mid3')
	// 지정한 mid에서는 노티바를 출력하지 않습니다
	var $disable_notify_bar_mid = array();

	// 노티바(알림바)를 감출 act - array('act1', 'act2', 'act3')
	// 지정한 act에서는 노티바를 출력하지 않습니다
	var $disable_notify_bar_act = array();

	// 알림을 보내지 않을 게시판 mid - array('mid1', 'mid2', 'mid3')
	// 지정한 mid에서는 댓글 알림을 보내지 않습니다
	var $disable_notify = array();
	// @@@@@@@@@@ 사용자 커스텀 끝


	var $_TYPE_DOCUMENT = 'D'; // 댓글
	var $_TYPE_COMMENT = 'C'; // 댓글의 댓글
	var $_TYPE_MENTION = 'M'; // 멘션
	var $_TYPE_MESSAGE = 'E'; // 쪽지 mEssage
	var $_TYPE_DOCUMENTS = 'P'; // 글 작성 알림
	var $_TYPE_TEST = 'T';

	var $triggers = array(
		array('comment.insertComment', 'ncenterlite', 'controller', 'triggerAfterInsertComment', 'after'),
		array('comment.deleteComment', 'ncenterlite', 'controller', 'triggerAfterDeleteComment', 'after'),
		array('document.insertDocument', 'ncenterlite', 'controller', 'triggerAfterInsertDocument', 'after'),
		array('document.deleteDocument', 'ncenterlite', 'controller', 'triggerAfterDeleteDocument', 'after'),
		array('display', 'ncenterlite', 'controller', 'triggerBeforeDisplay', 'before'),
		array('moduleHandler.proc', 'ncenterlite', 'controller', 'triggerAfterModuleHandlerProc', 'after'),
		array('moduleObject.proc', 'ncenterlite', 'controller', 'triggerBeforeModuleObjectProc', 'before'),
		array('member.deleteMember', 'ncenterlite', 'controller', 'triggerAfterDeleteMember', 'after'),
		array('communication.sendMessage', 'ncenterlite', 'controller', 'triggerAfterSendMessage', 'after'),
		);

	function _isDisable()
	{
		$result = FALSE;
		if(count($this->disable_notify))
		{
			$module_info = Context::get('module_info');
			if(in_array($module_info->mid, $this->disable_notify)) $result = TRUE;
		}

		return $result;
	}

	function moduleInstall()
	{
		return new Object();
	}

	function checkUpdate()
	{
		$oModuleModel = &getModel('module');
		$oDB = &DB::getInstance();

		foreach($this->triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4])) return true;
		}

		if(!$oDB->isColumnExists('ncenterlite_notify', 'readed'))
		{
			return true;
		}

		if(!$oDB->isColumnExists('ncenterlite_notify', 'target_browser'))
		{
			return true;
		}

		return false;
	}

	function moduleUpdate()
	{
		$oModuleModel = &getModel('module');
		$oModuleController = &getController('module');
		$oDB = &DB::getInstance();

		foreach($this->triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}

		if(!$oDB->isColumnExists('ncenterlite_notify','readed'))
		{
			$oDB->addColumn('ncenterlite_notify', 'readed', 'char', 1, 'N', true);
			$oDB->addIndex('ncenterlite_notify', 'idx_readed', array('readed'));
			$oDB->addIndex('ncenterlite_notify', 'idx_member_srl', array('member_srl'));
			$oDB->addIndex('ncenterlite_notify', 'idx_regdate', array('regdate'));
		}

		if(!$oDB->isColumnExists('ncenterlite_notify','target_browser'))
		{
			$oDB->addColumn('ncenterlite_notify', 'target_browser', 'varchar', 50, true);
		}

		return new Object(0, 'success_updated');
	}

	function recompileCache()
	{
		return new Object();
	}

	function moduleUninstall()
	{
		$oModuleController = &getController('module');

		foreach($this->triggers as $trigger)
		{
			$oModuleController->deleteTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
		}
		return new Object();
	}
}
