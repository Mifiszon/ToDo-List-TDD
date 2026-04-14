<?php
/**
 * User type.
 */

namespace App\Form\Type;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class UserType.
 */
class UserType extends AbstractType
{
    /**
     * Builds the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array<string, mixed> $options Form options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = null === $options['data']->getId();

        $builder
            ->add('email', EmailType::class, [
                'label' => 'label.email',
                'disabled' => !$isNew,
                'attr' => ['maxlength' => 180],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'label.password',
                'mapped' => false,
                'required' => $isNew,
                'constraints' => $isNew ? [
                    new NotBlank(message: 'message.password_required'),
                    new Length(min: 6, minMessage: 'message.password_too_short'),
                ] : [],
                'help' => $isNew ? '' : 'message.leave_empty_to_keep_current',
            ]);
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }

    /**
     * Returns the prefix of the template block name for this type.
     *
     * @return string The prefix of the template block name
     */
    public function getBlockPrefix(): string
    {
        return 'user';
    }
}
