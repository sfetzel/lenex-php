<?php
/**
 * This file is part of the lenex-php package.
 *
 * The Lenex file format is created by Swimrankings.net
 *
 * (c) Leon Verschuren <lverschuren@hotmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace leonverschuren\Lenex\Model;

class Pool
{
    /** @var string */
    public $name;

    /** @var int */
    public $laneMax;

    /** @var int */
    public $laneMin;

    /** @var int */
    public $temperature;

    /** @var string */
    public $type;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return int
     */
    public function getLaneMax()
    {
        return $this->laneMax;
    }

    /**
     * @param int $laneMax
     * @return $this
     */
    public function setLaneMax($laneMax)
    {
        $this->laneMax = $laneMax;

        return $this;
    }

    /**
     * @return int
     */
    public function getLaneMin()
    {
        return $this->laneMin;
    }

    /**
     * @param int $laneMin
     * @return $this
     */
    public function setLaneMin($laneMin)
    {
        $this->laneMin = $laneMin;

        return $this;
    }

    /**
     * @return int
     */
    public function getTemperature()
    {
        return $this->temperature;
    }

    /**
     * @param int $temperature
     * @return $this
     */
    public function setTemperature($temperature)
    {
        $this->temperature = $temperature;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }
}
