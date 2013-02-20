<?php

class PluginTopicblock_HookTopicblock extends Hook {

	public function RegisterHook() {
		$this->AddHook('topic_edit_show', 'TopicEditShow');
		$this->AddHook('topic_edit_before', 'TopicEditBefore');
		$this->AddHook('topic_delete_before', 'TopicDeleteBefore');
	}

	//*********************************************************************************************
	// Проверяем ограничение действия по количеству комментариев к топику
	// Возврат true - действие разрешено; false - действие запрещено	
	public function CheckActionByCommentsCount($oTopic,$sActionType){
		$iTopicCommentsCount	= $oTopic->getCountComment();
		$iCommentsLimit			= Config::Get('plugin.topicblock.'.$sActionType.'_comments_count_to_limit');
		
		if ($iTopicCommentsCount >= $iCommentsLimit) return false;
		else return true;	
	}
	
	//*********************************************************************************************
	// Проверяем ограничение действия по времени, прошедшему с момента публикации топика
	// Возврат true - действие разрешено; false - действие запрещено
	public function CheckActionByTimeLimit($oTopic,$sActionType){
		$iuTopicPublicationTime	= strtotime($oTopic->getDateAdd());
		$iNow					= time();
		$iTimeLimit				= Config::Get('plugin.topicblock.'.$sActionType.'_seconds_to_limit');
		
		if (($iNow - $iuTopicPublicationTime) > $iTimeLimit) return false;
		else return true;	
	}
	
	//*********************************************************************************************
	// Проверяет ограничения на действие по отношению к топику
	public function CheckLimits($oTopic,$sActionType){
		
		if($oTopic->getPublish() == 0){
			return true;
		}else{
			return ($this->CheckActionByCommentsCount($oTopic,$sActionType) and $this->CheckActionByTimeLimit($oTopic,$sActionType));
		}
	}
	
	//*********************************************************************************************
	// Обрабатываем хук, вызываемый при показе формы редактирования топика
	// Если выполняется условие ограничения, перенаправляем пользователя на страницу топика с
	// выводом соответственного сообщения
	public function TopicEditShow($aVars) {
		$oTopic				= $aVars['oTopic'];
		$oCurrentUser		= $this->User_GetUserCurrent();
		$sEditPermission	= Config::Get('plugin.topicblock.edit_permission_mode');
		
		if(!$this->CheckLimits($oTopic,'edit')){
			if(
				($sEditPermission == 'none') or
				(($sEditPermission == 'admin') and (!$oCurrentUser->isAdministrator()))){
			
				$this->Message_AddError($this->Lang_Get('plugin.topicblock.edit_not_permissed'),'',true);
				return Router::Location($oTopic->getUrl());
			}
				
		}
	}
	
	//*********************************************************************************************
	// Обрабатываем хук, вызываемый при записи редактируемого топика в БД
	// Если выполняется условие ограничения, перенаправляем пользователя обратно на страницу
	// редактирования топика с выводом соответственного сообщения и отменой записи в БД
	public function TopicEditBefore($aVars) {
		$oTopic				= $aVars['oTopic'];
		$oCurrentUser		= $this->User_GetUserCurrent();
		$sDraftPermission	= Config::Get('plugin.topicblock.draft_permission_mode');
		
		if((!$this->CheckLimits($oTopic,'draft')) and ($oTopic->getPublish() == 0)){
			if(
				($sDraftPermission == 'none') or
				(($sDraftPermission == 'admin') and (!$oCurrentUser->isAdministrator()))){
			
				$this->Message_AddError($this->Lang_Get('plugin.topicblock.draft_not_permissed'),'',true);
				return Router::Location(Router::Action('topic').'edit/'.$oTopic->getId());
			}
				
		}
	}
	
	//*********************************************************************************************
	// Обрабатываем хук, вызываемый перед удалением топика
	// Если выполняется условие ограничения, перенаправляем пользователя обратно на страницу
	// топика с выводом соответственного сообщения
	public function TopicDeleteBefore($aVars) {
		$oTopic				= $aVars['oTopic'];
		$oCurrentUser		= $this->User_GetUserCurrent();
		$sDeletePermission	= Config::Get('plugin.topicblock.delete_permission_mode');
		
		if(!$this->CheckLimits($oTopic,'delete')){
			if(
				($sDeletePermission == 'none') or
				(($sDeletePermission == 'admin') and (!$oCurrentUser->isAdministrator()))){
			
				$this->Message_AddError($this->Lang_Get('plugin.topicblock.delete_not_permissed'),'',true);
				return Router::Location($oTopic->getUrl());
			}
		}
	}
}

?>