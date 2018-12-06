/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */

$(function ()
{
    $.nette.ext(
    {
        load: function ()
        {
            var $form = $('#wakers_category_form'),
                $inputName = $form.find('input[name="name"]'),
                $inputSlug = $form.find('input[name="slug"]');

            var isNew = $inputSlug.val().length === 0;

            $inputName.on('change keyup', function ()
            {
                if (isNew)
                {
                    var text = Nette.webalize($inputName.val());

                    $inputSlug.val(text);
                }
            });
        }
    });
});