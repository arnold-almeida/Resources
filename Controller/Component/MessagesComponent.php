<?php

class MessagesComponent extends Component
{

    public $name = 'Messages';

    public $messages = array();

    public $hasCollectedValidationErrors = false;

    public $components = array('Session');

    /**
     * Wrapper for setting 'flash' messages.
     * You may call several times, they will be joined together in beforeRender().
     *
     * @param string $type Accepts: 'error', 'notice', 'success'
     *
     */
    public function add($message, $type = 'error')
    {
        if (empty($this->messages[$type])) {
            $this->messages[$type] = array();
        }

        if ($type == 'error') {
            $validationErrors = $this->validationErrors();

            //  Append validation errors to last 'error' message
            if (!empty($validationErrors)) {

                $message .= '<br/><ul><li>' . implode('</li><li>', $validationErrors) . '</li></ul>';
            }
        }

        $this->messages[$type][] = $message;
        $this->_persistToSession();
    }

    // @todo: Add validation errors and existing session messages
    public function _persistToSession()
    {

        //  List of all messages collected
        $messages = array();

        //  Get any existing messages
        $existing = $this->Session->read('Message');
        if (!empty($existing)) {
            foreach ($existing as $key => $message) {
                if (!isset($messages[$key])) {
                    $messages[$key] = array();
                }
                if (isset($message['message'])) {
                    $messages[$key][] = $message;
                } else {
                    foreach ($message as $subkey => $submessage) {
                        $messages[$key][] = $submessage;
                    }
                }
            }
        }

        //  Append current messages to list
        foreach ($this->messages as $key => $submessages) {
            if (empty($submessages)) {
                continue;
            }
            if (!isset($messages[$key])) {
                $messages[$key] = array();
            }
            foreach ($submessages as $submessage) {
                $messages[$key][] = array('message' => $submessage);
            }
        }

        $validationErrors = $this->validationErrors();

        //  Append validation errors to last 'error' message
        if (!empty($messages['error']) && !empty($validationErrors)) {
            $last = count($messages['error']) - 1;
            $existingMessage = $messages['error'][$last]['message'];
            $messages['error'][$last]['message'] = $existingMessage . '<br/><ul><li>' . implode('</li><li>', $validationErrors) . '</li></ul>';
        }

        $this->Session->write('Message', $messages);

        // Need to clear the already stored messages
        $this->messages = array();
    }

    public function getValidationErrors()
    {
        $this->hasCollectedValidationErrors = true;
        //  Find validation errors
        $validationErrors = array();
        $models = ClassRegistry::keys();
        foreach ($models as $currentModel) {
            if (ClassRegistry::isKeySet($currentModel)) {
                $currentObject = ClassRegistry::getObject($currentModel);
                if (is_a($currentObject, 'Model') && !empty($currentObject->validationErrors)) {
                    $validationErrors[Inflector::camelize($currentModel)] =& $currentObject->validationErrors;
                }
            }
        }

        $errors = array();
        foreach ($validationErrors as $model => $modelErrors) {
            foreach ($modelErrors as $fieldName => $error) {
                if (is_string($error)) {
                    $errors[] = Inflector::humanize($fieldName) . ': ' . $error;
                }
            }
        }

        return $errors;
    }

    private function validationErrors()
    {
        if ($this->hasCollectedValidationErrors == false) {
            return $this->getValidationErrors();
        }

        return false;
    }
}
