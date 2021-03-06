<?php
// Copyright (C) 2010-2013 Combodo SARL
//
//   This program is free software; you can redistribute it and/or modify
//   it under the terms of the GNU Lesser General Public License as published by
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

Dict::Add('EN US', 'English', 'English', array(
	// Dictionary entries go here
	'Class:MailInboxStandard' => 'Standard Mail Inbox',
	'Class:MailInboxStandard+' => 'Source of incoming eMails',
	'Class:MailInboxStandard/Attribute:behavior' => 'Behavior',
	'Class:MailInboxStandard/Attribute:behavior/Value:create_only' => 'Create new Tickets',
	'Class:MailInboxStandard/Attribute:behavior/Value:update_only' => 'Update existing Tickets',
	'Class:MailInboxStandard/Attribute:behavior/Value:both' => 'Create or Update Tickets',

	'Class:MailInboxStandard/Attribute:target_class' => 'Ticket Class',
	'Class:MailInboxStandard/Attribute:target_class/Value:Incident' => 'Incident',
	'Class:MailInboxStandard/Attribute:target_class/Value:UserRequest' => 'User Request',

	'Class:MailInboxStandard/Attribute:ticket_default_values' => 'Ticket Default Values',
	'Class:MailInboxStandard/Attribute:title_pattern+' => 'Pattern to match in the subject',
	'Class:MailInboxStandard/Attribute:title_pattern' => 'Title Pattern',
	'Class:MailInboxStandard/Attribute:title_pattern?' => 'Use PCRE syntax, including starting and ending delimiters',

	'Class:MailInboxStandard/Attribute:title_pattern_type' => 'Title Pattern link',
	'Class:MailInboxStandard/Attribute:close_pattern_enable' => 'Enable the Resolve pattern?',
	'Class:MailInboxStandard/Attribute:close_pattern_enable/Value:yes' => 'Yes',
	'Class:MailInboxStandard/Attribute:close_pattern_enable/Value:no' => 'No',
	'Class:MailInboxStandard/Attribute:close_pattern' => 'Resolve Pattern',
	
	'Class:MailInboxStandard/Attribute:unknown_caller_behavior' => 'Behavior in case of Unknown Caller',
	'Class:MailInboxStandard/Attribute:unknown_caller_behavior/Value:create_contact' => 'Create a new Person',
	'Class:MailInboxStandard/Attribute:unknown_caller_behavior/Value:reject_email' => 'Reject the eMail',

	'Class:MailInboxStandard/Attribute:trace' => 'Debug trace',
	'Class:MailInboxStandard/Attribute:trace/Value:yes' => 'Yes',
	'Class:MailInboxStandard/Attribute:trace/Value:no' => 'No',

	'Class:MailInboxStandard/Attribute:caller_default_values' => "New Person's Default Values",
	'Class:MailInboxStandard/Attribute:debug_log' => 'Debug Log',
	'Class:MailInboxStandard/Attribute:error_behavior' => 'Behavior',
	'Class:MailInboxStandard/Attribute:error_behavior/Value:delete' => 'Delete the message from the mailbox',
	'Class:MailInboxStandard/Attribute:error_behavior/Value:mark_as_error' => 'Keep the message in the mailbox',
	'Class:MailInboxStandard/Attribute:notify_errors_to' => 'Forward eMails To',
	'Class:MailInboxStandard/Attribute:notify_errors_from' => '(From)',
	'MailInbox:Server' => 'Mailbox Configuration',
	'MailInbox:Behavior' => 'Behavior on Incoming eMails',
	'MailInbox:Caller' => 'Unknown Callers',
	'MailInbox:Errors' => 'Emails in Error',
	'Menu:MailInboxes' => 'Incoming eMail Inboxes',
	'Menu:MailInboxes+' => 'Configuration of Inboxes to scan for Incoming eMails',
	'MailInboxStandard:DebugTrace' => 'Debug Trace',
	'MailInboxStandard:DebugTraceNotActive' => 'Activate the debug on this Inbox to see the debug trace here.',
));
