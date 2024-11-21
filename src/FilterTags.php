<?php

namespace Nullai\Vista;

class FilterTags implements \Stringable
{
    protected array $original = [];
    protected array $tags = [];
    protected array $globalAttributes = [];

    public function __construct()
    {
        $this->original = [
            'tags' => $this->tags,
            'globalAttributes' => $this->globalAttributes,
        ];
    }

    public function addGlobalAttributes(array $attributes) : static
    {
        $this->globalAttributes = array_unique(array_merge($this->globalAttributes, $attributes));

        return $this;
    }

    public function removeGlobalAttributes(array $attributes) : static
    {
        $this->globalAttributes = array_diff($this->globalAttributes, $attributes);

        return $this;
    }

    public function resetGlobalAttributes() : static
    {
        $this->globalAttributes = $this->original['globalAttributes'];

        return $this;
    }

    public function add($tag, $attributes) : static
    {
        if(isset($this->tags[$tag])) {
            $this->tags[$tag] = array_merge($this->tags[$tag], $attributes);
        }
        else {
            $this->tags[$tag] = $attributes;
        }

        return $this;
    }

    public function remove($tag) : static
    {
        unset($this->tags[$tag]);

        return $this;
    }

    public function resetTags() : static
    {
        $this->globalAttributes = $this->original['tags'];

        return $this;
    }

    public function __toString() : string
    {
        $list = [];
        $g = $this->globalAttributes;

        foreach($this->tags as $tag => $attributes) {
            $list[] = $tag . (count($attributes) > 0 || !empty($g) ? ':' . implode('|', array_unique(array_merge($attributes, $g))) : '');
        }

        return implode(',', $list);
    }
}