<?php
class ncenterliteModel extends ncenterlite
{
	var $config;

	function getConfig()
	{
		if(!$this->config)
		{
			$oModuleModel = &getModel('module');
			$config = $oModuleModel->getModuleConfig('ncenterlite');
			if(!$config->use) $config->use = 'Y';

			if(!$config->message_notify) $config->message_notify = 'Y';
			if(!$config->mention_format && !is_array($config->mention_format)) $config->mention_format = array('respect');
			if(!is_array($config->mention_format)) $config->mention_format = explode('|@|', $config->mention_format);
			if(!$config->document_notify) $config->document_notify = 'direct-comment';
			if(!$config->hide_module_srls) $config->hide_module_srls = array();
			if(!is_array($config->hide_module_srls)) $config->hide_module_srls = explode('|@|', $config->hide_module_srls);

			if(!$config->skin) $config->skin = 'default';
			if(!$config->colorset) $config->colorset = 'black';

			$this->config = $config;
		}

		return $this->config;
	}

	function getMyNotifyList($member_srl=null, $page=1, $readed='N')
	{
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();

		global $lang;

		$output = $this->_getMyNotifyList($member_srl, $page, $readed);
		$oMemberModel = &getModel('member');
		$list = $output->data;

		foreach($list as $k => $v)
		{
			$target_member = $v->target_nick_name;

			switch($v->type)
			{
				case 'D':
					$type = $lang->ncenterlite_document; //$type = '글';
				break;
				case 'C':
					$type = $lang->ncenterlite_comment; //$type = '댓글';
				break;
				// 메시지. 쪽지
				case 'E':
					$type = $lang->ncenterlite_type_message; //$type = '쪽지';
				break;
			}

			switch($v->target_type)
			{
				case 'C':
					$str = sprintf($lang->ncenterlite_commented, $target_member, $type, $v->target_summary);
					//$str = sprintf('<strong>%s</strong>님이 회원님의 %s에 <strong>"%s" 댓글</strong>을 남겼습니다.', $target_member, $type, $v->target_summary);
				break;
				case 'M':
					$str = sprintf($lang->ncenterlite_mentioned, $target_member,  $v->target_summary, $type);
					//$str = sprintf('<strong>%s</strong>님이 <strong>"%s" %s</strong>에서 회원님을 언급하였습니다.', $target_member,  $v->target_summary, $type);
				break;
				// 메시지. 쪽지
				case 'E':
					if(version_compare(__XE_VERSION__, '1.7.4', '>='))
					{
						$str = sprintf('<strong>%s</strong>님께서 <strong>%s</strong> 메세지를 보내셨습니다.',$target_member, $v->target_summary);
					}
					else
					{
						$str = sprintf($lang->ncenterlite_message_string, $v->target_summary);
					}
				break;
				case 'T':
					$str = sprintf('<strong>%s</strong>님! 스킨 테스트 알림을 완료했습니다.', $target_member);
				break;
				case 'P':
					$str = sprintf('<strong>%s</strong>님이 <strong>"%s"</strong>게시판에 <strong>%s</strong>글을 남겼습니다.', $target_member, $v->target_browser, $v->target_summary);
				break;
				case 'S':
					if($v->target_browser)
					{
						$str = sprintf('<strong>%s</strong>님이 <strong>"%s"</strong>게시판에 <strong>"%s"</strong>글을 남겼습니다.', $target_member, $v->target_browser, $v->target_summary);
					}
					else
					{
						$str = sprintf('<strong>%s</strong>님이 <strong>"%s"</strong>글을 남겼습니다.', $target_member, $v->target_browser, $v->target_summary);
					}
				break;	
			}

			$v->text = $str;
			$v->ago = $this->getAgo($v->regdate);
			$v->url = getUrl('','act','procNcenterliteRedirect', 'notify', $v->notify, 'url', $v->target_url);
			if($v->target_member_srl)
			{
				$profileImage = $oMemberModel->getProfileImage($v->target_member_srl);
				$v->profileImage = $profileImage->src;
			}

			$list[$k] = $v;
		}

		$output->data = $list;
		return $output;
	}

	function getMyNotifyListTpl()
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info) return new Object(-1, 'msg_not_permitted');

		$oMemberModel = &getModel('member');
		$memberConfig = $oMemberModel->getMemberConfig();
		$page = Context::get('page');

		$member_srl = $logged_info->member_srl;
		$tmp = $this->getMyNotifyList($member_srl, $page);
		foreach($tmp->data as $key => $obj)
		{
			$tmp->data[$key]->url = str_replace('&amp;', '&', $obj->url);
		}

		$list->data = $tmp->data;
		$list->page = $tmp->page_navigation;
		$this->add('list', $list);
		$this->add('useProfileImage', $memberConfig->profile_image);
	}

	function _getMyNotifyList($member_srl=null, $page=1, $readed='N')
	{
		if(!$member_srl)
		{
			$logged_info = Context::get('logged_info');
			if(!$logged_info) return array();

			$member_srl = $logged_info->member_srl;
		}

		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->page = $page ? $page : 1;
		if($readed) $args->readed = $readed;
		$output = executeQueryArray('ncenterlite.getNotifyList', $args);
		if(!$output->data) $output->data = array();

		return $output;
	}

	function _getNewCount($member_srl=null)
	{
		if(!$member_srl)
		{
			$logged_info = Context::get('logged_info');
			if(!$logged_info) return 0;

			$member_srl = $logged_info->member_srl;
		}

		$args->member_srl = $member_srl;
		$output = executeQuery('ncenterlite.getNotifyNewCount', $args);
		if(!$output->data) return 0;
		return $output->data->cnt;
	}


	function getColorsetList()
	{
		$oModuleModel = &getModel('module');
		$skin = Context::get('skin');

		$skin_info = $oModuleModel->loadSkinInfo($this->module_path, $skin);

		for($i=0, $c=count($skin_info->colorset); $i<$c; $i++)
		{
			$colorset = sprintf('%s|@|%s', $skin_info->colorset[$i]->name, $skin_info->colorset[$i]->title);
			$colorset_list[] = $colorset;
		}

		if(count($colorset_list)) $colorsets = implode("\n", $colorset_list);
		$this->add('colorset_list', $colorsets);
	}

	/**
	 * @brief 주어진 시간이 얼마 전 인지 반환
	 * @param string YmdHis
	 * @return string
	 **/
	function getAgo($datetime)
	{
		global $lang;
		$lang_type = Context::getLangType();

		$display = $lang->ncenterlite_date; // array('Year', 'Month', 'Day', 'Hour', 'Minute', 'Second')

		$ago = $lang->ncenterlite_ago; // 'Ago'

		$date = getdate(strtotime(zdate($datetime, 'Y-m-d H:i:s')));

		$current = getdate();
		$p = array('year', 'mon', 'mday', 'hours', 'minutes', 'seconds');
		$factor = array(0, 12, 30, 24, 60, 60);

		for($i = 0; $i < 6; $i++)
		{
			if($i > 0)
			{
				$current[$p[$i]] += $current[$p[$i - 1]] * $factor[$i];
				$date[$p[$i]] += $date[$p[$i - 1]] * $factor[$i];
			}

			if($current[$p[$i]] - $date[$p[$i]] > 1)
			{
				$value = $current[$p[$i]] - $date[$p[$i]];
				if($lang_type == 'en')
				{
					return $value . ' ' . $display[$i] . (($value != 1) ? 's' : '') . ' ' . $ago;
				}
				return $value . $display[$i] . ' ' . $ago;
			}
		}

		return zdate($datetime, 'Y-m-d');
	}
}
