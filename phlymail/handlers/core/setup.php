<?php
/**
 * mod.setup.php -> FrontEnd User Setup
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Core Handler
 * @subpackage Setup
 * @copyright 2001-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.7.8 2015-04-21 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$link_base = PHP_SELF.'?l=setup&h=core&'.give_passthrough(1).'&mode=';
$mode = (isset($_REQUEST['mode'])) ? $_REQUEST['mode'] : false;
$save_handler = 'email';
$Acnt = new DB_Controller_Account();

$featureSmsGwPath = $_PM_['path']['msggw'].'/'.$_PM_['core']['sms_use_gw'];
$featureSmsCredentialsPath = $_PM_['path']['conf'].'/msggw.'.$_PM_['core']['sms_use_gw'].'.ini.php';
$fetaureSmsActive = (isset($_PM_['core']['sms_feature_active']) && $_PM_['core']['sms_feature_active']);
if ($fetaureSmsActive) {
    $fetaureSmsActive = (isset($_PM_['core']['sms_active']) && $_PM_['core']['sms_active']);
}

// Prevent operations the user is not allowed to do
if (in_array($mode, array('addalias', 'editalias', 'dropalias', 'queryaliases', 'adduhead', 'edituhead', 'dropuhead', 'queryuheads'
                ,'addsignature', 'editsignature', 'dropsignature', 'getsignature', 'querysignatures', 'saveprofileorder'))
        && !$_SESSION['phM_privs']['all'] && !$_SESSION['phM_privs']['email_add_profile'] && !$_SESSION['phM_privs']['email_edit_profile']) {
    sendJS(array(), 1, 1);
}

if ('addalias' == $mode) {
    $sig = !strlen($_REQUEST['signature']) ? null : intval($_REQUEST['signature']);
    $Acnt->add_alias($_SESSION['phM_uid'], $_REQUEST['id'], $_REQUEST['email'], $_REQUEST['real_name'], $sig, $_REQUEST['sendvcf']);
    $mode = 'queryaliases';
}
if ('editalias' == $mode) {
    $sig = !strlen($_REQUEST['signature']) ? null : intval($_REQUEST['signature']);
    $Acnt->update_alias($_SESSION['phM_uid'], $_REQUEST['aid'], $_REQUEST['email'], $_REQUEST['real_name'], $sig, $_REQUEST['sendvcf']);
    $mode = 'queryaliases';
}
if ('dropalias' == $mode) {
    $Acnt->delete_alias($_SESSION['phM_uid'], $_REQUEST['aid']);
    $mode = 'queryaliases';
}
if ('queryaliases' == $mode ) {
    $data = $Acnt->getAccount($_SESSION['phM_uid'], $_REQUEST['id']);
    sendJS(array('alias' => $data['aliases']), 1, 1);
}

if ('adduhead' == $mode) {
    $hkey = preg_replace('![^\x21-\x39\x3B-\x7e]!', '', $_REQUEST['hkey']);
    $hval = preg_replace('!\r|\n!', '', $_REQUEST['hval']);
    $Acnt->add_uhead($_SESSION['phM_uid'], $_REQUEST['id'], $hkey, $hval);
    $mode = 'queryuheads';
}

if ('edituhead' == $mode) {
    $hkey = preg_replace('![^\x21-\x39\x3B-\x7e]!', '', $_REQUEST['hkey']);
    $hval = preg_replace('!\r|\n!', '', $_REQUEST['hval']);
    $Acnt->update_uhead($_SESSION['phM_uid'], $_REQUEST['id'], $_REQUEST['ohkey'], $hkey, $hval);
    $mode = 'queryuheads';
}

if ('dropuhead' == $mode) {
    $Acnt->delete_uhead($_SESSION['phM_uid'], $_REQUEST['id'], $_REQUEST['hkey']);
    $mode = 'queryuheads';
}

if ('queryuheads' == $mode ) {
    $return = array();
    $data = $Acnt->getAccount($_SESSION['phM_uid'], $_REQUEST['id']);
    if (!isset($data['userheaders']) || !is_array($data['userheaders'])) {
        $data['userheaders'] = array();
    }
    foreach ($data['userheaders'] as $hkey => $hval) {
        $return[] = array('hval' => $hval, 'hkey' => $hkey);
    }
    sendJS(array('uhead' => $return), 1, 1);
}

if ('addsignature' == $mode) {
    $sig = phm_stripslashes($_REQUEST['signature']);
    $sig_html = phm_stripslashes($_REQUEST['signature_html']);
    if ($sig_html == '<br />') {
        $sig_html = '';
    }
    $Acnt->add_signature($_SESSION['phM_uid'], $_REQUEST['title'], $sig, $sig_html);
    $mode = 'querysignatures';
}

if ('editsignature' == $mode) {
    $sig = phm_stripslashes($_REQUEST['signature']);
    $sig_html = phm_stripslashes($_REQUEST['signature_html']);
    if ($sig_html == '<br />') {
        $sig_html = '';
    }
    $Acnt->update_signature($_SESSION['phM_uid'], $_REQUEST['id'], $_REQUEST['title'], $sig, $sig_html);
    $mode = 'querysignatures';
}

if ('dropsignature' == $mode) {
    $Acnt->delete_signature($_SESSION['phM_uid'], $_REQUEST['id']);
    $mode = 'querysignatures';
}

if ('getsignature' == $mode) {
    $sig = $Acnt->get_signature($_SESSION['phM_uid'], $_REQUEST['id']);
    sendJS(array('signature' => $sig['signature'], 'signature_html' => $sig['signature_html']), 1, 1);
}

if ('querysignatures' == $mode ) {
    $return = array();
    $data = $Acnt->get_signature_list($_SESSION['phM_uid']);
    foreach ($data as $id => $signature) {
        $return[] = array('id' => $id, 'title' => $signature['title'] ? $signature['title'] : $WP_msg['undef']);
    }
    sendJS(array('signatures' => $return), 1, 1);
}

if ('saveprofileorder' == $mode) {
    $Acnt->reorderAccounts($_SESSION['phM_uid'], $_REQUEST['id']);
    sendJS(array('done' => 1), 1, 1);
}

if ('general' == $mode && ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['core_setup_settings'])) {
    $userdata = $DB->get_usrdata($_SESSION['phM_uid']);
    if (isset($_REQUEST['whattodo']) && 'save' == $_REQUEST['whattodo']) {
        $GlChFile = $DB->get_usr_choices($_SESSION['phM_uid']);
        $tokens = array
        		('core_theme_name' => array('req' => 'theme', 'def' => -1, 'chk' => 0)
                ,'core_mobile_theme_name' => array('req' => 'mobile_theme', 'def' => -1, 'chk' => 0)
                ,'core_language' => array('req' => 'lang', 'def' => -1, 'chk' => 0)
                ,'core_receipt_out' => array('req' => 'receiptout', 'def' => -1, 'chk' => 1)
                ,'core_send_html' => array('req' => 'sendhtml', 'def' => -1, 'chk' => 1)
                ,'core_sms_sender' => array('req' => 'smssender', 'def' => -1, 'chk' => 0)
                ,'core_pagesize' => array('req' => 'pagesize', 'def' => 150, 'chk' => 1)
        		,'core_automarkread' => array('req' => 'automarkread', 'def' => -1, 'chk' => 1)
                ,'core_automarkread_time' => array('req' => 'automarkread_time', 'def' => 0, 'chk' => 0)
                ,'core_newmail_showalert' => array('req' => 'alertmail', 'def' => -1, 'chk' => 1)
        		,'core_newmail_playsound' => array('req' => 'soundmail', 'def' => -1, 'chk' => 1)
                ,'core_newmail_soundfile' => array('req' => 'soundname', 'def' => -1, 'chk' => 0)
        		,'core_folders_usepreview' => array('req' => 'usepreview', 'def' => -1, 'chk' => 1)
                ,'core_plaintext_fontface' => array('req' => 'fontface', 'def' => -1, 'chk' => 0)
                ,'core_plaintext_fontsize' => array('req' => 'fontsize', 'def' => -1, 'chk' => 1)
        		,'core_showattachmentinline' => array('req' => 'showattachmentinline', 'def' => -1, 'chk' => 1)
                ,'core_mdn_behaviour' => array('req' => 'mdn_behaviour', 'def' => -1, 'chk' => 0)
                ,'core_logout_emptytrash' => array('req' => 'emptytrash', 'def' => -1, 'chk' => 1)
        		,'core_logout_emptyjunk' => array('req' => 'emptyjunk', 'def' => -1, 'chk' => 1)
                ,'core_logout_showprompt' => array('req' => 'logout_showprompt', 'def' => -1, 'chk' => 1)
                ,'core_email_preferred_part' => array('req' => 'email_preferred_part', 'def' => 'html', 'chk' => 1)
                ,'core_timezone' => array('req' => 'timezone', 'def' => -1, 'chk' => 0)
                ,'core_reply_samewin' => array('req' => 'answersamewin', 'def' => 0, 'chk' => 0)
                ,'core_teletype' => array('req' => 'teletype', 'def' => 'pro', 'chk' => 0)
                ,'core_answer_style' => array('req' => 'answer_style', 'def' => 'default', 'chk' => 1)
        		,'core_sentfolder_sms' => array('req' => 'sentfolder_sms', 'def' => 0, 'chk' => 1)
                ,'core_replysamefolder' => array('req' => 'replysamefolder', 'def' => -1, 'chk' => 1)
                ,'core_parsesmileys' => array('req' => 'parsesmileys', 'def' => 0, 'chk' => 1)
        		,'core_parseformat' => array('req' => 'parseformat', 'def' => 0, 'chk' => 1)
                ,'core_email_collapse_threads' => array('req' => 'collapse_threads', 'def' => 0, 'chk' => 1)
        		,'core_fax_sender' => array('req' => 'faxsender', 'def' => -1, 'chk' => 1)
                ,'core_fax_sender_name' => array('req' => 'faxsendername', 'def' => '', 'chk' => 1)
                ,'core_sentfolder_fax' => array('req' => 'sentfolder_fax', 'def' => 0, 'chk' => 1)
        		,'core_fax_status_email' => array('req' => 'faxstatusemail', 'def' => '', 'chk' => 1)
                ,'core_reply_dontcutsignatures' => array('req' => 'reply_dontcutsignatures', 'def' => 0, 'chk' => 1)
                ,'email_delete_markread' => array('req' => 'email_delete_markread', 'def' => 0, 'chk' => 1)
        		,'archive_override_delete' => array('req' => 'archive_override_delete', 'def' => 0, 'chk' => 1)
                ,'archive_mimic_foldertree' => array('req' => 'archive_mimic_foldertree', 'def' => 0, 'chk' => 1)
                ,'archive_partition_by_year' => array('req' => 'archive_partition_by_year', 'def' => 0, 'chk' => 1)
                ,'archive_email_autoarchive' => array('req' => 'archive_email_autoarchive', 'def' => 0, 'chk' => 1)
                ,'archive_email_autoarchive_age' => array('req' => 'archive_email_autoarchive_age', 'def' => -1, 'chk' => 0)
                ,'archive_email_autodelete' => array('req' => 'archive_email_autodelete', 'def' => 0, 'chk' => 1)
                ,'archive_email_autodelete_age' => array('req' => 'archive_email_autodelete_age', 'def' => -1, 'chk' => 0)
                ,'archive_calendar_autoarchive' => array('req' => 'archive_calendar_autoarchive', 'def' => 0, 'chk' => 1)
                ,'archive_calendar_autoarchive_age' => array('req' => 'archive_calendar_autoarchive_age', 'def' => -1, 'chk' => 0)
                ,'archive_calendar_autodelete' => array('req' => 'archive_calendar_autodelete', 'def' => 0, 'chk' => 1)
                ,'archive_calendar_autodelete_age' => array('req' => 'archive_calendar_autodelete_age', 'def' => -1, 'chk' => 0)
        		);
        if (defined('PHM_MOBILE')) {
            // These options are NOT available in the mobile frontend
            foreach (array('core_newmail_showalert', 'core_newmail_playsound', 'core_newmail_soundfile',
                    'core_reply_samewin', 'core_folders_usepreview', 'core_showattachmentinline',
                    'core_plaintext_fontface', 'core_plaintext_fontsize',
                    'core_teletype', 'core_send_html') as $tokunset) {
                unset($tokens[$tokunset]);
            }
        }
        // Die sind zweigeteilt und werden jetzt schnell zusammengfügt...
        foreach (array('archive_email_autoarchive', 'archive_email_autodelete', 'archive_calendar_autoarchive', 'archive_calendar_autodelete') as $pruef) {
            // Kleb...
            if (!empty($_REQUEST[$pruef.'_age_inp']) && !empty($_REQUEST[$pruef.'_age_drop'])) {
                $_REQUEST[$pruef.'_age'] = $_REQUEST[$pruef.'_age_inp'].' '.$_REQUEST[$pruef.'_age_drop'];
            }
            // Kein wert -> Checkbox ausknipsen
            if (empty($_REQUEST[$pruef.'_age'])) {
                $_REQUEST[$pruef] = '0';
            }
        }

        foreach ($tokens as $token => $detail) {
            // Ingore not defined values, except checkboxes, which are never sent when unchecked
            if ($detail['chk'] == 0 && !isset($_REQUEST[$detail['req']])) {
                continue;
            }
            $result = isset($_REQUEST[$detail['req']]) ? $_REQUEST[$detail['req']] : $detail['def'];

            if ($result == -1) {
                $result = '';
            }
            // split up to have first part and rest of token separated
            $v = explode('_', $token, 2);
            // Overwrite current settings with processed request data
            $GlChFile[$v[0]][$v[1]] = $result;
        }
        if (isset($_REQUEST['loginfolder'])) {
            if (!$_REQUEST['loginfolder']) {
                $GlChFile['core']['login_handler'] = $GlChFile['core']['login_folder'] = false;
            } elseif (preg_match('!^([a-z_]+)\:\:(root|[0-9]+)$!i', $_REQUEST['loginfolder'], $found)) {
                $GlChFile['core']['login_handler'] = $found[1];
                $GlChFile['core']['login_folder'] = $found[2];
            }
        }
        if (!empty($_REQUEST['pw'])) {
            if ($_REQUEST['pw'] != $_REQUEST['pw2']) {
                $WP_return = $WP_msg['SuPW1notPW2'];
            } else {
                // Tell backend API about password change
                require_once($_PM_['path']['admin'].'/lib/configapi.class.php');
                $cAPI = new configapi($_PM_);
                $cAPI->edit_user($_SESSION['phM_uid'], $_SESSION['phM_username'], $_REQUEST['pw'], '', $_SESSION['phM_username']);
                unset($cAPI);
            }
        }
        // Check validity of given SMS sender
        if (isset($_REQUEST['smssender']) && $_REQUEST['smssender']) {
            require_once($featureSmsGwPath . '/phm_shortmessage.php');
            $GW = new phm_shortmessage($featureSmsGwPath, $featureSmsCredentialsPath);
            $out = $GW->wash_input(array('from' => $_REQUEST['smssender']));
            if (!is_array($out)) {
                $WP_return = $WP_msg['ESMSFormat'];
            }
        }
        // Check validity of given Fax number
        if (isset($_REQUEST['faxsender']) && $_REQUEST['faxsender']) {
            require_once($featureSmsGwPath . '/phm_shortmessage.php');
            $GW = new phm_shortmessage($featureSmsGwPath, $featureSmsCredentialsPath);
            $out = $GW->wash_input(array('to' => $_REQUEST['faxsender'])); // Make sure, this really is a phone number
            if (!is_array($out)) {
                $WP_return = $WP_msg['EFaxFormat'];
            }
        }
        // User might switch the Teletype setting, this is held in the session in parallel
        if (isset($_SESSION['phM_tt'])) {
            $_SESSION['phM_tt'] = $GlChFile['core']['teletype'];
        }
        // 2FA: verify token given
        if (!empty($_REQUEST['2fa_sms_verify'])) {
            if ($_REQUEST['2fa_sms_verify'] == $GlChFile['2fa']['sms_verify_token']) {
                $GlChFile['2fa']['sms_to'] = $GlChFile['2fa']['sms_register'];
                unset($GlChFile['2fa']['sms_register']);
                unset($_REQUEST['2fa_sms_register']);
            } else {
                $WP_return = $WP_msg['2FaModeSmsEVerifyCodeInvalid'];
            }
            unset($GlChFile['2fa']['sms_verify_token']);
        }
        // 2FA: SMS number got passed to us
        if (!empty($_REQUEST['2fa_sms_register'])) {
            if (empty($GlChFile['2fa']['sms_verify_token'])
                    || !empty($_REQUEST['2fa_sms_resendverify'])
                    || (empty($GlChFile['2fa']['sms_register'])
                            || $GlChFile['2fa']['sms_register'] != $_REQUEST['2fa_sms_register'])
                    ) {
                require_once($featureSmsGwPath . '/phm_shortmessage.php');
                $GW = new phm_shortmessage($featureSmsGwPath, $featureSmsCredentialsPath);
                $out = $GW->wash_input(array('from' => $_REQUEST['2fa_sms_register']));
                if (!is_array($out)) {
                    $WP_return = $WP_msg['ESMSFormat'];
                } else {
                    $GlChFile['2fa']['sms_register'] = $out['from'];
                    $GlChFile['2fa']['sms_verify_token'] = SecurePassword::generate(6, false, STRONGPASS_DECIMALS);
                    $status = $GW->send_sms(
                            array(
                                    'from' => $out['from'],
                                    'to' => $out['from'],
                                    'text' => decode_utf8(str_replace('$token$', $GlChFile['2fa']['sms_verify_token'], $WP_msg['2FaModeSmsVerifyCodeText']))
                            ),
                            'sms'
                            );
                }
            }
        }
        if (!empty($_REQUEST['2fa_sms_unregister'])) {
            unset($GlChFile['2fa']['sms_to']);
        }

        if (!empty($_REQUEST['2fa_u2f_register'])) {
            @include_once 'Auth/Yubico.php';
            if (class_exists('Auth_Yubico')) {
                $yubi = new Auth_Yubico($_PM_core['2fa']['yubi_client_id'], $_PM_core['2fa']['yubi_client_key']);
                $yubi->verify($_REQUEST['2fa_u2f_register']);
                if (PEAR::isError($auth)) {
                    $WP_return = $auth->getMessage();
                } else {
                    $GlChFile['2fa']['u2f_serial'] = substr($_REQUEST['2fa_u2f_register'], 0, 12);
                }
            }
        }

        if (!empty($_REQUEST['2fa_mode'])) {
            $GlChFile['2fa']['mode'] = 'none';
            if ($_REQUEST['2fa_mode'] == 'sms'
                    && !empty($GlChFile['2fa']['sms_to'])) {
                $GlChFile['2fa']['mode'] = 'sms';
            }
            if ($_REQUEST['2fa_mode'] == 'u2f'
                    && !empty($GlChFile['2fa']['u2f_serial'])) {
                $GlChFile['2fa']['mode'] = 'u2f';
            }
        }

        if (!isset($WP_return)) {
            // Store some settings in cookies so they are available before login
            setcookie('phlyMail_Language', $GlChFile['core']['language'], time()+24*3600*1461, null, null, PHM_FORCE_SSL);
            setcookie('phlyMail_Theme', $GlChFile['core']['theme_name'], time()+24*3600*1461, null, null, PHM_FORCE_SSL);
            setcookie('phlyMail_Mobile_Theme', $GlChFile['core']['mobile_theme_name'], time()+24*3600*1461, null, null, PHM_FORCE_SSL);

            if ($_REQUEST['externalemail'] != $userdata['externalemail']) {
                // Tell backend API about change of external email
                require_once($_PM_['path']['admin'].'/lib/configapi.class.php');
                $cAPI = new configapi($_PM_);
                $cAPI->edit_user($_SESSION['phM_uid'], $_SESSION['phM_username'], $_REQUEST['pw'], $_REQUEST['externalemail'], '');
                unset($cAPI);
                $userdata['externalemail'] = $_REQUEST['externalemail'];
            }
            if (!empty($_REQUEST['pw'])) {
                $userdata['password'] = $_REQUEST['pw'];
                $userdata['salt'] = $_PM_['auth']['system_salt'];
            }
            $userdata['uid'] = $_SESSION['phM_uid'];
            $DB->upd_user($userdata);
            $WP_return = $DB->set_usr_choices($_SESSION['phM_uid'], $GlChFile) ? $WP_msg['optssaved'] : $WP_msg['optsnosave'];
        }
        header('Location: '.$link_base.'general&WP_return='.urlencode($WP_return).(!empty($_REQUEST['init_tab']) ? '&init_tab='.urlencode($_REQUEST['init_tab']) : ''));
        exit();
    }
    $data = $DB->get_usrdata($_SESSION['phM_uid']);
    $tpl = new phlyTemplate($_PM_['path']['templates'].'setup.general.tpl');
    if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['core_change_theme']) {

        $skins = array();
        $themeEngine = trim(file_get_contents($_PM_['path']['conf'].'/theme.engine'));

        $t_ht = $tpl->get_block('has_themes');
        $d_ = opendir($_PM_['path']['theme_dir']);
        while (false !== ($skinname = readdir($d_))) {
            if ($skinname == '.' || $skinname == '..'
                    || !is_dir($_PM_['path']['theme_dir'].'/'.$skinname)
                    || !file_exists($_PM_['path']['theme_dir'].'/'.$skinname.'/choices.ini.php')) {
                continue;
            }
            // Read theme's chocies
            $thChoi = parse_ini_file($_PM_['path']['theme_dir'].'/'.$skinname.'/choices.ini.php');
            // Must match any of the supported client types
            if (empty($thChoi['client_type']) || ($thChoi['client_type'] != 'mobile' && $thChoi['client_type'] != 'desktop')) {
                continue;
            }
            // Has engine setting and version matches?
            if (!isset($thChoi['engine']) || $thChoi['engine'] != $themeEngine) {
                continue;
            }
            $skins[$skinname] = $thChoi;
        }
        closedir($d_);
        ksort($skins);

        $t_s = $t_ht->get_block('skinline');
        $t_ms = $t_ht->get_block('mobiletheme');
        foreach ($skins as $skinname => $thChoi) {
            if ($thChoi['client_type'] == 'mobile') {
                $t_ms->assign('name', $skinname);
                if (!empty($_PM_['core']['mobile_theme_name']) && $skinname == $_PM_['core']['mobile_theme_name']) {
                    $t_ms->assign_block('sel');
                }
                $t_ht->assign('mobiletheme', $t_ms);
                $t_ms->clear();
                continue;
            }
            if ($thChoi['client_type'] == 'desktop') {
                $t_s->assign('skinname', $skinname);
                if ($skinname == $_PM_['core']['theme_name']) {
                    $t_s->assign_block('sel');
                }
                $t_ht->assign('skinline', $t_s);
                $t_s->clear();
                continue;
            }
        }
        $tpl->assign('has_themes', $t_ht);
    }

    $tpl->assign(array
            ('target_link' => htmlspecialchars($link_base.'general&whattodo=save')
            ,'link_base' => $link_base
            ,'WP_return' => isset($_REQUEST['WP_return']) ? $_REQUEST['WP_return'] : ''
            ,'fontsize' => isset($_PM_['core']['plaintext_fontsize']) ? $_PM_['core']['plaintext_fontsize'] : '12'
            ,'pagesize' => $_PM_['core']['pagesize']
            ,'externalemail' => $userdata['externalemail']
            ));
    if (isset($_PM_['core']['sms_feature_active']) && $_PM_['core']['sms_feature_active']) {
        $tpl->fill_block('smssender', 'sms_sender', isset($_PM_['core']['sms_sender']) ? $_PM_['core']['sms_sender'] : '');
    }
    if ((isset($_PM_['core']['fax_default_active']) && $_PM_['core']['fax_default_active'])
            || (isset($_PM_['core']['fax_active']) && $_PM_['core']['fax_active'])) {
        $tpl->fill_block('faxsender', array(
                'fax_sender' => isset($_PM_['core']['fax_sender']) ? $_PM_['core']['fax_sender'] : '',
                'fax_sender_name' => isset($_PM_['core']['fax_sender_name']) ? $_PM_['core']['fax_sender_name'] : '',
                'fax_status_email' => isset($_PM_['core']['fax_status_email']) ? $_PM_['core']['fax_status_email'] : ''
                ));
    }
    if (isset($_PM_['core']['answer_style']) && $_PM_['core']['answer_style'] == 'tofu') {
        $tpl->assign_block('answer_style_tofu');
    } else {
        $tpl->assign_block('answer_style_default');
    }
    // Allow selection of login folder, sent folder SMS and sent folder faxes
    $tplHasLoginFolder = $tpl->block_exists('loginfolder');
    if ($tplHasLoginFolder) {
        if (!isset($_PM_['core']['login_handler'])) {
            $_PM_['core']['login_handler'] = '';
        }
        if (!isset($_PM_['core']['login_folder'])) {
            $_PM_['core']['login_folder']  = '';
        }
        $t_lifo = $tpl->get_block('loginfolder');
    }

    if (!isset($_PM_['core']['sentfolder_sms'])) {
        $_PM_['core']['sentfolder_sms'] = 0;
    }
    $t_smsfo = $tpl->get_block('sentfolder_sms');
    $t_faxfo = $tpl->get_block('sentfolder_fax');
    foreach ($_SESSION['phM_uniqe_handlers'] as $type => $data) {
        $clsnam = 'handler_'.$type.'_api';
        if (!file_exists($_PM_['path']['handler'].'/'.basename($type).'/api.php')) {
            continue;
        }
        require_once($_PM_['path']['handler'].'/'.basename($type).'/api.php');
        if (!in_array('give_folderlist', get_class_methods($clsnam))) {
            continue;
        }
        if ($tplHasLoginFolder) {
            // Denote the handler the following structure belongs to
            $t_lifo->assign(array
                    ('id' => '" style="color:darkgray;" disabled="disabled'
                    ,'name' => phm_entities($data['i18n'])
                    ));
            $tpl->assign('loginfolder', $t_lifo);
            $t_lifo->clear();
        }
        // Actual folder structure output
        $API = new $clsnam($_PM_, $_SESSION['phM_uid']);
        foreach ($API->give_folderlist() as $k => $v) {
            $lvl_space = str_repeat('&nbsp;', ($v['level']+1)*2);
            if ($tplHasLoginFolder) {
                $t_lifo->assign(array(
                        'id' => (!$v['has_items']) ? '" style="color:darkgray;" disabled="disabled' : $type.'::'.$k,
                        'name' => $lvl_space.phm_entities($v['foldername'])
                        ));
                if ($_PM_['core']['login_handler'] == $type && $_PM_['core']['login_folder'] == $k) {
                    $t_lifo->assign_block('sel');
                }
                $tpl->assign('loginfolder', $t_lifo);
                $t_lifo->clear();
            }
            // While filling login folder also fill sent SMS / Fax default folder
            if ($type == 'email') {
                $lvl_space = str_repeat('&nbsp;', ($v['level'])*2);
                $t_smsfo->assign(array(
                        'id' => (!$v['has_items']) ? '" style="color:darkgray;" disabled="disabled' : $k,
                        'name' => $lvl_space.phm_entities($v['foldername'])
                        ));
                if (!empty($_PM_['core']['sentfolder_sms']) && $_PM_['core']['sentfolder_sms'] == $k) {
                    $t_smsfo->assign_block('sel');
                }
                $tpl->assign('sentfolder_sms', $t_smsfo);
                $t_smsfo->clear();
                $t_faxfo->assign(array(
                        'id' => (!$v['has_items']) ? '" style="color:darkgray;" disabled="disabled' : $k,
                        'name' => $lvl_space.phm_entities($v['foldername'])
                        ));
                if (!empty($_PM_['core']['sentfolder_fax']) && $_PM_['core']['sentfolder_fax'] == $k) {
                    $t_faxfo->assign_block('sel');
                }
                $tpl->assign('sentfolder_fax', $t_faxfo);
                $t_faxfo->clear();
            }
        }
    }
    $langs = $langnames = array();
    $d_ = opendir($_PM_['path']['message']);
    while (false !== ($langname = readdir($d_))) {
        if ($langname == '.' || $langname == '..') {
            continue;
        }
        if (!preg_match('/\.php$/i', trim($langname))) {
            continue;
        }
        preg_match(
                '!\$WP_msg\[\'language_name\'\]\ \=\ \'([^\']+)\'!',
                file_get_contents($_PM_['path']['message'].'/'.$langname),
                $found
                );
        $langname = preg_replace('/\.php$/i', '', trim($langname));
        $langs[] = $found[1];
        $langnames[] = $langname;
    }
    closedir($d_);
    array_multisort($langs, SORT_ASC, $langnames);
    $t_s = $tpl->get_block('langline');
    foreach($langs as $id => $langname) {
        $t_s->assign(array('id' => $langnames[$id], 'langname' => $langname));
        if ($langnames[$id] == $_PM_['core']['language']) {
            $t_s->assign_block('sel');
        }
        $tpl->assign('langline', $t_s);
        $t_s->clear();
    }
    if ($tpl->block_exists('fontface')) {
        $myfonts = $_PM_['path']['conf'].'/global.fontlist.phml';
        if (file_exists($myfonts) && is_readable($myfonts)) {
            $t_ff = $tpl->get_block('fontface');
            foreach (file($myfonts) as $line) {
                $line = trim($line);
                if (!$line) {
                    continue;
                }
                if ($line{0} == '#') {
                    continue;
                }
                if (preg_match('![^-a-zA-Z0-9,\s]!', $line)) {
                    continue;
                }
                $t_ff->assign('face', $line);
                if (isset($_PM_['core']['plaintext_fontface']) && $_PM_['core']['plaintext_fontface'] == $line) {
                    $t_ff->assign_block('sel');
                }
                $tpl->assign('fontface', $t_ff);
                $t_ff->clear();
            }
        } else {
            $tpl->fill_block('fontface', 'face', 'Arial, Helvetica, Verdana, sans-serif');
        }
    }

    if ($tpl->block_exists('soundnames')) {
        $d_ = opendir($_PM_['path']['frontend'].'/sounds');
        while (false !== ($soundname = readdir($d_))) {
            if ($soundname == '.' || $soundname == '..') {
                continue;
            }
            if (!preg_match('!\.(mp3|phsnd)$!', $soundname)) {
                continue;
            }
            $sounds[] = $soundname;
        }
        closedir($d_);
        sort($sounds);
        $t_s = $tpl->get_block('soundnames');
        foreach ($sounds as $soundname) {
            $t_s->assign('name', $soundname);
            if (isset($_PM_['core']['newmail_soundfile']) && $soundname == $_PM_['core']['newmail_soundfile']) {
                $t_s->assign_block('sel');
            }
            $tpl->assign('soundnames', $t_s);
            $t_s->clear();
        }
    }

    $t_ff = $tpl->get_block('timezone');
    foreach (DateTimeZone::listIdentifiers() as $line) {
 		$t_ff->assign(array('zone' => $line, 'zonename' => str_replace('_', ' ', $line)));
   		if (PHM_TIMEZONE == $line) {
   		    $t_ff->assign_block('sel');
   		}
        $tpl->assign('timezone', $t_ff);
        $t_ff->clear();
    }

    if (!isset($_PM_['core']['mdn_behaviour'])) {
        $_PM_['core']['mdn_behaviour'] = 'none';
    }
    $t_mdn = $tpl->get_block('mdnline');
    foreach (array('none' => 'optmdnbeha_never', 'ask' => 'optmdnbeha_ask', 'always' => 'optmdnbeha_send') as $behaviour => $message) {
        $t_mdn->assign(array('behaviour' => $behaviour, 'behaviourname' => $WP_msg[$message]));
        if ($_PM_['core']['mdn_behaviour'] == $behaviour) {
            $t_mdn->assign_block('sel');
        }
        $tpl->assign('mdnline', $t_mdn);
        $t_mdn->clear();
    }
    if (!isset($_PM_['core']['email_preferred_part'])) {
        $_PM_['core']['email_preferred_part'] = 'html';
    }
    $t_epp = $tpl->get_block('mailpreferredpart');
    foreach (array('html' => 'optpreferred_html', 'text' => 'optpreferred_text') as $part => $message) {
        $t_epp->assign(array('part' => $part, 'partname' => $WP_msg[$message]));
        if ($_PM_['core']['email_preferred_part'] == $part) {
            $t_epp->assign_block('sel');
        }
        $tpl->assign('mailpreferredpart', $t_epp);
        $t_epp->clear();
    }

    if (!empty($_PM_['core']['receipt_out'])) $tpl->assign_block('receipt');
    if (!empty($_PM_['core']['folders_usepreview']) && $tpl->block_exists('preview')) $tpl->assign_block('preview');
    if (!empty($_PM_['core']['newmail_showalert']) && $tpl->block_exists('alertmail')) $tpl->assign_block('alertmail');
    if (!empty($_PM_['core']['newmail_playsound']) && $tpl->block_exists('soundmail')) $tpl->assign_block('soundmail');
    if (!empty($_PM_['core']['send_html']) && $tpl->block_exists('sendhtml')) $tpl->assign_block('sendhtml');
    if (!empty($_PM_['core']['showattachmentinline']) && $tpl->block_exists('showattachmentinline')) $tpl->assign_block('showattachmentinline');
    if (!empty($_PM_['core']['logout_emptytrash'])) $tpl->assign_block('emptytrash');
    if (!empty($_PM_['core']['logout_emptyjunk'])) $tpl->assign_block('emptyjunk');
    if (!isset($_PM_['core']['logout_showprompt']) || $_PM_['core']['logout_showprompt']) $tpl->assign_block('logoutshowprompt');
    if (!empty($_PM_['core']['reply_samewin']) && $tpl->block_exists('answersamewin')) $tpl->assign_block('answersamewin');
    if (!empty($_PM_['core']['reply_dontcutsignatures'])) $tpl->assign_block('reply_dontcutsignatures');
    if (!empty($_PM_['core']['teletype']) && $_PM_['core']['teletype'] == 'sys' && $tpl->block_exists('teletype')) $tpl->assign_block('teletype');
    if (!empty($_PM_['core']['automarkread']) && $tpl->block_exists('automarkread')) $tpl->assign_block('automarkread');
    if (isset($_PM_['core']['automarkread_time'])) $tpl->assign('automarkread_time', intval($_PM_['core']['automarkread_time']));
    if (!empty($_PM_['core']['replysamefolder'])) $tpl->assign_block('replysamefolder');
    if (!isset($_PM_['core']['parsesmileys']) || $_PM_['core']['parsesmileys']) $tpl->assign_block('parsesmileys');
    if (!isset($_PM_['core']['parseformat']) || $_PM_['core']['parseformat']) $tpl->assign_block('parseformat');
    if (!empty($_PM_['core']['email_collapse_threads'])) $tpl->assign_block('collapse_threads');
    if (!empty($_PM_['email']['delete_markread'])) $tpl->assign_block('email_delete_markread');
    if (!empty($_PM_['archive']['override_delete'])) $tpl->assign_block('archive_override_delete');
    if (!empty($_PM_['archive']['mimic_foldertree'])) $tpl->assign_block('archive_mimic_foldertree');
    if (!empty($_PM_['archive']['partition_by_year'])) $tpl->assign_block('archive_partition_by_year');
    if (!empty($_PM_['archive']['email_autoarchive'])) $tpl->assign_block('archive_email_autoarchive');
    if (!empty($_PM_['archive']['email_autodelete'])) $tpl->assign_block('archive_email_autodelete');
    if (!empty($_PM_['archive']['calendar_autoarchive'])) $tpl->assign_block('archive_calendar_autoarchive');
    if (!empty($_PM_['archive']['calendar_autodelete'])) {
        $tpl->assign_block('archive_calendar_autodelete');
    }

    foreach (array('email_autoarchive_age' ,'email_autodelete_age' ,'calendar_autoarchive_age' ,'calendar_autodelete_age') as $token) {
        $val = 1;
        $unit = 'year';
        if (!empty($_PM_['archive'][$token]) && preg_match('!^(\d+)\s([a-zA-Z]+)$!', $_PM_['archive'][$token], $found)) {
            $val = $found[1];
            $unit = $found[2];
        }
        $tpl->assign('archive_'.$token, $val);
        $t_blk = $tpl->get_block('archive_'.$token.'_drop');
        foreach (array('day' => $WP_msg['Days'], 'week' => $WP_msg['Weeks'], 'month' => $WP_msg['Months'], 'year' => $WP_msg['Years']) as $k => $v) {
            $t_blk->assign(array('unit' => $k, 'name' => $v));
            if ($unit == $k) {
                $t_blk->assign_block('sel');
            }
            $tpl->assign('archive_'.$token.'_drop', $t_blk);
            $t_blk->clear();
        }
    }

    //
    // Two Factor Auth (2FA)
    //
    try {
        @include_once 'Auth/Yubico.php';
        if (!class_exists('Auth_Yubico')) {
            $_PM_['2fa']['yubi_secret_key'] = false;
        }
    } catch (Exception $e) {
        // Requiring the PEAR module failed with an exception.
        // This is unrecoverable
        $_PM_['2fa']['yubi_secret_key'] = false;
    }

    if (empty($_PM_['2fa']['yubi_client_id'])
            || empty($_PM_['2fa']['yubi_secret_key'])
            || !class_exists('Auth_Yubico')) {
        $tpl->assign_block('2fa_no_u2f');
        if (!empty($_PM_['2fa']['mode']) && $_PM_['2fa']['mode'] == 'u2f') {
            $_PM_['2fa']['mode'] = 'none';
        }
    }
    if (!$fetaureSmsActive) {
        $tpl->assign_block('2fa_no_sms');
        if (!empty($_PM_['2fa']['mode']) && $_PM_['2fa']['mode'] == 'sms') {
            $_PM_['2fa']['mode'] = 'none';
        }
    }

    if (empty($_PM_['2fa']['mode'])
            || $_PM_['2fa']['mode'] == 'none') {
        $tpl->assign_block('2fa_mode_none');
    } elseif ($_PM_['2fa']['mode'] == 'sms') {
        $tpl->assign_block('2fa_mode_sms');
    } elseif ($_PM_['2fa']['mode'] == 'u2f') {
        $tpl->assign_block('2fa_mode_u2f');
    }
    // SMS
    if (!empty($_PM_['2fa']['sms_to'])
            && empty($_PM_['2fa']['sms_verify_token'])) {
        $tpl->assign_block('2fa_registered_sms');
    } else {
        if (!empty($_PM_['2fa']['sms_verify_token'])) {
            $tpl->assign_block('2fa_verify_sms');
        }
        $tpl->assign_block('2fa_register_sms');
    }
    // Yubikey / U2F
    if (!empty($_PM_['2fa']['u2f_serial'])) {
        $tpl->assign_block('2fa_registered_u2f');
    } else {
        $tpl->assign_block('2fa_register_u2f');
    }

    if (!empty($_PM_['2fa']['sms_to'])) {
        $tpl->assign('2fa_sms_to', phm_entities($_PM_['2fa']['sms_to']));
    }
    if (!empty($_PM_['2fa']['sms_register'])) {
        $tpl->assign('2fa_sms_register', phm_entities($_PM_['2fa']['sms_register']));
    }
    if (!empty($_PM_['2fa']['u2f_serial'])) {
        $tpl->assign('2fa_u2f_serial', phm_entities($_PM_['2fa']['u2f_serial']));
    }


    // Allow JS to switch to a given tab on load
    if (!empty($_REQUEST['init_tab'])) {
        $tpl->assign('initital_tabulator', htmlentities($_REQUEST['init_tab']));
    } elseif (!empty($initital_tab)) {
        $tpl->assign('initital_tabulator', htmlentities($initital_tab));
    }
}

if ($mode == 'converttype' && ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['email_edit_profile']) ) {
    $account = (!empty($_REQUEST['account'])) ? $_REQUEST['account'] : false;
    $Acnt->convertAccount($_SESSION['phM_uid'], $account);
    sendJS(array('ok'), 1, 1);
}

if (($mode == 'saveold' && ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['email_edit_profile']) )
        || ($mode == 'savenew' && ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['email_add_profile']))) {
    $acctype = isset($_REQUEST['acctype']) ? $_REQUEST['acctype'] : 'pop3';
    $error = '';
    $account = (isset($_REQUEST['account'])) ? $_REQUEST['account'] : false;
    if ('' == $_REQUEST['popname']) {
        $error .= $WP_msg['enterProfname'].LF;
    }
    if ('' == $_REQUEST['popserver']) {
        $error .= ($acctype == 'imap' ? 'IMAP' : 'POP3').': '.$WP_msg['enterPOPserver'].LF;
    }
    if ('' == $_REQUEST['popuser'])  {
        $error .= ($acctype == 'imap' ? 'IMAP' : 'POP3').': '.$WP_msg['enterPOPuser'].LF;
    }
    if ('saveold' == $mode) {
        $check_accid = $Acnt->AccountNameExists($_SESSION['phM_uid'], $_REQUEST['popname']);
        if (isset($check_accid) && $account != $check_accid && $check_accid != '') {
            $error .= $account.'/'.$check_accid.': '.$WP_msg['SuPrfExists'];
        }
    } else {
        if ($Acnt->AccountNameExists($_SESSION['phM_uid'], $_REQUEST['popname'])) {
            $error .= $WP_msg['SuPrfExists'];
        }
    }
    if (!$error) {
        if ('savenew' == $mode) {
            $account = $Acnt->addAccount(array
                    ('uid' => $_SESSION['phM_uid']
                    ,'accname' => $_REQUEST['popname']
                    ,'checkevery' => $_REQUEST['checkevery']
                    ,'accid' => $Acnt->getMaxAccountId($_SESSION['phM_uid'])
                    ,'checkspam' => isset($_REQUEST['checkspam']) ? $_REQUEST['checkspam'] : 0
                    ,'acctype' => $acctype
                    ,'sig_on' => isset($_REQUEST['sig_on']) ? $_REQUEST['sig_on'] : 0
                    ,'popserver' => $_REQUEST['popserver']
                    ,'popport' => $_REQUEST['popport']
                    ,'popuser' => $_REQUEST['popuser']
                    ,'poppass' => $_REQUEST['poppass']
                    ,'popsec' => !empty($_REQUEST['popsec']) ? basename($_REQUEST['popsec']) : 'SSL'
                    ,'popallowselfsigned' => !empty($_REQUEST['popallowselfsigned']) ? 1 : 0
                    ,'leaveonserver' => isset($_REQUEST['leaveonserver']) ? $_REQUEST['leaveonserver'] : 0
                    ,'localkillserver' => isset($_REQUEST['localkillserver']) ? $_REQUEST['localkillserver'] : 0
                    ,'onlysubscribed' => isset($_REQUEST['onlysubscribed']) ? $_REQUEST['onlysubscribed'] : 0
                    ,'cachetype' => isset($_REQUEST['cachetype']) ? $_REQUEST['cachetype'] : 'struct'
                    ,'imapprefix' => isset($_REQUEST['imapprefix']) ? $_REQUEST['imapprefix'] : ''
                    ,'trustspamfilter' => isset($_REQUEST['trustspamfilter']) ? $_REQUEST['trustspamfilter'] : 0
                    ,'inbox' => isset($_REQUEST['inbox']) ? $_REQUEST['inbox'] : '0'
                    ,'sent' => isset($_REQUEST['sent_objects']) ? $_REQUEST['sent_objects'] : '0'
                    ,'drafts' => isset($_REQUEST['drafts']) ? $_REQUEST['drafts'] : '0'
                    ,'templates' => isset($_REQUEST['templates']) ? $_REQUEST['templates'] : '0'
                    ,'archive' => isset($_REQUEST['archive']) ? $_REQUEST['archive'] : '0'
                    ,'junk' => isset($_REQUEST['junk']) ? $_REQUEST['junk'] : '0'
                    ,'waste' => isset($_REQUEST['waste']) ? $_REQUEST['waste'] : '0'
                    ,'real_name' => $_REQUEST['real_name']
                    ,'address' => $_REQUEST['address']
                    ,'smtpserver' => $_REQUEST['smtp_host']
                    ,'smtpport' => $_REQUEST['smtp_port']
                    ,'smtpuser' => $_REQUEST['smtp_user']
                    ,'smtppass' => $_REQUEST['smtp_pass']
                    ,'smtpsec' => !empty($_REQUEST['smtpsec']) ? basename($_REQUEST['smtpsec']) : 'SSL'
                    ,'smtpallowselfsigned' => !empty($_REQUEST['smtpallowselfsigned']) ? 1 : 0
                    ,'signature' => $_REQUEST['signature']
                    ,'sendvcf' => $_REQUEST['sendvcf']
                    ));
            if ($account) {
                // Attempting to create the imapbox entry in the indexer via API call
                if ('imap' == $acctype) {
                    $profile = $Acnt->getProfileFromAccountId($_SESSION['phM_uid'], $account);
                    $API = new handler_email_api($_PM_, $_SESSION['phM_uid']);
                    $API->create_imapbox((($_REQUEST['popname']) ? $_REQUEST['popname'] : $_REQUEST['popserver'].' IMAP'), $profile);
                    unset($API);
                }
            }
        }
        if ('saveold' == $mode) {
            if (!$Acnt->updateAccount(array
                    ('uid' => $_SESSION['phM_uid']
                    ,'accid' => $account
                    ,'accname' => $_REQUEST['popname']
                    ,'checkevery' => $_REQUEST['checkevery']
                    ,'checkspam' => isset($_REQUEST['checkspam']) ? $_REQUEST['checkspam'] : 0
                    ,'acctype' => isset($_REQUEST['acctype']) ? $_REQUEST['acctype'] : 'pop3'
                    ,'sig_on' => isset($_REQUEST['sig_on']) ? $_REQUEST['sig_on'] : 0
                    ,'popserver' => $_REQUEST['popserver']
                    ,'popport' => $_REQUEST['popport']
                    ,'popuser' => $_REQUEST['popuser']
                    ,'poppass' => $_REQUEST['poppass']
                    ,'popsec' => !empty($_REQUEST['popsec']) ? basename($_REQUEST['popsec']) : 'SSL'
                    ,'popallowselfsigned' => !empty($_REQUEST['popallowselfsigned']) ? 1 : 0
                    ,'leaveonserver' => isset($_REQUEST['leaveonserver']) ? $_REQUEST['leaveonserver'] : 0
                    ,'localkillserver' => isset($_REQUEST['localkillserver']) ? $_REQUEST['localkillserver'] : 0
                    ,'onlysubscribed' => isset($_REQUEST['onlysubscribed']) ? $_REQUEST['onlysubscribed'] : 0
                    ,'cachetype' => isset($_REQUEST['cachetype']) ? $_REQUEST['cachetype'] : 'struct'
                    ,'imapprefix' => /*isset($_REQUEST['imapprefix']) ? $_REQUEST['imapprefix'] : */ '' // Not yet supported
                    ,'trustspamfilter' => isset($_REQUEST['trustspamfilter']) ? $_REQUEST['trustspamfilter'] : 0
                 	,'inbox' => isset($_REQUEST['inbox']) ? $_REQUEST['inbox'] : '0'
                    ,'sent' => isset($_REQUEST['sent_objects']) ? $_REQUEST['sent_objects'] : '0'
                    ,'drafts' => isset($_REQUEST['drafts']) ? $_REQUEST['drafts'] : '0'
                    ,'templates' => isset($_REQUEST['templates']) ? $_REQUEST['templates'] : '0'
                    ,'archive' => isset($_REQUEST['archive']) ? $_REQUEST['archive'] : '0'
                    ,'junk' => isset($_REQUEST['junk']) ? $_REQUEST['junk'] : '0'
                    ,'waste' => isset($_REQUEST['waste']) ? $_REQUEST['waste'] : '0'
                    ,'real_name' => $_REQUEST['real_name']
                    ,'address' => $_REQUEST['address']
                    ,'smtpserver' => $_REQUEST['smtp_host']
                    ,'smtpport' => $_REQUEST['smtp_port']
                    ,'smtpuser' => $_REQUEST['smtp_user']
                    ,'smtppass' => $_REQUEST['smtp_pass']
                    ,'smtpsec' => !empty($_REQUEST['smtpsec']) ? basename($_REQUEST['smtpsec']) : 'SSL'
                    ,'smtpallowselfsigned' => !empty($_REQUEST['smtpallowselfsigned']) ? 1 : 0
                    ,'signature' => $_REQUEST['signature']
                    ,'sendvcf' => $_REQUEST['sendvcf']
                    ))) {
                $error .= $WP_msg['optsnosave'];
            } else {
                if ('imap' == $acctype) {
                    $API = new handler_email_api($_PM_, $_SESSION['phM_uid']);
                    $profile = $Acnt->getProfileFromAccountId($_SESSION['phM_uid'], $account);
                    $folder = $API->get_system_folder('imapbox', $profile, false);
                    if (!$folder) {
                        // Attempting to create the imapbox entry in the indexer via API call in case it does not exist (this should NOT happen)
                        $API->create_imapbox((($_REQUEST['popname']) ? $_REQUEST['popname'] : $_REQUEST['popserver'].' IMAP'), $profile);
                    } else {
                        if (is_array($folder) && isset($folder['idx'])) {
                            $folder = $folder['idx'];
                        }
                        // Update the name according to what the user entered for it
                        $API->rename_imapbox($folder, (($_REQUEST['popname']) ? $_REQUEST['popname'] : $_REQUEST['popserver'].' IMAP'));
                    }
                    unset($API);
                }
            }
        }
    }
    if ($error) {
        sendJS(array('error' => $error), 1, 1);
    } else {
        $account = $Acnt->getProfileFromAccountId($_SESSION['phM_uid'], $account);
        sendJS(array('profsaved' => intval($account), 'mode' => $mode, 'profname' => $_REQUEST['popname']), 1, 1);
    }
}

