<?php
namespace Swissup\Core\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Config\ScopeInterface;

/**
 * Emulates callback inside some area code and scope.
 * It is used for CLI commands which should work with data available only in some scope.
 */
class EmulatedAreaProcessor
{
    /**
     * The application scope manager.
     *
     * @var \Magento\Framework\Config\ScopeInterface
     */
    private $scope;

    /**
     * The application state manager.
     *
     * @var \Magento\Framework\App\State
     */
    private $state;

    /**
     *
     * @var string
     */
    private $areaCode = Area::AREA_ADMINHTML;

    /**
     * @param \Magento\Framework\Config\ScopeInterface $scope The application scope manager
     * @param \Magento\Framework\App\State $state The application state manager
     */
    public function __construct(ScopeInterface $scope, State $state)
    {
        $this->scope = $scope;
        $this->state = $state;
    }

    /**
     *
     * @param string $areaCode
     */
    public function setArea($areaCode)
    {
        $this->areaCode = $areaCode;
        return $this;
    }

    /**
     * Emulates callback inside adminhtml area code and adminhtml scope.
     *
     * Returns the return value of the callback.
     *
     * @param callable $callback The callable to be called
     * @param array $params The parameters to be passed to the callback, as an indexed array
     * @param string $areaCode [global|frontend|adminhtml]
     * @return bool|int|float|string|array|null - as the result of this method is the result of callback,
     * you can use callback only with specified in this method return types
     * @throws \Exception The exception is thrown if the parameter $callback throws an exception
     */
    public function process(callable $callback, array $params = [], $areaCode = null)
    {
        $currentScope = $this->scope->getCurrentScope();

        if ($areaCode != null) {
            $this->areaCode = $areaCode;
        }

        try {
            return $this->state->emulateAreaCode($this->areaCode, function () use ($callback, $params) {
                $this->scope->setCurrentScope($this->areaCode);
                return call_user_func_array($callback, $params);
            });
        } catch (\Exception $exception) {
            throw $exception;
        } finally {
            $this->scope->setCurrentScope($currentScope);
        }
    }
}
