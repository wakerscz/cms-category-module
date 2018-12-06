<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */

namespace Wakers\CategoryModule\Repository;


use Propel\Runtime\Collection\ObjectCollection;
use Wakers\CategoryModule\Database\CategoryQuery;
use Wakers\CategoryModule\Database\Category;
use Wakers\LangModule\Database\Lang;
use Wakers\StructureModule\Database\Recipe;


class CategoryRepository
{
    /**
     * Prefix pro roots
     */
    const TREE_LANG_PREFIX = 'root';


    /**
     * @param string $slug
     * @return Category|NULL
     */
    public function findOneBySlug(string $slug) : ?Category
    {
        return CategoryQuery::create()
            ->findOneBySlug($slug);
    }


    /**
     * @param int $id
     * @return Category|NULL
     */
    public function findOneById(int $id) : ?Category
    {
        return CategoryQuery::create()
            ->findOneById($id);
    }


    /**
     * @param Category $category
     * @return ObjectCollection|Category[]
     */
    public function findDescendants(Category $category) : ObjectCollection
    {
        $descendants = $category->getDescendants();

        if ($descendants instanceof ObjectCollection)
        {
            return $descendants;
        }

        return new ObjectCollection;
    }


    /**
     * @param Category $category
     * @return ObjectCollection|Category[]
     */
    public function findChildren(Category $category) : ObjectCollection
    {
        $children = $category->getChildren();

        if ($children instanceof ObjectCollection)
        {
            return $children;
        }

        return new ObjectCollection;
    }


    /**
     * @param Lang $lang
     * @return Category|NULL
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function findLangRoot(Lang $lang) : ?Category
    {
        return CategoryQuery::create()
            ->filterByLang($lang)
            ->filterByTreeLevel(1)
            ->findOne();
    }


    /**
     * @return Category|NULL
     */
    public function findRoot() : ?Category
    {
        return CategoryQuery::create()
            ->findRoot();
    }


    /**
     * @param Recipe $recipe
     * @return ObjectCollection|Category[]
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function findByRecipe(Recipe $recipe) : ObjectCollection
    {
        $categories = CategoryQuery::create()
            ->useRecipeCategoryAllowedQuery()
                ->filterByRecipe($recipe)
            ->endUse()
            ->findTree();

        $result = new ObjectCollection;

        foreach ($categories as $category)
        {
            $result->append($category);

            foreach ($category->getDescendants() as $descendant)
            {
                $result->append($descendant);
            }
        }

        return $result;
    }
}