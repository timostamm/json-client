<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 15.05.18
 * Time: 14:23
 */

namespace TS\Web\JsonClient\Fixtures;


class Payload
{

    /** @var string */
    private $str;

    /** @var int */
    private $int;


    public function __construct(string $str, int $int)
    {
        $this->str = $str;
        $this->int = $int;
    }

    /**
     * @return string
     */
    public function getStr(): string
    {
        return $this->str;
    }

    /**
     * @param string $str
     */
    public function setStr(string $str): void
    {
        $this->str = $str;
    }

    /**
     * @return int
     */
    public function getInt(): int
    {
        return $this->int;
    }

    /**
     * @param int $int
     */
    public function setInt(int $int): void
    {
        $this->int = $int;
    }


}