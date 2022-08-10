<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Adshares\CmsBundle\Cms\Cms;
use Adshares\CmsBundle\Cms\FileUploader;
use Adshares\CmsBundle\Command\CreateUserCommand;
use Adshares\CmsBundle\Controller\AssetController;
use Adshares\CmsBundle\Controller\ContentController;
use Adshares\CmsBundle\Controller\SecurityController;
use Adshares\CmsBundle\EventListener\CmsListener;
use Adshares\CmsBundle\Repository\ContentRepository;
use Adshares\CmsBundle\Repository\UserRepository;
use Adshares\CmsBundle\Translation\TranslationLoader;
use Adshares\CmsBundle\Twig\AssetExtension;
use Adshares\CmsBundle\Twig\CmsExtension;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

return static function (ContainerConfigurator $container) {
    $container->services()

        ->set('cms', Cms::class)
            ->public()
            ->alias(Cms::class, 'cms')

        ->set('cms.controller.content', ContentController::class)
            ->public()
            ->args([
                service(TranslatorInterface::class),
                param('kernel.cache_dir'),
                service(Environment::class),
            ])
            ->tag('controller.service_arguments')

        ->set('cms.controller.asset', AssetController::class)
            ->public()
            ->args([service(FileUploader::class)])
            ->tag('controller.service_arguments')

        ->set('cms.controller.security', SecurityController::class)
            ->public()
            ->args([service(Environment::class)])
            ->tag('controller.service_arguments')

        ->set('cms.file_uploader', FileUploader::class)
            ->args([
                param('kernel.project_dir') . DIRECTORY_SEPARATOR . 'public',
                service(SluggerInterface::class),
                service(Packages::class),
                service(LoggerInterface::class),
            ])
            ->alias(FileUploader::class, 'cms.file_uploader')

        ->set(ContentRepository::class, ContentRepository::class)
            ->args([service(ManagerRegistry::class)])
            ->tag('doctrine.repository_service')

        ->set(UserRepository::class, UserRepository::class)
            ->args([
                service(ManagerRegistry::class),
                service(UserPasswordHasherInterface::class),
            ])
            ->tag('doctrine.repository_service')

        ->set(CmsListener::class, CmsListener::class)
            ->args([
                service(Cms::class),
                service(RouterInterface::class)
            ])
            ->tag('kernel.event_subscriber')

        ->set(CreateUserCommand::class, CreateUserCommand::class)
            ->args([
                service(UserRepository::class),
                service(UserPasswordHasherInterface::class)
            ])
            ->tag('console.command')

        ->set('twig.extension.asset', AssetExtension::class)
            ->args([
                service(Packages::class),
                service(ParameterBagInterface::class),
            ])
            ->tag('twig.extension')

        ->set('twig.extension.cms', CmsExtension::class)
            ->args([
                service(Cms::class),
                service(TranslatorInterface::class),
                service(RouterInterface::class),
            ])
            ->tag('twig.extension')

        ->set('translation.loader.content', TranslationLoader::class)
            ->args([
                service(ContentRepository::class),
                service(LoggerInterface::class),
            ])
            ->tag('translation.loader', ['alias' => 'bin'])
    ;
};
