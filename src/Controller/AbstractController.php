<?php

declare(strict_types=1);

namespace Spyck\EasyAdminExtension\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractController extends AbstractCrudController
{
    protected const string CLONE = 'entityId';

    public function createEntity(string $entityFqcn): object
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();

        if (null === $request) {
            return parent::createEntity($entityFqcn);
        }

        $entityId = $request->query->get(self::CLONE);

        if (null === $entityId) {
            return parent::createEntity($entityFqcn);
        }

        $entityManager = $this->container->get('doctrine')->getManager();

        $entity = $entityManager->getRepository($entityFqcn)->find($entityId);

        if (null === $entity) {
            throw $this->createNotFoundException('Entity not found');
        }

        return clone $entity;
    }

    protected function getCloneAction(): Action
    {
        $callback = function (object $entity): string {
            return $this->container->get(AdminUrlGenerator::class)
                ->setController(static::class)
                ->setAction(Action::NEW)
                ->unset(self::CLONE)
                ->set(self::CLONE, $entity->getId())
                ->generateUrl();
        };

        return Action::new('clone', 'Clone', 'fa fa-copy')
            ->linkToUrl($callback);
    }

    protected function redirectToIndex(): Response
    {
        $url = $this->container->get(AdminUrlGenerator::class)
            ->setController(static::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }
}
