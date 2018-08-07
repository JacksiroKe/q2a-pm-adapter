<?php
/*
	Private Message Adapter by Jackson Siro
	https://www.github.com/jacksiro/Q2A-PM-Adapter-Plugin

	Description: Messanger Plugin Language phrases

*/

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../../');
	exit;
}

	require_once QA_INCLUDE_DIR.'app/blobs.php';
	require_once QA_INCLUDE_DIR.'app/format.php';

	class qa_html_theme_layer extends qa_html_theme_base 
	{
		function __construct($template, $content, $rooturl, $request)
		{
			global $qa_layers;
			$this->pm_adapter_url = './' . $qa_layers['Private Message Layer']['urltoroot'];
			qa_html_theme_base::qa_html_theme_base($template, $content, $rooturl, $request);
		}
		
		function head_css() {
			qa_html_theme_base::head_css();
			if ($this->request == 'messages')
				$this->output('<link href="' . $this->pm_adapter_url . 'pm-style.css" type="text/css" rel="stylesheet" >');			
		}
		function doctype() 
		{		
			if ($this->request == 'messages') 
			{
				unset($this->content['title']);
				unset($this->content['custom']);
				unset($this->content['message_list']);
				unset($this->content['form_message']);
				
				require_once QA_INCLUDE_DIR . 'db/selects.php';
				require_once QA_INCLUDE_DIR . 'app/users.php';
				require_once QA_INCLUDE_DIR . 'app/format.php';
				require_once QA_INCLUDE_DIR . 'app/limits.php';
				
				$userid = qa_get_logged_in_userid();
				$handle = qa_get_logged_in_handle();
				
				$start = qa_get_start();
				$pagesize = qa_opt('page_size_pms');
				$state = qa_get_state();
				$messagesent = $state == 'message-sent';
				
				$this->content['title'] = qa_lang('misc/nav_user_pms');			
				$this->content['navigation']['sub'] = qa_user_sub_navigation($handle, 'messages', true);
				
				$this->content['custom'] = '<div class="qa-message-list">'.
					$this->pm_msgs_output($userid, $start).
					'</div>';
			} elseif (strpos($this->request,'message/') !== false) {
				qa_redirect('messanger/'.qa_request_part(1));
			}
			qa_html_theme_base::doctype();
		}
		
		function pm_msgs_output($userid, $start, $pm_html = '')
		{
			$pmSpecCountIN = qa_db_selectspec_count(qa_db_messages_inbox_selectspec('private', $userid, true));
			$pmSpecIN = qa_db_messages_inbox_selectspec('private', $userid, true, $start, 1);
			list($numMessagesIN, $userMessagesIN) = qa_db_select_with_pending($pmSpecCountIN, $pmSpecIN);
			
			$pmSpecCountOUT = qa_db_selectspec_count(qa_db_messages_outbox_selectspec('private', $userid, true));
			$pmSpecOUT = qa_db_messages_outbox_selectspec('private', $userid, true, $start, 1);
			list($numMessagesIN, $userMessagesIN, $numMessagesOUT, $userMessagesOUT) = qa_db_select_with_pending($pmSpecCountIN, $pmSpecIN, $pmSpecCountOUT, $pmSpecOUT);
			$userMessages = array_merge($userMessagesIN, $userMessagesOUT);
			$count = ($numMessagesIN['count'] + $numMessagesOUT['count']);
	
			pm_sort_by($userMessages, 'created');
			foreach ($userMessages as $message)
			{
				$asavatar = '<img src="'. $this->pm_adapter_url . 'pm-user.png" width="200" height="200"/>';
				if ($message['fromuserid'] == $userid) {
					$handle = $message['tohandle'];
					$username = qa_lang_html_sub('main/to_x', '<a href="'.qa_path_html('user/' . $handle).'">'.$handle.'</a>');
					$hasavatar = qa_get_user_avatar_html($message['toflags'], $message['toemail'], $handle, $message['toavatarblobid'], $message['toavatarwidth'], $message['toavatarheight'], qa_opt('avatar_profile_size'));
					
				} elseif ($message['touserid'] == $userid) {
					$handle = $message['fromhandle'];
					$username = qa_lang_html_sub('main/by_x', '<a href="'.qa_path_html('user/' . $handle).'">'.$handle.'</a>');
					$hasavatar = qa_get_user_avatar_html($message['fromflags'], $message['fromemail'], $handle, $message['fromavatarblobid'], $message['fromavatarwidth'], $message['fromavatarheight'], qa_opt('avatar_profile_size'));
				}
				
				$pm_html .= '<div class="qa-message-item">'. ($hasavatar ? $hasavatar  : $asavatar).	
				'<a href="'.qa_path_html('messanger/' . $handle).'" class="qa-message-content-link">'.
					'<div class="qa-message-content">'.substr(strip_tags($message['content']), 0, 160).'</div>'.
					'</a><span class="pm-messanger-list-meta">'.data_arr_str(time_formatter($message['created'], 7)).' '.$username.'</span></div>';
			}
			return $pm_html;
		}
		
	}

/*
	Omit PHP closing tag to help avoid accidental output
*/
