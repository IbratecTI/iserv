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
 * Localized data
 *
 * @author      Erwan Taloc <erwan.taloc@combodo.com>
 * @author      Romain Quetiez <romain.quetiez@combodo.com>
 * @author      Denis Flaven <denis.flaven@combodo.com>
 * @license     http://www.opensource.org/licenses/gpl-3.0.html LGPL
 */

Dict::Add('DE DE', 'German', 'Deutsch', array(
	//'Approval:Tab:Title' => 'Approval status',
	'Approval:Tab:Title' => 'Status der Genehmigung',
	
	'Approval:Tab:Start' => 'Start',
	
	//'Approval:Tab:End' => 'End',
	'Approval:Tab:End' => 'Ende',
		
	//'Approval:Tab:StepEnd-Limit' => 'Time limit (implicit result)',
	'Approval:Tab:StepEnd-Limit' => 'Zeitlimit (Implizites Ergebnis)',
	//'Approval:Tab:StepEnd-Theoretical' => 'Theoretical time limit (duration limited to %1$s mn)',
	'Approval:Tab:StepEnd-Theoretical' => 'Theoretisches Zeitlimit (Dauer begrenzt auf %1$s mn)',
	//'Approval:Tab:StepSumary-Ongoing' => 'Waiting for the replies',
	'Approval:Tab:StepSumary-Ongoing' => 'Warten auf Antwort',
	//'Approval:Tab:StepSumary-OK' => 'Approved',
	'Approval:Tab:StepSumary-OK' => 'Genehmigt',
	//'Approval:Tab:StepSumary-KO' => 'Rejected',
	'Approval:Tab:StepSumary-KO' => 'Abgelehnt',
	//'Approval:Tab:StepSumary-OK-Timeout' => 'Approved (timeout)',
	'Approval:Tab:StepSumary-OK-Timeout' => 'Genehmigt (Zeitüberschreitung)',
	//'Approval:Tab:StepSumary-KO-Timeout' => 'Rejected (timeout)',
	'Approval:Tab:StepSumary-KO-Timeout' => 'Abgelehnt (Zeitüberschreitung)',
	//'Approval:Tab:StepSumary-Idle' => 'Not started',
	'Approval:Tab:StepSumary-Idle' => 'Nicht begonnen',
	//'Approval:Tab:StepSumary-Skipped' => 'Skipped',
	'Approval:Tab:StepSumary-Skipped' => 'Übersprungen',

	//'Approval:Tab:Error' => 'An error occured during the approval process: %1$s',
	'Approval:Tab:Error' => 'Während des Genehmigungsprozesses trat ein Fehler auf: %1$s',

	//'Approval:Error:Email' => 'The email could not be sent (%1$s)',
	'Approval:Error:Email' => 'Die EMail konnte nicht gesendet werden (%1$s)',

	//'Approval:Action-Approve' => 'Approve',
	'Approval:Action-Approve' => 'Genehmigen',
	//'Approval:Action-Reject' => 'Reject',
	'Approval:Action-Reject' => 'Ablehnen',
	//'Approval:Action-ViewMoreInfo' => 'View more information',
	'Approval:Action-ViewMoreInfo' => 'Mehr Informationen ansehen',

	//'Approval:Form:Title' => 'Approval',
	'Approval:Form:Title' => 'Genehmigung',
	//'Approval:Form:Ref' => 'Approval process for %1$s',
	'Approval:Form:Ref' => 'Genhemigungsprozess für %1$s',

	//'Approval:Form:ApproverDeleted' => 'Sorry, the record corresponding to your identity has been deleted.', 
	'Approval:Form:ApproverDeleted' => 'Der zu ihrer Identität gehörende Datensatz wurde gelöscht.',
	//'Approval:Form:ObjectDeleted' => 'Sorry, the object of the approval has been deleted.',
	'Approval:Form:ObjectDeleted' => 'Das zu genehmigende Objekt wurde in iTop gelöscht.',

	//'Approval:Form:AlreadyApproved' => 'Sorry, the process has already been completed with result: Approved.',
	'Approval:Form:AlreadyApproved' => 'Der Prozess wurde bereits mit dem Ergebniss "Genehmigt" abgeschlossen.',
	//'Approval:Form:AlreadyRejected' => 'Sorry, the process has already been completed with result: Rejected.',
	'Approval:Form:AlreadyRejected' => 'Der Prozess wurde bereits mit dem Ergebnis "Abgelehnt" abgeschlossen.',

	//'Approval:Form:StepApproved' => 'Sorry, this phase has been completed with result: Approved. The approval process is continuing...',
	'Approval:Form:StepApproved' => 'Dieser Schritt wurde schon mit dem Ergebnis "Genehmigt" abgeschlossen. Der Genehmigungsprozess wird fortgesetzt...',
	//'Approval:Form:StepRejected' => 'Sorry, this phase has been completed with result: Rejected. The approval process is continuing...',
	'Approval:Form:StepRejected' => 'Dieser Schritt wurde schon mit dem Ergebnis "Abgelehnt" abgeschlossen. Der Genehmigungsprozess wird fortgesetzt...',

	//'Approval:Form:AnswerRecorded-Continue' => 'Your answer has been recorded. The approval process is continuing.',
	'Approval:Form:AnswerRecorded-Continue' => 'Die Auswahl wurde gespeichert. Der Genehmigungsprozess wird fortgesetzt.',
	//'Approval:Form:AnswerRecorded-Approved' => 'Your answer has been recorded: the approval is now complete with success.',
	'Approval:Form:AnswerRecorded-Approved' => 'Die Auswahl wurde gespeichert. Der Genehmigungsprozess ist erfolgreich abgeschlossen.',
	//'Approval:Form:AnswerRecorded-Rejected' => 'Your answer has been recorded: the approval is now complete with failure.',
	'Approval:Form:AnswerRecorded-Rejected' => 'Die Auswahl wurde gespeichert. Der Genehmigungsprozess ist mit dem Ergebniss "Abgelehnt" abgeschlossen.',

	//'Approval:ChangeTracking-MoreInfo' => 'Approval process',
	'Approval:ChangeTracking-MoreInfo' => 'Genehmigungsprozess',

	//'Approval:Ongoing-Title' => 'Ongoing approvals',
	'Approval:Ongoing-Title' => 'laufende Freigaben',
	//'Approval:Ongoing-Title+' => 'Approval processes for objects of class %1$s',
	'Approval:Ongoing-Title+' => 'Freigabe Prozesse für Objekte der Klasse %1$s',
	//'Approval:Ongoing-NothingCurrently' => 'There is no ongoing approval.',
	'Approval:Ongoing-NothingCurrently' => 'Es gibt keine laufenden Freigaben.',

	//'Approval:Remind-Btn' => 'Send a reminder...',
	'Approval:Remind-Btn' => 'Erinnerung versenden...',
	//'Approval:Remind-DlgTitle' => 'Send a reminder',
	'Approval:Remind-DlgTitle' => 'Erinnerung versenden',
	//'Approval:Remind-DlgBody' => 'The following contacts will be notified again:',
	'Approval:Remind-DlgBody' => 'Die folgenden Kontakte werden erneut benachrichtigt:',
	//'Approval:ReminderDone' => 'A reminder has been sent to %1$d persons.',
	'Approval:ReminderDone' => 'Eine Erinnerung wurde an %1$d Personen versandt.',
	//'Approval:Reminder-Subject' => '%1$s (reminder)',
	'Approval:Reminder-Subject' => '%1$s (Erinnerung)',
));
