<?php

$capabilities = array(
    'report/coursecompletion:viewreport'=>array (
	'riskbitmask'=>RISK_PERSONAL,
	'captype'=>'read',
	'contextlevel'=>CONTEXT_SYSTEM,
	'archetypes'=>array(
	    'manager'=>CAP_ALLOW
	    )
	),
);

?>

