<?php
App::uses('AppHelper', 'View/Helper');
class DataRendererHelper extends AppHelper
{
    public $helpers = array('Html', 'Form', 'Paginator', 'Tree');

    public $_slugCache = array();

    public function _defaultOptions()
    {
        $options = array(
            'tree_label_path'   => null,        // Model.field_name
            'show_headers' => true,
            'child_rows' => "",             // Rendered tabular data to put after </td></tr>
            'id' => false,
            'primaryKey' => 'id',
            'hiddenFields' => array('id','parent_id','depth','child_rows'),
            'emptyMessage' => "<span class='message'>No records found</span>",
            'wrapperClass' => 'ui-widget ui-widget-content ui-data-table',
            'containerClass' => 'ui-widget-content ui-corner-all ui-data',
            'rowClass' => 'ui-data-row even',
            'rowClassAlt' => 'ui-data-row ui-data-row-alt odd',
            'rowActionsClass' => 'ui-data-row-actions',
            'fieldClass' => 'ui-data-field',
            'headerClass' => 'tableLine1'
        );

        return $options;
    }

    public function _slug($label='')
    {
        if (isset($this->_slugCache[$label])) {
            return $this->_slugCache[$label];
        }
        $slug = preg_replace('#[^a-z0-9]#', '-', strtolower($label));
        $this->_slugCache[$label] = $slug;

        return $slug;
    }

    /**
     *
     * Renders a Media Plugin Image
     */
    public function mediaImage($Version = null, $item)
    {

        if (!empty($Version)) {
            $file = $this->Media->file($previewVersion . '/', $item);
        } else {
            $file = $this->Media->file($item);
        }

        if ($file) {
            return $this->Media->embed( $file, array(
                    'restrict' => array('image')
            ));
        }

        return false;

    }

    public function asSortableList($data,$options = array())
    {
        $options = Set::merge(
            $this->_defaultOptions(),
            $options
        );

        if (empty($data)) {
            return $this->renderNoData($options);
        }

        $out = '';
        $out .= "<div class='{$options['wrapperClass']}'>";
            $out .= "<ol class='{$options['containerClass']}' id='{$options['id']}' >";

            debug($data);

            $out .= "</ol>";
        $out .= "</div>";

        return $out;
    }

    public function asDefinitionList($data,$options = array())
    {
        $options = Set::merge(
            $this->_defaultOptions(),
            $options
        );

        if (empty($data)) {
            return $this->renderNoData($options);
        }

        $out = '';
        $out .= "<div class='{$options['wrapperClass']}'>";
            $out .= "<dl class='{$options['containerClass']}' id='{$options['id']}' >";

            foreach ($data as $dt => $dd) {

                $dt = Inflector::humanize($dt);
                $dd = (empty($dd)) ? '-' : $dd ;

                $out .= "<dt>{$dt}</dt>";
                $out .= "<dd>{$dd}</dd>";
            }

            $out .= "</dl>";
        $out .= "</div>";

        return $out;

    }

    public function filter($data)
    {

        foreach ($data as $i => $d) {
            if (empty($d)) {
                $data[$i] = '-';
            }

        }

        return $data;

    }

    public function asTableTree($data, $options = array())
    {
    $defaults = array(
        'type' => 'table-tree',
        'indent_style' => '-',
        );

     return $this->asTable($data, Set::merge($defaults,$options) );
    }

    public function renderNoData($options)
    {
        $out ='';
        $out .= "<div class='{$options['wrapperClass']}'>";
        $out .= "<table class='{$options['containerClass']} ui-data-empty'  id='{$options['id']}' border='0' cellpadding='0' cellspacing='0'>";
        $out .= "<tr><td>{$options['emptyMessage']}</td></tr>";
        $out .= "</table>";
        $out .= "</div>";

        return $out;
    }

