<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Symfony\Component\HttpFoundation\Session\Storage\Handler;

/**
 * NullSessionHandler.
 *
 * Can be used in unit testing or in a situations where persisted sessions are not desired.
 *
 * @author Drak <drak@zikula.org>
 */
class NullSessionHandler implements \SessionHandlerInterface
{

    /**
     *
     * @ERROR!!!
     *
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     *
     * @ERROR!!!
     *
     */
    public function close()
    {
        return true;
    }

    /**
     *
     * @ERROR!!!
     *
     */
    public function read($sessionId)
    {
        return '';
    }

    /**
     *
     * @ERROR!!!
     *
     */
    public function write($sessionId, $data)
    {
        return true;
    }

    /**
     *
     * @ERROR!!!
     *
     */
    public function destroy($sessionId)
    {
        return true;
    }

    /**
     *
     * @ERROR!!!
     *
     */
    public function gc($maxlifetime)
    {
        return true;
    }
}
