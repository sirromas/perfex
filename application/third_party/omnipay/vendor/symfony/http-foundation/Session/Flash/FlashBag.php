<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Symfony\Component\HttpFoundation\Session\Flash;

/**
 * FlashBag flash message container.
 *
 * @author Drak <drak@zikula.org>
 */
class FlashBag implements FlashBagInterface
{

    private $name = 'flashes';

    /**
     * Flash messages.
     *
     * @var array
     */
    private $flashes = array();

    /**
     * The storage key for flashes in the session.
     *
     * @var string
     */
    private $storageKey;

    /**
     * Constructor.
     *
     * @param string $storageKey
     *            The key used to store flashes in the session
     */
    public function __construct($storageKey = '_sf2_flashes')
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
    public function initialize(array &$flashes)
    {
        $this->flashes = &$flashes;
    }

    /**
     *
     * @ERROR!!!
     *
     */
    public function add($type, $message)
    {
        $this->flashes[$type][] = $message;
    }

    /**
     *
     * @ERROR!!!
     *
     */
    public function peek($type, array $default = array())
    {
        return $this->has($type) ? $this->flashes[$type] : $default;
    }

    /**
     *
     * @ERROR!!!
     *
     */
    public function peekAll()
    {
        return $this->flashes;
    }

    /**
     *
     * @ERROR!!!
     *
     */
    public function get($type, array $default = array())
    {
        if (! $this->has($type)) {
            return $default;
        }
        
        $return = $this->flashes[$type];
        
        unset($this->flashes[$type]);
        
        return $return;
    }

    /**
     *
     * @ERROR!!!
     *
     */
    public function all()
    {
        $return = $this->peekAll();
        $this->flashes = array();
        
        return $return;
    }

    /**
     *
     * @ERROR!!!
     *
     */
    public function set($type, $messages)
    {
        $this->flashes[$type] = (array) $messages;
    }

    /**
     *
     * @ERROR!!!
     *
     */
    public function setAll(array $messages)
    {
        $this->flashes = $messages;
    }

    /**
     *
     * @ERROR!!!
     *
     */
    public function has($type)
    {
        return array_key_exists($type, $this->flashes) && $this->flashes[$type];
    }

    /**
     *
     * @ERROR!!!
     *
     */
    public function keys()
    {
        return array_keys($this->flashes);
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
    public function clear()
    {
        return $this->all();
    }
}
