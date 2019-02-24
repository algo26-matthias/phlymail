<?php
/**
 * setup.filters.php -> Define inbox filtering rules
 *
 * @package phlyMail Nahariya 4.0+
 * @subpackage handler email
 * @author Matthias Sommerfeld
 * @copyright 2005-2015 phlyLabs, Berlin (http://phlylabs.de)
 * @version 4.1.1 2015-02-13 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

$global = 0;
if (!empty($_REQUEST['global']) && !empty($_SESSION['phM_privs']['all']) && !empty($_SESSION['phM_privs']['email_edit_global_filters'])) {
    $global = 1;
}
$link_base = PHP_SELF . '?l=setup&h=email&mod=filters&'.give_passthrough(1).($global ? '&global=1' : '').'&mode=';
$mode = (isset($_REQUEST['mode'])) ? $_REQUEST['mode'] : false;

$STOR = new handler_email_driver($global ? 0 : $_SESSION['phM_uid']);
if ('save' == $mode) {
	$filter = array
			('type' => 'incoming'
			,'match' => (isset($_REQUEST['match']) && 'all' == $_REQUEST['match']) ? 'all' : 'any'
			,'name' => (isset($_REQUEST['filtername'])) ? $_REQUEST['filtername'] : ''
			,'move' => (isset($_REQUEST['mv']) && $_REQUEST['mv']) ? 1 : 0
			,'move_to' => (isset($_REQUEST['mv_folder']) && $_REQUEST['mv_folder']) ? intval($_REQUEST['mv_folder']) : ''
			,'copy' => (isset($_REQUEST['cp']) && $_REQUEST['cp']) ? 1 : 0
			,'copy_to' => (isset($_REQUEST['cp_folder']) && $_REQUEST['cp_folder']) ? intval($_REQUEST['cp_folder']) : ''
			,'set_prio' => (isset($_REQUEST['priority']) && $_REQUEST['priority']) ? 1 : 0
			,'new_prio' => (isset($_REQUEST['prio_level']) && $_REQUEST['prio_level']) ? intval($_REQUEST['prio_level']) : 3
			,'mark_read' => (isset($_REQUEST['markread']) && $_REQUEST['markread']) ? 1 : 0
			,'markread_status' => (isset($_REQUEST['readstat']) && $_REQUEST['readstat']) ? $_REQUEST['readstat'] : ''
			,'set_colour' => (isset($_REQUEST['colourmark']) && $_REQUEST['colourmark']) ? 1 : 0
			,'new_colour' => (isset($_REQUEST['newcolour']) && $_REQUEST['newcolour']) ? preg_replace('![^0-9a-fA-F]!', '', $_REQUEST['newcolour']) : 'none'
			,'mark_junk' => (isset($_REQUEST['junk']) && $_REQUEST['junk']) ? 1 : 0
			,'archive' => (isset($_REQUEST['archive']) && $_REQUEST['archive']) ? 1 : 0
			,'delete' => (isset($_REQUEST['delete']) && $_REQUEST['delete']) ? 1 : 0
			,'alert_sms' => (isset($_REQUEST['alert_sms']) && $_REQUEST['alert_sms']) ? 1 : 0
			,'sms_to' => (isset($_REQUEST['sms_to']) && $_REQUEST['sms_to']) ? $_REQUEST['sms_to'] : ''
			,'sms_timeframe' => (isset($_REQUEST['sms_timeframe_fromh']) ? sprintf('%02d', intval($_REQUEST['sms_timeframe_fromh'])) : '00').':'
                    .(isset($_REQUEST['sms_timeframe_fromm']) ? sprintf('%02d', intval($_REQUEST['sms_timeframe_fromm'])) : '00').'-'
                    .(isset($_REQUEST['sms_timeframe_toh']) ? sprintf('%02d', intval($_REQUEST['sms_timeframe_toh'])) : '23').':'
                    .(isset($_REQUEST['sms_timeframe_tom']) ? sprintf('%02d', intval($_REQUEST['sms_timeframe_tom'])) : '59')
            ,'sms_minpause' => (isset($_REQUEST['sms_minpause_val'])) ? intval($_REQUEST['sms_minpause_val']) : 0
			,'alert_email' => (isset($_REQUEST['alert_email']) && $_REQUEST['alert_email']) ? 1 : 0
			,'email_to' => (isset($_REQUEST['email_to']) && $_REQUEST['email_to']) ? $_REQUEST['email_to'] : ''
			,'email_timeframe' => (isset($_REQUEST['email_timeframe_fromh']) ? sprintf('%02d', intval($_REQUEST['email_timeframe_fromh'])) : '00').':'
                    .(isset($_REQUEST['email_timeframe_fromm']) ? sprintf('%02d', intval($_REQUEST['email_timeframe_fromm'])) : '00').'-'
                    .(isset($_REQUEST['email_timeframe_toh']) ? sprintf('%02d', intval($_REQUEST['email_timeframe_toh'])) : '23').':'
                    .(isset($_REQUEST['email_timeframe_tom']) ? sprintf('%02d', intval($_REQUEST['email_timeframe_tom'])) : '59')
            ,'email_minpause' => (isset($_REQUEST['email_minpause_val'])) ? intval($_REQUEST['email_minpause_val']) : 0
			);
	foreach ($_REQUEST['field'] as $k => $v) {
		// No empty seraches get saved in the DB, only sensible ones
		if (!isset($_REQUEST['search'][$k]) || !$_REQUEST['search'][$k]) {
            continue;
        }
		$filter['rules'][] = array
				('field' => (isset($_REQUEST['field'][$k])) ? $_REQUEST['field'][$k] : ''
				,'operator' => (isset($_REQUEST['operator'][$k])) ? $_REQUEST['operator'][$k] : ''
				,'search' => $_REQUEST['search'][$k]
				);
	}
	if (isset($_REQUEST['filter']) && $_REQUEST['filter']) {
		$filter['id'] = $_REQUEST['filter'];
		$state = $STOR->filters_updatefilter($filter);
		if (!$state) { echo $DB->error(); exit; }
	} else {
	    $filter['active'] = 1;
	    $filter['type'] = 'incoming';
		$state = $STOR->filters_addfilter($filter);
		if (!$state) { echo $DB->error(); exit; }
	}
	header('Location: '.$link_base);
	exit;
}

if ('delete' == $mode && isset($_REQUEST['filter'])) {
	$state = $STOR->filters_removefilter(intval($_REQUEST['filter']));
	if (!$state) { echo $DB->error(); exit; }
	header('Location: '.$link_base);
	exit;
}

if ('activate' == $mode && isset($_REQUEST['filter'])) {
	$state = $STOR->filters_activatefilter(intval($_REQUEST['filter']));
	if (!$state) { echo $DB->error(); exit; }
	$urladd = (isset($_REQUEST['selected']) && $_REQUEST['selected']) ? '&selected='.$_REQUEST['selected'] : '';
	header('Location: '.$link_base.$urladd);
	exit;
}

if ('reorder' == $mode && isset($_REQUEST['filter'])) {
	$updown = (isset($_REQUEST['dir']) && $_REQUEST['dir'] == 'up') ? 'up' : 'down';
	$state = $STOR->filters_reorder(intval($_REQUEST['filter']), $updown);
	if (!$state) { echo $DB->error(); exit; }
	$urladd = (isset($_REQUEST['selected']) && $_REQUEST['selected']) ? '&selected='.$_REQUEST['selected'] : '';
	header('Location: '.$link_base.$urladd);
	exit;
}

if ('edit' == $mode && isset($_REQUEST['filter'])) {
    // Userdaten für externe Emailadresse
    $userdata = $DB->get_usrdata($_SESSION['phM_uid']);
	$tpl = new phlyTemplate($_PM_['path']['templates'].'email.filters.edit.tpl');
	if ($_REQUEST['filter']) {
		$filter = $STOR->filters_getfilter($_REQUEST['filter']);
		$urladd = '&filter='.$_REQUEST['filter'];
	}
	if (!isset($filter) || !$filter || empty($filter)) {
		$urladd = '';
		$filter = array
				('match' => 'any'
				,'name' => $WP_msg['EmailNewFilter']
				,'move' => false
				,'move_to' => ''
				,'copy' => false
				,'copy_to' => ''
				,'set_prio' => false
				,'new_prio' => 3
				,'set_colour' => false
				,'new_colour' => 'none'
				,'mark_read' => false
				,'markread_status' => false
				,'mark_junk' => false
				,'archive' => false
				,'delete' => false
				,'alert_sms' => false
                ,'sms_to' => phm_entities($_PM_['core']['sms_sender'])
                ,'sms_timeframe' => '00:00-23:59'
                ,'sms_minpause' => 0
                ,'alert_email' => false
			    ,'email_to' => phm_entities($userdata['email'])
			    ,'email_timeframe' => '00:00-23:59'
                ,'email_minpause' => 0
				,'rules' => array(0 => array('field' => '', 'operator' => '', 'search' => ''))
				);
	}
	// Split apart the timeframe parameters for SMS and EMail (the range of time of a day, where alerts are sent)
	// Warning: This is by way not robust, since the format MUST be the same as intended, always two digits for
	// minutes and hours...
	if (!preg_match('!^(\d\d)\:(\d\d)\-(\d\d)\:(\d\d)$!', $filter['sms_timeframe'], $smstf)) {
	    $smstf = array('00:00-23:59', '00', '00', '23', '59');
	}
	if (!preg_match('!^(\d\d)\:(\d\d)\-(\d\d)\:(\d\d)$!', $filter['email_timeframe'], $emailtf)) {
	    $emailtf = array('00:00-23:59', '00', '00', '23', '59');
	}
	$tpl->assign_block(('all' == $filter['match']) ? 'match_all' : 'match_any');
	$t_prio = $tpl->get_block('prioline');
	foreach (array(1 => $WP_msg['low'], 3 => $WP_msg['normal'], 5 => $WP_msg['high']) as $k => $v) {
		$t_prio->assign(array('val' => $k, 'name' => $v));
		if ($filter['new_prio'] == $k) {
            $t_prio->assign_block('sel');
        }
		$tpl->assign('prioline', $t_prio);
		$t_prio->clear();
	}
	$t_stat = $tpl->get_block('readline');
	foreach (array('read' => $WP_msg['FilterMarkRead'], 'unread' => $WP_msg['FilterMarkUnread']) as $k => $v) {
		$t_stat->assign(array('val' => $k, 'name' => $v));
		if ($filter['markread_status'] == $k) {
            $t_stat->assign_block('sel');
        }
		$tpl->assign('readline', $t_stat);
		$t_stat->clear();
	}
	$t_mv = $tpl->get_block('moveline');
	$t_cp = $tpl->get_block('copyline');
	$STOR->init_folders(false);
	foreach ($STOR->read_folders_flat(0) as $id => $data) {
    	$lvl_space = ($data['level'] > 0) ? str_repeat('&nbsp;', $data['level']*2) : '';
    	$t_mv->assign(array
        	    ('id' => (!$data['has_items']) ? '" style="color:darkgray;" disabled="disabled' : $id
            	,'friendly_name' => $lvl_space . phm_entities($data['foldername'])
            	));
        if ($id == $filter['move_to']) {
            $t_mv->assign_block('sel');
        }
    	$t_cp->assign(array
        	    ('id' => (!$data['has_items']) ? '" style="color:darkgray;" disabled="disabled' : $id
            	,'friendly_name' => $lvl_space . phm_entities($data['foldername'])
            	));
        if ($id == $filter['copy_to']) {
            $t_cp->assign_block('sel');
        }
    	$tpl->assign('moveline', $t_mv);
    	$tpl->assign('copyline', $t_cp);
    	$t_mv->clear();
    	$t_cp->clear();
	}
	// Create the dropdowns for time selection for Email and SMS alerts
	$t_sfh = $tpl->get_block('sms_tf_fh');
	$t_sth = $tpl->get_block('sms_tf_th');
	$t_efh = $tpl->get_block('email_tf_fh');
	$t_eth = $tpl->get_block('email_tf_th');
	$t_sfm = $tpl->get_block('sms_tf_fm');
	$t_stm = $tpl->get_block('sms_tf_tm');
	$t_efm = $tpl->get_block('email_tf_fm');
	$t_etm = $tpl->get_block('email_tf_tm');
	foreach (range(0, 59, 1) as $zeit) {
	    $zeit = sprintf('%02d', $zeit);
    	$t_sfm->assign('h', $zeit);
        if ($zeit == $smstf[2]) {
            $t_sfm->assign_block('sel');
        }
    	$tpl->assign('sms_tf_fm', $t_sfm);
    	$t_sfm->clear();
    	$t_stm->assign('h', $zeit);
        if ($zeit == $smstf[4]) {
            $t_stm->assign_block('sel');
        }
    	$tpl->assign('sms_tf_tm', $t_stm);
    	$t_stm->clear();
    	$t_efm->assign('h', $zeit);
        if ($zeit == $emailtf[2]) {
            $t_efm->assign_block('sel');
        }
    	$tpl->assign('email_tf_fm', $t_efm);
    	$t_efm->clear();
    	$t_etm->assign('h', $zeit);
        if ($zeit == $emailtf[4]) {
            $t_etm->assign_block('sel');
        }
    	$tpl->assign('email_tf_tm', $t_etm);
    	$t_etm->clear();
	    // Ready assigning minute values
	    if ($zeit > 23) {
            continue;
        }
    	$t_sfh->assign('h', $zeit);
        if ($zeit == $smstf[1]) {
            $t_sfh->assign_block('sel');
        }
    	$tpl->assign('sms_tf_fh', $t_sfh);
    	$t_sfh->clear();
    	$t_sth->assign('h', $zeit);
        if ($zeit == $smstf[3]) {
            $t_sth->assign_block('sel');
        }
    	$tpl->assign('sms_tf_th', $t_sth);
    	$t_sth->clear();
    	$t_efh->assign('h', $zeit);
        if ($zeit == $emailtf[1]) {
            $t_efh->assign_block('sel');
        }
    	$tpl->assign('email_tf_fh', $t_efh);
    	$t_efh->clear();
    	$t_eth->assign('h', $zeit);
        if ($zeit == $emailtf[3]) {
            $t_eth->assign_block('sel');
        }
    	$tpl->assign('email_tf_th', $t_eth);
    	$t_eth->clear();
	}
	if ($filter['move']) {
        $tpl->assign_block('move');
    }
	if ($filter['copy']) {
        $tpl->assign_block('copy');
    }
	if ($filter['set_prio']) {
        $tpl->assign_block('prio');
    }
	if ($filter['set_colour']) {
        $tpl->assign_block('colour');
    }
	if ($filter['mark_junk']) {
        $tpl->assign_block('junk');
    }
	if ($filter['mark_read']) {
        $tpl->assign_block('read');
    }
	if (!empty($filter['archive'])) {
	    $tpl->assign_block('archive');
	} elseif ($filter['delete']) {
	    $tpl->assign_block('delete');
	}
	if ($filter['alert_sms']) {
        $tpl->assign_block('alert_sms');
    }
	if ($filter['alert_email']) {
        $tpl->assign_block('alert_email');
    }

	$t_rulf = $tpl->get_block('field');
	$t_rulo = $tpl->get_block('operator');
	$rule_tpl_done = false;
	$t_r = $tpl->get_block('ruleset');
	foreach ($filter['rules'] as $k => $rule) {
		$t_r->assign(array('search' => phm_entities($rule['search']), 'id' => time(), 'msg_delete' => $WP_msg['del']));
		$t_f = $t_r->get_block('field');
		foreach (array
				('from' => $WP_msg['FilterFieldFrom']
				,'to' => $WP_msg['FilterFieldTo']
				,'cc' => $WP_msg['FilterFieldCC']
				,'to_cc' => $WP_msg['FilterFieldToCC']
				,'subject' => $WP_msg['FilterFieldSubject']
				,'date' => $WP_msg['FilterFieldDate']
				,'priority' => $WP_msg['FilterFieldPriority']
				,'other_header' => $WP_msg['FilterFieldOtherHeader']
				) as $k => $v) {
			$t_f->assign(array('k' => $k, 'v' => $v));
			if ($rule['field'] == $k) {
                $t_f->assign_block('sel');
            }
			$t_r->assign('field', $t_f);
			$t_f->clear();
			if (!$rule_tpl_done) {
				$t_rulf->assign(array('k' => $k, 'v' => $v));
				$tpl->assign('field', $t_rulf);
				$t_rulf->clear();
			}
		}
		$t_o = $t_r->get_block('operator');
		foreach (array
				('contains' => $WP_msg['FilterOp_contains']
				,'n_contains' => $WP_msg['FilterOp_n_contains']
				,'is' => $WP_msg['FilterOp_is']
				,'n_is' => $WP_msg['FilterOp_n_is']
				,'begins' => $WP_msg['FilterOp_begins']
				,'ends' => $WP_msg['FilterOp_ends']
				,'regex' => $WP_msg['FilterOp_regex']
				) as $k => $v) {
			$t_o->assign(array('k' => $k, 'v' => $v));
			if ($rule['operator'] == $k) {
                $t_o->assign_block('sel');
            }
			$t_r->assign('operator', $t_o);
			$t_o->clear();
			if (!$rule_tpl_done) {
				$t_rulo->assign(array('k' => $k, 'v' => $v));
				$tpl->assign('operator', $t_rulo);
				$t_rulo->clear();
			}
		}
		$rule_tpl_done = true;
		$tpl->assign('ruleset', $t_r);
		$t_r->clear();
	}

	$tpl->assign(array
			('filtername' => phm_entities($filter['name'])
			,'formlink' => $link_base.'save'.$urladd
			,'sms_to' => phm_entities($filter['sms_to'])
			,'email_to' => phm_entities($filter['email_to'])
			,'sms_minpause_val' => $filter['sms_minpause']
			,'email_minpause_val' => $filter['email_minpause']
			,'msg_name' => $WP_msg['FilterName']
			,'msg_headrules' => $WP_msg['FilterHeadRules']
			,'msg_headactions' => $WP_msg['FilterHeadActions']
			,'msg_matchany' => $WP_msg['FilterMatchAny']
			,'msg_matchall' => $WP_msg['FilterMatchAll']
			,'msg_delete' => $WP_msg['del']
			,'msg_addrule' => $WP_msg['FilterAddRule']
			,'msg_move' => $WP_msg['FilterMoveMail']
			,'msg_copy' => $WP_msg['FilterCopyMail']
			,'msg_setprio' => $WP_msg['FilterSetPrio']
			,'msg_setcolour' => $WP_msg['markmailColour']
			,'msg_markas' => $WP_msg['FilterMarkAs']
			,'msg_markjunk' => $WP_msg['FilterMarkJunk']
			,'msg_deletemail' => $WP_msg['FilterDeleteMail']
			,'msg_archivemail' => $WP_msg['FilterArchiveMail']
			,'msg_alert_sms' => $WP_msg['FilterAlertSMSTo']
			,'msg_alert_email' => $WP_msg['FilterAlertEmailTo']
			,'msg_between' => $WP_msg['FilterSendBetween']
			,'msg_minpause_sms' => $WP_msg['FilterMinPauseSMS']
			,'msg_minpause_email' => $WP_msg['FilterMinPauseEmail']
			,'newcolour' => (isset($filter['new_colour']) && $filter['new_colour'] != 'none') ? phm_entities($filter['new_colour']) : ''
			,'msg_save' => $WP_msg['save']
			,'e_searchterm' => $WP_msg['PleaseEnterSearchTerm']
			));
	return;
}
$tpl = new phlyTemplate($_PM_['path']['templates'].'email.filters.overview.tpl');
foreach ($STOR->filters_getlist('incoming', false) as $line) {
	$t_l = $tpl->get_block('filterline');
	if ($line['active']) {
		$t_l->fill_block('active', 'msg_active', $WP_msg['optactive']);
	} else {
		$t_l->fill_block('inactive', 'msg_inactive', $WP_msg['optinactive']);
	}
	$t_l->assign(array('name' => $line['name'], 'id' => $line['id'], 'layered_id' => $line['layered_id']));
	$tpl->assign('filterline', $t_l);
	$t_l->clear();
}
$tpl->assign(array
		('edit_url' => $link_base.'edit&filter='
		,'delete_url' => $link_base.'delete&filter='
		,'reorder_url' => $link_base.'reorder&filter='
		,'activate_url' => $link_base.'activate&filter='
		,'msg_new' => $WP_msg['MainNew']
		,'msg_edit' => $WP_msg['Edit']
		,'msg_delete' => $WP_msg['del']
		,'msg_up' => $WP_msg['MoveUp']
		,'msg_down' => $WP_msg['MoveDown']
		));
if (isset($_REQUEST['selected']) && $_REQUEST['selected']) {
	$tpl->fill_block('ifselected', 'selected', intval($_REQUEST['selected']));
}
