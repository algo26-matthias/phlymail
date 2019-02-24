<?php
/**
 * setup.users.php -> Management FrontEnd-Users
 * @package phlyMail Nahariya 4.0+ Default Branch
 * @subpackage Config interface
 * @copyright 2003-2016 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.4.7mod1 2016-01-25
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();
/**
 * TODO:
 * - Enable settings editor here
 *   - ported frontend setup window allows setting all options for each user
 * - Nice description on top of the page to explain everything
 * - Erscheinungsbild is obsolete, as well as user specific settings in Systemeinstellungen
 */
if (!isset($_SESSION['phM_perm_read']['users_']) && !$_SESSION['phM_superroot']) {
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
    $tpl->assign('msg_no_access', $WP_msg['no_access']);
    return;
}
$whattodo = (isset($_REQUEST['whattodo'])) ? $_REQUEST['whattodo'] : false;
$uid = (isset($_REQUEST['uid'])) ? $_REQUEST['uid'] : null;
$accid = (isset($_REQUEST['accid'])) ? $_REQUEST['accid'] : null;
$mode = (isset($_REQUEST['mode'])) ? $_REQUEST['mode'] : false;
$Acnt = new DB_Controller_Account();
$WP_return = false;
// Depends on the defined handler for storing mails.
$save_handler = 'email';
// We ported the frontend's mail account editor to the config. To avoid duplicating all messages we simply import them here
require_once($_PM_['path']['message'].'/'.$WP_conf['language'].'.php');

// Only allow writing AJAX operations for privileged users
if (in_array($mode, array('addalias', 'editalias', 'dropalias', 'queryaliases', 'adduhead', 'edituhead', 'dropuhead', 'queryuheads'
                ,'addsignature', 'editsignature', 'dropsignature', 'getsignature', 'querysignatures', 'saveprofileorder'))
        && !isset($_SESSION['phM_perm_write']['users_']) && !$_SESSION['phM_superroot']) {
    sendJS(array(), 1, 1);
}

if ('addalias' == $mode) {
    $Acnt->add_alias($_REQUEST['uid'], $_REQUEST['id'], $_REQUEST['email'], $_REQUEST['real_name']);
    $mode = 'queryaliases';
}
if ('editalias' == $mode) {
    $Acnt->update_alias($_REQUEST['uid'], $_REQUEST['aid'], $_REQUEST['email'], $_REQUEST['real_name']);
    $mode = 'queryaliases';
}
if ('dropalias' == $mode) {
    $Acnt->delete_alias($_REQUEST['uid'], $_REQUEST['aid']);
    $mode = 'queryaliases';
}
if ('queryaliases' == $mode ) {
    $return = array();
    $data = $Acnt->getAccount($_REQUEST['uid'], $_REQUEST['id']);
    foreach ($data['aliases'] as $aid => $alias) {
        $return[] = array('aid' => $aid, 'real_name' => $alias['real_name'], 'email' => $alias['email']);
    }
    sendJS(array('alias' => $return), 1, 1);
}
if ('adduhead' == $mode) {
    $hkey = preg_replace('![^\x21-\x39\x3B-\x7e]!', '', $_REQUEST['hkey']);
    $hval = preg_replace('!\r|\n!', '', $_REQUEST['hval']);
    $Acnt->add_uhead($_REQUEST['uid'], $_REQUEST['id'], $hkey, $hval);
    $mode = 'queryuheads';
}
if ('edituhead' == $mode) {
    $hkey = preg_replace('![^\x21-\x39\x3B-\x7e]!', '', $_REQUEST['hkey']);
    $hval = preg_replace('!\r|\n!', '', $_REQUEST['hval']);
    $Acnt->update_uhead($_REQUEST['uid'], $_REQUEST['id'], $_REQUEST['ohkey'], $hkey, $hval);
    $mode = 'queryuheads';
}
if ('dropuhead' == $mode) {
    $Acnt->delete_uhead($_REQUEST['uid'], $_REQUEST['id'], $_REQUEST['hkey']);
    $mode = 'queryuheads';
}
if ('queryuheads' == $mode ) {
    $data = $Acnt->getAccount($_REQUEST['uid'], $_REQUEST['id']);
    $return = array();
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
    $Acnt->add_signature($_REQUEST['uid'], $_REQUEST['title'], $sig, $sig_html);
    $mode = 'querysignatures';
}

if ('editsignature' == $mode) {
    $sig = phm_stripslashes($_REQUEST['signature']);
    $sig_html = phm_stripslashes($_REQUEST['signature_html']);
    if ($sig_html == '<br />') {
        $sig_html = '';
    }
    $Acnt->update_signature($_REQUEST['uid'], $_REQUEST['id'], $_REQUEST['title'], $sig, $sig_html);
    $mode = 'querysignatures';
}
if ('dropsignature' == $mode) {
    $Acnt->delete_signature($_REQUEST['uid'], $_REQUEST['id']);
    $mode = 'querysignatures';
}

if ('getsignature' == $mode) {
    $sig = $Acnt->get_signature($_REQUEST['uid'], $_REQUEST['id']);
    sendJS(array('signature' => $sig['signature'], 'signature_html' => $sig['signature_html']), 1, 1);
}

if ('querysignatures' == $mode ) {
    $return = array();
    $data = $Acnt->get_signature_list($_REQUEST['uid']);
    foreach ($data as $id => $signature) {
        $return[] = array('id' => $id, 'title' => $signature['title'] ? $signature['title'] : $WP_msg['undef']);
    }
    sendJS(array('signatures' => $return), 1, 1);
}

if ('saveprofileorder' == $mode) {
    $Acnt->reorderAccounts($_REQUEST['uid'], $_REQUEST['id']);
    sendJS(array('done' => 1), 1, 1);
}