    public function asList($data, $options = array())
    {
        $options = Set::merge(
            $this->_defaultOptions(),
            $options
        );

        $this->printPagination($data, $options);

        $out = '';
        $out .= "<div class='{$options['wrapperClass']}'>";
        $out .= "<ul class='{$options['containerClass']}' id='{$options['id']}' >";

        if (!empty($data)) {

            $headers = $this->_getHeaders($data, $options);
            $actions = $this->_getActions($data, $options);

//          $out .= '<li class="ui-widget-header">';
//          foreach ($headers as $index => $header) {
//
//              $class = 'ui-data-header-' . $this->_slug($header);
//
//              if (!empty($this->Paginator) && isset($options['pagination'][$header])) {
//                  $header = $this->Paginator->sort($header, $options['pagination'][$header]);
//              }
//
//              $out .= "<th class='{$class}'>{$header}</th>";
//          }
//          $out .= '</tr>';

            foreach ($data as $index => $row) {
                $rowClass = ($index % 2 == 0) ? $options['rowClass'] : $options['rowClassAlt'];

                $out .= "<li class='{$rowClass}'>";
                foreach ($row as $field => $value) {
                    if (in_array($field, $options['hiddenFields'], true)) continue;
                    $fieldClass = $options['fieldClass'] . ' ui-data-field-'.$this->_slug($field);
                    $out .= "<span class='{$fieldClass}'><label>{$field}</label>{$value}</span>";
                }
                if (!empty($actions)) {
                    $fieldClass = $options['rowActionsClass'];
                    $out .= "<span class='{$fieldClass}'>{$actions[$index]}</span>";
                }
                $out .= '</li>';

            }
        } else {
            $out .= "<li class='ui-data-empty'>{$options['emptyMessage']}</li>";
        }

        $out .= "</ul>";
        $out .= "</div>";

        return $out;
    }

    public function asTreeList($data , $options = array())
    {
        $options = Set::merge(
            $this->_defaultOptions(),
            $options
        );

        $out = '';
        $out .= "<div class='{$options['wrapperClass']}'>";
        $out .= "<ul class='{$options['containerClass']}' id='{$options['id']}' >";

        if (!empty($data)) {
            $level = 0;
            foreach ($data as $index => $row) {
                $rowClass = ($index % 2 == 0) ? $options['rowClass'] : $options['rowClassAlt'];
                $new_level = 0;
                $row_content = '';
                $first_element_in_row = true;
                foreach ($row as $field => $value) {
                    if (is_array($value)) {
                        $value = Set::flatten($value);
                        $value = $value[$options['tree_label_path']];
                    }
                    if (in_array($field, $options['hiddenFields'], true)) {continue;}
                    $fieldClass = $options['fieldClass'] . ' ui-data-field-'.$this->_slug($field);
                    if (in_array($this->_slug($field), array('title'))) {
                        $row_content .= "<dt class='{$fieldClass}'>{$value}</dt>";
                        $new_level = count(explode('-',array_shift(explode(' ',$value))));
                    } else {
                        $row_content .= "<dd class='{$fieldClass}'>{$value}</dd>";
                        if ($first_element_in_row) {$new_level = count(explode('-',array_shift(explode(' ',$value))));}
                    }
                    $first_element_in_row = false;
                }
                if (!empty($actions)) {
                    $fieldClass = $options['rowActionsClass'];
                    $row_content .= "<dd class='{$fieldClass}'>{$actions[$index]}</dd>";
                }

                if ($new_level > $level) {
                    if ($level > 0) {
                        // Start new nested level
                        $out .= "<ul>";
                        $out .= "<li>";
                        $out .= "<dl class='{$rowClass}'>";
                        $out .= $row_content;
                        $out .= '</dl>';
                    } else {
                        // Add first tree element
                        $out .= "<li>";
                        $out .= "<dl class='{$rowClass}'>";
                        $out .= $row_content;
                        $out .= '</dl>';
                    }
                } else {
                    if ($new_level < $level) {
                        // Go to one or more levels up, close previous opened nested level
                        $out .= '</li>';
                        $levels_up = floor(($level - $new_level) / 2 + ($level - $new_level) % 2);
                        for ($i = 0; $i < $levels_up; $i++) {
                            $out .= "</ul>";
                        }
                        $out .= "<li>";
                        $out .= "<dl class='{$rowClass}'>";
                        $out .= $row_content;
                        $out .= '</dl>';

                    } else {
                        // Add element to the same level
                        $out .= '</li>';
                        $out .= "<li>";
                        $out .= "<dl class='{$rowClass}'>";
                        $out .= $row_content;
                        $out .= '</dl>';
                    }
                }
                $level = $new_level;
            }
        } else {
            $out .= "<li class='ui-data-empty'>{$options['emptyMessage']}</li>";
        }
        $out .= '</li>';
        $out .= "</ul>";
        $out .= "</div>";

        echo $this->Html->script(array('/Resources/js/dnd.ui.js'), array('inline' => false));

        return $out;
    }

