<?php

namespace Garlic\User\Service\User;

use Garlic\User\Form\Type\AvatarType;
use Garlic\User\Traits\FormHelperTrait;
use Garlic\User\Traits\UserHelperTrait;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class Avatar
 */
class Avatar
{
    use UserHelperTrait;
    use FormHelperTrait;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var ObjectManager
     */
    private $entityManager;

    /**
     * @var Request
     */
    private $request;

    /**
     * Avatar constructor.
     *
     * @param FormFactoryInterface $formFactory
     * @param TranslatorInterface  $translator
     * @param RouterInterface      $router
     * @param string               $rootDir
     * @param ObjectManager        $entityManager
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        TranslatorInterface $translator,
        RouterInterface $router,
        string $rootDir,
        ObjectManager $entityManager
    ) {
        $this->formFactory = $formFactory;
        $this->translator = $translator;
        $this->router = $router;
        $this->rootDir = $rootDir;
        $this->entityManager = $entityManager;
    }

    /**
     * Set Request
     *
     * @param Request $request
     *
     * @return Avatar
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Save user's avatar
     *
     * @param UserInterface $user
     *
     * @return JsonResponse
     */
    public function changeAvatar(UserInterface $user)
    {
        $form = $this->formFactory
            ->create(AvatarType::class, $user);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $user->getFile();
            $fileName = md5($user->getId()).'.'.$file->guessExtension();
            $file->move(
                $this->rootDir.'/../'.getenv('AVATAR_DIRECTORY').'/'.$this->generatePathByName(
                    $fileName
                ).'/',
                $fileName
            );

            $user->setProfilePicture($fileName);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return new JsonResponse(
                [
                    'message' => 'user_avatar_update_success',
                    'user'    => $this->getUserData($user),
                ],
                JsonResponse::HTTP_ACCEPTED
            );
        }

        return new JsonResponse(
            [
                'errors' => $this->getErrorMessages($form),
            ],
            JsonResponse::HTTP_BAD_REQUEST
        );
    }

    /**
     * Remove user's avatar
     *
     * @param UserInterface $user
     *
     * @return JsonResponse
     */
    public function removeAvatar(UserInterface $user)
    {
        $this->entityManager->persist(
            $user->setProfilePicture('')
        );
        $this->entityManager->flush();

        return new JsonResponse(
            [
                'message' => 'user_avatar_remove_success',
                'user'    => $this->getUserData($user),
            ],
            JsonResponse::HTTP_OK
        );
    }
}
