<?php
/**
 * @author Paul van Santen, Douwe Kasemier
 */

session_start();

require_once('json_encode.php');

require_once('Savant3.php');
$tpl = new Savant3();
$tpl->setPath('template', 'templates');
//$tpl->addFilters(array('Savant3_Filter_trimwhitespace', 'filter'));

// Login tonen
if (empty($_SESSION['uid'])) {
    $tpl->display('login.tpl.php');
    /*
     * DEBUG, REMOVE LATER !!!!!!!!!
     */
    $_SESSION['uid'] = 1;
    $_SESSION['group'] = 'ROOT';
} else {
    if (!in_array($_SESSION['group'], array('ROOT','USR','OP'))) {
        die('Unknown group!');
    }
    $menu = array();

    function addMenu($name, $data) {
        global $menu;

        if (array_key_exists($name, $menu) && array_key_exists('pages', $menu[$name])
                && count($menu[$name]['pages'])) {
            foreach ($data['pages'] as $page) {
                $menu[$name]['pages'][] = $page;
            }
        } else {
            $menu[$name] = $data;
        }
    }

    $modules = explode("\n", file_get_contents('menu/'.strtolower($_SESSION['group']).'.perms'));
    foreach ($modules as $module) {
        $filename = 'menu/modules/'.strtolower($module).'.php';
        if (file_exists($filename)) {
            require_once($filename);
        }
    }
    
    //Build the javascript namespace based on menu structure
    $data = array();
    foreach ($menu as $mod) {
        $data[$mod['key']] = array();
        foreach ($mod['pages'] as $page) {
            $data[$mod['key']][$page['key']] = array();
            foreach ($page['tabs'] as $tab) {
                $data[$mod['key']][$page['key']][$tab['key']] = array();
            }
        }
    }
    //Space for xml objects
    $data['data'] = array();
    $tpl->modules = $menu;
    $tpl->namespace = json_encode_custom($data, true);

    $tpl->display('layout.tpl.php');
}
