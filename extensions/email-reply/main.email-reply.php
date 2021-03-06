<?php
// Copyright (C) 2012 Combodo SARL
//
//   This program is free software; you can redistribute it and/or modify
//   it under the terms of the GNU General Public License as published by
//   the Free Software Foundation; version 3 of the License.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of the GNU General Public License
//   along with this program; if not, write to the Free Software
//   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA


/**
 * Module email-reply
 *
 * @author      Erwan Taloc <erwan.taloc@combodo.com>
 * @author      Romain Quetiez <romain.quetiez@combodo.com>
 * @author      Denis Flaven <denis.flaven@combodo.com>
 * @license     http://www.opensource.org/licenses/gpl-3.0.html LGPL
 */
/**
 * To trigger notifications when a ticket is updated from the portal
 */

class TriggerOnLogUpdate extends TriggerOnObject
{
	public static function Init()
	{
		$aParams = array
		(
			"category" => "core/cmdb",
			"key_type" => "autoincrement",
			"name_attcode" => "description",
			"state_attcode" => "",
			"reconc_keys" => array('description'),
			"db_table" => "priv_trigger_onlogupdate",
			"db_key_field" => "id",
			"db_finalclass_field" => "",
			"display_template" => "",
		);
		MetaModel::Init_Params($aParams);
		MetaModel::Init_InheritAttributes();

		MetaModel::Init_AddAttribute(new AttributeString("target_log", array("allowed_values"=>null, "sql"=>"target_log", "default_value"=>'public_log', "is_null_allowed"=>false, "depends_on"=>array())));

		// Display lists
		MetaModel::Init_SetZListItems('details', array('description', 'target_class', 'target_log', 'action_list')); // Attributes to be displayed for the complete details
		MetaModel::Init_SetZListItems('list', array('finalclass', 'target_class', 'target_log', 'description')); // Attributes to be displayed for a list
		// Search criteria
	}
}
// Add class definitions here

// Add menus creation here

// Declare a class that implements iBackgroundProcess (will be called by the CRON)
// Extend the class AsyncTask to create a queue of asynchronous tasks (process by the CRON)
// Declare a class that implements iApplicationUIExtension (to tune object display and edition form)
// Declare a class that implements iApplicationObjectExtension (to tune object read/write rules)

class EmailReplyPlugIn implements iApplicationUIExtension, iApplicationObjectExtension
{
	public function OnDisplayProperties($oObject, WebPage $oPage, $bEditMode = false)
	{
		if ($bEditMode && !$oObject->IsNew())
		{
			$bEnabled = (bool) MetaModel::GetModuleSetting('email-reply', 'enabled_default', true);
			$sChecked = $bEnabled ? 'checked' : '';
			$oPage->add_ready_script("$('#form_2').append('<div id=\"emry_form_extension\"></div>');");
			foreach ($this->ListTargetCaseLogs(get_class($oObject)) as $sAttCode => $aTriggers)
			{
				$sModuleUrl = utils::GetAbsoluteUrlModulesRoot().'email-reply/';
				$oPage->add_linked_script($sModuleUrl.'email-reply.js');
				$oPage->add_dict_entry('UI-emry-noattachment');

				$oPage->add_ready_script("$('#field_2_$sAttCode div.caselog_input_header').append('<input type=\"checkbox\" $sChecked id=\"emry_enabled_$sAttCode\" name=\"emry_enabled[$sAttCode]\" value=\"yes\">&nbsp;<img id=\"emry_event_bus_$sAttCode\" src=\"../images/mail.png\">&nbsp;<span id=\"emry_file_list_$sAttCode\" style=\"display: inline-block;\"><img src=\"{$sModuleUrl}/paper_clip.png\">&nbsp;(<span id=\"emry_file_count_$sAttCode\">0</span>)</span>');");

				$oPage->add_ready_script("$('#emry_event_bus_$sAttCode').bind('add_blob', function(event, sContainerClass, sContainerId, sBlobAttCode, sFileName) {EmailReplyAddFile('$sAttCode', sContainerClass, sContainerId, sBlobAttCode, sFileName);} );");
				$oPage->add_ready_script("$('#attachment_plugin').bind('add_attachment', function(event, attId, sAttName) {EmailReplyAddFile('$sAttCode', 'Attachment', attId, 'contents', sAttName);} );");
				$oPage->add_ready_script("$('#attachment_plugin').bind('remove_attachment', function(event, attId, sAttName) {EmailReplyRemoveFile('$sAttCode', 'Attachment', attId, 'contents');} );");
				$oPage->add_ready_script("$('#emry_file_list_$sAttCode').attr('title', '".addslashes(Dict::S('UI-emry-noattachment'))."');");
			}
		}
	}

	public function OnDisplayRelations($oObject, WebPage $oPage, $bEditMode = false)
	{
	}

