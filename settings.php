<?php

// Add the link to the /report section of the Admin nav
$ADMIN->add(
	'reports',
	new admin_externalpage(
		'reportiomadfollowup',
		get_string('pluginname', 'report_iomadfollowup'),
		"/report/iomadfollowup/index.php",
		'moodle/site:viewreports'
	)
);

// tells moodle that plugin does not have any settings and only want to display link to external admin page.
$settings = null;
?>