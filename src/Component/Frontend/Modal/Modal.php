<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */

namespace Wakers\CategoryModule\Components\Frontend\Modal;


use Nette\Application\UI\Form;
use Propel\Runtime\Collection\ObjectCollection;
use Wakers\BaseModule\Component\Frontend\BaseControl;
use Wakers\BaseModule\Database\DatabaseException;
use Wakers\BaseModule\Util\AjaxValidate;
use Wakers\BaseModule\Util\NestedSet;
use Wakers\CategoryModule\Database\Category;
use Wakers\CategoryModule\Manager\CategoryManager;
use Wakers\CategoryModule\Repository\CategoryRepository;
use Wakers\LangModule\Database\Lang;
use Wakers\LangModule\Repository\LangRepository;
use Wakers\LangModule\Translator\Translate;


class Modal extends BaseControl
{
    use AjaxValidate;


    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;


    /**
     * @var CategoryManager
     */
    protected $categoryManager;


    /**
     * @var Lang
     */
    protected $activeLang;


    /**
     * @var Translate
     */
    protected $translate;


    /**
     * @var NestedSet
     */
    protected $nested;


    /**
     * @var callable
     */
    public $onSave = [];


    /**
     * @var callable
     */
    public $onOpen = [];


    /**
     * @var int
     * @persistent
     */
    public $categoryId;


    /**
     * Modal constructor.
     * @param CategoryRepository $categoryRepository
     * @param CategoryManager $categoryManager
     * @param Translate $translate
     * @param LangRepository $langRepository
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        CategoryManager $categoryManager,
        Translate $translate,
        LangRepository $langRepository
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->categoryManager = $categoryManager;
        $this->translate = $translate;

        $this->activeLang = $langRepository->getActiveLang();
        $this->nested = new NestedSet('Name');
    }


    /**
     * Render
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function render() : void
    {
        $langRoot = $this->categoryRepository->findLangRoot($this->activeLang);

        $categories = [];

        if ($langRoot && $langRoot->getDescendants() instanceof ObjectCollection)
        {
            $root = $this->categoryRepository->findDescendants($langRoot);
            $left = (count($root) > 0) ? $root[0]->getLeftValue() - 1 : 0;
            $categories = $this->nested->getTree($root, $left);
        }

        $this->template->categories = $categories;
        $this->template->setFile(__DIR__ . '/templates/modal.latte');
        $this->template->render();
    }


    /**
     * @return Form
     * @throws \Propel\Runtime\Exception\PropelException
     */
    protected function createComponentForm() : Form
    {
        $form = new Form;

        $form->addSelect('parentId', NULL)
            ->setRequired($this->translate->translate('Parent category is required.'));

        $form->addText('name')
            ->setRequired($this->translate->translate('Category name is required.'))
            ->addRule(Form::MIN_LENGTH, $this->translate->translate('Minimal length of name is %count% characters.', ['count' => 3]), 3)
            ->addRule(Form::MAX_LENGTH, $this->translate->translate('Maximal length of name is %count% characters.', ['count' => 64]), 64);

        $form->addText('slug')
            ->setRequired($this->translate->translate('Slug is required.'))
            ->addRule(Form::MIN_LENGTH, $this->translate->translate('Minimal length of slug is %count% characters.', ['count' => 3]), 3)
            ->addRule(Form::MAX_LENGTH, $this->translate->translate('Maximal length of slug is %count% characters.', ['count' => 64]), 64)
            ->addRule(Form::PATTERN,  $this->translate->translate('Slug can contain only a-z 0-9 and dash characters.'), '[a-z0-9\-]*');

        $form->addHidden('id', 0);

        $form->addSubmit('save');


        $langRoot = $this->categoryRepository->findLangRoot($this->activeLang);
        $category = NULL;

        if ($this->categoryId)
        {
            $category = $this->categoryRepository->findOneById($this->categoryId);
        }

        $parents = [-1 => $this->translate->translate('Without parent category')];

        // Nastaví možné nadřazené kategorie
        if ($langRoot)
        {
            $parents += $this->getWithoutDescendants($langRoot, $category);
        }

        $form['parentId']->setItems($parents);


        // Nastaví výchozí hodnoty pro editaci
        if ($category)
        {
            $parentId = $category->getParent() === $langRoot ? -1 : $category->getParent()->getId();

            $form->setDefaults([
                'name' =>  $category->getName(),
                'slug' => $category->getSlug(),
                'parentId' => $parentId,
                'id' => $category->getId()
            ]);
        }


        $form->onValidate[] = function (Form $form) { $this->validate($form); };
        $form->onSuccess[] = function (Form $form) { $this->success($form); };

        return $form;
    }


    /**
     * Success
     * @param Form $form
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function success(Form $form) : void
    {
        if ($this->presenter->isAjax())
        {
            $values = $form->getValues();

            try
            {
                if ($values->id > 0)
                {
                    $this->categoryManager->save($values->id, $values->parentId, $values->name, $values->slug);
                }
                else
                {
                    $this->categoryManager->add($this->activeLang, $values->parentId, $values->name, $values->slug);
                }

                $this->presenter->notificationAjax(
                    $this->translate->translate('Category saved'),
                    $this->translate->translate('Category was successfully saved.'),
                    'success',
                    FALSE
                );

                // Redraw form
                $langRoot = $this->categoryRepository->findLangRoot($this->activeLang);
                $parents = [-1 => $this->translate->translate('Without parent category')];

                // Nastaví možné nadřazené kategorie
                if ($langRoot)
                {
                    $parents += $this->getWithoutDescendants($langRoot, NULL);
                }

                $form['parentId']->setItems($parents);

                $form->reset();
                $this->onSave();
            }

            catch (DatabaseException $exception)
            {
                $this->presenter->notificationAjax(
                    $this->translate->translate('Error'),
                    $exception->getMessage(),
                    'error'
                );
            }
        }
    }


    /**
     * @param Category $root
     * @param Category|NULL $excludeBy
     * @return array
     * @throws \Exception
     */
    protected function getWithoutDescendants(Category $root, Category $excludeBy = NULL) : array
    {
        $tree = $this->categoryRepository->findDescendants($root);
        $left = (count($tree) > 0) ? $tree[0]->getLeftValue() - 1 : 0;

        $treeArray = $this->nested->getTree($tree, $left);
        $treeSorted = $this->nested->getFlatCollection($treeArray);

        $excluded = [];

        if ($excludeBy)
        {
            $excluded = count($excludeBy->getDescendants()) > 0 ? $excludeBy->getDescendants()->toKeyIndex() : [];
            $excluded[$excludeBy->getId()] = $excludeBy;
        }

        $parents = [];

        foreach ($treeSorted as $excludeBy)
        {
            if (!key_exists($excludeBy->getId(), $excluded))
            {
                $parents[$excludeBy->getId()] = str_repeat('––', $excludeBy->getLevel() - 2) . ' ' . $excludeBy->getName();
            }
        }

        return $parents;
    }


    /**
     * Handler pro editaci
     * @param int $id
     */
    public function handleEdit(int $id)
    {
        if ($this->presenter->isAjax())
        {
            $this->categoryId = $id;
            $this->onOpen();
        }
    }
}