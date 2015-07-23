<?php
/**
 * \Gb\Log2\AbstractWriter
 *
 * @author Gilles Bouthenot
 */

namespace Gb\Log2\Writer;

abstract class AbstractWriter
{
    protected $fLogLocation = null;
    protected $format = null;
    protected $levelMin = null;
    protected $levelMax = null;
    protected $logSeparator = null;

    abstract public function write(array $logdata);

    public function log(array $logdata)
    {
        if ($this->filterOut($logdata)) {
            return;
        }

        $this->write($logdata);
    }

    public function filterOut(array $logdata)
    {
        $level = $logdata["level"];
        if (null !== $this->levelMin && $level < $this->levelMin) {
            return true;
        }
        if (null !== $this->levelMax && $level > $this->levelMax) {
            return true;
        }
        return false;
    }

    public function format($arg = "__get")
    {
        if ("__get" === $arg) {
            return $this->format;
        } else {
            $this->format = $arg;
        }
    }

    public function levelMin($arg = "__get")
    {
        if ("__get" === $arg) {
            return $this->levelMin;
        } else {
            $this->levelMin = $arg;
        }
    }

    public function levelMax($arg = "__get")
    {
        if ("__get" === $arg) {
            return $this->levelMax;
        } else {
            $this->levelMax = $arg;
        }
    }

    public function logSeparator($arg = "__get")
    {
        if ("__get" === $arg) {
            return $this->logSeparator;
        } else {
            $this->logSeparator = $arg;
        }
    }
}
