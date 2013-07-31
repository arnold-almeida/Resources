<?php

class MenuHelper extends AppHelper {

    var $helpers = array('Html', 'Session');

    /**
     * @var HtmlHelper
     */
//  var $Html;

    /**
     * @var array
     * @access public
     */
    var $configs = array();
    var $testMenuStructure = false;

    /**
     * Returns a Contexulaised menu
     *
     */
    function getMenuByRole($role = null) {
        if (empty($role)) {
            $role = $this->Session->read('Auth.User.role');
        }
        $menu = Configure::read("ADMIN_MENU.{$role}");
        if (!empty($menu)) {
            return $menu;
        }
        trigger_error("No menu defined in menus.inc for ['{$role}']", E_USER_ERROR);
    }

    /**
     *
     * @var array
     * @access protected
     */
    var $defaults = array(
        'display' => array(
            'root' => true,
            'children' => true
        ),
        'role' => null,
        'params' => array(
            'controller' => array(), // Controller params
            'action' => null, // Current Controller.action
        ),
    );

    /**
     * Menu options
     * @var <type>
     */
    var $options = array();

    /**
     * Function used to reder the menu for the icing admin theme
     *
     */
    function renderAdminMenu($menu, $options = array()) {

        $this->options = Set::merge($this->defaults, $options);
        $count = 1;
        $max_items = count($menu);
        $menu = $this->standardize($menu);
        $out = "";

        $out.= "<ul id=\"nav\" class=\"menu-{$options['role']}\">";
        foreach ($menu as $label => $item) {

            if ('Dashboard' == $label && (true == $options['prefix_dashboards'])) {
                $user = $this->Session->read('Auth.User');
                $label = "{$user['first_name']} {$user['last_name']}'s dashboard";
            }

            $out.="<li>";
            $out.=$this->__printMenuItem($label, $item);
            if (true == $this->options['display']['children']) {
                $out.="<ul>";
                $childCount = 0;
                foreach ($item['children'] as $label => $item) {
                    $out.="<li>";
                    $out.=$this->__printMenuItem($label, $item);
                    $out.="</li>";
                    $childCount++;
                }
                $out.="</ul>";
            }
            $count++;
            $out.="</li>";
        }
        $out.="</ul>";
        return $out;
    }

    function __printMenuItem($label, $data) {
        return $this->Html->link($label, $data['url']);
    }

    function renderMenu($menu, $options = array()) {

        $this->options = Set::merge($this->defaults, $options);
        $count = 1;
        $max_items = count($menu);
        $menu = $this->standardize($menu);

        if (true == $this->options['display']['children']) {

            $currentRoot = $this->getSelectedRootNode($menu);

            if (!empty($currentRoot['children'])) {
                $menu = $currentRoot['children'];
            } else {
                return;
            }
        }

        $out = "<ul id=\"nav\" class=\"menu-{$options['role']}\">";
        foreach ($menu as $label => $item) {
            $out.=$this->printMenuItem($label, $item, $menu, $count, $max_items);

            if (isset($item['children']) && true == $this->options['display']['children']) {
                $out.="<ul>";
                $childCount = 0;

                foreach ($item['children'] as $label => $item) {
                    /*
                      debug($label);
                      debug($item);
                     */
                    $childCount++;
                }

                $out.="</ul>";
            }
            $count++;
        }

        $out.="</ul>";

        return $out;
    }

    /**
     * Ensure that all menu items have the keys "admin,plugin,prefix,controller,action"
     *
     * @return Array $menu
     */
    function standardize($menu) {

        foreach ($menu as $label => $item) {
            // Fix for poor cake links...
            if (array_key_exists('admin', $item['url']) && $item['url']['admin'] == true) {
                $menu[$label]['url'] = Set::merge($item['url'], array('prefix' => 'admin'));
            }
            if (!array_key_exists('plugin', $item['url']) && $item['url']['admin'] == true) {
                $menu[$label]['url'] = Set::merge($item['url'], array('plugin' => ''));
            }

            if (!array_key_exists('prefix', $item['url']) && $item['url']['prefix'] == true) {
                $menu[$label]['url'] = Set::merge($item['url'], array('prefix' => ''));
            }
        }

        return $menu;
    }

    function getSelectedRootNode($menu) {

        foreach ($menu as $label => $node) {

            $current = $this->__checkActiveNode($node);

            if (false != $current) {
                return $current;
            }

            // Check the child nodes to see if any of them are active ??
            if (!empty($node['children'])) {
                foreach ($node['children'] as $label => $childNode) {

                    $current = $this->__checkActiveNode($childNode);

                    if (false != $current) {
                        // Return the parent...
                        return $node;
                    }
                }
            }
        }
    }

    /**
     * Checks if this Node is active
     *
     * @param <type> $node
     * @return <type>
     */
    function __checkActiveNode($node) {

        if (is_array($node['url'])) {
            if (
                    ($this->options['params']['controller'] == $node['url']['controller'])
                    &&
                    ($this->options['params']['action'] == $node['url']['action'])
                    &&
                    ($this->options['params']['prefix'] == $node['url']['prefix'])
            ) {
                return $node;
            }
        } else {
            debug("MENU url is not in CakePhp array format !");
            debug($node['url']);
        }

        return false;
    }