    public function asSortableTable($data , $options = array())
    {
        $defaults = array(
            'type' => 'sortable-table',
            'id' => 'ui-sortable',
            'tbodyClass' => 'sort'

        );

        return $this->asTable($data , Set::merge( $defaults , $options ) );
    }

    public function progressBox($current , $total)
    {
        $percentage_complete = round( (($current/$total)*100) , 0 );

        $out = '<div style="display: block; border: 1px solid black; height: 15px;" title="'.$percentage_complete.'% Complete">';
                    $out.= '<span style="display: block; height: 15px; background-color: #EF4300; width: '.$percentage_complete.'%;">';
                    $out.= '</span>';
        $out.= '</div>';

        return $out;
    }

    /**
     * Generates a table for admin actions
     * @param  <type> $data
     * @param  <type> $options
     * @return string
     */
    public function asAdminTable($data, $options=array())
    {
        $options = Set::merge(
            $this->_defaultOptions(),
            $options ,
            array(
                'tbodyClass' => ''
            )
        );

        $out = '';

        if (isset($options['returnPagination']) && FALSE == $options['returnPagination']) {
            $this->_pagination = $this->printPagination($data, $options);
        } else {
            if (isset($this->_pagination)) {
                $out.=$this->_pagination;
            }
        }

        if (empty($data)) {
            return $this->renderNoData($options);
        }

        $out .= "<div class='{$options['wrapperClass']}'>";

        if (isset($options['multiActions'])) {
            $out .= $this->Form->create($options['multiActions']['model'] , array('url' => $this->here) );
        }

        $out .= "<table class='{$options['containerClass']}'  id='{$options['id']}' border='0' cellpadding='0' cellspacing='0'>";
        $out .= "<tbody class='{$options['tbodyClass']}'>";

        $headers = $this->_getHeaders($data, $options);
        $actions = $this->_getActions($data, $options);

        if (isset($options['multiActions'])) {
            $headers = Set::merge(array('<input type="checkbox" class="check_all">') , $headers );
        }

        if (true == $options['show_headers']) {
            $out .= '<tr class="ui-widget-header">';
            foreach ($headers as $index => $header) {

                $class = 'ui-data-header-' . $this->_slug($header);

                if (isset($options['pagination'][$header])) {
                    $headerClass = ($this->Paginator->sortKey() == $options['pagination'][$header])
                                ? ($this->Paginator->sortDir() == 'asc' ? 'ui-data-sort ui-data-sort-asc' : 'ui-data-sort ui-data-sort-desc')
                                : 'ui-data-sort';

                    $header = $this->Paginator->sort($header, $options['pagination'][$header], array('class'=>$headerClass , 'title' => "Sort by {$header}"));
                }

                $out .= "<th class='{$class}'>{$header}</th>";
            }
            $out .= '</tr>';
        }

        foreach ($data as $index => $row) {

            if (isset($options['multiActions'])) {

                if (!isset($row['id'])) {
                    trigger_error('In order to use MultiActions an id key must be define' , E_USER_ERROR );
                }
                if (!isset($options['multiActions']['model'])) {
                    trigger_error(' $options[\'multiActions\'][\'model\']  is not set' , E_USER_ERROR );
                }

                $row = Set::merge(array(
                    'Select' => $this->Form->input("{$options['multiActions']['model']}.{$index}.id" , array('type' => 'checkbox' , 'value' => $row['id'] , 'div' => false , 'label' => false ) )
                ) , $row );
            }

            $rowClass = ($index % 2 == 0) ? $options['rowClass'] : $options['rowClassAlt'];

            $out .= "<tr class='{$rowClass}' id='{$row['id']}_{$row['id']}'>";
            foreach ($row as $field => $value) {
                if (in_array($field, $options['hiddenFields'], true)) continue;
                $fieldClass = $options['fieldClass'];
                $out .= "<td class='{$fieldClass}'>{$value}</td>";
            }

            // This dosnt relly get called...
            if (!empty($actions)) {
                $fieldClass = $options['rowActionsClass'];
                $out .= "<td class='{$fieldClass}'>{$actions[$index]}</td>";
            }
            $out .= '</tr>';

            if (isset($data[$index]['child_rows'])) {
                $out.='<tr class="ui-inner-tr">';
                    // TODO AUTO CALCULATE the colspans so we can use it in more areas !
                    $out.='<td colspan="1">&nbsp;</td>';
                    $out.='<td colspan="5">';
                        $out.=$data[$index]['child_rows'];
                    $out.='</td>';
                $out.='</tr>';
            }

        }
        $out .= "</tbody>";
        $out .= "</table>";

        if (isset($options['multiActions'])) {
            $out .= $this->Form->input('multiAction' , array('value' => 1 , 'type' => 'hidden' ));
            $out .= $this->Form->input('_multiAction_type' , array('options' => $options['multiActions']['actions'] , 'label' => false , 'div' => false ));
            $out .= $this->Form->submit('Apply to selected' , array('class' => 'submit tiny' ));
            $out .= $this->Form->end();
        }
        $out .= "</div>";

        return $out;

    }

