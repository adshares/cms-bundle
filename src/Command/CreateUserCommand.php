<?php

namespace Adshares\CmsBundle\Command;

use Adshares\CmsBundle\Repository\UserRepository;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'cms:users:create',
    description: 'Creates a new user',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'User email address')
            ->addArgument('name', InputArgument::OPTIONAL, 'User name')
            ->addArgument('password', InputArgument::OPTIONAL, 'User password')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Add ADMIN role')
            ->addOption('editor', null, InputOption::VALUE_NONE, 'Add EDITOR role');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email') ?? $io->ask('Email address');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Invalid email address.');
        }
        $user = $this->userRepository->findByEmail($email);
        $name = $input->getArgument('name');
        $password = $input->getArgument('password');
        $role = 'USER';
        if ($input->getOption('admin')) {
            $role = 'ADMIN';
        } elseif ($input->getOption('editor')) {
            $role = 'EDITOR';
        }

        if (null === $user) {
            $name = $name ?? $io->ask('Name', substr($email, 0, strpos($email, '@')));
            if (empty($name)) {
                throw new RuntimeException('Name cannot be empty.');
            }
            $random = str_replace(['+', '/', '='], ['x', 'y', ''], base64_encode(random_bytes(8)));
            $password = $password ?? $io->ask('Password', $random);
            if (empty($password)) {
                throw new RuntimeException('Password cannot be empty.');
            }
            $user = $this->userRepository->createUser($email, $name, $password, ['ROLE_' . $role]);
            $action = 'created';
        } else {
            if (!empty($name)) {
                $user->setName($name);
            }
            if (!empty($password)) {
                $user->setPassword($this->passwordHasher->hashPassword($user, $password));
            }
            $user->setRoles(['ROLE_' . $role]);
            $this->userRepository->add($user, true);
            $action = 'changed';
        }

        $io->success([
            sprintf('%s %s (%s) has been %s', $role, $user->getEmail(), $user->getName(), $action),
            sprintf('Password: %s', !empty($password) ? $password : '[Not changed]'),
        ]);

        return Command::SUCCESS;
    }
}
