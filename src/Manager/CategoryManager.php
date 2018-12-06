<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */

namespace Wakers\CategoryModule\Manager;


use Nette\Utils\Strings;
use Wakers\BaseModule\Database\AbstractDatabase;
use Wakers\BaseModule\Database\DatabaseException;
use Wakers\CategoryModule\Database\Category;
use Wakers\CategoryModule\Repository\CategoryRepository;
use Wakers\LangModule\Database\Lang;
use Wakers\LangModule\Translator\Translate;


class CategoryManager extends AbstractDatabase
{
    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;


    /**
     * @var Translate
     */
    protected $translate;


    /**
     * CategoryManager constructor.
     * @param CategoryRepository $categoryRepository
     * @param Translate $translate
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        Translate $translate
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->translate = $translate;
    }


    /**
     * @param Lang $lang
     * @param int $parentId
     * @param string $name
     * @param string $slug
     * @throws DatabaseException
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function add(Lang $lang, int $parentId, string $name, string $slug) : void
    {
        $slug = Strings::webalize($slug);

        $parent = $this->categoryRepository->findOneById($parentId);
        $categoryBySlug = $this->categoryRepository->findOneBySlug($slug);

        // Zkontroluj, případně vytvoř roots
        if ($parentId < 1)
        {
            $parent = $this->categoryRepository->findLangRoot($lang);

            if (!$parent)
            {
                $parent = $this->makeLangRoot($lang);
            }
        }

        if ($categoryBySlug)
        {
            throw new DatabaseException($this->translate->translate("Slug: '%slug%' already exists.", ['slug' => $slug]));
        }

        if (!$parent)
        {
            throw new DatabaseException($this->translate->translate("Parent category with ID: '%id%' does not exists.", ['id' => $parentId]));
        }

        $category = new Category;
        $category->setLang($lang);
        $category->setName($name);
        $category->setSlug($slug);

        $category->setParent($parent);
        $category->insertAsLastChildOf($parent);

        $category->save();
    }


    /**
     * @param int $id
     * @param int $parentId
     * @param string $name
     * @param string $slug
     * @throws DatabaseException
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function save(int $id, int $parentId, string $name, string $slug) : void
    {
        $category = $this->categoryRepository->findOneById($id);
        $categoryBySlug = $this->categoryRepository->findOneBySlug($slug);

        if ($categoryBySlug && $categoryBySlug !== $category)
        {
            throw new DatabaseException($this->translate->translate("Slug '%slug% already exists.'", ['slug' => $slug]));
        }

        if ($parentId < 1)
        {
            $parent = $this->categoryRepository->findLangRoot($category->getLang());
        }
        else
        {
            $parent = $this->categoryRepository->findOneById($parentId);
        }

        $category->setSlug($slug);
        $category->setName($name);

        $category->setParent($parent);
        $category->moveToLastChildOf($parent);
        $category->save();
    }


    /**
     * @param Category $category
     * @throws \Exception
     */
    public function delete(Category $category) : void
    {
        $parent = $category->getParent();
        $children = $category->getChildren();

        $this->getConnection()->beginTransaction();

        try
        {
            foreach ($children as $child)
            {
                $child->setParent($parent);
                $child->moveToLastChildOf($parent);
                $child->save();
            }

            $category->delete();
            $this->getConnection()->commit();
        }
        catch (\Exception $exception)
        {
            $this->getConnection()->rollBack();
            throw $exception;
        }
    }


    /**
     * @param Lang $lang
     * @return Category
     * @throws \Propel\Runtime\Exception\PropelException
     */
    protected function makeLangRoot(Lang $lang) : Category
    {
        $name = CategoryRepository::TREE_LANG_PREFIX . '-' . $lang->getName();
        $root = $this->categoryRepository->findRoot();

        if (!$root)
        {
            $root = new Category;
            $root->setName(CategoryRepository::TREE_LANG_PREFIX);
            $root->setSlug(CategoryRepository::TREE_LANG_PREFIX);
            $root->makeRoot();
            $root->save();
        }

        $category = new Category;
        $category->setLang($lang);
        $category->setParent($root);
        $category->setName($name);
        $category->setSlug($name);

        $category->insertAsLastChildOf($root);
        $category->save();

        return $category;
    }

}