if ('kill' == $mode && ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['email_delete_profile'])) {
    $account = (isset($_REQUEST['account'])) ? (int) $_REQUEST['account'] : false;
    if (false !== $account) {
        $accdata = $Acnt->getAccount($_SESSION['phM_uid'], $account);
        $profile = $Acnt->getProfileFromAccountId($_SESSION['phM_uid'], $account);
        if ($accdata['acctype'] == 'imap') {
            $API = new handler_email_api($_PM_, $_SESSION['phM_uid']);
            $API->drop_imapbox($profile);
            unset($API);
        }
        $Acnt->deleteAccount($_SESSION['phM_uid'], $account);
    }
    sendJS(array('profsaved' => $account, 'mode' => $mode), 1, 1);
}

if ($mode == 'setdefacc' && ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['email_edit_profile'])) {
    $GlChFile = $DB->get_usr_choices($_SESSION['phM_uid']);
    if (isset($_REQUEST['def_prof'])) {
        $GlChFile['core']['default_profile'] = $_REQUEST['def_prof'];
    }
    $WP_return = ($DB->set_usr_choices($_SESSION['phM_uid'], $GlChFile)) ? $WP_msg['optssaved'] : $WP_msg['optsnosave'];
    header('Location: '.$link_base.'profiles');
    exit;
}

