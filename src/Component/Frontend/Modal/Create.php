<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */


namespace Wakers\CategoryModule\Component\Frontend\Modal;


trait Create
{
    /**
     * @var IModal
     * @inject
     */
    public $ICategory_Modal;


    /**
     * @return object
     */
    protected function createComponentCategoryModal() : object
    {
        $control = $this->ICategory_Modal->create();

        $control->onSave[] = function () use ($control)
        {
            $control->redrawControl('modal'); // Snippet Area
            $control->redrawControl('form');
            $control->redrawControl('summary');

            $this->getComponent('structureRecipeModal')->redrawControl('recipeForm');
        };

        $control->onOpen[] = function () use ($control)
        {
            $control->redrawControl('modal'); // SA
            $control->redrawControl('form');
        };

        return $control;
    }
}