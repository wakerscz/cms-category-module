<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */


namespace Wakers\CategoryModule\Component\Frontend\RemoveModal;


use Nette\Application\ForbiddenRequestException;
use Wakers\BaseModule\Component\Frontend\BaseControl;
use Wakers\CategoryModule\Database\Category;
use Wakers\CategoryModule\Manager\CategoryManager;
use Wakers\CategoryModule\Repository\CategoryRepository;
use Wakers\CategoryModule\Security\CategoryAuthorizator;


class RemoveModal extends BaseControl
{
    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;


    /**
     * @var CategoryManager
     */
    protected $categoryManager;


    /**
     * Entita načtena při otevření modálního okna
     * @var Category
     */
    protected $categoryEntity;


    /**
     * Callback volaný po odstranění uživatele
     * @var callable
     */
    public $onRemove = [];

    /**
     * @var callable
     */
    public $onOpen = [];


    /**
     * RemoveModal constructor.
     * @param CategoryRepository $categoryRepository
     * @param CategoryManager $categoryManager
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        CategoryManager $categoryManager
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->categoryManager = $categoryManager;
    }


    /**
     * Render
     */
    public function render() : void
    {
        $this->template->category = $this->categoryEntity;
        $this->template->setFile(__DIR__ . '/templates/removeModal.latte');
        $this->template->render();
    }


    /**
     * Handler pro otevření modálního okna a userEntity
     * @param int $id
     * @throws ForbiddenRequestException
     */
    public function handleOpen(int $id) : void
    {
        if ($this->presenter->isAjax())
        {
            $this->categoryEntity = $this->categoryRepository->findOneById($id);

            $this->presenter->handleModalToggle('show', '#wakers_category_remove_modal', FALSE);
            $this->onOpen();
        }
    }


    /**
     * Handler pro odstranění kategorie
     * @param int $id
     * @throws \Exception
     */
    public function handleRemove(int $id) : void
    {
        if ($this->presenter->isAjax() && $this->presenter->user->isAllowed(CategoryAuthorizator::RES_REMOVE))
        {
            $this->categoryEntity = $this->categoryRepository->findOneById($id);

            $this->categoryManager->delete($this->categoryEntity);

            $this->presenter->notificationAjax(
                'Kategorie odstaněna',
                "Kategorie '{$this->categoryEntity->getName()}' byla úspěšně odstaněna.",
                'success',
                FALSE
            );

            $this->presenter->handleModalToggle('hide', '#wakers_category_remove_modal', FALSE);

            $this->onRemove();
        }
    }
}