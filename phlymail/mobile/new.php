<?php
/**
 * Collect the "setup"new" screens of the respective handlers
 *
 * @package phlyMail Nahariya 4.0+ Default branch
 * @copyright 2012 phlyLabs, Berlin (http://phlylabs.de)
 * @version 
 */
// Only valid within phlyMail
defined('_IN_PHM_') || die();

// Build up links
$icon_path = $_PM_['path']['theme'].'/icons/';
$passthru1 = give_passthrough(1);
$link_base = PHP_SELF.'?'.$passthru1.'&h=';

$tpl = new phlyTemplate($_PM_['path']['templates'].'listfolder.general.tpl');

$t_l = $tpl->get_block('line');
$t_ld = $t_l->get_block('divider');
$t_lt = $t_l->get_block('target');
$t_ln = $t_l->get_block('notarget');

foreach ($_SESSION['phM_uniqe_handlers'] as $HDL => $handlerdata) {

    // Read the regular top button bar script, this e.g. names the handler
    if (file_exists($_PM_['path']['handler'].'/'.$HDL.'/topbuttonbar.php')) {
        $_PM_['handler']['path'] = $_PM_['path']['handler'].'/'.$HDL;
        $_PM_['handler']['name'] = $HDL;
        // It seems to be senseless to instantiate the class and right away destroy it again:
        // The constructor sets a session variable, which holds the i18n name of the handler
        $call = 'handler_'.$HDL.'_topbuttonbar';
        $API = new $call($_PM_);
        $menuItems = $API->get_new_menu();
        unset($API);
        if (empty($menuItems)) {
            continue;
        }

        foreach ($menuItems as $k => $item) {
            // Properties and permissions
            $propList = array();
            if (!empty($v['has_items'])) {
                $propList[] = 'has_items';
            }
            $t_lt->assign(array
                    ('proplist' => join(',', $propList)
                    ,'id' => $HDL . '_' . $k
                    ,'link'  => htmlspecialchars($link_base.$HDL.'&'.$item['localpath'])
                    ,'title' => phm_entities($item['name'])
                    ,'level' => 0
            ));
            $t_lt->fill_block('ticon', array('src' => $icon_path.$item['icon']));
            $t_l->assign('target', $t_lt);
            $t_lt->clear();

            $tpl->assign('line', $t_l);
            $t_l->clear();
        }
    }
}
