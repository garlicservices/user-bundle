<?php

namespace Garlic\User\Traits;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Trait FormHelperTrait
 */
trait FormHelperTrait
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * Return form error messages
     *
     * @param FormInterface $form
     *
     * @return array
     */
    protected function getErrorMessages(FormInterface $form)
    {
        if (!$this->translator instanceof TranslatorInterface) {
            $this->translator = $this->get('translator');
        }

        $errors = [];
        foreach ($form->getErrors() as $error) {
            $errors[] = $this->translator
                ->trans($error->getMessageTemplate(), [], 'validators', 'en');
        }

        /** @var FormInterface $child */
        foreach ($form->all() as $child) {
            if (!$child->isValid()) {
                $errors[$child->getName()] = $this->getErrorMessages($child);
            }
        }

        return $errors;
    }
}
