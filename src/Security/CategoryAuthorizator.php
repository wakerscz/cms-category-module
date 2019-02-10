<?php
/**
 * Copyright (c) 2019 Wakers.cz
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 */


namespace Wakers\CategoryModule\Security;


use Wakers\BaseModule\Builder\AclBuilder\AuthorizatorBuilder;
use Wakers\UserModule\Security\UserAuthorizator;


class CategoryAuthorizator extends AuthorizatorBuilder
{
    const
        RES_CATEGORY_MODULE = 'CATEGORY_RES_MODULE',    // Celý modul
        RES_FORM = 'CATEGORY_RES_FORM',                 // Formulář
        RES_SUMMARY = 'CATEGORY_RES_SUMMARY',           // Přehled kategorií
        RES_REMOVE = 'CATEGORY_RES_REMOVE'              // Odstranění kategorie
    ;


    /**
     * Build ACL
     * @return array
     */
    public function create() : array
    {
        /*
         * Resources
         */
        $this->addResource(self::RES_CATEGORY_MODULE);
        $this->addResource(self::RES_FORM);
        $this->addResource(self::RES_SUMMARY);
        $this->addResource(self::RES_REMOVE);


        /*
         * Privileges
         */
        $this->allow(
            [
                UserAuthorizator::ROLE_EDITOR
            ], [
                self::RES_CATEGORY_MODULE,
                self::RES_FORM,
                self::RES_SUMMARY,
                self::RES_REMOVE
            ]
        );

        return parent::create();
    }
}