    /**
     * Generate a table
     * @param  <type> $data
     * @param  <type> $options
     * @return string
     */
    public function asTable($data, $options=array())
    {
        $options = Set::merge(
            $this->_defaultOptions(),
            $options ,
            array(
                'tbodyClass' => '',
                'containerClass' => 'table table-striped'
            )
        );

        $out = '';

        //pagination on the top
        if (!empty($data)) {
            $out .= $this->printPagination($data, $options);
        }

        if (empty($data)) {
            return $this->renderNoData($options);
        }

        // Lets not output an empty ID attribute
        $tableId = null;
        if (!empty($options['id'])) {
            $tableId = "id='{$options['id']}'";
        }

        $out .= "<div class='{$options['wrapperClass']}'>";
        $out .= "<table class='sortable {$options['containerClass']} datatable' {$tableId}>";

        $headers = $this->_getHeaders($data, $options);
        $actions = $this->_getActions($data, $options);

        if (true == $options['show_headers']) {
            $out .= '<thead><tr class="ui-widget-header '.$options['headerClass'].'">';
            $count = 1;
            foreach ($headers as $index => $header) {
                $class = ' ui-data-header-' . $this->_slug($header) . ' th' . $count;
                $count++;

                if (isset($options['pagination'][$header])) {
                    $headerClass = ($this->Paginator->sortKey() == $options['pagination'][$header])
                                ? ($this->Paginator->sortDir() == 'asc' ? 'ui-data-sort ui-data-sort-asc' : 'ui-data-sort ui-data-sort-desc')
                                : 'ui-data-sort';

                    $header = $this->Paginator->sort($options['pagination'][$header], $header, array('class'=>$headerClass));
                }

                $out .= "<th class='{$class}'>{$header}</th>";
            }

            $out .= '</tr></thead>';
        }

        $out .= "<tbody class='{$options['tbodyClass']}'>";

        foreach ($data as $index => $row) {
            $rowClass = ($index % 2 == 0) ? $options['rowClass'] : $options['rowClassAlt'];

            if (isset($row['id'])) {
                $out .= "<tr class='{$rowClass}' id='{$row['id']}_{$row['id']}'>";
            } else {
                $out .= "<tr class='{$rowClass}'>";
            }

            foreach ($row as $field => $value) {
                $indent = "";

                if ($field == 'Title' && isset($options['type']) && $options['type'] == 'table-tree') {
                    if (!empty($row['parent_id'])) {
                        $indent = str_repeat($options['indent_style'], $row['depth']);
                    }
                }

                if (in_array($field, $options['hiddenFields'], true)) continue;
                $fieldClass = $options['fieldClass'];
                $out .= "<td class='{$fieldClass}'>{$indent} {$value}</td>";

            }

            // This dosnt relly get called...
            if (!empty($actions)) {
                $fieldClass = $options['rowActionsClass'];
                $out .= "<td class='{$fieldClass}'>{$actions[$index]}</td>";
            }
            $out .= '</tr>';

            if (isset($data[$index]['child_rows'])) {
                $out.='<tr>';
                // TODO AUTO CALCULATE the colspans so we can use it in more areas
                    $out.='<td colspan="1">&nbsp;</td>';
                    $out.='<td colspan="4">';
                        $out.=$data[$index]['child_rows'];
                    $out.='</td>';
                $out.='</tr>';
            }

        }
        $out .= "</tbody>";
        $out .= "</table>";
        $out .= "</div>";

        //pagination on the bottom
        if (!empty($data)) {
            $out .= $this->printPagination($data, $options);
        }

        return $out;
    }