if ($mode == 'profiles' && ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['email_add_profile'] || $_SESSION['phM_privs']['email_edit_profile'])) {
    $tpl = new phlyTemplate($_PM_['path']['templates'].'core.listprofiles.tpl');
    $acclist = $Acnt->getAccountIndex($_SESSION['phM_uid']);
    $counter = sizeof($acclist);
    if ($counter > 0 && is_array($acclist)) {
        $t_b = $tpl->get_block('menline');
        foreach ($acclist as $k => $v) {
            $pd = $Acnt->getAccount($_SESSION['phM_uid'], $k);
            $t_b->assign(array('profilenm' => $acclist[$k], 'id' => $k, 'msg_del' => $WP_msg['del']));
            if (isset($pd['acctype']) && $pd['acctype'] == 'pop3') {
                $t_b->assign_block('acctype_pop3');
            } elseif (isset($pd['acctype']) && $pd['acctype'] == 'imap') {
                $t_b->assign_block('acctype_imap');
            }
            $tpl->assign('menline', $t_b);
            $t_b->clear();
            $defacc[$k] = $acclist[$k]; // Save data for default account selection below
        }
    }
    // Selection of default account
    if (isset($defacc) && !empty($defacc)) {
        $t_da = $tpl->get_block('profline');
        foreach ($defacc as $k => $v) {
            $t_da->assign(array('id' => $k, 'name' => $v));
            if (isset($_PM_['core']['default_profile']) && $_PM_['core']['default_profile'] == $k) {
                $t_da->assign_block('sel');
            }
            $tpl->assign('profline', $t_da);
            $t_da->clear();
        }
    }
    $save_class = 'handler_'.$save_handler.'_api';
    $API = new $save_class($_PM_, $_SESSION['phM_uid']);
    $t_inb = $tpl->get_block('inboxline');
    foreach ($API->give_folderlist() as $id => $data) {
        $lvl_space = ($data['level'] > 0) ? str_repeat('&nbsp;', $data['level'] * 2) : '';
        $t_inb->assign(array
                ('id' => (!$data['has_items']) ? '" style="color:darkgray;" disabled="disabled' : $id
                ,'name' => $lvl_space . phm_entities($data['foldername'])
                ));
        $tpl->assign('inboxline', $t_inb);
        $t_inb->clear();
    }
    /** Maybe later again, not sensible right now
    $t_ctl = $tpl->get_block('cacheline');
    foreach (array('struct' => $WP_msg['IMAPFetchHeaders'], 'full' => $WP_msg['IMAPFetchFull']) as $k => $v) {
        $t_ctl->assign(array('id' => $k, 'name' => htmlspecialchars($v)));
        $tpl->assign('cacheline', $t_ctl);
        $t_ctl->clear();
    }*/
    // Tell the frontend, whether SSL is compiled in for transparent SSL support in POP3 / SMTP / IMAP
    if (function_exists('extension_loaded') && extension_loaded('openssl')) {
        $tpl->assign_block('ssl_available');
    }
    if ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['email_add_profile']) {
        $tpl->assign_block('may_add_profile');
    }
    $t_ss = $tpl->get_block('smtpsec');
    $t_ps = $tpl->get_block('popsec');
    foreach (array('SSL' => 'SSL', 'STARTTLS' => 'STARTTLS', 'AUTO' => $WP_msg['ConnectionSecurityAuto'], 'none' => $WP_msg['ConnectionSecurityNone']) as $k => $v) {
        $t_ss->assign(array('key' => $k, 'val' => $v));
        $tpl->assign('smtpsec', $t_ss);
        $t_ss->clear();
        $t_ps->assign(array('key' => $k, 'val' => $v));
        $tpl->assign('popsec', $t_ps);
        $t_ps->clear();
    }
    $tpl->assign(array
            ('msg_profile' => $WP_msg['ProfileName']
            ,'msg_addacct' => $WP_msg['addacct']
            ,'addlink' => htmlspecialchars($link_base.'add')
            ,'kill_request' => $WP_msg['deleAccount']
            ,'form_target' => htmlspecialchars($link_base.'setdefacc')
            ,'msg_defacc' => $WP_msg['default_account']
            ,'about_defacc' => str_replace('$1', $WP_msg['notdef'], $WP_msg['about_defacc'])
            ,'msg_notdef' => $WP_msg['notdef']
            ,'editlink' => $link_base.'loadprofile&account='
            ,'delelink' => $link_base.'kill&account='
            ,'savelink' => $link_base
            ,'getaliasesurl' => $link_base.'queryaliases'
            ,'addaliaslink' => $link_base.'addalias'
            ,'editaliaslink' => $link_base.'editalias'
            ,'dropaliaslink' => $link_base.'dropalias'
            ,'getsignaturesurl' => $link_base.'querysignatures'
            ,'getsignatureurl' => $link_base.'getsignature'
            ,'addsignaturelink' => $link_base.'addsignature'
            ,'editsignaturelink' => $link_base.'editsignature'
            ,'dropsignaturelink' => $link_base.'dropsignature'
            ,'getuheadsurl' => $link_base.'queryuheads'
            ,'adduheadlink' => $link_base.'adduhead'
            ,'edituheadlink' => $link_base.'edituhead'
            ,'dropuheadlink' => $link_base.'dropuhead'
            ,'saveordersurl' => $link_base.'saveprofileorder'
            ,'msg_popserver' => $WP_msg['popserver']
            ,'msg_popport' => $WP_msg['popport']
            ,'msg_popuser' => $WP_msg['popuser']
            ,'msg_poppass' => $WP_msg['poppass']
            ,'msg_popsec' => $WP_msg['ConnectionSecurity']
            ,'msg_email' => $WP_msg['email']
            ,'msg_realname' => $WP_msg['realname']
            ,'msg_fetchevery' => $WP_msg['popfetchevery']
            ,'msg_fetchfrontend' => $WP_msg['popfetchfrontend']
            ,'msg_fetchbackend' => $WP_msg['popfetchbackend']
            ,'msg_leaveonserver' => $WP_msg['popleaveonserver']
            ,'msg_auto' => $WP_msg['auto']
            ,'msg_no' => $WP_msg['no']
            ,'msg_checkspam' => $WP_msg['ProfileCheckSPAM']
            ,'msg_sigon' => $WP_msg['sigOn']
            ,'msg_dele' => $WP_msg['del']
            ,'msg_save' => $WP_msg['save']
            ,'msg_cancel' => $WP_msg['cancel']
            ,'msg_smtphost' => $WP_msg['optsmtphost']
            ,'msg_smtpport' => $WP_msg['optsmtpport']
            ,'msg_smtpuser' => $WP_msg['optsmtpuser']
            ,'msg_smtppass' => $WP_msg['optsmtppass']
            ,'msg_smtpsec' => $WP_msg['ConnectionSecurity']
            ,'copy_smtp' => $WP_msg['copy_smtp']
            ,'copy_pop3' => $WP_msg['copy_pop3']
            ,'msg_addalias' => $WP_msg['AddAlias']
            ,'e_enterprofname' => $WP_msg['enterProfname']
            ,'e_enterpopserver' => $WP_msg['enterPOPserver']
            ,'e_enterpopuser' => $WP_msg['enterPOPuser']
            ,'e_enteremail' => $WP_msg['SuDefineAEmail']
            ,'msg_reallydropalias' => $WP_msg['ReallyDropAlias']
            ,'msg_cachetype' => $WP_msg['IMAPFetchtype']
            ,'passthrough_2' => give_passthrough(2)
            ,'passthrough' => give_passthrough(1)
            ,'msg_generic' => $WP_msg['General']
            ,'msg_various' => $WP_msg['Various']
            ,'msg_aliases' => $WP_msg['Aliases']
            ,'msg_onlysubscribed' => $WP_msg['ImapOnlySubscribed']
            ,'msg_showprefix' => $WP_msg['ImapOnlyWithPrefix']
            ,'about_uheaders' => $WP_msg['UHeadAbout']
            ,'msg_hkey' => $WP_msg['UHeadHKey']
            ,'msg_hval' => $WP_msg['UHeadHVal']
            ,'msg_uhead' => $WP_msg['UHeadReiter']
            ,'msg_adduhead' => $WP_msg['UHeadAdd']
            ,'e_enterhkey' => $WP_msg['UHeadEEnterKey']
            ,'msg_reallydropuhead' => $WP_msg['UHeadReallyDrop']
            ,'msg_nossl_pop3' => $WP_msg['ENoSSLAvailablePOP3']
            ,'msg_nossl_imap' => $WP_msg['ENoSSLAvailableIMAP']
            ,'msg_nossl_smtp' => $WP_msg['ENoSSLAvailableSMTP']
            ,'msg_inboxfolder' => $WP_msg['EmailInboxFolder']
            ,'msg_sentfolder' => $WP_msg['EmailSentObjectsFolder']
            ,'msg_draftsfolder' => $WP_msg['EmailDraftsFolder']
            ,'msg_templatesfolder' => $WP_msg['EmailTemplatesFolder']
            ,'msg_archivefolder' => $WP_msg['EmailArchiveFolder']
            ,'msg_junkfolder' => $WP_msg['EmailJunkFolder']
            ,'msg_wastefolder' => $WP_msg['EmailWasteFolder']
            ,'msg_defaultfolder' => $WP_msg['EmailDefaultFolder']
            ,'msg_addsig' => $WP_msg['SignatureAdd']
            ,'msg_editsig' => $WP_msg['SignatureEdit']
            ,'msg_delesig' => $WP_msg['SignatureDele']
            ,'q_reallydelesig' => $WP_msg['QSignatureDele']
            ,'msg_sigtitle' => $WP_msg['BPlateName']
            ,'msg_folders' => $WP_msg['Folders']
            ,'msg_localkillserver' => $WP_msg['poplocalkillserver']
            ,'msg_sigval_txt' => $WP_msg['SigvalText']
            ,'msg_sigval_html' => $WP_msg['SigvalHTML']
            ,'msg_signature' => $WP_msg['sig']
            ,'msg_sendvcf' => $WP_msg['VCFsend']
            ,'msg_vcf_none' => $WP_msg['VCFsendNone']
            ,'msg_vcf_default' => $WP_msg['VCFsendDefault']
            ,'msg_vcf_priv' => $WP_msg['VCFsendPriv']
            ,'msg_vcf_busi' => $WP_msg['VCFsendBusi']
            ,'msg_vcf_all' => $WP_msg['VCFsendAll']
            ,'msg_sig_default' => $WP_msg['SigSendDefault']
            ,'msg_sig_none' => $WP_msg['SigSendNone']
            ,'msg_convert_to_imap' => $WP_msg['AccConvertToImap']
            ,'msg_convert_to_pop3' => $WP_msg['AccConvertToPop3']
            ));
}

