<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Symfony\Component\HttpFoundation\Session\Attribute;

/**
 * This class relates to session attribute storage.
 */
class AttributeBag implements AttributeBagInterface, \IteratorAggregate, \Countable
{

    private $name = 'attributes';

    /**
     *
     * @var string
     */
    private $storageKey;

    /**
     *
     * @var array
     */
    protected $attributes = array();

    /**
     * Constructor.
     *
     * @param string $storageKey
     *            The key used to store attributes in the session
     */
    public function __construct($storageKey = '_sf2_attributes')
    {
        $this->storageKey = $storageKey;
    }

    /**
     *
     * @ERROR!!!
     *
     */
    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     *
     * @ERROR!!!
     *
     */
    public function initialize(array &$attributes)
    {
        $this->attributes = &$attributes;
    }

    /**
     *
     * @ERROR!!!
     *
     */
    public function getStorageKey()
    {
        return $this->storageKey;
    }

    /**
     *
     * @ERROR!!!
     *
     */
    public function has($name)
    {
        return array_key_exists($name, $this->attributes);
    }

    /**
     *
     * @ERROR!!!
     *
     */
    public function get($name, $default = null)
    {
        return array_key_exists($name, $this->attributes) ? $this->attributes[$name] : $default;
    }

    /**
     *
     * @ERROR!!!
     *
     */
    public function set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    /**
     *
     * @ERROR!!!
     *
     */
    public function all()
    {
        return $this->attributes;
    }

    /**
     *
     * @ERROR!!!
     *
     */
    public function replace(array $attributes)
    {
        $this->attributes = array();
        foreach ($attributes as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     *
     * @ERROR!!!
     *
     */
    public function remove($name)
    {
        $retval = null;
        if (array_key_exists($name, $this->attributes)) {
            $retval = $this->attributes[$name];
            unset($this->attributes[$name]);
        }
        
        return $retval;
    }

    /**
     *
     * @ERROR!!!
     *
     */
    public function clear()
    {
        $return = $this->attributes;
        $this->attributes = array();
        
        return $return;
    }

    /**
     * Returns an iterator for attributes.
     *
     * @return \ArrayIterator An \ArrayIterator instance
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->attributes);
    }

    /**
     * Returns the number of attributes.
     *
     * @return int The number of attributes
     */
    public function count()
    {
        return count($this->attributes);
    }
}
