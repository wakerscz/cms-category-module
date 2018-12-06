<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */


namespace Wakers\CategoryModule\Component\Frontend\RemoveModal;


trait Create
{
    /**
     * @var IRemoveModal
     * @inject
     */
    public $ICategory_RemoveModal;


    /**
     * Modální okno pro odstranění kategorie
     * @return RemoveModal
     */
    protected function createComponentCategoryRemoveModal() : object
    {
        $control = $this->ICategory_RemoveModal->create();

        $control->onRemove[] = function ()
        {
            $this->getComponent('categoryModal')->redrawControl('modal'); // SA
            $this->getComponent('categoryModal')->redrawControl('form');
            $this->getComponent('categoryModal')->redrawControl('summary');

            $this->getComponent('structureRecipeModal')->redrawControl('form');
        };

        $control->onOpen[] = function () use ($control)
        {
            $control->redrawControl('modal');
        };

        return $control;
    }
}