if ($mode == 'loadprofile' && ($_SESSION['phM_privs']['all'] || $_SESSION['phM_privs']['email_edit_profile'])) {
    $out = $pd = array();
    $pd = $Acnt->getAccount($_SESSION['phM_uid'], $_REQUEST['account']);
    foreach (array('profilename' => 'accname', 'acctype' => 'acctype', 'smtp_host' => 'smtpserver'
            ,'smtp_port' => 'smtpport', 'smtp_user' => 'smtpuser', 'smtp_pass' => 'smtppass'
            ,'checkevery' => 'checkevery', 'leaveonserver' => 'leaveonserver',
            'localkillserver' => 'localkillserver', 'inbox' => 'inbox' ,'sent_objects' => 'sent',
            'junk' => 'junk', 'waste' => 'waste', 'drafts' => 'drafts', 'archive' => 'archive'
            ,'templates' => 'templates', 'cachetype' => 'cachetype', 'popserver' => 'popserver'
            ,'popport' => 'popport', 'popuser' => 'popuser', 'poppass' => 'poppass', 'trustspamfilter' => 'trustspamfilter'
            ,'address' => 'address', 'real_name' => 'real_name', 'signature' => 'signature', 'sig_on' => 'sig_on'
            ,'checkspam' => 'checkspam', 'onlysubscribed' => 'onlysubscribed', 'imapprefix' => 'imapprefix'
            ,'popsec' => 'popsec', 'smtpsec' => 'smtpsec', 'sendvcf' => 'sendvcf'
            ,'popallowselfsigned' => 'popallowselfsigned', 'smtpallowselfsigned' => 'smtpallowselfsigned') as $k => $v) {
        $out[$k] = isset($pd[$v]) ? (is_array($pd[$v]) ? $pd[$v][0] : $pd[$v]) : '';
    }
    sendJS(array('profile' => $out), 1, 1);
}
