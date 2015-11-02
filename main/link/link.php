<?php
/* For licensing terms, see /license.txt */

/**
 * Main script for the links tool.
 *
 * Features:
 * - Organize links into categories;
 * - favorites/bookmarks-like interface;
 * - move links up/down within a category;
 * - move categories up/down;
 * - expand/collapse all categories (except the main "non"-category);
 * - add link to 'root' category => category-less link is always visible.
 *
 * @author Julio Montoya code rewritten
 * @author Patrick Cool
 * @author René Haentjens, added CSV file import (October 2004)
 * @package chamilo.link

 */

// Including libraries
//require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_LINK;

$this_section = SECTION_COURSES;
api_protect_course_script();

$htmlHeadXtra[] = '<script type="text/javascript">
    $(document).ready( function() {
        for (i=0;i<$(".actions").length;i++) {
            if ($(".actions:eq("+i+")").html()=="<table border=\"0\"></table>" || $(".actions:eq("+i+")").html()=="" || $(".actions:eq("+i+")").html()==null) {
                $(".actions:eq("+i+")").hide();
            }
        }
     });

     function check_url(id, url) {
        var url = "'.api_get_path(WEB_AJAX_PATH).'link.ajax.php?a=check_url&url=" +url;
        var loading = " '.addslashes(Display::return_icon('loading1.gif')).'";
        $("#url_id_"+id).html(loading);
        $("#url_id_"+id).load(url);
     }
 </script>';

// @todo change the $_REQUEST into $_POST or $_GET
// @todo remove this code
$link_submitted = isset($_POST['submitLink']);
$category_submitted = isset($_POST['submitCategory']);
$urlview = !empty($_GET['urlview']) ? $_GET['urlview'] : '';
$submit_import = !empty($_POST['submitImport']) ? $_POST['submitImport'] : '';
$down = !empty($_GET['down']) ? $_GET['down'] : '';
$up = !empty($_GET['up']) ? $_GET['up'] : '';
$catmove = !empty($_GET['catmove']) ? $_GET['catmove'] : '';
$editlink = !empty($_REQUEST['editlink']) ? $_REQUEST['editlink'] : '';
$id = !empty($_REQUEST['id']) ? $_REQUEST['id'] : '';
$urllink = !empty($_REQUEST['urllink']) ? $_REQUEST['urllink'] : '';
$title = !empty($_REQUEST['title']) ? $_REQUEST['title'] : '';
$description = !empty($_REQUEST['description']) ? $_REQUEST['description'] : '';
$selectcategory = !empty($_REQUEST['selectcategory']) ? $_REQUEST['selectcategory'] : '';
$submit_link = isset($_REQUEST['submitLink']);
$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : '';
$category_title = !empty($_REQUEST['category_title']) ? $_REQUEST['category_title'] : '';
$submit_category = isset($_POST['submitCategory']);
$target_link = !empty($_REQUEST['target_link']) ? $_REQUEST['target_link'] : '_self';

$nameTools = get_lang('Links');

$course_id = api_get_course_int_id();
// Condition for the session
$session_id = api_get_session_id();
$condition_session = api_get_session_condition($session_id, true, true);

if ($action == 'addlink') {
    $nameTools = '';
    $interbreadcrumb[] = array('url' => 'link.php', 'name' => get_lang('Links'));
    $interbreadcrumb[] = array('url' => '#', 'name' => get_lang('AddLink'));
}

if ($action == 'addcategory') {
    $nameTools = '';
    $interbreadcrumb[] = array('url' => 'link.php', 'name' => get_lang('Links'));
    $interbreadcrumb[] = array('url' => '#', 'name' => get_lang('AddCategory'));
}

if ($action == 'editlink') {
    $nameTools = '';
    $interbreadcrumb[] = array('url' => 'link.php', 'name' => get_lang('Links'));
    $interbreadcrumb[] = array('url' => '#', 'name' => get_lang('EditLink'));
}

// Statistics
Event::event_access_tool(TOOL_LINK);

/*	Action Handling */
$nameTools = get_lang('Links');

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
$scope = isset($_REQUEST['scope']) ? $_REQUEST['scope'] : null;
$show = isset($_REQUEST['show']) && in_array(trim($_REQUEST['show']), ['all', 'none']) ? $_REQUEST['show'] : '';
$categoryId = isset($_REQUEST['category_id']) ? intval($_REQUEST['category_id']) : '';

$linkListUrl = api_get_self().'?'.api_get_cidreq().'&category_id='.$categoryId.'&show='.$show;

$content = null;

switch ($action) {
    case 'addlink':
        if (api_is_allowed_to_edit(null, true)) {
            $form = Link::getLinkForm(null, 'addlink');
            if ($form->validate()) {
                // Here we add a link
                Link::addlinkcategory("link");
                header('Location: '.$linkListUrl);
                exit;
            }
            $content = $form->returnForm();
        }
        break;
    case 'editlink':
        $form = Link::getLinkForm($id, 'editlink');
        if ($form->validate()) {

            Link::editLink($id, $form->getSubmitValues());
            header('Location: '.$linkListUrl);
            exit;
        }
        $content = $form->returnForm();
        break;
    case 'addcategory':
        if (api_is_allowed_to_edit(null, true)) {
            $form = Link::getCategoryForm(null, 'addcategory');

            if ($form->validate()) {
                // Here we add a category
                Link::addlinkcategory('category');
                header('Location: '.$linkListUrl);
                exit;
            }
            $content = $form->returnForm();
        }
        break;
    case 'editcategory':
        if (api_is_allowed_to_edit(null, true)) {
            $form = Link::getCategoryForm($id, 'editcategory');

            if ($form->validate()) {
                // Here we edit a category
                Link::editCategory($id, $form->getSubmitValues());

                header('Location: '.$linkListUrl);
                exit;
            }
            $content = $form->returnForm();
        }
        break;
    case 'deletelink':
        // Here we delete a link
        Link::deletelinkcategory($id, 'link');
        header('Location: '.$linkListUrl);
        exit;
        break;
    case 'deletecategory':
        // Here we delete a category
        Link::deletelinkcategory($id, 'category');
        header('Location: '.$linkListUrl);
        exit;
        break;
    case 'visible':
        // Here we edit a category
        Link::change_visibility_link($id, $scope);
        header('Location: '.$linkListUrl);
        exit;
        break;
    case 'invisible':
        // Here we edit a category
        Link::change_visibility_link($id, $scope);
        header('Location: '.$linkListUrl);
        exit;
        break;
    case 'up':
        Link::movecatlink('up', $up);
        header('Location: '.$linkListUrl);
        exit;
        break;
    case 'down':
        Link::movecatlink('down', $down);
        header('Location: '.$linkListUrl);
        exit;
        break;
    case 'list':
    default:
        ob_start();
        Link::listLinksAndCategories($course_id, $session_id, $categoryId, $show);
        $content = ob_get_clean();
        break;
}

Display::display_header($nameTools, 'Links');

/*	Introduction section */
Display::display_introduction_section(TOOL_LINK);

echo $content;

Display::display_footer();
