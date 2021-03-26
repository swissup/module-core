<?php

namespace Swissup\Core\Model\Module;

class MessageLogger
{
    protected $messages = array(
        'errors'  => array(),
        'notices' => array(),
        'success' => array()
    );

    /**
     * @param string $group
     * @param mixed $error array or string with error message
     * <pre>
     *  message required
     *  trace   optional
     * </pre>
     */
    public function addError($group, $error)
    {
        $this->messages['errors'][$group][] = $error;
    }

    public function getErrors()
    {
        return $this->messages['errors'];
    }

    /**
     * @param string $group
     * @param string $notice
     */
    public function addNotice($group, $notice)
    {
        $this->messages['notices'][$group][] = $notice;
    }

    public function getNotices()
    {
        return $this->messages['notices'];
    }

    /**
     * @param string $group
     * @param string $success
     */
    public function addSuccess($group, $success)
    {
        $this->messages['success'][$group][] = $success;
    }

    public function getSuccess()
    {
        return $this->messages['success'];
    }
}
