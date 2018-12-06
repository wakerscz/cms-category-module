<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */


namespace Wakers\CategoryModule\Component\Frontend\Modal;


interface IModal
{
    /**
     * @return Modal
     */
    public function create() : Modal;
}