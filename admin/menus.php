<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * @copyright       The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU Public License
 * @package         Mymenus
 * @since           1.0
 * @author          trabis <lusopoemas@gmail.com>
 * @version         $Id: menus.php
 */

$currentFile = basename(__FILE__);
include_once __DIR__ . '/admin_header.php';

$op = XoopsRequest::getCmd('op', 'list');
switch ($op) {
    case 'list':
    default:
        $apply_filter = XoopsRequest::getBool('apply_filter', false);
        //  admin navigation
        xoops_cp_header();
        $moduleAdmin = new ModuleAdmin();
        echo $moduleAdmin->addNavigation($currentFile);
        // buttons
        if ($apply_filter === true) {
            $moduleAdmin->addItemButton(_LIST, '?op=list', 'list');
        }
        $moduleAdmin->addItemButton(_ADD, $currentFile . "?op=edit", 'add');
        echo $moduleAdmin->renderButton();
        //
        $menusCount = $mymenus->getHandler('menus')->getCount();
        $GLOBALS['xoopsTpl']->assign('menusCount', $menusCount);
        //
        if ($menusCount > 0) {
            // get filter parameters
            $filter_menus_title_condition = XoopsRequest::getString('filter_menus_title_condition', '');
            $filter_menus_title           = XoopsRequest::getString('filter_menus_title', '');
            //
            $menusCriteria = new CriteriaCompo();
            //
            if ($apply_filter === true) {
                // evaluate title criteria
                if ($filter_menus_title != '') {
                    switch ($filter_menus_title_condition) {
                        case 'CONTAINS':
                        default:
                            $pre      = '%';
                            $post     = '%';
                            $function = 'LIKE';
                            break;
                        case 'MATCHES':
                            $pre      = '';
                            $post     = '';
                            $function = '=';
                            break;
                        case 'STARTSWITH':
                            $pre      = '';
                            $post     = '%';
                            $function = 'LIKE';
                            break;
                        case 'ENDSWITH':
                            $pre      = '%';
                            $post     = '';
                            $function = 'LIKE';
                            break;
                    }
                    $menusCriteria->add(new Criteria('title', $pre . $filter_menus_title . $post, $function));
                }
            }
            $GLOBALS['xoopsTpl']->assign('apply_filter', $apply_filter);
            $menusFilterCount = $mymenus->getHandler('menus')->getCount($menusCriteria);
            $GLOBALS['xoopsTpl']->assign('menusFilterCount', $menusFilterCount);
            //
            $menusCriteria->setSort('id');
            $menusCriteria->setOrder('ASC');
            //
            $start = XoopsRequest::getInt('start', 0);
            $limit = $mymenus->getConfig('admin_perpage');
            $menusCriteria->setStart($start);
            $menusCriteria->setLimit($limit);
            //
            if ($menusFilterCount > $limit) {
                xoops_load('XoopsPagenav');
                $linklist = "op={$op}";
                $linklist .= "&filter_menus_title_condition={$filter_menus_title_condition}";
                $linklist .= "&filter_menus_title={$filter_menus_title}";
                $pagenavObj = new XoopsPageNav($itemFilterCount, $limit, $start, 'start', $linklist);
                $pagenav    = $pagenavObj->renderNav(4);
            } else {
                $pagenav = '';
            }
            $GLOBALS['xoopsTpl']->assign('pagenav', $pagenav);
            //
            $filter_menus_title_condition_select = new XoopsFormSelect(_AM_MYMENUS_MENU_TITLE, 'filter_menus_title_condition', $filter_menus_title_condition, 1, false);
            $filter_menus_title_condition_select->addOption('CONTAINS', _CONTAINS);
            $filter_menus_title_condition_select->addOption('MATCHES', _MATCHES);
            $filter_menus_title_condition_select->addOption('STARTSWITH', _STARTSWITH);
            $filter_menus_title_condition_select->addOption('ENDSWITH', _ENDSWITH);
            $GLOBALS['xoopsTpl']->assign('filter_menus_title_condition_select', $filter_menus_title_condition_select->render());
            $GLOBALS['xoopsTpl']->assign('filter_menus_title_condition', $filter_menus_title_condition);
            $GLOBALS['xoopsTpl']->assign('filter_menus_title', $filter_menus_title);
            //
            $menusObjs = $mymenus->getHandler('menus')->getObjects($menusCriteria);
            foreach ($menusObjs as $menusObj) {
                $menusObjArray = $menusObj->getValues(); // as array
                $GLOBALS['xoopsTpl']->append('menus', $menusObjArray);
                unset($menusObjArray);
            }
            unset($menusCriteria, $menusObjs);
        } else {
            // NOP
        }
        $GLOBALS['xoopsTpl']->display($GLOBALS['xoops']->path("modules/{$mymenus->dirname}/templates/static/mymenus_admin_menus.tpl"));
        include_once __DIR__ . '/admin_footer.php';
        break;

    case 'add':
    case 'edit':
        //  admin navigation
        xoops_cp_header();
        $moduleAdmin = new ModuleAdmin();
        echo $moduleAdmin->addNavigation($currentFile);
        // buttons
        $moduleAdmin->addItemButton(_LIST, $currentFile . "?op=list", 'list');
        echo $moduleAdmin->renderButton();
        //
        $id = XoopsRequest::getInt('id', 0);
        if (!$menusObj = $mymenus->getHandler('menus')->get($id)) {
            // ERROR
            redirect_header($currentFile, 3, _AM_MYMENUS_MSG_ERROR);
        }
        $form = $menusObj->getForm();
        $form->display();
        //
        include_once __DIR__ . '/admin_footer.php';
        break;

    case 'save':
        if (!$GLOBALS['xoopsSecurity']->check()) {
            redirect_header($currentFile, 3, implode(',', $GLOBALS['xoopsSecurity']->getErrors()));
        }
        $id         = XoopsRequest::getInt('id', 0, 'POST');
        $isNewMenus = ($id == 0) ? true : false;
        //
        $menus_title = XoopsRequest::getString('title', '', 'POST');
        $menus_css   = XoopsRequest::getString('css', '', 'POST');
        //
        $menusObj = $mymenus->getHandler('menus')->get($id);
        //
        $menusObj->setVar('title', $menus_title);
        $menusObj->setVar('css', $menus_css);
        //
        if (!$mymenus->getHandler('menus')->insert($menusObj)) {
            // ERROR
            xoops_cp_header();
            echo $menusObj->getHtmlErrors();
            xoops_cp_footer();
            exit();
        }
        $id = (int)$menusObj->getVar('id');
        //
        if ($isNewMenus) {
            // NOP
        } else {
            // NOP
        }
        //
        redirect_header($currentFile, 3, _AM_MYMENUS_MSG_SUCCESS);
        break;

    case 'delete':
        $id       = XoopsRequest::getInt('id', null);
        $menusObj = $mymenus->getHandler('menus')->get($id);
        if (XoopsRequest::getBool('ok', false, 'POST') === true) {
            if (!$GLOBALS['xoopsSecurity']->check()) {
                redirect_header($currentFile, 3, implode(',', $GLOBALS['xoopsSecurity']->getErrors()));
            }
            // delete menus
            if (!$mymenus->getHandler('menus')->delete($menusObj)) {
                // ERROR
                xoops_cp_header();
                xoops_error(_AM_MYMENUS_MSG_ERROR, $menusObj->getVar('id'));
                xoops_cp_footer();
                exit();
            }
            // Delete links
            $mymenus->getHandler('links')->deleteAll(new Criteria('mid', $id));
            redirect_header($currentFile, 3, _AM_MYMENUS_MSG_DELETE_MENU_SUCCESS);
        } else {
            xoops_cp_header();
            xoops_confirm(
                array('ok' => true, 'id' => $id, 'op' => 'delete'),
//                $_SERVER['REQUEST_URI'],
                XoopsRequest::getString('REQUEST_URI','', 'SERVER'),
                sprintf(_AM_MYMENUS_MENUS_SUREDEL, $menusObj->getVar('title'))
            );
            include_once __DIR__ . '/admin_footer.php';
        }
        break;
}
