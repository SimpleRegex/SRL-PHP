<?php

namespace SRL;

class Match
{
    /** @var string[] */
    protected $rawData = [];

    /** @var string[] */
    protected $attributes = [];

    public function __construct(array $data = [])
    {
        $this->rawData = $data;

        foreach ($data as $k => $v) {
            if (is_string($k)) {
                $this->attributes[$k] = $v;
            }
        }

        if (empty($this->attributes)) {
            array_shift($data);
            $this->attributes = array_values($data);
        }
    }

    /**
     * Get one match. If using named capture groups, you can use that name here.
     *
     * @param string|int $name Name or position of match.
     * @return string|null
     */
    public function get($name)
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * Get all matches.
     *
     * @return array
     */
    public function getMatches() : array
    {
        return $this->attributes;
    }
}