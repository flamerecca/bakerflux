<?php

namespace Flamerecca\Bakerflow\Generators;


use Illuminate\Support\Collection;

class Generator
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var Collection
     */
    private $replaces;

    /**
     * Generator constructor.
     *
     * @param string $path
     * @param Collection $replaces
     */
    public function __construct(string $path, Collection $replaces)
    {
        $this->path = $path;
        $this->replaces = $replaces;
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $contents = file_get_contents($this->path);
        foreach ($this->replaces as $search => $replace) {
            $contents = str_replace(
                '{{' . strtoupper($search) . '}}',
                $replace,
                $contents
            );
        }

        return $contents;
    }
}