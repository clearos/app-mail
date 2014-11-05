<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'mail';
$app['version'] = '2.0.1';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('mail_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('mail_app_name');
$app['category'] = lang('base_category_system');
$app['subcategory'] = lang('base_subcategory_settings');


/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['mail']['title'] = $app['name'];
$app['controllers']['domain']['title'] = lang('mail_mail_domain');
$app['controllers']['notification']['title'] = lang('mail_smtp_notification_settings');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

// FIXME:'app-openldap-core => 1:1.1.4 should be handled some other way... maybe
$app['core_requires'] = array(
    'app-mail-notification-core',
    'app-network-core',
    'app-openldap-core => 1:1.1.4',
);

$app['requires'] = array(
    'app-network',
);
