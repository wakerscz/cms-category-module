<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */


namespace Wakers\CategoryModule\Components\Frontend\RemoveModal;


interface IRemoveModal
{
    /**
     * @return RemoveModal
     */
    public function create() : RemoveModal;
}