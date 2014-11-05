<?php

/**
 * Mail notification settings.
 *
 * @category   apps
 * @package    mail
 * @subpackage views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011-2014 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearcenter.com/support/documentation/clearos/mail/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.  
//  
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('base');
$this->lang->load('mail');

///////////////////////////////////////////////////////////////////////////////
// Form handler
///////////////////////////////////////////////////////////////////////////////

if ($mode === 'edit') {
    $read_only = FALSE;
    $buttons = array(
        form_submit_update('submit'),
        form_submit_custom('update_and_test', lang('mail_update_and_test')),
        anchor_cancel('/app/mail/notification')
    );
} else {
    $read_only = TRUE;
    $buttons = array(
        anchor_edit('/app/mail/notification/edit'),
        anchor_custom('/app/mail/notification/test', lang('mail_test'), 'high')
    );
}

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open('mail/notification/edit');
echo form_header(lang('mail_smtp_notification_settings'));

echo field_input('host', $host, lang('mail_smtp_hostname'), $read_only);
echo field_input('port', $port, lang('mail_smtp_port'), $read_only);
echo field_dropdown('encryption', $encryption_options, $encryption, lang('mail_encryption'), $read_only);
echo field_input('username', $username, lang('mail_smtp_username'), $read_only);
if (! $read_only)
    echo field_password('password', $password, lang('mail_smtp_password'), $read_only);
echo field_input('sender', $sender, lang('mail_sender_address'), $read_only);
echo field_button_set($buttons);

echo form_footer();
echo form_close();