    public function asTree($data, $options = array())
    {
        $options = Set::merge(
            $this->_defaultOptions(),
            array(
                'moveAction' => null,
                'containerClass' => 'ui-tree',
            ),
            $options
        );

        $view =& ClassRegistry::getObject('view');

        $out = '';
        $out .= $this->printPagination($data, $options);

        if (empty($data)) {
            $out .= $options['emptyMessage'];

            return $out;
        }

        if (empty($options['model'])) {
            $options['model'] = Inflector::classify($view->params['models'][0]);
        }

        if (empty($options['id'])) {
            $options['id'] = $options['model'] . 'Tree';
        }

        if (empty($options['element']) && empty($options['callback'])) {
            $options['callback'] = array(&$this, '_treeNode');

            if (empty($options['displayField'])) {
                $options['displayField'] = 'title';
            }
        }

        $out .= "<div id='{$options['id']}' class='ui-tree' rel='{$options['moveAction']}'>";

        $options['id'] = null;
        $options['class'] = 'tree-root';

        $this->_treeOptions = $options;
        $out .= $this->Tree->generate($data, $options);
        $this->_treeOptions = null;

        $out .= "</div>";

        //  Attach javascript libraries
        $view->addScript(sprintf($this->Javascript->tags['javascriptlink'], '/resources/js/jstree/jquery.cookie.js'));
        $view->addScript(sprintf($this->Javascript->tags['javascriptlink'], '/resources/js/jstree/_lib.js'));
        $view->addScript(sprintf($this->Javascript->tags['javascriptlink'], '/resources/js/jstree/tree_component.min.js'));
        $view->addScript(sprintf($this->Html->tags['css'], 'stylesheet', '/resources/css/jstree/tree_component.css', ''));

        return $out;
    }

    public function _treeNode($data)
    {
        $options = $this->_treeOptions;

        $modelName = $options['model'];
        $displayField = $options['displayField'];
        $node = $data['data'][$modelName];

        $isFolder = !empty($node['is_folder']) || !empty($node['is_category']);
        $nodeClass = ($isFolder) ? 'ui-tree-folder' : 'ui-tree-node';
        $nodeDomId = "{$modelName}TreeNode-{$node['id']}";

        $this->Tree->addItemAttribute('id', $nodeDomId);

        $out = '';

        $actions = $this->_getActions(array($node), $options);
        if (!empty($actions[0])) {
            $out .= "<div class='actions'>{$actions[0]}</div>";
        }

        $out .= "<a href='#' class='{$nodeClass}'>{$node[$displayField]}</a>";

        return $out;
    }

