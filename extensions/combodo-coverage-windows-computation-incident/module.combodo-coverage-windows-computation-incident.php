<?php


SetupWebPage::AddModule(
	__FILE__,
	'combodo-coverage-windows-computation-incident/1.0.0',
	array(
		// Identification
		//
		'label' => 'Plug-in SLA computation with coverage windows for Incident',
		'category' => 'business',

		// Setup
		//
		'dependencies' => array(
			'combodo-sla-computation/1.0.0',
			'itop-service-mgmt/2.0.0||itop-service-mgmt-provider/2.0.0',
			'itop-incident-mgmt-itil/2.0.0',

		),
		'mandatory' => false,
		'visible' => true,

		// Components
		//
		'datamodel' => array(
			'main.combodo-coverage-windows-computation-incident.php',			
		),
		'webservice' => array(
			
		),
		'data.struct' => array(

		),
		'data.sample' => array(
		),
		
		// Documentation
		//
		'doc.manual_setup' => '',
		'doc.more_information' => '',

		// Default settings
		//
		'settings' => array(
		),
	)
);
?>
