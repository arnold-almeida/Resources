<?php

class HistoryComponent extends Component
{
    /**
     * Other components this component uses
     *
     * @var array
     */
    public $components = array('Session');

    /**
     * Default settings for the component, overrideable by Settings.history array
     * in $config
     *
     * 'sessionKey' is a string for the key in the Session for storing history data
     * Note this will have further keys for storing the history stacks
     *
     * 'autoPush' - if true, will execute push() in afterRender()
     *
     * @var array
     */
    public $settings;

    public $ignore = false;

    /**
     * Called automatically after controller beforeFilter
     * Stores refernece to controller object
     * Merges Settings.history array in $config with default settings
     *
     * @param object $controller
     */
    public function startup(Controller $controller)
    {
        $this->Controller = $controller;
        $this->settings = array(
            'sessionKey' => 'History',
            'defaultHistoryKey' => 'site',
            'defaultTitle' => 'Previous page',
            'autoPush' => false,
        );
        $configSettings = Configure::read('Settings.history');
        if (!empty($configSettings)) {
            $this->settings = array_merge($this->settings, $configSettings);
        }
    }

    public function afterRender()
    {
        if (!empty($this->settings['autoPush'])) {
            $this->push();
        }
    }

    /**
     * Adds to the end of the history stack
     *
     */
    public function push($historyKey=null)
    {
        $uri = $this->getCurrentUri();
        $history = $this->getHistory($historyKey);
        $prev = current($history);
        $prevUri = !empty($prev['uri']) ? $prev['uri'] : false;

        switch (true) {

            case $uri === false:
            case $uri === $prevUri:
            case isset($this->Controller->params['bare']):
            case isset($this->Controller->params['requested']):
            case isset($this->Controller->params['isAjax']):
            case isset($this->Controller->params['url']['ext']) && ($this->Controller->params['url']['ext'] != 'html'):
            case $this->Controller->params['action'] == 'login':
            case $this->Controller->params['action'] == 'logout':
                return false;
        }

        if (isset($history[1]['uri']) && $history[1]['uri'] == $uri) {
            array_shift($history);
        } else {
            if (!empty($this->Controller->pageTitle)) {
                $title = $this->Controller->pageTitle;
            } else {
                $title = __('Previous page');
            }

            array_unshift($history, array('uri' => $uri, 'title' => $title));
        }

        $this->saveHistory($historyKey, $history);
    }

    /**
     * Redirect to previous page
     *
     * @param integer $index Index in the stack to redirect to. Default is -1,
     * i.e. go back to the page before the last one. Useful for after adding or
     * editing something, you want to go back to index. Use 0 if in an action that
     * never renders anything, e.g. delete.
     * @param mixed $default Could be an array or string - anything that
     * Controller::redirect will accept. Used if index of history stack is
     * unavailable for any reason.
     */
    public function back($index=-1, $historyKey=null, $default=null)
    {
        $history = $this->getHistory($historyKey);

        $index = abs($index);

        if (isset($history[$index]['uri'])) {
            $redirect = $history[$index]['uri'];
        } elseif ($default) {
            $redirect = $default;
        } else {
            $redirect = array('action' => 'index');
        }

        $this->Controller->redirect($redirect);
    }

    public function getHistory($historyKey)
    {
        if (empty($historyKey)) {
            $historyKey = $this->settings['defaultHistoryKey'];
        }
        $key = "{$this->settings['sessionKey']}.{$historyKey}";
        if ($this->Session->check($key)) {
            $history = $this->Session->read($key);
        } else {
            $history = array();
        }

        return $history;
    }

    public function saveHistory($historyKey, $history)
    {
        if (empty($historyKey)) {
            $historyKey = $this->settings['defaultHistoryKey'];
        }
        $key = "{$this->settings['sessionKey']}.{$historyKey}";
        $this->Session->write($key, $history);
    }

    public function getCurrentUri()
    {
        $uri = null;
        if (isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
        }

        if (!is_string($uri) || empty($uri)) {
            false;
        }

        return $uri;
    }

}