    public function printPagination($data, $options = array())
    {
        if (!$this->_hasPaginator($options)) {
            return false;
        }

        $currentAction = $this->params['action'];

        if (isset($paginationUrl)) {
            $paginatorOptions = array('url'=> array_merge(array('action'=>$currentAction), $paginationUrl));
        } else {
            $paginatorOptions = array(
                'url'=> array_merge(
                    array('action'=>$currentAction),
                    $this->params['pass'],
                    $this->params['named']
                )
            );
        }

        if (isset($pagination['options'])) {
            $paginatorOptions = array_merge($paginatorOptions, $pagination['options']);
        }

        if (method_exists($this->Paginator, 'pagination')) {
            return $this->Paginator->pagination();
        }

        $this->Paginator->options($paginatorOptions);
        $model = $this->Paginator->defaultModel();
        $params = $this->Paginator->params($model);

        $out = "<div class='ui-data-paging pagination right'>";
        $out .= "<div class='ui-data-paging-numbers'>";
        if ($params['pageCount'] > 1) {
            if ($params['pageCount'] > 0 && $params['page'] != 1) {
                $out .= $this->Paginator->link('&laquo;', array('page'=> 1), array('class'=>'ui-data-paging-first', 'escape' => false));
            } else {
                $out .= $this->Paginator->Html->div(null, '&laquo;', array('class'=> 'ui-data-paging-first ui-data-paging-disabled'), false);
            }

            $out .= $this->Paginator->prev('&lsaquo;', array('class'=>'ui-data-paging-prev', 'escape'=>false), null, array('class'=>'ui-data-paging-prev ui-data-paging-disabled', 'escape'=>false));
            $out .= $this->Paginator->numbers(array('separator' => false, 'class'=>'ui-data-paging-number'));
            $out .= $this->Paginator->next('&rsaquo;', array('class'=>'ui-data-paging-next', 'escape'=>false), null, array('class'=>'ui-data-paging-next ui-data-paging-disabled', 'escape'=>false));

            if ($params['page'] != $params['pageCount']) {
                $out .= $this->Paginator->link('&raquo;', array('page'=> $params['pageCount']), array('class'=>'ui-data-paging-last', 'escape' => false));
            } else {
                $out .= $this->Paginator->Html->div(null, '&raquo;', array('class'=> 'ui-data-paging-last ui-data-paging-disabled'), false);
            }
        }
        $out .= "</div>";

        $out .= "<div class='ui-data-paging-range'>Displaying " . $this->Paginator->counter(array('format'=>'range')) . " records</div>";
        $out .= "</div>";

        return $out;
    }

    public function _hasPaginator($options)
    {
        return (in_array('Paginator', $this->_View->helpers)
                ||
                array_key_exists('Paginator', $this->_View->helpers) && !empty($options['pagination'])
            )
            &&
            !empty($options['pagination']);
    }

    public function _getHeaders($data, $options=array())
    {
        $headers = array_keys($data[0]);

        //  Remove hidden fields from headers if supplied
        foreach ($options['hiddenFields'] as $hiddenField) {
            if (in_array($hiddenField, $headers)) {
                array_splice($headers, array_search($hiddenField, $headers), 1);
            }
        }

        if (!empty($options['actions'])) {
            $headers[] = 'Actions';
        }

        return $headers;
    }

    public function makeLink()
    {
    }

    public function sortableActions($actions, $options = array())
    {
        $defaults = array(
            'extra' => 'sortable'
        );

        return $this->actions($actions, Set::merge($defaults , $options));
    }

    public function actions($actions, $opt = array())
    {
        $first      = null;
        $dropdown   = array();
        $count      = 1;

        foreach ($actions as $label => $url) {
            $options = array(
                'escape' => false,
            );

            if (1 == $count) {
                $options['class'] = 'btn btn-mini btn-primary';
            }

            //if target array element present, use as target attribute for the link
            if (isset($url['target']) && is_array($url)) {
                $options['target'] = $url['target'];
                unset($url['target']);
            }

            $confirmMessage = null;

            if (is_array($url)) {
                if (!empty($url['confirmMessage'])) {
                    $confirmMessage = $url['confirmMessage'];
                    unset($url['confirmMessage']);
                } elseif (!empty($url['action']) && ($url['action'] == 'delete' || $url['action'] == 'admin_delete')) {
                    $confirmMessage = 'Are you sure you want to delete this record?';
                }
            }

            if ('Edit' == $label) {
                $label = '<i class="icon-pencil icon-white"></i> '.$label;
            }

            if (!empty($confirmMessage)) {
                $link = $this->Html->link($label, $url, $options, $confirmMessage);
            } else {
                if (is_array($url) && isset($url['url'])) {
                    $url = $url['url'];
                }
                $link = $this->Html->link($label, $url, $options);
            }

            if (1 == $count) {
                $first = $link;
            } else {
                $dropdown[$label] = $link;
            }

            $count++;
        }

        $out = '<div class="btn-group">';
        $out.=$first;
        $out.='<a href="#" data-toggle="dropdown" class="btn btn-mini btn-primary dropdown-toggle"><span class="caret"></span></a>';
        $out.='<ul class="dropdown-menu">';
            foreach ($dropdown as $label => $drop) {
                switch ($label) {
                    case'divider':
                        $out.='<li class="divider"></li>';
                        break;
                    default:
                        $out.='<li>'.$drop.'</li>';
                        break;
                }
            }
        $out.='</ul>';
        $out.= "</div>";

        return $out;
    }