	public function OnFormSubmit($oObject, $sFormPrefix = '')
	{
		$aCaseLogs = $this->ListTargetCaseLogs(get_class($oObject));
		if (count($aCaseLogs) > 0)
		{
			$aOperations = utils::ReadPostedParam('emry_enabled', array());
			$aTriggerContext = $oObject->ToArgs('this');
	
			foreach ($aCaseLogs as $sAttCode => $aTriggers)
			{
				$sOperation = isset($aOperations[$sAttCode]) ? $aOperations[$sAttCode] : 'no';
				$sLog = utils::ReadPostedParam('attr_'.$sAttCode, null, false, 'raw_data');
				if (($sOperation == 'yes') && ($sLog != null))
				{
					$aFileDefs = utils::ReadParam('emry_files_'.$sAttCode, array(), false, 'raw_data');
					if (count($aFileDefs) > 0)
					{
						$aFiles = array();
						foreach($aFileDefs as $sFileDef)
						{
							// Forward attachments into the pipe (via the context of the trigger)
							$aMatches = array();
							if (preg_match('|^(.+)::(.+)/(.+)$|', $sFileDef, $aMatches))
							{
								$sContainerClass = $aMatches[1];
								$sContainerId = $aMatches[2];
								$sBlobAttCode = $aMatches[3];
								$oContainer = MetaModel::GetObject($sContainerClass, $sContainerId, false);
								$oFile = $oContainer->Get($sBlobAttCode);
								$aFiles[] = $oFile;
							}
						}
						$aTriggerContext['attachments'] = $aFiles;
					}

					$aTriggerContext['case-log-reply'] = $sLog;
					foreach ($aTriggers as $oTrigger)
					{
						$oTrigger->DoActivate($aTriggerContext);
					}
				}
			}
		}
	}
	
	public function OnFormCancel($sTempId)
	{
	}

	public function EnumUsedAttributes($oObject)
	{
		return array();
	}

	public function GetIcon($oObject)
	{
		return '';
	}

	public function GetHilightClass($oObject)
	{
		// Possible return values are:
		// HILIGHT_CLASS_CRITICAL, HILIGHT_CLASS_WARNING, HILIGHT_CLASS_OK, HILIGHT_CLASS_NONE	
		return HILIGHT_CLASS_NONE;
	}

	public function EnumAllowedActions(DBObjectSet $oSet)
	{
		// No action
		return array();
    }

	public function OnIsModified($oObject)
	{
		return false;
	}

	public function OnCheckToWrite($oObject)
	{
		return array();
	}

	public function OnCheckToDelete($oObject)
	{
		return array();
	}

	public function OnDBUpdate($oObject, $oChange = null)
	{
	}
	
	public function OnDBInsert($oObject, $oChange = null)
	{
	}
	
	public function OnDBDelete($oObject, $oChange = null)
	{	
	}

	///////////////////////////////////////////////////////////////////////////////////////////////////////
	//
	// Plug-ins specific functions
	//
	///////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Helper to determine the case logs for the given class
	 * Conditions:
	 *  1) There is at least one trigger "on log update" for this class
	 *  2) 	 	 	 	
	 */	
	protected function ListTargetCaseLogs($sClass)
	{
		static $aTargets = array();
		if (!isset($aTargets[$sClass]))
		{
			$aTargets[$sClass] = array();

			// Optimization: check if the class has a case log. If not... do not perform any query
			$bHasCaseLog = false;
			foreach (MetaModel::ListAttributeDefs($sClass) as $sAttCode => $oAttDef)
			{
				if ($oAttDef instanceof AttributeCaseLog)
				{
					$bHasCaseLog = true;
					break;
				}
			}

			if ($bHasCaseLog)
			{
				$sClassList = implode("', '", MetaModel::EnumParentClasses($sClass, ENUM_PARENT_CLASSES_ALL));
				$oTriggerSet = new CMDBObjectSet(DBObjectSearch::FromOQL("SELECT TriggerOnLogUpdate AS T WHERE T.target_class IN ('$sClassList')"));
				while ($oTrigger = $oTriggerSet->Fetch())
				{
					$bHasActiveEmailAction = false;
					$oActionList = $oTrigger->Get('action_list');
					while ($oLink = $oActionList->Fetch())
					{
						$iActionId = $oLink->Get('action_id');
						$oAction = MetaModel::GetObject('Action', $iActionId);
						if (($oAction instanceof ActionEmail) && $oAction->IsActive())
						{
							$bHasActiveEmailAction = true;
							break;
						}
					}
	
					if ($bHasActiveEmailAction)
					{
						$aTargets[$sClass][$oTrigger->Get('target_log')][] = $oTrigger;
					}
				}
			}
		}

		return $aTargets[$sClass];
	}
}
?>