if ('get_uperm' == $mode) {
    $uinfo = $DB->get_usrdata($_REQUEST['uid']);
    $uperm = $DB->get_user_permissions($_REQUEST['uid'], true);
    sendJS(array('got_uperm' => $uperm, 'uid' => intval($_REQUEST['uid']), 'uname' => $uinfo['username']), 1, 1);
}
if ('set_uperm' == $mode) {
    $perms = array();
    foreach ($_REQUEST['p'] as $k => $v) {
        list ($hdl, $act) = explode('_', $k, 2);
        $perms[] = array('handler' => $hdl, 'action' => $act, 'perm' => $v);
    }
    $DB->set_user_permissions($_REQUEST['uid'], $perms);
    sendJS(array('set_uperm' => 1), 1, 1);
}

if (($mode == 'saveold' || $mode == 'savenew') && (isset($_SESSION['phM_perm_write']['users_']) || $_SESSION['phM_superroot'])) {
    $acctype = isset($_REQUEST['acctype']) ? $_REQUEST['acctype'] : 'pop3';
    $error = '';
    $account = (isset($_REQUEST['account'])) ? $_REQUEST['account'] : false;
    if ('' == $_REQUEST['popname']) {
        $error .= $WP_msg['enterProfname'].LF;
    }
    if ('' == $_REQUEST['popserver']) {
        $error .= ($acctype == 'imap' ? 'IMAP' : 'POP3').': '.$WP_msg['enterPOPserver'].LF;
    }
    if ('' == $_REQUEST['popuser']) {
        $error .= ($acctype == 'imap' ? 'IMAP' : 'POP3').': '.$WP_msg['enterPOPuser'].LF;
    }
    if ('saveold' == $mode) {
        $check_accid = $Acnt->AccountNameExists($_REQUEST['uid'], $_REQUEST['popname']);
        if (isset($check_accid) && $account != $check_accid && $check_accid != '') {
            $error .= $account.'/'.$check_accid.': '.$WP_msg['SuPrfExists'];
        }
    } else {
        if ($Acnt->AccountNameExists($_REQUEST['uid'], $_REQUEST['popname'])) {
            $error .= $WP_msg['SuPrfExists'];
        }
    }
    if (!$error) {
        if ('savenew' == $mode) {
            $account = $Acnt->addAccount(array(
                    'uid' => (int) $_REQUEST['uid'],
                    'accname' => $_REQUEST['popname'],
                    'checkevery' => (int) $_REQUEST['checkevery'],
                    'accid' => $Acnt->getMaxAccountId($_REQUEST['uid']),
                    'checkspam' => isset($_REQUEST['checkspam']) ? (int) $_REQUEST['checkspam'] : 0,
                    'acctype' => $acctype,
                    'sig_on' => isset($_REQUEST['sig_on']) ? $_REQUEST['sig_on'] : 0,
                    'popserver' => $_REQUEST['popserver'],
                    'popport' => $_REQUEST['popport'],
                    'popuser' => $_REQUEST['popuser'],
                    'poppass' => $_REQUEST['poppass'],
                    'popsec' => !empty($_REQUEST['popsec']) ? basename($_REQUEST['popsec']) : 'SSL',
                    'popallowselfsigned' => !empty($_REQUEST['popallowselfsigned']) ? 1 : 0,
                    'leaveonserver' => isset($_REQUEST['leaveonserver']) ? $_REQUEST['leaveonserver'] : 0,
                    'localkillserver' => isset($_REQUEST['localkillserver']) ? $_REQUEST['localkillserver'] : 0,
                    'onlysubscribed' => isset($_REQUEST['onlysubscribed']) ? $_REQUEST['onlysubscribed'] : 0,
                    'cachetype' => isset($_REQUEST['cachetype']) ? $_REQUEST['cachetype'] : 'struct',
                    'imapprefix' => isset($_REQUEST['imapprefix']) ? $_REQUEST['imapprefix'] : '',
                    'trustspamfilter' => isset($_REQUEST['trustspamfilter']) ? $_REQUEST['trustspamfilter'] : 0,
                    'inbox' => isset($_REQUEST['inbox']) ? $_REQUEST['inbox'] : 0,
                    'sent' => isset($_REQUEST['sent_objects']) ? $_REQUEST['sent_objects'] : 0,
                    'drafts' => isset($_REQUEST['drafts']) ? $_REQUEST['drafts'] : 0,
                    'templates' => isset($_REQUEST['templates']) ? $_REQUEST['templates'] : 0,
                    'archive' => isset($_REQUEST['archive']) ? $_REQUEST['archive'] : 0,
                    'junk' => isset($_REQUEST['junk']) ? $_REQUEST['junk'] : 0,
                    'waste' => isset($_REQUEST['waste']) ? $_REQUEST['waste'] : 0,
                    'real_name' => $_REQUEST['real_name'],
                    'address' => $_REQUEST['address'],
                    'smtpserver' => $_REQUEST['smtp_host'],
                    'smtpport' => $_REQUEST['smtp_port'],
                    'smtpuser' => $_REQUEST['smtp_user'],
                    'smtppass' => $_REQUEST['smtp_pass'],
                    'smtpsec' => !empty($_REQUEST['smtpsec']) ? basename($_REQUEST['smtpsec']) : 'SSL',
                    'smtpallowselfsigned' => !empty($_REQUEST['smtpallowselfsigned']) ? 1 : 0,
                    'signature' => $_REQUEST['signature'],
                    'sendvcf' => $_REQUEST['sendvcf']
                    ));
            if ($account) {
                // Attempting to create the imapbox entry in the indexer via API call
                if ('imap' == $acctype) {
                    $profile = $Acnt->getProfileFromAccountId($_REQUEST['uid'], $account);
                    $API = new handler_email_api($_PM_, $_REQUEST['uid']);
                    $API->create_imapbox((($_REQUEST['popname']) ? $_REQUEST['popname'] : $_REQUEST['popserver'].' IMAP'), $profile);
                    unset($API);
                }
            }
        }
        if ('saveold' == $mode) {
            if (!$Acnt->updateAccount(array
                    ('uid' => $_REQUEST['uid']
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
                // Attempting to create the imapbox entry in the indexer via API call in case it does not exist (this should NOT happen)
                if ('imap' == $acctype) {
                    $API = new handler_email_api($_PM_, $_REQUEST['uid']);
                    $profile = $Acnt->getProfileFromAccountId($_REQUEST['uid'], $account);
                    $folder = $API->get_system_folder('imapbox', $profile, false);
                    if (!$folder) {
                        $API->create_imapbox((($_REQUEST['popname']) ? $_REQUEST['popname'] : $_REQUEST['popserver'].' IMAP'), $profile);
                    }
                    unset($API);
                }
            }
        }
    }
    if ($error) {
        sendJS(array('error' => $error), 1, 1);
    } else {
        $account = $Acnt->getProfileFromAccountId($_REQUEST['uid'], $account);
        sendJS(array('profsaved' => intval($account), 'mode' => $mode, 'profname' => phm_addcslashes($_REQUEST['popname'])), 1, 1);
    }
}
if ('kill' == $mode && (isset($_SESSION['phM_perm_write']['users_']) || $_SESSION['phM_superroot'])) {
    $account = (isset($_REQUEST['account'])) ? (int) $_REQUEST['account'] : false;
    if (false !== $account) {
        $accdata = $Acnt->getAccount($_REQUEST['uid'], $account);
        $profile = $Acnt->getProfileFromAccountId($_REQUEST['uid'], $account);
        if ($accdata['acctype'] == 'imap') {
            $API = new handler_email_api($_PM_, $_REQUEST['uid']);
            $API->drop_imapbox($profile);
            unset($API);
        }
        $Acnt->deleteAccount($_REQUEST['uid'], $account);
    }
    sendJS(array('profsaved' => $account, 'mode' => $mode), 1, 1);
}
if ($mode == 'setdefacc' && (isset($_SESSION['phM_perm_write']['users_']) || $_SESSION['phM_superroot'])) {
    $GlChFile = $DB->get_usr_choices($_REQUEST['uid']);
    if (isset($_REQUEST['def_prof'])) {
        $GlChFile['core']['default_profile'] = $_REQUEST['def_prof'];
    }
    $WP_return = ($DB->set_usr_choices($_REQUEST['uid'], $GlChFile)) ? $WP_msg['optssaved'] : $WP_msg['optsnosave'];
    header('Location: '.$link_base.'users&mode=profiles&uid='.$_REQUEST['uid']);
    exit;
}

if ($mode == 'profiles' && (isset($_SESSION['phM_perm_write']['users_']) || $_SESSION['phM_superroot'])) {
    $outer_template = 'um.framed.tpl';
    $link_base .= 'users&uid='.intval($_REQUEST['uid']).'&mode=';
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/um.editacc.tpl');
    $GlChFile = $DB->get_usr_choices($_REQUEST['uid']);
    $defacc = array();
    $t_b = $tpl->get_block('menline');
    foreach ($Acnt->getAccountIndex($_REQUEST['uid'], true, false) as $k => $v) {
        $t_b->assign(array('profilenm' => phm_entities($v['accname']), 'id' => $v['accid'], 'msg_del' => $WP_msg['del']));
        $t_b->assign_block($v['acctype'] == 'pop3' ? 'acctype_pop3' : 'acctype_imap');
        $tpl->assign('menline', $t_b);
        $t_b->clear();
        // Save data for default account selection below
        $defacc[$v['accid']] = $v['accname'];
    }
    // Selection of default account
    if (!empty($defacc)) {
        $t_da = $tpl->get_block('profline');
        foreach ($defacc as $k => $v) {
            $t_da->assign(array('id' => $k, 'name' => phm_entities($v)));
            if (isset($GlChFile['core']['default_profile']) && $GlChFile['core']['default_profile'] == $k) {
                $t_da->assign_block('sel');
            }
            $tpl->assign('profline', $t_da);
            $t_da->clear();
        }
    }
    $save_class = 'handler_'.$save_handler.'_api';
    $API = new $save_class($_PM_, $_REQUEST['uid']);
    $t_inb = $tpl->get_block('inboxline');
    foreach ($API->give_folderlist() as $id => $data) {
        $lvl_space = ($data['level'] > 0) ? str_repeat('&nbsp;', $data['level'] * 2) : '';
        $t_inb->assign(array
                ('id' => (!$data['has_items']) ? '" style="color:darkgray;" disabled="disabled' : $id
                ,'name' => $lvl_space . phm_entities($data['foldername'])
                ));
        $tpl->assign('inboxline', $t_inb);
        $t_inb->clear();
    }/*
    $t_ctl = $tpl->get_block('cacheline');
    foreach (array('struct' => $WP_msg['IMAPFetchHeaders'], 'full' => $WP_msg['IMAPFetchFull']) as $k => $v) { // still beta
        $t_ctl->assign(array('id' => $k, 'name' => phm_entities($v)));
        $tpl->assign('cacheline', $t_ctl);
        $t_ctl->clear();
    }*/
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
    // Tell the frontend, whether SSL support is compiled in for transparent SSL support in POP3 / SMTP
    if (function_exists('extension_loaded') && extension_loaded('openssl')) {
        $tpl->assign_block('ssl_available');
    }
    $tpl->assign(array
            ('msg_profile' => $WP_msg['ProfileName']
            ,'msg_addacct' => $WP_msg['addacct']
            ,'addlink' => htmlspecialchars($link_base.'add')
            ,'kill_request' => $WP_msg['deleAccount']
            ,'form_target' => htmlspecialchars($link_base.'setdefacc&uid='.$_REQUEST['uid'])
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
            ,'msg_aliases' => $WP_msg['AliasesDefined']
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
            // ,'msg_aliases' => $WP_msg['Aliases']
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
            ,'effective_uid' => (double) $_REQUEST['uid']
            ,'confpath' => CONFIGPATH
            ));
    return;
}
if ($mode == 'loadprofile' && (isset($_SESSION['phM_perm_write']['users_']) || $_SESSION['phM_superroot'])) {
    $pd = $out = array();
    $pd = $Acnt->getAccount($_REQUEST['uid'], $_REQUEST['account']);
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
        $out[$k] = isset($pd[$v]) ? $pd[$v] : '';
    }
    sendJS(array('profile' => $out), 1, 1);
}
// User management
if ('savenewuser' == $whattodo || 'saveolduser' == $whattodo) {
    $error = false;
    if (!isset($_SESSION['phM_perm_write']['users_']) && !$_SESSION['phM_superroot']) {
        $tpl = new phlyTemplate(CONFIGPATH.'/templates/setup.noaccess.tpl');
        $tpl->assign('msg_no_access', $WP_msg['no_access']);
        return;
    }
    $PHM = $_REQUEST['PHM'];
    $PHM['salt'] = $_PM_['auth']['system_salt'];
    if ('savenewuser' == $whattodo) {
        if ($DB->checkfor_username($PHM['username'])) {
            $error .= $WP_msg['SuUserExists'];
        }
        if ('' == $PHM['password']) {
            $error .= $WP_msg['SuDefinePW'];
        }
        if ('' == $PHM['username']) {
            $error .= $WP_msg['SuDefineUN'];
        }
    } elseif ('saveolduser' == $whattodo) {
        /* UN changes are no longer allowed, since this would invalidate the password digest
    	$if_exists = $DB->checkfor_username($PHM['username']);
    	if ($if_exists && $if_exists != $uid) $error .= $WP_msg['SuUserExists'];
    	*/
    }
    if ($PHM['password'] != $PHM['password2']) {
        $error .= $WP_msg['SuPW1notPW2'];
    }
    if (!$error) {
        $tokvar = array(
                'core' => array
                        ('debugging_level' => (!empty($_REQUEST['debugging_level'])) ? basename($_REQUEST['debugging_level']) : 'system'
                        ,'show_motd' => (isset($_REQUEST['showmotd'])) ? $_REQUEST['showmotd'] : 0
                        ,'theme_name' => $_REQUEST['theme']
                        ,'language' => $_REQUEST['language']
                        ,'MOTD' => phm_stripslashes($_REQUEST['MOTD'])
                        ,'showlinkconfig' => (!empty($_REQUEST['showlinkconfig'])) ? 1 : 0
                        ,'logincheckupdates' => (!empty($_REQUEST['logincheckupdates'])) ? ($_REQUEST['logincheckupdates'] == 'beta' ? 'beta' : 'stable') : 0
                        )
                ,'auth' => array
                        ('tie_session_ip' => (!empty($_REQUEST['sessionip'])) ? $_REQUEST['sessionip'] : 0
                        ,'session_cookie' => (!empty($_REQUEST['sessioncookie'])) ? $_REQUEST['sessioncookie'] : 0
                        )
                );
        if ('savenewuser' == $whattodo) {
            // Reduce optional specific languages (like de_Du) to the base language (e.g. de)
            if (strstr($_PM_['core']['language'], '_')) {
                $_PM_['core']['language'] = substr($_PM_['core']['language'], 0, strpos($_PM_['core']['language'], '_'));
            }
            // Create user in DB
            $uid = $DB->add_user($PHM);
            $DB->set_usr_choices($uid, $tokvar);
            // Groups may only be set, if the driver supports it
            if (isset($DB->features['groups']) && $DB->features['groups']) {
                $DB->set_usergrouplist($uid, isset($_REQUEST['groups']) ? $_REQUEST['groups'] : array());
            }
            // Tell handlers about it
            foreach ($_PM_['handlers'] as $handler => $active) {
                // Only look for active handlers
                if (!$active) {
                    continue;
                }
                // Look for an installation API call available
                if (!file_exists($_PM_['path']['handler'].'/'.$handler.'/configapi.php')) {
                    continue;
                }
                require_once($_PM_['path']['handler'].'/'.$handler.'/configapi.php');
                $call = 'handler_'.$handler.'_configapi';
                if (!in_array('create_user', get_class_methods($call))) {
                    continue;
                }
                $API = new $call($_PM_, $uid);
                $state = $API->create_user();
                if (!$state) {
                	$error = $API->get_errors();
                	$DB->delete_user($PHM['username']);
                	break;
                }
                unset($API);
            }
            // Tell backend API about it
            require_once(CONFIGPATH.'/lib/configapi.class.php');
            $cAPI = new configapi($_PM_, $DB);
            $cAPI->create_user($uid, $PHM['username'], $PHM['password'], $PHM['email']);
            unset($cAPI);
        }
        if ('saveolduser' == $whattodo) {
            $allchoices = merge_PM($DB->get_usr_choices($uid), $tokvar);
            // Some of the settings can be inherited from the master setting, these should not appear in the user settings array stored in the DB
            foreach (array('debugging_level' => 'core_debugging_level', 'showmotd' => 'core_show_motd'
                    ,'sessionip' => 'auth_tie_session_ip', 'sessioncookie' => 'auth_session_cookie') as $k => $v) {
                if (!empty($_REQUEST[$k]) && $_REQUEST[$k] == 2) {
                    $v2 = explode('_', $v, 2);
                    unset($allchoices[$v2[0]][$v2[1]]);
                }
            }
            unset($allchoices['core']['provider_name']);
            $DB->set_usr_choices($uid, $allchoices);
            // Groups may only be set, if the driver supports it
            if (isset($DB->features['groups']) && $DB->features['groups']) {
                $DB->set_usergrouplist($uid, isset($_REQUEST['groups']) ? $_REQUEST['groups'] : array());
            }
            // Update DB
            if (!$DB->upd_user(array_merge($PHM, array('uid' => $uid)))) {
                echo $DB->error();
                unset($uid);
                exit;
            }
            // Tell backend API about it
            $PHM2 = $DB->get_usrdata($uid);
            require_once(CONFIGPATH.'/lib/configapi.class.php');
            $cAPI = new configapi($_PM_);
            $cAPI->edit_user($uid, $PHM2['username'], $PHM['password'], $PHM['email'], $PHM['username']);
            unset($cAPI);
        }
    }
    $whattodo = (isset($uid) && $uid) ? 'edituser' : 'adduser';
    if (!$error) {
        header('Location: '.$link_base.'users&whattodo='.$whattodo.'&uid='.(isset($uid) ? $uid : ''));
        exit;
    }
}
if ('resetfail' == $whattodo) {
    $DB->reset_usrfail($uid);
    $whattodo = 'edituser';
}
if ('active' == $whattodo) {
    $PHM = $DB->get_usrdata($uid);
    $DB->onoff_user($PHM['username'], 1);
    unset($uid);
    $whattodo = false;
}
if ('inactive' == $whattodo) {
    $PHM = $DB->get_usrdata($uid);
    $DB->onoff_user($PHM['username'], 0);
    unset($uid);
    $whattodo = false;
}
if ('deleuser' == $whattodo) {
    $PHM = $DB->get_usrdata($uid);
    if (isset($_REQUEST['really']) && 'yeahyeah' == $_REQUEST['really']) {
        $PHM2 = $DB->get_usrdata($uid);
        // Remove user from DB
        $DB->delete_user($PHM['username']);
        // Involve APIs for active handlers to tell them about it
        foreach ($_PM_['handlers'] as $handler => $active) {
            // Only look for active handlers
            if (!$active) {
                continue;
            }
            // Look for an installation API call available
            if (!file_exists($_PM_['path']['handler'].'/'.$handler.'/configapi.php')) {
                continue;
            }
            require_once($_PM_['path']['handler'].'/'.$handler.'/configapi.php');
            $call = 'handler_'.$handler.'_configapi';
            if (!in_array('remove_user', get_class_methods($call))) {
                continue;
            }
            $API = new $call($_PM_, $uid);
            $API->remove_user();
            unset($API);
        }
        // Tell backend API about it
        require_once(CONFIGPATH.'/lib/configapi.class.php');
        $cAPI = new configapi($_PM_);
        $cAPI->delete_user($uid, $PHM2['username']);
        unset($cAPI);
        cfg_removedir($_PM_['path']['userbase'].'/'.$uid);
        unset($uid);
        $whattodo = false;
    } else {
        $profiles = $Acnt->getAccountIndex($uid);
        $tpl = new phlyTemplate(CONFIGPATH.'/templates/um.deleuser.tpl');
        $tpl->assign(array
                ('link_yes' => $link_base.htmlspecialchars('users&whattodo='.$whattodo.'&really=yeahyeah&uid='.$uid)
                ,'link_no' => $link_base.'users'
                ,'msg_yes' => $WP_msg['yes']
                ,'msg_no' => $WP_msg['no']
                ,'msg_real' => $WP_msg['SuDelUserReal']
                ,'msg_accstat' => (!empty($profiles)) ? $WP_msg['SuDelUserAccs'] : $WP_msg['SuDelUserNoAccs']
                ));
    }
}
if ('adduser' == $whattodo || 'edituser' == $whattodo) {
    if (isset($_REQUEST['PHM'])) {
        $PHM = $_REQUEST['PHM'];
    }
    if ('adduser' == $whattodo) {
        $currUsers = $DB->get_usercount();
        $my_PM_ = $_PM_;
        $nwhatto = 'savenewuser';
        if (!isset($PHM)) {
            $PHM = array(
                    'username' => '', 'active' => 1, 'password' => '', 'password2' => '',
                    'email' => '', 'www' => '', 'firstname' => '', 'lastname' => '', 'birthday' => '',
                    'tel_private' => '', 'fax' => '', 'tel_business' => '', 'cellular' => '',
                    'email' => '', 'visibility' => 'private', 'externalemail' => '',
                    'customer_number' => ''
                    );
        }
    }
    if ('edituser' == $whattodo) {
        $nwhatto = 'saveolduser&uid='.$uid;
        if (isset($uid) && !isset($PHM['username'])) {
            $PHM = $DB->get_usrdata($uid);
            unset($PHM['password']);
        }
        $my_PM_ = $DB->get_usr_choices($uid);
    }

    $tpl = new phlyTemplate(CONFIGPATH.'/templates/um.edituser.tpl');
    $tpl->assign(array
            ('head_text' => ('adduser' == $whattodo) ? $WP_msg['SuEnterBD'] : $WP_msg['SuEditBD']
            ,'msg_sysuser' => $WP_msg['sysuser']
            ));
    if (isset($error) && $error) {
        $tpl->fill_block('error', 'error', $error);
    }
    if ('adduser' == $whattodo) {
        $tpl->fill_block('adduser', 'name', phm_entities($PHM['username']));
    } else {
        $tpl->fill_block('edituser', array('name' => phm_entities($PHM['username']), 'uid' => $uid));
    }
    $tpl->assign_block(isset($PHM['active']) && $PHM['active'] ? 'selyes' : 'selno');
    if ('edituser' == $whattodo) {
        $t_edit = $tpl->get_block('editprof');
        $t_edit->assign(array('uid' => $uid, 'msg_edit' => $WP_msg['editprofiles']));
        $t_edit->fill_block('delprof', array
                ('link_del' => htmlspecialchars($link_base.'users&whattodo=deleuser&uid='.$uid)
                ,'msg_del' => $WP_msg['del']
                ));
        $tpl->assign('editprof', $t_edit);
        $tpl->fill_block('editsms', array
                ('link_sms' => htmlspecialchars($link_base.'sms&whattodo=edituser&uid='.$uid)
                ,'msg_sms' => $WP_msg['UMSetSMS']
                ));
        $tpl->fill_block('editquota', array
                ('link_quota' => htmlspecialchars($link_base.'quotas&whattodo=edituser&uid='.$uid)
                ,'msg_quota' => $WP_msg['setquota']
                ));
        if (isset($DB->features['permissions']) && $DB->features['permissions']) {
            $tpl->fill_block('editprivs', array('uid' => $uid, 'msg_privileges' => $WP_msg['Privileges']));
        }

        $t_umod = $tpl->get_block('usermod');
        for ($n = 0; isset($_PM_['useredit'][$n]); $n++) {
            $t_umod->assign(array
                    ('link_usermod' => htmlspecialchars($_PM_['useredit'][$n][1].'&uid='.$uid)
                    ,'msg_usermod' => $_PM_['useredit'][$n][0],
                    ));
            $tpl->assign('usermod', $t_umod);
            $t_umod->clear();
        }

        $t_lf = $tpl->get_block('loginfail');
        $failure = $DB->get_usrfail($uid);
        $failedlogin = ($failure['fail_count']+0).' / '.$_PM_['auth']['countonfail'];
        if ($failure['fail_count'] > 0) {
            $failedlogin .= ' ('.date($WP_msg['dateformat'], $failure['fail_time']).')';
            $t_lf->fill_block('resetfail', array
                    ('msg_resetfail' => $WP_msg['SuReset']
                    ,'link_resetfail' => htmlspecialchars($link_base.'users&whattodo=resetfail&uid='.$uid)
                    ));
        }
        $t_lf->assign(array
                ('loginfail' => $failedlogin
                ,'lastlogin' => isset($PHM['login_time']) ? date($WP_msg['dateformat'], $PHM['login_time']) : '---'
                ,'lastlogout' => isset($PHM['logout_time']) ? date($WP_msg['dateformat'], $PHM['logout_time']) : '---'
                ,'leg_stat' => $WP_msg['CUMLegStat']
                ));
        $tpl->assign('loginfail', $t_lf);
        $tpl->assign(array
                ('msg_syspass' => $WP_msg['syspass']
                ,'where_user' => str_replace('$1', phm_entities($PHM['username']), $WP_msg['UMLinkUser'])
                ));
    } else {
        $tpl->assign(array('msg_syspass' => $WP_msg['sysnewpass'], 'where_user' => $WP_msg['UMLinkUserNew']));
    }
    if (isset($DB->features['groups']) && $DB->features['groups']) {
        $t_hgrp = $tpl->get_block('has_groups');
        $groups = $DB->get_grouplist(false);
        $usergroups = ('edituser' == $whattodo) ? $DB->get_usergrouplist($uid) : array();
        if (!empty($groups)) {
            $t_grpl = $t_hgrp->get_block('groupline');
            cfg_out_groups($groups['childs'], 0, 0, $usergroups); // The structure allows hierarchic groups, so a helper is needed
        }
        $tpl->assign('has_groups', $t_hgrp);
    } else {
        $tpl->assign_block('no_groups');
    }
    if (isset($DB->features['permissions']) && $DB->features['permissions']) {
        $t_ph = $tpl->get_block('priv_handler');
        $t_pp = $t_ph->get_block('priv_priv');
        // Read all handlers' available privileges
        foreach ($_PM_['handlers'] as $handler => $active) {
            // Look for an installation API call available
            if (!file_exists($_PM_['path']['handler'].'/'.$handler.'/configapi.php')) {
                continue;
            }
            require_once($_PM_['path']['handler'].'/'.$handler.'/configapi.php');
            $call = 'handler_'.$handler.'_configapi';
            if (!in_array('get_perm_actions', get_class_methods($call))) {
                continue;
            }
            $API = new $call($_PM_, 0);
            $perms = $API->get_perm_actions($WP_conf['language']);
            if (empty($perms)) {
                unset($API);
                continue;
            }
            $t_ph->assign(array('handlername' => ucfirst($handler), 'handler' => $handler));
            foreach ($perms as $k => $v) {
                $t_pp->assign(array('handler' => $handler, 'priv' => $k, 'privname' => $v));
                $t_ph->assign('priv_priv', $t_pp);
                $t_pp->clear();
            }
            $tpl->assign('priv_handler', $t_ph);
            $t_ph->clear();
            unset($API);
        }
    }
    $tpl->assign(array
            ('target_link' => htmlspecialchars($link_base.'users&whattodo='.$nwhatto)
            ,'link_edpf' => $link_base.'users&mode=profiles&uid='
            ,'link_um' => htmlspecialchars($link_base.'users')
            ,'userpriv_geturl' => $link_base.'users&mode=get_uperm&uid='.$uid
            ,'userpriv_seturl' => $link_base.'users&mode=set_uperm&uid='.$uid
            ,'where_um' => $WP_msg['UMLinkUM']
            ,'msg_save' => $WP_msg['save']
            ,'msg_cancel' => $WP_msg['cancel']
            ,'msg_groups' => $WP_msg['groups']
            ,'leg_basic' => $WP_msg['UMLegBasic']
            ,'msg_active' => $WP_msg['optactive']
            ,'msg_yes' => $WP_msg['yes']
            ,'msg_no' => $WP_msg['no']
            ,'msg_syspass2' => $WP_msg['syspass2']
            ,'msg_email' => $WP_msg['email']
            ,'msg_externalemail' => $WP_msg['sysextemail']
            ,'msg_www' => $WP_msg['WWW']
            ,'msg_firstname' => $WP_msg['Firstname']
            ,'msg_lastname' => $WP_msg['Surname']
            ,'msg_tel_private' => $WP_msg['TelPersonal']
            ,'msg_tel_business' => $WP_msg['TelBusiness']
            ,'msg_fax' => $WP_msg['Fax']
            ,'msg_cellular' => $WP_msg['Cellular']
            ,'msg_lastlogin' => $WP_msg['SuLastLogin']
            ,'msg_lastlogout' => $WP_msg['SuLastLogout']
            ,'msg_loginfail' => $WP_msg['SuLoginFail']
            ,'msg_all' => $WP_msg['all']
            ,'msg_none' => $WP_msg['none']
            ,'msg_inherit' => $WP_msg['inherit']
            ,'msg_simple' => $WP_msg['simple']
            ,'head_privs_user' => $WP_msg['PrivilegesOfUser']
            ,'poptitle_privileges' => $WP_msg['PrivilegesOfTheUser']
            ,'msg_setup' => $WP_msg['MenuSettings']
            ,'leg_motd' => $WP_msg['LegMOTD']
            ,'leg_sessionsec' => $WP_msg['LegSessSec']
            ,'leg_general' => $WP_msg['general']
            ,'leg_debugging' => $WP_msg['LegDebug']
            ,'leg_providername' => $WP_msg['LegName']
            ,'msg_showmotd' => $WP_msg['SuShowMOTD']
            ,'about_sessionsec' => $WP_msg['AboutSessSec']
            ,'msg_sessionip' => $WP_msg['SuTieSessionIp']
            ,'msg_sessioncookie' => $WP_msg['SuTieSessionCookie']
            ,'msg_opttheme' => $WP_msg['optskin']
            ,'msg_optlang' => $WP_msg['optlang']
            ,'msg_debugging' => $WP_msg['DebReportWhat']
            ,'about_debugging' => $WP_msg['AboutDebug']
//            ,'leg_providername' => $WP_msg['SuNameOfService']
            ,'msg_providername' => $WP_msg['SuNameOfService']
            ,'about_providername' => $WP_msg['AboutProvName']
            ,'msg_mayeditsettings' => $WP_msg['SuOptUserAllowConf']
            ,'msg_mayeditprofiles' => $WP_msg['SuOptUserConfAcc']
            ,'msg_visibility' => $WP_msg['ContactVisibility']
            ,'msg_private' => $WP_msg['ContactPrivate']
            ,'msg_public' => $WP_msg['ContactPublic']
            ,'msg_showlinkconfig' => $WP_msg['FEShowLinkConfig']
            ,'msg_logincheckupdates' => $WP_msg['FELoginCheckUpdates']
            ,'msg_logincheckupdatebetas' => $WP_msg['FELoginCheckUpdateBetas']
            ,'msg_CustomerNumber' => $WP_msg['CustomerNumber']
            ,'password' => isset($PHM['password']) ? phm_entities($PHM['password']) : ''
            ,'password2' => isset($PHM['password2']) ? phm_entities($PHM['password2']) : ''
            ,'email' => isset($PHM['email']) ? phm_entities($PHM['email']) : ''
            ,'externalemail' => phm_entities($PHM['externalemail'])
            ,'www' => isset($PHM['www']) ? phm_entities($PHM['www']) : ''
            ,'firstname' => isset($PHM['firstname']) ? phm_entities($PHM['firstname']) : ''
            ,'lastname' => isset($PHM['lastname']) ? phm_entities($PHM['lastname']) : ''
            ,'tel_private' => isset($PHM['tel_private']) ? phm_entities($PHM['tel_private']) : ''
            ,'tel_business' => isset($PHM['tel_business']) ? phm_entities($PHM['tel_business']) : ''
            ,'fax' => isset($PHM['fax']) ? phm_entities($PHM['fax']) : ''
            ,'cellular' => isset($PHM['cellular']) ? phm_entities($PHM['cellular']) : ''
            ,'MOTD' => isset($my_PM_['core']['MOTD']) ? htmlspecialchars(phm_stripslashes($my_PM_['core']['MOTD'])) : ''
            ,'customer_number' => isset($PHM['customer_number']) ? phm_entities($PHM['customer_number']) : ''
            ));
    // Some settings may be inherited from the master
    foreach (array('showmotd' => 'core_show_motd', 'sessionip' => 'auth_tie_session_ip', 'sessioncookie' => 'auth_session_cookie') as $k => $v) {
            $v2 = explode('_', $v, 2);
        if (isset($my_PM_[$v2[0]][$v2[1]])) {
            if ($my_PM_[$v2[0]][$v2[1]]) {
                $tpl->assign_block($k.'_1');
            } else {
                $tpl->assign_block($k.'_0');
            }
        } else {
            $tpl->assign_block($k.'_2');
        }
    }
    $t_deb = $tpl->get_block('debug_level');
    foreach ([
            '2_' => 'inherit',
            '0_disabled' => 'DebReportNone',
            '1_enabled' => 'DebReportAll',
            '3_system' => 'DebReportSystem'] as $k => $v) {
        $k2 = explode('_', $k, 2);
        $t_deb->assign(array('level' => $k2[0], 'msg_level' => $WP_msg[$v]));
        if (isset($my_PM_['core']['debugging_level']) && $my_PM_['core']['debugging_level'] == $k2[1]) {
            $t_deb->assign_block('sel');
        }
        $tpl->assign('debug_level', $t_deb);
        $t_deb->clear();
    }
    if (!empty($my_PM_['core']['showlinkconfig'])) {
        $tpl->assign_block('showlinkconfig');
    }
    if ($tpl->block_exists('logincheckupdates')) {
        if (!empty($my_PM_['core']['logincheckupdates'])) {
            $tpl->assign_block('logincheckupdates');
            if ($my_PM_['core']['logincheckupdates'] == 'beta') {
                $tpl->assign_block('logincheckupdatebetas');
            }
        }
    }
    $blockname = (isset($PHM['visibility']) && $PHM['visibility'] == 'public') ? 'sel_visibility_public' : 'sel_visibility_private';
    $tpl->assign($blockname, ' selected="selected"');

    $d_ = opendir($_PM_['path']['theme']);
    while (false !== ($skinname = readdir($d_))) {
        if ($skinname == '.' or $skinname == '..'
            || !is_dir($_PM_['path']['theme'].'/'.$skinname)) {
            continue;
        }
        if (file_exists($_PM_['path']['theme'].'/'.$skinname.'/main.tpl')) {
            $skins[] = $skinname;
        }
    }
    closedir($d_);
    sort($skins);
    $t_s = $tpl->get_block('themeline');
    foreach($skins as $skinname) {
        $t_s->assign(array('key' => $skinname,  'themename' => $skinname));
        if ($skinname == $my_PM_['core']['theme_name']) {
            $t_s->assign_block('sel');
        }
        $tpl->assign('themeline', $t_s);
        $t_s->clear();
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
        preg_match('!\$WP_msg\[\'language_name\'\]\ \=\ \'([^\']+)\'!', file_get_contents($_PM_['path']['message'].'/'.$langname), $found);
        $langname = preg_replace('/\.php$/i', '', trim($langname));
        $langs[] = $found[1];
        $langnames[] = $langname;
    }
    closedir($d_);
    array_multisort($langs, SORT_ASC, $langnames);
    $t_s = $tpl->get_block('langline');
    foreach($langs as $id => $langname) {
        $t_s->assign(array('key' => $langnames[$id], 'langname' => $langname));
        if ($langnames[$id] == $my_PM_['core']['language']) {
            $t_s->assign_block('sel');
        }
        $tpl->assign('langline', $t_s);
        $t_s->clear();
    }
}
if (!$whattodo || ($whattodo != 'adduser' && (!isset($uid) || !$uid))) {
    // Request
    $search = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';
    $criteria = (isset($_REQUEST['criteria']) && $_REQUEST['criteria']) ? $_REQUEST['criteria'] : 'all';
    $tpl = new phlyTemplate(CONFIGPATH.'/templates/um.overview.tpl');
    if ($WP_return) {
        $tpl->fill_block('return', 'WP_return', $WP_return);
    }
    $overview = $DB->get_usroverview($_PM_['auth']['countonfail']);
    foreach (array('all', 'inactive', 'locked', 'active') as $v) {
        if ($overview[$v] > 0) {
            $tpl->assign_block('search_'.$v);
            $tpl->assign('link_search_'.$v, htmlspecialchars($link_base.'users&search=&criteria='.$v));
            $tpl->assign('users_'.$v, number_format($overview[$v], 0, $WP_msg['dec'], $WP_msg['tho']));
        } else {
            $tpl->assign('users_'.$v, 0);
        }
    }
    $tpl->assign('users_max', $WP_msg['Unlimited']);
    $tpl->assign('search', $search);
    $tpl->assign_block('sel_crit_'.$criteria);
    if (!isset($search)) {
        $search = '*';
    }
    $users = $DB->get_usridx($search, $criteria);
    if (is_array($users)) {
        $myI = 0;
        $tpl_m = $tpl->get_block('menu');
        $tpl_m->assign(array('username' => $WP_msg['sysuser'], 'active' => $WP_msg['optactive']));
        $tpl_ml = $tpl_m->get_block('menuline');
        foreach ($users as $k => $v) {
            ++$myI;
            $usrdata = $DB->get_usrdata($k);
            if ($usrdata['active'] == 0) {
                $tpl_ml->assign('active', $WP_msg['no']);
                $tpl_ml->assign('link_active', htmlspecialchars($link_base.'users&whattodo=active&uid='.$k));
            } else {
                $tpl_ml->assign('active', $WP_msg['yes']);
                $tpl_ml->assign('link_active', htmlspecialchars($link_base.'users&whattodo=inactive&uid='.$k));
            }
            $tpl_ml->assign(array(
                    'uid' => $k,
                    'username' => phm_entities($v),
                    'link_dele' => htmlspecialchars($link_base.'users&whattodo=deleuser&uid='.$k),
                    'msg_dele' => $WP_msg['del'],
                    'link_edit' => htmlspecialchars($link_base.'users&whattodo=edituser&uid='.$k),
                    'msg_edit' => $WP_msg['edit'],
                    'msg_edpf' => $WP_msg['editprofiles']
                    ));
            $tpl_m->assign('menuline',$tpl_ml);
            $tpl_ml->clear();
        }
        $tpl->assign('menu', $tpl_m);
    } else {
        $tpl->assign_block('nomenu');
    }
    if (!isset($nooo) || 'o' != $nooo) {
        $tpl->fill_block('adduser', array
                ('link_adduser' => htmlspecialchars($link_base.'users&whattodo=adduser')
                ,'msg_adduser' => $WP_msg['SuAddUser']
                ));
    }
    $tpl->assign(array
            ('link_base' => htmlspecialchars($link_base)
            ,'head_text' => $WP_msg['SuHeadUser'], 'msg_cancel' => $WP_msg['cancel']
            ,'regusers' => $WP_msg['UMregusers'], 'maxlicence' => $WP_msg['UMmaxlicence']
            ,'msg_all' => $WP_msg['all'], 'msg_active' => $WP_msg['optactive']
            ,'msg_inactive' => $WP_msg['optinactive'], 'msg_locked' => $WP_msg['optlocked']
            ,'searchcrit' => $WP_msg['UMsearchcrit'], 'msg_finduser' => $WP_msg['UMfinduser']
            ,'msg_title' => $WP_msg['UMtitinpfind'], 'msg_find' => $WP_msg['UMfind']
            ,'msg_nomatch' => $WP_msg['UMnomatch']
            ,'link_edpf' => $link_base.'users&mode=profiles&uid='
            ,'confpath' => CONFIGPATH
            ,'search_target' => htmlspecialchars($link_base.'users')
            ));
}

function cfg_removedir($path)
{
    $d = opendir($path);
    while ($file = readdir($d)) {
        $name = $path.'/'.$file;
        if ('.' == $file) {
            continue;
        }
        if ('..' == $file) {
            continue;
        }
        if (is_dir($name)) {
            cfg_removedir($name);
        } else {
            unlink($name);
        }
    }
    closedir($d);
    rmdir($path);
}

function cfg_out_groups(&$groups, $child = 0, $level = 0, $usergroups = array())
{
    foreach ($groups[$child] as $v) {
        $GLOBALS['t_grpl']->assign(array('gname' => str_repeat('&nbsp;&nbsp;', $level).$v['friendly_name'], 'gid' => $v['gid']));
        if (in_array($v['gid'], $usergroups)) {
            $GLOBALS['t_grpl']->assign_block('sel');
        }
        $GLOBALS['t_hgrp']->assign('groupline', $GLOBALS['t_grpl']);
        $GLOBALS['t_grpl']->clear();
        if (isset($groups[$v['gid']])) {
            cfg_out_groups($groups, $v['gid'], $level+1, $usergroups);
        }
    }
}