    public function __actions($actions, $opt = array())
    {
        $out = "<ul class='actions'>";
        foreach ($actions as $label => $url) {
            $options = array();
            $options = array(
                            'escape' => false,
            );

            //if target array element present, use as target attribute for the link
            if (isset($url['target']) && is_array($url)) {
                $options['target'] = $url['target'];
                unset($url['target']);
            }

            $confirmMessage = null;

            if (is_array($url)) {
                if (!empty($url['confirmMessage'])) {
                    $confirmMessage = $url['confirmMessage'];
                    unset($url['confirmMessage']);
                } elseif (!empty($url['action']) && ($url['action'] == 'delete' || $url['action'] == 'admin_delete')) {
                    $confirmMessage = 'Are you sure you want to delete this record?';
                }
            }

            if (!empty($confirmMessage)) {
                $out .= "<li>" . $this->Html->link($label, $url, $options, $confirmMessage) . "</li>";
            } else {
                if (is_array($url) && isset($url['url'])) {
                    $url = $url['url'];
                }
                $out .= "<li>" . $this->Html->link($label, $url, $options) . "</li>";
            }

        }

        if (isset($opt['extra'])) {

            switch ($opt['extra']) {
                case'sortable':
                    $out .= "<li>" . $this->Html->link('Move' , array('action' => 'move') , array('class' => 'action-move')) . "</li>";
                    break;
                default:
                        die();exit();
                    break;
            }
        }

        $out .= "</ul>";

        return $out;
    }

    public function _getActions($data, $options=array())
    {
        if (empty($options['actions'])) {
            return false;
        }

        $actions = array();
        foreach ($data as $index => $row) {

            $rowActions = '';
            foreach ($options['actions'] as $action => $url) {

                // Extract any custom confirm messages here, and remove from the URL array
                //  so they are not included as a named param, which can break other url elements
                if (isset($url['confirmMessage'])) {
                    $confirmMessage = String::insert($url['confirmMessage'], $data[$index]);
                    $url['confirmMessage'] = '';
                } else {
                    $confirmMessage = 'Are you sure you want to delete #"' .$data[$index][$options['primaryKey']] . '"?';
                }

                if (is_array($url)) {
                    $actionUrl = am($url, array($data[$index][$options['primaryKey']]));
                } elseif (is_string($url)) {
                    trigger_error('URL must be provided in array syntax, not string syntax, as sprintf() not multibyte friendly');
                    $actionUrl = sprintf($url, $data[$index][$options['primaryKey']]);
                }

                if (strtolower($action) == 'delete' || isset($url['confirmMessage'])) {
                    $rowAction = $this->Html->link(
                        $action,
                        $actionUrl,
                        array('class'=>'action'),
                        $confirmMessage
                    );
                } else {
                    $rowAction = $this->Html->link(
                        $action,
                        $actionUrl,
                        array('class'=>'action')
                    );

                }

                $class = "ui-state-default ui-corner-all ui-data-action-" . $this->_slug($action);
                $rowActions .= "<span class='{$class}'>{$rowAction}</span> ";
            }

            $actions[$index] = $rowActions;
        }

        return $actions;
    }

    public function getPagination()
    {
        return $this->_pagination;
    }

}