    /**
     *
     * @param <type> $label
     * @param <type> $item
     * @param <type> $menu
     * @param <type> $count
     * @param <type> $max_items
     * @return string
     */
    function printMenuItem($label, $item, $menu, $count, $max_items) {

        $params = $this->options['params'];

        $isPermitted = true;

        if (isset($item['permission'])) {

            $isPermitted = false;
            if (is_string($item['permission'])) {
                $item['permission'] = array($item['permission']);
            }


            // Check if this person has root access!
            $root_access = $this->Session->read('Auth.User.permissions.0');

            if (isset($root_access) && $root_access == '*') {
                $isPermitted = true;
            } else {

                foreach ($item['permission'] as $permission) {

                    $permissionAccess = $this->Session->read('Auth.User.permissions');
                    if (empty($permissionAccess)) {
                        $permissionAccess = array();
                    }
                    if (in_array($permission, $permissionAccess)) {
                        $isPermitted = true;
                        continue;
                    } else {
                        //debug($session->read('Auth.User.role') . ' : does not have permission to access "' . $permission . '" update User->getPermissions()');
                    }
                }
            }
        }

        $isPermitted = true;


        if (!$isPermitted) {
            return;
        }

        $linkSelected = '';
        $isActive = $this->isMenuActive($label, $menu, $params);
        $itemClass = Inflector::slug(strtolower($label), '-');
        if ($isActive) {
            //$itemClass .= ' menu-active';
            $itemClass .= ' selected';
            $linkSelected = 'selected';
        }
        if (1 == $count) {
            $itemClass .= ' first';
        }


        if ($max_items == $count) {
            $itemClass .= ' last';
        }

        $out = "<li class='{$itemClass}'>";
        $out.=$this->Html->link($label, $item['url'], array('class' => 'action ' . $linkSelected));

        if ($isActive && !empty($item['children'])) {
            $child_max_items = count($item['children']);
            $child_count = 0;

            $out.="<ul class='menu-admin'>";

            foreach ($item['children'] as $childLabel => $childItem) {
                @$out.=$this->printMenuItem($childLabel, $childItem, $item['children'], $html, $params, $session, $child_count, $child_max_items);
            }

            $out.="</ul>";
        }


        $out.="</li>";

        return $out;
    }

    function isMenuActive($label, $menu, $params) {
        if (empty($menu[$label])) {
            return false;
        }

        $node = $menu[$label];

        if (($params['controller'] == $node['url']['controller']) && ($params['action'] == $node['url']['action'])) {
            return true;
        } else {
            if (empty($node['children'])) {
                return false;
            }

            foreach ($node['children'] as $childLabel => $childItem) {
                if ($this->isMenuActive($childLabel, $node['children'], $params)) {
                    return true;
                }
            }
            return false;
        }
    }

/**
 * Generates a Menu
 */
    public function generate($menu=array(), $currentUrl, $out=array()) {

        if(!is_array($menu)){ return false; }

        foreach($menu as $label => &$node) {

            $hasChildren= false;
            $liClass    = array();
            $aClass     = array();

            $aClass[] = strtolower($label);

            // If no plugin key is set add one!
            if (!isset($node['url']['plugin'])) {
                $node['url']['plugin'] = false;
            }

            $url = Router::url($node['url']);

            // Is this a dropdown ?
            if (!empty($node['children'])) {
                $hasChildren = true;
                $liClass[] = 'dropdown';
                $aClass[]   = 'dropdown-toggle';
            }

            // Add active class ?
            if(false != strpos($currentUrl,$url)) {
                $liClass[] = 'active';
            }

            if (true == $hasChildren) {
                $out[] = '<li class="'.implode(" ", $liClass).'"><a data-toggle="dropdown" class="'.implode(" ", $aClass).'" href="#">'.$label.'<b class="caret"></b></a></a>';
                $out[] = '<ul class="dropdown-menu">';
                    $out = $this->generate($node['children'], $currentUrl, $out);
                $out[] = '</ul></li>';
            } else {
                $out[] = '<li class="'.implode(" ", $liClass).'"><a class="'.implode(" ", $aClass).'" href="'.$url.'">'.$label.'</a></li>';
            }

        }

        return $out;
    }

/**
 *<li class="nav-header">List header</li>
 * <li class="active"><a href="#">Home</a></li>
 * <li><a href="#">Library</a></li>
 * <li><a href="#">Applications</a></li>
 * <li class="nav-header">Another list header</li>
 * <li><a href="#">Profile</a></li>
 * <li><a href="#">Settings</a></li>
 * <li class="divider"></li>
 * <li><a href="#">Help</a></li>
 */
    function renderNavActions($actions=array()) {
        $out = array();
        foreach($actions as $label => $link) {
            switch($link['type']) {
                case'divider':
                    $out[] = '<li class="divider"></li>';
                    break;
                case'header':
                    $out[] = '<li class="nav-header">'.$label.'</li>';
                    break;
                case'url':
                    $url = Router::url($link['url']);
                    $out[] = '<li><a href="'.$url.'">'.$label.'</a></li> ';
                    break;
            }
        }
        return implode("\r\n", $out);
    }

}
