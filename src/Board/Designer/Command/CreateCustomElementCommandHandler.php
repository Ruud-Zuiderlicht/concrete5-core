<?php

namespace Concrete\Core\Board\Designer\Command;

use Concrete\Core\Entity\Board\Board;
use Concrete\Core\Entity\Board\Designer\CustomElement;
use Concrete\Core\User\User;
use Doctrine\ORM\EntityManager;

class CreateCustomElementCommandHandler
{

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var User
     */
    protected $user;

    public function __construct(User $user, EntityManager $entityManager)
    {
        $this->user = $user;
        $this->entityManager = $entityManager;
    }

    public function handle(CreateCustomElementCommand $command)
    {
        if ($this->user->isRegistered()) {
            $author = $this->user->getUserInfoObject()->getEntityObject();
        }

        $element = new CustomElement();
        $element->setElementName($command->getElementName());
        $element->setCreationMethod($command->getCreationMethod());
        $element->setDateCreated(time());
        $element->setAuthor($author);

        $this->entityManager->persist($element);
        $this->entityManager->flush();
        
        return $element;
    }

    
}
