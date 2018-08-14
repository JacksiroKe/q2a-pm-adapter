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
			$this->pm_adapter_url = '../' . $qa_layers['Private Message Layer']['urltoroot'];
			qa_html_theme_base::qa_html_theme_base($template, $content, $rooturl, $request);
		}
		
		function head_css() {
			qa_html_theme_base::head_css();
			if ($this->request == 'messages' || $this->template == 'feedbacks')
				$this->output('<link href="' . $this->pm_adapter_url . 
				'pm-style.css" type="text/css" rel="stylesheet" >');			
		}
		
		function head_script(){
			qa_html_theme_base::head_script();
			if ($this->template == 'feedbacks')
				$this->output('<script type="text/javascript" src="' . $this->pm_adapter_url . 
				'pm-script.js"></script>');
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
			} 
			elseif (strpos($this->request,'message/') !== false) qa_redirect('messanger/'.qa_request_part(1));
			elseif ($this->request == 'feedback') qa_redirect('send_feedback');
			elseif ($this->request == 'admin/feedbacks') {
				$this->template = 'feedbacks';
				$this->pm_navigation('feedbacks');
				$this->content['suggest_next']="";
				$this->content['title'] = qa_lang_html('admin/admin_title') . ' - ' . qa_lang_html('pm_lang/feedbacks');
				$this->content = $this->pm_feedbacks();
			}
			elseif (strpos($this->request,'messanger/') !== false) $this->template = 'ask';
			elseif ($this->request == 'send_feedback') $this->template = 'ask';
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
		
		function nav_list($pm_navigation, $class, $level=null)
		{
			if($this->template=='admin') {
				if ($class == 'nav-sub') {
					if (qa_opt('feedback_enabled'))
						$pm_navigation['feedbacks'] = array(
							'label' => qa_lang_html('pm_lang/feedbacks'),
							'url' => qa_path_html('admin/feedbacks'),
						);
				}
				if ( $this->request == 'admin/feedbacks' )
				$pm_navigation = array_merge(qa_admin_sub_navigation(), $pm_navigation);
			}
			//if(count($pm_navigation) > 1 ) 
				qa_html_theme_base::nav_list($pm_navigation, $class, $level=null);	
		}
		
		function pm_navigation($request)
		{
			$this->content['navigation']['sub'] = qa_admin_sub_navigation();
			$this->content['navigation']['sub']['feedbacks'] = array(
				'label' => qa_lang('pm_lang/feedbacks'),	
				'url' => qa_path_html('admin/feedbacks'),  
				'selected' => ($request == 'feedbacks' ) ? 'selected' : '',
			);
			return 	$this->content['navigation']['sub'];
		}
			
		public function pm_feedbacks()
		{
			require_once(QA_MESSANGER_DIR. '/pm-base.php');
			$this->content['error'] = '';

			$pm_html = $limit_fbs = $page_form = '';
			$messages = array();
			if (qa_clicked('do_action')){
				
				$action = @$_POST['um-action'];
				$feedbackids = @$_POST['chk-feedback-checked'];
				$fcount = count($feedbackids);
				
				if($action=='removeuser') $messages[] = pm_feedback_remover($fcount, $feedbackids);
			}
			
			// Load limited users per page
			$page_fbs = (int)qa_html(qa_opt('pm_feedbacks_per_page'));
			$page_on = isset($_GET['page']) ? (int)$_GET['page']-1 : 0;
			if( qa_opt('pm_feedback_filter')=='active' ) $fb_count = pm_feedbacks_count();
			else $fb_count = pm_feedbacks_count();
			
			if ($page_fbs == 0) $page_all = 1; 
			else $page_all = ceil($fb_count / $page_fbs);
			
			if($page_fbs != 0 || $page_all != 1)
			{
				$limit_fbs = ' LIMIT ' . ($page_on * $page_fbs) . ',' . $page_fbs;
				$pm_html .= su_page_form($page_fbs, $page_all, $page_on);
			}
			$feedbacks = pm_load_feedbacks($limit_fbs);
			foreach($messages as $message) $pm_html .= '<div class="qa-error">' . $message . '</div>';
			
			pm_sort_by($feedbacks, 'created');
			$pm_html .= pm_page_format();
						foreach ($feedbacks as $fb){
							$isanswered =($fb['parentid'] == 0) ? false : true;
							$pm_html .= '
								<tr title="'.$fb['content'].'">
									<td valign="top" style="width:20px"><label><input id="chk-feedback-' . $fb['feedbackid'] . '" class="chk-feedback" name="chk-feedback-checked[]" type="checkbox" value="' .  $fb['feedbackid'] . '"></label></td>
									<td valign="top"><a href="'.qa_path_html('user/'.$fb['handle']).'">' . $fb['name'] .'</a><br><small>' . qa_html(qa_user_level_string($fb['level'])) .'</small>)</td>
									<td style="width:120px">' . $fb['email'] .'</td>
									<td valign="top">' . $fb['topic'] .'</td>
									<td valign="top" style="max-width:100%">' . substr(strip_tags($fb['content']), 0, 120) .'</td>
									<td valign="top" style="width:120px">' . data_arr_str(time_formatter($fb['created'], 7)) .' <br><small>' . (qa_lang_html($isanswered ? 'pm_lang/feedback_answered' : 'pm_lang/feedback_not_answered') . ' ') .'</small></td>
									
								</tr>';
						}
						$pm_html .= '
						</tbody>
					</table>
				</form>';
			$this->content['custom'] = $pm_html;
			return $this->content;
		}
	}

/*
	Omit PHP closing tag to help avoid accidental output
*/
