<?php

require_once 'ErrorCode.php';

/**
 * 
 * @author Kotlyar Maksim kotlyar.maksim@gmail.com
 */
class UniversalErrorCatcher_Catcher
{
   /**
    * 
    * @var string
    */
    protected $memoryReserv = '';
    
    /**
     *
     * @var mixed
     */
    protected $callbacks = array();
    
    /**
     *
     * @param mixed $callback
     * 
     * @throws InvalidArgumentException if invalid callback provided
     * 
     * @return UniversalErrorHandler_Handler 
     */
    public function registerCallback($callback)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Invalid callback provided.');
        }
        
        $this->callbacks[] = $callback;
        
        return $this;
    }
    
    /**
     *
     * @param mixed $callbackToUnregister
     * 
     * @return UniversalErrorHandler_Handler 
     */
    public function unregisterCallback($callbackToUnregister)
    {
        foreach ($this->callbacks as $key => $callback) {
            if ($callbackToUnregister == $callback) {
                unset($this->callbacks[$key]);
            }
        }
        
        return $this;
    }
  
    /**
     *
     * @return void
     */
    public function start()
    {
        $this->memoryReserv = str_repeat('x', 1024 * 500);

        // it needs to be done to find out whether the error comes from the ordinary code or it is under @
        // it could be any less zero values
        0 == error_reporting() &&  @error_reporting(-1);

        set_error_handler(array($this, 'handleError'));
        register_shutdown_function(array($this, 'handleFatalError'));
        set_exception_handler(array($this, 'handleException'));
    }

    /**
     *
     * @param Exception $e
     *
     * @return void
     */
    public function handleException(Exception $e)
    {
        foreach ($this->callbacks as $callback) {
            call_user_func_array($callback, array($e));
        }
    }

    /**
     *
     * @param string $errno
     * @param string $errstr
     * @param string $errfile
     * @param string $errline
     *
     * @return ErrorException
     */
    public function handleError($errno, $errstr, $errfile, $errline)
    {
        $this->handleException(new ErrorException($errstr, 0, $errno, $errfile, $errline));

        return false;
    }

    /**
     *
     * @return void
     */
    public function handleFatalError()
    {
        $error = error_get_last();

        $skipHandling = 
          !$error || 
          !isset($error['type']) || 
          !in_array($error['type'], UniversalErrorHandler_ErrorCode::getFatals());
        if ($skipHandling) return;

        $this->freeMemory();

        @$this->handleError($error['type'], $error['message'], $error['file'], $error['line']);
    }

    /**
     *
     * @return void
     */
    protected function freeMemory()
    {
        unset($this->memoryReserv);
    }
}