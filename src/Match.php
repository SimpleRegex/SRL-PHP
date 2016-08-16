<?php

namespace SRL;

class Match
{
    protected $rawData = [];

    protected $name = null;
    protected $match = null;

    public function __construct(array $data = [])
    {
        $this->rawData = $data;

        foreach ($data as $k => $v) {
            if (is_string($k)) {
                $this->name = $k;
                $this->match = $v;

                return;
            }
        }

        $this->match = $data[1] ?? null;
    }

    /**
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getMatch()
    {
        return $this->match;
    }
}