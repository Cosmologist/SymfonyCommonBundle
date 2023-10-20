<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;

class FormBuilderUtils
{
    /**
     * Replace FormBuilder field.
     *
     * Нельзя изменить настройки полей после добавления в FormBuilder, но можно создать дубликат поля с нужными настройками
     * и заменить им поле в FormBuilder, что данный метод и делает.
     *
     * @param FormBuilderInterface $formBuilder The form builder
     * @param string               $name        The field name
     * @param string|null          $type        The field type
     * @param array                $options     The field options
     *
     * @todo translate
     */
    public static function replace(FormBuilderInterface $formBuilder, string $name, string $type = null, array $options = [])
    {
        /**
         * Есть соблазн - объединить переданные опции с опциями поля находящегося в FormBuilder - но так делать нельзя,
         * так как при добавлении поля в FormBuilder, некоторые опции назначаются под капотом самого FormBuilder и если их
         * мержить, то могут возникнуть конфликты, например:
         * Добавлям поле с типом DateTimeType, без дополнительных опций, FormBuilder автоматически при добавлении проставит опцию 'compaund' => true,
         * тепрь мы создаем одноименно поле с таким же типом, но с опцией 'widget' => 'single_text' и если мы смержим опции, то результатирующий набор оппций будет такой:
         * [..., 'widget' => 'single_text', ..., 'compound' => true, ...], что вызовет ошибки, нарпимер, при валидации.
         */
        $formBuilder->add(
            $formBuilder->create(
                $name,
                $type ?? get_class($formBuilder->get($name)->getType()->getInnerType()),
                $options)
        );
    }
}
