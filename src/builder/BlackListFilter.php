<?php

/**
 * Black list filter
 *
 * PHP version 5
 *
 * @category  DiffSniffer\Builder
 * @package   DiffSniffer\Builder
 * @author    Sergei Morozov <morozov@tut.by>
 * @copyright 2013 Sergei Morozov
 * @license   http://mit-license.org/ MIT Licence
 * @link      http://github.com/morozov/diff-sniffer-builder
 */
namespace builder;

/**
 * Black list filter
 *
 * PHP version 5
 *
 * @category  DiffSniffer\Builder
 * @package   DiffSniffer\Builder
 * @author    Sergei Morozov <morozov@tut.by>
 * @copyright 2013 Sergei Morozov
 * @license   http://mit-license.org/ MIT Licence
 * @link      http://github.com/morozov/diff-sniffer-builder
 */
class BlackListFilter extends \RecursiveFilterIterator
{
    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var array
     */
    protected $exclude = array();

    /**
     * Constructor
     *
     * @param \RecursiveIterator $it       Iterator to filter data from
     * @param array              $exclude  Excluded paths
     * @param string             $basePath Base path provided by inner iterators
     */
    public function __construct(\RecursiveIterator $it, array $exclude, $basePath = null)
    {
        $this->exclude = $exclude;
        if ($basePath === null) {
            $it->rewind();
            $basePath = (string) $it->current()->getPath();
        }
        $this->basePath = $basePath;
        parent::__construct($it);
    }

    /**
     * {@inheritDoc}
     */
    public function accept()
    {
        $current = $this->current();
        $path = $current->getPathName();
        $subPath = substr($path, strlen($this->basePath) + 1);
        return !in_array($subPath, $this->exclude);
    }

    /**
     * {@inheritDoc}
     */
    public function getChildren()
    {
        return new self(
            $this->getInnerIterator()->getChildren(),
            $this->exclude,
            $this->basePath
        );
    }
}
